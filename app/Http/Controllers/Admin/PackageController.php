<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Package;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

/**
 * P3-T1: package CRUD. Feature toggles and limit fields are rendered from
 * config/features.php, so the GUI can never drift from the code registry.
 *
 * Prices are nullable on purpose: the owner has not decided them yet, and a
 * null price renders "Hubungi kami" on the landing page.
 */
class PackageController extends Controller
{
    /** Limit keys stored in the package `limits` JSON. */
    public const LIMIT_KEYS = [
        'max_guests',
        'max_gallery_images',
        'max_photobooth_per_guest',
        'active_days_after_event',
    ];

    public function index()
    {
        $packages = Package::query()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->map(fn (Package $package) => [
                'id' => $package->id,
                'name' => $package->name,
                'slug' => $package->slug,
                'description' => $package->description,
                'price' => $package->price,
                'features' => $package->featureFlags(),
                'limits' => $package->limitValues(),
                'is_public' => $package->is_public,
                'is_active' => $package->is_active,
                'sort_order' => $package->sort_order,
                'invitations_count' => $package->invitations()->count(),
            ]);

        return Inertia::render('Admin/PackageIndex', [
            'packages' => $packages,
            'featureRegistry' => collect(config('features'))->map(
                fn (array $feature, string $key) => [
                    'key' => $key,
                    'label' => $feature['label'] ?? $key,
                    'description' => $feature['description'] ?? null,
                ]
            )->values(),
            'limitKeys' => self::LIMIT_KEYS,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $this->validated($request);

        Package::create($this->payload($validated));

        return back()->with('success', 'Paket berhasil dibuat.');
    }

    public function update(Request $request, int $id)
    {
        $package = Package::findOrFail($id);
        $validated = $this->validated($request, $package->id);

        $package->update($this->payload($validated));

        return back()->with('success', 'Paket berhasil diperbarui.');
    }

    public function destroy(int $id)
    {
        $package = Package::findOrFail($id);

        // Invitations keep working: the FK is nullOnDelete, which restores the
        // "no package = all features on" default.
        $package->delete();

        return back()->with('success', 'Paket dihapus. Undangan yang memakainya kembali tanpa paket.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?int $ignoreId = null): array
    {
        $featureKeys = array_keys(config('features', []));

        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => [
                'required', 'string', 'max:255',
                'regex:/^[a-z0-9]+(-[a-z0-9]+)*$/',
                Rule::unique('packages', 'slug')->ignore($ignoreId),
            ],
            'description' => ['nullable', 'string', 'max:1000'],
            'price' => ['nullable', 'integer', 'min:0'],
            'features' => ['nullable', 'array'],
            'features.*' => ['in:'.implode(',', $featureKeys)],
            'limits' => ['nullable', 'array'],
            'limits.*' => ['nullable', 'integer', 'min:0'],
            'is_public' => ['required', 'boolean'],
            'is_active' => ['required', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:1000'],
        ]);
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    private function payload(array $validated): array
    {
        $featureKeys = array_keys(config('features', []));
        $enabled = $validated['features'] ?? [];

        $features = [];
        foreach ($featureKeys as $key) {
            $features[$key] = in_array($key, $enabled, true);
        }

        $limits = [];
        foreach (self::LIMIT_KEYS as $key) {
            $value = $validated['limits'][$key] ?? null;
            $limits[$key] = $value === null || $value === '' ? null : (int) $value;
        }

        return [
            'name' => $validated['name'],
            'slug' => Str::slug($validated['slug']),
            'description' => $validated['description'] ?? null,
            'price' => $validated['price'] ?? null,
            'features' => $features,
            'limits' => $limits,
            'is_public' => (bool) $validated['is_public'],
            'is_active' => (bool) $validated['is_active'],
            'sort_order' => $validated['sort_order'] ?? 0,
        ];
    }
}
