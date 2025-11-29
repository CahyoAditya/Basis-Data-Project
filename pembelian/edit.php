<?php
include '../config/db.php';
include '../layout/header.php';

// Cek dulu, ada ID Transaksi yang dikirim gak di URL?
if (!isset($_GET['id'])) {
    echo "<script>alert('ID Transaksi tidak ditemukan!'); window.location='index.php';</script>";
    exit;
}
$id = $_GET['id'];

// Ambil data transaksi lama sebelum diedit
// Kita butuh data lama buat balikin stok (kalau jumlah beli berubah)
$sql = "SELECT tp.*, dp.bahan_baku_id, dp.jumlah, dp.sub_total_pembelian 
        FROM transaksi_pembelian tp
        JOIN detail_pembelian dp ON tp.transaksi_pembelian_id = dp.transaksi_pembelian_id
        WHERE tp.transaksi_pembelian_id = :id";
$stmt = $pdo->prepare($sql);
$stmt->execute(['id' => $id]);
$oldData = $stmt->fetch();

// Kalau datanya gak ketemu, tendang balik ke index
if (!$oldData) {
    echo "<script>alert('Data tidak ditemukan!'); window.location='index.php';</script>";
    exit;
}

// Ambil data Pemasok & Bahan Baku buat isi dropdown
$pemasok = $pdo->query("SELECT * FROM pemasok ORDER BY nama_pemasok ASC")->fetchAll();
$bahan = $pdo->query("SELECT * FROM bahan_baku ORDER BY nama_bahan ASC")->fetchAll();

// Buat updatenya
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        // Mulai transaksi database
        $pdo->beginTransaction();

        // 1. Balikin stok lama
        // Kurangi stok di gudang sesuai jumlah yang DULU dibeli
        $stmtRevert = $pdo->prepare("UPDATE bahan_baku SET stok_bahan = stok_bahan - :old_qty WHERE bahan_baku_id = :old_bid");
        $stmtRevert->execute([
            'old_qty' => $oldData['jumlah'],
            'old_bid' => $oldData['bahan_baku_id']
        ]);

        // 2. Update transaksi pembelian
        $stmtUpdateH = $pdo->prepare("UPDATE transaksi_pembelian SET pemasok_id = :pid, tanggal_pembelian = :tgl, total_harga_beli = :tot WHERE transaksi_pembelian_id = :tid");
        $stmtUpdateH->execute([
            'pid' => $_POST['pemasok_id'],
            'tgl' => $_POST['tanggal'],
            'tot' => $_POST['total_harga'],
            'tid' => $id
        ]);

        // 3. Update detail pembelian
        $stmtUpdateD = $pdo->prepare("UPDATE detail_pembelian SET bahan_baku_id = :bid, jumlah = :qty, sub_total_pembelian = :sub WHERE transaksi_pembelian_id = :tid");
        $stmtUpdateD->execute([
            'bid' => $_POST['bahan_id'],
            'qty' => $_POST['jumlah_beli'],
            'sub' => $_POST['total_harga'],
            'tid' => $id
        ]);

        // 4. Tambah stok di gudang sesuai jumlah BARU yang diinput
        $stmtNewStock = $pdo->prepare("UPDATE bahan_baku SET stok_bahan = stok_bahan + :new_qty WHERE bahan_baku_id = :new_bid");
        $stmtNewStock->execute([
            'new_qty' => $_POST['jumlah_beli'],
            'new_bid' => $_POST['bahan_id']
        ]);

        // Kalau semua lancar, gas simpen!
        $pdo->commit();
        echo "<script>alert('Transaksi berhasil diedit & Stok telah disesuaikan!'); window.location='index.php';</script>";

    } catch (PDOException $e) {
        // Kalau ada error, batalin semua (Rollback)
        $pdo->rollBack();
        
        // Cek error stok minus (kalau stok gak cukup buat dikurangi)
        if (strpos($e->getMessage(), 'stok_bahan') !== false) {
            $error = "Gagal Edit: Stok bahan baku tidak mencukupi untuk dikurangi (Mungkin sudah terpakai produksi).";
        } else {
            $error = "Gagal Edit: " . $e->getMessage();
        }
    }
}
?>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card shadow-sm">
            <div class="card-header bg-white py-3">
                <h5 class="mb-0"><i class="bi bi-pencil-square me-2"></i>Edit Transaksi Pembelian</h5>
            </div>
            <div class="card-body p-4">
                
                <?php if(isset($error)): ?>
                    <div class="alert alert-danger"><?= $error ?></div>
                <?php endif; ?>

                <div class="alert alert-info small">
                    <i class="bi bi-info-circle me-2"></i>
                    <strong>Info Sistem:</strong> Mengubah jumlah atau jenis barang akan otomatis mengoreksi stok di gudang.
                </div>

                <form method="POST">
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <label class="form-label text-muted small fw-bold">ID Transaksi</label>
                            <input type="text" class="form-control bg-light fw-bold" value="<?= $oldData['transaksi_pembelian_id'] ?>" disabled>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Tanggal</label>
                            <input type="date" name="tanggal" class="form-control" value="<?= $oldData['tanggal_pembelian'] ?>" required>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-bold">Pemasok</label>
                        <select name="pemasok_id" class="form-select" required>
                            <?php foreach($pemasok as $p): ?>
                                <option value="<?= $p['pemasok_id'] ?>" <?= ($p['pemasok_id'] == $oldData['pemasok_id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($p['nama_pemasok']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <hr>

                    <h6 class="text-uppercase text-muted fw-bold mb-3" style="font-size: 0.8rem;">Detail Barang</h6>
                    <div class="row">
                        <div class="col-md-12 mb-3">
                            <label class="form-label fw-bold">Bahan Baku</label>
                            <select name="bahan_id" class="form-select" required>
                                <?php foreach($bahan as $b): ?>
                                    <option value="<?= $b['bahan_baku_id'] ?>" <?= ($b['bahan_baku_id'] == $oldData['bahan_baku_id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($b['nama_bahan']) ?> (Stok Gudang: <?= $b['stok_bahan'] ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Jumlah (Qty)</label>
                            <input type="number" name="jumlah_beli" class="form-control" value="<?= $oldData['jumlah'] ?>" min="1" required>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Total Harga (Rp)</label>
                            <input type="number" name="total_harga" class="form-control" value="<?= $oldData['total_harga_beli'] ?>" min="0" required>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end gap-2 mt-4">
                        <a href="index.php" class="btn btn-light">Batal</a>
                        <button type="submit" class="btn btn-warning px-4 fw-bold">Update Transaksi</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include '../layout/footer.php'; ?>