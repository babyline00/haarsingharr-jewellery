{{-- Shared report chart: dependency-free SVG (line or bar) with an accessible
     table fallback. Expects:
       $labels  array  - x-axis labels (strings)
       $series  array  - each: ['label' => string, 'points' => float[], 'color' => hex|null]
       $type    'line' | 'bar'
     Optional: $height (px, default 240), $money (format values as Rs.), $caption --}}
@props([
    'labels' => [],
    'series' => [],
    'type' => 'line',
    'height' => 240,
    'money' => false,
    'caption' => null,
])

@unless ($labels === [] || $series === [])
    @php
        $W = 640;
        $padTop = 14;
        $padBottom = 30;
        $padLeft = 10;
        $padRight = 10;
        $plotW = $W - $padLeft - $padRight;
        $plotH = $height - $padTop - $padBottom;
        $max = max(1, array_reduce(
            $series,
            fn ($carry, $s) => max($carry, ...array_map(floatval(...), $s['points'])),
            0.0,
        ));

        $n = count($labels);
        // One x per label.
        $xs = [];
        if ($type === 'bar') {
            $slot = $plotW / max(1, $n);
            foreach (range(0, $n - 1) as $i) {
                $xs[] = $padLeft + $slot * $i + $slot / 2;
            }
        } else {
            $step = $n > 1 ? $plotW / ($n - 1) : $plotW / 2;
            foreach (range(0, $n - 1) as $i) {
                $xs[] = $padLeft + $step * $i;
            }
        }

        $defaultColors = ['#A48B5F', '#565257', '#8a7d6b'];
        $pointY = fn ($value) => $padTop + (1 - $value / $max) * $plotH;

        $fmtValue = function ($value) use ($money) {
            return $money ? 'Rs. '.number_format($value) : number_format($value);
        };

        // Compact y-axis ticks: 4 lattice lines between 0 and max.
        $ticks = [];
        foreach (range(0, 4) as $t) {
            $ticks[] = $max * $t / 4;
        }

        // Sparse x labels: first, last, and up to two in between.
        $labelIdx = $n <= 4 ? range(0, $n - 1) : array_unique([
            0, (int) floor(($n - 1) / 3), (int) floor(2 * ($n - 1) / 3), $n - 1,
        ]);
    @endphp

    <figure
        role="group"
        aria-label="{{ $caption ?? __('Chart') }} — table version follows"
        class="card p-4"
    >
        <svg
            viewBox="0 0 {{ $W }} {{ $height }}"
            class="w-full h-auto"
            role="img"
            aria-label="{{ $caption ?? __('Chart') }}"
            preserveAspectRatio="xMidYMid meet"
        >
            @foreach ($ticks as $k => $tick)
                <line
                    x1="{{ $padLeft }}" x2="{{ $W - $padRight }}"
                    y1="{{ round($pointY($tick), 1) }}" y2="{{ round($pointY($tick), 1) }}"
                    stroke="currentColor" stroke-opacity="0.12" stroke-width="1" />
                <text
                    x="{{ $padLeft }}" y="{{ round($pointY($tick) - 4, 1) }}"
                    font-size="11" fill="currentColor" opacity="0.55"
                    text-anchor="start">{{ number_format($tick) }}</text>
            @endforeach

            @foreach ($labelIdx as $i)
                <text
                    x="{{ round($xs[$i], 1) }}" y="{{ $height - 10 }}"
                    font-size="11" fill="currentColor" opacity="0.75"
                    text-anchor="{{ $i === 0 ? 'start' : ($i === $n - 1 ? 'end' : 'middle') }}"
                >{{ \Illuminate\Support\Str::limit((string) $labels[$i], 12) }}</text>
            @endforeach

            @foreach ($series as $si => $s)
                @php $color = $s['color'] ?? $defaultColors[$si % count($defaultColors)]; @endphp
                @if ($type === 'bar')
                    @php $slot = $plotW / max(1, $n); $barW = min(34, $slot * 0.55); @endphp
                    @foreach ($s['points'] as $i => $value)
                        @if ((float) $value > 0)
                            <rect
                                x="{{ round($xs[$i] - $barW / 2, 1) }}"
                                y="{{ round($pointY($value), 1) }}"
                                width="{{ round($barW, 1) }}"
                                height="{{ round($plotH + $padTop - $pointY($value), 1) }}"
                                rx="3" fill="{{ $color }}" fill-opacity="0.85" />
                        @endif
                    @endforeach
                @else
                    <polyline
                        points="{{ implode(' ', array_map(
                            fn ($i, $value) => round($xs[$i], 1).','.round($pointY($value), 1),
                            array_keys($s['points']), $s['points'],
                        )) }}"
                        fill="none" stroke="{{ $color }}" stroke-width="2.5"
                        stroke-linecap="round" stroke-linejoin="round" />
                    @foreach ($s['points'] as $i => $value)
                        <circle
                            cx="{{ round($xs[$i], 1) }}" cy="{{ round($pointY($value), 1) }}"
                            r="3" fill="{{ $color }}" />
                    @endforeach
                @endif
            @endforeach
        </svg>

        <figcaption class="sr-only">{{ $caption ?? __('Chart') }}</figcaption>

        @if (count($series) > 1 || $type === 'bar')
            <div class="flex flex-wrap items-center gap-4 mt-2 px-1">
                @foreach ($series as $si => $s)
                    @php $color = $s['color'] ?? $defaultColors[$si % count($defaultColors)]; @endphp
                    <span class="inline-flex items-center gap-2 text-xs text-muted">
                        <span class="inline-block w-3 h-3 rounded-sm" style="background: {{ $color }}"></span>
                        {{ $s['label'] }}
                    </span>
                @endforeach
            </div>
        @endif

        {{-- Table fallback --}}
        <details class="mt-2">
            <summary class="text-xs text-muted cursor-pointer hover:text-charcoal select-none">
                View chart as table
            </summary>
            <div class="overflow-x-auto mt-2">
                <table class="w-full text-sm">
                    <thead class="text-xs uppercase tracking-widest text-muted">
                        <tr>
                            <th class="text-left p-2"><span class="sr-only">Point</span></th>
                            @foreach ($series as $s)
                                <th class="text-right p-2">{{ $s['label'] }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-charcoal/5">
                        @foreach ($labels as $i => $label)
                            <tr>
                                <td class="p-2 whitespace-nowrap">{{ $label }}</td>
                                @foreach ($series as $s)
                                    <td class="p-2 text-right tabular-nums">
                                        {{ $fmtValue($s['points'][$i] ?? 0) }}
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </details>
    </figure>
@endunless
