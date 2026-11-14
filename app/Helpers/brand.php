<?php

if (! function_exists('brand_name')) {
    function brand_name(): string
    {
        try {
            return app(\App\Services\SettingService::class)->getString('app_name', (string) config('branding.name'));
        } catch (\Throwable) {
            return (string) config('branding.name');
        }
    }
}

if (! function_exists('brand_tagline')) {
    function brand_tagline(): string
    {
        try {
            return app(\App\Services\SettingService::class)->getString('app_tagline', (string) config('branding.tagline'));
        } catch (\Throwable) {
            return (string) config('branding.tagline');
        }
    }
}

if (! function_exists('app_description')) {
    function app_description(): string
    {
        try {
            return app(\App\Services\SettingService::class)->getString('app_description', (string) config('branding.description', ''));
        } catch (\Throwable) {
            return (string) config('branding.description', '');
        }
    }
}

if (! function_exists('brand_address')) {
    function brand_address(): string
    {
        try {
            $addr = trim(app(\App\Services\SettingService::class)->getString('company_address'));
            return $addr !== '' ? $addr : 'jl. Perwira no.8 kapangsari Petarukan';
        } catch (\Throwable) {
            return 'jl. Perwira no.8 kapangsari Petarukan';
        }
    }
}

if (! function_exists('brand_whatsapp')) {
    function brand_whatsapp(): string
    {
        try {
            $wa = trim(app(\App\Services\SettingService::class)->getString('company_whatsapp'));
            return $wa !== '' ? $wa : '082322872201 / 087733300186';
        } catch (\Throwable) {
            return '082322872201 / 087733300186';
        }
    }
}

if (! function_exists('brand_logo_url')) {
    function brand_logo_url(): string
    {
        try {
            $path = trim(app(\App\Services\SettingService::class)->getString('company_logo'));

            if ($path !== '' && \Illuminate\Support\Facades\Storage::disk('public')->exists($path)) {
                $fullStoragePath = \Illuminate\Support\Facades\Storage::disk('public')->path($path);
                $v = file_exists($fullStoragePath) ? filemtime($fullStoragePath) : time();

                return \Illuminate\Support\Facades\Storage::disk('public')->url($path) . '?v=' . $v;
            }
        } catch (\Throwable) {
            // fallback ke logo default
        }

        $logo = (string) config('branding.logo', 'logo.png');
        $filePath = public_path($logo);
        $v = file_exists($filePath) ? filemtime($filePath) : time();

        return asset($logo) . '?v=' . $v;
    }
}

if (! function_exists('brand_has_custom_logo')) {
    function brand_has_custom_logo(): bool
    {
        try {
            $path = trim(app(\App\Services\SettingService::class)->getString('company_logo'));

            return $path !== '' && \Illuminate\Support\Facades\Storage::disk('public')->exists($path);
        } catch (\Throwable) {
            return false;
        }
    }
}

if (! function_exists('user_can_menu')) {
    function user_can_menu(?string $permission): bool
    {
        if ($permission === null || $permission === '') {
            return true;
        }

        $user = auth()->user();

        if (! $user) {
            return false;
        }

        if (! \Spatie\Permission\Models\Permission::query()->where('name', $permission)->exists()) {
            return false;
        }

        return $user->can($permission);
    }
}

if (! function_exists('is_active_menu')) {
    function is_active_menu(string|array $routes): string
    {
        $routes = (array) $routes;

        foreach ($routes as $route) {
            if (request()->routeIs($route) || request()->is($route)) {
                return ' active';
            }
        }

        return '';
    }
}

if (! function_exists('is_active_submenu')) {
    function is_active_submenu(array $submenus): string
    {
        $routes = collect($submenus)->pluck('route')->filter()->all();

        return is_active_menu($routes);
    }
}

if (! function_exists('terbilang')) {
    function terbilang($number): string
    {
        $number = abs((float) $number);
        $words = ['', 'Satu', 'Dua', 'Tiga', 'Empat', 'Lima', 'Enam', 'Tujuh', 'Delapan', 'Sembilan', 'Sepuluh', 'Sebelas'];
        $result = '';

        if ($number < 12) {
            $result = ' '.$words[(int) $number];
        } elseif ($number < 20) {
            $result = terbilang($number - 10).' Belas ';
        } elseif ($number < 100) {
            $result = terbilang((int) ($number / 10)).' Puluh '.terbilang($number % 10);
        } elseif ($number < 200) {
            $result = ' Seratus '.terbilang($number - 100);
        } elseif ($number < 1000) {
            $result = terbilang((int) ($number / 100)).' Ratus '.terbilang($number % 100);
        } elseif ($number < 2000) {
            $result = ' Seribu '.terbilang($number - 1000);
        } elseif ($number < 1000000) {
            $result = terbilang((int) ($number / 1000)).' Ribu '.terbilang($number % 1000);
        } elseif ($number < 1000000000) {
            $result = terbilang((int) ($number / 1000000)).' Juta '.terbilang($number % 1000000);
        } elseif ($number < 1000000000000) {
            $result = terbilang((int) ($number / 1000000000)).' Miliar '.terbilang(fmod($number, 1000000000));
        }

        return preg_replace('/\s+/', ' ', trim($result));
    }
}
