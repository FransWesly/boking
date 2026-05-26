<?php
require_once __DIR__ . '/../termasuk/koneksi.php';

$error = '';
if (isset($_SESSION['admin_logged']) && $_SESSION['admin_logged'] === true) {
    header('Location: dasbor.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($username === '' || $password === '') {
        $error = 'Masukkan username dan password.';
    } else {
        $stmt = $pdo->prepare('SELECT * FROM admins WHERE username = ? LIMIT 1');
        $stmt->execute([$username]);
        $admin = $stmt->fetch();
        if ($admin && password_verify($password, $admin['password'])) {
            $_SESSION['admin_logged'] = true;
            $_SESSION['admin_name'] = $admin['name'];
            header('Location: dasbor.php');
            exit;
        }
        $error = 'Username atau password salah.';
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Admin - StadionBola</title>
    <link rel="stylesheet" href="../gaya/gaya.css">
</head>
<body>
    <main class="section section-booking">
        <div class="container" style="max-width: 480px;">
            <div class="card glass login-card">
                <div class="login-header">
                    <span class="section-label">Admin Access</span>
                    <h2>Login Dashboard</h2>
                    <p style="color: var(--text-muted); margin: 0;">Masukkan kredensial admin untuk mengakses panel kontrol.</p>
                </div>
                <?php if ($error): ?>
                    <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>
                <form method="post" class="login-form">
                    <label>
                        <span class="label-text">Username</span>
                        <input type="text" name="username" required autofocus>
                    </label>
                    <label>
                        <span class="label-text">Password</span>
                        <input type="password" name="password" required>
                    </label>
                    <button class="btn btn-primary login-btn" type="submit">Masuk ke Dashboard</button>
                </form>
                <div class="login-footer">
                    <a href="../index.php" class="btn btn-link">← Kembali ke Beranda</a>
                </div>
            </div>
        </div>
    </main>
</body>
</html>
