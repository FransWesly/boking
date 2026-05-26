<?php
require_once __DIR__ . '/../termasuk/koneksi.php';
require_once __DIR__ . '/../termasuk/otentikasi.php';

/**
 * Pesan bahasa Indonesia untuk kode error unggah PHP.
 */
function pesan_error_unggah_banner(int $kode): string
{
    switch ($kode) {
        case UPLOAD_ERR_INI_SIZE:
            return 'Ukuran file melebihi batas upload_max_filesize di server. Perkecil gambar atau naikkan batas di php.ini.';
        case UPLOAD_ERR_FORM_SIZE:
            return 'Ukuran file melebihi batas form (MAX_FILE_SIZE). Perkecil gambar lalu coba lagi.';
        case UPLOAD_ERR_PARTIAL:
            return 'File hanya terunggah sebagian. Coba unggah lagi.';
        case UPLOAD_ERR_NO_TMP_DIR:
            return 'Folder sementara PHP tidak ada. Periksa konfigurasi upload_tmp_dir / hubungi administrator.';
        case UPLOAD_ERR_CANT_WRITE:
            return 'Server gagal menulis file ke disk (penyimpanan penuh atau gagal tulis). Hubungi administrator.';
        case UPLOAD_ERR_EXTENSION:
            return 'Unggah dihentikan oleh ekstensi PHP di server.';
        default:
            return 'Terjadi kesalahan saat mengunggah (kode ' . $kode . ').';
    }
}

$message = '';
$formSuccess = false;
$matches = [];

if (isset($_GET['saved']) && $_GET['saved'] === '1') {
    $message = 'Event baru berhasil ditambahkan.';
    $formSuccess = true;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $event_name = trim($_POST['event_name'] ?? '');
    $stadium = trim($_POST['stadium'] ?? '');
    $match_date = trim($_POST['match_date'] ?? '');
    $match_time = trim($_POST['match_time'] ?? '');
    $price_general = floatval($_POST['price_general'] ?? 0);
    $price_vip = floatval($_POST['price_vip'] ?? 0);
    $price_vvip = floatval($_POST['price_vvip'] ?? 0);
    $available_general = intval($_POST['available_general'] ?? 0);
    $available_vip = intval($_POST['available_vip'] ?? 0);
    $available_vvip = intval($_POST['available_vvip'] ?? 0);
    $banner_url = '';

    if ($event_name === '' || $stadium === '' || $match_date === '' || $match_time === '' || $price_general <= 0 || $price_vip <= 0 || $price_vvip <= 0 || $available_general < 0 || $available_vip < 0 || $available_vvip < 0) {
        $message = 'Semua kolom harus diisi dengan benar.';
    } else {
        if (!empty($_FILES['banner_image']) && isset($_FILES['banner_image']['error'])) {
            $fileErr = (int) $_FILES['banner_image']['error'];

            if ($fileErr === UPLOAD_ERR_OK) {
                $uploadDir = __DIR__ . '/../unggahan';
                if (!is_dir($uploadDir)) {
                    if (!@mkdir($uploadDir, 0775, true)) {
                        $message = 'Gagal membuat folder unggahan. Pastikan folder proyek bisa ditulis atau buat folder "unggahan" secara manual dengan izin tulis.';
                    }
                }
                if ($message === '' && !is_writable($uploadDir)) {
                    $message = 'Folder unggahan tidak bisa ditulis. Beri izin tulis pada folder unggahan/ (mis. chmod 775 atau 777 di Linux).';
                }
                if ($message === '') {
                    $fileInfo = pathinfo($_FILES['banner_image']['name']);
                    $extension = strtolower($fileInfo['extension'] ?? '');
                    $allowed = ['jpg', 'jpeg', 'png', 'webp'];
                    if (!in_array($extension, $allowed, true)) {
                        $message = 'Banner harus berupa file JPG, PNG, atau WEBP (ekstensi .jpg, .jpeg, .png, .webp).';
                    } else {
                        $fileName = uniqid('banner_', true) . '.' . $extension;
                        $filePath = $uploadDir . '/' . $fileName;
                        $tmp = $_FILES['banner_image']['tmp_name'];
                        if (is_uploaded_file($tmp) && move_uploaded_file($tmp, $filePath)) {
                            $banner_url = 'unggahan/' . $fileName;
                        } else {
                            $message = 'Gagal menyimpan file banner ke server. Periksa izin folder unggahan/ dan pastikan ada ruang disk.';
                        }
                    }
                }
            } elseif ($fileErr !== UPLOAD_ERR_NO_FILE) {
                $message = pesan_error_unggah_banner($fileErr);
            }
        }

        if ($message === '') {
            $total_seats = $available_general + $available_vip + $available_vvip;
            $stmt = $pdo->prepare('INSERT INTO matches (event_name, banner_url, price_general, price_vip, price_vvip, available_general, available_vip, available_vvip, team_home, team_away, stadium, match_date, match_time, price, available_seats) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
            $stmt->execute([$event_name, $banner_url, $price_general, $price_vip, $price_vvip, $available_general, $available_vip, $available_vvip, $event_name, '', $stadium, $match_date, $match_time, $price_general, $total_seats]);
            header('Location: event.php?saved=1');
            exit;
        }
    }
}

if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $deleteId = intval($_GET['id']);
    $stmt = $pdo->prepare('DELETE FROM matches WHERE id = ?');
    $stmt->execute([$deleteId]);
    header('Location: event.php');
    exit;
}

