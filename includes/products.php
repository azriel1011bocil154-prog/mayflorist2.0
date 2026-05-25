<?php

include 'koneksi.php';

$products = [];

$query = mysqli_query($conn, "
  SELECT 
    produk.*,
    kategori.nama_kategori
  FROM produk
  LEFT JOIN kategori
  ON produk.id_kategori = kategori.id_kategori
  ORDER BY id_produk DESC
");

if (!$query) {
  die("Query error: " . mysqli_error($conn));
}

while ($row = mysqli_fetch_assoc($query)) {

  $products[] = [

    'id'       => $row['id_produk'],
    'name'     => $row['nama_produk'],
    'price'    => $row['harga_produk'],
    'rating'   => $row['rating'],
    'category' => $row['nama_kategori'],
    'image'    => 'assets/images/' . $row['gambar_produk'],
    'desc'     => $row['deskripsi_produk'],
    'stock'    => $row['stok_produk']

  ];
}

function formatRupiah($angka) {
  return 'Rp ' . number_format($angka, 0, ',', '.');
}

function getProductBySlug($slug, $products) {
    foreach ($products as $product) {
        if (($product['slug'] ?? '') === $slug) {
            return $product;
        }
    }
    return null;
}