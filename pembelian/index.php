<?php
include '../config/db.php';
include '../layout/header.php';

// Buat hapus transaksi
if (isset($_GET['hapus_id'])) {
    $id = $_GET['hapus_id'];
    try {
        // Mulai transaksi db
        $pdo->beginTransaction();
        
        // 1. Cek dulu barang apa aja yang dulu dibeli di transaksi ini
        $stmtGet = $pdo->prepare("SELECT bahan_baku_id, jumlah FROM detail_pembelian WHERE transaksi_pembelian_id = :id");
        $stmtGet->execute(['id' => $id]);
        $items = $stmtGet->fetchAll();

        // 2. Kurangi stok di gudang (karena pembeliannya dibatalin)
        $stmtRevert = $pdo->prepare("UPDATE bahan_baku SET stok_bahan = stok_bahan - :qty WHERE bahan_baku_id = :bid");
        foreach ($items as $item) {
            $stmtRevert->execute(['qty' => $item['jumlah'], 'bid' => $item['bahan_baku_id']]);
        }
        
        // 3. Hapus data transaksinya (detail & header)
        $pdo->prepare("DELETE FROM detail_pembelian WHERE transaksi_pembelian_id = :id")->execute(['id' => $id]);
        $pdo->prepare("DELETE FROM transaksi_pembelian WHERE transaksi_pembelian_id = :id")->execute(['id' => $id]);
        
        // Simpan kalo udah
        $pdo->commit();
        echo "<div class='alert alert-success alert-dismissible fade show'>Transaksi dihapus & Stok dikoreksi.<button type='button' class='btn-close' data-bs-dismiss='alert'></button></div>";

    } catch (PDOException $e) {
        // Kalau error, batalin semua
        $pdo->rollBack();
        
        // Cek error stok minus (kalau barang udah kepake produksi, gak bisa dihapus)
        if ($e->getCode() == '23514') {
            echo "<div class='alert alert-danger'>Gagal: Barang sudah terpakai produksi (Stok tidak cukup untuk ditarik).</div>";
        } else {
            echo "<div class='alert alert-danger'>Error: " . $e->getMessage() . "</div>";
        }
    }
}

// Ambil data pembelian + nama pemasoknya
$sql = "SELECT tp.*, p.nama_pemasok FROM transaksi_pembelian tp JOIN pemasok p ON tp.pemasok_id = p.pemasok_id ORDER BY tp.tanggal_pembelian DESC, tp.transaksi_pembelian_id DESC";
$stmt = $pdo->query($sql);
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h3 class="fw-bold mb-0 text-dark">Riwayat Pembelian</h3>
    <a href="create.php" class="btn btn-primary shadow-sm"><i class="bi bi-cart-plus me-2"></i>Catat Pembelian</a>
</div>

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
                                <a href="index.php?hapus_id=<?= $row['transaksi_pembelian_id'] ?>" 
                                   class="btn btn-sm btn-outline-danger"
                                   onclick="return confirm('PERINGATAN: Menghapus transaksi ini akan MENGURANGI stok bahan baku. Lanjutkan?')">
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