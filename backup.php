<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

require __DIR__ . '/../config/db.php';

// Nama database kamu
$nama_database = "penggajian";   // sesuaikan kalo beda

// Buat nama file
$nama_file = "backup_" . $nama_database . "_" . date("Ymd_His") . ".sql";

// Header agar browser mendownload file
header("Content-Disposition: attachment; filename=\"$nama_file\"");
header("Content-Type: application/sql");

// Ambil semua tabel dalam DB
$tables = [];
$res = mysqli_query($conn, "SHOW TABLES");
while ($row = mysqli_fetch_row($res)) {
    $tables[] = $row[0];
}

$output = "";

// Loop setiap tabel
foreach ($tables as $table) {

    // --- Dump struktur tabel ---
    $output .= "-- ----------------------------------------\n";
    $output .= "-- Struktur tabel: `$table`\n";
    $output .= "-- ----------------------------------------\n\n";

    $res2 = mysqli_query($conn, "SHOW CREATE TABLE `$table`");
    $row2 = mysqli_fetch_row($res2);

    $output .= "DROP TABLE IF EXISTS `$table`;\n";
    $output .= $row2[1] . ";\n\n";

    // --- Dump data tabel ---
    $output .= "-- Data untuk tabel: `$table`\n\n";

    $res3 = mysqli_query($conn, "SELECT * FROM `$table`");
    $jumlah_kolom = mysqli_num_fields($res3);

    while ($row3 = mysqli_fetch_row($res3)) {

        $output .= "INSERT INTO `$table` VALUES(";

        for ($i = 0; $i < $jumlah_kolom; $i++) {
            if ($row3[$i] === NULL) {
                $output .= "NULL";
            } else {
                $output .= "'" . mysqli_real_escape_string($conn, $row3[$i]) . "'";
            }

            if ($i < $jumlah_kolom - 1) {
                $output .= ",";
            }
        }

        $output .= ");\n";
    }

    $output .= "\n\n";
}

// Keluarkan file ke browser
echo $output;
exit;
?>
