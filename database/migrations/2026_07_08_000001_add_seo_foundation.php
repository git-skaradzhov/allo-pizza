<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('store_settings', function (Blueprint $table) {
            $table->string('seo_title_suffix')->nullable()->after('closed_message');
            $table->text('seo_default_description')->nullable()->after('seo_title_suffix');
            $table->string('seo_default_image')->nullable()->after('seo_default_description');
            $table->text('organization_description')->nullable()->after('seo_default_image');
            $table->string('organization_logo')->nullable()->after('organization_description');
            $table->string('google_analytics_id')->nullable()->after('organization_logo');
            $table->string('google_tag_manager_id')->nullable()->after('google_analytics_id');
            $table->string('google_site_verification')->nullable()->after('google_tag_manager_id');
            $table->string('bing_site_verification')->nullable()->after('google_site_verification');
            $table->string('meta_pixel_id')->nullable()->after('bing_site_verification');
        });

        foreach (['pages', 'categories', 'products'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->string('canonical_url')->nullable()->after('seo_description');
                $table->string('meta_robots')->nullable()->after('canonical_url');
                $table->string('og_title')->nullable()->after('meta_robots');
                $table->text('og_description')->nullable()->after('og_title');
                $table->string('og_image')->nullable()->after('og_description');
                $table->string('twitter_title')->nullable()->after('og_image');
                $table->text('twitter_description')->nullable()->after('twitter_title');
                $table->string('twitter_image')->nullable()->after('twitter_description');
                $table->string('focus_keyword')->nullable()->after('twitter_image');
            });
        }

        Schema::table('products', function (Blueprint $table) {
            $table->string('image_alt')->nullable()->after('image');
        });

        Schema::create('redirects', function (Blueprint $table) {
            $table->id();
            $table->string('from_path')->unique();
            $table->string('to_path');
            $table->unsignedSmallInteger('status_code')->default(301);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('redirects');

        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('image_alt');
        });

        foreach (['pages', 'categories', 'products'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->dropColumn([
                    'canonical_url',
                    'meta_robots',
                    'og_title',
                    'og_description',
                    'og_image',
                    'twitter_title',
                    'twitter_description',
                    'twitter_image',
                    'focus_keyword',
                ]);
            });
        }

        Schema::table('store_settings', function (Blueprint $table) {
            $table->dropColumn([
                'seo_title_suffix',
                'seo_default_description',
                'seo_default_image',
                'organization_description',
                'organization_logo',
                'google_analytics_id',
                'google_tag_manager_id',
                'google_site_verification',
                'bing_site_verification',
                'meta_pixel_id',
            ]);
        });
    }
};
