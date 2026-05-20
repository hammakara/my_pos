<?php

namespace App\Services;

use App\Models\CafeSetting;

class SettingService extends BaseService
{
    public function getAllSettings()
    {
        return CafeSetting::all()->pluck('value', 'key');
    }

    public function updateSettings(array $data)
    {
        foreach ($data as $key => $value) {
            CafeSetting::updateOrCreate(
                ['key' => $key],
                ['value' => $value]
            );
        }

        return $this->getAllSettings();
    }

    public function getSetting(string $key)
    {
        $setting = CafeSetting::where('key', $key)->first();
        return $setting ? $setting->value : null;
    }
}
