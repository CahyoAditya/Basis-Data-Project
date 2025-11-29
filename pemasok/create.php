<?php
include '../config/db.php';
include '../layout/header.php';

// Cek dulu ID terakhir yang ada di database
$prefix = "PS";
$queryCheck = $pdo->prepare("SELECT pemasok_id FROM pemasok WHERE pemasok_id LIKE :prefix ORDER BY pemasok_id DESC LIMIT 1");
$queryCheck->execute(['prefix' => $prefix . '%']);
$lastId = $queryCheck->fetchColumn();

// Kalau ada data lama, lanjutin nomornya. Kalau belum ada, mulai dari PS001
if ($lastId) {
    $number = (int) substr($lastId, 2);
    $newId = $prefix . str_pad($number + 1, 3, "0", STR_PAD_LEFT);
} else {
    $newId = $prefix . "001";
}

// Kalau tombol simpan diklik, proses datanya
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        // Siapin query insert
        $stmt = $pdo->prepare("INSERT INTO pemasok (pemasok_id, nama_pemasok, alamat_pemasok, no_telepon_pemasok) VALUES (:id, :nama, :alamat, :telp)");
        
        // Eksekusi simpan data
        $stmt->execute([
            'id' => $_POST['id'],
            'nama' => $_POST['nama'],
            'alamat' => $_POST['alamat'],
            'telp' => $_POST['telp']
        ]);
        
        // Sukses! Kasih notif dan balik ke halaman list
        echo "<script>alert('Pemasok berhasil disimpan!'); window.location='index.php';</script>";
    } catch (PDOException $e) {
        // Kalau gagal, simpan errornya buat ditampilin
        $error = "Error: " . $e->getMessage();
    }
}
?>

<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card shadow-sm">
            <div class="card-header bg-white">
                <h5 class="mb-0"><i class="bi bi-person-plus me-2"></i>Tambah Pemasok Baru</h5>
            </div>
            <div class="card-body">
                <?php if(isset($error)): ?>
                    <div class="alert alert-danger"><?= $error ?></div>
                <?php endif; ?>

                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label text-muted small text-uppercase fw-bold">ID Pemasok (Auto)</label>
                        <input type="text" name="id" class="form-control bg-light fw-bold text-primary" value="<?= $newId ?>" readonly>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Nama Pemasok</label>
                        <input type="text" name="nama" class="form-control" placeholder="Contoh: Grosir Sumber Jaya" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">No. Telepon</label>
                        <input type="text" name="telp" class="form-control" placeholder="0812..." required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Alamat</label>
                        <textarea name="alamat" class="form-control" rows="2"></textarea>
                    </div>
                    
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary py-2">Simpan Data</button>
                        <a href="index.php" class="btn btn-light text-muted">Batal</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include '../layout/footer.php'; ?>