<?php

declare(strict_types=1);

namespace App\Http\Controllers\Pixury;

use App\Http\Controllers\Controller;
use App\Jobs\Pixury\PixuryGenerationJob;
use App\Pixury\Creative\GenerationService;
use App\Pixury\Creative\Models\Creation;
use App\Pixury\Templates\TemplateRepository;
use App\Pixury\Templates\TemplateService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class PixuryController extends Controller
{
    public function __construct(
        protected TemplateService $templateService,
        protected GenerationService $generationService
    ) {}

    public function index(Request $request): View
    {
        $featuredTemplates = $this->templateService->getFeaturedTemplates(4);
        $categories = $this->templateService->getCategories();
        $recentCreations = $this->generationService->getUserCreations($request->user(), 6);

        return view('pixury.index', [
            'featuredTemplates' => $featuredTemplates,
            'categories' => $categories,
            'recentCreations' => $recentCreations,
        ]);
    }

    public function templates(Request $request): View
    {
        $selectedCategory = $request->query('category', 'all');
        $templates = $selectedCategory === 'all'
            ? $this->templateService->getAllTemplates()
            : $this->templateService->getTemplatesByCategory($selectedCategory);

        $categories = $this->templateService->getCategories();

        return view('pixury.templates', [
            'templates' => $templates,
            'categories' => $categories,
            'selectedCategory' => $selectedCategory,
        ]);
    }

    public function create(Request $request): View
    {
        $templateId = $request->query('template');
        $template = $templateId ? $this->templateService->getTemplate($templateId) : null;
        $templates = $this->templateService->getAllTemplates();
        $categories = $this->templateService->getCategories();

        return view('pixury.create', [
            'selectedTemplate' => $template,
            'templates' => $templates,
            'categories' => $categories,
        ]);
    }

    public function creations(Request $request): View
    {
        $status = $request->query('status', '');
        $creations = $this->generationService->getUserCreations($request->user(), 20, $status);

        return view('pixury.creations', [
            'creations' => $creations,
            'currentStatus' => $status,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validTemplateIds = array_column($this->templateService->getAllTemplates(), 'id');

        $validated = $request->validate([
            'template_id' => ['nullable', 'string', Rule::in($validTemplateIds)],
            'type'        => ['required', Rule::in(['image', 'video'])],
            'prompt'      => ['required', 'string', 'max:5000'],
            'negative_prompt' => ['nullable', 'string', 'max:2000'],

            // Only these keys are accepted. Unknown FAL parameters are rejected.
            'options'     => ['nullable', 'array:aspect_ratio,guidance_scale,num_steps,seed'],
            'options.aspect_ratio'   => ['nullable', Rule::in(TemplateRepository::ASPECT_RATIOS)],
            'options.guidance_scale' => ['nullable', 'numeric', 'min:1', 'max:20'],
            'options.num_steps'      => ['nullable', 'integer', 'min:1', 'max:100'],
            'options.seed'           => ['nullable', 'integer', 'min:0'],

            'product_image_path' => ['nullable', 'string', 'url', 'max:2048'],
        ]);

        $creation = $this->generationService->createCreation($request->user(), [
            'template_id'       => $validated['template_id'] ?? null,
            'type'              => $validated['type'],
            'prompt'            => $validated['prompt'],
            'negative_prompt'   => $validated['negative_prompt'] ?? null,
            'options'           => $validated['options'] ?? [],
            'product_image_path'=> $validated['product_image_path'] ?? null,
        ]);

        PixuryGenerationJob::dispatch($creation->id);

        return response()->json([
            'success' => true,
            'creation_id' => $creation->id,
            'status' => $creation->status,
            'message' => 'Generation started',
        ]);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $creation = $this->generationService->findCreation($id, $request->user());

        if (!$creation) {
            return response()->json([
                'success' => false,
                'message' => 'Creation not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'creation' => $creation->load('user'),
        ]);
    }

    public function status(Request $request, int $id): JsonResponse
    {
        $creation = $this->generationService->findCreation($id, $request->user());

        if (!$creation) {
            return response()->json([
                'success' => false,
                'message' => 'Creation not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'status' => $creation->status,
            'result_url' => $creation->result_url,
            'thumb_url' => $creation->thumb_url,
            'error_message' => $creation->error_message,
            'credits_used' => $creation->credits_used,
            'generation_time' => $creation->generation_time,
            'fal_request_id' => $creation->fal_request_id,
            'fal_entity' => $creation->fal_entity,
            'can_retry' => $creation->canRetry(),
            'is_terminal' => $creation->isTerminal(),
        ]);
    }

    /**
     * Retry a failed generation.
     */
    public function retry(Request $request, int $id): JsonResponse
    {
        $creation = $this->generationService->findCreation($id, $request->user());

        if (!$creation) {
            return response()->json([
                'success' => false,
                'message' => 'Creation not found',
            ], 404);
        }

        if (!$creation->canRetry()) {
            return response()->json([
                'success' => false,
                'message' => 'This creation cannot be retried',
            ], 422);
        }

        $creation->resetForRetry();
        PixuryGenerationJob::dispatch($creation->id);

        return response()->json([
            'success' => true,
            'creation_id' => $creation->id,
            'status' => $creation->fresh()->status,
            'message' => 'Generation retry started',
        ]);
    }

    /**
     * Product images must be reachable by the FAL.ai API (a remote service),
     * so they are stored on the configured r2 disk which exposes a public URL.
     * Falls back to the local `public` disk when r2 is not configured.
     */
    protected function productImageDisk(): \Illuminate\Contracts\Filesystem\Filesystem
    {
        if (config('filesystems.disks.r2.bucket')) {
            return Storage::disk('r2');
        }

        return Storage::disk('public');
    }

    public function upload(Request $request): JsonResponse
    {
        $request->validate([
            'image' => ['required', 'image', 'max:10240'], // 10MB max
        ]);

        $file = $request->file('image');
        $extension = strtolower($file->getClientOriginalExtension() ?: 'jpg');
        $filename = 'pixury/products/' . $request->user()->id . '/' . Str::uuid() . '.' . $extension;

        $disk = $this->productImageDisk();
        $disk->put($filename, file_get_contents($file->getRealPath()), 'public');

        return response()->json([
            'success' => true,
            'path'    => $disk->url($filename),
            'disk'    => $disk === Storage::disk('r2') ? 'r2' : 'public',
            'message' => 'Image uploaded successfully',
        ]);
    }
}