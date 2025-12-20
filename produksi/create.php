<?php
include '../config/db.php';
include '../layout/header.php';

$karyawan = $pdo->query("SELECT * FROM karyawan ORDER BY nama_karyawan ASC")->fetchAll();
$produk = $pdo->query("SELECT * FROM produk_jadi ORDER BY nama_produk ASC")->fetchAll();
$bahan = $pdo->query("SELECT * FROM bahan_baku ORDER BY nama_bahan ASC")->fetchAll();

$prefix = "SP";
$queryCheck = $pdo->prepare("SELECT sesi_produksi_id FROM sesi_produksi WHERE sesi_produksi_id LIKE :prefix ORDER BY sesi_produksi_id DESC LIMIT 1");
$queryCheck->execute(['prefix' => $prefix . '%']);
$lastId = $queryCheck->fetchColumn();
$newId = $lastId ? $prefix . str_pad(((int) substr($lastId, 2)) + 1, 3, "0", STR_PAD_LEFT) : $prefix . "001";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        $pids = $_POST['produk_id'] ?? [];
        $qtys = $_POST['jumlah_hasil'] ?? [];
        for ($i=0; $i < count($pids); $i++) {
            if ($qtys[$i] <= 0) throw new Exception("Qty Produk tidak boleh 0 atau Negatif!");
        }

        $bids = $_POST['bahan_id'] ?? [];         
        $bqtys = $_POST['jumlah_pakai'] ?? [];
        for ($i=0; $i < count($bids); $i++) {
            if ($bqtys[$i] < 0) throw new Exception("Qty Bahan Baku tidak boleh Negatif!");
        }

        $pdo->beginTransaction();

        $totalHasil = array_sum($qtys);
        $stmtSesi = $pdo->prepare("INSERT INTO sesi_produksi (sesi_produksi_id, karyawan_id, tanggal_produksi, jumlah_dihasilkan) VALUES (:id, :kid, :tgl, :jml)");
        $stmtSesi->execute([
            'id' => $_POST['sesi_id'],
            'kid' => $_POST['karyawan_id'],
            'tgl' => $_POST['tanggal'],
            'jml' => $totalHasil
        ]);

        $stmtDetProd = $pdo->prepare("INSERT INTO detail_produksi (produk_jadi_id, sesi_produksi_id, sub_total_hasil_produksi) VALUES (:pid, :sid, :jml)");
        $stmtUpProd = $pdo->prepare("UPDATE produk_jadi SET stok_tersedia = stok_tersedia + :jml WHERE produk_jadi_id = :pid");
        $stmtDetBahan = $pdo->prepare("INSERT INTO detail_bahan (sesi_produksi_id, bahan_baku_id, sub_total_bahan_dipakai) VALUES (:sid, :bid, :jml)");
        $stmtUpBahan = $pdo->prepare("UPDATE bahan_baku SET stok_bahan = stok_bahan - :jml WHERE bahan_baku_id = :bid");

        for ($i=0; $i < count($pids); $i++) {
            $stmtDetProd->execute(['pid' => $pids[$i], 'sid' => $_POST['sesi_id'], 'jml' => $qtys[$i]]);
            $stmtUpProd->execute(['jml' => $qtys[$i], 'pid' => $pids[$i]]);
        }

        for ($i = 0; $i < count($bids); $i++) {
            $bid = $bids[$i];
            $qty = $bqtys[$i];
            if($bid && $qty > 0) {
                $stmtDetBahan->execute(['sid' => $_POST['sesi_id'], 'bid' => $bid, 'jml' => $qty]);
                $stmtUpBahan->execute(['jml' => $qty, 'bid' => $bid]);
            }
        }

        $pdo->commit();
        echo "<script>alert('Produksi Berhasil Disimpan!'); window.location='index.php';</script>";

    } catch (Exception $e) {
        $pdo->rollBack();
        $error = strpos($e->getMessage(), 'stok_bahan') !== false ? "Gagal: Stok bahan baku tidak mencukupi." : "Gagal: " . $e->getMessage();
    }
}
?>

