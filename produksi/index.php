<?php
include '../config/db.php';
include '../layout/header.php';

$msg = "";
$confirm_id = null;
$list_produk_tarik = [];
$list_bahan_kembali = [];
$is_safe = true;

if (isset($_POST['delete_final_id'])) {
    $id = $_POST['delete_final_id'];
    try {
        $pdo->beginTransaction();

        $stmtGetBahan = $pdo->prepare("SELECT bahan_baku_id, sub_total_bahan_dipakai FROM detail_bahan WHERE sesi_produksi_id = :id");
        $stmtGetBahan->execute(['id' => $id]);
        $bahanList = $stmtGetBahan->fetchAll();
        $stmtRestoreBahan = $pdo->prepare("UPDATE bahan_baku SET stok_bahan = stok_bahan + :qty WHERE bahan_baku_id = :bid");
        foreach ($bahanList as $b) {
            $stmtRestoreBahan->execute(['qty' => $b['sub_total_bahan_dipakai'], 'bid' => $b['bahan_baku_id']]);
        }

        $stmtGetProd = $pdo->prepare("SELECT produk_jadi_id, sub_total_hasil_produksi FROM detail_produksi WHERE sesi_produksi_id = :id");
        $stmtGetProd->execute(['id' => $id]);
        $prodList = $stmtGetProd->fetchAll();
        $stmtRemoveProd = $pdo->prepare("UPDATE produk_jadi SET stok_tersedia = stok_tersedia - :qty WHERE produk_jadi_id = :pid");
        foreach ($prodList as $p) {
            $stmtRemoveProd->execute(['qty' => $p['sub_total_hasil_produksi'], 'pid' => $p['produk_jadi_id']]);
        }

        $pdo->prepare("DELETE FROM detail_bahan WHERE sesi_produksi_id = :id")->execute(['id' => $id]);
        $pdo->prepare("DELETE FROM detail_produksi WHERE sesi_produksi_id = :id")->execute(['id' => $id]);
        $pdo->prepare("DELETE FROM sesi_produksi WHERE sesi_produksi_id = :id")->execute(['id' => $id]);
        
        $pdo->commit();
        $msg = "<div class='alert alert-success alert-dismissible fade show'><i class='bi bi-check-circle me-2'></i>Sesi Produksi berhasil dibatalkan.<button type='button' class='btn-close' data-bs-dismiss='alert'></button></div>";

        unset($_GET['hapus_id']); 

    } catch (PDOException $e) {
        $pdo->rollBack();
        $msg = "<div class='alert alert-danger'>Gagal: " . $e->getMessage() . "</div>";
    }
}

