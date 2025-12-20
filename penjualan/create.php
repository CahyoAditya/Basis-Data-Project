<?php
include '../config/db.php';
include '../layout/header.php';

// 1. Ambil Data Pelanggan
$sqlPel = "SELECT p.pelanggan_id, 
                  COALESCE(per.nama_perorangan, b.nama_bisnis) AS nama_pelanggan,
                  CASE WHEN per.nama_perorangan IS NOT NULL THEN 'Perorangan' ELSE 'Bisnis' END AS tipe
           FROM pelanggan p
           LEFT JOIN perorangan per ON p.pelanggan_id = per.pelanggan_id
           LEFT JOIN bisnis b ON p.pelanggan_id = b.pelanggan_id
           ORDER BY nama_pelanggan ASC";
$pelanggan = $pdo->query($sqlPel)->fetchAll();

// 2. Ambil Data Produk
$produk = $pdo->query("SELECT * FROM produk_jadi ORDER BY nama_produk ASC")->fetchAll();

// 3. Auto ID (TPJxxx)
$prefix = "TPJ";
$queryCheck = $pdo->prepare("SELECT transaksi_penjualan_id FROM transaksi_penjualan WHERE transaksi_penjualan_id LIKE :prefix ORDER BY transaksi_penjualan_id DESC LIMIT 1");
$queryCheck->execute(['prefix' => $prefix . '%']);
$lastId = $queryCheck->fetchColumn();
$newId = $lastId ? $prefix . str_pad(((int) substr($lastId, 3)) + 1, 3, "0", STR_PAD_LEFT) : $prefix . "001";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        $pdo->beginTransaction();

        // Validasi Duplikat Produk
        $pids = $_POST['produk_id'];
        // Filter array agar yang kosong tidak ikut dicek
        $pids = array_filter($pids); 

        if (count($pids) !== count(array_unique($pids))) {
            throw new Exception("Terdeteksi produk ganda! Silakan gabungkan jumlahnya dalam satu baris.");
        }

        // Validasi Qty Minus
        foreach($_POST['jumlah'] as $qty) {
            if ($qty <= 0) throw new Exception("Jumlah barang tidak boleh 0 atau negatif!");
        }

        // Insert Header
        $grandTotal = 0;
        foreach($_POST['subtotal'] as $sub) $grandTotal += $sub;

        $stmtHead = $pdo->prepare("INSERT INTO transaksi_penjualan (transaksi_penjualan_id, pelanggan_id, tanggal_penjualan, total_harga_jual) VALUES (:tid, :pid, :tgl, :total)");
        $stmtHead->execute([
            'tid' => $_POST['trans_id'],
            'pid' => $_POST['pelanggan_id'],
            'tgl' => $_POST['tanggal'],
            'total' => $grandTotal
        ]);

        // Insert Detail & Update Stok
        $stmtDet = $pdo->prepare("INSERT INTO detail_penjualan (transaksi_penjualan_id, produk_jadi_id, jumlah, sub_total_penjualan) VALUES (:tid, :pid, :qty, :sub)");
        $stmtStok = $pdo->prepare("UPDATE produk_jadi SET stok_tersedia = stok_tersedia - :qty WHERE produk_jadi_id = :pid");

        $pids = $_POST['produk_id'];
        $qtys = $_POST['jumlah']; 
        $subs = $_POST['subtotal'];

        for ($i=0; $i < count($pids); $i++) {
            if ($qtys[$i] > 0) {
                $stmtDet->execute([
                    'tid' => $_POST['trans_id'],
                    'pid' => $pids[$i],
                    'qty' => $qtys[$i],
                    'sub' => $subs[$i]
                ]);
                
                $stmtStok->execute([
                    'qty' => $qtys[$i],
                    'pid' => $pids[$i]
                ]);
            }
        }

        $pdo->commit();
        echo "<script>alert('Transaksi Penjualan Berhasil Disimpan!'); window.location='create.php';</script>";

    } catch (Exception $e) {
        $pdo->rollBack();
        $error = strpos($e->getMessage(), 'stok_tersedia') !== false ? "Gagal: Stok produk tidak mencukupi!" : "Gagal: " . $e->getMessage();
    }
}
?>

