<?php
$host = "localhost";
$port = "5432";
$dbname = "manajemenusaharumahan";
$user = "postgres";
$password = "password";

try {
    $dsn = "pgsql:host=$host;port=$port;dbname=$dbname";
    $pdo = new PDO($dsn, $user, $password);

    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Koneksi gagal: " . $e->getMessage());
}

$base_url = "http://localhost/project"; 
?>
