<?php
include '../config/db.php';
include '../layout/header.php';

// Ambil data buat ngisi dropdown di form nanti
$karyawan = $pdo->query("SELECT * FROM karyawan ORDER BY nama_karyawan ASC")->fetchAll();
$bahan = $pdo->query("SELECT * FROM bahan_baku ORDER BY nama_bahan ASC")->fetchAll();
$produk = $pdo->query("SELECT * FROM produk_jadi ORDER BY nama_produk ASC")->fetchAll();

// Cek ID sesi terakhir, kalau ada lanjutin nomornya, kalau gak mulai dari SP001
$prefix = "SP";
$queryCheck = $pdo->prepare("SELECT sesi_produksi_id FROM sesi_produksi WHERE sesi_produksi_id LIKE :prefix ORDER BY sesi_produksi_id DESC LIMIT 1");
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
        // Mulai transaksi database biar aman
        $pdo->beginTransaction();

        // 1. Simpan Header Sesi (Info Tanggal & Karyawan)
        $stmtSesi = $pdo->prepare("INSERT INTO sesi_produksi (sesi_produksi_id, karyawan_id, tanggal_produksi, jumlah_dihasilkan) VALUES (:id, :kid, :tgl, :jml)");
        $stmtSesi->execute([
            'id' => $_POST['sesi_id'],
            'kid' => $_POST['karyawan_id'],
            'tgl' => $_POST['tanggal'],
            'jml' => $_POST['jumlah_hasil']
        ]);

        // 2. Simpan Detail Produk Jadi (Output Produksi)
        $stmtDetProd = $pdo->prepare("INSERT INTO detail_produksi (produk_jadi_id, sesi_produksi_id, sub_total_hasil_produksi) VALUES (:pid, :sid, :jml)");
        $stmtDetProd->execute([
            'pid' => $_POST['produk_id'],
            'sid' => $_POST['sesi_id'],
            'jml' => $_POST['jumlah_hasil']
        ]);

        // 3. Tambah Stok Produk Jadi di Gudang
        $stmtUpProd = $pdo->prepare("UPDATE produk_jadi SET stok_tersedia = stok_tersedia + :jml WHERE produk_jadi_id = :pid");
        $stmtUpProd->execute([
            'jml' => $_POST['jumlah_hasil'],
            'pid' => $_POST['produk_id']
        ]);

        // 4. Simpan Detail Bahan Baku (Resep yang Dipakai)
        $bahanIds = $_POST['bahan_id'];         // Array ID Bahan
        $qtys = $_POST['jumlah_pakai'];         // Array Jumlah Pakai

        $stmtDetBahan = $pdo->prepare("INSERT INTO detail_bahan (sesi_produksi_id, bahan_baku_id, sub_total_bahan_dipakai) VALUES (:sid, :bid, :jml)");
        $stmtUpBahan = $pdo->prepare("UPDATE bahan_baku SET stok_bahan = stok_bahan - :jml WHERE bahan_baku_id = :bid");

        for ($i = 0; $i < count($bahanIds); $i++) {
            $bid = $bahanIds[$i];
            $qty = $qtys[$i];

            // Catat bahan yang kepake
            $stmtDetBahan->execute([
                'sid' => $_POST['sesi_id'],
                'bid' => $bid,
                'jml' => $qty
            ]);

            // Kurangi stok bahan di gudang
            $stmtUpBahan->execute([
                'jml' => $qty,
                'bid' => $bid
            ]);
        }

        // Kalau semua lancar, save!
        $pdo->commit();
        echo "<div class='alert alert-success'>
                <h5 class='fw-bold'><i class='bi bi-check-circle me-2'></i>Produksi Berhasil!</h5>
                <p class='mb-0'>Stok Produk Jadi bertambah, dan berbagai Stok Bahan Baku telah dikurangi.</p>
              </div>";
        // Refresh halaman biar form bersih
        echo "<meta http-equiv='refresh' content='2;url=index.php'>";

    } catch (PDOException $e) {
        // Kalau ada error, batalin semua perubahan
        $pdo->rollBack();
        
        // Cek error khusus kalau stok bahan gak cukup (minus)
        if ($e->getCode() == '23514') {
             $error = "Gagal: Salah satu stok bahan baku tidak mencukupi (Minus).";
        } else {
             $error = "Error System: " . $e->getMessage();
        }
    }
}
?>

