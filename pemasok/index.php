<?php
include '../config/db.php';
include '../layout/header.php';

// Siapin variabel buat nampung pesan & status hapus
$warning_msg = "";
$success_msg = "";
$delete_target_id = "";
$related_count = 0;

// Buat ngapus paksa (JIKA USER UDAH KLIK 'YA')
if (isset($_POST['confirm_delete_id'])) {
    $id = $_POST['confirm_delete_id'];
    try {
        $pdo->beginTransaction();
    
        // 1. Hapus detail barang yang pernah dibeli dari pemasok ini
        $sqlCucu = "DELETE FROM detail_pembelian WHERE transaksi_pembelian_id IN (SELECT transaksi_pembelian_id FROM transaksi_pembelian WHERE pemasok_id = :id)";
        $stmtCucu = $pdo->prepare($sqlCucu);
        $stmtCucu->execute(['id' => $id]);

        // 2. Hapus riwayat transaksi pembeliannya
        $stmtAnak = $pdo->prepare("DELETE FROM transaksi_pembelian WHERE pemasok_id = :id");
        $stmtAnak->execute(['id' => $id]);

        // 3. Terakhir, hapus data pemasoknya
        $stmtInduk = $pdo->prepare("DELETE FROM pemasok WHERE pemasok_id = :id");
        $stmtInduk->execute(['id' => $id]);

        // Simpan perubahan permanen
        $pdo->commit();
        $success_msg = "Data Pemasok beserta seluruh riwayat transaksinya berhasil dihapus.";
        
        unset($_GET['hapus_id']);
    } catch (PDOException $e) {
        // Kalau error, balikin kondisi awal
        $pdo->rollBack();
        $warning_msg = "Gagal menghapus: " . $e->getMessage();
    }
}

// Cek lagi
if (isset($_GET['hapus_id'])) {
    $id = $_GET['hapus_id'];
    
    // Cek ada berapa transaksi yang nyangkut sama pemasok ini?
    $stmtCek = $pdo->prepare("SELECT COUNT(*) FROM transaksi_pembelian WHERE pemasok_id = :id");
    $stmtCek->execute(['id' => $id]);
    $related_count = $stmtCek->fetchColumn();

    // Kalau ada isinya, jangan langsung hapus. Munculin peringatan dulu.
    if ($related_count > 0) {
        $delete_target_id = $id; 
    } else {
        // Kalau bersih, sikat!
        try {
            $stmt = $pdo->prepare("DELETE FROM pemasok WHERE pemasok_id = :id");
            $stmt->execute(['id' => $id]);
            $success_msg = "Pemasok berhasil dihapus (tidak ada riwayat transaksi).";
        } catch (PDOException $e) {
            $warning_msg = "Gagal: " . $e->getMessage();
        }
    }
}

// Ambil semua data pemasok buat ditampilin di tabel
$stmt = $pdo->query("SELECT * FROM pemasok ORDER BY pemasok_id ASC");
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h3 class="fw-bold mb-0 text-dark">Data Pemasok</h3>
    <a href="create.php" class="btn btn-primary shadow-sm"><i class="bi bi-plus-lg me-2"></i>Tambah Pemasok</a>
</div>

<?php if ($success_msg): ?>
    <div class="alert alert-success alert-dismissible fade show"><?= $success_msg ?> <button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
<?php endif; ?>
<?php if ($warning_msg): ?>
    <div class="alert alert-danger"><?= $warning_msg ?></div>
<?php endif; ?>

<?php if ($delete_target_id): ?>
<div class="alert alert-warning border-danger shadow-sm">
    <h5 class="text-danger fw-bold"><i class="bi bi-exclamation-triangle-fill me-2"></i>Konfirmasi Penghapusan</h5>
    <p>Anda akan menghapus pemasok <strong><?= htmlspecialchars($delete_target_id) ?></strong>.</p>
    <p class="mb-0">Pemasok ini memiliki <strong><?= $related_count ?> riwayat transaksi pembelian</strong>.</p>
    <p>Jika dilanjutkan, <strong>seluruh data transaksi dan detail barang yang dibeli dari pemasok ini akan ikut TERHAPUS.</strong></p>
    <hr>
    <form method="POST">
        <input type="hidden" name="confirm_delete_id" value="<?= $delete_target_id ?>">
        <button type="submit" class="btn btn-danger fw-bold">Ya, Hapus Semuanya</button>
        <a href="index.php" class="btn btn-secondary">Batal</a>
    </form>
</div>
<?php endif; ?>

<div class="card shadow-sm border-0">
    <div class="card-body p-0">
        
        <?php if ($stmt->rowCount() > 0): ?>
            <div class="table-responsive">
                <table class="table table-hover mb-0 align-middle">
                    <thead class="bg-light">
                        <tr>
                            <th class="ps-4">ID</th>
                            <th>Nama Pemasok</th>
                            <th>No. Telepon</th>
                            <th>Alamat</th>
                            <th class="text-end pe-4">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $stmt->fetch()): ?>
                        <tr>
                            <td class="ps-4 fw-bold text-primary"><?= $row['pemasok_id'] ?></td>
                            <td><?= htmlspecialchars($row['nama_pemasok']) ?></td>
                            <td><?= htmlspecialchars($row['no_telepon_pemasok']) ?></td>
                            <td><?= htmlspecialchars($row['alamat_pemasok']) ?></td>
                            <td class="text-end pe-4">
                                <a href="edit.php?id=<?= $row['pemasok_id'] ?>" class="btn btn-sm btn-outline-warning me-1"><i class="bi bi-pencil"></i></a>
                                <a href="index.php?hapus_id=<?= $row['pemasok_id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Hapus pemasok ini?')"><i class="bi bi-trash"></i></a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="text-center py-5">
                <div class="text-muted mb-3">
                    <i class="bi bi-shop-window fs-1 opacity-50"></i>
                </div>
                <h5 class="fw-bold text-muted">Belum ada data pemasok</h5>
                <p class="text-muted small mb-3">Silakan tambahkan data supplier bahan baku Anda.</p>
                <a href="create.php" class="btn btn-outline-primary btn-sm">
                    <i class="bi bi-plus-lg me-1"></i> Tambah Baru
                </a>
            </div>
        <?php endif; ?>

    </div>
</div>

<?php include '../layout/footer.php'; ?>