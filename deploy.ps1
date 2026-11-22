# Deploy script for AR-POS to Proxmox CT 20488 (192.168.66.20)
$ErrorActionPreference = "Stop"

$CT_IP = "192.168.66.20"
$CT_USER = "root"
$REMOTE_PATH = "/var/www/arcell.store"

Write-Host "==========================================" -ForegroundColor Cyan
Write-Host "🚀 Memulai Auto-Deploy ke CT 20488 (AR-POS)..." -ForegroundColor Cyan
Write-Host "==========================================" -ForegroundColor Cyan

# 1. Pastikan commit lokal ter-push ke GitHub
Write-Host "`n[1/3] Memeriksa & Mengirim update ke GitHub..." -ForegroundColor Yellow
git push origin main

# 2. Tarik update di server CT dan bersihkan cache Laravel
Write-Host "`n[2/3] Menerapkan update di CT $CT_IP..." -ForegroundColor Yellow
$remoteCommands = @"
cd $REMOTE_PATH
git config --global --add safe.directory $REMOTE_PATH
git pull origin main
php artisan migrate --force
[ -L public/storage ] || php artisan storage:link --quiet || true
php artisan optimize:clear
chown -R www-data:www-data $REMOTE_PATH
chmod -R 775 $REMOTE_PATH/storage $REMOTE_PATH/bootstrap/cache
"@

ssh -o StrictHostKeyChecking=no "$CT_USER@$CT_IP" "$remoteCommands"

Write-Host "`n[3/3] Selesai!" -ForegroundColor Green
Write-Host "==========================================" -ForegroundColor Green
Write-Host "✅ Sukses! Aplikasi di CT 20488 sudah update." -ForegroundColor Green
Write-Host "==========================================" -ForegroundColor Green
