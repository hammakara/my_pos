<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\SettingService;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    protected $settingService;

    public function __construct(SettingService $settingService)
    {
        $this->settingService = $settingService;
    }

    public function index()
    {
        return response()->json($this->settingService->getAllSettings());
    }

    public function update(Request $request)
    {
        $settings = $request->validate([
            'cafe_name'     => 'nullable|string',
            'currency'      => 'nullable|string',
            'tax_rate'      => 'nullable|numeric',
            'exchange_rate' => 'nullable|numeric',
        ]);

        $this->settingService->updateSettings($settings);
        return response()->json(['message' => 'Settings updated']);
    }
}
