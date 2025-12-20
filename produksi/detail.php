<?php
include '../config/db.php';
include '../layout/header.php';

$id = isset($_GET['id']) ? $_GET['id'] : '';

$stmtHeader = $pdo->prepare("
    SELECT sp.*, k.nama_karyawan 
    FROM sesi_produksi sp 
    JOIN karyawan k ON sp.karyawan_id = k.karyawan_id 
    WHERE sp.sesi_produksi_id = :id
");
$stmtHeader->execute(['id' => $id]);
$header = $stmtHeader->fetch();

if (!$header) {
    echo "<script>alert('Data sesi produksi tidak ditemukan!'); window.location='index.php';</script>";
    exit;
}

$stmtProduk = $pdo->prepare("
    SELECT dp.*, p.nama_produk 
    FROM detail_produksi dp 
    JOIN produk_jadi p ON dp.produk_jadi_id = p.produk_jadi_id 
    WHERE dp.sesi_produksi_id = :id
");
$stmtProduk->execute(['id' => $id]);
$outputs = $stmtProduk->fetchAll();

$stmtBahan = $pdo->prepare("
    SELECT db.*, b.nama_bahan 
    FROM detail_bahan db 
    JOIN bahan_baku b ON db.bahan_baku_id = b.bahan_baku_id 
    WHERE db.sesi_produksi_id = :id
");
$stmtBahan->execute(['id' => $id]);
$inputs = $stmtBahan->fetchAll();
?>

<div class="row justify-content-center">
    <div class="col-lg-9">
        
        <a href="index.php" class="btn btn-light mb-3 fw-bold text-muted border shadow-sm px-3">
            <i class="bi bi-arrow-left me-2"></i>Kembali
        </a>

        <div class="card shadow-sm border-0" id="printArea">
            <div class="card-header bg-white py-4 border-bottom">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <h5 class="fw-bold text-primary mb-1 text-uppercase">
                            <i class="bi bi-gear-wide-connected me-2"></i>Laporan Produksi
                        </h5>
                        <span class="badge bg-light text-dark border">ID: <?= htmlspecialchars($header['sesi_produksi_id']) ?></span>
                    </div>
                    <div class="text-end">
                        <small class="text-muted d-block text-uppercase fw-bold" style="font-size: 0.7rem;">Tanggal Produksi</small>
                        <span class="fw-bold fs-5"><?= date('d M Y', strtotime($header['tanggal_produksi'])) ?></span>
                    </div>
                </div>
            </div>
            
            <div class="card-body p-4">
                
                <div class="row mb-4">
                    <div class="col-md-6">
                        <table class="table table-sm table-borderless mb-0">
                            <tr>
                                <td class="text-muted small fw-bold text-uppercase" width="140">Penanggung Jawab</td>
                                <td class="fw-bold text-dark">: <?= htmlspecialchars($header['nama_karyawan']) ?></td>
                            </tr>
                            <tr>
                                <td class="text-muted small fw-bold text-uppercase">Total Output</td>
                                <td class="fw-bold text-success">: <?= $header['jumlah_dihasilkan'] ?> Pcs</td>
                            </tr>
                        </table>
                    </div>
                </div>

                <hr class="border-secondary opacity-10 mb-4">

                <div class="mb-4">
                    <h6 class="fw-bold text-success mb-3 small text-uppercase">
                        <i class="bi bi-box-seam me-2"></i>1. Produk Dihasilkan (Stok Bertambah)
                    </h6>
                    <div class="table-responsive">
                        <table class="table table-bordered align-middle">
                            <thead class="bg-success bg-opacity-10 text-success small text-uppercase">
                                <tr>
                                    <th class="text-center" width="5%">No</th>
                                    <th>Nama Produk Jadi</th>
                                    <th class="text-center" width="20%">Qty Hasil</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $no=1; foreach($outputs as $prod): ?>
                                <tr>
                                    <td class="text-center small text-muted"><?= $no++ ?></td>
                                    <td class="fw-bold"><?= htmlspecialchars($prod['nama_produk']) ?></td>
                                    <td class="text-center fw-bold text-success">+<?= $prod['sub_total_hasil_produksi'] ?> Pcs</td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="mb-2">
                    <h6 class="fw-bold text-danger mb-3 small text-uppercase">
                        <i class="bi bi-basket me-2"></i>2. Bahan Baku Digunakan (Stok Berkurang)
                    </h6>
                    <div class="table-responsive">
                        <table class="table table-bordered align-middle">
                            <thead class="bg-danger bg-opacity-10 text-danger small text-uppercase">
                                <tr>
                                    <th class="text-center" width="5%">No</th>
                                    <th>Nama Bahan Baku</th>
                                    <th class="text-center" width="20%">Qty Pakai</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $no=1; foreach($inputs as $bah): ?>
                                <tr>
                                    <td class="text-center small text-muted"><?= $no++ ?></td>
                                    <td class="fw-bold"><?= htmlspecialchars($bah['nama_bahan']) ?></td>
                                    <td class="text-center fw-bold text-danger">-<?= $bah['sub_total_bahan_dipakai'] ?> Unit</td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="mt-4 pt-3 border-top text-center">
                    <small class="text-muted fst-italic">Dokumen ini digenerate otomatis oleh sistem.</small>
                </div>

            </div>
        </div>

        <div class="text-end mt-3 mb-5">
            <button onclick="window.print()" class="btn btn-dark shadow-sm fw-bold px-4">
                <i class="bi bi-printer me-2"></i>Cetak Laporan
            </button>
        </div>

    </div>
</div>

<style>
    @media print {
        body * { visibility: hidden; }
        #printArea, #printArea * { visibility: visible; }
        #printArea { position: absolute; left: 0; top: 0; width: 100%; border: none !important; box-shadow: none !important; }
        .btn, header, footer { display: none !important; }
        .card-header { background-color: white !important; border-bottom: 2px solid #000 !important; }
    }
</style>

<?php include '../layout/footer.php'; ?>