<form method="POST">
    
    <div class="card shadow-sm border-0 mb-4">
        
        <div class="card-header bg-primary text-white py-3">
            <h5 class="mb-0 fw-bold"><i class="bi bi-cart-check-fill me-2"></i>Catat Penjualan Baru</h5>
        </div>

        <div class="card-body p-4">
            
            <?php if(isset($error)): ?>
                <div class="alert alert-danger mb-4"><i class="bi bi-exclamation-triangle-fill me-2"></i><?= $error ?></div>
            <?php endif; ?>

            <div class="row mb-4">
                <div class="col-md-4">
                    <label class="form-label text-muted small fw-bold">ID Transaksi</label>
                    <input type="text" name="trans_id" class="form-control bg-light fw-bold text-primary" value="<?= $newId ?>" readonly>
                </div>
                <div class="col-md-4">
                    <label class="form-label text-muted small fw-bold">Tanggal</label>
                    <input type="date" name="tanggal" class="form-control" value="<?= date('Y-m-d') ?>" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label text-muted small fw-bold">Pelanggan</label>
                    <select name="pelanggan_id" class="form-select" required>
                        <option value="" disabled selected>-- Pilih Pelanggan --</option>
                        <?php foreach($pelanggan as $p): ?>
                            <option value="<?= $p['pelanggan_id'] ?>">
                                <?= htmlspecialchars($p['nama_pelanggan']) ?> (<?= $p['tipe'] ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <h6 class="fw-bold text-primary mb-3"><i class="bi bi-list-check me-2"></i>Daftar Produk Terjual</h6>
            
            <div class="table-responsive mb-3">
                <table class="table table-bordered align-middle" id="tabelJual">
                    <thead class="bg-light text-center small text-uppercase text-muted">
                        <tr>
                            <th width="40%">Nama Produk</th>
                            <th width="15%">Harga (Rp)</th>
                            <th width="15%">Qty</th>
                            <th width="20%">Subtotal (Rp)</th>
                            <th width="10%">Aksi</th>
                        </tr>
                    </thead>
                    <tbody id="bodyJual">
                        <tr>
                            <td>
                                <select name="produk_id[]" class="form-select border-0 produk-select" onchange="updateHarga(this)" required>
                                    <option value="" data-harga="0" selected>- Pilih Produk -</option>
                                    <?php foreach($produk as $pr): ?>
                                        <option value="<?= $pr['produk_jadi_id'] ?>" data-harga="<?= $pr['harga_jual'] ?>">
                                            <?= $pr['nama_produk'] ?> (Stok: <?= $pr['stok_tersedia'] ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                            <td>
                                <input type="text" class="form-control bg-light text-end border-0 harga-satuan" value="0" readonly>
                            </td>
                            <td>
                                <input type="number" name="jumlah[]" class="form-control text-center border-0 qty-input" min="1" value="1" oninput="hitungSubtotal(this)" required>
                            </td>
                            <td>
                                <input type="number" name="subtotal[]" class="form-control bg-light text-end fw-bold border-0 subtotal-input" value="0" readonly>
                            </td>
                            <td class="text-center">
                                <button type="button" class="btn btn-light text-danger btn-sm" onclick="hapusBaris(this)"><i class="bi bi-trash"></i></button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <button type="button" class="btn btn-outline-primary btn-sm border-dashed fw-bold" onclick="tambahBaris()">
                <i class="bi bi-plus-lg me-1"></i> Tambah Baris Produk
            </button>

        </div> <div class="card-footer bg-light p-4 border-top">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <h5 class="mb-0 fw-bold text-muted small text-uppercase">Grand Total Penjualan:</h5>
                    <h3 class="fw-bold text-primary mb-0" id="grandTotalText">Rp 0</h3>
                </div>
                <div class="col-md-6 text-end">
                    <div class="d-inline-block bg-white border rounded px-3 py-2 shadow-sm">
                        <a href="index.php" class="btn btn-secondary fw-bold me-2">Batal</a>
                        <button type="submit" class="btn btn-primary fw-bold px-4">
                            Simpan Transaksi
                        </button>
                    </div>
                </div>
            </div>
        </div>

    </div>
</form>

<script>
    function updateHarga(select) {
        const row = select.closest('tr');
        const harga = select.options[select.selectedIndex].getAttribute('data-harga');
        row.querySelector('.harga-satuan').value = harga;
        hitungSubtotal(row.querySelector('.qty-input'));
    }

    function hitungSubtotal(input) {
        if(input.value < 0) { input.value = 1; alert("Qty tidak boleh negatif"); }
        const row = input.closest('tr');
        const harga = parseFloat(row.querySelector('.harga-satuan').value) || 0;
        const qty = parseFloat(input.value) || 0;
        const sub = harga * qty;
        row.querySelector('.subtotal-input').value = sub;
        hitungGrandTotal();
    }

    function hitungGrandTotal() {
        let total = 0;
        document.querySelectorAll('.subtotal-input').forEach(input => total += parseFloat(input.value) || 0);
        document.getElementById('grandTotalText').innerText = 'Rp ' + total.toLocaleString('id-ID');
    }

    function tambahBaris() {
        const row = document.querySelector('#bodyJual tr').cloneNode(true);
        row.querySelectorAll('input').forEach(i => i.value = ''); 
        row.querySelector('select').selectedIndex = 0; 
        row.querySelector('.qty-input').value = 1; 
        row.querySelector('.harga-satuan').value = 0;
        row.querySelector('.subtotal-input').value = 0;
        document.getElementById('bodyJual').appendChild(row);
    }
    
    function hapusBaris(btn) {
        if(document.querySelectorAll('#bodyJual tr').length > 1) {
            btn.closest('tr').remove();
            hitungGrandTotal();
        }
    }
</script>

<?php include '../layout/footer.php'; ?>