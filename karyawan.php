<?php
session_start();
require __DIR__ . "/../config/db.php";

// Redirect jika belum login
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit;
}

// -------------------------
// HANDLE TAMBAH DATA
// -------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['tambah'])) {
    $nama  = trim($_POST['nama_karyawan']);
    $posisi = trim($_POST['posisi']);
    $alamat = trim($_POST['alamat']);
    $no_hp = trim($_POST['no_hp']);
    $gaji = trim($_POST['gaji_pokok']);

    $sql = "INSERT INTO karyawan (nama_karyawan, posisi, alamat, no_hp, gaji_pokok)
            VALUES (?, ?, ?, ?, ?)";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "sssss", $nama, $posisi, $alamat, $no_hp, $gaji);
    mysqli_stmt_execute($stmt);

    header("Location: karyawan.php?success=tambah");
    exit;
}

// -------------------------
// HANDLE HAPUS DATA
// -------------------------
if (isset($_GET['hapus'])) {
    $id = (int)$_GET['hapus'];

    $sql = "DELETE FROM karyawan WHERE id_karyawan = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);

    header("Location: karyawan.php?success=hapus");
    exit;
}

// -------------------------
// HANDLE EDIT DATA
// -------------------------
$edit_mode = false;
$edit_data = null;

if (isset($_GET['edit'])) {
    $edit_mode = true;
    $id_edit = (int)$_GET['edit'];

    $result = mysqli_query($conn, "SELECT * FROM karyawan WHERE id_karyawan = $id_edit LIMIT 1");
    $edit_data = mysqli_fetch_assoc($result);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update'])) {

    $id = (int)$_POST['id_karyawan'];
    $nama  = trim($_POST['nama_karyawan']);
    $posisi = trim($_POST['posisi']);
    $alamat = trim($_POST['alamat']);
    $no_hp = trim($_POST['no_hp']);
    $gaji = trim($_POST['gaji_pokok']);

    $sql = "UPDATE karyawan 
            SET nama_karyawan=?, posisi=?, alamat=?, no_hp=?, gaji_pokok=? 
            WHERE id_karyawan=?";
    $stmt = mysqli_prepare($conn, $sql);

    mysqli_stmt_bind_param($stmt, "sssssi", $nama, $posisi, $alamat, $no_hp, $gaji, $id);
    mysqli_stmt_execute($stmt);

    header("Location: karyawan.php?success=edit");
    exit;
}

// -------------------------
// AMBIL DATA KARYAWAN
// -------------------------
$result = mysqli_query($conn, "SELECT * FROM karyawan ORDER BY id_karyawan ASC");
$karyawan = mysqli_fetch_all($result, MYSQLI_ASSOC);

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Kelola Data Karyawan</title>

  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">

  <script src="https://cdn.tailwindcss.com?plugins=forms,typography"></script>

  <script>
    tailwind.config = {
      theme: {
        extend: {
          colors: {
            primary: "#A0522D",
          }
        }
      }
    }
  </script>

  <link rel="stylesheet" href="styles.css">
</head>

<body class="bg-background-light dark:bg-background-dark font-display">

