<?php
include '../config/db.php';
include '../layout/header.php';

if (isset($_GET['hapus_id'])) {
    $id = $_GET['hapus_id'];
    try {
        $cekTrans = $pdo->prepare("SELECT COUNT(*) FROM transaksi_penjualan WHERE pelanggan_id = :id");
        $cekTrans->execute(['id' => $id]);
        
        if ($cekTrans->fetchColumn() > 0) {
            echo "<script>alert('Gagal Hapus: Pelanggan ini memiliki riwayat transaksi penjualan. Hapus transaksinya terlebih dahulu.'); window.location='index.php';</script>";
        } else {
            $pdo->beginTransaction();
            $pdo->prepare("DELETE FROM perorangan WHERE pelanggan_id = :id")->execute(['id' => $id]);
            $pdo->prepare("DELETE FROM bisnis WHERE pelanggan_id = :id")->execute(['id' => $id]);
            
            $pdo->prepare("DELETE FROM pelanggan WHERE pelanggan_id = :id")->execute(['id' => $id]);
            
            $pdo->commit();
            echo "<script>alert('Data pelanggan berhasil dihapus!'); window.location='index.php';</script>";
        }
    } catch (PDOException $e) {
        $pdo->rollBack();
        echo "<div class='alert alert-danger'>Error: " . $e->getMessage() . "</div>";
    }
}

$sql = "SELECT p.*, 
               COALESCE(per.nama_perorangan, b.nama_bisnis) AS nama_asli,
               CASE WHEN per.nama_perorangan IS NOT NULL THEN 'Perorangan' ELSE 'Bisnis' END AS tipe
        FROM pelanggan p
        LEFT JOIN perorangan per ON p.pelanggan_id = per.pelanggan_id
        LEFT JOIN bisnis b ON p.pelanggan_id = b.pelanggan_id
        ORDER BY p.pelanggan_id DESC";
$stmt = $pdo->query($sql);
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h3 class="fw-bold mb-0 text-dark"><i class="bi bi-people-fill me-2"></i>Data Pelanggan</h3>
    <a href="create.php" class="btn btn-primary shadow-sm"><i class="bi bi-person-plus-fill me-2"></i>Tambah Pelanggan</a>
</div>

<div class="card shadow-sm border-0">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead class="bg-light">
                    <tr>
                        <th class="ps-4">ID</th>
                        <th>Nama Pelanggan</th>
                        <th>Tipe</th>
                        <th>No. Telepon</th>
                        <th>Alamat</th>
                        <th class="text-end pe-4">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($stmt->rowCount() > 0): ?>
                        <?php while ($row = $stmt->fetch()): ?>
                        <tr>
                            <td class="ps-4 fw-bold text-primary"><?= htmlspecialchars($row['pelanggan_id']) ?></td>
                            <td>
                                <span class="fw-bold"><?= htmlspecialchars($row['nama_asli']) ?></span>
                            </td>
                            <td>
                                <?php if($row['tipe'] == 'Perorangan'): ?>
                                    <span class="badge bg-info text-dark bg-opacity-10 border border-info">Individu</span>
                                <?php else: ?>
                                    <span class="badge bg-warning text-dark bg-opacity-10 border border-warning">Bisnis</span>
                                <?php endif; ?>
                            </td>
                            <td><?= htmlspecialchars($row['no_telepon_pelanggan']) ?></td>
                            <td class="small text-muted"><?= htmlspecialchars($row['alamat_pelanggan']) ?></td>
                            <td class="text-end pe-4">
                                <a href="edit.php?id=<?= $row['pelanggan_id'] ?>" class="btn btn-sm btn-outline-warning me-1"><i class="bi bi-pencil-square"></i></a>
                                <a href="index.php?hapus_id=<?= $row['pelanggan_id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Yakin ingin menghapus pelanggan ini?')"><i class="bi bi-trash"></i></a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="text-center py-5 text-muted">Belum ada data pelanggan.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include '../layout/footer.php'; ?>