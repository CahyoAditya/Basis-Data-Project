<?php
include '../config/db.php';
include '../layout/header.php';

// Cek dulu, ada ID yang dikirim lewat URL gak?
if (!isset($_GET['id'])) {
    echo "<script>alert('ID Pemasok tidak ditemukan!'); window.location='index.php';</script>";
    exit;
}

$id = $_GET['id'];

// Ambil data lama pemasok dari database berdasarkan ID buat diisi ke form
$stmt = $pdo->prepare("SELECT * FROM pemasok WHERE pemasok_id = :id");
$stmt->execute(['id' => $id]);
$data = $stmt->fetch();

// Kalau datanya gak ketemu di db, balikin ke halaman index
if (!$data) {
    echo "<script>alert('Data pemasok tidak ditemukan!'); window.location='index.php';</script>";
    exit;
}

// Kalau tombol simpan diklik, update data baru ke database
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        $stmtUpdate = $pdo->prepare("UPDATE pemasok SET nama_pemasok=:nama, alamat_pemasok=:alamat, no_telepon_pemasok=:telp WHERE pemasok_id=:id");
        $stmtUpdate->execute([
            'nama' => $_POST['nama'],
            'alamat' => $_POST['alamat'],
            'telp' => $_POST['telp'],
            'id' => $id
        ]);
        echo "<script>alert('Data berhasil diperbarui!'); window.location='index.php';</script>";
    } catch (PDOException $e) {
        $error = "Gagal update: " . $e->getMessage();
    }
}
?>

<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card shadow-sm">
            <div class="card-header bg-white">
                <h5 class="mb-0"><i class="bi bi-pencil-square me-2"></i>Edit Data Pemasok</h5>
            </div>
            <div class="card-body">
                <?php if(isset($error)): ?>
                    <div class="alert alert-danger"><?= $error ?></div>
                <?php endif; ?>

                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label text-muted small text-uppercase fw-bold">ID Pemasok</label>
                        <input type="text" class="form-control bg-light" value="<?= $data['pemasok_id'] ?>" readonly>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Nama Pemasok</label>
                        <input type="text" name="nama" class="form-control" value="<?= htmlspecialchars($data['nama_pemasok']) ?>" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">No. Telepon</label>
                        <input type="text" name="telp" class="form-control" value="<?= htmlspecialchars($data['no_telepon_pemasok']) ?>" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Alamat</label>
                        <textarea name="alamat" class="form-control" rows="2"><?= htmlspecialchars($data['alamat_pemasok']) ?></textarea>
                    </div>
                    
                    <div class="d-flex justify-content-end gap-2">
                        <a href="index.php" class="btn btn-light">Batal</a>
                        <button type="submit" class="btn btn-primary px-4">Simpan Perubahan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include '../layout/footer.php'; ?>