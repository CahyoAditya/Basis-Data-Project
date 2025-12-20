<?php
include 'config/db.php';
include 'layout/header.php';

$bulan = isset($_GET['bulan']) ? $_GET['bulan'] : date('m');
$tahun = isset($_GET['tahun']) ? $_GET['tahun'] : date('Y');

$stmtJual = $pdo->prepare("SELECT SUM(total_harga_jual) FROM transaksi_penjualan WHERE EXTRACT(MONTH FROM tanggal_penjualan) = :m AND EXTRACT(YEAR FROM tanggal_penjualan) = :y");
$stmtJual->execute(['m' => $bulan, 'y' => $tahun]);
$pemasukan = $stmtJual->fetchColumn() ?: 0;

$stmtBeli = $pdo->prepare("SELECT SUM(total_harga_beli) FROM transaksi_pembelian WHERE EXTRACT(MONTH FROM tanggal_pembelian) = :m AND EXTRACT(YEAR FROM tanggal_pembelian) = :y");
$stmtBeli->execute(['m' => $bulan, 'y' => $tahun]);
$pengeluaran = $stmtBeli->fetchColumn() ?: 0;

$labaBersih = $pemasukan - $pengeluaran;

$stmtAlertBahan = $pdo->query("SELECT * FROM bahan_baku WHERE stok_bahan <= 20 ORDER BY stok_bahan ASC LIMIT 5");
$lowBahan = $stmtAlertBahan->fetchAll();

$stmtAlertProduk = $pdo->query("SELECT * FROM produk_jadi WHERE stok_tersedia <= 20 ORDER BY stok_tersedia ASC LIMIT 5");
$lowProduk = $stmtAlertProduk->fetchAll();

