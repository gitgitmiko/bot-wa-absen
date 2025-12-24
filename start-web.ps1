# Script untuk menjalankan PHP Web Server
Write-Host "Starting PHP Web Server..." -ForegroundColor Green
Write-Host ""
Write-Host "Web akan berjalan di: http://localhost:8000" -ForegroundColor Cyan
Write-Host "Tekan Ctrl+C untuk menghentikan server" -ForegroundColor Yellow
Write-Host ""

# Cek beberapa lokasi umum PHP di Windows
$phpPaths = @(
    "C:\xampp\php\php.exe",
    "C:\wamp64\bin\php\php8.2.0\php.exe",
    "C:\wamp64\bin\php\php8.1.0\php.exe",
    "C:\php\php.exe",
    "php.exe"  # Jika sudah di PATH
)

$phpFound = $false
foreach ($path in $phpPaths) {
    if (Test-Path $path) {
        Write-Host "PHP ditemukan di: $path" -ForegroundColor Green
        & $path -S localhost:8000
        $phpFound = $true
        break
    } elseif ($path -eq "php.exe") {
        # Coba cek apakah php ada di PATH
        $phpCheck = Get-Command php -ErrorAction SilentlyContinue
        if ($phpCheck) {
            Write-Host "PHP ditemukan di PATH" -ForegroundColor Green
            php -S localhost:8000
            $phpFound = $true
            break
        }
    }
}

if (-not $phpFound) {
    Write-Host "PHP tidak ditemukan!" -ForegroundColor Red
    Write-Host ""
    Write-Host "Silakan install PHP atau XAMPP terlebih dahulu." -ForegroundColor Yellow
    Write-Host "Atau edit file ini dan tambahkan path PHP Anda." -ForegroundColor Yellow
    Write-Host ""
    Write-Host "Download XAMPP: https://www.apachefriends.org/" -ForegroundColor Cyan
    Write-Host "Download PHP: https://windows.php.net/download/" -ForegroundColor Cyan
    Write-Host ""
    Read-Host "Tekan Enter untuk keluar"
}

