<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\HandlesEntityPhoto;
use App\Services\SettingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SettingController extends Controller
{
    use HandlesEntityPhoto;

    public function __construct(
        private SettingService $settings,
    ) {
        $this->middleware('permission:settings view')->only(['edit']);
        $this->middleware('permission:settings edit')->only(['update']);
    }

    public function edit(): View
    {
        return view('settings.edit', [
            'settings' => $this->settings->all(),
            'logoUrl' => brand_has_custom_logo() ? brand_logo_url() : null,
        ]);
    }

    public function update(Request $request): RedirectResponse
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

        return redirect()
            ->route('settings.edit')
            ->with('success', 'Pengaturan aplikasi berhasil disimpan.');
    }
}
