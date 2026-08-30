<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithFileUploads;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use App\Models\Setting;
use App\Livewire\Concerns\WithRepeaters;

class SettingsComponent extends Component
{
    use WithFileUploads, WithRepeaters;

    public $settingId;
    public $existing_logo, $existing_og;

    // Контакты / организация
    public $site_name, $org_legal_name, $bin, $slogan, $map, $copyright, $year;
    public $work_time, $phone, $city_phone, $email, $address, $geo_lat, $geo_lng;
    public array $social_links = [];
    public $logo, $default_og_image;

    // Дефолтные мета
    public $default_meta_title, $default_meta_title_en, $default_meta_title_kz;
    public $default_meta_description, $default_meta_description_en, $default_meta_description_kz;

    // Аналитика и верификации
    public $ga4_id, $gtm_id, $yandex_metrika_id, $google_verification, $yandex_verification;
    public $default_locale = 'ru', $robots_extra;

    public function mount()
    {
        $s = Setting::getSettings();
        $this->settingId = $s->id;
        foreach ([
            'site_name','org_legal_name','bin','slogan','map','copyright','year',
            'work_time','phone','city_phone','email','address','geo_lat','geo_lng',
            'default_meta_title','default_meta_title_en','default_meta_title_kz',
            'default_meta_description','default_meta_description_en','default_meta_description_kz',
            'ga4_id','gtm_id','yandex_metrika_id','google_verification','yandex_verification',
            'default_locale','robots_extra',
        ] as $f) { $this->{$f} = $s->{$f}; }
        $this->default_locale = $s->default_locale ?: 'ru';
        $this->social_links = $s->social_links ?? [];
        $this->existing_logo = $s->logo;
        $this->existing_og = $s->default_og_image;
    }

    public function save()
    {
        $this->validate([
            'logo' => 'nullable|image|max:5120',
            'default_og_image' => 'nullable|image|max:5120',
            'email' => 'nullable|email',
        ]);

        $s = Setting::findOrFail($this->settingId);
        foreach ([
            'site_name','org_legal_name','bin','slogan','map','copyright','year',
            'work_time','phone','city_phone','email','address','geo_lat','geo_lng',
            'default_meta_title','default_meta_title_en','default_meta_title_kz',
            'default_meta_description','default_meta_description_en','default_meta_description_kz',
            'ga4_id','gtm_id','yandex_metrika_id','google_verification','yandex_verification',
            'default_locale','robots_extra',
        ] as $f) { $s->{$f} = $this->{$f}; }
        $s->social_links = array_values(array_filter($this->social_links, fn ($l) => ! empty($l['url'])));

        if ($this->logo) {
            $n = 'logo_' . Carbon::now()->timestamp . '.' . $this->logo->extension();
            $this->logo->storeAs('settings', $n); $s->logo = $n;
        }
        if ($this->default_og_image) {
            $n = 'og_' . Carbon::now()->timestamp . '.' . $this->default_og_image->extension();
            $this->default_og_image->storeAs('settings', $n); $s->default_og_image = $n;
        }
        $s->save();

        $this->existing_logo = $s->logo;
        $this->existing_og = $s->default_og_image;
        $this->logo = null; $this->default_og_image = null;
        $this->dispatch('toast', message: 'Настройки сохранены', type: 'success');
    }

    public function render()
    {
        return view('livewire.settings-component')->layout('layouts.admin');
    }
}
