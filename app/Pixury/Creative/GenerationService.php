<?php

declare(strict_types=1);

namespace App\Pixury\Creative;

use App\Domains\Entity\Enums\EntityEnum;
use App\Domains\Entity\Facades\Entity;
use App\Models\User;
use App\Pixury\Creative\Models\Creation;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class GenerationService
{
    public function getGenerationStatus(): array
    {
        return [
            'status' => 'ready',
            'message' => 'AI generation via FalAIService',
        ];
    }

    public function createCreation(User $user, array $data): Creation
    {
        $creation = Creation::create([
            'user_id'           => $user->id,
            'template_id'       => $data['template_id'] ?? null,
            'type'              => $data['type'] ?? 'image',
            'status'            => 'pending',
            'prompt'            => $data['prompt'] ?? '',
            'negative_prompt'   => $data['negative_prompt'] ?? null,
            'options'           => $data['options'] ?? [],
            'product_image_path'=> $data['product_image_path'] ?? null,
        ]);

        return $creation;
    }

    public function getUserCreations(User $user, int $perPage = 12, string $status = ''): LengthAwarePaginator
    {
        $query = Creation::where('user_id', $user->id)
            ->orderByDesc('created_at');

        if ($status) {
            $query->where('status', $status);
        }

        return $query->paginate($perPage);
    }

    public function findCreation(int $id, User $user): ?Creation
    {
        return Creation::where('user_id', $user->id)->find($id);
    }

    public function estimateCredits(string $entitySlug, array $options = []): float
    {
        try {
            $entity = EntityEnum::fromSlug($entitySlug);
            $driver = Entity::driver($entity)->forGuest();
            $driver->calculateCredit();
            $credits = $driver->getCalculatedInputCredit();

            return $credits > 0 ? $credits : $this->getDefaultCreditEstimate($entitySlug, $options);
        } catch (\Throwable) {
            return $this->getDefaultCreditEstimate($entitySlug, $options);
        }
    }

    protected function getDefaultCreditEstimate(string $entitySlug, array $options = []): float
    {
        $estimates = [
            'flux-pro'           => 0.05,
            'flux-pro/v1__1'     => 0.05,
            'flux-pro/kontext'   => 0.08,
            'nano-banana'        => 0.05,
            'nano-banana-pro'    => 0.08,
            'kling-video'        => 2.0,
            'klingV21'           => 3.0,
            'luma-dream-machine' => 1.5,
            'minimax'            => 1.5,
            'veo2'               => 5.0,
            'veo3__1/text-to-video' => 2.0,
            'heygen-video-agent' => 3.0,
        ];

        return $estimates[$entitySlug] ?? 0.10;
    }

    public function getEntityForTemplate(string $templateId, string $type): EntityEnum
    {
        $imageEntities = [
            'product-showcase'   => EntityEnum::FLUX_PRO,
            'lifestyle-shot'     => EntityEnum::FLUX_PRO,
            'hero-banner'        => EntityEnum::FLUX_PRO_1_1,
            'social-post'        => EntityEnum::NANO_BANANA,
            'story-format'       => EntityEnum::NANO_BANANA,
            'product-comparison' => EntityEnum::FLUX_PRO_KONTEXT,
            'flat-lay'           => EntityEnum::FLUX_PRO,
            'minimal-product'    => EntityEnum::FLUX_PRO,
            'bundle-pack'        => EntityEnum::FLUX_PRO_1_1,
            'seasonal-campaign'  => EntityEnum::FLUX_PRO_1_1,
        ];

        $videoEntities = [
            'product-showcase'   => EntityEnum::KLING_VIDEO,
            'lifestyle-shot'     => EntityEnum::LUMA_DREAM_MACHINE,
            'hero-banner'        => EntityEnum::VEO_3_1_TEXT_TO_VIDEO,
            'social-post'        => EntityEnum::KLING_VIDEO,
            'story-format'       => EntityEnum::KLING_VIDEO,
            'product-comparison' => EntityEnum::KLING_VIDEO,
            'flat-lay'           => EntityEnum::LUMA_DREAM_MACHINE,
            'minimal-product'    => EntityEnum::KLING_VIDEO,
            'bundle-pack'        => EntityEnum::LUMA_DREAM_MACHINE,
            'seasonal-campaign'  => EntityEnum::VEO_3_1_TEXT_TO_VIDEO,
        ];

        if ($type === 'video') {
            return $videoEntities[$templateId] ?? EntityEnum::KLING_VIDEO;
        }

        return $imageEntities[$templateId] ?? EntityEnum::FLUX_PRO;
    }

    public function storeGeneratedAsset(string $url, string $userId, string $type): string
    {
        $disk = Storage::disk('r2');
        $extension = $type === 'video' ? 'mp4' : 'webp';
        $filename = "pixury/{$type}s/{$userId}/" . Str::uuid() . ".{$extension}";

        $content = @file_get_contents($url);
        if ($content === false) {
            throw new \RuntimeException("Failed to download generated asset from {$url}");
        }

        $disk->put($filename, $content, 'public');
        return $disk->url($filename);
    }

    public function generateThumbnail(string $url, string $userId): string
    {
        return $this->storeGeneratedAsset($url, $userId, 'thumb');
    }
}