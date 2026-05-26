<?php
require_once __DIR__ . '/termasuk/koneksi.php';

$matchId = isset($_GET['match_id']) ? intval($_GET['match_id']) : 0;
$message = '';
$success = false;
$match = null;

try {
    $stmt = $pdo->prepare('SELECT * FROM matches WHERE id = ?');
    $stmt->execute([$matchId]);
    $match = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $match = null;
}

if (!$match) {
    header('Location: index.php');
    exit;
}

$eventName = $match['event_name'] ?: ($match['team_home'] . ' vs ' . $match['team_away']);

$bookedByCategory = ['Umum' => 0, 'VIP' => 0, 'VVIP' => 0];
try {
    $bkStmt = $pdo->prepare('SELECT ticket_category, COALESCE(SUM(seats), 0) AS qty FROM bookings WHERE match_id = ? GROUP BY ticket_category');
    $bkStmt->execute([$matchId]);
    foreach ($bkStmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $cat = $row['ticket_category'];
        if (isset($bookedByCategory[$cat])) {
            $bookedByCategory[$cat] = intval($row['qty']);
        }
    }
} catch (Exception $e) {
    // tetap nol jika query gagal
}

$ticketCategory = $_POST['ticket_category'] ?? 'Umum';
$paymentMethod = $_POST['payment_method'] ?? 'Transfer Bank';
$quantity = intval($_POST['seats'] ?? 1);
$categoryKeys = [
    'Umum' => ['stock' => 'available_general', 'price' => 'price_general'],
    'VIP' => ['stock' => 'available_vip', 'price' => 'price_vip'],
    'VVIP' => ['stock' => 'available_vvip', 'price' => 'price_vvip'],
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $ticketCategory = $_POST['ticket_category'] ?? 'Umum';
    $paymentMethod = trim($_POST['payment_method'] ?? 'Transfer Bank');
    $quantity = intval($_POST['seats'] ?? 1);

    $categoryKey = $categoryKeys[$ticketCategory]['stock'] ?? 'available_general';
    $unitPriceKey = $categoryKeys[$ticketCategory]['price'] ?? 'price_general';
    $availableStock = intval($match[$categoryKey]);
    $pricePerTicket = floatval($match[$unitPriceKey]);

    if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || $phone === '' || $quantity < 1 || !isset($categoryKeys[$ticketCategory]) || $paymentMethod === '') {
        $message = 'Lengkapi semua data dengan benar.';
    } elseif ($quantity > $availableStock) {
        $message = 'Maaf, jumlah tiket melebihi ketersediaan kategori yang dipilih.';
    } else {
        try {
            $pdo->beginTransaction();
            $total = $pricePerTicket * $quantity;

            $update = $pdo->prepare("UPDATE matches SET $categoryKey = $categoryKey - ?, available_seats = GREATEST(available_seats - ?, 0) WHERE id = ? AND $categoryKey >= ?");
            $update->execute([$quantity, $quantity, $matchId, $quantity]);

            if ($update->rowCount() !== 1) {
                throw new RuntimeException('stock_unavailable');
            }

            $insert = $pdo->prepare('INSERT INTO bookings (match_id, customer_name, customer_email, customer_phone, ticket_category, ticket_price, payment_method, seats, total_price) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)');
            $insert->execute([$matchId, $name, $email, $phone, $ticketCategory, $pricePerTicket, $paymentMethod, $quantity, $total]);
            $bookingId = $pdo->lastInsertId();
            $pdo->commit();

            // Redirect ke halaman konfirmasi
            header('Location: konfirmasi_pemesanan.php?booking_id=' . intval($bookingId));
            exit;
        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $message = $e->getMessage() === 'stock_unavailable'
                ? 'Maaf, stok tiket baru saja berubah. Silakan pilih jumlah yang lebih kecil.'
                : 'Terjadi kesalahan saat proses booking. Coba lagi nanti.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking Tiket - <?php echo htmlspecialchars($eventName); ?></title>
    <link rel="stylesheet" href="gaya/gaya.css">
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

    <main class="section section-booking">
        <div class="container booking-grid">
            <div class="booking-summary card glass">
                <span class="section-label">Detail Event</span>
                <h2><?php echo htmlspecialchars($eventName); ?></h2>
                <p><?php echo date('d M Y', strtotime($match['match_date'])); ?> • <?php echo date('H:i', strtotime($match['match_time'])); ?></p>
                <p><?php echo htmlspecialchars($match['stadium']); ?></p>
                <?php if (!empty($match['banner_url'])): ?>
                    <img class="match-banner" src="<?php echo htmlspecialchars($match['banner_url']); ?>" alt="Banner <?php echo htmlspecialchars($eventName); ?>">
                <?php endif; ?>
                <div class="price-info">
                    <span>Harga Tiket</span>
                    <strong>Umum Rp <?php echo number_format($match['price_general'], 0, ',', '.'); ?></strong>
                </div>
                <div class="price-info">
                    <span>VIP</span>
                    <strong>Rp <?php echo number_format($match['price_vip'], 0, ',', '.'); ?></strong>
                </div>
                <div class="price-info">
                    <span>VVIP</span>
                    <strong>Rp <?php echo number_format($match['price_vvip'], 0, ',', '.'); ?></strong>
                </div>
                <p class="available">Sisa tiket: Umum <?php echo intval($match['available_general']); ?> • VIP <?php echo intval($match['available_vip']); ?> • VVIP <?php echo intval($match['available_vvip']); ?></p>
                <div class="seat-map-stage" aria-hidden="true">Arah pandang ke lapangan</div>
                <div class="seat-map" id="seatMap"></div>
                <ul class="seat-legend">
                    <li><span class="seat-legend-swatch seat-legend-free"></span> Tersedia</li>
                    <li><span class="seat-legend-swatch seat-legend-busy"></span> Sudah dibooking</li>
                    <li><span class="seat-legend-swatch seat-legend-pick"></span> Dipilih (proses)</li>
                </ul>
                <div class="seat-summary" id="seatSummary">
                    <p><strong>Dipilih:</strong> <span id="selectedCount">0</span> tiket • <strong>Total Harga:</strong> Rp <span id="selectedTotal">0</span></p>
                </div>
                <p class="seat-map-note">Peta menampilkan paling banyak 32 kursi per kategori, proporsional dengan terjual/tersisa.</p>
            </div>

            <form class="booking-form card glass" method="post" novalidate>
                <span class="section-label">Isi Data & Pilih Tiket</span>
                <?php if ($message && !$success): ?>
                    <div class="alert alert-error"><?php echo htmlspecialchars($message); ?></div>
                <?php endif; ?>
                <label>
                    Nama Lengkap
                    <input type="text" name="name" required value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>">
                </label>
                <label>
                    Email
                    <input type="email" name="email" required value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                </label>
                <label>
                    Nomor Telepon
                    <input type="text" name="phone" required value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>">
                </label>
                <label>
                    Kategori Tiket
                    <select name="ticket_category" id="ticketCategory" required>
                        <option value="Umum" <?php echo $ticketCategory === 'Umum' ? 'selected' : ''; ?>>Umum - Rp <?php echo number_format($match['price_general'], 0, ',', '.'); ?></option>
                        <option value="VIP" <?php echo $ticketCategory === 'VIP' ? 'selected' : ''; ?>>VIP - Rp <?php echo number_format($match['price_vip'], 0, ',', '.'); ?></option>
                        <option value="VVIP" <?php echo $ticketCategory === 'VVIP' ? 'selected' : ''; ?>>VVIP - Rp <?php echo number_format($match['price_vvip'], 0, ',', '.'); ?></option>
                    </select>
                </label>
                <label>
                    Jumlah Tiket
                    <input type="number" name="seats" id="ticketCount" min="0" max="<?php echo intval($match['available_general']); ?>" value="<?php echo intval($_POST['seats'] ?? 1); ?>">
                    <small id="stockInfo" style="color: var(--text-muted);">Maksimum: <?php echo intval($match['available_general']); ?> tiket</small>
                </label>
                <label>
                    Metode Pembayaran
                    <select name="payment_method" required>
                        <option value="Transfer Bank" <?php echo $paymentMethod === 'Transfer Bank' ? 'selected' : ''; ?>>Transfer Bank</option>
                        <option value="OVO / GoPay" <?php echo $paymentMethod === 'OVO / GoPay' ? 'selected' : ''; ?>>OVO / GoPay</option>
                        <option value="Kartu Kredit" <?php echo $paymentMethod === 'Kartu Kredit' ? 'selected' : ''; ?>>Kartu Kredit</option>
                    </select>
                </label>
                <button class="btn btn-primary" type="submit">Konfirmasi Booking</button>
                <a class="btn btn-link" href="index.php#matches">Kembali ke Event</a>
            </form>
        </div>
    </main>

    <footer class="footer footer-small">
        <p>&copy; <?php echo date('Y'); ?> StadionBola.</p>
    </footer>

    <script src="skrip/aplikasi.js"></script>
    <script>
        const ticketData = {
            Umum: { available: <?php echo intval($match['available_general']); ?>, booked: <?php echo intval($bookedByCategory['Umum']); ?>, price: <?php echo floatval($match['price_general']); ?> },
            VIP: { available: <?php echo intval($match['available_vip']); ?>, booked: <?php echo intval($bookedByCategory['VIP']); ?>, price: <?php echo floatval($match['price_vip']); ?> },
            VVIP: { available: <?php echo intval($match['available_vvip']); ?>, booked: <?php echo intval($bookedByCategory['VVIP']); ?>, price: <?php echo floatval($match['price_vvip']); ?> }
        };

        const seatMap = document.getElementById('seatMap');
        const ticketCategory = document.getElementById('ticketCategory');
        const ticketCount = document.getElementById('ticketCount');
        const stockInfo = document.getElementById('stockInfo');
        const selectedCount = document.getElementById('selectedCount');
        const selectedTotal = document.getElementById('selectedTotal');

        function formatRupiah(value) {
            return new Intl.NumberFormat('id-ID').format(value);
        }

        function updateSummary(category) {
            const data = ticketData[category] || ticketData.Umum;
            const selected = seatMap.querySelectorAll('.seat.selected').length;
            const avail = Math.max(0, Number(data.available) || 0);
            if (avail <= 0) {
                ticketCount.value = 0;
            } else {
                ticketCount.value = selected > 0 ? selected : 1;
            }
            selectedCount.textContent = selected;
            selectedTotal.textContent = formatRupiah(selected > 0 ? selected * data.price : 0);
        }

        function renderSeatMap(category) {
            const data = ticketData[category] || ticketData.Umum;
            seatMap.innerHTML = '';
            const booked = Math.max(0, Number(data.booked) || 0);
            const available = Math.max(0, Number(data.available) || 0);
            const total = booked + available;

            if (total <= 0) {
                const seat = document.createElement('div');
                seat.className = 'seat sold';
                seat.textContent = '–';
                seat.title = 'Tiket habis';
                seatMap.appendChild(seat);
                stockInfo.textContent = `Kategori ${category}: tiket tidak tersedia.`;
                ticketCount.min = 0;
                ticketCount.max = 0;
                ticketCount.value = 0;
                ticketCount.disabled = true;
                selectedCount.textContent = '0';
                selectedTotal.textContent = '0';
                return;
            }

            ticketCount.disabled = false;
            const seats = Math.min(32, total);
            let soldSlots = Math.round((booked / total) * seats);
            soldSlots = Math.min(seats, Math.max(0, soldSlots));

            for (let i = 1; i <= seats; i += 1) {
                const seat = document.createElement('div');
                seat.textContent = i;
                if (i <= soldSlots) {
                    seat.className = 'seat sold';
                    seat.title = 'Sudah dibooking';
                } else {
                    seat.className = 'seat empty';
                    seat.title = 'Tersedia — klik untuk memilih';
                    seat.addEventListener('click', () => {
                        const selectedSeats = seatMap.querySelectorAll('.seat.selected');
                        if (seat.classList.contains('selected')) {
                            seat.classList.remove('selected');
                        } else if (selectedSeats.length < available) {
                            seat.classList.add('selected');
                        }
                        updateSummary(category);
                    });
                }
                seatMap.appendChild(seat);
            }
            stockInfo.textContent = available > 0
                ? `Maksimum ${available} tiket tersedia (kategori ${category}). Terjual: ${booked}.`
                : `Kategori ${category} sudah habis terjual (${booked} tiket).`;
            ticketCount.min = available > 0 ? 1 : 0;
            ticketCount.max = Math.max(available, 0);
            ticketCount.disabled = available <= 0;
            if (ticketCount.value > available) {
                ticketCount.value = available > 0 ? available : 0;
            }
            const freeSeats = seatMap.querySelectorAll('.seat.empty');
            const pick = available <= 0 ? 0 : Math.min(
                parseInt(ticketCount.value, 10) || 1,
                available,
                freeSeats.length
            );
            seatMap.querySelectorAll('.seat.selected').forEach(el => el.classList.remove('selected'));
            Array.from(freeSeats).slice(0, pick).forEach(el => el.classList.add('selected'));
            updateSummary(category);
        }

        ticketCount.addEventListener('input', () => {
            const category = ticketCategory.value;
            const data = ticketData[category] || ticketData.Umum;
            if (data.available <= 0 || ticketCount.disabled) {
                return;
            }
            let count = parseInt(ticketCount.value, 10) || 1;
            if (count < 1) count = 1;
            const maxPick = Math.min(data.available, seatMap.querySelectorAll('.seat.empty').length);
            if (count > maxPick) count = maxPick;
            ticketCount.value = count;
            const greens = seatMap.querySelectorAll('.seat.empty');
            seatMap.querySelectorAll('.seat.selected').forEach(seat => seat.classList.remove('selected'));
            Array.from(greens).slice(0, count).forEach(seat => seat.classList.add('selected'));
            updateSummary(category);
        });

        if (ticketCategory && ticketCount && seatMap) {
            renderSeatMap(ticketCategory.value);
            ticketCategory.addEventListener('change', () => renderSeatMap(ticketCategory.value));
        }
    </script>
</body>
</html>
