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

// ========== HANDLE POST: TAMBAH / UPDATE ==========
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_laporan     = isset($_POST['id_laporan']) ? (int) $_POST['id_laporan'] : 0;
    $id_karyawan    = isset($_POST['id_karyawan']) ? (int) $_POST['id_karyawan'] : 0;
    $tanggal        = trim($_POST['tanggal'] ?? '');
    $jumlah_service = isset($_POST['jumlah_service']) ? (int) $_POST['jumlah_service'] : 0;
    $catatan        = trim($_POST['catatan'] ?? '');

    if ($id_karyawan <= 0 || $tanggal === '' || $jumlah_service <= 0) {
        $error_msg = "Karyawan, tanggal, dan jumlah service wajib diisi dan jumlah harus lebih dari 0.";
    } else {
        if ($id_laporan > 0) {
            // UPDATE
            $sql = "UPDATE laporan_service 
                    SET id_karyawan = ?, tanggal = ?, jumlah_service = ?, catatan = ?
                    WHERE id_laporan = ?";
            $stmt = mysqli_prepare($conn, $sql);
            if ($stmt) {
                mysqli_stmt_bind_param($stmt, "isisi",
                    $id_karyawan,
                    $tanggal,
                    $jumlah_service,
                    $catatan,
                    $id_laporan
                );
                if (mysqli_stmt_execute($stmt)) {
                    header("Location: servis.php?success=update");
                    exit;
                } else {
                    $error_msg = "Gagal mengupdate laporan: " . mysqli_error($conn);
                }
            } else {
                $error_msg = "Gagal mempersiapkan query update: " . mysqli_error($conn);
            }
        } else {
            // INSERT
            $sql = "INSERT INTO laporan_service (id_karyawan, tanggal, jumlah_service, catatan)
                    VALUES (?, ?, ?, ?)";
            $stmt = mysqli_prepare($conn, $sql);
            if ($stmt) {
                mysqli_stmt_bind_param($stmt, "isis",
                    $id_karyawan,
                    $tanggal,
                    $jumlah_service,
                    $catatan
                );
                if (mysqli_stmt_execute($stmt)) {
                    header("Location: servis.php?success=tambah");
                    exit;
                } else {
                    $error_msg = "Gagal menyimpan laporan: " . mysqli_error($conn);
                }
            } else {
                $error_msg = "Gagal mempersiapkan query insert: " . mysqli_error($conn);
            }
        }
    }
}

// ========== HANDLE DELETE ==========
if (isset($_GET['hapus'])) {
    $id_hapus = (int) $_GET['hapus'];
    if ($id_hapus > 0) {
        $sql  = "DELETE FROM laporan_service WHERE id_laporan = ?";
        $stmt = mysqli_prepare($conn, $sql);
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "i", $id_hapus);
            if (mysqli_stmt_execute($stmt)) {
                header("Location: servis.php?success=hapus");
                exit;
            } else {
                $error_msg = "Gagal menghapus laporan: " . mysqli_error($conn);
            }
        } else {
            $error_msg = "Gagal mempersiapkan query hapus: " . mysqli_error($conn);
        }
    }
}

// ========== HANDLE SUCCESS MESSAGE DARI REDIRECT ==========
if (isset($_GET['success'])) {
    if ($_GET['success'] === 'tambah') {
        $success_msg = "Laporan service berhasil ditambahkan.";
    } elseif ($_GET['success'] === 'update') {
        $success_msg = "Laporan service berhasil diperbarui.";
    } elseif ($_GET['success'] === 'hapus') {
        $success_msg = "Laporan service berhasil dihapus.";
    }
}

// ========== HANDLE EDIT MODE ==========
$edit_mode = false;
$edit_data = null;

if (isset($_GET['edit'])) {
    $id_edit = (int) $_GET['edit'];
    if ($id_edit > 0) {
        $sql  = "SELECT * FROM laporan_service WHERE id_laporan = ?";
        $stmt = mysqli_prepare($conn, $sql);
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "i", $id_edit);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            $edit_data = mysqli_fetch_assoc($result);
            if ($edit_data) {
                $edit_mode = true;
            }
        }
    }
}

