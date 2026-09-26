@extends('panel.layout.app', ['disable_tblr' => true])
@section('title', __('Pixury Creative Studio'))
@section('titlebar_pretitle', '')
@section('titlebar_subtitle', __('Create stunning product photography with AI-powered templates'))

@section('titlebar_actions')
    <a href="{{ route('dashboard.user.pixury.templates') }}" class="inline-flex">
        <x-button variant="ghost-shadow">
            <x-tabler-layout-grid class="size-4 me-2" />
            {{ __('Browse Templates') }}
        </x-button>
    </a>
    <a href="{{ route('dashboard.user.pixury.create') }}" class="inline-flex">
        <x-button>
            <x-tabler-plus class="size-4 me-2" />
            {{ __('Start Creating') }}
        </x-button>
    </a>
@endsection

@section('content')
<div class="py-10">
    {{-- Featured Templates --}}
    <div class="mb-10">
        <div class="flex items-center justify-between mb-6">
            <h3 class="m-0 text-lg font-semibold">{{ __('Featured Templates') }}</h3>
            <a href="{{ route('dashboard.user.pixury.templates') }}" class="text-sm font-medium text-primary hover:underline">
                {{ __('View All') }} <x-tabler-chevron-right class="inline size-4" />
            </a>
        </div>
        @if (!empty($featuredTemplates))
            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
                @foreach ($featuredTemplates as $template)
                    <a href="{{ route('dashboard.user.pixury.create', ['template' => $template['id']]) }}" class="group block">
                        <x-card class="transition hover:-translate-y-1 hover:shadow-xl hover:shadow-black/5 active:translate-y-0 h-full">
                            <div class="relative aspect-[4/3] overflow-hidden rounded-xl">
                                <img
                                    class="size-full object-cover transition group-hover:scale-105"
                                    src="{{ $template['thumbnail'] }}"
                                    alt="{{ $template['name'] }}"
                                    loading="lazy"
                                >
                                <div class="absolute inset-0 bg-gradient-to-t from-black/60 via-black/20 to-transparent" aria-hidden="true"></div>
                                <div class="absolute bottom-3 left-3 right-3 flex items-center justify-between">
                                    <span class="inline-flex items-center gap-1 rounded-full bg-white/90 px-2.5 py-1 text-[11px] font-medium">
                                        {{ $template['category'] }}
                                    </span>
                                    <span class="inline-flex size-8 items-center justify-center rounded-full bg-white/90 text-heading">
                                        <x-tabler-arrow-right class="size-4" />
                                    </span>
                                </div>
                            </div>
                            <div class="pt-3">
                                <h4 class="m-0 mb-1 font-semibold line-clamp-1">{{ $template['name'] }}</h4>
                                <p class="m-0 text-2xs text-muted-foreground line-clamp-2">{{ $template['description'] }}</p>
                                <div class="mt-2 flex flex-wrap gap-1">
                                    @foreach ($template['tags'] as $tag)
                                        <span class="inline-flex items-center rounded-full border border-border bg-muted px-2 py-[2px] text-[10px] font-medium">{{ $tag }}</span>
                                    @endforeach
                                </div>
                            </div>
                        </x-card>
                    </a>
                @endforeach
            </div>
        @else
            <x-empty-state
                icon="tabler-image-in-picture"
                title="{{ __('No templates available') }}"
                description="{{ __('Templates will appear here once configured.') }}"
            />
        @endif
    </div>

    {{-- Categories --}}
    <div class="mb-10">
        <h3 class="m-0 mb-6 text-lg font-semibold">{{ __('Browse by Category') }}</h3>
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
            @foreach ($categories as $category)
                <a href="{{ route('dashboard.user.pixury.templates', ['category' => $category]) }}" class="group">
                    <x-card class="p-6 text-center transition hover:shadow-lg hover:border-primary/50 hover:-translate-y-1">
                        <div class="inline-flex size-16 items-center justify-center mx-auto mb-4 rounded-2xl bg-primary/10 text-primary group-hover:bg-primary group-hover:text-primary-foreground transition">
                            <x-tabler-category class="size-7" />
                        </div>
                        <h4 class="m-0 font-semibold">{{ $category }}</h4>
                        <p class="m-0 mt-1 text-sm text-muted-foreground">
                            {{ count($featuredTemplates) }} {{ __('templates') }}
                        </p>
                    </x-card>
                </a>
            @endforeach
        </div>
    </div>

    {{-- Recent Creations --}}
    @if (!empty($recentCreations))
        <div class="mb-10">
            <div class="flex items-center justify-between mb-6">
                <h3 class="m-0 text-lg font-semibold">{{ __('Recent Creations') }}</h3>
                <a href="{{ route('dashboard.user.pixury.creations') }}" class="text-sm font-medium text-primary hover:underline">
                    {{ __('View All') }} <x-tabler-chevron-right class="inline size-4" />
                </a>
            </div>
            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
                @foreach ($recentCreations as $creation)
                    <x-card class="transition hover:shadow-lg">
                        <div class="relative aspect-[4/3] overflow-hidden rounded-xl">
                            <img class="size-full object-cover" src="{{ $creation['thumb_url'] ?? 'https://via.placeholder.com/400x300' }}" alt="{{ $creation['title'] ?? 'Creation' }}">
                        </div>
                        <div class="p-3">
                            <h4 class="m-0 mb-1 font-medium line-clamp-1">{{ $creation['title'] ?? 'Untitled' }}</h4>
                            <p class="m-0 text-2xs text-muted-foreground">{{ $creation['created_at'] ?? '' }}</p>
                        </div>
                    </x-card>
                @endforeach
            </div>
        </div>
    @else
        <div class="text-center py-16">
            <x-card class="max-w-md mx-auto">
                <div class="text-center py-8">
                    <div class="inline-flex size-16 items-center justify-center mx-auto mb-4 rounded-2xl bg-primary/10 text-primary">
                        <x-tabler-photo class="size-8" />
                    </div>
                    <h3 class="m-0 mb-2 text-lg font-semibold">{{ __('No Creations Yet') }}</h3>
                    <p class="m-0 mb-6 text-muted-foreground">{{ __('Start by selecting a template and creating your first product photo.') }}</p>
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
@endsection