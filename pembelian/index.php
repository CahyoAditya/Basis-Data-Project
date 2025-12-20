<?php
include '../config/db.php';
include '../layout/header.php';

$msg = "";
$confirm_data = null;
$items_to_revert = [];
$is_safe_delete = true;

if (isset($_POST['delete_final_id'])) {
    $id = $_POST['delete_final_id'];
    try {
        $pdo->beginTransaction();

        // Ambil lagi data barang untuk dikurangi stoknya
        $stmtGet = $pdo->prepare("SELECT bahan_baku_id, jumlah FROM detail_pembelian WHERE transaksi_pembelian_id = :id");
        $stmtGet->execute(['id' => $id]);
        $items = $stmtGet->fetchAll();

        // Kurangi Stok Gudang (Revert Stock)
        $stmtRevert = $pdo->prepare("UPDATE bahan_baku SET stok_bahan = stok_bahan - :qty WHERE bahan_baku_id = :bid");
        foreach ($items as $item) {
            $stmtRevert->execute(['qty' => $item['jumlah'], 'bid' => $item['bahan_baku_id']]);
        }
        
        // Hapus Data Transaksi
        $pdo->prepare("DELETE FROM detail_pembelian WHERE transaksi_pembelian_id = :id")->execute(['id' => $id]);
        $pdo->prepare("DELETE FROM transaksi_pembelian WHERE transaksi_pembelian_id = :id")->execute(['id' => $id]);
        
        $pdo->commit();
        $msg = "<div class='alert alert-success alert-dismissible fade show'>...Transaksi berhasil dihapus...</div>";

        unset($_GET['hapus_id']);

    } catch (PDOException $e) {
        $pdo->rollBack();
        $msg = "<div class='alert alert-danger'><strong>Gagal:</strong> " . $e->getMessage() . "</div>";
    }
}

