<?php
// koneksi.php

$host     = "localhost";
$username = "root";
$password = "";
$database = "mayflorist";

// Membuat koneksi
$conn = mysqli_connect($host, $username, $password, $database);

// 1. Cek koneksi terlebih dahulu sebelum melakukan query apa pun
if (!$conn) {
    die("Koneksi database gagal: " . mysqli_connect_error());
}

// 2. Atur timezone untuk PHP
date_default_timezone_set('Asia/Jakarta');

// 3. Atur timezone untuk sesi MySQL
mysqli_query($conn, "SET time_zone = '+07:00'");
?>