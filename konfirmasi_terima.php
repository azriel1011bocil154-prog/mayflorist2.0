<?php
// konfirmasi-terima.php
session_start();
include 'koneksi.php';

if (empty($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

if (isset($_GET['no'])) {
    $id_pesanan = mysqli_real_escape_string($conn, $_GET['no']);
    $id_user = $_SESSION['user']['id_user'];

    // Validasi: Pastikan pesanan ini milik user yang login & statusnya memang 'dikirim'
    $cek = mysqli_query($conn, "SELECT * FROM pesanan WHERE id_pesanan = '$id_pesanan' AND id_user = '$id_user' AND status_pesanan = 'dikirim'");
    
    if (mysqli_num_rows($cek) > 0) {
        // Ubah status_pesanan menjadi selesai
        $update = mysqli_query($conn, "UPDATE pesanan SET status_pesanan = 'selesai' WHERE id_pesanan = '$id_pesanan'");
        
        if ($update) {
            header('Location: riwayat.php?status=sukses');
            exit;
        }
    }
}

// Jika ada fraud/salah akses, kembalikan ke halaman pesanan
header('Location: pesanan.php');
exit;