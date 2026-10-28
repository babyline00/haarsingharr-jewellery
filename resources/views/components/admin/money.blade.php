@props([
    'amount' => 0,
    'currency' => 'PKR',
    'decimals' => 0,
])

@php
    $prefix = $currency === 'PKR' ? 'Rs. ' : $currency . ' ';
@endphp

<span {{ $attributes->merge(['class' => 'tabular-nums whitespace-nowrap']) }}>{{ $prefix }}{{ number_format((float) $amount, $decimals) }}</span>
