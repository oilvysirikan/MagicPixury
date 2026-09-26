@extends('panel.layout.app', ['disable_tblr' => true])
@section('title', __('My Creations'))
@section('titlebar_pretitle', '')
@section('titlebar_subtitle', __('Your generated product photography and creative assets'))

@section('titlebar_actions')
    <a href="{{ route('dashboard.user.pixury.create') }}" class="inline-flex">
        <x-button>
            <x-tabler-plus class="size-4 me-2" />
            {{ __('Create New') }}
        </x-button>
    </a>
    <a href="{{ route('dashboard.user.pixury.templates') }}" class="inline-flex">
        <x-button variant="ghost-shadow">
            <x-tabler-layout-grid class="size-4 me-2" />
            {{ __('Browse Templates') }}
        </x-button>
    </a>
@endsection

@section('content')
<div class="py-10">
    {{-- Filter Tabs --}}
    <div class="mb-6">
        <div class="flex flex-wrap gap-2" role="tablist">
            <a
                href="{{ route('dashboard.user.pixury.creations', ['status' => '']) }}"
                class="@class(['inline-flex items-center gap-1.5 rounded-full border px-3 py-1.5 text-sm font-medium transition', 'bg-primary text-primary-foreground border-primary' => empty($currentStatus), 'bg-background hover:bg-muted' => !empty($currentStatus)])"
                role="tab"
            >
                {{ __('All') }} <span class="inline-flex size-5 items-center justify-center rounded-full bg-primary/20 text-primary text-[11px]">{{ $creations->total() }}</span>
            </a>
            <a
                href="{{ route('dashboard.user.pixury.creations', ['status' => 'completed']) }}"
                class="@class(['inline-flex items-center gap-1.5 rounded-full border px-3 py-1.5 text-sm font-medium transition', 'bg-primary text-primary-foreground border-primary' => $currentStatus === 'completed', 'bg-background hover:bg-muted' => $currentStatus !== 'completed'])"
                role="tab"
            >
                {{ __('Completed') }} <span class="inline-flex size-5 items-center justify-center rounded-full bg-green-100 text-green-700 text-[11px]">{{ $creations->filter(fn($c) => $c->status === 'completed')->count() }}</span>
            </a>
            <a
                href="{{ route('dashboard.user.pixury.creations', ['status' => 'processing']) }}"
                class="@class(['inline-flex items-center gap-1.5 rounded-full border px-3 py-1.5 text-sm font-medium transition', 'bg-primary text-primary-foreground border-primary' => $currentStatus === 'processing', 'bg-background hover:bg-muted' => $currentStatus !== 'processing'])"
                role="tab"
            >
                {{ __('Processing') }} <span class="inline-flex size-5 items-center justify-center rounded-full bg-yellow-100 text-yellow-700 text-[11px]">{{ $creations->filter(fn($c) => $c->status === 'processing')->count() }}</span>
            </a>
            <a
                href="{{ route('dashboard.user.pixury.creations', ['status' => 'pending']) }}"
                class="@class(['inline-flex items-center gap-1.5 rounded-full border px-3 py-1.5 text-sm font-medium transition', 'bg-primary text-primary-foreground border-primary' => $currentStatus === 'pending', 'bg-background hover:bg-muted' => $currentStatus !== 'pending'])"
                role="tab"
            >
                {{ __('Pending') }} <span class="inline-flex size-5 items-center justify-center rounded-full bg-blue-100 text-blue-700 text-[11px]">{{ $creations->filter(fn($c) => $c->status === 'pending')->count() }}</span>
            </a>
            <a
                href="{{ route('dashboard.user.pixury.creations', ['status' => 'failed']) }}"
                class="@class(['inline-flex items-center gap-1.5 rounded-full border px-3 py-1.5 text-sm font-medium transition', 'bg-primary text-primary-foreground border-primary' => $currentStatus === 'failed', 'bg-background hover:bg-muted' => $currentStatus !== 'failed'])"
                role="tab"
            >
                {{ __('Failed') }} <span class="inline-flex size-5 items-center justify-center rounded-full bg-red-100 text-red-700 text-[11px]">{{ $creations->filter(fn($c) => $c->status === 'failed')->count() }}</span>
            </a>
        </div>
    </div>

    {{-- Creations Grid --}}
    @if ($creations->isNotEmpty())
        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
            @foreach ($creations as $creation)
                <x-card class="transition hover:shadow-lg group relative overflow-hidden">
                    <div class="relative aspect-[4/3] overflow-hidden rounded-t-xl">
                        <img
                            class="size-full object-cover transition group-hover:scale-105"
                            src="{{ $creation->thumb_url ?? 'https://via.placeholder.com/400x300' }}"
                            alt="{{ $creation->prompt ? Str::limit($creation->prompt, 50) : 'Creation' }}"
                            loading="lazy"
                        >
                        <div class="absolute inset-0 bg-gradient-to-t from-black/60 via-black/20 to-transparent" aria-hidden="true"></div>

                        {{-- Status Badge --}}
                        <div class="absolute top-3 left-3">
                            <span class="inline-flex items-center gap-1 rounded-full bg-white/90 px-2.5 py-1 text-[11px] font-medium">
                                @if ($creation->isCompleted())
                                    <span class="inline-flex size-1.5 rounded-full bg-green-500" aria-hidden="true"></span>
                                    {{ __('Ready') }}
                                @elseif ($creation->isProcessing())
                                    <span class="inline-flex size-1.5 rounded-full bg-yellow-500 animate-pulse" aria-hidden="true"></span>
                                    {{ __('Processing') }}
                                @elseif ($creation->isFailed())
                                    <span class="inline-flex size-1.5 rounded-full bg-red-500" aria-hidden="true"></span>
                                    {{ __('Failed') }}
                                @else
                                    <span class="inline-flex size-1.5 rounded-full bg-blue-500 animate-pulse" aria-hidden="true"></span>
                                    {{ __('Pending') }}
                                @endif
                            </span>
                        </div>

                        {{-- Actions Overlay --}}
                        <div class="absolute bottom-3 left-3 right-3 flex items-center justify-between opacity-0 group-hover:opacity-100 transition-opacity">
                            <div class="flex gap-2">
                                <button class="inline-flex size-9 items-center justify-center rounded-full bg-white/90 text-heading hover:bg-white transition" title="{{ __('View') }}" wire:click="$dispatch('viewCreation', {{ $creation->id }})">
                                    <x-tabler-eye class="size-4" />
                                </button>
                                <a href="{{ $creation->result_url }}" target="_blank" class="inline-flex size-9 items-center justify-center rounded-full bg-white/90 text-heading hover:bg-white transition" title="{{ __('Download') }}">
                                    <x-tabler-download class="size-4" />
                                </a>
                                <button class="inline-flex size-9 items-center justify-center rounded-full bg-white/90 text-heading hover:bg-white transition" title="{{ __('Share') }}" wire:click="$dispatch('shareCreation', {{ $creation->id }})">
                                    <x-tabler-share class="size-4" />
                                </button>
                            </div>
                            <button class="inline-flex size-9 items-center justify-center rounded-full bg-white/90 text-heading hover:bg-white transition" title="{{ __('More') }}" wire:click="$dispatch('moreCreation', {{ $creation->id }})">
                                <x-tabler-dots-vertical class="size-4" />
                            </button>
                        </div>
                    </div>
                    <div class="p-3">
                        <div class="flex items-start justify-between gap-2">
                            <div class="min-w-0 flex-1">
                                <h4 class="m-0 mb-1 font-semibold line-clamp-1">{{ $creation->prompt ? Str::limit($creation->prompt, 60) : 'Untitled' }}</h4>
                                <p class="m-0 text-2xs text-muted-foreground">{{ $creation->template_id ?? 'Custom' }}</p>
                            </div>
                            <span class="inline-flex items-center gap-1 rounded-full border border-border bg-muted px-2 py-1 text-[10px] font-medium whitespace-nowrap">
                                {{ $creation->type }}
                            </span>
                        </div>
                        <div class="mt-2 flex items-center justify-between text-[11px] text-muted-foreground">
                            <span>{{ $creation->created_at->diffForHumans() }}</span>
                            <span>{{ $creation->resolution ?? '' }}</span>
                        </div>
                        @if ($creation->isFailed())
                            <div class="mt-2 p-2 text-[10px] text-red-600 bg-red-50 rounded-lg">
                                {{ $creation->error_message }}
                            </div>
                        @endif
                    </div>
                </x-card>
            @endforeach
        </div>

        {{-- Pagination --}}
        <div class="mt-8 flex justify-center">
            {{ $creations->links() }}
        </div>
    @else
        {{-- Empty State --}}
        <div class="text-center py-20">
            <x-card class="max-w-md mx-auto">
                <div class="text-center py-12">
                    <div class="inline-flex size-20 items-center justify-center mx-auto mb-6 rounded-2xl bg-primary/10 text-primary">
                        <x-tabler-photo class="size-10" />
                    </div>
                    <h3 class="m-0 mb-3 text-xl font-semibold">{{ __('No Creations Yet') }}</h3>
                    <p class="m-0 mb-8 text-muted-foreground">{{ __('Your generated product photos and videos will appear here. Start by choosing a template.') }}</p>
                    <div class="flex flex-col gap-3 sm:flex-row sm:justify-center">
                        <a href="{{ route('dashboard.user.pixury.templates') }}">
                            <x-button size="lg">
                                <x-tabler-layout-grid class="size-4 me-2" />
                                {{ __('Browse Templates') }}
                            </x-button>
                        </a>
                        <a href="{{ route('dashboard.user.pixury.create') }}">
                            <x-button size="lg" variant="outline">
                                <x-tabler-plus class="size-4 me-2" />
                                {{ __('Start Creating') }}
                            </x-button>
                        </a>
                    </div>
                </div>
            </x-card>
        </div>
    @endif
</div>
@endsection