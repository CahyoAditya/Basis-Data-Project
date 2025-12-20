<?php
include '../config/db.php';
include '../layout/header.php';

$bahan = $pdo->query("SELECT * FROM bahan_baku ORDER BY nama_bahan ASC")->fetchAll();

$prefix = "PJ";
$queryCheck = $pdo->prepare("SELECT produk_jadi_id FROM produk_jadi WHERE produk_jadi_id LIKE :prefix ORDER BY produk_jadi_id DESC LIMIT 1");
$queryCheck->execute(['prefix' => $prefix . '%']);
$lastId = $queryCheck->fetchColumn();
$newId = $lastId ? $prefix . str_pad(((int) substr($lastId, 2)) + 1, 3, "0", STR_PAD_LEFT) : $prefix . "001";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        $pdo->beginTransaction();

        if ($_POST['harga'] <= 0) {
            throw new Exception("Harga jual harus lebih dari 0!");
        }
        if ($_POST['stok'] < 0) {
            throw new Exception("Stok awal tidak boleh negatif!");
        }

        $bids = $_POST['bahan_id'] ?? [];
        $qtys = $_POST['jumlah_butuh'] ?? [];
        
        $bids = array_filter($bids);

        if (count($bids) !== count(array_unique($bids))) {
            throw new Exception("Terdeteksi bahan baku ganda dalam resep! Silakan gabungkan jumlahnya.");
        }
        
        foreach ($bids as $index => $bid) {
            if (isset($qtys[$index]) && $qtys[$index] <= 0) {
                 throw new Exception("Jumlah kebutuhan bahan tidak boleh 0 atau negatif!");
            }
        }

        $stmtProd = $pdo->prepare("INSERT INTO produk_jadi (produk_jadi_id, nama_produk, harga_jual, stok_tersedia) VALUES (:id, :nama, :harga, :stok)");
        $stmtProd->execute([
            'id' => $_POST['id'],
            'nama' => $_POST['nama'],
            'harga' => $_POST['harga'],
            'stok' => $_POST['stok']
        ]);

        $stmtResep = $pdo->prepare("INSERT INTO resep_produk (produk_jadi_id, bahan_baku_id, jumlah_butuh_per_unit) VALUES (:pid, :bid, :qty)");

        foreach ($_POST['bahan_id'] as $index => $bid) {
            $qty = $_POST['jumlah_butuh'][$index];
            if ($bid && $qty > 0) {
                $stmtResep->execute([
                    'pid' => $_POST['id'],
                    'bid' => $bid,
                    'qty' => $qty
                ]);
            }
        }

        $pdo->commit();
        echo "<script>alert('Produk Baru Berhasil Ditambahkan!'); window.location='index.php';</script>";

    } catch (Exception $e) { 
        $pdo->rollBack();
        
        if (strpos($e->getMessage(), 'unique constraint') !== false) {
             $error = "Gagal: Terdeteksi duplikasi data database.";
        } else {
             $error = "Gagal: " . $e->getMessage();
        }
    }
}
?>

