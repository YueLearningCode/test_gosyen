<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require __DIR__ . '/../config/db.php';

// Cek login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Pesan sukses / error
$success_msg = "";
$error_msg   = "";

// ==========================================
// FETCH KARYAWAN UNTUK DROPDOWN
// ==========================================
$list_karyawan = [];
$res = mysqli_query($conn, "SELECT id_karyawan, nama_karyawan, posisi, gaji_pokok 
                            FROM karyawan ORDER BY nama_karyawan ASC");
while ($row = mysqli_fetch_assoc($res)) {
    $list_karyawan[] = $row;
}

// ==========================================
// HANDLE POST: HITUNG & SIMPAN GAJI
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $id_karyawan = (int) $_POST['employee'];
    $bulan       = (int) $_POST['month'];
    $tahun       = (int) $_POST['year'];
    $bonus_per   = (int) $_POST['bonus'];

    if ($id_karyawan === 0 || $bulan === 0 || $tahun === 0) {
        $error_msg = "Semua data wajib diisi!";
    } else {
        // Ambil data karyawan
        $sql = "SELECT gaji_pokok, nama_karyawan, posisi FROM karyawan WHERE id_karyawan = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $id_karyawan);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $karyawan = mysqli_fetch_assoc($result);

        if (!$karyawan) {
            $error_msg = "Karyawan tidak ditemukan!";
        } else {
            $gaji_pokok = (int) $karyawan['gaji_pokok'];

            // Hitung total service dalam bulan itu
            $sql = "SELECT SUM(jumlah_service) AS total_service
                    FROM laporan_service
                    WHERE id_karyawan = ?
                    AND MONTH(tanggal) = ?
                    AND YEAR(tanggal) = ?";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "iii", $id_karyawan, $bulan, $tahun);
            mysqli_stmt_execute($stmt);
            $service_result = mysqli_stmt_get_result($stmt);
            $service_data   = mysqli_fetch_assoc($service_result);

            $total_service = (int) ($service_data['total_service'] ?? 0);

            // Hitung bonus & total gaji
            $total_bonus = $total_service * $bonus_per;
            $total_gaji  = $gaji_pokok + $total_bonus;

            // Simpan ke tabel penggajian
            $sql = "INSERT INTO penggajian
                    (id_karyawan, bulan, tahun, total_service, gaji_pokok, 
                     bonus_per_service, total_bonus, total_gaji, tanggal_proses)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())";

            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "iiiiiiii",
                $id_karyawan, $bulan, $tahun, $total_service,
                $gaji_pokok, $bonus_per, $total_bonus, $total_gaji
            );
            if (mysqli_stmt_execute($stmt)) {
                $success_msg = "Penggajian berhasil dihitung & disimpan!";
            } else {
                $error_msg = "Gagal menyimpan penggajian: " . mysqli_error($conn);
            }
        }
    }
}

// ==========================================
// FETCH RIWAYAT PENGGAJIAN
// ==========================================
$riwayat = [];
$sql = "SELECT p.*, k.nama_karyawan, k.posisi 
        FROM penggajian p
        JOIN karyawan k ON k.id_karyawan = p.id_karyawan
        ORDER BY p.tanggal_proses DESC";
$res = mysqli_query($conn, $sql);
while ($row = mysqli_fetch_assoc($res)) {
    $riwayat[] = $row;
}

// ==========================================
// KONVERSI BULAN ANGKA → NAMA
// ==========================================
function nama_bulan($num)
{
    $arr = [
        1 => 'Januari', 'Februari', 'Maret', 'April',
        'Mei', 'Juni', 'Juli', 'Agustus',
        'September', 'Oktober', 'November', 'Desember'
    ];
    return $arr[$num] ?? $num;
}

?>

<!-- HTML DESIGN (PUNYA KAMU, DIPERTAHANKAN) -->
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta content="width=device-width, initial-scale=1.0" name="viewport" />
    <title>Penggajian Bulanan</title>

    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet" />

    <!-- TailwindCSS CDN -->
    <script src="https://cdn.tailwindcss.com?plugins=forms,typography"></script>

    <script>
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    colors: {
                        primary: "#A16207",
                        "background-light": "#FEFCE8",
                        "background-dark": "#1C1917",
                    },
                    fontFamily: {
                        display: ["Poppins", "sans-serif"],
                    },
                },
            },
        };
    </script>
</head>

