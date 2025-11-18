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

$id_user = (int) $_SESSION['user_id'];

// ==== AMBIL DATA USER DARI DATABASE (untuk nama, jabatan, foto) ====
$sqlUser = "SELECT nama_lengkap, username, jabatan, foto FROM user WHERE id_user = ? LIMIT 1";
$stmtUser = mysqli_prepare($conn, $sqlUser);
mysqli_stmt_bind_param($stmtUser, "i", $id_user);
mysqli_stmt_execute($stmtUser);
$resUser = mysqli_stmt_get_result($stmtUser);
$dbUser = mysqli_fetch_assoc($resUser);

// Kalau ada di DB, pakai itu. Kalau tidak, fallback ke session.
$nama_user = $dbUser['nama_lengkap']
    ?? ($_SESSION['nama'] ?? ($_SESSION['username'] ?? 'User'));
$username  = $dbUser['username'] ?? ($_SESSION['username'] ?? '');
$role_user = $dbUser['jabatan']  ?? ($_SESSION['role'] ?? 'user');
$foto_user = $dbUser['foto'] ?? null;

// Avatar initial (dipakai kalau tidak ada foto)
$initial = strtoupper(substr($nama_user !== '' ? $nama_user : $username, 0, 1));

// ====== HELPER ======
function nama_bulan($b) {
    $list = [
        1 => 'Januari', 2 => 'Februari', 3 => 'Maret',
        4 => 'April', 5 => 'Mei', 6 => 'Juni',
        7 => 'Juli', 8 => 'Agustus', 9 => 'September',
        10 => 'Oktober', 11 => 'November', 12 => 'Desember'
    ];
    return $list[$b] ?? $b;
}

// ====== PERIODE (BULAN/TAHUN) YANG DIPILIH DI DASHBOARD ======
$bulan = isset($_GET['bulan']) ? (int) $_GET['bulan'] : (int) date('n');
$tahun = isset($_GET['tahun']) ? (int) $_GET['tahun'] : (int) date('Y');
if ($bulan < 1 || $bulan > 12) $bulan = (int)date('n');

// Hitung prev/next bulan untuk tombol kalender
$prevMonth = $bulan - 1;
$prevYear  = $tahun;
if ($prevMonth < 1) {
    $prevMonth = 12;
    $prevYear--;
}
$nextMonth = $bulan + 1;
$nextYear  = $tahun;
if ($nextMonth > 12) {
    $nextMonth = 1;
    $nextYear++;
}

// ====== TOTAL KARYAWAN ======
$total_karyawan = 0;
$res = mysqli_query($conn, "SELECT COUNT(*) AS jml FROM karyawan");
if ($res) {
    $row = mysqli_fetch_assoc($res);
    $total_karyawan = (int) ($row['jml'] ?? 0);
}

// ====== TOTAL GAJI BULAN INI (SUDAH DIBAYAR) ======
$total_gaji_bulan_ini = 0;
$sql = "SELECT COALESCE(SUM(total_gaji),0) AS total
        FROM penggajian
        WHERE status_pembayaran = 'lunas'
          AND bulan = ?
          AND tahun = ?";
$stmt = mysqli_prepare($conn, $sql);
if ($stmt) {
    mysqli_stmt_bind_param($stmt, "ii", $bulan, $tahun);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);
    $total_gaji_bulan_ini = (float) ($row['total'] ?? 0);
}

// ====== MENUNGGU PEMBAYARAN (BELUM LUNAS) DI BULAN/Tahun TERPILIH ======
$menunggu_pembayaran = 0;
$sql = "SELECT COUNT(*) AS jml
        FROM penggajian
        WHERE status_pembayaran = 'belum'
          AND bulan = ?
          AND tahun = ?";
$stmt = mysqli_prepare($conn, $sql);
if ($stmt) {
    mysqli_stmt_bind_param($stmt, "ii", $bulan, $tahun);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);
    $menunggu_pembayaran = (int) ($row['jml'] ?? 0);
}

