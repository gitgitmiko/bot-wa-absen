@echo off
echo Starting PHP Web Server...
echo.
echo Web akan berjalan di: http://localhost:8000
echo Tekan Ctrl+C untuk menghentikan server
echo.

REM Cek beberapa lokasi umum PHP di Windows
if exist "C:\xampp\php\php.exe" (
    "C:\xampp\php\php.exe" -S localhost:8000
) else if exist "C:\wamp64\bin\php\php8.2.0\php.exe" (
    "C:\wamp64\bin\php\php8.2.0\php.exe" -S localhost:8000
) else if exist "C:\php\php.exe" (
    "C:\php\php.exe" -S localhost:8000
) else (
    echo PHP tidak ditemukan!
    echo.
    echo Silakan install PHP atau XAMPP terlebih dahulu.
    echo Atau edit file ini dan tambahkan path PHP Anda.
    echo.
    pause
)

