@extends('panel.layout.app', ['disable_tblr' => true])
@section('title', __('Create'))
@section('titlebar_pretitle', '')
@section('titlebar_subtitle', $selectedTemplate ? __('Customize and generate: ') . $selectedTemplate['name'] : __('Select a template and start creating'))

@section('titlebar_actions')
    <a href="{{ route('dashboard.user.pixury.templates') }}" class="inline-flex">
        <x-button variant="ghost-shadow">
            <x-tabler-chevron-left class="size-4 me-2" />
            {{ __('Back to Templates') }}
        </x-button>
    </a>
@endsection

@section('content')
<div class="py-10">
    @if ($selectedTemplate)
        {{-- Selected Template Card --}}
        <div class="mb-8">
            <x-card class="overflow-hidden">
                <div class="relative aspect-[4/3]">
                    <img class="size-full object-cover" src="{{ $selectedTemplate['thumbnail'] }}" alt="{{ $selectedTemplate['name'] }}">
                    <div class="absolute inset-0 bg-gradient-to-t from-black/60 via-black/20 to-transparent" aria-hidden="true"></div>
                    <div class="absolute bottom-4 left-4 right-4 flex items-center justify-between">
                        <span class="inline-flex items-center gap-1 rounded-full bg-white/90 px-3 py-1 text-sm font-medium">
                            {{ $selectedTemplate['category'] }}
                        </span>
                    </div>
                </div>
                <div class="p-6">
                    <h3 class="m-0 mb-2 text-xl font-semibold">{{ $selectedTemplate['name'] }}</h3>
                    <p class="m-0 mb-4 text-muted-foreground">{{ $selectedTemplate['description'] }}</p>
                    <div class="flex flex-wrap gap-2 mb-4">
                        @foreach ($selectedTemplate['tags'] as $tag)
                            <span class="inline-flex items-center rounded-full border border-border bg-muted px-2.5 py-1 text-sm font-medium">{{ $tag }}</span>
                        @endforeach
                    </div>
                    <div class="flex items-center gap-4 text-sm text-muted-foreground border-t border-border pt-4">
                        <span class="flex items-center gap-1">
                            <x-tabler-layout class="size-4" />
                            {{ $selectedTemplate['aspect_ratio'] }}
                        </span>
                        <span class="flex items-center gap-1">
                            <x-tabler-maximize class="size-4" />
                            {{ $selectedTemplate['resolution'] }}
                        </span>
                    </div>
                </div>
            </x-card>
        </div>

        {{-- Generation Form --}}
        <div class="grid grid-cols-1 gap-8 lg:grid-cols-[1fr_400px]">
            {{-- Main Form --}}
            <div>
                <x-card>
                    <div class="p-6 border-b border-border">
                        <h4 class="m-0 text-lg font-semibold">{{ __('Generation Settings') }}</h4>
                    </div>
                    <div class="p-6 space-y-6">
                        {{-- Product Upload --}}
                        <div>
                            <label class="block mb-2 text-sm font-medium">{{ __('Product Image') }} <span class="text-danger">*</span></label>
                            <div class="relative">
                                <input
                                    type="file"
                                    accept="image/*"
                                    id="product-image"
                                    class="absolute inset-0 w-full h-full opacity-0 cursor-pointer"
                                >
                                <label for="product-image" class="lqd-dropzone flex cursor-pointer flex-col items-center justify-center gap-3 rounded-xl border-2 border-dashed border-border p-8 text-center transition hover:border-primary hover:bg-primary/5">
                                    <x-tabler-upload class="size-10 text-muted-foreground" />
                                    <p class="m-0 font-medium">{{ __('Drag & drop your product image here') }}</p>
                                    <p class="m-0 text-sm text-muted-foreground">{{ __('or click to browse') }}</p>
                                    <p class="m-0 text-xs text-muted-foreground">{{ __('Supports JPG, PNG, WebP up to 10MB') }}</p>
                                </label>
                                <div id="product-preview" class="hidden mt-4">
                                    <img id="product-preview-img" class="max-h-48 mx-auto rounded-xl shadow-lg" alt="Product preview">
                                </div>
                            </div>
                            <p id="product-upload-status" class="m-0 mt-2 text-xs text-muted-foreground hidden"></p>
                        </div>

                        {{-- Prompt --}}
                        <div>
                            <label for="prompt" class="block mb-2 text-sm font-medium">{{ __('Prompt') }} <span class="text-danger">*</span></label>
                            <textarea
                                id="prompt"
                                rows="4"
                                class="w-full rounded-xl border border-border bg-background px-4 py-3 text-sm placeholder:text-muted-foreground focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20"
                                placeholder="{{ __('Describe what you want to create... e.g., "Professional product photography of a wireless headphone on a marble surface with soft studio lighting, 8k resolution"') }}"
                            ></textarea>
                            <p class="m-0 mt-1 text-xs text-muted-foreground">{{ __('Be specific about lighting, background, angle, and style for best results.') }}</p>
                        </div>

                        {{-- Negative Prompt --}}
                        <div>
                            <label for="negative-prompt" class="block mb-2 text-sm font-medium">{{ __('Negative Prompt (Optional)') }}</label>
                            <textarea
                                id="negative-prompt"
                                rows="2"
                                class="w-full rounded-xl border border-border bg-background px-4 py-3 text-sm placeholder:text-muted-foreground focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20"
                                placeholder="{{ __('Things to avoid: blurry, low quality, distorted, watermark, text, ugly, deformed') }}"
                            ></textarea>
                        </div>

                        {{-- Settings Row --}}
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div>
                                <label for="guidance-scale" class="block mb-2 text-sm font-medium">{{ __('Guidance Scale') }}</label>
                                <input type="range" id="guidance-scale" min="1" max="20" value="7" step="0.5" class="w-full">
                                <p class="m-0 mt-1 text-sm text-muted-foreground"><span id="guidance-value">7</span> {{ __('How closely to follow the prompt') }}</p>
                            </div>
                            <div>
                                <label for="num-steps" class="block mb-2 text-sm font-medium">{{ __('Steps') }}</label>
                                <input type="range" id="num-steps" min="10" max="50" value="30" step="1" class="w-full">
                                <p class="m-0 mt-1 text-sm text-muted-foreground"><span id="steps-value">30</span> {{ __('Number of denoising steps') }}</p>
                            </div>
                        </div>

                        {{-- Seed --}}
                        <div>
                            <label for="seed" class="block mb-2 text-sm font-medium">{{ __('Seed (Optional)') }}</label>
                            <div class="flex gap-2">
                                <input
                                    type="number"
                                    id="seed"
                                    min="0"
                                    max="2147483647"
                                    placeholder="{{ __('Random') }}"
                                    class="w-full rounded-xl border border-border bg-background px-4 py-3 text-sm focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20"
                                >
                                <button type="button" id="random-seed" class="px-4 rounded-xl border border-border bg-background text-sm font-medium hover:bg-muted transition">
                                    <x-tabler-dice class="size-4 me-1" /> {{ __('Random') }}
                                </button>
                            </div>
                        </div>
                    </div>
                </x-card>
            </div>

            {{-- Sidebar --}}
            <div>
                <x-card class="sticky top-24">
                    <div class="p-6 border-b border-border">
                        <h4 class="m-0 text-lg font-semibold">{{ __('Preview & Generate') }}</h4>
                    </div>
                    <div class="p-6 space-y-4">
                        {{-- Preview Area --}}
                        <div class="relative aspect-[4/3] rounded-xl overflow-hidden bg-muted">
                            <div id="generation-preview" class="absolute inset-0 flex items-center justify-center text-muted-foreground">
                                <x-tabler-photo class="size-12 opacity-50" />
                                <p class="absolute bottom-4 text-center text-sm">{{ __('Result will appear here') }}</p>
                            </div>
                            <div id="generation-loading" class="hidden absolute inset-0 flex items-center justify-center bg-black/50">
                                <div class="text-center">
                                    <div class="inline-flex size-12 border-4 border-primary border-t-transparent rounded-full animate-spin mb-3"></div>
                                    <p class="text-white font-medium">{{ __('Generating...') }}</p>
                                    <p id="generation-status-text" class="text-white/70 text-sm mt-1"></p>
                                </div>
                            </div>
                        </div>

                        {{-- Cost Estimate --}}
                        <div class="rounded-lg bg-muted/50 p-4">
                            <div class="flex items-center justify-between text-sm">
                                <span class="font-medium">{{ __('Estimated Cost') }}</span>
                                <span id="estimated-cost" class="font-semibold text-primary">{{ __('Calculating...') }}</span>
                            </div>
                            <p class="m-0 mt-1 text-xs text-muted-foreground">{{ __('Cost may vary based on resolution and model') }}</p>
                        </div>

                        {{-- User Balance --}}
                        @auth
                            <div class="flex items-center justify-between text-sm border-t border-border pt-4">
                                <span class="font-medium">{{ __('Your Credits') }}</span>
                                <span id="user-credits" class="font-semibold">{{ auth()->user()->credits ?? 0 }} {{ __('credits') }}</span>
                            </div>
                        @endauth

                        {{-- Generate Button --}}
                        <button
                            id="generate-btn"
                            type="button"
                            class="w-full py-3 rounded-xl bg-primary text-primary-foreground font-semibold text-sm hover:bg-primary/90 transition disabled:opacity-50 disabled:cursor-not-allowed"
                            disabled
                        >
                            <x-tabler-wand class="size-4 me-2" />
                            {{ __('Generate') }}
                        </button>

                        <p class="m-0 text-center text-xs text-muted-foreground">
                            {{ __('Your generation will be processed in the background. Check My Creations for results.') }}
                        </p>
                    </div>
                </x-card>

                {{-- Quick Tips --}}
                <x-card class="mt-4">
                    <div class="p-6">
                        <h5 class="m-0 mb-4 text-sm font-semibold flex items-center gap-2">
                            <x-tabler-bulb class="size-4 text-primary" />
                            {{ __('Tips for Better Results') }}
                        </h5>
                        <ul class="m-0 space-y-2 text-sm text-muted-foreground">
                            <li class="flex items-start gap-2">
                                <x-tabler-check class="size-4 flex-shrink-0 text-primary mt-0.5" />
                                Use high-quality product images with clean backgrounds
                            </li>
                            <li class="flex items-start gap-2">
                                <x-tabler-check class="size-4 flex-shrink-0 text-primary mt-0.5" />
                                Be specific about lighting (studio, natural, dramatic)
                            </li>
                            <li class="flex items-start gap-2">
                                <x-tabler-check class="size-4 flex-shrink-0 text-primary mt-0.5" />
                                Mention camera angle (front, 45°, top-down, macro)
                            </li>
                            <li class="flex items-start gap-2">
                                <x-tabler-check class="size-4 flex-shrink-0 text-primary mt-0.5" />
                                Specify background (solid color, gradient, environment)
                            </li>
                        </ul>
                    </div>
                </x-card>
            </div>
        </div>
    @else
        {{-- No Template Selected --}}
        <div class="text-center py-16">
            <x-card class="max-w-2xl mx-auto">
                <div class="text-center py-12">
                    <div class="inline-flex size-20 items-center justify-center mx-auto mb-6 rounded-2xl bg-primary/10 text-primary">
                        <x-tabler-layout-grid class="size-10" />
                    </div>
                    <h3 class="m-0 mb-3 text-xl font-semibold">{{ __('Select a Template First') }}</h3>
                    <p class="m-0 mb-8 text-muted-foreground">{{ __('Choose a template from the library to start creating your product photography.') }}</p>
                    <a href="{{ route('dashboard.user.pixury.templates') }}">
                        <x-button size="lg">
                            <x-tabler-layout-grid class="size-4 me-2" />
                            {{ __('Browse Templates') }}
                        </x-button>
                    </a>
                </div>
            </x-card>
        </div>
    @endif