$stmtTopProd = $pdo->query("
    SELECT p.nama_produk, SUM(dp.jumlah) as total_terjual
    FROM detail_penjualan dp
    JOIN produk_jadi p ON dp.produk_jadi_id = p.produk_jadi_id
    GROUP BY p.nama_produk
    ORDER BY total_terjual DESC
    LIMIT 5
");
$topProduk = $stmtTopProd->fetchAll();

$stmtTopCust = $pdo->query("
    SELECT 
        COALESCE(per.nama_perorangan, b.nama_bisnis) AS nama_pelanggan,
        SUM(tp.total_harga_jual) as total_belanja
    FROM transaksi_penjualan tp
    JOIN pelanggan p ON tp.pelanggan_id = p.pelanggan_id
    LEFT JOIN perorangan per ON p.pelanggan_id = per.pelanggan_id
    LEFT JOIN bisnis b ON p.pelanggan_id = b.pelanggan_id
    GROUP BY nama_pelanggan
    ORDER BY total_belanja DESC
    LIMIT 5
");
$topPelanggan = $stmtTopCust->fetchAll();
?>

<div class="row align-items-center mb-4">
    <div class="col-md-7">
        <h3 class="fw-bold text-dark mb-0"><i class="bi bi-speedometer2 me-2"></i>Dashboard Owner</h3>
        <p class="text-muted mb-0">Ringkasan performa dan peringatan stok.</p>
    </div>
    <div class="col-md-5">
        <div class="card bg-white border shadow-sm">
            <div class="card-body py-2 px-3 d-flex align-items-center justify-content-between">
                <span class="small fw-bold text-muted me-2 text-uppercase"><i class="bi bi-calendar3 me-1"></i> Filter:</span>
                <form method="GET" class="d-flex gap-2 m-0 flex-grow-1">
                    <select name="bulan" class="form-select form-select-sm border-secondary fw-bold text-dark" onchange="this.form.submit()">
                        <?php for($i=1; $i<=12; $i++): ?>
                            <option value="<?= $i ?>" <?= ($i == $bulan) ? 'selected' : '' ?>><?= date('F', mktime(0, 0, 0, $i, 10)) ?></option>
                        <?php endfor; ?>
                    </select>
                    <select name="tahun" class="form-select form-select-sm border-secondary fw-bold text-dark" onchange="this.form.submit()">
                        <?php for($i=2024; $i<=date('Y'); $i++): ?>
                            <option value="<?= $i ?>" <?= ($i == $tahun) ? 'selected' : '' ?>><?= $i ?></option>
                        <?php endfor; ?>
                    </select>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="row mb-4 g-3">
    <div class="col-md-4">
        <div class="card shadow-sm border-0 border-start border-4 border-success h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h6 class="text-uppercase text-muted fw-bold small mb-0">Pemasukan</h6>
                    <span class="badge bg-success bg-opacity-10 text-success"><i class="bi bi-arrow-up"></i> Sales</span>
                </div>
                <h3 class="fw-bold text-dark mb-0">Rp <?= number_format($pemasukan, 0, ',', '.') ?></h3>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card shadow-sm border-0 border-start border-4 border-danger h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h6 class="text-uppercase text-muted fw-bold small mb-0">Pengeluaran</h6>
                    <span class="badge bg-danger bg-opacity-10 text-danger"><i class="bi bi-arrow-down"></i> Buy</span>
                </div>
                <h3 class="fw-bold text-dark mb-0">Rp <?= number_format($pengeluaran, 0, ',', '.') ?></h3>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card shadow-sm border-0 border-start border-4 border-primary bg-primary text-white h-100" style="background: linear-gradient(135deg, #0d6efd 0%, #0a58ca 100%);">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h6 class="text-uppercase text-white-50 fw-bold small mb-0">Laba Bersih</h6>
                    <i class="bi bi-wallet2 fs-5 text-white-50"></i>
                </div>
                <h3 class="fw-bold text-white mb-0">Rp <?= number_format($labaBersih, 0, ',', '.') ?></h3>
            </div>
        </div>
    </div>
</div>

<div class="row mb-4">
    <div class="col-lg-6 mb-4 mb-lg-0">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
                <h6 class="mb-0 fw-bold text-danger"><i class="bi bi-exclamation-triangle-fill me-2"></i>Stok Bahan Menipis</h6>
                <a href="pembelian/create.php" class="btn btn-sm btn-outline-danger">Beli Bahan</a>
            </div>
            <div class="card-body p-0">
                <table class="table table-hover mb-0 align-middle">
                    <thead class="bg-light small">
                        <tr>
                            <th class="ps-3">Nama Bahan</th>
                            <th class="text-center">Sisa</th>
                            <th class="text-end pe-3">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(count($lowBahan) > 0): ?>
                            <?php foreach($lowBahan as $b): 
                                // LOGIKA WARNA
                                $stok = $b['stok_bahan'];
                                if ($stok <= 10) {
                                    $badgeColor = 'bg-danger'; 
                                    $statusText = 'KRITIS';
                                } else {
                                    $badgeColor = 'bg-warning text-dark'; 
                                    $statusText = 'MENIPIS';
                                }
                            ?>
                            <tr>
                                <td class="ps-3 fw-bold"><?= htmlspecialchars($b['nama_bahan']) ?></td>
                                <td class="text-center">
                                    <span class="badge <?= $badgeColor ?>"><?= $stok ?> <?= htmlspecialchars($b['satuan'] ?? '') ?></span>
                                </td>
                                <td class="text-end pe-3">
                                    <span class="badge rounded-pill <?= $badgeColor ?>"><?= $statusText ?></span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="3" class="text-center py-4 text-secondary small">Aman! Tidak ada stok bahan di bawah 20.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
                <h6 class="mb-0 fw-bold text-warning text-dark"><i class="bi bi-box-seam-fill me-2"></i>Stok Produk Rendah</h6>
                <a href="produksi/create.php" class="btn btn-sm btn-outline-warning text-dark">Produksi</a>
            </div>
            <div class="card-body p-0">
                <table class="table table-hover mb-0 align-middle">
                    <thead class="bg-light small">
                        <tr>
                            <th class="ps-3">Nama Produk</th>
                            <th class="text-center">Sisa</th>
                            <th class="text-end pe-3">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(count($lowProduk) > 0): ?>
                            <?php foreach($lowProduk as $p): 
                                // LOGIKA WARNA
                                $stok = $p['stok_tersedia'];
                                if ($stok <= 10) {
                                    $badgeColor = 'bg-danger'; 
                                    $statusText = 'KRITIS';
                                } else {
                                    $badgeColor = 'bg-warning text-dark'; 
                                    $statusText = 'MENIPIS';
                                }
                            ?>
                            <tr>
                                <td class="ps-3 fw-bold text-dark"><?= htmlspecialchars($p['nama_produk']) ?></td>
                                <td class="text-center">
                                    <span class="badge <?= $badgeColor ?> border border-light"><?= $stok ?> pcs</span>
                                </td>
                                <td class="text-end pe-3">
                                    <span class="badge rounded-pill <?= $badgeColor ?>"><?= $statusText ?></span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="3" class="text-center py-4 text-secondary small">Aman! Stok produk di atas 20 semua.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-6 mb-4">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-body">
                <h6 class="fw-bold text-dark mb-3"><i class="bi bi-star-fill text-warning me-2"></i>Produk Terlaris</h6>
                <?php if(count($topProduk) > 0): ?>
                    <ul class="list-group list-group-flush">
                        <?php foreach($topProduk as $idx => $tp): ?>
                        <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                            <span><span class="fw-bold me-2 text-muted">#<?= $idx+1 ?></span> <?= htmlspecialchars($tp['nama_produk']) ?></span>
                            <span class="badge bg-success bg-opacity-10 text-success border border-success"><?= $tp['total_terjual'] ?> Terjual</span>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <p class="text-muted small text-center py-3">Belum ada data penjualan.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-md-6 mb-4">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-body">
                <h6 class="fw-bold text-dark mb-3"><i class="bi bi-person-heart text-danger me-2"></i>Pelanggan Sultan</h6>
                <?php if(count($topPelanggan) > 0): ?>
                    <ul class="list-group list-group-flush">
                        <?php foreach($topPelanggan as $idx => $tc): ?>
                        <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                            <div class="d-flex align-items-center">
                                <i class="bi bi-person-circle fs-4 me-2 text-secondary"></i>
                                <span class="fw-bold text-dark small"><?= htmlspecialchars($tc['nama_pelanggan']) ?></span>
                            </div>
                            <span class="text-primary fw-bold small">Rp <?= number_format($tc['total_belanja'],0,',','.') ?></span>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <p class="text-muted small text-center py-3">Belum ada pelanggan.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include 'layout/footer.php'; ?>