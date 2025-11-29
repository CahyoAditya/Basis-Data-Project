<?php
include '../config/db.php';
include '../layout/header.php';

// Buat format otomatis biar user nggak bingung
$prefix = "BB";
$queryCheck = $pdo->prepare("SELECT bahan_baku_id FROM bahan_baku WHERE bahan_baku_id LIKE :prefix ORDER BY bahan_baku_id DESC LIMIT 1");
$queryCheck->execute(['prefix' => $prefix . '%']);
$lastId = $queryCheck->fetchColumn();

// Kalau belum ada data, mulai dari BB001
if ($lastId) {
    $number = (int) substr($lastId, 2); 
    $newId = $prefix . str_pad($number + 1, 3, "0", STR_PAD_LEFT);
} else {
    $newId = $prefix . "001";
}

// Kalau tombol simpan diklik, gas simpan ke database
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        $stmt = $pdo->prepare("INSERT INTO bahan_baku (bahan_baku_id, nama_bahan, stok_bahan) VALUES (:id, :nama, :stok)");
        $stmt->execute([
            'id' => $_POST['id'], 
            'nama' => $_POST['nama'], 
            'stok' => $_POST['stok']
        ]);
        // Sukses! Kasih notif dan balik ke halaman utama
        echo "<script>alert('Bahan baku berhasil ditambahkan!'); window.location='index.php';</script>";
    } catch (PDOException $e) {
        // Kalau error, tangkap pesannya buat ditampilin
        $error = "Error: " . $e->getMessage();
    }
}
?>

<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white py-3">
                <h5 class="mb-0 fw-bold text-dark">
                    <i class="bi bi-box-seam me-2 text-primary"></i>Tambah Bahan Baku Baru
                </h5>
            </div>
            <div class="card-body p-4">
                
                <?php if(isset($error)): ?>
                    <div class="alert alert-danger d-flex align-items-center mb-4" role="alert">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i>
                        <div><?= $error ?></div>
                    </div>
                <?php endif; ?>

                <form method="POST">
                    
                    <div class="mb-4">
                        <label class="form-label text-muted small text-uppercase fw-bold">ID Bahan (Auto)</label>
                        <div class="input-group">
                            <input type="text" name="id" class="form-control bg-light fw-bold text-primary border-start-0" value="<?= $newId ?>" readonly>
                        </div>
                        <div class="form-text small">ID dibuat otomatis oleh sistem.</div>
                    </div>
                    
                    <div class="mb-4">
                        <label class="form-label fw-bold text-dark">Nama Bahan Baku</label>
                        <input type="text" name="nama" class="form-control" placeholder="Contoh: Tepung Terigu" required>
                    </div>
                    
                    <div class="mb-4">
                        <label class="form-label fw-bold text-dark">Stok Awal</label>
                        <div class="input-group">
                            <input type="number" name="stok" class="form-control" min="0" value="0" required>
                            <span class="input-group-text bg-white text-muted">Unit</span>
                        </div>
                        <div class="form-text small text-muted"><i class="bi bi-info-circle me-1"></i> Masukkan jumlah stok fisik yang ada di gudang saat ini.</div>
                    </div>
                    
                    <div class="d-grid gap-2 d-md-flex justify-content-md-end mt-5">
                        <a href="index.php" class="btn btn-light me-md-2 px-4 fw-bold">Batal</a>
                        <button type="submit" class="btn btn-primary px-4 fw-bold shadow-sm">
                            <i class="bi bi-save me-2"></i>Simpan Data
                        </button>
                    </div>

                </form>
            </div>
        </div>
    </div>
</div>

<?php include '../layout/footer.php'; ?>