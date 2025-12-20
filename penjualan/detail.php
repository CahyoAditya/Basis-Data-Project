<?php
include '../config/db.php';
include '../layout/header.php';

$id = isset($_GET['id']) ? $_GET['id'] : '';

// 1. Ambil Header
$sqlHeader = "SELECT tp.*, 
                     COALESCE(per.nama_perorangan, b.nama_bisnis) AS nama_pelanggan,
                     p.alamat_pelanggan, p.no_telepon_pelanggan
              FROM transaksi_penjualan tp
              JOIN pelanggan p ON tp.pelanggan_id = p.pelanggan_id
              LEFT JOIN perorangan per ON p.pelanggan_id = per.pelanggan_id
              LEFT JOIN bisnis b ON p.pelanggan_id = b.pelanggan_id
              WHERE tp.transaksi_penjualan_id = :id";
$stmtHead = $pdo->prepare($sqlHeader);
$stmtHead->execute(['id' => $id]);
$header = $stmtHead->fetch();

if (!$header) {
    echo "<script>alert('Transaksi tidak ditemukan!'); window.location='index.php';</script>";
    exit;
}

// 2. Ambil Detail
$sqlDetail = "SELECT dp.*, pj.nama_produk, pj.harga_jual 
              FROM detail_penjualan dp
              JOIN produk_jadi pj ON dp.produk_jadi_id = pj.produk_jadi_id
              WHERE dp.transaksi_penjualan_id = :id";
$stmtDet = $pdo->prepare($sqlDetail);
$stmtDet->execute(['id' => $id]);
$details = $stmtDet->fetchAll();
?>

<div class="row justify-content-center">
    <div class="col-lg-8">
        
        <a href="index.php" class="btn btn-light mb-3 fw-bold text-muted border px-3">
            <i class="bi bi-arrow-left me-2"></i>Kembali
        </a>

        <div class="card shadow-sm border-0" id="printArea">
            <div class="card-header bg-white py-4 border-bottom-0">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="fw-bold text-primary mb-1 text-uppercase">BUKTI PENJUALAN BARANG</h5>
                        <span class="badge bg-light text-dark border">#<?= htmlspecialchars($header['transaksi_penjualan_id']) ?></span>
                    </div>
                    <div class="text-end">
                        <small class="text-muted d-block text-uppercase fw-bold" style="font-size: 0.7rem;">Tanggal Transaksi</small>
                        <span class="fw-bold fs-5"><?= date('d F Y', strtotime($header['tanggal_penjualan'])) ?></span>
                    </div>
                </div>
            </div>
            
            <div class="card-body p-4">
                
                <div class="row mb-4">
                    <div class="col-sm-6">
                        <h6 class="text-muted small fw-bold text-uppercase">Kepada Yth (Pelanggan):</h6>
                        <h5 class="fw-bold text-dark mb-1"><?= htmlspecialchars($header['nama_pelanggan']) ?></h5>
                        <p class="text-muted small mb-0">
                            <?= htmlspecialchars($header['alamat_pelanggan']) ?><br>
                            Telp: <?= htmlspecialchars($header['no_telepon_pelanggan']) ?>
                        </p>
                    </div>
                </div>

                <div class="table-responsive mb-4">
                    <table class="table table-bordered align-middle">
                        <thead class="bg-light text-center small text-uppercase text-muted">
                            <tr>
                                <th width="5%">No</th>
                                <th class="text-start">Nama Produk</th>
                                <th width="15%">Qty</th>
                                <th class="text-end" width="25%">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $no=1; foreach($details as $item): ?>
                            <tr>
                                <td class="text-center small text-muted"><?= $no++ ?></td>
                                <td class="fw-bold"><?= htmlspecialchars($item['nama_produk']) ?></td>
                                <td class="text-center"><?= $item['jumlah'] ?> Pcs</td>
                                <td class="text-end fw-bold">Rp <?= number_format($item['sub_total_penjualan'], 0, ',', '.') ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot class="bg-light">
                            <tr>
                                <td colspan="3" class="text-end fw-bold text-uppercase py-3">Total Penjualan</td>
                                <td class="text-end fw-bold text-primary fs-5 py-3 pe-3">
                                    Rp <?= number_format($header['total_harga_jual'], 0, ',', '.') ?>
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <div class="alert alert-success d-flex align-items-center small bg-opacity-10 border-success text-success">
                    <i class="bi bi-check-circle-fill me-2 fs-5"></i>
                    <div>
                        Transaksi ini telah tercatat dan stok produk di gudang telah <strong>berkurang</strong> otomatis.
                    </div>
                </div>

                <div class="mt-4 pt-3 border-top text-center">
                    <small class="text-muted fst-italic">Terima kasih telah berbelanja di Usaha Rumahan.</small>
                </div>

            </div>
        </div>

        <div class="text-end mt-3 mb-5">
            <button onclick="window.print()" class="btn btn-secondary shadow-sm fw-bold px-4">
                <i class="bi bi-printer me-2"></i>Cetak Bukti
            </button>
        </div>

    </div>
</div>

<style>
    @media print {
        body * { visibility: hidden; }
        #printArea, #printArea * { visibility: visible; }
        #printArea { position: absolute; left: 0; top: 0; width: 100%; border: none !important; box-shadow: none !important; }
        .btn { display: none !important; }
        .card-header { background-color: white !important; border-bottom: 2px solid #000 !important; }
    }
</style>

<?php include '../layout/footer.php'; ?>