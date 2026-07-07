<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Response;

class RobotsController extends Controller
{
    public function index(): Response
    {
        $content = view('seo.robots', [
            'sitemapUrl' => url('/sitemap.xml'),
            'disallow' => config('seo.robots_disallow', []),
        ])->render();

        return response($content, 200, [
            'Content-Type' => 'text/plain; charset=UTF-8',
        ]);
    }
}
