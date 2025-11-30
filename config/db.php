<?php
$host = "localhost";
$port = "5432";
$dbname = "ManajemenUsahaRumahan";
$user = "postgres";
$password = "your_password";

try {
    $dsn = "pgsql:host=$host;port=$port;dbname=$dbname";
    $pdo = new PDO($dsn, $user, $password);

    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Koneksi gagal: " . $e->getMessage());
}

$base_url = "http://localhost/project"; 
?>