// ====== PEMBAYARAN TERDEKAT (3 data penggajian belum dibayar) ======
$pembayaran_terdekat = [];
$sql = "SELECT p.*, k.nama_karyawan
        FROM penggajian p
        JOIN karyawan k ON k.id_karyawan = p.id_karyawan
        WHERE p.status_pembayaran = 'belum'
        ORDER BY p.tahun ASC, p.bulan ASC, p.id_penggajian ASC
        LIMIT 3";
$res = mysqli_query($conn, $sql);
if ($res) {
    while ($row = mysqli_fetch_assoc($res)) {
        $pembayaran_terdekat[] = $row;
    }
}

// ====== DATA UNTUK CHART: TOTAL GAJI PER BULAN (HANYA LUNAS) ======
$chart_labels = [];
$chart_values = [];

$sql = "SELECT tahun, bulan, COALESCE(SUM(total_gaji),0) AS total
        FROM penggajian
        WHERE status_pembayaran = 'lunas'
        GROUP BY tahun, bulan
        ORDER BY tahun DESC, bulan DESC
        LIMIT 6";
$res = mysqli_query($conn, $sql);
$temp = [];
if ($res) {
    while ($row = mysqli_fetch_assoc($res)) {
        $temp[] = $row;
    }
    // Balik urutan jadi kronologis (lama -> baru)
    $temp = array_reverse($temp);
    foreach ($temp as $r) {
        $chart_labels[] = nama_bulan((int)$r['bulan']) . " " . $r['tahun'];
        $chart_values[] = (float)$r['total'];
    }
}

// ====== DATA KALENDER UNTUK PERIODE DIPILIH ======
$firstDayTs    = mktime(0, 0, 0, $bulan, 1, $tahun);
$daysInMonth   = (int) date('t', $firstDayTs);
$firstWeekday  = (int) date('w', $firstDayTs); // 0=Sunday (S)
$calendarCells = [];

// Kosong sebelum tanggal 1
for ($i = 0; $i < $firstWeekday; $i++) {
    $calendarCells[] = '';
}
// Isi tanggal
for ($d = 1; $d <= $daysInMonth; $d++) {
    $calendarCells[] = $d;
}
// Pad sampai kelipatan 7
while (count($calendarCells) % 7 !== 0) {
    $calendarCells[] = '';
}

$todayDay   = (int) date('j');
$todayMonth = (int) date('n');
$todayYear  = (int) date('Y');
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta content="width=device-width, initial-scale=1.0" name="viewport" />
    <title>Payroll Dashboard</title>

    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons+Outlined" rel="stylesheet" />

    <!-- Tailwind -->
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
                    borderRadius: {
                        DEFAULT: "0.5rem",
                    },
                },
            },
        };
    </script>

    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <link rel="stylesheet" href="styles.css" />
</head>

