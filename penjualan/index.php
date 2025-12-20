<?php
include '../config/db.php';
include '../layout/header.php';

if (isset($_GET['hapus_id'])) {
    $id = $_GET['hapus_id'];
    try {
        $pdo->beginTransaction();

        $stmtGet = $pdo->prepare("SELECT produk_jadi_id, jumlah FROM detail_penjualan WHERE transaksi_penjualan_id = :id");
        $stmtGet->execute(['id' => $id]);
        $items = $stmtGet->fetchAll();

        $stmtRestore = $pdo->prepare("UPDATE produk_jadi SET stok_tersedia = stok_tersedia + :qty WHERE produk_jadi_id = :pid");
        foreach ($items as $item) {
            $stmtRestore->execute(['qty' => $item['jumlah'], 'pid' => $item['produk_jadi_id']]);
        }

        $pdo->prepare("DELETE FROM detail_penjualan WHERE transaksi_penjualan_id = :id")->execute(['id' => $id]);
        $pdo->prepare("DELETE FROM transaksi_penjualan WHERE transaksi_penjualan_id = :id")->execute(['id' => $id]);

        $pdo->commit();
        echo "<script>alert('Transaksi dihapus & stok dikembalikan!'); window.location='index.php';</script>";
    } catch (Exception $e) {
        $pdo->rollBack();
        echo "<div class='alert alert-danger'>Error: " . $e->getMessage() . "</div>";
    }
}

$sql = "SELECT tp.*, 
               COALESCE(per.nama_perorangan, b.nama_bisnis) AS nama_pelanggan,
               CASE WHEN per.nama_perorangan IS NOT NULL THEN 'Perorangan' ELSE 'Bisnis' END AS tipe_pelanggan
        FROM transaksi_penjualan tp
        JOIN pelanggan p ON tp.pelanggan_id = p.pelanggan_id
        LEFT JOIN perorangan per ON p.pelanggan_id = per.pelanggan_id
        LEFT JOIN bisnis b ON p.pelanggan_id = b.pelanggan_id
        ORDER BY tp.tanggal_penjualan DESC, tp.transaksi_penjualan_id DESC";
$stmt = $pdo->query($sql);
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h3 class="fw-bold mb-0 text-dark"><i class="bi bi-receipt me-2"></i>Riwayat Penjualan</h3>
    <a href="create.php" class="btn btn-primary shadow-sm">
        <i class="bi bi-plus-lg me-2"></i>Catat Transaksi Baru
    </a>
</div>

<div class="card shadow-sm border-0">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead class="bg-light text-uppercase small">
                    <tr>
                        <th class="ps-4">ID Transaksi</th>
                        <th>Tanggal</th>
                        <th>Pelanggan</th>
                        <th>Total Belanja</th>
                        <th class="text-end pe-4">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($stmt->rowCount() > 0): ?>
                        <?php while ($row = $stmt->fetch()): ?>
                        <tr>
                            <td class="ps-4 fw-bold text-primary">
                                <a href="detail.php?id=<?= $row['transaksi_penjualan_id'] ?>" class="text-decoration-none">
                                    <?= htmlspecialchars($row['transaksi_penjualan_id']) ?>
                                </a>
                            </td>
                            <td><?= date('d M Y', strtotime($row['tanggal_penjualan'])) ?></td>
                            <td>
                                <div class="fw-bold"><?= htmlspecialchars($row['nama_pelanggan']) ?></div>
                                <span class="badge bg-light text-secondary border"><?= $row['tipe_pelanggan'] ?></span>
                            </td>
                            <td class="fw-bold text-success">
                                Rp <?= number_format($row['total_harga_jual'], 0, ',', '.') ?>
                            </td>
                            <td class="text-end pe-4">
                                <a href="detail.php?id=<?= $row['transaksi_penjualan_id'] ?>" class="btn btn-sm btn-info text-white me-1">
                                    <i class="bi bi-eye"></i> Detail
                                </a>
                                <a href="index.php?hapus_id=<?= $row['transaksi_penjualan_id'] ?>" 
                                   class="btn btn-sm btn-outline-danger" 
                                   onclick="return confirm('Hapus transaksi ini? Stok produk akan dikembalikan.')">
                                   <i class="bi bi-trash"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="text-center py-5 text-muted">
                                <i class="bi bi-cart-x fs-1 d-block mb-2 opacity-50"></i>
                                Belum ada riwayat penjualan.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include '../layout/footer.php'; ?>