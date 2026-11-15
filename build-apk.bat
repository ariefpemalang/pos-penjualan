@echo off
echo ========================================================
echo   BUILD APK - AR SERVICE POS (ANDROID)
echo ========================================================
echo.

echo 1. Menyinkronkan file web mobile ke Android Assets...
if not exist "mobile\android\app\src\main\assets\www" mkdir "mobile\android\app\src\main\assets\www"
xcopy /E /I /Y "mobile\www\*" "mobile\android\app\src\main\assets\www\"
xcopy /E /I /Y "mobile\www\*" "public\mobile\"

echo.
echo 2. Memeriksa ketersediaan Gradle / Android SDK...
cd mobile\android

where gradle >nul 2>nul
if %ERRORLEVEL% EQU 0 (
    echo Gradle terdeteksi, memulai build debug APK...
    call gradle assembleDebug
    if %ERRORLEVEL% EQU 0 (
        echo [SUKSES] APK berhasil dibuat di:
        echo mobile\android\app\build\outputs\apk\debug\app-debug.apk
        copy /Y "app\build\outputs\apk\debug\app-debug.apk" "..\..\AR-Service-POS.apk"
        echo Salinan tersimpan di root folder: AR-Service-POS.apk
        cd ..\..
        pause
        exit /b 0
    )
)

if exist "gradlew.bat" (
    echo Gradle wrapper terdeteksi, menjalankan gradlew assembleDebug...
    call gradlew.bat assembleDebug
    if %ERRORLEVEL% EQU 0 (
        echo [SUKSES] APK berhasil dibuat di:
        echo mobile\android\app\build\outputs\apk\debug\app-debug.apk
        copy /Y "app\build\outputs\apk\debug\app-debug.apk" "..\..\AR-Service-POS.apk"
        echo Salinan tersimpan di root folder: AR-Service-POS.apk
        cd ..\..
        pause
        exit /b 0
    )
)

cd ..\..
echo.
echo [INFO] Lingkungan build Android lokal (Android SDK / Gradle) belum lengkap.
echo.
echo TIPS MUDAH UNTUK MENDAPATKAN FILE APK:
echo 1. Buka folder 'mobile\android' menggunakan Android Studio, lalu klik:
echo    Build -^> Build Bundle(s) / APK(s) -^> Build APK(s)
echo.
echo 2. Atau push perubahan ini ke GitHub (origin main).
echo    GitHub Actions akan otomatis membuat file APK (.apk) di tab 'Actions'!
echo.
echo 3. Atau buka langsung di HP Anda melalui Chrome:
echo    http://[IP-KOMPUTER]:8000/mobile
echo    lalu klik menu titik 3 di Chrome -^> 'Tambahkan ke Layar Utama' (Install PWA).
echo.
pause
