<?php
include '../config/db.php';
include '../layout/header.php';

// Generate ID Otomatis
$prefix = "PL";
$queryCheck = $pdo->prepare("SELECT pelanggan_id FROM pelanggan WHERE pelanggan_id LIKE :prefix ORDER BY pelanggan_id DESC LIMIT 1");
$queryCheck->execute(['prefix' => $prefix . '%']);
$lastId = $queryCheck->fetchColumn();
$newId = $lastId ? $prefix . str_pad(((int) substr($lastId, 2)) + 1, 3, "0", STR_PAD_LEFT) : $prefix . "001";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        $pdo->beginTransaction();

        $stmtInduk = $pdo->prepare("INSERT INTO pelanggan (pelanggan_id, no_telepon_pelanggan, alamat_pelanggan) VALUES (:id, :telp, :alamat)");
        $stmtInduk->execute([
            'id' => $_POST['id'],
            'telp' => $_POST['no_telepon'],
            'alamat' => $_POST['alamat']
        ]);

        if ($_POST['tipe_pelanggan'] == 'perorangan') {
            $stmtAnak = $pdo->prepare("INSERT INTO perorangan (pelanggan_id, nama_perorangan) VALUES (:id, :nama)");
        } else {
            $stmtAnak = $pdo->prepare("INSERT INTO bisnis (pelanggan_id, nama_bisnis) VALUES (:id, :nama)");
        }
        $stmtAnak->execute(['id' => $_POST['id'], 'nama' => $_POST['nama']]);

        $pdo->commit();
        echo "<script>alert('Pelanggan Berhasil Ditambahkan!'); window.location='index.php';</script>";

    } catch (PDOException $e) {
        $pdo->rollBack();
        $error = "Error: " . $e->getMessage();
    }
}
?>

<form method="POST">
    
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-header bg-primary text-white py-3">
            <h5 class="mb-0 fw-bold"><i class="bi bi-person-plus-fill me-2"></i>Tambah Pelanggan Baru</h5>
        </div>
        <div class="card-body p-4">
            
            <?php if(isset($error)): ?><div class="alert alert-danger mb-4"><?= $error ?></div><?php endif; ?>

            <div class="row">
                
                <div class="col-lg-6 mb-4 border-end">
                    <h6 class="fw-bold text-primary mb-3"><i class="bi bi-card-heading me-2"></i>Identitas Pelanggan</h6>
                    
                    <div class="mb-3">
                        <label class="form-label text-muted small fw-bold">ID Pelanggan (Auto)</label>
                        <input type="text" name="id" class="form-control bg-light fw-bold text-primary" value="<?= $newId ?>" readonly>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Tipe Pelanggan</label>
                        <select name="tipe_pelanggan" id="tipeSelect" class="form-select" onchange="gantiLabel()" required>
                            <option value="perorangan">Perorangan (Individu)</option>
                            <option value="bisnis">Bisnis (Toko/Perusahaan)</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold" id="labelNama">Nama Lengkap</label>
                        <input type="text" name="nama" id="inputNama" class="form-control" placeholder="Contoh: Budi Santoso" required>
                    </div>
                </div>

                <div class="col-lg-6 ps-lg-4">
                    <h6 class="fw-bold text-success mb-3"><i class="bi bi-telephone-inbound me-2"></i>Informasi Kontak</h6>
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold">No. Telepon / WhatsApp</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light"><i class="bi bi-telephone"></i></span>
                            <input type="text" name="no_telepon" class="form-control" placeholder="0812..." required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Alamat Lengkap</label>
                        <textarea name="alamat" class="form-control" rows="4" placeholder="Nama Jalan, No. Rumah, RT/RW, Kota..." required></textarea>
                    </div>
                </div>

            </div> 
        </div>

        <div class="card-footer bg-light p-4 border-top">
            <div class="row align-items-center">
                <div class="col-md-6 text-muted small"><i class="bi bi-info-circle me-1"></i> Data pelanggan digunakan untuk mencatat penjualan.</div>
                <div class="col-md-6 text-end">
                    <div class="d-inline-block bg-white border rounded px-3 py-2 shadow-sm">
                        <a href="index.php" class="btn btn-secondary fw-bold me-2">Batal</a>
                        <button type="submit" class="btn btn-primary fw-bold px-4">Simpan Pelanggan</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>

<script>
    function gantiLabel() {
        const tipe = document.getElementById('tipeSelect').value;
        const label = document.getElementById('labelNama');
        const input = document.getElementById('inputNama');

        if (tipe === 'bisnis') {
            label.innerText = 'Nama Bisnis / Toko';
            input.placeholder = 'Contoh: Toko Maju Jaya';
        } else {
            label.innerText = 'Nama Lengkap';
            input.placeholder = 'Contoh: Budi Santoso';
        }
    }
</script>

<?php include '../layout/footer.php'; ?>