if (isset($_GET['hapus_id'])) {
    $id = $_GET['hapus_id'];
    
    // Ambil Data Item + Stok Gudang Saat Ini
    $stmtCheck = $pdo->prepare("
        SELECT dp.*, b.nama_bahan, b.stok_bahan AS stok_gudang_sekarang 
        FROM detail_pembelian dp
        JOIN bahan_baku b ON dp.bahan_baku_id = b.bahan_baku_id
        WHERE dp.transaksi_pembelian_id = :id
    ");
    $stmtCheck->execute(['id' => $id]);
    $items_to_revert = $stmtCheck->fetchAll();

    if (count($items_to_revert) > 0) {
        $confirm_data = $id;
        
        // Cek Safety: Apakah stok gudang cukup buat ditarik?
        foreach ($items_to_revert as $item) {
            if ($item['stok_gudang_sekarang'] < $item['jumlah']) {
                $is_safe_delete = false;
            }
        }
    } else {
        $msg = "<div class='alert alert-warning'>Data transaksi tidak ditemukan atau kosong.</div>";
    }
}

$sql = "SELECT tp.*, p.nama_pemasok FROM transaksi_pembelian tp JOIN pemasok p ON tp.pemasok_id = p.pemasok_id ORDER BY tp.tanggal_pembelian DESC, tp.transaksi_pembelian_id DESC";
$stmt = $pdo->query($sql);
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h3 class="fw-bold mb-0 text-dark">Riwayat Pembelian</h3>
    <a href="create.php" class="btn btn-primary shadow-sm"><i class="bi bi-cart-plus me-2"></i>Catat Pembelian</a>
</div>

<?= $msg ?>

<?php if ($confirm_data): ?>
<div class="card border-danger shadow-sm mb-4">
    <div class="card-header bg-danger text-white fw-bold">
        <i class="bi bi-exclamation-triangle-fill me-2"></i>Konfirmasi Pembatalan Transaksi
    </div>
    <div class="card-body">
        <p>Anda hendak menghapus transaksi <strong><?= $confirm_data ?></strong>.</p>
        <p>Tindakan ini akan <strong>MENARIK KEMBALI (MENGURANGI)</strong> stok bahan baku berikut dari gudang:</p>
        
        <table class="table table-bordered table-sm small align-middle">
            <thead class="bg-light">
                <tr>
                    <th>Nama Bahan</th>
                    <th class="text-center">Qty Dibatalkan</th>
                    <th class="text-center">Stok Gudang Saat Ini</th>
                    <th class="text-center">Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($items_to_revert as $item): 
                    $qty_tarik = $item['jumlah'];
                    $stok_now = $item['stok_gudang_sekarang'];
                    $aman = ($stok_now >= $qty_tarik);
                ?>
                <tr>
                    <td class="fw-bold"><?= $item['nama_bahan'] ?></td>
                    <td class="text-center text-danger fw-bold">-<?= $qty_tarik ?></td>
                    <td class="text-center"><?= $stok_now ?></td>
                    <td class="text-center">
                        <?php if ($aman): ?>
                            <span class="badge bg-success">Aman</span>
                        <?php else: ?>
                            <span class="badge bg-danger">Stok Kurang!</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <?php if (!$is_safe_delete): ?>
            <div class="alert alert-danger d-flex align-items-center mb-0">
                <i class="bi bi-x-circle-fill fs-4 me-3"></i>
                <div>
                    <strong>TIDAK DAPAT MENGHAPUS!</strong><br>
                    Beberapa bahan baku sudah terpakai (Stok Gudang < Qty Pembelian).<br>
                    Menghapus transaksi ini akan menyebabkan stok menjadi minus.
                </div>
            </div>
            <div class="mt-3 text-end">
                <a href="index.php" class="btn btn-secondary px-4">Kembali</a>
            </div>
        <?php else: ?>
            <div class="alert alert-warning d-flex align-items-center mb-3">
                <i class="bi bi-info-circle-fill fs-4 me-3"></i>
                <div>
                    Stok gudang mencukupi. Klik tombol di bawah untuk memproses penghapusan.
                </div>
            </div>
            <form method="POST" class="text-end">
                <input type="hidden" name="delete_final_id" value="<?= $confirm_data ?>">
                <a href="index.php" class="btn btn-light border me-2">Batal</a>
                <button type="submit" class="btn btn-danger fw-bold">Ya, Hapus Transaksi & Kurangi Stok</button>
            </form>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>
<div class="card shadow-sm border-0">
    <div class="card-body p-0">
        
        <?php if ($stmt->rowCount() > 0): ?>
            <div class="table-responsive">
                <table class="table table-hover mb-0 align-middle">
                    <thead class="bg-light">
                        <tr>
                            <th class="ps-4">ID Transaksi</th>
                            <th>Tanggal</th>
                            <th>Pemasok</th>
                            <th>Total Harga</th>
                            <th class="text-end pe-4">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $stmt->fetch()): ?>
                        <tr>
                            <td class="ps-4 fw-bold text-primary"><?= htmlspecialchars($row['transaksi_pembelian_id']) ?></td>
                            <td><?= date('d M Y', strtotime($row['tanggal_pembelian'])) ?></td>
                            <td><?= htmlspecialchars($row['nama_pemasok']) ?></td>
                            <td class="fw-bold text-success">Rp <?= number_format($row['total_harga_beli'], 0, ',', '.') ?></td>
                            <td class="text-end pe-4">
                                <a href="detail.php?id=<?= $row['transaksi_pembelian_id'] ?>" 
                                   class="btn btn-sm btn-outline-primary me-1" title="Lihat Detail">
                                   <i class="bi bi-eye"></i>
                                </a>
                                <a href="index.php?hapus_id=<?= $row['transaksi_pembelian_id'] ?>" 
                                   class="btn btn-sm btn-outline-danger">
                                   <i class="bi bi-trash"></i> Hapus
                                </a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="text-center py-5">
                <div class="text-muted mb-3">
                    <i class="bi bi-cart-x fs-1 opacity-50"></i>
                </div>
                <h5 class="fw-bold text-muted">Belum ada riwayat pembelian</h5>
                <p class="text-muted small mb-3">Lakukan pembelian bahan baku untuk menambah stok gudang.</p>
                <a href="create.php" class="btn btn-outline-primary btn-sm">
                    <i class="bi bi-plus-lg me-1"></i> Catat Pembelian
                </a>
            </div>
        <?php endif; ?>

    </div>
</div>
<?php include '../layout/footer.php'; ?>