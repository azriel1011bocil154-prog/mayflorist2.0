<?php
// koneksi.php

$host     = "localhost";
$username = "root";
$password = "";
$database = "mayflorist";

// Membuat koneksi
$conn = mysqli_connect($host, $username, $password, $database);

// Cek koneksi
if (!$conn) {
    die("Koneksi database gagal: " . mysqli_connect_error());
}

// Optional: set timezone
date_default_timezone_set('Asia/Jakarta');
?>