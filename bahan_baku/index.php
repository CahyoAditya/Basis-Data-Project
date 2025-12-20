<?php
include '../config/db.php';
include '../layout/header.php';

// Siapin variabel buat nampung pesan dan status hapus
$msg = "";
$delete_target = "";
$dep_beli = 0;
$dep_prod = 0;

// Logic buat hapus paksa (jika dan hanya jika user udah setuju)
if (isset($_POST['confirm_id'])) {
    $id = $_POST['confirm_id'];
    try {
        // Mulai transaksi database biar aman
        $pdo->beginTransaction();
        
        // Hapus dulu data anaknya (detail pembelian & produksi)
        $pdo->prepare("DELETE FROM detail_pembelian WHERE bahan_baku_id = :id")->execute(['id'=>$id]);
        $pdo->prepare("DELETE FROM detail_bahan WHERE bahan_baku_id = :id")->execute(['id'=>$id]);
        
        // Baru deh hapus induknya (bahan baku)
        $pdo->prepare("DELETE FROM bahan_baku WHERE bahan_baku_id = :id")->execute(['id'=>$id]);
        
        // Commit perubahan kalau sukses semua
        $pdo->commit();
        $msg = "<div class='alert alert-success'>Data Bahan Baku & Riwayatnya Berhasil Dihapus.</div>";
        
        unset($_GET['hapus_id']);
    } catch (Exception $e) {
        // Kalau error, balikin kondisi semula (Rollback)
        $pdo->rollBack();
        $msg = "<div class='alert alert-danger'>Error: " . $e->getMessage() . "</div>";
    }
}

// Logic buat cek sebelum hapus
if (isset($_GET['hapus_id'])) {
    $id = $_GET['hapus_id'];
    
    // Cek apakah bahan ini pernah dibeli?
    $stmt1 = $pdo->prepare("SELECT COUNT(*) FROM detail_pembelian WHERE bahan_baku_id = :id");
    $stmt1->execute(['id'=>$id]);
    $dep_beli = $stmt1->fetchColumn();

    // Cek apakah bahan ini pernah dipakai produksi?
    $stmt2 = $pdo->prepare("SELECT COUNT(*) FROM detail_bahan WHERE bahan_baku_id = :id");
    $stmt2->execute(['id'=>$id]);
    $dep_prod = $stmt2->fetchColumn();

    // Kalau ada jejaknya, jangan langsung hapus. Minta konfirmasi dulu.
    if (($dep_beli + $dep_prod) > 0) {
        $delete_target = $id;
    } else {
        // Kalau bersih tanpa riwayat, langsung hapus aja
        $pdo->prepare("DELETE FROM bahan_baku WHERE bahan_baku_id = :id")->execute(['id'=>$id]);
        $msg = "<div class='alert alert-success'>Bahan baku berhasil dihapus.</div>";
    }
}

// Ambil semua data bahan baku buat ditampilin di tabel
$stmt = $pdo->query("SELECT * FROM bahan_baku ORDER BY bahan_baku_id ASC");
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h3 class="fw-bold mb-0 text-dark">Stok Bahan Baku</h3>
    <a href="create.php" class="btn btn-primary shadow-sm"><i class="bi bi-plus-lg me-2"></i>Tambah Bahan</a>
</div>

<?= $msg ?>

<?php if ($delete_target): ?>
<div class="alert alert-warning border-danger shadow-sm">
    <h5 class="text-danger fw-bold">⚠️ Peringatan Ketergantungan Data</h5>
    <p>Bahan Baku <strong><?= $delete_target ?></strong> terhubung dengan data lain:</p>
    <ul>
        <li>Tercatat dalam <strong><?= $dep_beli ?></strong> riwayat pembelian.</li>
        <li>Tercatat dalam <strong><?= $dep_prod ?></strong> riwayat penggunaan produksi.</li>
    </ul>
    <p>Menghapus data ini akan <strong>menghapus detail di riwayat tersebut</strong> (namun header transaksi tetap ada).</p>
    
    <form method="POST">
        <input type="hidden" name="confirm_id" value="<?= $delete_target ?>">
        <button type="submit" class="btn btn-danger fw-bold">Ya, Hapus Bahan Ini</button>
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
                            <th class="ps-4">ID Bahan</th>
                            <th>Nama Bahan</th>
                            <th>Stok Tersedia</th>
                            <th class="text-end pe-4">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $stmt->fetch()): ?>
                        <tr>
                            <td class="ps-4 fw-bold text-primary"><?= $row['bahan_baku_id'] ?></td>
                            <td><?= htmlspecialchars($row['nama_bahan']) ?></td>
                            <td><span class="badge bg-info text-dark"><?= $row['stok_bahan'] ?></span></td>
                            <td class="text-end pe-4">
                                <a href="edit.php?id=<?= $row['bahan_baku_id'] ?>" class="btn btn-sm btn-outline-warning me-1"><i class="bi bi-pencil"></i></a>
                                <a href="index.php?hapus_id=<?= $row['bahan_baku_id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Hapus bahan baku ini?')"><i class="bi bi-trash"></i></a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="text-center py-5">
                <div class="text-muted mb-3">
                    <i class="bi bi-box-seam fs-1 opacity-50"></i>
                </div>
                <h5 class="fw-bold text-muted">Stok bahan baku kosong</h5>
                <p class="text-muted small mb-3">Belum ada data bahan baku yang terdaftar di sistem.</p>
                <a href="create.php" class="btn btn-outline-primary btn-sm">
                    <i class="bi bi-plus-lg me-1"></i> Tambah Bahan
                </a>
            </div>
        <?php endif; ?>

    </div>
</div>

<?php include '../layout/footer.php'; ?>