<head>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" />
</head>
<?php
// Pastikan koneksi sudah dibuat
include 'koneksi.php';

// Ambil data produk
$query = mysqli_query($conn, "
  SELECT 
    produk.*,
    kategori.nama_kategori
  FROM produk
  LEFT JOIN kategori ON produk.id_kategori = kategori.id_kategori
  ORDER BY id_produk DESC
");

// Fungsi untuk membuat slug
function makeSlug($text) {
    $text = strtolower(trim($text));
    $text = preg_replace('/[^a-z0-9\s-]/', '', $text);
    $text = preg_replace('/[\s-]+/', '-', $text);
    return trim($text, '-');
}

// Array untuk menyimpan produk
$products = [];

if (!$query) {
    die("Query error: " . mysqli_error($conn));
}

// Loop untuk setiap produk
while ($row = mysqli_fetch_assoc($query)) {
    $product_id = $row['id_produk'];

    // Hitung rata-rata rating dari review
    $avg_query = mysqli_query($conn, "SELECT AVG(rating) AS avg_rating FROM review WHERE id_produk = $product_id");
    $avg_result = mysqli_fetch_assoc($avg_query);
    $avg_rating = $avg_result['avg_rating'] ?? 0;

    // Update kolom rating di tabel produk
    mysqli_query($conn, "UPDATE produk SET rating = $avg_rating WHERE id_produk = $product_id");

    // Buat slug secara dinamis dari nama produk
    $slug = makeSlug($row['nama_produk']);

    // Tambahkan ke array produk
    $products[] = [
        'id'       => $row['id_produk'],
        'name'     => $row['nama_produk'],
        'slug'     => $slug, // dari fungsi
        'price'    => $row['harga_produk'],
        'rating'   => $avg_rating,
        'category' => $row['nama_kategori'],
        'image'    => 'assets/images/' . $row['gambar_produk'],
        'desc'     => $row['deskripsi_produk'],
        'stock'    => $row['stok_produk']
    ];
}
// Fungsi format rupiah
function formatRupiah($angka) {
    return 'Rp ' . number_format($angka, 0, ',', '.');
}

// Fungsi cari produk berdasarkan slug
function getProductBySlug($slug, $products) {
    foreach ($products as $product) {
        if (($product['slug'] ?? '') === $slug) {
            return $product;
        }
    }
    return null;
}
?>