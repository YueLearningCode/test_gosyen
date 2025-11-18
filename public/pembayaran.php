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

$success_msg = "";
$error_msg   = "";

// ======================= HELPER BULAN =========================
function nama_bulan($num)
{
    $arr = [
        1 => 'Januari', 'Februari', 'Maret', 'April',
        'Mei', 'Juni', 'Juli', 'Agustus',
        'September', 'Oktober', 'November', 'Desember'
    ];
    return $arr[$num] ?? $num;
}

// ======================= HANDLE POST: TANDAI LUNAS =========================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_penggajian = isset($_POST['id_penggajian']) ? (int) $_POST['id_penggajian'] : 0;
    $metode        = trim($_POST['metode'] ?? '');
    $keterangan    = trim($_POST['keterangan'] ?? '');

    if ($id_penggajian <= 0 || $metode === '' || $metode === '--Pilih--') {
        $error_msg = "Metode pembayaran wajib dipilih.";
    } else {
        $sql  = "UPDATE penggajian
                 SET status_pembayaran = 'lunas',
                     tanggal_pembayaran = CURDATE(),
                     metode_pembayaran = ?,
                     keterangan_pembayaran = ?
                 WHERE id_penggajian = ?";

        $stmt = mysqli_prepare($conn, $sql);
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "ssi", $metode, $keterangan, $id_penggajian);
            if (mysqli_stmt_execute($stmt)) {
                $success_msg = "Penggajian berhasil ditandai sebagai lunas.";
            } else {
                $error_msg = "Gagal mengupdate pembayaran: " . mysqli_error($conn);
            }
        } else {
            $error_msg = "Gagal mempersiapkan query: " . mysqli_error($conn);
        }
    }
}

// ======================= FETCH: GAJI BELUM DIBAYAR =========================
$belum_dibayar = [];
$sql = "SELECT p.*, k.nama_karyawan, k.posisi
        FROM penggajian p
        JOIN karyawan k ON k.id_karyawan = p.id_karyawan
        WHERE p.status_pembayaran = 'belum'
        ORDER BY p.tahun DESC, p.bulan DESC, k.nama_karyawan ASC";
$res = mysqli_query($conn, $sql);
if ($res) {
    while ($row = mysqli_fetch_assoc($res)) {
        $belum_dibayar[] = $row;
    }
}

// ======================= FETCH: RIWAYAT LUNAS =========================
$riwayat_lunas = [];
$sql = "SELECT p.*, k.nama_karyawan, k.posisi
        FROM penggajian p
        JOIN karyawan k ON k.id_karyawan = p.id_karyawan
        WHERE p.status_pembayaran = 'lunas'
        ORDER BY p.tanggal_pembayaran DESC, p.tahun DESC, p.bulan DESC";
$res = mysqli_query($conn, $sql);
if ($res) {
    while ($row = mysqli_fetch_assoc($res)) {
        $riwayat_lunas[] = $row;
    }
}

?>
<!DOCTYPE html>
<html class="light" lang="en">
<head>
  <meta charset="utf-8" />
  <meta content="width=device-width, initial-scale=1.0" name="viewport" />
  <title>Pembayaran Penggajian</title>

  <!-- TailwindCSS CDN -->
  <script src="https://cdn.tailwindcss.com?plugins=forms,typography"></script>

  <!-- Tailwind Config (inline, ganti tailwind-config.js) -->
  <script>
    tailwind.config = {
      darkMode: "class",
      theme: {
        extend: {
          colors: {
            primary: "#A16207",
            "background-light": "#FEFCE8",
            "background-dark": "#1C1917",
            "surface-light": "#FFFFFF",
            "surface-dark": "#1F2933",
            "border-light": "#E5E7EB",
            "border-dark": "#4B5563",
            "text-light": "#111827",
            "text-dark": "#E5E7EB",
          },
          fontFamily: {
            display: ["Poppins", "sans-serif"],
          },
        },
      },
    };
  </script>

  <!-- Fonts -->
  <link
    href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap"
    rel="stylesheet"
  />
  <link
    href="https://fonts.googleapis.com/icon?family=Material+Icons+Outlined"
    rel="stylesheet"
  />