<form method="POST">
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-header bg-primary text-white py-3">
            <h5 class="mb-0 fw-bold"><i class="bi bi-box-seam-fill me-2"></i>Tambah Produk Baru</h5>
        </div>

        <div class="card-body p-4">
            <?php if(isset($error)): ?>
                <div class="alert alert-danger mb-4"><i class="bi bi-exclamation-triangle-fill me-2"></i><?= $error ?></div>
            <?php endif; ?>

            <div class="row">
                <div class="col-lg-5 mb-4 mb-lg-0 border-end">
                    <h6 class="fw-bold text-primary mb-3"><i class="bi bi-info-circle me-2"></i>Informasi Dasar</h6>
                    <div class="mb-3">
                        <label class="form-label text-muted small fw-bold">ID Produk (Auto)</label>
                        <input type="text" name="id" class="form-control bg-light fw-bold text-primary" value="<?= $newId ?>" readonly>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold text-dark">Nama Produk</label>
                        <input type="text" name="nama" class="form-control" placeholder="Contoh: Nugget Ayam Spesial" required>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold text-dark">Harga Jual (Rp)</label>
                            <input type="number" name="harga" class="form-control" min="1" placeholder="0" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold text-dark">Stok Awal</label>
                            <input type="number" name="stok" class="form-control" min="0" value="0">
                            <div class="form-text small text-muted">Biasanya 0.</div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-7 ps-lg-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 class="fw-bold text-success mb-0"><i class="bi bi-basket me-2"></i>Komposisi Resep (Per 1 Unit)</h6>
                        <button type="button" class="btn btn-sm btn-outline-success fw-bold" onclick="tambahBahan()">
                            <i class="bi bi-plus-lg me-1"></i> Tambah Bahan
                        </button>
                    </div>

                    <div class="table-responsive bg-light rounded border p-2" style="min-height: 200px;">
                        <table class="table table-sm table-borderless align-middle mb-0">
                            <thead class="text-muted small border-bottom">
                                <tr>
                                    <th class="ps-2">Bahan Baku</th>
                                    <th class="text-center" width="25%">Butuh (Unit)</th>
                                    <th width="10%"></th>
                                </tr>
                            </thead>
                            <tbody id="tabelResep">
                                <tr id="emptyRow">
                                    <td colspan="3" class="text-center text-muted py-5 small fst-italic">
                                        Belum ada bahan.<br>Klik tombol <b>+ Tambah Bahan</b> untuk mengisi resep.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="alert alert-info d-flex align-items-center mt-3 mb-0 small py-2 bg-opacity-10 border-info text-info">
                        <i class="bi bi-lightbulb-fill me-2"></i>
                        <div>Resep ini akan digunakan sistem untuk <strong>mengurangi stok bahan otomatis</strong> saat Anda mencatat produksi.</div>
                    </div>
                </div>
            </div> 
        </div> 

        <div class="card-footer bg-light p-4 border-top">
            <div class="row align-items-center">
                <div class="col-md-6 text-muted small"><i class="bi bi-check-circle me-1"></i> Pastikan harga jual sudah termasuk profit margin.</div>
                <div class="col-md-6 text-end">
                    <div class="d-inline-block bg-white border rounded px-3 py-2 shadow-sm">
                        <a href="index.php" class="btn btn-secondary fw-bold me-2">Batal</a>
                        <button type="submit" class="btn btn-primary fw-bold px-4">Simpan Produk</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>

<script>
    let bahanList = <?php echo json_encode($bahan); ?>;

    function tambahBahan() {
        const emptyRow = document.getElementById('emptyRow');
        if (emptyRow) emptyRow.remove();

        let options = '<option value="" disabled selected>- Pilih Bahan -</option>';
        bahanList.forEach(b => {
            options += `<option value="${b.bahan_baku_id}">${b.nama_bahan} (${b.stok_bahan} di gudang)</option>`;
        });

        const html = `
            <tr>
                <td class="ps-2">
                    <select name="bahan_id[]" class="form-select form-select-sm border" required>${options}</select>
                </td>
                <td>
                    <input type="number" name="jumlah_butuh[]" class="form-control form-control-sm text-center fw-bold text-success border" placeholder="1" min="1" required>
                </td>
                <td class="text-center">
                    <button type="button" class="btn btn-sm btn-link text-danger p-0" onclick="hapusBaris(this)"><i class="bi bi-x-circle-fill"></i></button>
                </td>
            </tr>
        `;
        document.getElementById('tabelResep').insertAdjacentHTML('beforeend', html);
    }

    function hapusBaris(btn) {
        btn.closest('tr').remove();
        const tbody = document.getElementById('tabelResep');
        if (tbody.children.length === 0) {
            tbody.innerHTML = `<tr id="emptyRow"><td colspan="3" class="text-center text-muted py-5 small fst-italic">Belum ada bahan.<br>Klik tombol <b>+ Tambah Bahan</b> untuk mengisi resep.</td></tr>`;
        }
    }
</script>

<?php include '../layout/footer.php'; ?>