<div class="container mx-auto p-6">

    <a href="dashboard.php" class="inline-flex items-center text-primary mb-6 text-sm font-medium hover:underline">
        <span class="material-icons mr-1">arrow_back</span>
        Kembali ke Dashboard
    </a>

    <h1 class="text-3xl font-bold mb-8">Kelola Data Karyawan</h1>


    <!-- ====================== FORM TAMBAH / EDIT ======================== -->
    <div class="bg-white p-6 rounded-lg shadow mb-10">

        <h2 class="text-xl font-semibold mb-6">
            <?= $edit_mode ? "Edit Karyawan" : "Tambah Karyawan"; ?>
        </h2>

        <form method="POST">
            <?php if ($edit_mode): ?>
                <input type="hidden" name="id_karyawan" value="<?= $edit_data['id_karyawan']; ?>">
            <?php endif; ?>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block mb-1 font-medium">Nama Karyawan</label>
                    <input class="w-full border rounded p-2"
                        name="nama_karyawan"
                        value="<?= $edit_mode ? htmlspecialchars($edit_data['nama_karyawan']) : ''; ?>"
                        required>
                </div>

                <div>
                    <label class="block mb-1 font-medium">Posisi</label>
                    <input class="w-full border rounded p-2"
                        name="posisi"
                        value="<?= $edit_mode ? htmlspecialchars($edit_data['posisi']) : ''; ?>"
                        required>
                </div>

                <div class="md:col-span-2">
                    <label class="block mb-1 font-medium">Alamat</label>
                    <textarea class="w-full border rounded p-2"
                        name="alamat" rows="3"
                        required><?= $edit_mode ? htmlspecialchars($edit_data['alamat']) : ''; ?></textarea>
                </div>

                <div>
                    <label class="block mb-1 font-medium">No HP</label>
                    <input class="w-full border rounded p-2"
                        name="no_hp"
                        value="<?= $edit_mode ? htmlspecialchars($edit_data['no_hp']) : ''; ?>"
                        required>
                </div>

                <div>
                    <label class="block mb-1 font-medium">Gaji Pokok</label>
                    <input type="number" class="w-full border rounded p-2"
                        name="gaji_pokok"
                        value="<?= $edit_mode ? htmlspecialchars($edit_data['gaji_pokok']) : ''; ?>"
                        required>
                </div>
            </div>

            <div class="mt-6 text-right">
                <?php if ($edit_mode): ?>
                    <button type="submit" name="update"
                        class="bg-primary text-white px-6 py-2 rounded hover:opacity-90">Update</button>
                <?php else: ?>
                    <button type="submit" name="tambah"
                        class="bg-primary text-white px-6 py-2 rounded hover:opacity-90">Simpan</button>
                <?php endif; ?>
            </div>
        </form>

    </div>



    <!-- ============================ TABEL KARYAWAN ============================ -->
    <h2 class="text-xl font-semibold mb-4">Daftar Karyawan</h2>

    <div class="overflow-x-auto bg-white rounded-lg shadow">
        <table class="w-full text-sm text-left">
            <thead class="border-b">
                <tr>
                    <th class="px-6 py-4">ID</th>
                    <th class="px-6 py-4">Nama</th>
                    <th class="px-6 py-4">Posisi</th>
                    <th class="px-6 py-4">Alamat</th>
                    <th class="px-6 py-4">No HP</th>
                    <th class="px-6 py-4">Gaji Pokok</th>
                    <th class="px-6 py-4">Aksi</th>
                </tr>
            </thead>

            <tbody>
                <?php if (count($karyawan) === 0): ?>
                    <tr>
                        <td colspan="7" class="text-center p-4 text-gray-500">Belum ada data karyawan.</td>
                    </tr>
                <?php endif; ?>

                <?php foreach ($karyawan as $row): ?>
                    <tr class="border-b">
                        <td class="px-6 py-4"><?= $row['id_karyawan']; ?></td>
                        <td class="px-6 py-4"><?= htmlspecialchars($row['nama_karyawan']); ?></td>
                        <td class="px-6 py-4"><?= htmlspecialchars($row['posisi']); ?></td>
                        <td class="px-6 py-4"><?= htmlspecialchars($row['alamat']); ?></td>
                        <td class="px-6 py-4"><?= htmlspecialchars($row['no_hp']); ?></td>
                        <td class="px-6 py-4">
                            <?= "Rp " . number_format($row['gaji_pokok'], 0, ',', '.'); ?>
                        </td>

                        <td class="px-6 py-4 whitespace-nowrap">
                            <a class="text-primary font-medium mr-4 hover:underline"
                                href="karyawan.php?edit=<?= $row['id_karyawan']; ?>">Edit</a>

                            <a class="text-red-600 font-medium hover:underline"
                                onclick="return confirm('Yakin ingin menghapus?');"
                                href="karyawan.php?hapus=<?= $row['id_karyawan']; ?>">Hapus</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>

        </table>
    </div>

</div>
</body>
</html>
