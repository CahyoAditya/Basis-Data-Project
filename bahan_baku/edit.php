<?php
include '../config/db.php';
include '../layout/header.php';

// Ambil ID dari URL, terus tarik datanya dari db buat diisi ke form
$id = $_GET['id'];
$stmt = $pdo->prepare("SELECT * FROM bahan_baku WHERE bahan_baku_id = :id");
$stmt->execute(['id' => $id]);
$data = $stmt->fetch();

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
        header("Location: index.php");
    } catch (PDOException $e) {
        echo "<div class='alert alert-danger'>Error: " . $e->getMessage() . "</div>";
    }
}
?>

<h3>Edit Bahan Baku</h3>

<form method="POST">
    <div class="mb-3">
        <label>ID Bahan</label>
        <input type="text" class="form-control" value="<?= $data['bahan_baku_id'] ?>" disabled>
    </div>
    <div class="mb-3">
        <label>Nama Bahan</label>
        <input type="text" name="nama" class="form-control" value="<?= $data['nama_bahan'] ?>" required>
    </div>
    <div class="mb-3">
        <label>Stok (Koreksi)</label>
        <input type="number" name="stok" class="form-control" min="0" value="<?= $data['stok_bahan'] ?>" required>
    </div>
    <button type="submit" class="btn btn-primary">Update</button>
    <a href="index.php" class="btn btn-secondary">Batal</a>
</form>

<?php include '../layout/footer.php'; ?>