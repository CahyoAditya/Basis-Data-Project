<?php
// Cek alamat dasar biar link gak error
if (!isset($base_url)) {
    $base_url = "http://localhost/usaharumahan";
}

$current_page = basename($_SERVER['PHP_SELF']);
$current_dir = basename(dirname($_SERVER['PHP_SELF']));
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistem Usaha Rumahan</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="<?= $base_url ?>/assets/css/style.css">
</head>
<body>

<div class="d-flex" id="wrapper">
    
    <div class="border-end" id="sidebar-wrapper">
        <div class="sidebar-heading">
            <i class="bi bi-shop-window text-primary"></i> 
            <span class="sidebar-text ms-2">Usaha Rumahan</span>
        </div>
        
        <div class="list-group list-group-flush mt-2">
            
            <a href="<?= $base_url ?>/index.php" 
               class="list-group-item list-group-item-action <?= ($current_page == 'index.php' && $current_dir == 'usaharumahan') ? 'active-link' : '' ?>">
                <i class="bi bi-speedometer2" title="Dashboard"></i> 
                <span class="sidebar-text">Dashboard</span>
            </a>

            <div class="sidebar-text px-3 mt-3 mb-1 text-white-50 small fw-bold text-uppercase" style="font-size: 0.7rem; letter-spacing: 0.5px;">
                Transaksi & Produksi
            </div>

            <a href="<?= $base_url ?>/pembelian/index.php" 
                class="list-group-item list-group-item-action <?= ($current_dir == 'pembelian') ? 'active-link' : '' ?>">
                <i class="bi bi-cart-plus" title="Transaksi Pembelian"></i> 
                <span class="sidebar-text">Transaksi Pembelian</span>
            </a>

            <a href="<?= $base_url ?>/produksi/index.php" 
                class="list-group-item list-group-item-action <?= ($current_dir == 'produksi') ? 'active-link' : '' ?>">
                <i class="bi bi-gear-wide-connected" title="Sesi Produksi"></i> 
                <span class="sidebar-text">Sesi Produksi</span>
            </a>

            <a href="<?= $base_url ?>/penjualan/index.php" 
                class="list-group-item list-group-item-action <?= ($current_dir == 'penjualan') ? 'active-link' : '' ?>">
                <i class="bi bi-receipt" title="Penjualan"></i> 
                <span class="sidebar-text">Penjualan</span>
            </a>

            <div class="sidebar-text px-3 mt-3 mb-1 text-white-50 small fw-bold text-uppercase" style="font-size: 0.7rem; letter-spacing: 0.5px;">
                Stok
            </div>

            <a href="<?= $base_url ?>/bahan_baku/index.php" 
               class="list-group-item list-group-item-action <?= ($current_dir == 'bahan_baku') ? 'active-link' : '' ?>">
                <i class="bi bi-box-seam" title="Bahan Baku"></i> 
                <span class="sidebar-text">Bahan Baku</span>
            </a>

            <a href="<?= $base_url ?>/produk_jadi/index.php" 
                class="list-group-item list-group-item-action <?= ($current_dir == 'produk_jadi') ? 'active-link' : '' ?>">
                <i class="bi bi-box-seam-fill" title="Produk Jadi"></i> 
                <span class="sidebar-text">Produk Jadi</span>
            </a>

            <div class="sidebar-text px-3 mt-3 mb-1 text-white-50 small fw-bold text-uppercase" style="font-size: 0.7rem; letter-spacing: 0.5px;">
                Data Master
            </div>

            <a href="<?= $base_url ?>/pemasok/index.php" 
               class="list-group-item list-group-item-action <?= ($current_dir == 'pemasok') ? 'active-link' : '' ?>">
                <i class="bi bi-truck" title="Data Pemasok"></i> 
                <span class="sidebar-text">Data Pemasok</span>
            </a>
            
            <a href="<?= $base_url ?>/karyawan/index.php" 
                class="list-group-item list-group-item-action <?= ($current_dir == 'karyawan') ? 'active-link' : '' ?>">
                <i class="bi bi-people" title="Data Karyawan"></i> 
                <span class="sidebar-text">Data Karyawan</span>
            </a>

            <a href="<?= $base_url ?>/pelanggan/index.php" 
                class="list-group-item list-group-item-action <?= ($current_dir == 'pelanggan') ? 'active-link' : '' ?>">
                <i class="bi bi-people-fill" title="Pelanggan"></i> 
                <span class="sidebar-text">Pelanggan</span>
            </a>
            
            <div class="mb-5"></div>
        </div>
    </div>

    <div id="page-content-wrapper">
        
        <nav class="top-navbar">
            <button class="btn btn-light shadow-sm" id="sidebarToggle">
                <i class="bi bi-list fs-5"></i>
            </button>
            
            <div class="d-flex align-items-center">
                <div class="text-end me-3 d-none d-sm-block">
                    <small class="text-muted d-block text-uppercase" style="font-size: 0.7rem; letter-spacing: 1px;">Hari ini</small>
                    <span class="fw-bold text-dark">
                        <?= date('l, d F Y') ?>
                    </span>
                </div>
                
                <div class="bg-light text-primary icon-circle d-flex align-items-center justify-content-center border" style="width: 40px; height: 40px;">
                    <i class="bi bi-calendar-event"></i>
                </div>
            </div>
        </nav>

        <div class="container-fluid p-4">