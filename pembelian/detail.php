<?php
include '../config/db.php';
include '../layout/header.php';

$id = isset($_GET['id']) ? $_GET['id'] : '';

// 1. Ambil Data Header Transaksi
$stmtHeader = $pdo->prepare("
    SELECT tp.*, p.nama_pemasok, p.alamat_pemasok, p.no_telepon_pemasok 
    FROM transaksi_pembelian tp 
    JOIN pemasok p ON tp.pemasok_id = p.pemasok_id 
    WHERE tp.transaksi_pembelian_id = :id
");
$stmtHeader->execute(['id' => $id]);
$header = $stmtHeader->fetch();

// Jika data tidak ada, tendang balik
if (!$header) {
    echo "<script>alert('Transaksi tidak ditemukan!'); window.location='index.php';</script>";
    exit;
}

// Ambil Detail Barang
$stmtDetail = $pdo->prepare("
    SELECT dp.*, b.nama_bahan 
    FROM detail_pembelian dp 
    JOIN bahan_baku b ON dp.bahan_baku_id = b.bahan_baku_id 
    WHERE dp.transaksi_pembelian_id = :id
");
$stmtDetail->execute(['id' => $id]);
$details = $stmtDetail->fetchAll();
?>

<div class="row justify-content-center">
    <div class="col-lg-8">
        
        <a href="index.php" class="btn btn-light mb-3 fw-bold text-muted"><i class="bi bi-arrow-left"></i> Kembali</a>

        <div class="card shadow border-0" id="printArea">
            <div class="card-header bg-white py-3 border-bottom-0">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="fw-bold text-primary mb-1">BUKTI PEMBELIAN BARANG</h5>
                        <span class="badge bg-light text-dark border">#<?= htmlspecialchars($header['transaksi_pembelian_id']) ?></span>
                    </div>
                    <div class="text-end">
                        <small class="text-muted d-block">Tanggal Transaksi</small>
                        <span class="fw-bold"><?= date('d F Y', strtotime($header['tanggal_pembelian'])) ?></span>
                    </div>
                </div>
            </div>
            
            <div class="card-body p-4">
                <div class="row mb-4">
                    <div class="col-sm-6">
                        <h6 class="text-muted small fw-bold text-uppercase">Diterima Dari (Pemasok):</h6>
                        <h5 class="fw-bold text-dark"><?= htmlspecialchars($header['nama_pemasok']) ?></h5>
                        <p class="text-muted small mb-0">
                            <?= htmlspecialchars($header['alamat_pemasok'] ?? '-') ?><br>
                            Telp: <?= htmlspecialchars($header['no_telepon_pemasok']) ?>
                        </p>
                    </div>
                </div>

                <div class="table-responsive mb-4">
                    <table class="table table-bordered align-middle">
                        <thead class="bg-light text-center small text-uppercase">
                            <tr>
                                <th>No</th>
                                <th class="text-start">Nama Bahan Baku</th>
                                <th>Qty</th>
                                <th class="text-end">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $no=1; foreach($details as $item): ?>
                            <tr>
                                <td class="text-center" width="5%"><?= $no++ ?></td>
                                <td class="fw-bold text-dark"><?= htmlspecialchars($item['nama_bahan']) ?></td>
                                <td class="text-center"><?= $item['jumlah'] ?> Unit</td>
                                <td class="text-end">Rp <?= number_format($item['sub_total_pembelian'], 0, ',', '.') ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot class="bg-light">
                            <tr>
                                <td colspan="3" class="text-end fw-bold text-uppercase">Total Pembelian</td>
                                <td class="text-end fw-bold text-primary fs-5">
                                    Rp <?= number_format($header['total_harga_beli'], 0, ',', '.') ?>
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <div class="alert alert-info d-flex align-items-center small bg-opacity-10 border-info text-info">
                    <i class="bi bi-info-circle-fill me-2 fs-5"></i>
                    <div>
                        Transaksi ini telah tercatat dan stok bahan baku di gudang telah <strong>bertambah</strong> otomatis.
                    </div>
                </div>

            </div>
        </div>

        <div class="text-end mt-3">
            <button onclick="window.print()" class="btn btn-secondary"><i class="bi bi-printer me-2"></i>Cetak Bukti</button>
        </div>

    </div>
</div>

<style>
    /* Biar pas diprint yang muncul cuma kartunya aja */
    @media print {
        body * { visibility: hidden; }
        #printArea, #printArea * { visibility: visible; }
        #printArea { position: absolute; left: 0; top: 0; width: 100%; }
        .btn { display: none; }
    }
</style>

<?php include '../layout/footer.php'; ?>