</div>

@push('script')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Product image preview & upload
    const productInput = document.getElementById('product-image');
    const productPreview = document.getElementById('product-preview');
    const productPreviewImg = document.getElementById('product-preview-img');
    const productUploadStatus = document.getElementById('product-upload-status');
    const generateBtn = document.getElementById('generate-btn');
    const dropzone = document.querySelector('.lqd-dropzone');

    let productImagePath = null;

    if (productInput && productPreview && productPreviewImg) {
        productInput.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                // Preview locally
                const reader = new FileReader();
                reader.onload = function(e) {
                    productPreviewImg.src = e.target.result;
                    productPreview.classList.remove('hidden');
                    dropzone.classList.add('hidden');
                    updateGenerateButton();
                };
                reader.readAsDataURL(file);

                // Upload to server
                uploadProductImage(file);
            }
        });
    }

    async function uploadProductImage(file) {
        const formData = new FormData();
        formData.append('image', file);

        if (productUploadStatus) {
            productUploadStatus.textContent = 'Uploading...';
            productUploadStatus.classList.remove('hidden', 'text-green-600', 'text-red-600');
            productUploadStatus.classList.add('text-muted-foreground');
        }

        try {
            const response = await fetch('{{ route("dashboard.user.pixury.upload") }}', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                },
                body: formData,
            });

            const data = await response.json();

            if (data.success) {
                productImagePath = data.path;
                if (productUploadStatus) {
                    productUploadStatus.textContent = 'Uploaded successfully';
                    productUploadStatus.classList.remove('text-muted-foreground', 'text-red-600');
                    productUploadStatus.classList.add('text-green-600');
                }
                updateGenerateButton();
            } else {
                throw new Error(data.message || 'Upload failed');
            }
        } catch (error) {
            if (productUploadStatus) {
                productUploadStatus.textContent = 'Upload failed: ' + error.message;
                productUploadStatus.classList.remove('text-muted-foreground', 'text-green-600');
                productUploadStatus.classList.add('text-red-600');
            }
            productImagePath = null;
            updateGenerateButton();
        }
    }

    // Enable generate button when product image is selected
    function updateGenerateButton() {
        if (generateBtn) {
            const hasImage = productInput?.files?.length > 0 || productImagePath;
            generateBtn.disabled = !hasImage;
        }
    }

    // Range input value display
    const guidanceScale = document.getElementById('guidance-scale');
    const guidanceValue = document.getElementById('guidance-value');
    if (guidanceScale && guidanceValue) {
        guidanceScale.addEventListener('input', function() {
            guidanceValue.textContent = this.value;
        });
    }

    const numSteps = document.getElementById('num-steps');
    const stepsValue = document.getElementById('steps-value');
    if (numSteps && stepsValue) {
        numSteps.addEventListener('input', function() {
            stepsValue.textContent = this.value;
        });
    }

    // Random seed
    const randomSeedBtn = document.getElementById('random-seed');
    const seedInput = document.getElementById('seed');
    if (randomSeedBtn && seedInput) {
        randomSeedBtn.addEventListener('click', function() {
            seedInput.value = Math.floor(Math.random() * 2147483647);
        });
    }

    // Generate button handler
    if (generateBtn) {
        generateBtn.addEventListener('click', async function() {
            const prompt = document.getElementById('prompt')?.value?.trim();
            if (!prompt) {
                alert('Please enter a prompt');
                return;
            }

            if (!productImagePath && !productInput?.files?.length) {
                alert('Please upload a product image');
                return;
            }

            generateBtn.disabled = true;
            generateBtn.innerHTML = '<x-tabler-loader class="size-4 me-2 animate-spin" /> Generating...';

            const generationLoading = document.getElementById('generation-loading');
            const generationPreview = document.getElementById('generation-preview');
            const generationStatusText = document.getElementById('generation-status-text');

            if (generationLoading) generationLoading.classList.remove('hidden');
            if (generationPreview) generationPreview.classList.add('hidden');

            const options = {
                guidance_scale: parseFloat(document.getElementById('guidance-scale')?.value) || 7,
                num_steps: parseInt(document.getElementById('num-steps')?.value) || 30,
                seed: document.getElementById('seed')?.value ? parseInt(document.getElementById('seed')?.value) : null,
                aspect_ratio: '1:1', // Default, could be enhanced
            };

            const payload = {
                template_id: '{{ $selectedTemplate['id'] ?? '' }}',
                type: 'image',
                prompt: prompt,
                negative_prompt: document.getElementById('negative-prompt')?.value || '',
                options: options,
                product_image_path: productImagePath,
            };

            try {
                const response = await fetch('{{ route("dashboard.user.pixury.generate") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                    },
                    body: JSON.stringify(payload),
                });

                const data = await response.json();

                if (!data.success) {
                    throw new Error(data.message || 'Failed to start generation');
                }

                // Poll for status
                pollGenerationStatus(data.creation_id);

            } catch (error) {
                console.error('Generation error:', error);
                alert('Failed to start generation: ' + error.message);
                resetGenerateButton();
            }
        });
    }

    async function pollGenerationStatus(creationId) {
        const generationLoading = document.getElementById('generation-loading');
        const generationPreview = document.getElementById('generation-preview');
        const generationStatusText = document.getElementById('generation-status-text');
        const generateBtn = document.getElementById('generate-btn');

        async function checkStatus() {
            try {
                const response = await fetch(`{{ route("dashboard.user.pixury.creations.status", ["id" => ":id"]) }}`.replace(':id', creationId), {
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                    },
                });

                const data = await response.json();

                if (!data.success) {
                    throw new Error(data.message || 'Failed to check status');
                }

                if (generationStatusText) {
                    generationStatusText.textContent = capitalizeFirst(data.status);
                }

                if (data.status === 'completed') {
                    if (generationLoading) generationLoading.classList.add('hidden');
                    if (generationPreview) generationPreview.classList.remove('hidden');
                    if (generationPreview && data.result_url) {
                        generationPreview.innerHTML = `
                            <img src="${data.result_url}" class="size-full object-cover" alt="Generated">
                        `;
                    }
                    if (generateBtn) {
                        generateBtn.disabled = false;
                        generateBtn.innerHTML = '<x-tabler-wand class="size-4 me-2" /> Generate';
                    }
                    // Refresh creations list or redirect
                    setTimeout(() => {
                        window.location.href = '{{ route("dashboard.user.pixury.creations") }}';
                    }, 2000);
                    return;
                }

                if (data.status === 'failed') {
                    if (generationLoading) generationLoading.classList.add('hidden');
                    if (generationPreview) generationPreview.classList.remove('hidden');
                    if (generationPreview) {
                        generationPreview.innerHTML = `
                            <div class="text-center text-red-500">
                                <x-tabler-alert-circle class="size-12 mx-auto mb-2" />
                                <p>Generation failed: ${data.error_message || 'Unknown error'}</p>
                            </div>
                        `;
                    }
                    if (generateBtn) {
                        generateBtn.disabled = false;
                        generateBtn.innerHTML = '<x-tabler-wand class="size-4 me-2" /> Generate';
                    }
                    return;
                }

                // Still processing, check again
                setTimeout(checkStatus, 3000);

            } catch (error) {
                console.error('Status check error:', error);
                setTimeout(checkStatus, 5000);
            }
        }

        checkStatus();
    }

    function resetGenerateButton() {
        const generateBtn = document.getElementById('generate-btn');
        const generationLoading = document.getElementById('generation-loading');
        const generationPreview = document.getElementById('generation-preview');

        if (generateBtn) {
            generateBtn.disabled = false;
            generateBtn.innerHTML = '<x-tabler-wand class="size-4 me-2" /> Generate';
        }
        if (generationLoading) generationLoading.classList.add('hidden');
        if (generationPreview) generationPreview.classList.remove('hidden');
    }

    function capitalizeFirst(str) {
        return str.charAt(0).toUpperCase() + str.slice(1);
    }
});
</script>
@endpush