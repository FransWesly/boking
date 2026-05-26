<?php
require_once __DIR__ . '/termasuk/koneksi.php';

$matches = [];
try {
    $stmt = $pdo->query("SELECT * FROM matches WHERE match_date >= CURDATE() ORDER BY match_date, match_time");
    $matches = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $matches = [];
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Boking Tiket Bola</title>
    <link rel="stylesheet" href="gaya/gaya.css">
</head>

<body>
    <header class="hero hero-home">
        <div class="hero-slider" aria-hidden="true"></div>
        <nav class="navbar">
            <a class="brand" href="index.php">StadionBola</a>
            <div class="nav-links">
                <a href="#matches">Pertandingan</a>
                <a href="#fitur">Fitur</a>
                <a href="#contact">Kontak</a>
            </div>
            <div class="nav-actions">
                <a class="btn btn-secondary" href="pengelola/masuk.php">Admin Login</a>
            </div>
            <button class="menu-toggle" aria-label="Toggle menu">☰</button>
        </nav>
        <div class="hero-content">
            <span class="eyebrow">Ticket Booking Platform</span>
            <h1>Pesan Tiket Nonton Bola dengan Mudah</h1>
            <p>Temukan jadwal pertandingan, pilih kursi, dan booking langsung dalam beberapa klik. Tampilan modern dan
                proses cepat untuk fans setia.</p>
            <div class="hero-actions">
                <a class="btn btn-primary" href="#matches">Lihat Jadwal</a>
                <a class="btn btn-secondary" href="pengelola/masuk.php">Admin</a>
            </div>
        </div>
    </header>

    <main>
        <section class="section section-features" id="fitur">
            <div class="container grid-3">
                <div class="feature-card">
                    <h3>Booking Instan</h3>
                    <p>Pilih pertandingan dan isi data untuk amankan tiket. Sistem kami otomatis menghitung total harga.
                    </p>
                </div>
                <div class="feature-card">
                    <h3>Data Terpercaya</h3>
                    <p>Kelola tiket dengan aman, semua informasi pertandingan dan transaksi tersimpan di database.</p>
                </div>
                <div class="feature-card">
                    <h3>Dashboard Admin</h3>
                    <p>Admin dapat menambah pertandingan, memantau booking, dan mengelola data dengan antarmuka simpel.
                    </p>
                </div>
            </div>
        </section>

        <section class="section section-matches" id="matches">
            <div class="container section-header">
                <span class="section-label">Jadwal Terbaru</span>
                <h2>Pertandingan Mendatang</h2>
                <p>Pilih pertandingan favorit Anda dan pesan sekarang sebelum tiket habis.</p>
            </div>
            <div class="grid-3 cards">
                <?php if (!$matches): ?>
                <div class="empty-state">
                    <p>Belum ada event tersedia untuk saat ini. Silakan cek kembali nanti atau hubungi admin.</p>
                </div>
                <?php else: ?>
                <?php foreach ($matches as $match): ?>
                <?php $eventName = $match['event_name'] ?: ($match['team_home'] . ' vs ' . $match['team_away']); ?>
                <article class="match-card">
                    <?php if (!empty($match['banner_url'])): ?>
                    <img class="match-banner" src="<?php echo htmlspecialchars($match['banner_url']); ?>"
                        alt="Banner <?php echo htmlspecialchars($eventName); ?>">
                    <?php endif; ?>
                    <div class="match-info">
                        <span class="match-teams"><?php echo htmlspecialchars($eventName); ?></span>
                        <span class="match-date"><?php echo date('d M Y', strtotime($match['match_date'])); ?> •
                            <?php echo date('H:i', strtotime($match['match_time'])); ?></span>
                        <span class="match-stadium"><?php echo htmlspecialchars($match['stadium']); ?></span>
                    </div>
                    <div class="match-meta">
                        <span class="price">Umum Rp
                            <?php echo number_format($match['price_general'], 0, ',', '.'); ?></span>
                        <span class="seats">Umum <?php echo intval($match['available_general']); ?> • VIP
                            <?php echo intval($match['available_vip']); ?> • VVIP
                            <?php echo intval($match['available_vvip']); ?></span>
                    </div>
                    <a class="btn btn-primary" href="pemesanan.php?match_id=<?php echo intval($match['id']); ?>">Pesan
                        Sekarang</a>
                </article>
                <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </section>

        <section class="section section-cta">
            <div class="container cta-card">
                <div>
                    <h2>Siap nonton langsung di stadion?</h2>
                    <p>Booking tiket sekarang dan rasakan sensasi sepak bola dengan dukungan suporter sejati.</p>
                </div>
                <a class="btn btn-secondary" href="#matches">Pesan Tiket</a>
            </div>
        </section>

        <section class="section section-contact" id="contact">
            <div class="container contact-grid">
                <div>
                    <h2>Hubungi Kami</h2>
                    <p>Butuh bantuan atau ingin menambahkan pertandingan? Tim support kami siap membantu.</p>
                </div>
                <div class="contact-info">
                    <p><strong>Email:</strong> support@stadionbola.id</p>
                    <p><strong>Telepon:</strong> 0812-3456-7890</p>
                </div>
            </div>
        </section>
    </main>

    <footer class="footer">
        <p>&copy; <?php echo date('Y'); ?> StadionBola. All rights reserved.</p>
    </footer>

    <script src="skrip/aplikasi.js"></script>
</body>

</html>