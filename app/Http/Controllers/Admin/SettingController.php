<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\Settings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

/**
 * P3-T5: platform-wide settings (business identity, WhatsApp CTA, branding).
 * These values feed the landing page, the SEO fallbacks and the admin layout.
 */
class SettingController extends Controller
{
    /** Keys this screen owns. Values are stored as JSON in `settings`. */
    private const KEYS = [
        'business_name',
        'whatsapp_number',
        'instagram_url',
        'footer_text',
        'platform_logo',
    ];

    public function edit()
    {
        return Inertia::render('Admin/SettingsIndex', [
            'settings' => collect(self::KEYS)->mapWithKeys(
                fn (string $key) => [$key => Settings::get($key)]
            ),
        ]);
    }

    public function update(Request $request)
    {
        $request->validate([
            'business_name' => ['nullable', 'string', 'max:255'],
            'whatsapp_number' => ['nullable', 'string', 'max:32'],
            'instagram_url' => ['nullable', 'string', 'max:255'],
            'footer_text' => ['nullable', 'string', 'max:500'],
            'platform_logo' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:2048'],
        ], [
            'platform_logo.image' => 'Logo harus berupa gambar.',
            'platform_logo.max' => 'Logo maksimal 2 MB.',
        ]);

        foreach (['business_name', 'whatsapp_number', 'instagram_url', 'footer_text'] as $key) {
            if ($request->has($key)) {
                $this->put($key, $request->input($key));
            }
        }

        if ($request->hasFile('platform_logo')) {
            $path = $request->file('platform_logo')->store('platform', 'public');
            $this->put('platform_logo', Storage::url($path));
        }

        Settings::flush();

        return back()->with('success', 'Pengaturan berhasil disimpan.');
    }

    private function put(string $key, mixed $value): void
    {
        $normalized = is_string($value) ? trim($value) : $value;
        $normalized = $normalized === '' ? null : $normalized;

        DB::table('settings')->updateOrInsert(
            ['key' => $key],
            ['value' => json_encode($normalized), 'updated_at' => now(), 'created_at' => now()]
        );
    }
}
