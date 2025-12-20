<?php
include '../config/db.php';
include '../layout/header.php';

// Ambil ID dari URL, terus tarik datanya dari db buat diisi ke form
$id = isset($_GET['id']) ? $_GET['id'] : '';

// Validasi jika ID tidak ada
if (!$id) {
    echo "<script>alert('ID tidak ditemukan!'); window.location='index.php';</script>";
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM bahan_baku WHERE bahan_baku_id = :id");
$stmt->execute(['id' => $id]);
$data = $stmt->fetch();

// Jika data tidak ditemukan
if (!$data) {
    echo "<script>alert('Data bahan tidak ditemukan!'); window.location='index.php';</script>";
    exit;
}

// Kalo tombol update diklik, tangkap inputan baru
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nama = $_POST['nama'];
    $stok = $_POST['stok']; 

    // Siapin query update biar data lama keganti
    $sql = "UPDATE bahan_baku SET nama_bahan = :nama, stok_bahan = :stok WHERE bahan_baku_id = :id";
    $stmtUpdate = $pdo->prepare($sql);
    
    // Gas update! Kalo sukses balik ke index, kalo gagal munculin error
    try {
        $stmtUpdate->execute(['nama' => $nama, 'stok' => $stok, 'id' => $id]);
        echo "<script>alert('Data berhasil diperbarui!'); window.location='index.php';</script>";
    } catch (PDOException $e) {
        echo "<div class='alert alert-danger'>Error: " . $e->getMessage() . "</div>";
    }
}
?>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white py-3">
                <h5 class="mb-0 fw-bold text-dark">
                    <i class="bi bi-pencil-square me-2 text-primary"></i>Edit Bahan Baku
                </h5>
            </div>

            <div class="card-body p-4">
                <form method="POST">
                    
                    <div class="mb-4">
                        <label class="form-label small fw-bold text-muted text-uppercase">ID Bahan (Auto)</label>
                        <input type="text" class="form-control bg-light" value="<?= htmlspecialchars($data['bahan_baku_id']) ?>" readonly>
                        <div class="form-text text-muted">ID dibuat otomatis oleh sistem dan tidak dapat diubah.</div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-bold">Nama Bahan Baku</label>
                        <input type="text" name="nama" class="form-control" value="<?= htmlspecialchars($data['nama_bahan']) ?>" required placeholder="Contoh: Tepung Terigu">
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-bold">Stok (Koreksi)</label>
                        <div class="input-group">
                            <input type="number" name="stok" class="form-control" min="0" value="<?= htmlspecialchars($data['stok_bahan']) ?>" required>
                            <span class="input-group-text bg-white text-muted">Unit</span>
                        </div>
                        <div class="form-text text-muted">
                            <i class="bi bi-info-circle me-1"></i>Masukkan jumlah stok fisik yang ada di gudang saat ini.
                        </div>
                    </div>

                    <hr class="my-4">

                    <div class="d-flex justify-content-end gap-2">
                        <a href="index.php" class="btn btn-light border fw-bold px-4">Batal</a>
                        <button type="submit" class="btn btn-primary fw-bold px-4">
                            <i class="bi bi-save me-2"></i>Update Data
                        </button>
                    </div>

                </form>
            </div>
        </div>
    </div>
</div>

<?php include '../layout/footer.php'; ?>