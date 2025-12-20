<?php
include '../config/db.php';
include '../layout/header.php';

$id = isset($_GET['id']) ? $_GET['id'] : '';
$stmt = $pdo->prepare("SELECT * FROM karyawan WHERE karyawan_id = :id");
$stmt->execute(['id' => $id]);
$data = $stmt->fetch();

if (!$data) {
    echo "<script>alert('Data karyawan tidak ditemukan!'); window.location='index.php';</script>";
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        $stmtUp = $pdo->prepare("UPDATE karyawan SET nama_karyawan=:nama, no_telepon_karyawan=:telp WHERE karyawan_id=:id");
        $stmtUp->execute([
            'nama' => $_POST['nama'],
            'telp' => $_POST['telp'],
            'id' => $id
        ]);
        echo "<script>alert('Data karyawan diperbarui!'); window.location='index.php';</script>";
    } catch (PDOException $e) {
        $error = "Error: " . $e->getMessage();
    }
}
?>

<div class="row justify-content-center">
    <div class="col-lg-6">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white py-3">
                <h5 class="mb-0 fw-bold text-dark">
                    <i class="bi bi-pencil-square me-2 text-primary"></i>Edit Data Karyawan
                </h5>
            </div>
            <div class="card-body p-4">
                
                <?php if(isset($error)): ?>
                    <div class="alert alert-danger d-flex align-items-center" role="alert">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i>
                        <div><?= $error ?></div>
                    </div>
                <?php endif; ?>

                <form method="POST">
                    
                    <div class="mb-4">
                        <label class="form-label text-muted small text-uppercase fw-bold">ID Karyawan</label>
                        <input type="text" class="form-control bg-light fw-bold text-dark" value="<?= htmlspecialchars($data['karyawan_id']) ?>" readonly>
                        <div class="form-text text-muted small">ID tidak dapat diubah.</div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-bold text-dark">Nama Lengkap</label>
                        <input type="text" name="nama" class="form-control" value="<?= htmlspecialchars($data['nama_karyawan']) ?>" required>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-bold text-dark">No. Telepon</label>
                        <input type="text" name="telp" class="form-control" value="<?= htmlspecialchars($data['no_telepon_karyawan']) ?>" required>
                    </div>
                    
                    <hr class="my-4">

                    <div class="d-flex justify-content-end gap-2">
                        <a href="index.php" class="btn btn-light border fw-bold px-4">Batal</a>
                        <button type="submit" class="btn btn-primary fw-bold px-4 shadow-sm">
                            <i class="bi bi-save me-2"></i>Simpan Perubahan
                        </button>
                    </div>

                </form>
            </div>
        </div>
    </div>
</div>

<?php include '../layout/footer.php'; ?>