<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

// Slip hanya bisa diakses jika sudah login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

require __DIR__ . '/../config/db.php';

// Ambil id_penggajian dari URL
$id_penggajian = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($id_penggajian <= 0) {
    die("ID penggajian tidak valid.");
}

/*
==========================================
  AMBIL DATA PENGGAJIAN + KARYAWAN
==========================================
*/
$sql = "
    SELECT p.*, k.nama_karyawan, k.posisi, k.alamat, k.no_hp
    FROM penggajian p
    JOIN karyawan k ON k.id_karyawan = p.id_karyawan
    WHERE p.id_penggajian = ?
    LIMIT 1
";

$stmt = mysqli_prepare($conn, $sql);
if (!$stmt) {
    die("Gagal prepare penggajian: " . mysqli_error($conn));
}

mysqli_stmt_bind_param($stmt, "i", $id_penggajian);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$data = mysqli_fetch_assoc($result);

if (!$data) {
    die("Data slip gaji tidak ditemukan.");
}

/*
==========================================
  CEK / SIMPAN DATA KE dokumen_gaji
==========================================
*/
$sql_doc = "SELECT * FROM dokumen_gaji WHERE id_penggajian = ? LIMIT 1";
$stmt_doc = mysqli_prepare($conn, $sql_doc);
if (!$stmt_doc) {
    die("Gagal prepare dokumen_gaji: " . mysqli_error($conn));
}

mysqli_stmt_bind_param($stmt_doc, "i", $id_penggajian);
mysqli_stmt_execute($stmt_doc);
$res_doc = mysqli_stmt_get_result($stmt_doc);
$dok = mysqli_fetch_assoc($res_doc);

if ($dok) {
    // Sudah pernah dibuat sebelumnya
    $nama_file        = $dok['nama_file'];
    $tanggal_generate = $dok['tanggal_generate'];
} else {
    // Belum ada → buat record baru
    $nama_bersih = preg_replace('/[^a-zA-Z0-9_]/', '_', strtolower($data['nama_karyawan']));
    $nama_file   = "slip_gaji_" . $nama_bersih . "_" . $data['tahun'] . sprintf('%02d', $data['bulan']) . ".pdf";
    $tanggal_generate = date('Y-m-d');

    $sql_ins = "INSERT INTO dokumen_gaji (id_penggajian, nama_file, tanggal_generate)
                VALUES (?, ?, ?)";
    $stmt_ins = mysqli_prepare($conn, $sql_ins);
    if (!$stmt_ins) {
        die("Gagal prepare INSERT dokumen_gaji: " . mysqli_error($conn));
    }

    mysqli_stmt_bind_param($stmt_ins, "iss", $id_penggajian, $nama_file, $tanggal_generate);
    mysqli_stmt_execute($stmt_ins);
}

/*
==========================================
  FUNGSI BANTU & PERIODE
==========================================
*/
function nama_bulan($b) {
    $list = [
        1 => 'Januari', 2 => 'Februari', 3 => 'Maret',
        4 => 'April', 5 => 'Mei', 6 => 'Juni',
        7 => 'Juli', 8 => 'Agustus', 9 => 'September',
        10 => 'Oktober', 11 => 'November', 12 => 'Desember'
    ];
    return $list[$b] ?? $b;
}

$periode = nama_bulan($data['bulan']) . " " . $data['tahun'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Slip Gaji - <?php echo htmlspecialchars($data['nama_karyawan']); ?></title>
    <style>
        body {
            font-family: Arial, sans-serif;
        }
        .slip-container {
            width: 600px;
            margin: 20px auto;
            border: 1px solid #333;
            padding: 20px;
        }
        .header-slip {
            text-align: center;
            margin-bottom: 20px;
        }
        .header-slip h2 {
            margin: 0;
        }
        .section {
            margin-bottom: 15px;
        }
        table {
            width: 100%;
        }
        td {
            padding: 4px 0;
            vertical-align: top;
        }
        .total {
            font-weight: bold;
        }
        .btn-print {
            margin: 15px 0;
            text-align: center;
        }
        @media print {
            .btn-print {
                display: none;
            }
        }
    </style>
</head>
<body>

<div class="slip-container">
    <div class="header-slip">
        <h2>Slip Gaji Karyawan</h2>
        <p>Periode: <?php echo $periode; ?></p>
    </div>

    <div class="section">
        <h3>Info Dokumen</h3>
        <table>
            <tr>
                <td>Nama Dokumen</td>
                <td>: <?php echo htmlspecialchars($nama_file); ?></td>
            </tr>
            <tr>
                <td>Tanggal Generate</td>
                <td>: <?php echo htmlspecialchars($tanggal_generate); ?></td>
            </tr>
        </table>
    </div>

    <div class="section">
        <h3>Data Karyawan</h3>
        <table>
            <tr>
                <td>Nama</td>
                <td>: <?php echo htmlspecialchars($data['nama_karyawan']); ?></td>
            </tr>
            <tr>
                <td>Posisi</td>
                <td>: <?php echo htmlspecialchars($data['posisi']); ?></td>
            </tr>
            <tr>
                <td>Alamat</td>
                <td>: <?php echo htmlspecialchars($data['alamat']); ?></td>
            </tr>
            <tr>
                <td>No. HP</td>
                <td>: <?php echo htmlspecialchars($data['no_hp']); ?></td>
            </tr>
        </table>
    </div>

    <div class="section">
        <h3>Rincian Gaji</h3>
        <table>
            <tr>
                <td>Gaji Pokok</td>
                <td>: Rp <?php echo number_format($data['gaji_pokok']); ?></td>
            </tr>
            <tr>
                <td>Total Service</td>
                <td>: <?php echo (int) $data['total_service']; ?></td>
            </tr>
            <tr>
                <td>Bonus per Service</td>
                <td>: Rp <?php echo number_format($data['bonus_per_service']); ?></td>
            </tr>
            <tr>
                <td>Total Bonus</td>
                <td>: Rp <?php echo number_format($data['total_bonus']); ?></td>
            </tr>
            <tr class="total">
                <td>Total Gaji Diterima</td>
                <td>: Rp <?php echo number_format($data['total_gaji']); ?></td>
            </tr>
            <tr>
                <td>Tanggal Proses</td>
                <td>: <?php echo htmlspecialchars($data['tanggal_proses']); ?></td>
            </tr>
        </table>
    </div>

    <div class="btn-print">
        <button onclick="window.print()">Print / Simpan PDF</button>
    </div>
</div>

</body>
</html>