// ========== AMBIL DATA KARYAWAN UNTUK DROPDOWN ==========
$list_karyawan = [];
$res = mysqli_query($conn, "SELECT id_karyawan, nama_karyawan, posisi FROM karyawan ORDER BY nama_karyawan ASC");
if ($res) {
    while ($row = mysqli_fetch_assoc($res)) {
        $list_karyawan[] = $row;
    }
}

// ========== AMBIL DATA LAPORAN UNTUK TABEL ==========
$laporan = [];
$sql = "SELECT ls.*, k.nama_karyawan, k.posisi
        FROM laporan_service ls
        JOIN karyawan k ON k.id_karyawan = ls.id_karyawan
        ORDER BY ls.tanggal DESC, ls.id_laporan DESC";
$res = mysqli_query($conn, $sql);
if ($res) {
    while ($row = mysqli_fetch_assoc($res)) {
        $laporan[] = $row;
    }
}

// ========== NILAI DEFAULT FORM ==========
$form_id_laporan     = 0;
$form_id_karyawan    = '';
$form_tanggal        = date('Y-m-d');
$form_jumlah_service = '';
$form_catatan        = '';

// Jika ada error pada POST, pertahankan input user
if ($error_msg && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $form_id_laporan     = (int) ($_POST['id_laporan'] ?? 0);
    $form_id_karyawan    = (int) ($_POST['id_karyawan'] ?? 0);
    $form_tanggal        = $_POST['tanggal'] ?? date('Y-m-d');
    $form_jumlah_service = $_POST['jumlah_service'] ?? '';
    $form_catatan        = $_POST['catatan'] ?? '';
} elseif ($edit_mode && $edit_data) { // Jika sedang edit, isi dari data edit
    $form_id_laporan     = (int) $edit_data['id_laporan'];
    $form_id_karyawan    = (int) $edit_data['id_karyawan'];
    $form_tanggal        = $edit_data['tanggal'];
    $form_jumlah_service = $edit_data['jumlah_service'];
    $form_catatan        = $edit_data['catatan'];
}

?>
<!DOCTYPE html>
<html class="light" lang="en">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Laporan Service Harian</title>

    <!-- TailwindCSS CDN -->
    <script src="https://cdn.tailwindcss.com?plugins=forms,typography"></script>

    <!-- Inline Tailwind Config (menggantikan tailwind-config.js eksternal) -->
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

    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons+Outlined" rel="stylesheet" />
</head>

