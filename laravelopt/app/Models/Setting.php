<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    use HasFactory;

    protected $fillable = [
        'logo', 'site_name', 'org_legal_name', 'bin', 'slogan', 'map', 'copyright',
        'year', 'work_time', 'phone', 'city_phone', 'email', 'address',
        'geo_lat', 'geo_lng', 'social_links',
        'default_meta_title', 'default_meta_title_en', 'default_meta_title_kz',
        'default_meta_description', 'default_meta_description_en', 'default_meta_description_kz',
        'default_og_image',
        'ga4_id', 'gtm_id', 'yandex_metrika_id', 'google_verification', 'yandex_verification',
        'default_locale', 'robots_extra',
    ];

    protected $casts = [
        'social_links' => 'array',
    ];

    public static function getSettings()
    {
        return self::firstOrCreate([], [
            'site_name' => '',
        ]);
    }
}
