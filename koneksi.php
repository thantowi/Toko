
<?php

$conn = new mysqli(
    "localhost",
    "root",
    "",
    "db_produk"
);

if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");

?>