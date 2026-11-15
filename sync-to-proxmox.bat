@echo off
echo ============================================
echo   SYNC Laragon ke Proxmox - AR Service
echo ============================================
echo.
echo Server  : 192.168.66.22
echo User    : root
echo Remote  : /var/www/pos-and-service
echo.
echo CATATAN: .env, vendor/, storage/, node_modules/ TIDAK akan disalin
echo.

set /p PASS=Masukkan password SSH root: 

echo.
echo Memulai sinkronisasi...
echo.

"C:\Program Files (x86)\WinSCP\WinSCP.com" /command ^
  "open sftp://root:%PASS%@192.168.66.22/ -hostkey=*" ^
  "option batch abort" ^
  "option confirm off" ^
  "synchronize remote -delete -criteria=time -filemask=""| .env; vendor/; storage/; node_modules/; .git/; sync-to-proxmox.bat"" ""%~dp0\"" ""/var/www/pos-and-service""" ^
  "exit"

echo.
if %ERRORLEVEL% EQU 0 (
    echo [SUKSES] Semua file berhasil disinkronisasi ke Proxmox!
) else (
    echo [ERROR] Sinkronisasi gagal. Periksa koneksi dan password.
)
echo.
pause
