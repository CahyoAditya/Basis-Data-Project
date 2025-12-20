<?php
include '../config/db.php';
include '../layout/header.php';

if (isset($_GET['hapus_id'])) {
    $id = $_GET['hapus_id'];
    try {
        // Cek dulu apakah karyawan ini ada di riwayat produksi?
        $stmtCek = $pdo->prepare("SELECT COUNT(*) FROM sesi_produksi WHERE karyawan_id = :id");
        $stmtCek->execute(['id' => $id]);
        
        if ($stmtCek->fetchColumn() > 0) {
            echo "<script>alert('Gagal Hapus: Karyawan ini tercatat dalam riwayat produksi. Hapus dulu riwayat produksinya.'); window.location='index.php';</script>";
        } else {
            $stmtDel = $pdo->prepare("DELETE FROM karyawan WHERE karyawan_id = :id");
            $stmtDel->execute(['id' => $id]);
            echo "<script>alert('Data karyawan berhasil dihapus!'); window.location='index.php';</script>";
        }
    } catch (PDOException $e) {
        echo "<div class='alert alert-danger'>Error: " . $e->getMessage() . "</div>";
    }
}

// Ambil semua data karyawan
$stmt = $pdo->query("SELECT * FROM karyawan ORDER BY karyawan_id ASC");
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h3 class="fw-bold mb-0 text-dark"><i class="bi bi-people-fill me-2"></i>Data Karyawan</h3>
    <a href="create.php" class="btn btn-primary shadow-sm"><i class="bi bi-person-plus-fill me-2"></i>Tambah Karyawan</a>
</div>

<div class="card shadow-sm border-0">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead class="bg-light">
                    <tr>
                        <th class="ps-4">ID Karyawan</th>
                        <th>Nama Lengkap</th>
                        <th>No. Telepon</th>
                        <th class="text-end pe-4">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($stmt->rowCount() > 0): ?>
                        <?php while ($row = $stmt->fetch()): ?>
                        <tr>
                            <td class="ps-4 fw-bold text-primary"><?= htmlspecialchars($row['karyawan_id']) ?></td>
                            <td>
                                <span class="fw-bold text-dark"><?= htmlspecialchars($row['nama_karyawan']) ?></span>
                            </td>
                            <td><?= htmlspecialchars($row['no_telepon_karyawan']) ?></td>
                            <td class="text-end pe-4">
                                <a href="edit.php?id=<?= $row['karyawan_id'] ?>" class="btn btn-sm btn-outline-warning me-1"><i class="bi bi-pencil-square"></i></a>
                                <a href="index.php?hapus_id=<?= $row['karyawan_id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Yakin ingin menghapus karyawan ini?')"><i class="bi bi-trash"></i></a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4" class="text-center py-5 text-muted">Belum ada data karyawan.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include '../layout/footer.php'; ?>