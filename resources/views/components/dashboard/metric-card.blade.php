@php
    $severityStyles = [
        'critical' => ['border' => 'border-l-red-500', 'text' => 'text-red-600 dark:text-red-400', 'icon' => 'exclamation-triangle'],
        'warning' => ['border' => 'border-l-amber-500', 'text' => 'text-amber-600 dark:text-amber-400', 'icon' => 'clock'],
        'info' => ['border' => 'border-l-indigo-500', 'text' => 'text-indigo-600 dark:text-indigo-400', 'icon' => 'information-circle'],
        'neutral' => ['border' => 'border-l-zinc-300 dark:border-l-zinc-600', 'text' => 'text-zinc-700 dark:text-zinc-300', 'icon' => 'check-circle'],
    ];
    $style = $severityStyles[$metric->severity] ?? $severityStyles['neutral'];
    $isEmpty = $metric->value === 0;
@endphp

<div {{ $attributes->class(['bg-white dark:bg-zinc-800 rounded-lg shadow-sm border-l-4 p-4', $style['border']]) }}>
    <div class="flex items-start justify-between gap-2">
        <div class="min-w-0">
            <p class="text-sm font-medium text-zinc-500 dark:text-zinc-400 truncate">{{ $metric->label }}</p>

            @if ($isEmpty)
                <p class="mt-1 text-sm text-zinc-400 dark:text-zinc-500">
                    {{ $metric->emptyStateText ?? __('Tidak ada.') }}
                </p>
            @else
                <p class="mt-1 flex items-center gap-1.5 text-2xl font-semibold {{ $style['text'] }}">
                    <flux:icon :name="$style['icon']" class="size-5 shrink-0" />
                    {{ $metric->value }}
                </p>
            @endif
        </div>
    </div>

    @if ($metric->route && ! $isEmpty)
        <a href="{{ $metric->route }}" wire:navigate class="mt-3 inline-flex text-sm font-medium text-indigo-600 hover:text-indigo-900 dark:text-indigo-400">
            {{ __('Lihat Detail') }} &rarr;
        </a>
    @endif
</div>
