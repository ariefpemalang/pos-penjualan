# pos-and-service

Aplikasi POS & service **AR Service** (smartphone dan laptop) berbasis Laravel 13.

## Paket

- Laravel Fortify (autentikasi)
- Spatie Laravel Permission (role & permission)
- Yajra DataTables (tabel server-side)

## Instalasi

```bash
composer install
cp .env.example .env
php artisan key:generate
# Sesuaikan DB di .env (MySQL)
php artisan migrate --seed
php artisan serve
```

## Login Default

| Field | Value |
|-------|-------|
| Email | `admin@arcell.store` |
| Password | `password` |

## Palette Warna (AR Service)

| Token | Hex | Penggunaan |
|-------|-----|------------|
| Emas utama | `#D4A017` | Primary, accent, tombol |
| Emas terang | `#F0C040` | Highlight |
| Amber gelap | `#8C5A14` | Hover / depth |
| Charcoal | `#2B2B2B` | Sidebar & login |

## Struktur Layout

Layout admin **custom** (bukan template pihak ketiga):

- Bootstrap **5.3.3** + Bootstrap Icons
- `public/css/atha-admin.css` — tema AR Service (light & dark)
- `public/js/admin.js` — sidebar mobile, theme toggle
- `resources/views/layouts/` — header, sidebar, footer
