
CREATE DATABASE db_produk;

USE db_produk;

CREATE TABLE products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama_produk VARCHAR(150) NOT NULL,
    deskripsi TEXT,
    gambar VARCHAR(255),
    kategori VARCHAR(100),
    harga DECIMAL(12,2) NOT NULL DEFAULT 0,
    stok INT NOT NULL DEFAULT 0
);