<div class="row justify-content-center">
    <div class="col-xl-10"> 
        
        <div class="d-flex align-items-center mb-4">
            <div class="bg-success bg-opacity-10 p-3 rounded-circle me-3 text-success icon-circle" style="width: 50px; height: 50px;">
                <i class="bi bi-gear-wide-connected fs-3"></i>
            </div>
            <div>
                <h4 class="fw-bold mb-0 text-dark">Catat Sesi Produksi</h4>
                <p class="text-muted small mb-0">Input hasil produksi dan bahan baku yang digunakan.</p>
            </div>
        </div>

        <form method="POST">
            
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-body p-4">
                    <h6 class="text-uppercase text-muted fw-bold small mb-3">Informasi Sesi</h6>
                    <div class="row">
                        <div class="col-md-4 mb-3 mb-md-0">
                            <label class="form-label small fw-bold text-muted">ID Sesi (Auto)</label>
                            <input type="text" name="sesi_id" class="form-control bg-light fw-bold text-primary border-0" value="<?= $newId ?>" readonly>
                        </div>
                        <div class="col-md-4 mb-3 mb-md-0">
                             <label class="form-label small fw-bold">Tanggal Produksi</label>
                             <input type="date" name="tanggal" class="form-control" value="<?= date('Y-m-d') ?>" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Penanggung Jawab</label>
                            <select name="karyawan_id" class="form-select" required>
                                <option value="" disabled selected>-- Pilih Karyawan --</option>
                                <?php foreach($karyawan as $k): ?>
                                    <option value="<?= $k['karyawan_id'] ?>"><?= $k['nama_karyawan'] ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-lg-5 mb-4">
                    <div class="card shadow-sm border-0 h-100">
                        <div class="card-header bg-success text-white py-3">
                            <h6 class="mb-0 fw-bold"><i class="bi bi-box-seam me-2"></i>HASIL PRODUKSI (Output)</h6>
                        </div>
                        <div class="card-body p-4 bg-success bg-opacity-10">
                            <p class="small text-muted mb-3">Produk apa yang berhasil dibuat hari ini?</p>
                            
                            <div class="mb-3">
                                <label class="form-label fw-bold text-success">Produk Jadi</label>
                                <select name="produk_id" class="form-select border-success" required>
                                    <option value="" disabled selected>-- Pilih Produk --</option>
                                    <?php foreach($produk as $p): ?>
                                        <option value="<?= $p['produk_jadi_id'] ?>">
                                            <?= $p['nama_produk'] ?> (Stok: <?= $p['stok_tersedia'] ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label fw-bold text-success">Jumlah Dihasilkan (Pcs)</label>
                                <div class="input-group">
                                    <input type="number" name="jumlah_hasil" class="form-control border-success fw-bold text-success" placeholder="0" min="1" required style="font-size: 1.2rem;">
                                    <span class="input-group-text bg-success text-white border-success">Pcs</span>
                                </div>
                                <div class="form-text text-success small"><i class="bi bi-arrow-up-circle me-1"></i>Stok Gudang akan bertambah.</div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-7 mb-4">
                    <div class="card shadow-sm border-0 h-100">
                        <div class="card-header bg-danger text-white py-3 d-flex justify-content-between align-items-center">
                            <h6 class="mb-0 fw-bold"><i class="bi bi-basket me-2"></i>BAHAN BAKU (Input)</h6>
                            <button type="button" class="btn btn-light text-danger shadow-sm py-1 px-3" style="font-size: 0.85rem; font-weight: 600;" onclick="tambahBaris()">
                                <i class="bi bi-plus-lg me-1"></i> Tambah
                            </button>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0" id="tableBahan">
                                    <thead class="bg-light text-muted small text-uppercase">
                                        <tr>
                                            <th class="ps-4" style="width: 55%;">Nama Bahan</th>
                                            <th style="width: 30%;">Jumlah Pakai</th>
                                            <th class="text-center" style="width: 15%;">Hapus</th>
                                        </tr>
                                    </thead>
                                    <tbody id="bahanBody">
                                        <tr>
                                            <td class="ps-4">
                                                <select name="bahan_id[]" class="form-select form-select-sm" required>
                                                    <option value="" disabled selected>- Pilih -</option>
                                                    <?php foreach($bahan as $b): ?>
                                                        <option value="<?= $b['bahan_baku_id'] ?>">
                                                            <?= $b['nama_bahan'] ?> (Sisa: <?= $b['stok_bahan'] ?>)
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </td>
                                            <td>
                                                <input type="number" name="jumlah_pakai[]" class="form-control form-control-sm" placeholder="0" min="1" required>
                                            </td>
                                            <td class="text-center">
                                                <button type="button" class="btn btn-outline-danger btn-sm border-0" onclick="hapusBaris(this)" disabled>
                                                    <i class="bi bi-x-circle-fill fs-5"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            <div class="p-3 bg-light border-top">
                                <small class="text-muted"><i class="bi bi-info-circle me-1"></i> Masukkan semua bahan yang terpakai untuk resep ini. Stok bahan akan otomatis dikurangi.</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="d-flex justify-content-end mt-2">
                <a href="index.php" class="btn btn-light btn-lg py-2 px-4 me-3" style="font-size: 1.0rem; font-weight: 600;">Batal</a>
                <button type="submit" class="btn btn-primary btn-lg py-2 px-4 fw-bold shadow-sm" style="font-size: 1.0rem; font-weight: 600;">
                    <i class="bi bi-save me-2"></i>Simpan Produksi
                </button>
            </div>

        </form>
    </div>
</div>

<script>
    function tambahBaris() {
        const tableBody = document.getElementById('bahanBody');
        const firstRow = tableBody.rows[0];
        // Clone baris pertama
        const newRow = firstRow.cloneNode(true);
        
        // Reset nilai input
        newRow.querySelectorAll('input').forEach(input => input.value = '');
        newRow.querySelectorAll('select').forEach(select => select.selectedIndex = 0);
        
        // Aktifin tombol hapus
        const btnHapus = newRow.querySelector('button');
        btnHapus.disabled = false;
        btnHapus.onclick = function() { hapusBaris(this); };
        
        tableBody.appendChild(newRow);
    }

    function hapusBaris(button) {
        const row = button.closest('tr');
        // Sisain minimal satu baris
        if (document.querySelectorAll('#bahanBody tr').length > 1) { row.remove(); } 
        else { alert("Minimal satu bahan!"); }
    }
</script>

<?php include '../layout/footer.php'; ?>