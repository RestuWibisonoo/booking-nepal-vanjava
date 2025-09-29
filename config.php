<?php
// Konfigurasi database
$host = "localhost";
$user = "root";        // ubah sesuai user MySQL
$pass = "";            // ubah sesuai password MySQL
$db   = "db_nepalvanjava";

// Koneksi
$conn = mysqli_connect($host, $user, $pass, $db);

// Cek koneksi
if (!$conn) {
    die("Koneksi gagal: " . mysqli_connect_error());
}
?>
