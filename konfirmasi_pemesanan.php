<?php
require_once __DIR__ . '/termasuk/koneksi.php';
require_once __DIR__ . '/vendor/autoload.php';

use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Writer\PngWriter;

$bookingId = isset($_GET['booking_id']) ? intval($_GET['booking_id']) : 0;
$booking = null;
$match = null;

try {
    $stmt = $pdo->prepare('SELECT b.*, m.event_name, m.team_home, m.team_away, m.stadium, m.match_date, m.match_time 
                           FROM bookings b 
                           JOIN matches m ON b.match_id = m.id 
                           WHERE b.id = ?');
    $stmt->execute([$bookingId]);
    $booking = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$booking) {
        die('Booking tidak ditemukan.');
    }
} catch (Exception $e) {
    die('Error: ' . htmlspecialchars($e->getMessage()));
}

$eventName = $booking['event_name'] ?: ($booking['team_home'] . ' vs ' . $booking['team_away']);
$bookingCode = 'BK-' . str_pad($booking['id'], 6, '0', STR_PAD_LEFT);

// Generate QR code data
$qrData = $bookingCode . '|' .
          $booking['customer_name'] . '|' .
          $eventName . '|' .
          date('d-m-Y H:i', strtotime($booking['created_at'])) . '|' .
          'Rp ' . number_format($booking['total_price'], 0, ',', '.');

// Generate QR code untuk download
if (isset($_GET['download']) && $_GET['download'] === 'qrcode') {
    try {
        $result = Builder::create()
            ->writer(new PngWriter())
            ->data($qrData)
            ->size(300)
            ->margin(10)
            ->build();
        
        header('Content-Type: image/png');
        header('Content-Disposition: attachment; filename="qrcode-' . $bookingCode . '.png"');
        echo $result->getString();
        exit;
    } catch (Exception $e) {
        die('Error generating QR code: ' . htmlspecialchars($e->getMessage()));
    }
}

