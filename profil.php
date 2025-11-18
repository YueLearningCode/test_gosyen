<?php
session_start();
require __DIR__ . '/../config/db.php';

// Cek login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$id_user = (int) $_SESSION['user_id'];

// Ambil data user dari database
$sql = "SELECT * FROM user WHERE id_user = ? LIMIT 1";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $id_user);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$user = mysqli_fetch_assoc($result);

if (!$user) {
    die("User tidak ditemukan.");
}

// Data
$nama_lengkap  = $user['nama_lengkap'] ?? '';
$username      = $user['username'] ?? '';
$jabatan       = $user['jabatan'] ?? '';
$alamat        = $user['alamat'] ?? '';
$tempat_lahir  = $user['tempat_lahir'] ?? '';
$tanggal_lahir = $user['tanggal_lahir'] ?? null;
$foto          = $user['foto'] ?? null;

// Format tanggal lahir
$tanggal_lahir_display = $tanggal_lahir ? date('d-m-Y', strtotime($tanggal_lahir)) : '-';

// Avatar initial
$initial = strtoupper(substr($nama_lengkap !== '' ? $nama_lengkap : $username, 0, 1));
?>
<!DOCTYPE html>
<html class="light" lang="en">

<head>
    <meta charset="utf-8" />
    <meta content="width=device-width, initial-scale=1.0" name="viewport" />
    <title>Profil Saya</title>

    <!-- Tailwind -->
    <script src="https://cdn.tailwindcss.com?plugins=forms,typography"></script>
    <script src="tailwind-config.js"></script>

    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet" />

</head>

<body class="bg-background-light dark:bg-background-dark font-display text-gray-800 dark:text-gray-200 min-h-screen">

    <div class="container mx-auto p-4 sm:p-6 lg:p-8 max-w-3xl">

        <a href="dashboard.php"
           class="inline-flex items-center text-primary mb-6 text-sm font-medium hover:underline">
            <span class="material-icons-outlined mr-1 text-base"></span>
            Kembali ke Dashboard
        </a>

        <h1 class="text-3xl font-bold mb-6 text-gray-900 dark:text-white">Profil Saya</h1>

        <?php if (!empty($_SESSION['profil_success'])): ?>
            <div class="mb-4 rounded bg-green-100 text-green-800 px-4 py-3 text-sm">
                <?= htmlspecialchars($_SESSION['profil_success']); ?>
            </div>
            <?php unset($_SESSION['profil_success']); ?>
        <?php endif; ?>

        <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow-md border border-border-light dark:border-border-dark">

            <!-- Bagian atas: foto / avatar + nama -->
            <div class="flex items-center gap-6 mb-6">
                <?php if ($foto): ?>
                    <img src="uploads/<?= htmlspecialchars($foto); ?>"
                         alt="Foto Profil"
                         class="w-20 h-20 rounded-full object-cover shadow" />
                <?php else: ?>
                    <div class="w-20 h-20 rounded-full bg-primary text-white flex items-center justify-center text-3xl font-bold">
                        <?= $initial; ?>
                    </div>
                <?php endif; ?>

                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Nama Lengkap</p>
                    <p class="text-xl font-semibold text-gray-900 dark:text-white"><?= htmlspecialchars($nama_lengkap); ?></p>

                    <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Jabatan</p>
                    <p class="text-base font-medium"><?= htmlspecialchars($jabatan); ?></p>
                </div>
            </div>

            <hr class="my-4 border-border-light dark:border-border-dark" />

            <!-- Detail lainnya -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Username</p>
                    <p class="font-medium"><?= htmlspecialchars($username); ?></p>
                </div>

                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Tempat & Tanggal Lahir</p>
                    <p class="font-medium">
                        <?= $tempat_lahir ? htmlspecialchars($tempat_lahir) : '-'; ?>
                        <?= $tanggal_lahir ? ', ' . $tanggal_lahir_display : ''; ?>
                    </p>
                </div>

                <div class="md:col-span-2">
                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-1">Alamat</p>
                    <p class="font-medium whitespace-pre-line">
                        <?= $alamat ? nl2br(htmlspecialchars($alamat)) : '-'; ?>
                    </p>
                </div>
            </div>

            <div class="mt-8 text-right">
                <a href="edit_profil.php"
                   class="inline-flex items-center bg-primary text-white px-5 py-2 rounded hover:bg-opacity-90 transition text-sm font-medium">
                    <span class="material-icons-outlined text-sm mr-1"></span>
                    Edit Profil
                </a>
            </div>
        </div>

    </div>

</body>

</html>