<body class="font-display bg-background-light dark:bg-background-dark text-text-light dark:text-text-dark min-h-screen">

    <div class="container mx-auto p-4 sm:p-6 lg:p-8">
        <header class="mb-8">
            <h1 class="text-3xl font-bold">Penggajian Bulanan</h1>
            <a class="inline-flex items-center text-primary hover:underline mt-2 transition-colors"
                href="dashboard.php">
                <span class="material-symbols-outlined mr-1 text-lg">arrow_back</span>
                Kembali ke Dashboard
            </a>
        </header>

        <!-- Success Message -->
        <?php if ($success_msg): ?>
        <div class="p-3 mb-4 text-green-800 bg-green-200 rounded">
            <?= $success_msg ?>
        </div>
        <?php endif; ?>

        <?php if ($error_msg): ?>
        <div class="p-3 mb-4 text-red-800 bg-red-200 rounded">
            <?= $error_msg ?>
        </div>
        <?php endif; ?>

        <div class="grid grid-cols-1 gap-8">

            <!-- Hitung Gaji -->
            <section class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow-md">
                <h2 class="text-xl font-semibold mb-6">Hitung Gaji Karyawan</h2>

                <form class="space-y-6" method="post">

                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">

                        <!-- KARYAWAN -->
                        <div>
                            <label class="block text-sm font-medium mb-1" for="employee">Karyawan</label>
                            <select class="w-full rounded border p-2" id="employee" name="employee" required>
                                <option value="">-- Pilih Karyawan --</option>
                                <?php foreach ($list_karyawan as $k): ?>
                                <option value="<?= $k['id_karyawan'] ?>">
                                    <?= htmlspecialchars($k['nama_karyawan']) ?> (
                                    <?= htmlspecialchars($k['posisi']) ?>)
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- BULAN -->
                        <div>
                            <label class="block text-sm font-medium mb-1" for="month">Bulan</label>
                            <select class="w-full rounded border p-2" id="month" name="month" required>
                                <?php for ($b = 1; $b <= 12; $b++): ?>
                                <option value="<?= $b ?>">
                                    <?= nama_bulan($b) ?>
                                </option>
                                <?php endfor; ?>
                            </select>
                        </div>

                        <!-- TAHUN -->
                        <div>
                            <label class="block text-sm font-medium mb-1" for="year">Tahun</label>
                            <input class="w-full rounded border p-2" id="year" name="year" type="number"
                                value="<?= date('Y') ?>" required />
                        </div>

                        <!-- BONUS PER SERVICE -->
                        <div>
                            <label class="block text-sm font-medium mb-1" for="bonus">Bonus per Service (Rp)</label>
                            <input class="w-full rounded border p-2" id="bonus" name="bonus" type="number" value="10000"
                                required />
                        </div>

                    </div>

                    <div class="flex justify-end pt-4">
                        <button class="bg-primary text-white font-semibold py-2 px-6 rounded" type="submit">
                            Hitung & Simpan
                        </button>
                    </div>
                </form>
            </section>

            <!-- Riwayat Penggajian -->
            <section class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow-md">
                <h2 class="text-xl font-semibold mb-6">Riwayat Penggajian</h2>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead>
                            <tr>
                                <th class="p-3">Periode</th>
                                <th class="p-3">Nama Karyawan</th>
                                <th class="p-3">Posisi</th>
                                <th class="p-3 text-center">Total Service</th>
                                <th class="p-3 text-right">Gaji Pokok</th>
                                <th class="p-3 text-right">Total Bonus</th>
                                <th class="p-3 text-right font-bold">Total Gaji</th>
                                <th class="p-3 text-center">Tgl Proses</th>
                                <th class="p-3 text-center">Aksi</th>
                            </tr>
                        </thead>

                        <tbody class="divide-y">
                            <?php foreach ($riwayat as $row): ?>
                            <tr>
                                <td class="p-3">
                                    <?= nama_bulan($row['bulan']) . " " . $row['tahun'] ?>
                                </td>
                                <td class="p-3">
                                    <?= htmlspecialchars($row['nama_karyawan']) ?>
                                </td>
                                <td class="p-3">
                                    <?= htmlspecialchars($row['posisi']) ?>
                                </td>
                                <td class="p-3 text-center">
                                    <?= $row['total_service'] ?>
                                </td>
                                <td class="p-3 text-right">Rp
                                    <?= number_format($row['gaji_pokok'], 0, ',', '.') ?>
                                </td>
                                <td class="p-3 text-right">Rp
                                    <?= number_format($row['total_bonus'], 0, ',', '.') ?>
                                </td>
                                <td class="p-3 text-right font-bold">
                                    Rp
                                    <?= number_format($row['total_gaji'], 0, ',', '.') ?>
                                </td>
                                <td class="p-3 text-center">
                                    <?= $row['tanggal_proses'] ?>
                                </td>

                                <td class="p-3 text-center">
                                    <div class="flex justify-center gap-4">

                                        <a class="text-primary hover:underline font-medium"
                                            href="slip_gaji.php?id=<?= $row['id_penggajian'] ?>">
                                            Lihat Slip
                                        </a>

                                        <a class="text-primary hover:underline font-medium"
                                            href="bukti_pembayaran.php?id=<?= $row['id_penggajian'] ?>">
                                            Lihat Bukti
                                        </a>

                                    </div>
                                </td>

                            </tr>
                            <?php endforeach; ?>
                        </tbody>

                    </table>
                </div>

            </section>

        </div>
    </div>

</body>

</html>