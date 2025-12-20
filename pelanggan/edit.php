<?php
include '../config/db.php';
include '../layout/header.php';

$id = $_GET['id'] ?? '';

// Ambil Data Lama
$stmt = $pdo->prepare("
    SELECT p.*, 
           COALESCE(per.nama_perorangan, b.nama_bisnis) AS nama_asli,
           CASE WHEN per.nama_perorangan IS NOT NULL THEN 'Perorangan' ELSE 'Bisnis' END AS tipe
    FROM pelanggan p
    LEFT JOIN perorangan per ON p.pelanggan_id = per.pelanggan_id
    LEFT JOIN bisnis b ON p.pelanggan_id = b.pelanggan_id
    WHERE p.pelanggan_id = :id
");
$stmt->execute(['id' => $id]);
$data = $stmt->fetch();

if (!$data) {
    echo "<script>alert('Data tidak ditemukan!'); window.location='index.php';</script>";
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        $pdo->beginTransaction();

        $stmtInduk = $pdo->prepare("UPDATE pelanggan SET no_telepon_pelanggan = :telp, alamat_pelanggan = :alamat WHERE pelanggan_id = :id");
        $stmtInduk->execute(['telp' => $_POST['no_telepon'], 'alamat' => $_POST['alamat'], 'id' => $id]);

        if ($data['tipe'] == 'Perorangan') {
            $stmtAnak = $pdo->prepare("UPDATE perorangan SET nama_perorangan = :nama WHERE pelanggan_id = :id");
        } else {
            $stmtAnak = $pdo->prepare("UPDATE bisnis SET nama_bisnis = :nama WHERE pelanggan_id = :id");
        }
        $stmtAnak->execute(['nama' => $_POST['nama'], 'id' => $id]);

        $pdo->commit();
        echo "<script>alert('Data Pelanggan Berhasil Diupdate!'); window.location='index.php';</script>";

    } catch (PDOException $e) {
        $pdo->rollBack();
        $error = "Error: " . $e->getMessage();
    }
}
?>

<form method="POST">
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-header bg-primary text-white py-3">
            <h5 class="mb-0 fw-bold"><i class="bi bi-pencil-square me-2"></i>Edit Data Pelanggan</h5>
        </div>
        <div class="card-body p-4">
            
            <?php if(isset($error)): ?><div class="alert alert-danger mb-4"><?= $error ?></div><?php endif; ?>

            <div class="row">
                
                <div class="col-lg-6 mb-4 border-end">
                    <h6 class="fw-bold text-primary mb-3"><i class="bi bi-card-heading me-2"></i>Identitas Pelanggan</h6>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label text-muted small fw-bold">ID Pelanggan</label>
                            <input type="text" class="form-control bg-light fw-bold" value="<?= $data['pelanggan_id'] ?>" readonly>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-muted small fw-bold">Tipe</label>
                            <input type="text" class="form-control bg-light" value="<?= $data['tipe'] ?>" readonly>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Nama Lengkap / Bisnis</label>
                        <input type="text" name="nama" class="form-control fw-bold text-dark" value="<?= htmlspecialchars($data['nama_asli']) ?>" required>
                    </div>
                </div>

                <div class="col-lg-6 ps-lg-4">
                    <h6 class="fw-bold text-success mb-3"><i class="bi bi-telephone-inbound me-2"></i>Informasi Kontak</h6>
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold">No. Telepon</label>
                        <input type="text" name="no_telepon" class="form-control" value="<?= htmlspecialchars($data['no_telepon_pelanggan']) ?>" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Alamat Lengkap</label>
                        <textarea name="alamat" class="form-control" rows="4" required><?= htmlspecialchars($data['alamat_pelanggan']) ?></textarea>
                    </div>
                </div>

            </div> 
        </div>

        <div class="card-footer bg-light p-4 border-top">
            <div class="row align-items-center">
                <div class="col-md-6 text-muted small"><i class="bi bi-info-circle me-1"></i> Perubahan data akan langsung tersimpan.</div>
                <div class="col-md-6 text-end">
                    <div class="d-inline-block bg-white border rounded px-3 py-2 shadow-sm">
                        <a href="index.php" class="btn btn-secondary fw-bold me-2">Batal</a>
                        <button type="submit" class="btn btn-primary fw-bold px-4">Simpan Perubahan</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>

<?php include '../layout/footer.php'; ?>