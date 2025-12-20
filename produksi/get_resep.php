<?php
// Set header biar browser tau ini data JSON
header('Content-Type: application/json');

// Koneksi Database
include '../config/db.php';

if (isset($_GET['id'])) {
    $id = $_GET['id'];

    // Ambil data resep + stok bahan baku saat ini
    // Kita join tabel resep_produk dengan bahan_baku
    $stmt = $pdo->prepare("
        SELECT 
            rp.bahan_baku_id, 
            rp.jumlah_butuh_per_unit, 
            b.nama_bahan, 
            b.stok_bahan 
        FROM resep_produk rp
        JOIN bahan_baku b ON rp.bahan_baku_id = b.bahan_baku_id
        WHERE rp.produk_jadi_id = :id
    ");
    
    $stmt->execute(['id' => $id]);
    $resep = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Kirim balik data dalam format JSON
    echo json_encode($resep);
} else {
    // Kalau gak ada ID, balikin array kosong
    echo json_encode([]);
}
?>