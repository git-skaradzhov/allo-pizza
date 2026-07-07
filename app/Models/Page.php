<?php

namespace App\Models;

use App\Concerns\HasSeoFields;
use App\Contracts\SeoMeta;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Page extends Model implements SeoMeta
{
    use HasFactory;
    use HasSeoFields;

    protected $fillable = [
        'title',
        'slug',
        'content',
        'featured_image',
        'seo_title',
        'seo_description',
        'canonical_url',
        'meta_robots',
        'og_title',
        'og_description',
        'og_image',
        'twitter_title',
        'twitter_description',
        'twitter_image',
        'focus_keyword',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }
}
