<?php
session_start();
require __DIR__ . '/../config/db.php';

// Cek login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$id_user = (int) $_SESSION['user_id'];

// Ambil data user
$sql = "SELECT * FROM user WHERE id_user = ? LIMIT 1";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $id_user);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$user = mysqli_fetch_assoc($result);

if (!$user) {
    die("User tidak ditemukan.");
}

$errors = [];
$success = "";

// Proses form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $nama_lengkap  = trim($_POST['nama_lengkap'] ?? '');
    $username      = trim($_POST['username'] ?? '');
    $jabatan       = trim($_POST['jabatan'] ?? '');
    $alamat        = trim($_POST['alamat'] ?? '');
    $tempat_lahir  = trim($_POST['tempat_lahir'] ?? '');
    $tanggal_lahir = trim($_POST['tanggal_lahir'] ?? '');
    $tanggal_lahir_db = $tanggal_lahir !== '' ? $tanggal_lahir : null;

    if ($nama_lengkap === '' || $username === '') {
        $errors[] = "Nama lengkap dan username wajib diisi.";
    }

    // Cek username tidak bentrok dengan user lain
    $sqlCek = "SELECT id_user FROM user WHERE username = ? AND id_user != ?";
    $stmtCek = mysqli_prepare($conn, $sqlCek);
    mysqli_stmt_bind_param($stmtCek, "si", $username, $id_user);
    mysqli_stmt_execute($stmtCek);
    $resCek = mysqli_stmt_get_result($stmtCek);
    if (mysqli_num_rows($resCek) > 0) {
        $errors[] = "Username sudah digunakan oleh pengguna lain.";
    }

    // Handle upload foto
    $foto_baru = $user['foto'] ?? null; // default: foto lama

    if (isset($_FILES['foto']) && $_FILES['foto']['error'] !== UPLOAD_ERR_NO_FILE) {
        $file     = $_FILES['foto'];
        $namaAsli = $file['name'];
        $tmpPath  = $file['tmp_name'];
        $size     = $file['size'];
        $errCode  = $file['error'];

        if ($errCode !== UPLOAD_ERR_OK) {
            $errors[] = "Gagal upload foto (error code: $errCode).";
        } else {
            $ext = strtolower(pathinfo($namaAsli, PATHINFO_EXTENSION));
            $allowed = ['jpg', 'jpeg', 'png'];

            if (!in_array($ext, $allowed)) {
                $errors[] = "Format foto harus JPG atau PNG.";
            } elseif ($size > 2 * 1024 * 1024) { // 2MB
                $errors[] = "Ukuran foto maksimal 2MB.";
            } else {
                // Pastikan folder upload ada
                $uploadDir = __DIR__ . '/uploads/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }

                // Hapus foto lama kalau ada
                if (!empty($user['foto'])) {
                    $oldPath = $uploadDir . $user['foto'];
                    if (file_exists($oldPath)) {
                        @unlink($oldPath);
                    }
                }

                // Buat nama file baru
                $namaBaruFile = 'user_' . $id_user . '_' . time() . '.' . $ext;

                if (move_uploaded_file($tmpPath, $uploadDir . $namaBaruFile)) {
                    $foto_baru = $namaBaruFile;
                } else {
                    $errors[] = "Gagal menyimpan file foto ke server.";
                }
            }
        }
    }

    // Jika tidak ada error, update DB
    if (empty($errors)) {
        $sqlUpdate = "UPDATE user
                      SET nama_lengkap = ?,
                          username = ?,
                          jabatan = ?,
                          alamat = ?,
                          tempat_lahir = ?,
                          tanggal_lahir = ?,
                          foto = ?
                      WHERE id_user = ?";
        $stmtUp = mysqli_prepare($conn, $sqlUpdate);
        mysqli_stmt_bind_param(
            $stmtUp,
            "sssssssi",
            $nama_lengkap,
            $username,
            $jabatan,
            $alamat,
            $tempat_lahir,
            $tanggal_lahir_db,
            $foto_baru,
            $id_user
        );

        if (mysqli_stmt_execute($stmtUp)) {
            // Update session juga
            $_SESSION['nama']  = $nama_lengkap;
            $_SESSION['role']  = $jabatan;
            $_SESSION['username'] = $username;

            $_SESSION['profil_success'] = "Profil berhasil diperbarui.";
            header("Location: profil.php");
            exit;
        } else {
            $errors[] = "Gagal menyimpan perubahan ke database.";
        }
    }

    // Jika ada error, biar form terisi ulang dengan data terbaru dari POST
    $user['nama_lengkap']  = $nama_lengkap;
    $user['username']      = $username;
    $user['jabatan']       = $jabatan;
    $user['alamat']        = $alamat;
    $user['tempat_lahir']  = $tempat_lahir;
    $user['tanggal_lahir'] = $tanggal_lahir_db;
    $user['foto']          = $foto_baru;
}

// Data untuk form
$nama_lengkap  = $user['nama_lengkap'] ?? '';
$username      = $user['username'] ?? '';
$jabatan       = $user['jabatan'] ?? '';
$alamat        = $user['alamat'] ?? '';
$tempat_lahir  = $user['tempat_lahir'] ?? '';
$tanggal_lahir = $user['tanggal_lahir'] ?? null;
$foto          = $user['foto'] ?? null;

