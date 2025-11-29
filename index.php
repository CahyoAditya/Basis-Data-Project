<?php
include 'config/db.php';
include 'layout/header.php'; 

// 1. Ambil data bahan baku (diurutin dari stok paling dikit biar ketahuan mana yang habis)
$stmtBahan = $pdo->query("SELECT * FROM bahan_baku ORDER BY stok_bahan ASC");
$bahanBaku = $stmtBahan->fetchAll();

// 2. Ambil data produk jadi (diurutin dari stok terbanyak)
$stmtProduk = $pdo->query("SELECT * FROM produk_jadi ORDER BY stok_tersedia DESC");
$produkJadi = $stmtProduk->fetchAll();

// 3. Tentuin batas stok kritis buat indikator warna nanti
$lowStockLimit = 10; 
?>

<div class="mb-4">
    <h3 class="fw-bold text-dark mb-1">Dashboard Utama</h3>
    <p class="text-muted">Selamat datang di panel kontrol usaha rumahan Anda.</p>
</div>

<div class="row">
    
    <div class="col-lg-6 mb-4">
        <div class="card shadow-sm h-100 border-0">
            <div class="card-header bg-white py-3 border-0 d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center">
                    <div class="bg-primary rounded-pill me-2" style="width: 4px; height: 20px;"></div>
                    <h6 class="mb-0 fw-bold text-dark">Stok Bahan Baku</h6>
                </div>
                <a href="bahan_baku/index.php" class="btn btn-sm btn-outline-primary rounded-pill px-3">Kelola Data</a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover table-striped mb-0 align-middle">
                        <thead class="bg-light text-uppercase small text-muted">
                            <tr>
                                <th class="ps-4 py-3">Nama Bahan</th>
                                <th class="text-center">Stok Gudang</th>
                                <th class="text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($bahanBaku as $bb): ?>
                                <tr>
                                    <td class="ps-4 fw-medium small"><?= htmlspecialchars($bb['nama_bahan']) ?></td>
                                    <td class="text-center fw-bold text-dark"><?= htmlspecialchars($bb['stok_bahan']) ?></td>
                                    <td class="text-center">
                                        <?php if ($bb['stok_bahan'] <= $lowStockLimit): ?>
                                            <span class="badge bg-danger text-white rounded-pill px-2 small">Kritis</span>
                                        <?php elseif ($bb['stok_bahan'] <= 20): ?>
                                            <span class="badge bg-warning text-dark rounded-pill px-2 small">Menipis</span>
                                        <?php else: ?>
                                            <span class="badge bg-success bg-opacity-10 text-success rounded-pill px-2 small">Aman</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-lg-6 mb-4">
        <div class="card shadow-sm h-100 border-0">
            <div class="card-header bg-white py-3 border-0 d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center">
                    <div class="bg-success rounded-pill me-2" style="width: 4px; height: 20px;"></div>
                    <h6 class="mb-0 fw-bold text-dark">Stok Produk Jadi</h6>
                </div>
                <a href="produksi/index.php" class="btn btn-sm btn-outline-success rounded-pill px-3">Riwayat</a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0 align-middle">
                        <thead class="bg-light text-uppercase small text-muted">
                            <tr>
                                <th class="ps-4 py-3">Nama Produk</th>
                                <th class="text-end pe-4">Tersedia (Pcs)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($produkJadi as $pj): ?>
                                <tr>
                                    <td class="ps-4">
                                        <div class="d-flex align-items-center">
                                            <i class="bi bi-box-seam text-success me-2 small"></i>
                                            <span class="fw-medium small"><?= htmlspecialchars($pj['nama_produk']) ?></span>
                                        </div>
                                    </td>
                                    <td class="text-end pe-4">
                                        <span class="fw-bold text-dark"><?= htmlspecialchars($pj['stok_tersedia']) ?></span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row mb-4">
    
    <div class="col-md-6 mb-3">
        <div class="card shadow-sm border-0 bg-primary bg-opacity-10 h-100">
            <div class="card-body p-4">
                <div class="bg-white p-3 icon-circle shadow-sm mb-3 text-primary" style="width: 60px; height: 60px;">
                    <i class="bi bi-cart-plus-fill fs-2"></i>
                </div>
                <h4 class="fw-bold text-primary mb-2">Restock Bahan</h4>
                <p class="text-muted mb-4 small">
                    Stok gudang menipis? Catat transaksi pembelian baru dari pemasok di sini untuk menambah jumlah stok.
                </p>
                <div class="d-grid">
                    <a href="pembelian/create.php" class="btn btn-primary fw-semibold shadow-sm py-2">
                        <i class="bi bi-plus-lg me-2"></i>Catat Pembelian
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-6 mb-3">
        <div class="card shadow-sm border-0 bg-success bg-opacity-10 h-100">
            <div class="card-body p-4">
                <div class="bg-white p-3 icon-circle shadow-sm mb-3 text-success" style="width: 60px; height: 60px;">
                    <i class="bi bi-gear-wide-connected fs-2"></i>
                </div>
                <h4 class="fw-bold text-success mb-2">Mulai Produksi</h4>
                <p class="text-muted mb-4 small">
                    Olah bahan baku menjadi produk jadi. Stok bahan akan berkurang dan stok produk jadi akan bertambah otomatis.
                </p>
                <div class="d-grid">
                    <a href="produksi/create.php" class="btn btn-success fw-semibold shadow-sm py-2">
                        <i class="bi bi-play-fill me-2"></i>Catat Produksi
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'layout/footer.php'; ?>