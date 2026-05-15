<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CafeSetting;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    public function index()
    {
        return CafeSetting::all()->pluck('value', 'key');
    }

    public function update(Request $request)
    {
        $settings = $request->validate([
            'cafe_name' => 'nullable|string',
            'currency'  => 'nullable|string',
            'tax_rate'  => 'nullable|numeric',
        ]);

        foreach ($settings as $key => $value) {
            CafeSetting::updateOrCreate(['key' => $key], ['value' => $value]);
        }

        return response()->json(['message' => 'Settings updated']);
    }
}
