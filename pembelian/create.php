<?php
include '../config/db.php';
include '../layout/header.php';

// Ambil data Pemasok & Bahan Baku buat ngisi pilihan dropdown
$pemasok = $pdo->query("SELECT * FROM pemasok ORDER BY nama_pemasok ASC")->fetchAll();
$bahan = $pdo->query("SELECT * FROM bahan_baku ORDER BY nama_bahan ASC")->fetchAll();

// Cek ID terakhir, terus tambah 1. Kalau belum ada, mulai dari TPB001
$prefix = "TPB";
$queryCheck = $pdo->prepare("SELECT transaksi_pembelian_id FROM transaksi_pembelian WHERE transaksi_pembelian_id LIKE :prefix ORDER BY transaksi_pembelian_id DESC LIMIT 1");
$queryCheck->execute(['prefix' => $prefix . '%']);
$lastId = $queryCheck->fetchColumn();

if ($lastId) {
    $number = (int) substr($lastId, 3);
    $newId = $prefix . str_pad($number + 1, 3, "0", STR_PAD_LEFT);
} else {
    $newId = $prefix . "001";
}

// Buat nyimpen banyak data sekaligus
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        // Mulai transaksi database biar aman
        $pdo->beginTransaction();

        // 1. Hitung total semua belanjaan dari input subtotal
        $grandTotal = 0;
        $subTotals = $_POST['sub_total'];
        foreach ($subTotals as $sub) {
            $grandTotal += (int)$sub;
        }

        // 2. Simpan data utama transaksi
        $sqlH = "INSERT INTO transaksi_pembelian (transaksi_pembelian_id, pemasok_id, tanggal_pembelian, total_harga_beli) 
                 VALUES (:tid, :pid, :tgl, :tot)";
        $stmtH = $pdo->prepare($sqlH);
        $stmtH->execute([
            'tid' => $_POST['trans_id'],
            'pid' => $_POST['pemasok_id'],
            'tgl' => $_POST['tanggal'],
            'tot' => $grandTotal
        ]);

        // 3. Simpan detail barang satu per satu
        $bahanIds = $_POST['bahan_id'];
        $qtys = $_POST['jumlah_beli'];
        
        $sqlD = "INSERT INTO detail_pembelian (transaksi_pembelian_id, bahan_baku_id, sub_total_pembelian, jumlah) 
                 VALUES (:tid, :bid, :sub, :jml)";
        $stmtD = $pdo->prepare($sqlD);

        $sqlUp = "UPDATE bahan_baku SET stok_bahan = stok_bahan + :qty WHERE bahan_baku_id = :bid";
        $stmtUp = $pdo->prepare($sqlUp);

        for ($i = 0; $i < count($bahanIds); $i++) {
            $bid = $bahanIds[$i];
            $qty = $qtys[$i];
            $sub = $subTotals[$i];

            // Masukin ke tabel detail pembelian
            $stmtD->execute([
                'tid' => $_POST['trans_id'],
                'bid' => $bid,
                'sub' => $sub,
                'jml' => $qty
            ]);

            // Jangan lupa update stok di gudang
            $stmtUp->execute([
                'qty' => $qty,
                'bid' => $bid
            ]);
        }

        // Kalau lancar semua, tinggal simpan aja
        $pdo->commit();
        echo "<div class='alert alert-success'>
                <h5 class='fw-bold'><i class='bi bi-check-circle me-2'></i>Transaksi Berhasil!</h5>
                <p>Data pembelian multi-item telah disimpan dan stok diperbarui.</p>
              </div>";
        // Refresh halaman biar form bersih lagi
        echo "<meta http-equiv='refresh' content='2'>"; 

    } catch (Exception $e) {
        // Kalau ada error, batalin semua perubahan
        $pdo->rollBack();
        echo "<div class='alert alert-danger'>Gagal: " . $e->getMessage() . "</div>";
    }
}
?>

