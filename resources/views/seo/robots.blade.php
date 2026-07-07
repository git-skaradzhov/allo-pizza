User-agent: *
@foreach ($disallow as $path)
Disallow: {{ $path }}
@endforeach

Sitemap: {{ $sitemapUrl }}