try {
    $matches = $pdo->query('SELECT * FROM matches ORDER BY match_date, match_time DESC')->fetchAll();
} catch (Exception $e) {
    $matches = [];
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Event - StadionBola</title>
    <link rel="stylesheet" href="../gaya/gaya.css">
</head>
<body class="admin-layout">
    <aside class="admin-sidebar">
        <div class="sidebar-header">
            <a class="brand" href="dasbor.php">Admin StadionBola</a>
        </div>
        <nav class="sidebar-nav">
            <a href="dasbor.php" class="nav-item">Dashboard</a>
            <a href="event.php" class="nav-item active">Event</a>
            <a href="data_pemesanan.php" class="nav-item">Booking</a>
            <a href="keluar.php" class="nav-item logout">Keluar</a>
        </nav>
    </aside>

    <main class="admin-main">
        <header class="admin-header">
            <h1 class="admin-page-title">Kelola event</h1>
        </header>

        <div class="admin-content">
            <?php if ($message !== ''): ?>
                <div class="alert <?php echo $formSuccess ? 'alert-success' : 'alert-error'; ?>"><?php echo htmlspecialchars($message); ?></div>
            <?php endif; ?>

            <div class="card glass" style="margin-bottom: 2rem;">
                <div class="admin-card-head">
                    <div>
                        <h2>Tambah event baru</h2>
                        <p>Isi data event dan tiket. Banner bersifat opsional; tanpa gambar, event tetap bisa ditampilkan di beranda.</p>
                    </div>
                </div>

                <form method="post" enctype="multipart/form-data" class="admin-event-form" id="formTambahEvent">
                    <div class="admin-event-form-layout">
                        <div class="admin-event-form-main">
                            <fieldset class="form-section-admin">
                                <legend>Detail event</legend>
                                <div class="form-grid-admin cols-1">
                                    <label>
                                        Nama event
                                        <input type="text" name="event_name" required placeholder="Contoh: Final Piala Indonesia 2026" autocomplete="off">
                                    </label>
                                </div>
                                <div class="form-grid-admin cols-3">
                                    <label>
                                        Stadion / lokasi
                                        <input type="text" name="stadium" required placeholder="Gelora Bung Karno" autocomplete="off">
                                    </label>
                                    <label>
                                        Tanggal
                                        <input type="date" name="match_date" required>
                                    </label>
                                    <label>
                                        Waktu mulai
                                        <input type="time" name="match_time" required>
                                    </label>
                                </div>
                            </fieldset>

                            <fieldset class="form-section-admin">
                                <legend>Harga &amp; kuota tiket</legend>
                                <p class="field-hint" style="margin: 0 0 1rem;">Atur harga dan jumlah tiket per zona. Total kapasitas = penjumlahan kuota di bawah.</p>

                                <p class="form-inline-heading">Harga per tiket (Rp)</p>
                                <div class="form-grid-admin cols-3">
                                    <label>
                                        Umum
                                        <input type="number" name="price_general" min="1" step="1000" required placeholder="75000" inputmode="numeric">
                                    </label>
                                    <label>
                                        VIP
                                        <input type="number" name="price_vip" min="1" step="1000" required placeholder="150000" inputmode="numeric">
                                    </label>
                                    <label>
                                        VVIP
                                        <input type="number" name="price_vvip" min="1" step="1000" required placeholder="350000" inputmode="numeric">
                                    </label>
                                </div>

                                <p class="form-inline-heading">Kuota (jumlah tiket)</p>
                                <div class="form-grid-admin cols-3">
                                    <label>
                                        Zona umum
                                        <input type="number" name="available_general" min="0" required placeholder="500" inputmode="numeric">
                                    </label>
                                    <label>
                                        Zona VIP
                                        <input type="number" name="available_vip" min="0" required placeholder="120" inputmode="numeric">
                                    </label>
                                    <label>
                                        Zona VVIP
                                        <input type="number" name="available_vvip" min="0" required placeholder="40" inputmode="numeric">
                                    </label>
                                </div>
                            </fieldset>
                        </div>

                        <aside class="admin-event-form-aside">
                            <fieldset class="form-section-admin form-section-banner">
                                <legend>Foto banner event</legend>
                                <div class="admin-banner-block">
                                    <div class="admin-banner-preview" id="bannerPreview" aria-live="polite">
                                        <span class="admin-banner-placeholder" id="bannerPlaceholder">Belum ada gambar dipilih.<br>Gunakan tombol di bawah untuk mengunggah.</span>
                                        <img id="bannerPreviewImg" alt="Pratinjau banner" hidden>
                                    </div>
                                    <input type="file" name="banner_image" id="inputBannerImage" class="sr-only" accept="image/png,image/jpeg,image/webp" aria-describedby="bannerHint">
                                    <label for="inputBannerImage" class="btn btn-secondary admin-banner-choose">Pilih gambar dari perangkat</label>
                                    <p class="admin-banner-filename" id="bannerFilename" aria-label="Nama file terpilih"></p>
                                    <p class="field-hint" id="bannerHint">
                                        Format JPG, PNG, atau WEBP; disarankan gambar mendatar.
                                        <span class="banner-hint-strong">Unggah foto bersifat opsional.</span>
                                        Anda bisa menambahkan gambar sekarang sebelum menyimpan, atau mengosongkan dulu — kolom ini tetap ada setiap kali Anda menambah event baru, sehingga foto bisa Anda pasang kapan saja sebelum klik simpan.
                                    </p>
                                </div>
                            </fieldset>
                        </aside>
                    </div>

                    <div class="form-actions-admin">
                        <button class="btn btn-primary" type="submit">Simpan event</button>
                    </div>
                </form>
            </div>

            <div class="card glass">
                <div class="admin-card-head">
                    <div>
                        <h2>Daftar event</h2>
                        <p>Ringkasan semua pertandingan yang aktif di sistem pemesanan.</p>
                    </div>
                </div>
                <div class="admin-table-wrap">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th class="col-thumb">Banner</th>
                                <th>Event</th>
                                <th>Jadwal</th>
                                <th>Harga</th>
                                <th>Stok</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($matches as $match): ?>
                                <tr>
                                    <td class="col-thumb">
                                        <?php if (!empty($match['banner_url'])): ?>
                                            <img class="admin-event-thumb" src="../<?php echo htmlspecialchars($match['banner_url']); ?>" alt="" loading="lazy" width="72" height="48">
                                        <?php else: ?>
                                            <span class="admin-event-thumb-placeholder" title="Belum ada foto">Tanpa<br>foto</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php echo htmlspecialchars($match['event_name'] ?: $match['team_home']); ?>
                                        <div class="admin-cell-muted"><?php echo htmlspecialchars($match['stadium']); ?></div>
                                    </td>
                                    <td><?php echo date('d M Y', strtotime($match['match_date'])); ?> • <?php echo date('H:i', strtotime($match['match_time'])); ?></td>
                                    <td>
                                        <div class="admin-price-stack">
                                            <span>Umum Rp <?php echo number_format($match['price_general'], 0, ',', '.'); ?></span>
                                            <span>VIP Rp <?php echo number_format($match['price_vip'], 0, ',', '.'); ?></span>
                                            <span>VVIP Rp <?php echo number_format($match['price_vvip'], 0, ',', '.'); ?></span>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="admin-stock-pills">
                                            <span class="stock-pill">Umum <?php echo intval($match['available_general']); ?></span>
                                            <span class="stock-pill">VIP <?php echo intval($match['available_vip']); ?></span>
                                            <span class="stock-pill">VVIP <?php echo intval($match['available_vvip']); ?></span>
                                        </div>
                                    </td>
                                    <td><a class="btn btn-link" href="event.php?action=delete&id=<?php echo intval($match['id']); ?>" onclick="return confirm('Hapus event ini?');">Hapus</a></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>
    <script>
        (function () {
            var input = document.getElementById('inputBannerImage');
            var preview = document.getElementById('bannerPreview');
            var img = document.getElementById('bannerPreviewImg');
            var placeholder = document.getElementById('bannerPlaceholder');
            var filenameEl = document.getElementById('bannerFilename');
            if (!input || !preview || !img || !placeholder) return;

            function clearPreview() {
                img.removeAttribute('src');
                img.setAttribute('hidden', '');
                placeholder.removeAttribute('hidden');
                preview.classList.remove('has-image');
                if (filenameEl) filenameEl.textContent = '';
            }

            input.addEventListener('change', function () {
                var f = input.files && input.files[0];
                if (!f) {
                    clearPreview();
                    return;
                }
                if (filenameEl) filenameEl.textContent = f.name;
                var reader = new FileReader();
                reader.onload = function () {
                    img.src = reader.result;
                    img.removeAttribute('hidden');
                    placeholder.setAttribute('hidden', '');
                    preview.classList.add('has-image');
                };
                reader.readAsDataURL(f);
            });
        })();
    </script>
</body>
</html>
