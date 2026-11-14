<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\Concerns\RespondsWithApi;
use App\Http\Controllers\Concerns\HandlesEntityPhoto;
use App\Http\Controllers\Controller;
use App\Services\SettingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    use HandlesEntityPhoto;
    use RespondsWithApi;

    public function __construct(
        private SettingService $settings,
    ) {
        $this->middleware('permission:settings view')->only(['show']);
        $this->middleware('permission:settings edit')->only(['update']);
    }

    public function show(): JsonResponse
    {
        return $this->apiSuccess('Pengaturan aplikasi berhasil diambil.', [
            'settings' => $this->settingsPayload(),
        ]);
    }

    public function update(Request $request): JsonResponse
    {
        $validated = $request->validate(array_merge([
            'app_name' => ['required', 'string', 'max:255'],
            'app_tagline' => ['nullable', 'string', 'max:255'],
            'app_description' => ['nullable', 'string', 'max:2000'],
            'company_address' => ['nullable', 'string', 'max:500'],
            'company_whatsapp' => ['nullable', 'string', 'max:30'],
        ], $this->photoRules()));

        $currentLogo = $this->settings->getString('company_logo') ?: null;
        $logoPath = $this->resolvePhotoPath($request, 'photos/branding', $currentLogo);

        $payload = [
            'app_name' => $validated['app_name'],
            'app_tagline' => $validated['app_tagline'] ?? '',
            'app_description' => $validated['app_description'] ?? '',
            'company_address' => trim((string) ($validated['company_address'] ?? '')),
            'company_whatsapp' => trim((string) ($validated['company_whatsapp'] ?? '')),
            'company_logo' => $logoPath ?? '',
        ];

        $this->settings->setMany($payload);

        return $this->apiSuccess('Pengaturan aplikasi berhasil disimpan.', [
            'settings' => $this->settingsPayload(),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function settingsPayload(): array
    {
        $all = $this->settings->all();
        $logoUrl = null;

        if (brand_has_custom_logo()) {
            $raw = brand_logo_url();
            $logoUrl = str_starts_with($raw, 'http://') || str_starts_with($raw, 'https://')
                ? $raw
                : url($raw);
        }

        return [
            'app_name' => (string) ($all['app_name'] ?? ''),
            'app_tagline' => (string) ($all['app_tagline'] ?? ''),
            'app_description' => (string) ($all['app_description'] ?? ''),
            'company_address' => (string) ($all['company_address'] ?? ''),
            'company_whatsapp' => (string) ($all['company_whatsapp'] ?? ''),
            'logo_url' => $logoUrl,
        ];
    }
}
