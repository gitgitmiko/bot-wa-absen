@echo off
echo ========================================
echo Setup Project untuk XAMPP
echo ========================================
echo.

REM Cek apakah XAMPP ada
if not exist "C:\xampp\htdocs" (
    echo ERROR: XAMPP tidak ditemukan di C:\xampp\htdocs
    echo Silakan install XAMPP terlebih dahulu.
    pause
    exit /b 1
)

echo [1/3] Membuat folder project di htdocs...
if not exist "C:\xampp\htdocs\bot-wa-real" (
    mkdir "C:\xampp\htdocs\bot-wa-real"
    echo Folder dibuat: C:\xampp\htdocs\bot-wa-real
) else (
    echo Folder sudah ada: C:\xampp\htdocs\bot-wa-real
)

echo.
echo [2/3] Copy file project...
echo Ini akan menyalin semua file ke htdocs...
echo.
pause

xcopy /E /I /Y "%~dp0*" "C:\xampp\htdocs\bot-wa-real\" /EXCLUDE:exclude-list.txt 2>nul

if errorlevel 1 (
    echo.
    echo Catatan: Beberapa file mungkin tidak ter-copy.
    echo Silakan copy manual jika perlu.
) else (
    echo.
    echo File berhasil di-copy!
)

echo.
echo [3/3] Setup selesai!
echo.
echo ========================================
echo Langkah selanjutnya:
echo ========================================
echo 1. Pastikan Apache sudah running di XAMPP Control Panel
echo 2. Pastikan extension PostgreSQL aktif di C:\xampp\php\php.ini
echo    - Cari: ;extension=pdo_pgsql
echo    - Hapus tanda ; menjadi: extension=pdo_pgsql
echo    - Cari: ;extension=pgsql
echo    - Hapus tanda ; menjadi: extension=pgsql
echo 3. Restart Apache di XAMPP Control Panel
echo 4. Buka browser dan akses: http://localhost/bot-wa-real/login.php
echo.
echo ========================================
echo PENTING: Project ini menggunakan PostgreSQL
echo Pastikan PostgreSQL sudah terinstall dan running
echo ========================================
echo.
pause