<div class="row justify-content-center">
    <div class="col-lg-10">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-primary text-white py-3">
                <h5 class="mb-0"><i class="bi bi-cart-plus-fill me-2"></i>Catat Pembelian (Multi Item)</h5>
            </div>
            <div class="card-body p-4">
                
                <form method="POST" id="formPembelian">
                    
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
                            <label class="form-label text-muted small fw-bold">Pemasok</label>
                            <select name="pemasok_id" class="form-select" required>
                                <option value="" disabled selected>-- Pilih Pemasok --</option>
                                <?php foreach($pemasok as $p): ?>
                                    <option value="<?= $p['pemasok_id'] ?>"><?= $p['nama_pemasok'] ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <hr>

                    <!-- Ini buat tabel input barang (Bisa nambah baris btw) -->
                    <h6 class="text-primary fw-bold mb-3"><i class="bi bi-list-check me-2"></i>Daftar Barang yang Dibeli</h6>
                    
                    <div class="table-responsive mb-3">
                        <table class="table table-bordered align-middle" id="tableItems">
                            <thead class="bg-light text-center">
                                <tr>
                                    <th style="width: 40%;">Bahan Baku</th>
                                    <th style="width: 20%;">Qty</th>
                                    <th style="width: 30%;">Harga Subtotal (Rp)</th>
                                    <th style="width: 10%;">Aksi</th>
                                </tr>
                            </thead>
                            <tbody id="itemsBody">
                                <!-- Baris Pertama -->
                                <tr>
                                    <td>
                                        <select name="bahan_id[]" class="form-select" required>
                                            <option value="" disabled selected>- Pilih -</option>
                                            <?php foreach($bahan as $b): ?>
                                                <option value="<?= $b['bahan_baku_id'] ?>"><?= $b['nama_bahan'] ?> (Stok: <?= $b['stok_bahan'] ?>)</option>
                                            <?php endforeach; ?>
                                        </select>
                                    </td>
                                    <td>
                                        <input type="number" name="jumlah_beli[]" class="form-control text-center" placeholder="0" min="1" required>
                                    </td>
                                    <td>
                                        <div class="input-group">
                                            <span class="input-group-text">Rp</span>
                                            <input type="number" name="sub_total[]" class="form-control subtotal-input" placeholder="0" min="0" required oninput="hitungGrandTotal()">
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        <button type="button" class="btn btn-danger btn-sm" onclick="hapusBaris(this)" disabled><i class="bi bi-trash"></i></button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <button type="button" class="btn btn-outline-primary btn-sm mb-4" onclick="tambahBaris()">
                        <i class="bi bi-plus-lg me-1"></i> Tambah Baris Barang
                    </button>

                    <!-- Tampilan Total & Tombol Simpan -->
                    <div class="row justify-content-end">
                        <div class="col-md-5">
                            <div class="card bg-light border-0 p-3">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <span class="fw-bold text-muted">Grand Total:</span>
                                    <h4 class="fw-bold text-primary m-0" id="displayGrandTotal">Rp 0</h4>
                                </div>
                                <button type="submit" class="btn btn-primary w-100 fw-bold py-2 shadow-sm">
                                    Simpan Semua Transaksi
                                </button>
                            </div>
                        </div>
                    </div>

                </form>
            </div>
        </div>
    </div>
</div>

<!-- Script buat nambah baris & hitung duit otomatis -->
<script>
    function hitungGrandTotal() {
        let total = 0;
        const inputs = document.querySelectorAll('.subtotal-input');
        
        // Jumlahin semua subtotal
        inputs.forEach(input => {
            let val = parseInt(input.value) || 0;
            total += val;
        });

        // Tampilin format Rupiah biar ganteng dikit wkwk
        document.getElementById('displayGrandTotal').innerText = 'Rp ' + total.toLocaleString('id-ID');
    }

    function tambahBaris() {
        const tableBody = document.getElementById('itemsBody');
        const firstRow = tableBody.rows[0];
        // Copy baris pertama
        const newRow = firstRow.cloneNode(true);

        // Bersihin nilai input di baris baru
        const inputs = newRow.querySelectorAll('input');
        inputs.forEach(input => input.value = '');
        
        const selects = newRow.querySelectorAll('select');
        selects.forEach(select => select.selectedIndex = 0);

        // Aktifin tombol hapus
        const btnHapus = newRow.querySelector('button');
        btnHapus.disabled = false;
        btnHapus.onclick = function() { hapusBaris(this); };

        tableBody.appendChild(newRow);
    }

    function hapusBaris(button) {
        const row = button.closest('tr');
        // Sisain minimal satu baris, jangan dihapus semua
        if (document.querySelectorAll('#itemsBody tr').length > 1) {
            row.remove();
            hitungGrandTotal(); // Hitung ulang total
        } else {
            alert("Minimal harus ada satu barang!");
        }
    }
</script>

<?php include '../layout/footer.php'; ?>