<body class="bg-background-light dark:bg-background-dark font-display text-gray-800 dark:text-gray-200">

    <div class="container mx-auto p-4 sm:p-6 lg:p-8">
        <div class="max-w-7xl mx-auto">

            <header class="mb-8">
                <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Laporan Service Harian</h1>
                <a href="dashboard.php" class="mt-2 inline-flex items-center text-primary hover:underline">
                    <span class="material-icons-outlined mr-1">arrow_back</span>
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

            <main class="space-y-12">

                <!-- Form Tambah / Edit Laporan -->
                <section class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow-sm">
                    <div class="flex items-center justify-between mb-6">
                        <h2 class="text-xl font-semibold text-gray-900 dark:text-white">
                            <?php echo $form_id_laporan > 0 ? "Edit Laporan Service" : "Tambah Laporan Service"; ?>
                        </h2>
                        <?php if ($form_id_laporan > 0): ?>
                            <a href="servis.php" class="text-sm text-primary hover:underline">
                                Batal Edit
                            </a>
                        <?php endif; ?>
                    </div>

                    <form class="grid grid-cols-1 md:grid-cols-2 gap-6" method="post" action="servis.php">
                        <input type="hidden" name="id_laporan" value="<?php echo (int)$form_id_laporan; ?>">

                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300"
                                for="karyawan">Karyawan</label>
                            <select id="karyawan" name="id_karyawan"
                                class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm 
                                       focus:border-primary focus:ring-primary bg-background-light dark:bg-gray-700 dark:text-white sm:text-sm">
                                <option value="">-- Pilih Karyawan --</option>
                                <?php foreach ($list_karyawan as $k): ?>
                                    <option value="<?php echo (int)$k['id_karyawan']; ?>"
                                        <?php echo ($form_id_karyawan == $k['id_karyawan']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($k['nama_karyawan']); ?> 
                                        (<?php echo htmlspecialchars($k['posisi']); ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300"
                                for="tanggal-servis">Tanggal Servis</label>
                            <input type="date" id="tanggal-servis" name="tanggal"
                                value="<?php echo htmlspecialchars($form_tanggal); ?>"
                                class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm 
                                       focus:border-primary focus:ring-primary bg-background-light dark:bg-gray-700 dark:text-white sm:text-sm" />
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300"
                                for="jumlah-service">Jumlah Service</label>
                            <input type="number" id="jumlah-service" name="jumlah_service"
                                value="<?php echo htmlspecialchars($form_jumlah_service); ?>"
                                class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm 
                                       focus:border-primary focus:ring-primary bg-background-light dark:bg-gray-700 dark:text-white sm:text-sm" />
                        </div>

                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300"
                                for="catatan">Catatan</label>
                            <textarea rows="3" id="catatan" name="catatan"
                                class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm 
                                       focus:border-primary focus:ring-primary bg-background-light dark:bg-gray-700 dark:text-white sm:text-sm"><?php echo htmlspecialchars($form_catatan); ?></textarea>
                        </div>

                        <div class="md:col-span-2 flex justify-start">
                            <button type="submit"
                                class="inline-flex justify-center py-2 px-6 border border-transparent shadow-sm text-sm font-medium 
                                       rounded-md text-white bg-primary hover:bg-opacity-90 focus:outline-none focus:ring-2 
                                       focus:ring-offset-2 focus:ring-primary">
                                <?php echo $form_id_laporan > 0 ? "Update" : "Simpan"; ?>
                            </button>
                        </div>
                    </form>
                </section>

                <!-- Tabel Laporan -->
                <section>
                    <h2 class="text-xl font-semibold mb-4 text-gray-900 dark:text-white">Daftar Laporan Service</h2>
                    <div class="overflow-x-auto bg-white dark:bg-gray-800 rounded-lg shadow-sm">

                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-700">
                                <tr>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                        Tanggal</th>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                        Nama Karyawan</th>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                        Posisi</th>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                        Jumlah Service</th>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                        Catatan</th>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                        Aksi</th>
                                </tr>
                            </thead>

                            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                <?php if (count($laporan) === 0): ?>
                                    <tr>
                                        <td colspan="6" class="px-6 py-4 text-center text-sm text-gray-500 dark:text-gray-400">
                                            Belum ada laporan service.
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($laporan as $row): ?>
                                        <tr>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                                <?php echo htmlspecialchars($row['tanggal']); ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                                <?php echo htmlspecialchars($row['nama_karyawan']); ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                                <?php echo htmlspecialchars($row['posisi']); ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                                <?php echo (int)$row['jumlah_service']; ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                                <?php echo htmlspecialchars($row['catatan']); ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                                <a href="servis.php?edit=<?php echo (int)$row['id_laporan']; ?>"
                                                    class="text-primary hover:underline">Edit</a>
                                                <span class="mx-1 text-gray-300 dark:text-gray-600">|</span>
                                                <a href="servis.php?hapus=<?php echo (int)$row['id_laporan']; ?>"
                                                    class="text-red-600 dark:text-red-400 hover:underline"
                                                    onclick="return confirm('Yakin ingin menghapus laporan ini?');">
                                                    Hapus
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>

                    </div>
                </section>

            </main>

        </div>
    </div>

</body>

</html>
