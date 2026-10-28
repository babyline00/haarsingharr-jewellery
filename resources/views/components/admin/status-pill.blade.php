@props([
    'status' => null,
    'label' => null,
])

@php
    $key = strtolower((string) $status);

    $map = [
        // Success / settled
        'active' => 'badge-success',
        'approved' => 'badge-success',
        'completed' => 'badge-success',
        'delivered' => 'badge-success',
        'paid' => 'badge-success',
        'published' => 'badge-success',
        'success' => 'badge-success',
        'confirmed' => 'badge-success',
        'accepted' => 'badge-success',

        // In progress / informational
        'processing' => 'badge-info',
        'packed' => 'badge-info',
        'shipped' => 'badge-info',
        'sent' => 'badge-info',
        'open' => 'badge-info',
        'in_review' => 'badge-info',
        'reviewing' => 'badge-info',

        // Waiting
        'pending' => 'badge-warning',
        'unpaid' => 'badge-warning',
        'awaiting_payment' => 'badge-warning',
        'on_hold' => 'badge-warning',
        'scheduled' => 'badge-warning',
        'low_stock' => 'badge-warning',
        'partially_refunded' => 'badge-warning',
        'partially_fulfilled' => 'badge-warning',

        // Inactive
        'draft' => 'badge-draft',
        'inactive' => 'badge-draft',
        'discontinued' => 'badge-draft',
        'archived' => 'badge-draft',

        // Negative / terminal
        'cancelled' => 'badge-danger',
        'canceled' => 'badge-danger',
        'failed' => 'badge-danger',
        'failure' => 'badge-danger',
        'rejected' => 'badge-danger',
        'declined' => 'badge-danger',
        'expired' => 'badge-danger',
        'out_of_stock' => 'badge-danger',
        'returned' => 'badge-rose',
        'refunded' => 'badge-rose',
        'disabled' => 'badge-charcoal',
    ];

    $class = $map[$key] ?? 'badge-charcoal';
    $text = $label ?? \Illuminate\Support\Str::headline(str_replace(['_', '-'], ' ', $key));
@endphp

<span {{ $attributes->merge(['class' => 'badge ' . $class]) }}>{{ $text }}</span>