</head>
<body
  class="font-display bg-background-light dark:bg-background-dark text-gray-800 dark:text-gray-200 antialiased"
>
  <div class="min-h-screen p-4 sm:p-6 lg:p-8">
    <div class="max-w-7xl mx-auto">

      <header class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
          Pembayaran Penggajian
        </h1>
        <a
          href="dashboard.php"
          class="inline-flex items-center text-primary hover:underline mt-2"
        >
          <span class="material-icons-outlined text-xl mr-1">arrow_back</span>
          Kembali ke Dashboard
        </a>
      </header>

      <?php if ($success_msg): ?>
        <div class="mb-4 rounded bg-green-100 text-green-800 px-4 py-3 text-sm">
          <?php echo htmlspecialchars($success_msg); ?>
        </div>
      <?php endif; ?>

      <?php if ($error_msg): ?>
        <div class="mb-4 rounded bg-red-100 text-red-800 px-4 py-3 text-sm">
          <?php echo htmlspecialchars($error_msg); ?>
        </div>
      <?php endif; ?>

      <!-- Daftar Gaji Belum Dibayar -->
      <div
        class="bg-background-light dark:bg-gray-800 p-6 rounded-lg shadow-md mb-8"
      >
        <h2 class="text-xl font-semibold mb-4 text-gray-900 dark:text-white">
          Daftar Gaji yang Belum Dibayar
        </h2>
        <div class="overflow-x-auto">
          <table class="w-full text-sm text-left">
            <thead
              class="bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 uppercase"
            >
              <tr>
                <th class="px-6 py-3 rounded-l-lg" scope="col">Periode</th>
                <th class="px-6 py-3" scope="col">Nama Karyawan</th>
                <th class="px-6 py-3" scope="col">Posisi</th>
                <th class="px-6 py-3" scope="col">Total Service</th>
                <th class="px-6 py-3" scope="col">Total Gaji</th>
                <th class="px-6 py-3 rounded-r-lg" scope="col">
                  Aksi Pembayaran
                </th>
              </tr>
            </thead>
            <tbody>
              <?php if (count($belum_dibayar) === 0): ?>
                <tr>
                  <td colspan="6" class="px-6 py-4 text-center text-gray-500 dark:text-gray-400">
                    Tidak ada gaji yang menunggu pembayaran.
                  </td>
                </tr>
              <?php else: ?>
                <?php foreach ($belum_dibayar as $row): ?>
                  <tr
                    class="bg-background-light dark:bg-gray-800 border-b dark:border-gray-700 align-top"
                  >
                    <td class="px-6 py-4">
                      <?php echo nama_bulan((int)$row['bulan']) . ' ' . $row['tahun']; ?>
                    </td>
                    <td class="px-6 py-4 font-medium text-gray-900 dark:text-white">
                      <?php echo htmlspecialchars($row['nama_karyawan']); ?>
                    </td>
                    <td class="px-6 py-4">
                      <?php echo htmlspecialchars($row['posisi']); ?>
                    </td>
                    <td class="px-6 py-4">
                      <?php echo (int)$row['total_service']; ?>
                    </td>
                    <td class="px-6 py-4 font-semibold text-gray-900 dark:text-white">
                      Rp <?php echo number_format($row['total_gaji'], 0, ',', '.'); ?>
                    </td>
                    <td class="px-6 py-4">
                      <form class="space-y-3 w-48" method="post" action="pembayaran.php">
                        <input type="hidden" name="id_penggajian" value="<?php echo (int)$row['id_penggajian']; ?>">

                        <div>
                          <label class="sr-only" for="metode_<?php echo $row['id_penggajian']; ?>">Metode</label>
                          <select
                            id="metode_<?php echo $row['id_penggajian']; ?>"
                            name="metode"
                            class="w-full bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary focus:border-primary block p-2.5 dark:bg-gray-600 dark:border-gray-500 dark:placeholder-gray-400 dark:text-white"
                          >
                            <option value="">--Pilih--</option>
                            <option value="cash">Cash</option>
                            <option value="qris">Qris</option>
                            <option value="transfer">Transfer</option>
                          </select>
                        </div>

                        <div>
                          <label class="sr-only" for="keterangan_<?php echo $row['id_penggajian']; ?>">Keterangan</label>
                          <input
                            id="keterangan_<?php echo $row['id_penggajian']; ?>"
                            name="keterangan"
                            type="text"
                            placeholder="Keterangan..."
                            class="w-full bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary focus:border-primary block p-2.5 dark:bg-gray-600 dark:border-gray-500 dark:placeholder-gray-400 dark:text-white"
                          />
                        </div>

                        <button
                          type="submit"
                          class="w-full text-white bg-primary hover:bg-opacity-90 focus:ring-4 focus:outline-none focus:ring-amber-800 font-medium rounded-lg text-sm px-5 py-2.5 text-center"
                        >
                          Tandai Lunas
                        </button>
                      </form>
                    </td>
                  </tr>
                <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Riwayat Pembayaran -->
      <div
        class="bg-background-light dark:bg-gray-800 p-6 rounded-lg shadow-md"
      >
        <h2 class="text-xl font-semibold mb-4 text-gray-900 dark:text-white">
          Riwayat Pembayaran Gaji (Lunas)
        </h2>
        <div class="overflow-x-auto">
          <table class="w-full text-sm text-left">
            <thead
              class="bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 uppercase"
            >
              <tr>
                <th class="px-6 py-3 rounded-l-lg" scope="col">Periode</th>
                <th class="px-6 py-3" scope="col">Nama Karyawan</th>
                <th class="px-6 py-3" scope="col">Posisi</th>
                <th class="px-6 py-3" scope="col">Total Gaji</th>
                <th class="px-6 py-3" scope="col">Status</th>
                <th class="px-6 py-3" scope="col">Tanggal Bayar</th>
                <th class="px-6 py-3" scope="col">Metode</th>
                <th class="px-6 py-3 rounded-r-lg" scope="col">Keterangan</th>
              </tr>
            </thead>
            <tbody>
              <?php if (count($riwayat_lunas) === 0): ?>
                <tr>
                  <td colspan="8" class="px-6 py-4 text-center text-gray-500 dark:text-gray-400">
                    Belum ada riwayat pembayaran gaji.
                  </td>
                </tr>
              <?php else: ?>
                <?php foreach ($riwayat_lunas as $row): ?>
                  <tr
                    class="bg-background-light dark:bg-gray-800 border-b dark:border-gray-700"
                  >
                    <td class="px-6 py-4">
                      <?php echo nama_bulan((int)$row['bulan']) . ' ' . $row['tahun']; ?>
                    </td>
                    <td class="px-6 py-4 font-medium text-gray-900 dark:text-white">
                      <?php echo htmlspecialchars($row['nama_karyawan']); ?>
                    </td>
                    <td class="px-6 py-4">
                      <?php echo htmlspecialchars($row['posisi']); ?>
                    </td>
                    <td class="px-6 py-4 font-semibold text-gray-900 dark:text-white">
                      Rp <?php echo number_format($row['total_gaji'], 0, ',', '.'); ?>
                    </td>
                    <td class="px-6 py-4">
                      <span
                        class="bg-green-100 text-green-800 text-xs font-medium mr-2 px-2.5 py-0.5 rounded-full dark:bg-green-900 dark:text-green-300"
                      >Lunas</span>
                    </td>
                    <td class="px-6 py-4">
                      <?php echo $row['tanggal_pembayaran'] ?: '-'; ?>
                    </td>
                    <td class="px-6 py-4">
                      <?php echo htmlspecialchars($row['metode_pembayaran'] ?: '-'); ?>
                    </td>
                    <td class="px-6 py-4">
                      <?php echo htmlspecialchars($row['keterangan_pembayaran'] ?: '-'); ?>
                    </td>
                  </tr>
                <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>

    </div>
  </div>
</body>
</html>
