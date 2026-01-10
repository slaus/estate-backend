<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\SettingResource;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SettingController extends Controller
{
    public function index(Request $request, $group)
    {
        $settings = Setting::where('group', $group)
            ->get()
            ->mapWithKeys(function ($setting) {
                return [$setting->name => $setting->value];
            });

        return response()->json($settings);
    }

    public function store(Request $request, $group)
    {
        $validator = Validator::make($request->all(), [
            'settings' => 'required|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors()
            ], 422);
        }

        foreach ($request->settings as $name => $value) {
            Setting::updateOrCreate(
                ['group' => $group, 'name' => $name],
                ['value' => $value]
            );
        }

        $settings = Setting::where('group', $group)
            ->get()
            ->mapWithKeys(function ($setting) {
                return [$setting->name => $setting->value];
            });

        return response()->json([
            'message' => 'Налаштування збережено',
            'data' => $settings
        ]);
    }

    public function showPublic(Request $request, $group)
    {
        $lang = $request->header('Accept-Language', 'uk');
        
        $settings = Setting::where('group', $group)
            ->get()
            ->mapWithKeys(function ($setting) use ($lang) {
                $value = $setting->value;
                
                if (is_array($value)) {
                    $result = $value[$lang] ?? $value['uk'] ?? array_values($value)[0] ?? '';
                } else {
                    $result = $value;
                }
                
                return [$setting->name => $result];
            });

        return response()->json([
            'success' => true,
            'group' => $group,
            'data' => $settings,
        ]);
    }
}