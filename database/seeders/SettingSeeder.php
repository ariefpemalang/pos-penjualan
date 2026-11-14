<?php

namespace Database\Seeders;

use App\Services\SettingService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

class SettingSeeder extends Seeder
{
    public function run(): void
    {
        $settings = app(SettingService::class);

        $settings->setMany([
            'app_name' => config('branding.name'),
            'app_tagline' => config('branding.tagline'),
            'app_description' => config('branding.description'),
            'company_logo' => $this->seedBrandLogo(),
        ]);
    }

    private function seedBrandLogo(): string
    {
        $source = collect([
            public_path('logo.png'),
            base_path('logo.png'),
        ])->first(fn (string $path) => is_file($path));

        if ($source === null) {
            return '';
        }

        $path = 'photos/branding/logo.png';
        Storage::disk('public')->put($path, file_get_contents($source));

        return $path;
    }
}