if (isset($_GET['hapus_id'])) {
    $confirm_id = $_GET['hapus_id'];
    
    $stmtCekProd = $pdo->prepare("
        SELECT dp.*, p.nama_produk, p.stok_tersedia AS stok_gudang
        FROM detail_produksi dp
        JOIN produk_jadi p ON dp.produk_jadi_id = p.produk_jadi_id
        WHERE dp.sesi_produksi_id = :id
    ");
    $stmtCekProd->execute(['id' => $confirm_id]);
    $list_produk_tarik = $stmtCekProd->fetchAll();

    $stmtCekBahan = $pdo->prepare("
        SELECT db.*, b.nama_bahan 
        FROM detail_bahan db
        JOIN bahan_baku b ON db.bahan_baku_id = b.bahan_baku_id
        WHERE db.sesi_produksi_id = :id
    ");
    $stmtCekBahan->execute(['id' => $confirm_id]);
    $list_bahan_kembali = $stmtCekBahan->fetchAll();

    foreach ($list_produk_tarik as $p) {
        if ($p['stok_gudang'] < $p['sub_total_hasil_produksi']) {
            $is_safe = false;
        }
    }
}

$sql = "SELECT sp.*, k.nama_karyawan FROM sesi_produksi sp LEFT JOIN karyawan k ON sp.karyawan_id = k.karyawan_id ORDER BY sp.tanggal_produksi DESC, sp.sesi_produksi_id DESC";
$stmt = $pdo->query($sql);
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h3 class="fw-bold mb-0 text-dark">Riwayat Produksi</h3>
    <a href="create.php" class="btn btn-primary shadow-sm"><i class="bi bi-gear-wide-connected me-2"></i>Catat Produksi</a>
</div>

<?= $msg ?>

<?php if ($confirm_id): ?>
<div class="card border-danger shadow-sm mb-4">
    <div class="card-header bg-danger text-white fw-bold">
        <i class="bi bi-exclamation-triangle-fill me-2"></i>Konfirmasi Pembatalan Produksi
    </div>
    <div class="card-body">
        <p>Anda hendak membatalkan Sesi Produksi <strong><?= $confirm_id ?></strong>. Berikut dampaknya:</p>

        <div class="row">
            <div class="col-md-6">
                <h6 class="fw-bold text-danger border-bottom pb-2">1. Produk Jadi Akan Dihapus (Stok Berkurang)</h6>
                <table class="table table-sm table-bordered align-middle small">
                    <thead class="bg-light">
                        <tr>
                            <th>Nama Produk</th>
                            <th class="text-center">Qty Ditarik</th>
                            <th class="text-center">Stok Gudang</th>
                            <th class="text-center">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($list_produk_tarik as $p): 
                            $aman = ($p['stok_gudang'] >= $p['sub_total_hasil_produksi']);
                        ?>
                        <tr>
                            <td><?= $p['nama_produk'] ?></td>
                            <td class="text-center fw-bold text-danger">-<?= $p['sub_total_hasil_produksi'] ?></td>
                            <td class="text-center"><?= $p['stok_gudang'] ?></td>
                            <td class="text-center">
                                <?php if($aman): ?>
                                    <span class="badge bg-success">Aman</span>
                                <?php else: ?>
                                    <span class="badge bg-danger">Terjual!</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="col-md-6">
                <h6 class="fw-bold text-success border-bottom pb-2">2. Bahan Baku Dikembalikan (Stok Bertambah)</h6>
                <ul class="list-group list-group-flush small">
                    <?php foreach($list_bahan_kembali as $b): ?>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <?= $b['nama_bahan'] ?>
                            <span class="badge bg-success rounded-pill">+<?= $b['sub_total_bahan_dipakai'] ?> Unit</span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>

        <hr>

        <?php if (!$is_safe): ?>
            <div class="alert alert-danger d-flex align-items-center mb-0">
                <i class="bi bi-x-circle-fill fs-4 me-3"></i>
                <div>
                    <strong>TIDAK DAPAT MENGHAPUS!</strong><br>
                    Produk hasil sesi ini sebagian sudah terjual (Stok Gudang < Qty Produksi).<br>
                    Anda tidak bisa membatalkan produksi jika barangnya sudah tidak ada.
                </div>
            </div>
            <div class="mt-3 text-end">
                <a href="index.php" class="btn btn-secondary px-4">Kembali</a>
            </div>
        <?php else: ?>
            <div class="d-flex justify-content-between align-items-center">
                <div class="text-muted small">
                    <i class="bi bi-info-circle me-1"></i> Stok produk mencukupi untuk ditarik. Aman untuk dihapus.
                </div>
                <form method="POST">
                    <input type="hidden" name="delete_final_id" value="<?= $confirm_id ?>">
                    <a href="index.php" class="btn btn-light border me-2">Batal</a>
                    <button type="submit" class="btn btn-danger fw-bold">Ya, Batalkan Produksi</button>
                </form>
            </div>
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
                            <td><?= htmlspecialchars($row['nama_karyawan'] ?? '-') ?></td>
                            <td><span class="fw-bold"><?= htmlspecialchars($row['jumlah_dihasilkan']) ?></span> pcs</td>
                            <td class="text-end pe-4">
                                <a href="detail.php?id=<?= $row['sesi_produksi_id'] ?>" 
                                    class="btn btn-sm btn-outline-primary me-1" 
                                    title="Lihat Rincian">
                                    <i class="bi bi-eye"></i>
                                </a>
                                <a href="index.php?hapus_id=<?= $row['sesi_produksi_id'] ?>" 
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
                <div class="text-muted mb-3"><i class="bi bi-clipboard-x fs-1 opacity-50"></i></div>
                <h5 class="fw-bold text-muted">Belum ada aktivitas produksi</h5>
                <a href="create.php" class="btn btn-outline-primary btn-sm mt-2">Catat Produksi</a>
            </div>
        <?php endif; ?>

    </div>
</div>

<?php include '../layout/footer.php'; ?>