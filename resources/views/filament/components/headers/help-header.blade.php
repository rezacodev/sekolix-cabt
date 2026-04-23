@props([
    'helpAction' => null,
    'otherActions' => [],
    'breadcrumbs' => [],
    'heading' => null,
    'subheading' => null,
])

<div class="filament-page-header">
    @if (filled($breadcrumbs))
        <div class="mb-2">
            <x-filament::breadcrumbs :breadcrumbs="$breadcrumbs" />
        </div>
    @endif

    <header class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div class="flex flex-col gap-2">
            <div class="flex items-center gap-3">
                <h1 class="text-2xl font-semibold tracking-tight text-gray-900 dark:text-white">
                    {{ $heading }}
                </h1>

                @if ($helpAction)
                    <x-filament::actions :actions="[$helpAction]" alignment="start" class="!gap-0" />
                @endif
            </div>

            @if ($subheading)
                <p class="max-w-4xl text-sm text-gray-600 dark:text-gray-300">
                    {{ $subheading }}
                </p>
            @endif
        </div>

        <div class="flex items-center gap-2">
            <x-filament::actions :actions="$otherActions" />
        </div>
    </header>
</div>
