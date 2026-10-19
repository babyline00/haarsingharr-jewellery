<?php

/**
 * Haarsingharr Design System — centralised colour palette, typography and spacing tokens.
 * Mirrors resources/css/theme.css so Tailwind can consume them as design tokens.
 */

return [

    'palette' => [
        'ivory'         => '#FBF8F2', // primary background
        'ivory_alt'     => '#F4EEE2',
        'porcelain'     => '#FFFFFF',
        'charcoal'      => '#1F1B16', // primary text
        'charcoal_soft' => '#3A3530',
        'muted'         => '#6F6A63',
        'champagne'     => '#C8A968', // primary accent
        'champagne_dark'=> '#A8854A',
        'rose'          => '#B47B5A', // warm contrast
        'plum'          => '#3E2B33', // dark sections
        'success'       => '#3F8E5C',
        'danger'        => '#B23A48',
        'warning'       => '#D79C3F',
        'info'          => '#5B7CB6',
    ],

    'typography' => [
        'headings' => "'Cormorant Garamond', 'Playfair Display', Georgia, serif",
        'body'     => "'Inter', 'Helvetica Neue', Arial, sans-serif",
        'mono'     => "'JetBrains Mono', 'Source Code Pro', monospace",
    ],

    'layout' => [
        'max_width'     => '1280px',
        'content_width' => '960px',
        'page_padding'  => '24px',
        'radius_sm'     => '4px',
        'radius_md'     => '8px',
        'radius_lg'     => '16px',
        'shadow_soft'   => '0 1px 3px rgba(31, 27, 22, 0.04), 0 8px 24px rgba(31, 27, 22, 0.06)',
        'shadow_hover'   => '0 4px 12px rgba(31, 27, 22, 0.08), 0 16px 48px rgba(31, 27, 22, 0.10)',
    ],

    'typography_sizes' => [
        'display' => 'clamp(2.5rem, 5vw, 4.5rem)',
        'h1'      => 'clamp(2rem, 4vw, 3.25rem)',
        'h2'      => 'clamp(1.5rem, 3vw, 2.25rem)',
        'h3'      => 'clamp(1.25rem, 2vw, 1.5rem)',
        'body'    => '1rem',
        'small'   => '0.875rem',
        'micro'   => '0.75rem',
    ],
];