$initial = strtoupper(substr($nama_lengkap !== '' ? $nama_lengkap : $username, 0, 1));
?>
<!DOCTYPE html>
<html class="light" lang="en">

<head>
    <meta charset="utf-8" />
    <meta content="width=device-width, initial-scale=1.0" name="viewport" />
    <title>Edit Profil</title>

    <!-- Tailwind -->
    <script src="https://cdn.tailwindcss.com?plugins=forms,typography"></script>
    <script src="tailwind-config.js"></script>

    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons+Outlined" rel="stylesheet" />

</head>

<body class="bg-background-light dark:bg-background-dark font-display text-gray-800 dark:text-gray-200 min-h-screen">

    <div class="container mx-auto p-4 sm:p-6 lg:p-8 max-w-3xl">

        <a href="profil.php"
           class="inline-flex items-center text-primary mb-6 text-sm font-medium hover:underline">
            <span class="material-icons-outlined mr-1 text-base">arrow_back</span>
            Kembali ke Profil
        </a>

        <h1 class="text-3xl font-bold mb-6 text-gray-900 dark:text-white">Edit Profil</h1>

        <?php if (!empty($errors)): ?>
            <div class="mb-4 rounded bg-red-100 text-red-800 px-4 py-3 text-sm">
                <ul class="list-disc list-inside">
                    <?php foreach ($errors as $e): ?>
                        <li><?= htmlspecialchars($e); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow-md border border-border-light dark:border-border-dark">

            <form method="post" enctype="multipart/form-data" class="space-y-6">

                <!-- Foto / Avatar -->
                <div class="flex items-center gap-6">
                    <?php if ($foto): ?>
                        <img src="uploads/<?= htmlspecialchars($foto); ?>"
                             alt="Foto Profil"
                             class="w-20 h-20 rounded-full object-cover shadow" />
                    <?php else: ?>
                        <div class="w-20 h-20 rounded-full bg-primary text-white flex items-center justify-center text-3xl font-bold">
                            <?= $initial; ?>
                        </div>
                    <?php endif; ?>

                    <div class="flex-1">
                        <label class="block text-sm font-medium mb-1">Foto Profil</label>
                        <input type="file" name="foto"
                               class="w-full text-sm text-gray-700 dark:text-gray-200 bg-background-light dark:bg-gray-700 border border-border-light dark:border-border-dark rounded p-1.5" />
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                            Format: JPG/PNG, maks 2MB. Kosongkan jika tidak ingin mengubah.
                        </p>
                    </div>
                </div>

                <hr class="border-border-light dark:border-border-dark" />

                <!-- Nama lengkap -->
                <div>
                    <label class="block text-sm font-medium mb-1">Nama Lengkap</label>
                    <input type="text" name="nama_lengkap"
                           class="w-full rounded border-border-light dark:border-border-dark bg-background-light dark:bg-gray-700 p-2"
                           value="<?= htmlspecialchars($nama_lengkap); ?>" required />
                </div>

                <!-- Username -->
                <div>
                    <label class="block text-sm font-medium mb-1">Username</label>
                    <input type="text" name="username"
                           class="w-full rounded border-border-light dark:border-border-dark bg-background-light dark:bg-gray-700 p-2"
                           value="<?= htmlspecialchars($username); ?>" required />
                </div>

                <!-- Jabatan -->
                <div>
                    <label class="block text-sm font-medium mb-1">Jabatan</label>
                    <input type="text" name="jabatan"
                           class="w-full rounded border-border-light dark:border-border-dark bg-background-light dark:bg-gray-700 p-2"
                           value="<?= htmlspecialchars($jabatan); ?>" />
                </div>

                <!-- Tempat lahir & tanggal lahir -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium mb-1">Tempat Lahir</label>
                        <input type="text" name="tempat_lahir"
                               class="w-full rounded border-border-light dark:border-border-dark bg-background-light dark:bg-gray-700 p-2"
                               value="<?= htmlspecialchars($tempat_lahir); ?>" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Tanggal Lahir</label>
                        <input type="date" name="tanggal_lahir"
                               class="w-full rounded border-border-light dark:border-border-dark bg-background-light dark:bg-gray-700 p-2"
                               value="<?= $tanggal_lahir ? htmlspecialchars($tanggal_lahir) : ''; ?>" />
                    </div>
                </div>

                <!-- Alamat -->
                <div>
                    <label class="block text-sm font-medium mb-1">Alamat</label>
                    <textarea name="alamat" rows="3"
                              class="w-full rounded border-border-light dark:border-border-dark bg-background-light dark:bg-gray-700 p-2"><?= htmlspecialchars($alamat); ?></textarea>
                </div>

                <div class="pt-2 text-right">
                    <button type="submit"
                            class="inline-flex items-center bg-primary text-white font-semibold py-2 px-6 rounded hover:bg-opacity-90 transition">
                        <span class="material-icons-outlined text-sm mr-1">save</span>
                        Simpan Perubahan
                    </button>
                </div>

            </form>

        </div>

    </div>

</body>

</html>