<form method="POST">
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-header bg-primary text-white py-3">
            <h5 class="mb-0 fw-bold"><i class="bi bi-gear-wide-connected me-2"></i>Catat Sesi Produksi</h5>
        </div>

        <div class="card-body p-4">
            <?php if(isset($error)): ?>
                <div class="alert alert-danger mb-4"><i class="bi bi-exclamation-triangle-fill me-2"></i><?= $error ?></div>
            <?php endif; ?>

            <div class="row mb-4">
                <div class="col-md-4">
                    <label class="form-label text-muted small fw-bold">ID Sesi Produksi</label>
                    <input type="text" name="sesi_id" class="form-control bg-light fw-bold text-primary" value="<?= $newId ?>" readonly>
                </div>
                <div class="col-md-4">
                    <label class="form-label text-muted small fw-bold">Tanggal</label>
                    <input type="date" name="tanggal" class="form-control" value="<?= date('Y-m-d') ?>" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label text-muted small fw-bold">Penanggung Jawab</label>
                    <select name="karyawan_id" class="form-select" required>
                        <option value="" disabled selected>-- Pilih Karyawan --</option>
                        <?php foreach($karyawan as $k): ?>
                            <option value="<?= $k['karyawan_id'] ?>"><?= $k['nama_karyawan'] ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <hr class="my-4 opacity-25">

            <div class="row">
                <div class="col-lg-6 mb-4 mb-lg-0 border-end">
                    <h6 class="fw-bold text-primary mb-3"><i class="bi bi-box-seam me-2"></i>Daftar Produk yang Dihasilkan</h6>
                    <div class="table-responsive mb-2">
                        <table class="table table-bordered align-middle">
                            <thead class="bg-light text-center small text-uppercase text-muted">
                                <tr>
                                    <th>Produk Jadi</th>
                                    <th width="25%">Qty</th>
                                    <th width="10%"></th>
                                </tr>
                            </thead>
                            <tbody id="tabelProduk">
                                <tr class="baris-produk">
                                    <td>
                                        <select name="produk_id[]" class="form-select border-0 select-produk" onchange="hitungKebutuhanBahan()" required>
                                            <option value="" disabled selected>- Pilih -</option>
                                            <?php foreach($produk as $p): ?>
                                                <option value="<?= $p['produk_jadi_id'] ?>"><?= $p['nama_produk'] ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </td>
                                    <td>
                                        <input type="number" name="jumlah_hasil[]" class="form-control text-center border-0 input-qty-prod" value="0" min="1" oninput="hitungKebutuhanBahan()" required>
                                    </td>
                                    <td class="text-center">
                                        <button type="button" class="btn btn-light text-danger btn-sm" onclick="hapusBarisProduk(this)"><i class="bi bi-trash"></i></button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <button type="button" class="btn btn-outline-primary btn-sm fw-bold border-dashed w-100" onclick="tambahBarisProduk()">
                        <i class="bi bi-plus-lg me-1"></i> Tambah Baris Produk
                    </button>
                </div>

                <div class="col-lg-6 ps-lg-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 class="fw-bold text-danger mb-0"><i class="bi bi-basket me-2"></i>Bahan Baku Terpakai</h6>
                        <button type="button" class="btn btn-sm btn-link text-decoration-none text-danger fw-bold p-0" onclick="tambahBahanManual()">
                            + Tambah Manual
                        </button>
                    </div>

                    <div class="table-responsive bg-light rounded border p-2" style="min-height: 150px;">
                        <table class="table table-sm table-borderless align-middle mb-0">
                            <thead class="text-muted small border-bottom">
                                <tr>
                                    <th class="ps-2">Nama Bahan</th>
                                    <th class="text-center" width="30%">Estimasi (Unit)</th>
                                    <th width="10%"></th>
                                </tr>
                            </thead>
                            <tbody id="tabelBahan">
                                <tr>
                                    <td colspan="3" class="text-center text-muted py-4 small fst-italic">
                                        Pilih produk di sebelah kiri,<br>bahan akan muncul otomatis.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="form-text small text-end mt-1 fst-italic">
                        *Angka dapat diedit sesuai kebutuhan riil.
                    </div>
                </div>
            </div> 
        </div> 

        <div class="card-footer bg-light p-4 border-top">
            <div class="row align-items-center">
                <div class="col-md-6 text-muted small">
                    <i class="bi bi-info-circle me-1"></i> 
                    Pastikan stok bahan baku mencukupi sebelum menyimpan.
                </div>
                <div class="col-md-6 text-end">
                    <div class="d-inline-block bg-white border rounded px-3 py-2 shadow-sm">
                        <a href="index.php" class="btn btn-secondary fw-bold me-2">Batal</a>
                        <button type="submit" class="btn btn-primary fw-bold px-4">
                            Simpan Sesi Produksi
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>

