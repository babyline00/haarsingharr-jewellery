<?php echo '<?xml version="1.0" encoding="UTF-8"?>'; ?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:image="http://www.sitemaps.org/schemas/sitemap-image/1.1">
    @foreach ($urls as $url)
    <url>
        <loc>{{ $url['loc'] }}</loc>
        @if (isset($url['lastmod']))<lastmod>{{ $url['lastmod'] }}</lastmod>@endif
        <changefreq>weekly</changefreq>
        <priority>{{ $url['priority'] ?? '0.7' }}</priority>
        @if (! empty($url['images']))
            @foreach ($url['images'] as $image)
            <image:image>
                <image:loc>{{ $image }}</image:loc>
            </image:image>
            @endforeach
        @endif
    </url>
    @endforeach
</urlset>
