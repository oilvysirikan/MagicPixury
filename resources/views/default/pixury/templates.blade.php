@extends('panel.layout.app', ['disable_tblr' => true])
@section('title', __('Templates'))
@section('titlebar_pretitle', '')
@section('titlebar_subtitle', __('Browse and select from our collection of AI-powered templates'))

@section('titlebar_actions')
    <a href="{{ route('dashboard.user.pixury.create') }}" class="inline-flex">
        <x-button>
            <x-tabler-plus class="size-4 me-2" />
            {{ __('Start Creating') }}
        </x-button>
    </a>
@endsection

@section('content')
<div class="py-10">
    {{-- Category Filter --}}
    <div class="mb-6">
        <div class="flex flex-wrap gap-2" role="tablist">
            <a
                href="{{ route('dashboard.user.pixury.templates', ['category' => 'all']) }}"
                class="@class(['inline-flex items-center gap-1.5 rounded-full border px-3 py-1.5 text-sm font-medium transition', 'bg-primary text-primary-foreground border-primary' => $selectedCategory === 'all', 'bg-background hover:bg-muted' => $selectedCategory !== 'all'])"
                role="tab"
                aria-selected="{{ $selectedCategory === 'all' }}"
            >
                {{ __('All') }}
            </a>
            @foreach ($categories as $category)
                <a
                    href="{{ route('dashboard.user.pixury.templates', ['category' => $category]) }}"
                    class="@class(['inline-flex items-center gap-1.5 rounded-full border px-3 py-1.5 text-sm font-medium transition', 'bg-primary text-primary-foreground border-primary' => $selectedCategory === $category, 'bg-background hover:bg-muted' => $selectedCategory !== $category])"
                    role="tab"
                    aria-selected="{{ $selectedCategory === $category }}"
                >
                    {{ $category }}
                </a>
            @endforeach
        </div>
    </div>

    {{-- Templates Grid --}}
    @if (!empty($templates))
        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
            @foreach ($templates as $template)
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
                        <div class="p-3">
                            <h4 class="m-0 mb-1 font-semibold line-clamp-1">{{ $template['name'] }}</h4>
                            <p class="m-0 mb-2 text-2xs text-muted-foreground line-clamp-2">{{ $template['description'] }}</p>
                            <div class="flex flex-wrap gap-1">
                                @foreach ($template['tags'] as $tag)
                                    <span class="inline-flex items-center rounded-full border border-border bg-muted px-2 py-[2px] text-[10px] font-medium">{{ $tag }}</span>
                                @endforeach
                            </div>
                            <div class="mt-3 pt-3 border-t border-border flex items-center justify-between text-[11px] text-muted-foreground">
                                <span>{{ $template['aspect_ratio'] }}</span>
                                <span>{{ $template['resolution'] }}</span>
                            </div>
                        </div>
                    </x-card>
                </a>
            @endforeach
        </div>
    @else
        <x-empty-state
            icon="tabler-image-in-picture"
            title="{{ __('No templates found') }}"
            description="{{ __('Try selecting a different category or check back later.') }}"
        />
    @endif
</div>
@endsection