<script>
    let resepCache = {}; 
    let bahanList = <?php echo json_encode($bahan); ?>;

    function tambahBarisProduk() {
        const row = document.querySelector('.baris-produk').cloneNode(true);
        row.querySelector('select').value = "";
        row.querySelector('.input-qty-prod').value = "0";
        document.getElementById('tabelProduk').appendChild(row);
    }

    function hapusBarisProduk(btn) {
        if(document.querySelectorAll('.baris-produk').length > 1) {
            btn.closest('tr').remove();
            hitungKebutuhanBahan();
        } else {
            alert("Minimal satu produk harus diproduksi!");
        }
    }

    async function hitungKebutuhanBahan() {
        let kebutuhanTotal = {}; 
        const rows = document.querySelectorAll('.baris-produk');
        
        for (let row of rows) {
            const pid = row.querySelector('.select-produk').value;
            const qty = parseFloat(row.querySelector('.input-qty-prod').value) || 0;

            if (pid && qty > 0) {
                if (!resepCache[pid]) {
                    try {
                        const res = await fetch('get_resep.php?id=' + pid);
                        resepCache[pid] = await res.json();
                    } catch (e) { console.error("Gagal ambil resep"); }
                }

                if (resepCache[pid]) {
                    resepCache[pid].forEach(item => {
                        let butuh = item.jumlah_butuh_per_unit * qty;
                        if (kebutuhanTotal[item.bahan_baku_id]) {
                            kebutuhanTotal[item.bahan_baku_id].qty += butuh;
                        } else {
                            kebutuhanTotal[item.bahan_baku_id] = {
                                nama: item.nama_bahan,
                                stok: item.stok_bahan,
                                qty: butuh
                            };
                        }
                    });
                }
            }
        }
        renderTabelBahan(kebutuhanTotal);
    }

    function renderTabelBahan(kebutuhanTotal) {
        const tbody = document.getElementById('tabelBahan');
        tbody.innerHTML = '';

        if (Object.keys(kebutuhanTotal).length === 0) {
            tbody.innerHTML = '<tr><td colspan="3" class="text-center text-muted py-4 small fst-italic">Pilih produk di sebelah kiri,<br>bahan akan muncul otomatis.</td></tr>';
            return;
        }

        for (let [bid, data] of Object.entries(kebutuhanTotal)) {
            const html = `
                <tr>
                    <td class="ps-2">
                        <input type="hidden" name="bahan_id[]" value="${bid}">
                        <div class="fw-bold text-dark text-truncate" style="max-width: 150px;">${data.nama}</div>
                        <div class="text-muted" style="font-size: 0.7rem;">Stok: ${data.stok}</div>
                    </td>
                    <td>
                        <input type="number" name="jumlah_pakai[]" 
                               class="form-control form-control-sm fw-bold text-danger text-center bg-white border" 
                               value="${data.qty}" min="0" oninput="validasiMin(this)" required>
                    </td>
                    <td class="text-center text-muted">-</td>
                </tr>
            `;
            tbody.insertAdjacentHTML('beforeend', html);
        }
    }

    function tambahBahanManual() {
        let options = '<option value="" disabled selected>- Pilih -</option>';
        bahanList.forEach(b => {
            options += `<option value="${b.bahan_baku_id}">${b.nama_bahan}</option>`;
        });

        const html = `
            <tr>
                <td class="ps-2">
                    <select name="bahan_id[]" class="form-select form-select-sm border py-0" style="font-size: 0.85rem;" required>${options}</select>
                </td>
                <td>
                    <input type="number" name="jumlah_pakai[]" class="form-control form-control-sm fw-bold text-danger text-center bg-white border" value="0" min="0" oninput="validasiMin(this)" required>
                </td>
                <td class="text-center">
                    <button type="button" class="btn btn-sm btn-link text-danger p-0" onclick="this.closest('tr').remove()"><i class="bi bi-x"></i></button>
                </td>
            </tr>
        `;
        document.getElementById('tabelBahan').insertAdjacentHTML('beforeend', html);
    }

    function validasiMin(input) {
        if (input.value < 0) {
            input.value = 0;
            alert("Jumlah tidak boleh negatif!");
        }
    }
</script>

<?php include '../layout/footer.php'; ?>