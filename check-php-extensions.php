<?php
/**
 * Script untuk mengecek PHP Extensions yang terinstall
 * Akses file ini di browser: http://localhost:8000/check-php-extensions.php
 */

echo "<h1>PHP Extensions Check</h1>";
echo "<h2>Versi PHP: " . phpversion() . "</h2>";

echo "<h3>Extension yang Diperlukan:</h3>";
echo "<ul>";

$required = ['pdo', 'pdo_pgsql', 'pgsql'];
$allExtensions = get_loaded_extensions();

foreach ($required as $ext) {
    $installed = in_array($ext, $allExtensions);
    $status = $installed ? "✅ TERINSTALL" : "❌ TIDAK TERINSTALL";
    $color = $installed ? "green" : "red";
    echo "<li style='color: $color;'><strong>$ext</strong>: $status</li>";
}

echo "</ul>";

echo "<h3>Semua Extension yang Terinstall:</h3>";
echo "<pre>";
print_r($allExtensions);
echo "</pre>";

echo "<h3>PHP Configuration (php.ini):</h3>";
echo "<p><strong>Loaded php.ini:</strong> " . php_ini_loaded_file() . "</p>";
echo "<p><strong>Additional ini files:</strong> " . php_ini_scanned_files() . "</p>";

echo "<h3>PDO Drivers yang Tersedia:</h3>";
echo "<pre>";
print_r(PDO::getAvailableDrivers());
echo "</pre>";

echo "<hr>";
echo "<h2>Cara Mengaktifkan Extension PostgreSQL di Windows:</h2>";
echo "<ol>";
echo "<li>Buka file php.ini (lihat path di atas)</li>";
echo "<li>Cari baris yang berisi <code>;extension=pdo_pgsql</code></li>";
echo "<li>Hapus tanda <code>;</code> di depannya menjadi <code>extension=pdo_pgsql</code></li>";
echo "<li>Cari juga <code>;extension=pgsql</code> dan aktifkan juga</li>";
echo "<li>Simpan file php.ini</li>";
echo "<li>Restart PHP server (hentikan dan jalankan lagi)</li>";
echo "</ol>";

echo "<h3>Jika Extension File Tidak Ada:</h3>";
echo "<p>Anda perlu download DLL file untuk PostgreSQL extension:</p>";
echo "<ul>";
echo "<li>Download dari: <a href='https://pecl.php.net/package/pdo_pgsql' target='_blank'>https://pecl.php.net/package/pdo_pgsql</a></li>";
echo "<li>Atau install PostgreSQL client library terlebih dahulu</li>";
echo "<li>Copy DLL file ke folder <code>ext/</code> di folder PHP Anda</li>";
echo "</ul>";

