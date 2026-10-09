
<?php
require "koneksi.php";

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $nama = trim($_POST["nama_produk"] ?? "");
    $deskripsi = trim($_POST["deskripsi"] ?? "");
    $kategori = trim($_POST["kategori"] ?? "");
    $hargaInput = $_POST["harga"] ?? "";
    $stokInput = $_POST["stok"] ?? "";

    $gambar = "";

    // Validasi data produk
    if (
        $nama === "" ||
        strlen($nama) > 150 ||
        strlen($kategori) > 100 ||
        !is_numeric($hargaInput) ||
        (float)$hargaInput < 0 ||
        !filter_var($stokInput, FILTER_VALIDATE_INT) ||
        (int)$stokInput < 0
    ) {
        $error = "Periksa kembali nama, harga, dan stok.";
    }

    // Periksa gambar yang diupload
    if ($error === "") {
        if (!isset($_FILES["gambar"]) ||
            $_FILES["gambar"]["error"] !== UPLOAD_ERR_OK) {
            $error = "Pilih gambar terlebih dahulu.";
        } elseif ($_FILES["gambar"]["size"] > 2 * 1024 * 1024) {
            $error = "Ukuran gambar maksimal 2 MB.";
        } else {
            $tmp = $_FILES["gambar"]["tmp_name"];
            $mime = (new finfo(FILEINFO_MIME_TYPE))->file($tmp);

            $allowed = [
                "image/jpeg" => "jpg",
                "image/png"  => "png",
                "image/webp" => "webp"
            ];

            if (!isset($allowed[$mime]) ||
                @getimagesize($tmp) === false) {
                $error = "Gunakan gambar JPG, PNG, atau WEBP.";
            } else {
                $gambar = bin2hex(random_bytes(16))
                    . "." . $allowed[$mime];
            }
        }
    }

    // Simpan gambar dan data produk
    if ($error === "") {
        $folder = __DIR__ . "/uploads/";

        if (!is_dir($folder) || !is_writable($folder)) {
            $error = "Folder uploads tidak ditemukan atau tidak bisa ditulis.";
        } elseif (!move_uploaded_file(
            $_FILES["gambar"]["tmp_name"],
            $folder . $gambar
        )) {
            $error = "Gagal menyimpan file gambar.";
        } else {
            $harga = (float)$hargaInput;
            $stok = (int)$stokInput;

            $stmt = $conn->prepare(
                "INSERT INTO products
                (nama_produk, deskripsi, gambar, kategori, harga, stok)
                VALUES (?, ?, ?, ?, ?, ?)"
            );

            $stmt->bind_param(
                "ssssdi",
                $nama,
                $deskripsi,
                $gambar,
                $kategori,
                $harga,
                $stok
            );

            if ($stmt->execute()) {
                $stmt->close();
                header("Location: index.php");
                exit;
            }

            $stmt->close();

            // Hapus gambar jika data gagal disimpan
            @unlink($folder . $gambar);
            $error = "Gagal menyimpan produk ke database.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Tambah Produk</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet">
</head>

<body class="bg-light">
<div class="container py-5">
    <div class="card shadow-sm mx-auto" style="max-width: 650px">
        <div class="card-body p-4">
            <h2 class="mb-4">Tambah Produk</h2>

            <?php if ($error !== ""): ?>
                <div class="alert alert-danger">
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data">
                <div class="mb-3">
                    <label class="form-label">Nama Produk</label>
                    <input type="text" name="nama_produk"
                           class="form-control" maxlength="150" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Deskripsi</label>
                    <textarea name="deskripsi"
                              class="form-control" rows="3"></textarea>
                </div>

                <div class="mb-3">
                    <label class="form-label">Gambar Produk</label>
                    <input type="file" name="gambar"
                           class="form-control"
                           accept=".jpg,.jpeg,.png,.webp" required>
                    <div class="form-text">
                        Format JPG, PNG, atau WEBP. Maksimal 2 MB.
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Kategori</label>
                    <input type="text" name="kategori"
                           class="form-control" maxlength="100">
                </div>

                <div class="mb-3">
                    <label class="form-label">Harga (Rp)</label>
                    <input type="number" name="harga"
                           class="form-control" min="0"
                           step="0.01" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Stok</label>
                    <input type="number" name="stok"
                           class="form-control" min="0"
                           step="1" required>
                </div>

                <button type="submit" class="btn btn-primary">
                    Simpan Produk
                </button>

                <a href="index.php" class="btn btn-secondary">
                    Kembali
                </a>
            </form>
        </div>
    </div>
</div>
</body>
</html>