<?php
require_once __DIR__ . '/../termasuk/koneksi.php';
require_once __DIR__ . '/../termasuk/otentikasi.php';

$stats = [
    'matches' => 0,
    'bookings' => 0,
    'available_seats' => 0,
    'revenue' => 0,
];
$categorySales = [];
try {
    $stats['matches'] = $pdo->query('SELECT COUNT(*) FROM matches')->fetchColumn();
    $stats['bookings'] = $pdo->query('SELECT COUNT(*) FROM bookings')->fetchColumn();
    $stats['available_seats'] = $pdo->query('SELECT COALESCE(SUM(available_seats), 0) FROM matches')->fetchColumn();
    $stats['revenue'] = $pdo->query('SELECT COALESCE(SUM(total_price), 0) FROM bookings')->fetchColumn();
    $recent = $pdo->query('SELECT b.*, m.event_name, m.team_home, m.team_away FROM bookings b JOIN matches m ON b.match_id = m.id ORDER BY b.id DESC LIMIT 5')->fetchAll();
    $categorySales = $pdo->query("SELECT ticket_category, COALESCE(SUM(seats),0) AS seats_sold, COALESCE(SUM(total_price),0) AS revenue FROM bookings GROUP BY ticket_category")->fetchAll();
} catch (Exception $e) {
    $recent = [];
    $categorySales = [];
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin - StadionBola</title>
    <link rel="stylesheet" href="../gaya/gaya.css">
</head>
<body>
    <aside class="admin-sidebar">
        <div class="sidebar-header">
            <a class="brand" href="dasbor.php">Admin StadionBola</a>
        </div>
        <nav class="sidebar-nav">
            <a href="dasbor.php" class="nav-item active">Dashboard</a>
            <a href="event.php" class="nav-item">Event</a>
            <a href="data_pemesanan.php" class="nav-item">Booking</a>
            <a href="keluar.php" class="nav-item logout">Logout</a>
        </nav>
    </aside>

    <main class="admin-main">
        <header class="admin-header">
            <h1>Dashboard</h1>
            <p>Halo, <?php echo htmlspecialchars($_SESSION['admin_name']); ?> - Ringkasan data pertandingan dan booking tiket saat ini.</p>
        </header>

        <div class="admin-content">
            <div class="grid-3">
                <div class="feature-card">
                    <span class="section-label">Pertandingan</span>
                    <h3><?php echo intval($stats['matches']); ?></h3>
                    <p>Total pertandingan aktif yang bisa dipesan oleh pengguna.</p>
                </div>
                <div class="feature-card">
                    <span class="section-label">Booking</span>
                    <h3><?php echo intval($stats['bookings']); ?></h3>
                    <p>Total pemesanan tiket yang sudah masuk ke sistem.</p>
                </div>
                <div class="feature-card">
                    <span class="section-label">Tiket Tersisa</span>
                    <h3><?php echo intval($stats['available_seats']); ?></h3>
                    <p>Semua kapasitas sisa tiket yang tersedia di semua pertandingan.</p>
                </div>
            </div>

            <div class="card glass" style="margin-top: 2rem;">
                <span class="section-label">Penjualan Tiket</span>
                <h3>Pendapatan & Penjualan per Kategori</h3>
                <p style="color: var(--text-muted); margin-top: 0.75rem;">Lihat distribusi kategori tiket melalui ringkasan penjualan berikut.</p>
                <?php if ($categorySales): ?>
                    <?php foreach ($categorySales as $sale): ?>
                        <?php $width = $stats['revenue'] > 0 ? min(100, round($sale['revenue'] / $stats['revenue'] * 100)) : 0; ?>
                        <div class="chart-row">
                            <div class="chart-label"><?php echo htmlspecialchars($sale['ticket_category']); ?></div>
                            <div class="chart-bar">
                                <div class="chart-bar-fill" style="width: <?php echo $width; ?>%;"></div>
                            </div>
                            <div style="min-width: 8rem; text-align: right; color: var(--text-muted);">Rp <?php echo number_format($sale['revenue'], 0, ',', '.'); ?></div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p style="margin-top: 1rem; color: var(--text-muted);">Belum ada penjualan tiket untuk saat ini.</p>
                <?php endif; ?>
            </div>

            <section class="section" style="padding-top: 2rem;">
                <div class="card glass">
                    <span class="section-label">Booking Terbaru</span>
                    <h3>5 Transaksi Terakhir</h3>
                    <?php if ($recent): ?>
                        <div style="overflow-x:auto; margin-top:1.25rem;">
                            <table style="width:100%; border-collapse: collapse; color: var(--text);">
                                <thead>
                                    <tr style="border-bottom: 1px solid rgba(255,255,255,0.08);">
                                        <th style="text-align:left; padding: 0.9rem 0;">Nama</th>
                                        <th style="text-align:left; padding: 0.9rem 0;">Pertandingan</th>
                                        <th style="text-align:left; padding: 0.9rem 0;">Jumlah</th>
                                        <th style="text-align:left; padding: 0.9rem 0;">Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recent as $row): ?>
                                        <tr style="border-bottom: 1px solid rgba(255,255,255,0.08);">
                                            <td style="padding: 0.9rem 0;"><?php echo htmlspecialchars($row['customer_name']); ?></td>
                                            <td style="padding: 0.9rem 0;"><?php echo htmlspecialchars($row['team_home'] . ' vs ' . $row['team_away']); ?></td>
                                            <td style="padding: 0.9rem 0;"><?php echo intval($row['seats']); ?></td>
                                            <td style="padding: 0.9rem 0;">Rp <?php echo number_format($row['total_price'], 0, ',', '.'); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p style="color: var(--text-muted); margin-top: 1rem;">Belum ada transaksi terbaru.</p>
                    <?php endif; ?>
                </div>
            </section>
        </div>
    </main>
</body>
</html>
