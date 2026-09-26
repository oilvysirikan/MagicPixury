<?php

declare(strict_types=1);

namespace App\Jobs\Pixury;

use App\Domains\Entity\Enums\EntityEnum;
use App\Domains\Entity\Facades\Entity;
use App\Domains\Engine\Services\FalAIService;
use App\Models\User;
use App\Models\UserUsageCredit;
use App\Pixury\Creative\Models\Creation;
use App\Pixury\Creative\GenerationService;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PixuryGenerationJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public $timeout = 840; // 14 minutes - must be less than worker timeout (15 min) and queue retry_after (15 min)

    public $tries = 3;

    public $backoff = [30, 60, 120]; // Exponential backoff: 30s, 60s, 120s

    public function __construct(
        public int $creationId
    ) {}

    public function handle(GenerationService $generationService): void
    {
        $creation = Creation::find($this->creationId);

        if (!$creation) {
            Log::warning('Pixury creation not found', ['creation_id' => $this->creationId]);
            return;
        }

        // Idempotency: Only process if in pending or processing state
        if (!$this->canProcess($creation)) {
            Log::info('Pixury creation skipped - invalid state', [
                'creation_id' => $this->creationId,
                'status' => $creation->status,
                'attempt' => $this->attempts(),
            ]);
            return;
        }

        $user = User::find($creation->user_id);
        if (!$user) {
            $this->failCreation($creation, 'User not found');
            return;
        }

        // Transition to processing (idempotent - safe to call multiple times)
        $this->transitionToProcessing($creation);

        $startTime = microtime(true);

        try {
            $entity = $generationService->getEntityForTemplate(
                $creation->template_id ?? '',
                $creation->type
            );

            // FAL Request Idempotency: reuse existing request_id if present
            $falRequestId = $creation->fal_request_id;
            $isNewRequest = false;

            if (!$falRequestId) {
                $falRequestId = $this->generateWithFal($creation, $entity, $user);
                $isNewRequest = true;

                if (!$falRequestId) {
                    throw new Exception('Failed to initiate generation');
                }

                // Store request_id immediately so retries can resume
                $creation->update([
                    'fal_request_id' => $falRequestId,
                    'fal_entity'     => $entity->slug(),
                ]);
            } else {
                Log::info('Resuming existing FAL request', [
                    'creation_id' => $creation->id,
                    'fal_request_id' => $falRequestId,
                ]);
            }

            $result = $this->pollForResult($falRequestId, $entity);

            if (!$result || !isset($result['image'])) {
                throw new Exception('Generation failed or timed out');
            }

            $imageUrl = $result['image']['url'] ?? null;
            if (!$imageUrl) {
                throw new Exception('No result URL returned');
            }

            $generationTime = (int) (microtime(true) - $startTime);

            // Download and store asset BEFORE deducting credits
            $resultUrl = $generationService->storeGeneratedAsset($imageUrl, (string) $user->id, $creation->type);
            $thumbUrl = $creation->type === 'video'
                ? $generationService->generateThumbnail($imageUrl, (string) $user->id)
                : $resultUrl;

            // Credit Deduction Idempotency: only deduct if not already deducted
            $creditsUsed = $this->deductCreditsIfNeeded($creation, $user, $entity, $creation->options ?? []);

            $creation->markCompleted([
                'result_url'       => $resultUrl,
                'thumb_url'        => $thumbUrl,
                'fal_result'       => $result,
                'credits_used'     => $creditsUsed,
                'generation_time'  => $generationTime,
                'resolution'       => $result['size'] ?? null,
                'aspect_ratio'     => $this->extractAspectRatio($creation->options ?? []),
            ]);

            Log::info('Pixury generation completed', [
                'creation_id' => $creation->id,
                'user_id'     => $user->id,
                'entity'      => $entity->slug(),
                'credits'     => $creditsUsed,
                'time'        => $generationTime,
                'is_new_request' => $isNewRequest,
                'attempt'     => $this->attempts(),
            ]);

        } catch (Exception $e) {
            // Only mark failed if not already completed
            if (!$creation->isCompleted()) {
                $this->failCreation($creation, $e->getMessage());
            }

            Log::error('Pixury generation failed', [
                'creation_id' => $creation->id,
                'user_id'     => $user->id ?? null,
                'error'       => $e->getMessage(),
                'attempt'     => $this->attempts(),
            ]);
        }
    }

    /**
     * Check if job can process this creation.
     * Prevents duplicate processing on retries.
     */
    protected function canProcess(Creation $creation): bool
    {
        // Allow processing if pending or already processing (resume)
        // Block if already completed or failed
        if ($creation->isCompleted()) {
            Log::info('Creation already completed, skipping', ['creation_id' => $creation->id]);
            return false;
        }

        if ($creation->isFailed() && $this->attempts() >= $this->tries) {
            Log::info('Creation failed and max retries reached', ['creation_id' => $creation->id]);
            return false;
        }

        return true;
    }

    /**
     * Idempotent transition to processing state.
     * Uses atomic update to prevent race conditions.
     */
    protected function transitionToProcessing(Creation $creation): void
    {
        $updated = Creation::where('id', $creation->id)
            ->whereIn('status', ['pending', 'processing'])
            ->update([
                'status' => 'processing',
                'updated_at' => now(),
            ]);

        if (!$updated && !$creation->isProcessing()) {
            // Another process may have transitioned it - reload and check
            $creation->refresh();
            if (!$creation->isProcessing()) {
                throw new Exception('Could not transition to processing - current status: ' . $creation->status);
            }
        }

        $creation->refresh();
    }

    /**
     * Credit Deduction Idempotency:
     * - Primary guard: Creation.credits_used > 0 (per-Creation, collision-free)
     * - Only deduct if credits_used == 0
     * - Update Creation.credits_used after successful deduction
     * - DO NOT use time-window query on UserUsageCredit (collision risk)
     */
    protected function deductCreditsIfNeeded(Creation $creation, User $user, EntityEnum $entity, array $options): float
    {
        // Primary idempotency guard: per-Creation credits_used field
        // This is collision-free because each Creation has a unique ID
        if (($creation->credits_used ?? 0) > 0) {
            Log::info('Credits already deducted for this Creation, skipping', [
                'creation_id' => $creation->id,
                'credits_used' => $creation->credits_used,
            ]);
            return (float) $creation->credits_used;
        }

        // Deduct credits atomically (wrapped in DB transaction)
        return $this->deductCredits($user, $entity, $options);
    }

    /**
     * Credit deduction using existing Entity/HasCreditLimit system.
     * Creates UserUsageCredit record for audit trail.
     * Wrapped in DB transaction for our operations' atomicity.
     */
    protected function deductCredits(User $user, EntityEnum $entity, array $options): float
    {
        $driver = Entity::driver($entity)->forUser($user);

        if (!$driver->hasCreditBalance()) {
            throw new Exception('Insufficient credits');
        }

        $driver->calculateCredit();
        $calculatedCredit = $driver->getCalculatedInputCredit();

        $creditToDeduct = $calculatedCredit > 0
            ? $calculatedCredit
            : $this->estimateCreditForEntity($entity, $options);

        // Atomic credit deduction with DB transaction for our operations
        // Note: decreaseCredit() itself is not transactional in MagicAI core,
        // but our wrapper ensures our state updates are atomic.
        return DB::transaction(function () use ($driver, $user, $entity, $creditToDeduct) {
            $success = $driver->decreaseCredit($creditToDeduct);

            if (!$success) {
                throw new Exception('Credit deduction failed');
            }

            return $creditToDeduct;
        });
    }

    /**
     * FAL Request Idempotency:
     * If fal_request_id already exists, resume polling instead of creating new request.
     */
    protected function generateWithFal(Creation $creation, EntityEnum $entity, User $user): ?string
    {
        $options = $creation->options ?? [];
        $prompt = $creation->prompt;
        $negativePrompt = $creation->negative_prompt;
        $productImagePath = $creation->product_image_path;

        $falOptions = [
            'aspect_ratio'    => $this->mapAspectRatio($options['aspect_ratio'] ?? '1:1'),
            'image_reference' => $productImagePath ? [$productImagePath] : null,
        ];

        if ($negativePrompt) {
            $options['negative_prompt'] = $negativePrompt;
        }

        if ($entity->value === 'flux-pro' || $entity->value === 'flux-pro-1.1') {
            return FalAIService::generate($prompt, $entity, $falOptions);
        }

        if (str_contains($entity->value, 'kling')) {
            return FalAIService::klingGenerate($prompt);
        }

        if (str_contains($entity->value, 'luma')) {
            return FalAIService::lumaGenerate($prompt);
        }

        if (str_contains($entity->value, 'minimax')) {
            return FalAIService::minimaxGenerate($prompt);
        }

        if (str_contains($entity->value, 'veo')) {
            $response = FalAIService::veo2Generate($prompt);
            if ($response instanceof \GuzzleHttp\Promise\PromiseInterface) {
                $response = $response->wait();
            }
            return $response->json('request_id') ?? null;
        }

        return FalAIService::generate($prompt, $entity, $falOptions);
    }

    /**
     * Poll FAL for result with configurable timeout.
     * Respects job timeout (840s) with buffer for finalization.
     */
    protected function pollForResult(string $requestId, EntityEnum $entity): ?array
    {
        $maxAttempts = 150; // 150 * 5s = 750s (12.5 min), leaves ~90s buffer before job timeout
        $attempt = 0;
        $startTime = time();

        while ($attempt < $maxAttempts) {
            // Check job timeout
            if (time() - $startTime > 780) { // 13 min safety margin
                throw new Exception('Polling timeout - job time limit approaching');
            }

            $result = FalAIService::check($requestId, $entity);

            if ($result) {
                if (isset($result['status']) && $result['status'] === 'FAILED') {
                    throw new Exception($result['error'] ?? 'Generation failed');
                }

                if (isset($result['image'])) {
                    return $result;
                }
            }

            sleep(5);
            $attempt++;
        }

        return null;
    }

    /**
     * Mark creation as failed with error message.
     * Idempotent - safe to call multiple times.
     */
    protected function failCreation(Creation $creation, string $error): void
    {
        // Only update if not already completed
        if (!$creation->isCompleted()) {
            $creation->markFailed($error);
        }
    }

    protected function estimateCreditForEntity(EntityEnum $entity, array $options): float
    {
        $estimates = [
            EntityEnum::FLUX_PRO->slug()              => 0.05,
            EntityEnum::FLUX_PRO_1_1->slug()          => 0.05,
            EntityEnum::FLUX_PRO_KONTEXT->slug()      => 0.08,
            EntityEnum::NANO_BANANA->slug()           => 0.05,
            EntityEnum::NANO_BANANA_PRO->slug()       => 0.08,
            EntityEnum::KLING_VIDEO->slug()           => 2.0,
            EntityEnum::KLING_2_1->slug()             => 3.0,
            EntityEnum::LUMA_DREAM_MACHINE->slug()    => 1.5,
            EntityEnum::MINIMAX->slug()               => 1.5,
            EntityEnum::VEO_2->slug()                 => 5.0,
            EntityEnum::VEO_3_1_TEXT_TO_VIDEO->slug() => 2.0,
            EntityEnum::HEYGEN_VIDEO_AGENT->slug()    => 3.0,
        ];

        return $estimates[$entity->slug()] ?? 0.10;
    }

    protected function mapAspectRatio(string $ratio): ?array
    {
        $map = [
            '1:1'   => ['width' => 1024, 'height' => 1024],
            '4:5'   => ['width' => 1024, 'height' => 1280],
            '16:9'  => ['width' => 1920, 'height' => 1080],
            '9:16'  => ['width' => 1080, 'height' => 1920],
            '3:2'   => ['width' => 1536, 'height' => 1024],
            '4:3'   => ['width' => 1365, 'height' => 1024],
        ];

        return $map[$ratio] ?? ['width' => 1024, 'height' => 1024];
    }

    protected function extractAspectRatio(array $options): string
    {
        return $options['aspect_ratio'] ?? '1:1';
    }
}