<?php
// TAMPILKAN SEMUA ERROR (BIAR GAK CUMA LAYAR PUTIH)
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h3>Mulai test koneksi...</h3>";

// panggil file koneksi
require __DIR__ . '/../config/db.php';

echo "<p>Koneksi ke database BERHASIL dibuat.</p>";

// tes query sederhana
$sql = "SHOW TABLES";
$result = mysqli_query($conn, $sql);

if (!$result) {
    die("<p>Query gagal: " . mysqli_error($conn) . "</p>");
}

echo "<h4>Daftar tabel di database:</h4>";
echo "<ul>";
while ($row = mysqli_fetch_row($result)) {
    echo "<li>" . htmlspecialchars($row[0]) . "</li>";
}
echo "</ul>";
