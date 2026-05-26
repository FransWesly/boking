<?php
require_once __DIR__ . '/../termasuk/koneksi.php';
require_once __DIR__ . '/../termasuk/otentikasi.php';

$bookings = [];
try {
    $bookings = $pdo->query('SELECT b.*, m.event_name, m.team_home, m.team_away, m.match_date, m.match_time FROM bookings b JOIN matches m ON b.match_id = m.id ORDER BY b.id DESC')->fetchAll();
} catch (Exception $e) {
    $bookings = [];
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Booking - StadionBola</title>
    <link rel="stylesheet" href="../gaya/gaya.css">
</head>
<body class="admin-layout">
    <aside class="admin-sidebar">
        <div class="sidebar-header">
            <a class="brand" href="dasbor.php">Admin StadionBola</a>
        </div>
        <nav class="sidebar-nav">
            <a href="dasbor.php" class="nav-item">Dashboard</a>
            <a href="event.php" class="nav-item">Event</a>
            <a href="data_pemesanan.php" class="nav-item active">Booking</a>
            <a href="keluar.php" class="nav-item logout">Keluar</a>
        </nav>
    </aside>

    <main class="admin-main">
        <header class="admin-header">
            <h1>Data Booking</h1>
            <p>Semua transaksi pemesanan tiket yang telah tercatat.</p>
        </header>

        <div class="admin-content">
            <div class="card glass">
                <h3 style="margin-top: 0; margin-bottom: 1.5rem;">Daftar Transaksi</h3>
                <div style="overflow-x:auto;">
                    <table style="width:100%; border-collapse: collapse; color: var(--text);">
                        <thead>
                            <tr style="border-bottom: 1px solid rgba(255,255,255,0.08);">
                                <th style="text-align:left; padding: 1rem 0;">Nama</th>
                                <th style="text-align:left; padding: 1rem 0;">Event</th>
                                <th style="text-align:left; padding: 1rem 0;">Kategori</th>
                                <th style="text-align:left; padding: 1rem 0;">Email / Telepon</th>
                                <th style="text-align:left; padding: 1rem 0;">Tiket</th>
                                <th style="text-align:left; padding: 1rem 0;">Total</th>
                                <th style="text-align:left; padding: 1rem 0;">Pembayaran</th>
                                <th style="text-align:left; padding: 1rem 0;">Waktu</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($bookings): ?>
                                <?php foreach ($bookings as $booking): ?>
                                    <tr style="border-bottom: 1px solid rgba(255,255,255,0.08);">
                                        <td style="padding: 1rem 0;"><?php echo htmlspecialchars($booking['customer_name']); ?></td>
                                        <td style="padding: 1rem 0;"><?php echo htmlspecialchars($booking['event_name'] ?: ($booking['team_home'] . ' vs ' . $booking['team_away'])); ?></td>
                                        <td style="padding: 1rem 0;"><?php echo htmlspecialchars($booking['ticket_category']); ?></td>
                                        <td style="padding: 1rem 0;"><?php echo htmlspecialchars($booking['customer_email']); ?><br><?php echo htmlspecialchars($booking['customer_phone']); ?></td>
                                        <td style="padding: 1rem 0;"><?php echo intval($booking['seats']); ?></td>
                                        <td style="padding: 1rem 0;">Rp <?php echo number_format($booking['total_price'], 0, ',', '.'); ?></td>
                                        <td style="padding: 1rem 0;"><?php echo htmlspecialchars($booking['payment_method']); ?></td>
                                        <td style="padding: 1rem 0;"><?php echo date('d M Y H:i', strtotime($booking['created_at'])); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" style="padding: 1rem 0; color: var(--text-muted);">Belum ada booking yang tercatat.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>
</body>
</html>