<body class="font-display bg-background-light dark:bg-background-dark text-stone-700 dark:text-stone-300">
    <div class="flex min-h-screen">
        <aside
            class="w-72 bg-white dark:bg-stone-900/50 p-6 flex flex-col justify-between border-r border-stone-200 dark:border-stone-800">
            <div>
                <div class="mb-12 flex items-center gap-3">
                    <span class="material-icons-outlined text-primary text-3xl">payments</span>
                    <h1 class="text-2xl font-bold text-stone-800 dark:text-white">PayRoll</h1>
                </div>
                <nav class="flex flex-col space-y-2">
                    <a class="flex items-center gap-4 px-4 py-3 rounded-lg text-white bg-primary font-medium shadow-sm"
                        href="dashboard.php">
                        <span class="material-icons-outlined">dashboard</span>
                        <span>Beranda</span>
                    </a>
                    <a class="flex items-center gap-4 px-4 py-3 rounded-lg hover:bg-stone-100 dark:hover:bg-stone-800/60 transition-colors duration-200"
                        href="karyawan.php">
                        <span class="material-icons-outlined">group</span>
                        <span>Kelola Data Karyawan</span>
                    </a>
                    <a class="flex items-center gap-4 px-4 py-3 rounded-lg hover:bg-stone-100 dark:hover:bg-stone-800/60 transition-colors duration-200"
                        href="servis.php">
                        <span class="material-icons-outlined">summarize</span>
                        <span>Laporan Service Harian</span>
                    </a>
                    <a class="flex items-center gap-4 px-4 py-3 rounded-lg hover:bg-stone-100 dark:hover:bg-stone-800/60 transition-colors duration-200"
                        href="gaji.php">
                        <span class="material-icons-outlined">calculate</span>
                        <span>Hitung Gaji Bulanan</span>
                    </a>
                    <a class="flex items-center gap-4 px-4 py-3 rounded-lg hover:bg-stone-100 dark:hover:bg-stone-800/60 transition-colors duration-200"
                        href="gaji.php">
                        <span class="material-icons-outlined">history</span>
                        <span>Riwayat Penggajian</span>
                    </a>
                    <a class="flex items-center gap-4 px-4 py-3 rounded-lg hover:bg-stone-100 dark:hover:bg-stone-800/60 transition-colors duration-200"
                        href="pembayaran.php">
                        <span class="material-icons-outlined">paid</span>
                        <span>Pembayaran Gaji</span>
                    </a>
                    <a class="flex items-center gap-4 px-4 py-3 rounded-lg hover:bg-stone-100 dark:hover:bg-stone-800/60 transition-colors duration-200"
                        href="backup.php">
                        <span class="material-icons-outlined">backup</span>
                        <span>Backup Database</span>
                    </a>
                </nav>
            </div>
            <div>
                <a class="flex items-center gap-4 px-4 py-3 rounded-lg text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors duration-200"
                    href="logout.php">
                    <span class="material-icons-outlined">logout</span>
                    <span>Logout</span>
                </a>
            </div>
        </aside>

        <main class="flex-1 p-8 md:p-12">
            <header class="mb-12">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="text-3xl font-bold text-stone-800 dark:text-white">Beranda</h2>
                        <p class="text-stone-500 dark:text-stone-400 mt-1">
                            Selamat datang, <?= htmlspecialchars($nama_user); ?>
                        </p>
                    </div>
                    <div class="flex items-center gap-4">
                        <div class="text-right">
                            <p class="font-semibold text-stone-800 dark:text-white">
                                <?= htmlspecialchars($nama_user); ?>
                            </p>
                            <p class="text-sm text-stone-500 dark:text-stone-400">
                                <?= htmlspecialchars(ucfirst($role_user)); ?>
                            </p>
                        </div>

                        <!-- AVATAR PROFIL (PAKAI FOTO JIKA ADA, KALAU TIDAK INITIAL) -->
                        <?php if (!empty($foto_user)): ?>
                            <a href="profil.php">
                                <img src="uploads/<?= htmlspecialchars($foto_user); ?>"
                                     alt="Foto Profil"
                                     class="w-12 h-12 rounded-full object-cover shadow hover:opacity-80 transition" />
                            </a>
                        <?php else: ?>
                            <a href="profil.php"
                               class="w-12 h-12 rounded-full flex items-center justify-center text-xl font-bold text-white hover:opacity-80 transition"
                               style="background-color:#A16207;">
                                <?= $initial; ?>
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </header>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <div class="lg:col-span-2 space-y-8">
                    <!-- KARTU STATISTIK -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div
                            class="bg-white dark:bg-stone-900/50 p-6 rounded-lg border border-stone-200 dark:border-stone-800">
                            <div class="flex items-center gap-4">
                                <div class="p-3 bg-amber-100 dark:bg-amber-900/50 rounded-full">
                                    <span
                                        class="material-icons-outlined text-amber-600 dark:text-amber-400">groups</span>
                                </div>
                                <div>
                                    <p class="text-sm text-stone-500 dark:text-stone-400">Total Karyawan</p>
                                    <p class="text-2xl font-bold text-stone-800 dark:text-white">
                                        <?= number_format($total_karyawan); ?>
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div
                            class="bg-white dark:bg-stone-900/50 p-6 rounded-lg border border-stone-200 dark:border-stone-800">
                            <div class="flex items-center gap-4">
                                <div class="p-3 bg-green-100 dark:bg-green-900/50 rounded-full">
                                    <span
                                        class="material-icons-outlined text-green-600 dark:text-green-400">account_balance_wallet</span>
                                </div>
                                <div>
                                    <p class="text-sm text-stone-500 dark:text-stone-400">
                                        Total Gaji Bulan Ini<br>
                                        <span class="text-xs">(<?= nama_bulan($bulan) . " " . $tahun; ?>)</span>
                                    </p>
                                    <p class="text-2xl font-bold text-stone-800 dark:text-white">
                                        Rp <?= number_format($total_gaji_bulan_ini, 0, ',', '.'); ?>
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div
                            class="bg-white dark:bg-stone-900/50 p-6 rounded-lg border border-stone-200 dark:border-stone-800">
                            <div class="flex items-center gap-4">
                                <div class="p-3 bg-red-100 dark:bg-red-900/50 rounded-full">
                                    <span
                                        class="material-icons-outlined text-red-600 dark:text-red-400">pending_actions</span>
                                </div>
                                <div>
                                    <p class="text-sm text-stone-500 dark:text-stone-400">
                                        Menunggu Pembayaran<br>
                                        <span class="text-xs">(<?= nama_bulan($bulan) . " " . $tahun; ?>)</span>
                                    </p>
                                    <p class="text-2xl font-bold text-stone-800 dark:text-white">
                                        <?= number_format($menunggu_pembayaran); ?>
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- CHART RIWAYAT GAJI -->
                    <div
                        class="bg-white dark:bg-stone-900/50 p-6 rounded-lg border border-stone-200 dark:border-stone-800">
                        <h3 class="text-xl font-semibold text-stone-800 dark:text-white mb-4">Riwayat Pengeluaran Gaji</h3>
                        <div class="h-64 bg-stone-50 dark:bg-stone-800/50 rounded-md flex items-center justify-center">
                            <canvas id="gajiChart" class="w-full h-full"></canvas>
                        </div>
                    </div>
                </div>

                <!-- KOLOM KANAN -->
                <div class="lg:col-span-1 space-y-8">
                    <!-- KALENDER -->
                    <div
                        class="bg-white dark:bg-stone-900/50 p-6 rounded-lg border border-stone-200 dark:border-stone-800">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-xl font-semibold text-stone-800 dark:text-white">Kalender Laporan</h3>
                            <div class="flex items-center gap-2">
                                <a class="p-1 rounded-full hover:bg-stone-100 dark:hover:bg-stone-800"
                                    href="dashboard.php?bulan=<?= $prevMonth; ?>&tahun=<?= $prevYear; ?>">
                                    <span class="material-icons-outlined text-stone-500">chevron_left</span>
                                </a>
                                <span class="text-sm font-medium text-stone-700 dark:text-stone-300">
                                    <?= nama_bulan($bulan) . " " . $tahun; ?>
                                </span>
                                <a class="p-1 rounded-full hover:bg-stone-100 dark:hover:bg-stone-800"
                                    href="dashboard.php?bulan=<?= $nextMonth; ?>&tahun=<?= $nextYear; ?>">
                                    <span class="material-icons-outlined text-stone-500">chevron_right</span>
                                </a>
                            </div>
                        </div>

                        <div class="grid grid-cols-7 gap-y-2 text-center text-sm">
                            <!-- Header hari -->
                            <div class="font-medium text-stone-400 dark:text-stone-500">S</div>
                            <div class="font-medium text-stone-400 dark:text-stone-500">S</div>
                            <div class="font-medium text-stone-400 dark:text-stone-500">R</div>
                            <div class="font-medium text-stone-400 dark:text-stone-500">K</div>
                            <div class="font-medium text-stone-400 dark:text-stone-500">J</div>
                            <div class="font-medium text-stone-400 dark:text-stone-500">S</div>
                            <div class="font-medium text-stone-400 dark:text-stone-500">M</div>

                            <!-- Isi tanggal -->
                            <?php foreach ($calendarCells as $day): ?>
                                <?php if ($day === ''): ?>
                                    <div class="text-stone-400 dark:text-stone-600">&nbsp;</div>
                                <?php else: ?>
                                    <?php $isToday = ($day == $todayDay && $bulan == $todayMonth && $tahun == $todayYear); ?>
                                    <div class="relative">
                                        <span class="relative z-10 <?= $isToday ? 'text-white' : ''; ?>">
                                            <?= $day; ?>
                                        </span>
                                        <div class="absolute inset-0 flex items-center justify-center">
                                            <div class="h-7 w-7 rounded-full
                                                <?= $isToday ? 'bg-primary' : 'bg-primary/10 dark:bg-primary/20'; ?>">
                                            </div>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- PEMBAYARAN TERDEKAT -->
                    <div
                        class="bg-white dark:bg-stone-900/50 p-6 rounded-lg border border-stone-200 dark:border-stone-800">
                        <h3 class="text-xl font-semibold text-stone-800 dark:text-white mb-4">Pembayaran Terdekat</h3>
                        <div class="space-y-4">
                            <?php if (count($pembayaran_terdekat) === 0): ?>
                                <p class="text-sm text-stone-500 dark:text-stone-400">
                                    Tidak ada penggajian yang menunggu pembayaran.
                                </p>
                            <?php else: ?>
                                <?php foreach ($pembayaran_terdekat as $row): ?>
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center gap-3">
                                            <div
                                                class="w-10 h-10 rounded-full bg-amber-100 dark:bg-stone-700 flex items-center justify-center text-sm font-semibold text-stone-700">
                                                <?= strtoupper(substr($row['nama_karyawan'], 0, 1)); ?>
                                            </div>
                                            <div>
                                                <p class="font-medium text-stone-800 dark:text-white">
                                                    <?= htmlspecialchars($row['nama_karyawan']); ?>
                                                </p>
                                                <p class="text-sm text-stone-500 dark:text-stone-400">
                                                    Jatuh tempo: <?= nama_bulan((int)$row['bulan']) . " " . $row['tahun']; ?>
                                                </p>
                                            </div>
                                        </div>
                                        <span class="font-semibold text-primary">
                                            Rp <?= number_format($row['total_gaji'], 0, ',', '.'); ?>
                                        </span>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

        </main>
    </div>

    <!-- SCRIPT CHART -->
    <script>
        const labelsGaji = <?= json_encode($chart_labels); ?>;
        const dataGaji = <?= json_encode($chart_values); ?>;

        if (labelsGaji.length > 0) {
            const ctx = document.getElementById('gajiChart').getContext('2d');
            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: labelsGaji,
                    datasets: [{
                        label: 'Total Gaji Dibayar',
                        data: dataGaji,
                        tension: 0.3,
                        borderWidth: 2,
                        pointRadius: 3,
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false }
                    },
                    scales: {
                        y: {
                            ticks: {
                                callback: function (value) {
                                    return 'Rp ' + value.toLocaleString('id-ID');
                                }
                            }
                        }
                    }
                }
            });
        } else {
            const canvasParent = document.getElementById('gajiChart').parentElement;
            canvasParent.innerHTML =
                "<p class='text-stone-400 dark:text-stone-500 text-sm'>Belum ada data pengeluaran gaji yang dibayar.</p>";
        }
    </script>
</body>

</html>