// Generate PDF download
if (isset($_GET['download']) && $_GET['download'] === 'pdf') {
    ob_start();
    try {
        $result = Builder::create()
            ->writer(new PngWriter())
            ->data($qrData)
            ->size(300)
            ->margin(10)
            ->build();
        $qrBase64 = base64_encode($result->getString());
    } catch (Exception $e) {
        $qrBase64 = '';
    }
    ?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Tiket Booking - <?php echo htmlspecialchars($bookingCode); ?></title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; padding: 20px; }
        .ticket { max-width: 600px; margin: 0 auto; border: 2px solid #667eea; padding: 20px; background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%); }
        .ticket-header { text-align: center; margin-bottom: 20px; }
        .ticket-header h1 { margin: 0; font-size: 28px; color: #667eea; }
        .ticket-event { text-align: center; font-size: 22px; font-weight: bold; margin: 15px 0; color: #333; }
        .ticket-details { margin: 15px 0; }
        .ticket-details p { margin: 8px 0; line-height: 1.6; }
        .ticket-qrcode { text-align: center; margin: 20px 0; }
        .ticket-qrcode img { max-width: 280px; border: 3px solid #667eea; padding: 10px; background: white; }
        .ticket-qrcode-text { text-align: center; font-size: 18px; font-weight: bold; margin: 10px 0; color: #333; letter-spacing: 2px; }
        .ticket-total { text-align: center; margin: 20px 0; }
        .ticket-total strong { font-size: 26px; color: #667eea; }
        .ticket-footer { text-align: center; margin-top: 20px; font-size: 12px; color: #666; }
        .divider { border-top: 2px solid #667eea; margin: 15px 0; }
        table { width: 100%; border-collapse: collapse; }
        td { padding: 6px; border-bottom: 1px solid #ddd; }
        td:first-child { font-weight: bold; width: 40%; color: #667eea; }
        @media print { body { margin: 0; padding: 0; } .ticket { background: white; } }
    </style>
</head>
<body onload="window.print();">
    <div class="ticket">
        <div class="ticket-header">
            <h1>🎫 TIKET BOOKING</h1>
        </div>
        
        <div class="ticket-event"><?php echo htmlspecialchars($eventName); ?></div>
        

        <div class="ticket-qrcode">
            <?php if ($qrBase64): ?>
                <img src="data:image/png;base64,<?php echo $qrBase64; ?>" alt="QR Code">
            <?php endif; ?>
            <div class="ticket-qrcode-text"><?php echo htmlspecialchars($bookingCode); ?></div>
        </div>
        
        <div class="ticket-footer">
            <p>Harap tunjukkan QR code ini saat memasuki stadion.</p>
            <p>Tanggal cetak: <?php echo date('d-m-Y H:i'); ?></p>
            <p>© 2026 StadionBola - Pemesanan Tiket Stadion</p>
        </div>
    </div>
</body>
</html>
    <?php
    $content = ob_get_clean();
    header('Content-Type: text/html; charset=UTF-8');
    header('Content-Disposition: attachment; filename="tiket-' . $bookingCode . '.html"');
    echo $content;
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Konfirmasi Booking - <?php echo htmlspecialchars($eventName); ?></title>
    <link rel="stylesheet" href="gaya/gaya.css">
    <style>
        .confirmation-container {
            max-width: 700px;
            margin: 40px auto;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 15px;
            padding: 40px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            color: white;
            text-align: center;
        }
        .confirmation-icon {
            font-size: 70px;
            margin-bottom: 20px;
            animation: bounce 0.6s ease-in-out;
        }
        @keyframes bounce {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-20px); }
        }
        .confirmation-title {
            font-size: 32px;
            margin-bottom: 10px;
            font-weight: bold;
        }
        .confirmation-subtitle {
            font-size: 16px;
            opacity: 0.95;
            margin-bottom: 35px;
        }
        .booking-card {
            background: white;
            color: #333;
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 25px;
            text-align: left;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }
        .booking-card h3 {
            margin: 0 0 18px 0;
            color: #667eea;
            border-bottom: 3px solid #667eea;
            padding-bottom: 12px;
            font-size: 18px;
        }
        .booking-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #eee;
            font-size: 15px;
        }
        .booking-row:last-child {
            border-bottom: none;
        }
        .booking-label {
            font-weight: bold;
            color: #667eea;
        }
        .booking-value {
            color: #333;
            text-align: right;
            flex: 1;
            margin-left: 20px;
        }
        .qris-card {
            background: white;
            border-radius: 15px;
            padding: 0;
            margin-bottom: 25px;
            text-align: center;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        .qris-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 25px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            font-weight: bold;
        }
        .qris-logo {
            font-size: 24px;
            font-style: italic;
            letter-spacing: 1px;
        }
        .gpn-logo {
            font-size: 16px;
        }
        .qris-merchant-info {
            padding: 20px 20px 10px 20px;
            color: #333;
        }
        .qris-merchant-info h3 {
            margin: 0 0 5px 0;
            font-size: 22px;
            color: #111;
        }
        .qris-merchant-info p {
            margin: 2px 0;
            font-size: 14px;
            color: #666;
        }
        .qris-qr-wrapper {
            padding: 20px;
            background: white;
        }
        .qris-qr-inner {
            display: inline-block;
            padding: 15px;
            background: white;
            border: 4px solid #667eea;
            border-radius: 15px;
        }
        .qris-qr-inner img {
            display: block;
            margin: 0;
            max-width: 100%;
        }
        .qris-footer {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 15px;
        }
        .qris-slogan {
            font-size: 18px;
            font-weight: bold;
            margin: 0 0 5px 0;
            letter-spacing: 1px;
        }
        .qris-sub-slogan {
            font-size: 12px;
            margin: 0;
            opacity: 0.9;
        }
        .total-price {
            font-size: 28px;
            font-weight: bold;
            margin: 25px 0;
            padding: 20px;
            background: rgba(255,255,255,0.2);
            border-radius: 10px;
            border: 2px solid rgba(255,255,255,0.3);
        }
        .action-buttons {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
            justify-content: center;
            margin-top: 25px;
        }
        .btn {
            padding: 13px 28px;
            border: none;
            border-radius: 8px;
            font-size: 15px;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
            white-space: nowrap;
        }
        .btn-primary {
            background: white;
            color: #667eea;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
        }
        .btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.3);
        }
        .btn-secondary {
            background: rgba(255,255,255,0.2);
            color: white;
            border: 2px solid white;
        }
        .btn-secondary:hover {
            background: white;
            color: #667eea;
            transform: scale(1.05);
        }
        .info-box {
            background: rgba(255,255,255,0.1);
            border-left: 5px solid white;
            padding: 18px;
            border-radius: 8px;
            margin-top: 25px;
            font-size: 14px;
            line-height: 1.6;
        }
        .print-button {
            padding: 13px 28px;
            background: white;
            color: #667eea;
            border: none;
            border-radius: 8px;
            font-weight: bold;
            cursor: pointer;
            margin-right: 0;
            transition: all 0.3s ease;
            font-size: 15px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
        }
        .print-button:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.3);
        }
        @media (max-width: 600px) {
            .confirmation-container {
                margin: 20px;
                padding: 25px;
            }
            .booking-row {
                flex-direction: column;
            }
            .booking-value {
                text-align: left;
                margin-left: 0;
                margin-top: 5px;
            }
            .action-buttons {
                flex-direction: column;
            }
            .btn, .print-button {
                width: 100%;
                margin: 0 0 10px 0;
            }
        }
    </style>
</head>
<body>
    <header class="header-simple">
        <nav class="navbar">
            <a class="brand" href="index.php">StadionBola</a>
            <div class="nav-links">
                <a href="index.php">Beranda</a>
                <a href="index.php#matches">Event</a>
                <a class="btn btn-secondary" href="pengelola/masuk.php">Admin</a>
            </div>
            <button class="menu-toggle" aria-label="Toggle menu">☰</button>
        </nav>
    </header>

    <main>
        <div class="confirmation-container">
            <div class="confirmation-icon">✅</div>
            <div class="confirmation-title">Booking Berhasil!</div>
            <div class="confirmation-subtitle">Tiket Anda sudah tersimpan, silakan lakukan pembayaran</div>


            <!-- QRIS Section -->
            <div class="qris-card">
                <div class="qris-header">
                    <div class="qris-logo">QRIS</div>
                    <div class="gpn-logo">GPN</div>
                </div>
                <div class="qris-merchant-info">
                    <h3>StadionBola</h3>
                    <p>NMID: ID<?php echo substr(md5($bookingCode), 0, 10); ?></p>
                    <p>TID: <?php echo htmlspecialchars($bookingCode); ?></p>
                </div>
                <div class="qris-qr-wrapper">
                    <div class="qris-qr-inner">
                        <?php
                        try {
                            $result = Builder::create()
                                ->writer(new PngWriter())
                                ->data($qrData)
                                ->size(300)
                                ->margin(0)
                                ->build();
                            echo '<img src="data:image/png;base64,' . base64_encode($result->getString()) . '" alt="QR Code">';
                        } catch (Exception $e) {
                            echo '<p style="color: red; font-weight: bold;">❌ Gagal membuat QR code</p>';
                        }
                        ?>
                    </div>
                </div>
                <div class="qris-footer">
                    <p class="qris-slogan">SATU QRIS UNTUK SEMUA</p>
                    <p class="qris-sub-slogan">Cek aplikasi penyelenggara<br>di: www.aspi-qris.id</p>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="action-buttons">
                <a href="?booking_id=<?php echo $booking['id']; ?>&download=qrcode" class="btn btn-primary" style="font-size: 16px; padding: 15px 30px;">📥 Download Gambar untuk Pembayaran</a>
                <button class="print-button" onclick="window.print();">🖨️ Cetak Tiket</button>
            </div>

            <!-- Info Box -->
            <div class="info-box">
                <strong>⚠️ Penting:</strong><br>
                Segera lakukan pembayaran. Setelah pembayaran berhasil, simpan tiket/QR code ini dengan baik untuk memasuki stadion. Jika tidak melakukan pembayaran dalam 24 jam, booking otomatis dibatalkan.
            </div>

            <!-- Back Button -->
            <div style="margin-top: 25px;">
                <a href="index.php" class="btn btn-secondary" style="width: calc(100% - 30px); padding: 13px 15px; display: block;">← Kembali ke Beranda</a>
            </div>
        </div>
    </main>

    <footer class="footer">
        <p>&copy; 2026 StadionBola. Semua hak dilindungi.</p>
    </footer>

    <script src="skrip/aplikasi.js"></script>
</body>
</html>
