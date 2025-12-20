<?php
include '../config/db.php';
include '../layout/header.php';

// Auto ID (KRxxx)
$prefix = "KR";
$queryCheck = $pdo->prepare("SELECT karyawan_id FROM karyawan WHERE karyawan_id LIKE :prefix ORDER BY karyawan_id DESC LIMIT 1");
$queryCheck->execute(['prefix' => $prefix . '%']);
$lastId = $queryCheck->fetchColumn();

if ($lastId) {
    $number = (int) substr($lastId, 2);
    $newId = $prefix . str_pad($number + 1, 3, "0", STR_PAD_LEFT);
} else {
    $newId = $prefix . "001";
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        $stmt = $pdo->prepare("INSERT INTO karyawan (karyawan_id, nama_karyawan, no_telepon_karyawan) VALUES (:id, :nama, :telp)");
        $stmt->execute([
            'id' => $_POST['id'],
            'nama' => $_POST['nama'],
            'telp' => $_POST['telp']
        ]);
        echo "<script>alert('Karyawan berhasil ditambahkan!'); window.location='index.php';</script>";
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
                    <i class="bi bi-person-plus-fill me-2 text-primary"></i>Tambah Karyawan Baru
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
                        <label class="form-label text-muted small text-uppercase fw-bold">ID Karyawan (Auto)</label>
                        <input type="text" name="id" class="form-control bg-light fw-bold text-primary" value="<?= $newId ?>" readonly>
                        <div class="form-text text-muted small">ID dibuat otomatis oleh sistem.</div>
                    </div>
                    
                    <div class="mb-4">
                        <label class="form-label fw-bold text-dark">Nama Lengkap</label>
                        <input type="text" name="nama" class="form-control" placeholder="Contoh: Budi Santoso" required>
                    </div>
                    
                    <div class="mb-4">
                        <label class="form-label fw-bold text-dark">No. Telepon</label>
                        <input type="text" name="telp" class="form-control" placeholder="0812..." required>
                    </div>
                    
                    <hr class="my-4">

                    <div class="d-flex justify-content-end gap-2">
                        <a href="index.php" class="btn btn-light border fw-bold px-4">Batal</a>
                        <button type="submit" class="btn btn-primary fw-bold px-4 shadow-sm">
                            <i class="bi bi-save me-2"></i>Simpan Data
                        </button>
                    </div>

                </form>
            </div>
        </div>
    </div>
</div>

<?php include '../layout/footer.php'; ?>