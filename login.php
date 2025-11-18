<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require __DIR__ . '/../config/db.php';

$error = "";

// Jika sudah login, lempar ke dashboard
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {
        $error = "Username dan password wajib diisi.";
    } else {
        // Struktur tabel: user (id_user, username, password, jabatan, nama_lengkap)
        $sql = "SELECT * FROM user WHERE username = ? LIMIT 1";
        $stmt = mysqli_prepare($conn, $sql);
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "s", $username);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            $user   = mysqli_fetch_assoc($result);

            if ($user && password_verify($password, $user['password'])) {
                $_SESSION['user_id']  = $user['id_user'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role']     = $user['jabatan'];
                $_SESSION['nama']     = $user['nama_lengkap'];
                header('Location: dashboard.php');
                exit;
            } else {
                $error = "Username atau password salah.";
            }
        } else {
            $error = "Gagal prepare query: " . mysqli_error($conn);
        }
    }
}
?>
<!DOCTYPE html>
<html class="light" lang="en">

<head>
    <meta charset="utf-8" />
    <meta content="width=device-width, initial-scale=1.0" name="viewport" />
    <title>Login</title>

    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>

    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap"
        rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet" />

    <!-- Tailwind Config -->
    <script>
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    colors: {
                        "primary": "#cf7317",
                        "background-light": "#f8f7f6",
                        "background-dark": "#211911",
                        "brand": {
                            "brown": "#6F4E37",
                            "dark-brown": "#3B2F2F",
                            "light-brown": "#D2B48C",
                            "off-white": "#FEFDFB"
                        }
                    },
                    fontFamily: {
                        "display": ["Manrope", "sans-serif"]
                    },
                    borderRadius: { "DEFAULT": "0.25rem", "lg": "0.5rem", "xl": "0.75rem", "full": "9999px" },
                },
            },
        }
    </script>

    <!-- External CSS (pastikan file ini ada di folder yang sama dengan login.php) -->
    <link rel="stylesheet" href="styles.css">
</head>

<body class="bg-background-light dark:bg-background-dark font-display">
    <div class="relative flex h-auto min-h-screen w-full flex-col group/design-root overflow-hidden">
        <div class="layout-container flex h-full grow flex-col">
            <div class="flex flex-1 justify-center items-center">
                <div class="layout-content-container flex flex-col w-full">
                    <div class="grid grid-cols-1 md:grid-cols-5 min-h-screen">

                        <!-- Left Image Panel -->
                        <div class="hidden md:flex md:col-span-2 w-full h-full bg-center bg-no-repeat bg-cover"
                            style='background-image: url("https://lh3.googleusercontent.com/aida-public/AB6AXuAwT6001F4s1AyQIUU5NDWZh7JfU_0N4dtolxUWARSrw8ZGeRhGqJ8ckM1ZegMfiRkXD-5IukebwtK-kzDcaqjydw3dupwC_H1wzXzU4w1bLcV8f-WkyoomadPl7jQst4AZQ-m5FzOxIe1GI5JbcxuqDKPhF-BGeTx8SvJ72Q6xw2NGCjCtJXapKiyX1lXk5LGjSWT_A2LgjkX8v8qATfuls0EBRfhsR0sQrcyJHQVFzAS6Joy91r6AveVkD857vhrU0C6Y6VzvUe8");'>
                        </div>

                        <!-- Right Form Panel -->
                        <div
                            class="col-span-1 md:col-span-3 bg-brand-off-white dark:bg-background-dark flex flex-col items-center justify-center p-8 lg:p-12">
                            <div class="w-full max-w-md space-y-8">

                                <!-- Header -->
                                <div class="text-center">
                                    <div
                                        class="inline-flex items-center justify-center bg-brand-light-brown/20 dark:bg-primary/20 p-3 rounded-full mb-4">
                                        <span
                                            class="material-symbols-outlined text-brand-brown dark:text-primary icon-large">lock</span>
                                    </div>
                                    <h1
                                        class="text-brand-dark-brown dark:text-gray-200 text-3xl font-bold tracking-tight">
                                        Welcome Back</h1>
                                    <p class="text-gray-500 dark:text-gray-400 mt-2">Enter your credentials to access
                                        your account.</p>

                                    <?php if ($error): ?>
                                    <div class="mt-3 text-sm text-red-600">
                                        <?php echo htmlspecialchars($error); ?>
                                    </div>
                                    <?php endif; ?>
                                </div>

                                <!-- Form (DESAIN ASLI, hanya ditambah atribut name & type) -->
                                <form method="post" action="login.php" class="flex flex-col gap-6">

                                    <div class="flex flex-col gap-2">
                                        <label class="text-brand-dark-brown dark:text-gray-300 font-medium"
                                            for="username">Username</label>
                                        <input class="form-input custom-input" id="username" name="username"
                                            placeholder="Enter your username" />
                                    </div>

                                    <div class="flex flex-col gap-2">
                                        <label class="text-brand-dark-brown dark:text-gray-300 font-medium"
                                            for="password">Password</label>
                                        <input class="form-input custom-input" id="password" name="password"
                                            placeholder="Enter your password" type="password" />
                                    </div>

                                    <div class="flex items-center justify-between">
                                        <label class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400">
                                            <input class="form-checkbox h-4 w-4 rounded text-brand-brown"
                                                type="checkbox" />
                                            Remember me
                                        </label>
                                        <a class="text-sm font-medium text-brand-brown hover:text-brand-dark-brown dark:text-brand-light-brown dark:hover:text-primary transition-colors"
                                            href="#">
                                            Forgot Password?
                                        </a>
                                    </div>

                                    <button type="submit" class="btn-login">Log In</button>
                                </form>

                                <p class="text-center text-sm text-gray-500 dark:text-gray-400">
                                    Don't have an account?
                                    <a class="font-medium text-brand-brown hover:text-brand-dark-brown dark:text-brand-light-brown dark:hover:text-primary transition-colors"
                                        href="#">Sign Up</a>
                                </p>

                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>

</html>
