# AR Service POS - Aplikasi Mobile & Panduan Pembuatan APK

Aplikasi Mobile POS dan Kasir Bengkel Servis untuk **AR Service** (Smartphone & Laptop) yang dirancang khusus dengan antarmuka layar sentuh (**User-Friendly Touch UI**) yang responsif, modern, dan cepat.

---

## ✨ Fitur Unggulan Mobile UI

1. **Kasir Layar Sentuh (Touch POS)**:
   - Grid produk visual dilengkapi status stok (`Stok Tersedia`, `Sisa Sedikit`, `Habis`) dan harga jelas.
   - Filter cepat berdasarkan kategori barang (Aksesoris, Sparepart, Unit, dll).
   - Kolom pencarian instan (nama atau kode barcode).
   - Bilah keranjang mengambang (*floating cart*) yang menampilkan total belanja dan jumlah item secara *real-time*.

2. **Keranjang & Pembayaran Cepat**:
   - Kontrol kuantitas (`+` / `−` / hapus) yang nyaman di jari.
   - Pilihan pelanggan: Umum (*Walk-in*) atau pelanggan terdaftar.
   - Tombol nominal uang cepat: `[Uang Pas]`, `[Rp 20.000]`, `[Rp 50.000]`, `[Rp 100.000]`, `[Rp 200.000]`.
   - Perhitungan otomatis kembalian uang secara akurat.
   - Pilihan metode bayar: **Tunai**, **Transfer Bank**, dan **QRIS**.

3. **Struk Digital & Cetak Termal**:
   - Tampilan nota kasir ramah printer termal (58mm/80mm).
   - Dukungan cetak langsung via sistem pencetakan Android (*PrintManager*).
   - Tombol **Kirim WhatsApp**: Mengirimkan nota penjualan rapi langsung ke WhatsApp pelanggan dalam 1 klik.

4. **Monitoring Servis & Stok**:
   - Pantau status tiket servis (Masuk, Pengerjaan, Menunggu Sparepart, Selesai, Diambil).
   - Cek stok barang menipis (*Low Stock Alert*).

5. **Koneksi Fleksibel**:
   - Dapat dihubungkan ke server lokal (Laragon), IP lokal Proxmox (`http://192.168.66.22`), maupun domain publik.
   - Dilengkapi tombol **Uji Koneksi** untuk memastikan status server *Online*.

---

## 📱 3 Cara Mendapatkan & Menjalankan APK

### Cara 1: Otomatis via GitHub Actions (Sangat Direkomendasikan)
Anda tidak perlu repot menginstal Android Studio atau SDK yang berukuran belasan Gigabyte di komputer lokal Anda:
1. Lakukan `git add .`, `git commit -m "feat: add mobile pos and apk build"`, lalu `git push origin main`.
2. Buka repository Anda di GitHub: [https://github.com/ariefpemalang/pos-penjualan](https://github.com/ariefpemalang/pos-penjualan)
3. Klik tab **Actions** di bagian atas.
4. Pilih workflow **Build Android APK**.
5. Setelah proses selesai (~1-2 menit), klik hasil build tersebut dan unduh file `AR-Service-POS.apk` pada bagian **Artifacts**.
6. Pasang (install) file APK ke smartphone atau tablet Android Anda.

---

### Cara 2: Build Lokal dengan Android Studio
Jika di komputer Anda telah terpasang **Android Studio**:
1. Buka Android Studio.
2. Pilih **Open**, lalu arahkan ke folder:
   `c:\laragon\www\ar-pos\mobile\android`
3. Tunggu Gradle selesai melakukan sinkronisasi file proyek.
4. Klik menu **Build** > **Build Bundle(s) / APK(s)** > **Build APK(s)**.
5. File APK akan terbentuk di:
   `mobile/android/app/build/outputs/apk/debug/app-debug.apk`

---

### Cara 3: Langsung Pasang di HP Tanpa APK (PWA Mode)
Aplikasi ini sudah berstandar **Progressive Web App (PWA)**:
1. Hubungkan HP Anda ke WiFi yang sama dengan komputer / server.
2. Buka browser **Google Chrome** di HP Anda.
3. Ketikkan alamat:
   `http://[IP-KOMPUTER-ANDA]/mobile` (misalnya: `http://192.168.66.22/mobile`)
4. Tekan tombol menu titik tiga (⋮) di pojok kanan atas Chrome.
5. Pilih **"Tambahkan ke Layar Utama"** / **"Instal Aplikasi"**.
6. Aplikasi AR POS akan otomatis terpasang di HP Anda lengkap dengan ikon logo AR Service, berjalan layar penuh (*fullscreen*), dan dapat dibuka layaknya aplikasi native dari Play Store.

---

## 🔑 Akun Login Bawaan

| Field | Nilai |
|---|---|
| **URL Server** | `http://192.168.66.22` (atau sesuaikan IP server Anda) |
| **API Key** | `pos-service-mobile-dev-key` |
| **Email** | `admin@arcell.store` |
| **Password** | `password` |
