<?php
include '../config/db.php';
include '../layout/header.php';

if (isset($_GET['hapus_id'])) {
    $id = $_GET['hapus_id'];
    try {
        $cekJual = $pdo->prepare("SELECT COUNT(*) FROM detail_penjualan WHERE produk_jadi_id = :id");
        $cekJual->execute(['id' => $id]);

        $cekProd = $pdo->prepare("SELECT COUNT(*) FROM detail_produksi WHERE produk_jadi_id = :id");
        $cekProd->execute(['id' => $id]);

        if ($cekJual->fetchColumn() > 0 || $cekProd->fetchColumn() > 0) {
            echo "<script>alert('Gagal Hapus: Produk ini sudah memiliki riwayat transaksi Penjualan atau Produksi. Data tidak boleh dihapus demi integritas laporan.'); window.location='index.php';</script>";
        } else {
            $pdo->beginTransaction();

            $stmtDelResep = $pdo->prepare("DELETE FROM resep_produk WHERE produk_jadi_id = :id");
            $stmtDelResep->execute(['id' => $id]);

            $stmtDelProd = $pdo->prepare("DELETE FROM produk_jadi WHERE produk_jadi_id = :id");
            $stmtDelProd->execute(['id' => $id]);

            $pdo->commit();
            echo "<script>alert('Produk dan Resep terkait berhasil dihapus!'); window.location='index.php';</script>";
        }
    } catch (PDOException $e) {
        $pdo->rollBack();
        echo "<div class='alert alert-danger'>Error: " . $e->getMessage() . "</div>";
    }
}

$stmt = $pdo->query("SELECT * FROM produk_jadi ORDER BY produk_jadi_id ASC");
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h3 class="fw-bold mb-0 text-dark"><i class="bi bi-box-seam-fill me-2"></i>Data Produk Jadi</h3>
    <a href="create.php" class="btn btn-success shadow-sm"><i class="bi bi-plus-lg me-2"></i>Tambah Produk Baru</a>
</div>

<div class="card shadow-sm border-0">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead class="bg-light">
                    <tr>
                        <th class="ps-4">ID Produk</th>
                        <th>Nama Produk</th>
                        <th>Harga Jual</th>
                        <th>Stok Tersedia</th>
                        <th class="text-end pe-4">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($stmt->rowCount() > 0): ?>
                        <?php while ($row = $stmt->fetch()): ?>
                        <tr>
                            <td class="ps-4 fw-bold text-success"><?= htmlspecialchars($row['produk_jadi_id']) ?></td>
                            <td><?= htmlspecialchars($row['nama_produk']) ?></td>
                            <td>Rp <?= number_format($row['harga_jual'], 0, ',', '.') ?></td>
                            <td>
                                <?php if ($row['stok_teredia'] <= 10): ?>
                                    <span class="badge bg-warning text-dark"><?= $row['stok_tersedia'] ?> pcs</span>
                                <?php else: ?>
                                    <span class="badge bg-success"><?= $row['stok_tersedia'] ?> pcs</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end pe-4">
                                <a href="edit.php?id=<?= $row['produk_jadi_id'] ?>" class="btn btn-sm btn-outline-warning me-1"><i class="bi bi-pencil-square"></i></a>
                                <a href="index.php?hapus_id=<?= $row['produk_jadi_id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Yakin ingin menghapus produk ini? Resepnya juga akan terhapus.')"><i class="bi bi-trash"></i></a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="text-center py-5 text-muted">Belum ada data produk.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include '../layout/footer.php'; ?>