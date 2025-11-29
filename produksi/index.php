<?php
include '../config/db.php';
include '../layout/header.php';

if (isset($_GET['hapus_id'])) {
    $id = $_GET['hapus_id'];
    try {
        // Mulai transaksi biar aman
        $pdo->beginTransaction();
        
        // Cek dulu bahan apa aja yang kepake di sesi ini
        $stmtGetBahan = $pdo->prepare("SELECT bahan_baku_id, sub_total_bahan_dipakai FROM detail_bahan WHERE sesi_produksi_id = :id");
        $stmtGetBahan->execute(['id' => $id]);
        $bahanList = $stmtGetBahan->fetchAll();

        // Balikin stoknya
        $stmtRestoreBahan = $pdo->prepare("UPDATE bahan_baku SET stok_bahan = stok_bahan + :qty WHERE bahan_baku_id = :bid");
        foreach ($bahanList as $b) {
            $stmtRestoreBahan->execute(['qty' => $b['sub_total_bahan_dipakai'], 'bid' => $b['bahan_baku_id']]);
        }

        // Cek produk apa yang dihasilkan
        $stmtGetProd = $pdo->prepare("SELECT produk_jadi_id, sub_total_hasil_produksi FROM detail_produksi WHERE sesi_produksi_id = :id");
        $stmtGetProd->execute(['id' => $id]);
        $prodList = $stmtGetProd->fetchAll();

        // Hapus stok produk jadinya
        $stmtRemoveProd = $pdo->prepare("UPDATE produk_jadi SET stok_tersedia = stok_tersedia - :qty WHERE produk_jadi_id = :pid");
        foreach ($prodList as $p) {
            $stmtRemoveProd->execute(['qty' => $p['sub_total_hasil_produksi'], 'pid' => $p['produk_jadi_id']]);
        }

        $pdo->prepare("DELETE FROM detail_bahan WHERE sesi_produksi_id = :id")->execute(['id' => $id]);
        $pdo->prepare("DELETE FROM detail_produksi WHERE sesi_produksi_id = :id")->execute(['id' => $id]);
        $pdo->prepare("DELETE FROM sesi_produksi WHERE sesi_produksi_id = :id")->execute(['id' => $id]);
        
        // Simpan perubahan!
        $pdo->commit();
        echo "<div class='alert alert-success alert-dismissible fade show'>Produksi Dibatalkan. Stok dikembalikan.<button type='button' class='btn-close' data-bs-dismiss='alert'></button></div>";

    } catch (PDOException $e) {
        // Kalau error, batalin semua
        $pdo->rollBack();
        
        // Cek error stok minus (kalau produknya udah laku terjual, gak bisa dihapus)
        if ($e->getCode() == '23514') {
            echo "<div class='alert alert-danger'>Gagal: Produk hasil sesi ini sudah terjual (Stok tidak cukup ditarik).</div>";
        } else {
            echo "<div class='alert alert-danger'>Error: " . $e->getMessage() . "</div>";
        }
    }
}

// Ambil data riwayat produksi + nama karyawan
$sql = "SELECT sp.*, k.nama_karyawan FROM sesi_produksi sp LEFT JOIN karyawan k ON sp.karyawan_id = k.karyawan_id ORDER BY sp.tanggal_produksi DESC, sp.sesi_produksi_id DESC";
$stmt = $pdo->query($sql);
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h3 class="fw-bold mb-0 text-dark">Riwayat Produksi</h3>
    <a href="create.php" class="btn btn-primary shadow-sm"><i class="bi bi-gear-wide-connected me-2"></i>Catat Produksi</a>
</div>

<div class="card shadow-sm border-0">
    <div class="card-body p-0">
        
        <?php if ($stmt->rowCount() > 0): ?>
            <div class="table-responsive">
                <table class="table table-hover mb-0 align-middle">
                    <thead class="bg-light">
                        <tr>
                            <th class="ps-4">ID Sesi</th>
                            <th>Tanggal</th>
                            <th>PJ (Karyawan)</th>
                            <th>Jml Hasil</th>
                            <th class="text-end pe-4">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $stmt->fetch()): ?>
                        <tr>
                            <td class="ps-4 fw-bold text-primary"><?= htmlspecialchars($row['sesi_produksi_id']) ?></td>
                            <td><?= date('d M Y', strtotime($row['tanggal_produksi'])) ?></td>
                            <td>
                                <?php if ($row['nama_karyawan']): ?>
                                    <span class="badge bg-light text-dark border"><?= htmlspecialchars($row['nama_karyawan']) ?></span>
                                <?php else: ?>
                                    <span class="text-muted small">-</span>
                                <?php endif; ?>
                            </td>
                            <td><span class="fw-bold"><?= htmlspecialchars($row['jumlah_dihasilkan']) ?></span> pcs</td>
                            <td class="text-end pe-4">
                                <a href="index.php?hapus_id=<?= $row['sesi_produksi_id'] ?>" 
                                   class="btn btn-sm btn-outline-danger"
                                   onclick="return confirm('PERINGATAN: Membatalkan produksi akan MENGEMBALIKAN bahan baku dan MENGHAPUS stok produk jadi. Lanjutkan?')">
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
                    <i class="bi bi-clipboard-x fs-1 opacity-50"></i>
                </div>
                <h5 class="fw-bold text-muted">Belum ada aktivitas produksi</h5>
                <p class="text-muted small mb-3">Mulai catat sesi produksi untuk mengolah bahan baku menjadi produk jadi.</p>
                <a href="create.php" class="btn btn-outline-primary btn-sm">
                    <i class="bi bi-plus-lg me-1"></i> Catat Produksi
                </a>
            </div>
        <?php endif; ?>

    </div>
</div>
<?php include '../layout/footer.php'; ?>