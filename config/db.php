<?php
// KONFIGURASI DATABASE
$host = "localhost";          // server database
$user = "root";               // user default XAMPP
$pass = "Gosyen";                   // password default (kosong)
$db   = "penggajian_hp";      // nama database yang kamu buat di phpMyAdmin

// KONEKSI
$conn = mysqli_connect($host, $user, $pass, $db);

// CEK KONEKSI
if (!$conn) {
    die("Koneksi database gagal: " . mysqli_connect_error());
}

// Optional: matikan ini di production, tapi berguna saat ngedevelop
// echo "Koneksi database BERHASIL!";
?>
