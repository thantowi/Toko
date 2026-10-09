
<?php
require "koneksi.php";

$error = "";
$id = filter_input(INPUT_GET, "id", FILTER_VALIDATE_INT);

if (!$id || $id < 1) {
    exit("ID produk tidak valid.");
}

// 1. Ambil data produk berdasarkan ID
$stmt = $conn->prepare(
    "SELECT * FROM products WHERE id = ?"
);
$stmt->bind_param("i", $id);
$stmt->execute();

$result = $stmt->get_result();
$produk = $result->fetch_assoc();
$stmt->close();

if (!$produk) {
    exit("Produk tidak ditemukan.");
}

// Simpan nama gambar lama
$gambarLama = $produk["gambar"] ?? "";

// 2. Proses ketika tombol Simpan Perubahan diklik
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $nama = trim($_POST["nama_produk"] ?? "");
    $deskripsi = trim($_POST["deskripsi"] ?? "");
    $kategori = trim($_POST["kategori"] ?? "");
    $hargaInput = $_POST["harga"] ?? "";
    $stokInput = $_POST["stok"] ?? "";

    $gambarBaru = $gambarLama;
    $fileBaru = "";

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
        $error = "Periksa kembali nama, kategori, harga, dan stok.";
    }

    // 3. Jika pengguna memilih gambar baru
    if (
        $error === "" &&
        isset($_FILES["gambar"]) &&
        $_FILES["gambar"]["error"] !== UPLOAD_ERR_NO_FILE
    ) {
        if ($_FILES["gambar"]["error"] !== UPLOAD_ERR_OK) {
            $error = "Gagal mengunggah gambar.";
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

            if (
                !isset($allowed[$mime]) ||
                @getimagesize($tmp) === false
            ) {
                $error = "Gunakan gambar JPG, PNG, atau WEBP.";
            } else {
                $fileBaru = bin2hex(random_bytes(16))
                    . "." . $allowed[$mime];

                $folder = __DIR__ . "/uploads/";

                if (!is_dir($folder) || !is_writable($folder)) {
                    $error = "Folder uploads tidak tersedia.";
                } elseif (!move_uploaded_file(
                    $tmp,
                    $folder . $fileBaru
                )) {
                    $error = "Gagal menyimpan gambar baru.";
                } else {
                    $gambarBaru = $fileBaru;
                }
            }
        }
    }

    // 4. Perbarui data di database
    if ($error === "") {
        $harga = (float)$hargaInput;
        $stok = (int)$stokInput;

        $stmt = $conn->prepare(
            "UPDATE products
             SET nama_produk = ?,
                 deskripsi = ?,
                 gambar = ?,
                 kategori = ?,
                 harga = ?,
                 stok = ?
             WHERE id = ?"
        );

        $stmt->bind_param(
            "ssssdii",
            $nama,
            $deskripsi,
            $gambarBaru,
            $kategori,
            $harga,
            $stok,
            $id
        );

        if ($stmt->execute()) {
            $stmt->close();

            // Hapus gambar lama jika diganti gambar baru
            if ($fileBaru !== "" && $gambarLama !== "") {
                $namaFileLama = basename($gambarLama);
                $pathLama = __DIR__ . "/uploads/" . $namaFileLama;

                if (is_file($pathLama)) {
                    @unlink($pathLama);
                }
            }

            header("Location: index.php");
            exit;
        }

        $stmt->close();

        // Hapus gambar baru jika penyimpanan database gagal
        if ($fileBaru !== "") {
            @unlink(__DIR__ . "/uploads/" . $fileBaru);
        }

        $error = "Gagal memperbarui produk.";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Edit Produk</title>
    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >
</head>
<body class="bg-light">

<div class="container py-5">
    <div class="card shadow-sm mx-auto" style="max-width: 650px">
        <div class="card-body p-4">

            <h2 class="mb-4">Edit Produk</h2>

            <?php if ($error !== ""): ?>
                <div class="alert alert-danger">
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data">

                <div class="mb-3">
                    <label class="form-label">Nama Produk</label>
                    <input
                        type="text"
                        name="nama_produk"
                        class="form-control"
                        maxlength="150"
                        value="<?= htmlspecialchars($produk["nama_produk"]) ?>"
                        required
                    >
                </div>

                <div class="mb-3">
                    <label class="form-label">Deskripsi</label>
                    <textarea
                        name="deskripsi"
                        class="form-control"
                        rows="3"
                    ><?= htmlspecialchars($produk["deskripsi"] ?? "") ?></textarea>
                </div>

                <div class="mb-3">
                    <label class="form-label">Gambar Saat Ini</label><br>

                    <?php if (!empty($gambarLama)): ?>
                        <img
                            src="uploads/<?= rawurlencode(basename($gambarLama)) ?>"
                            alt="Gambar produk"
                            width="120"
                            height="120"
                            style="object-fit: cover;"
                            class="mb-2"
                        >
                    <?php else: ?>
                        <p>Belum ada gambar.</p>
                    <?php endif; ?>

                    <input
                        type="file"
                        name="gambar"
                        class="form-control mt-2"
                        accept=".jpg,.jpeg,.png,.webp"
                    >

                    <div class="form-text">
                        Kosongkan jika tidak ingin mengganti gambar.
                        Format JPG, PNG, atau WEBP; maksimal 2 MB.
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Kategori</label>
                    <input
                        type="text"
                        name="kategori"
                        class="form-control"
                        maxlength="100"
                        value="<?= htmlspecialchars($produk["kategori"] ?? "") ?>"
                    >
                </div>

                <div class="mb-3">
                    <label class="form-label">Harga (Rp)</label>
                    <input
                        type="number"
                        name="harga"
                        class="form-control"
                        min="0"
                        step="0.01"
                        value="<?= htmlspecialchars((string)$produk["harga"]) ?>"
                        required
                    >
                </div>

                <div class="mb-3">
                    <label class="form-label">Stok</label>
                    <input
                        type="number"
                        name="stok"
                        class="form-control"
                        min="0"
                        step="1"
                        value="<?= htmlspecialchars((string)$produk["stok"]) ?>"
                        required
                    >
                </div>

                <button type="submit" class="btn btn-primary">
                    Simpan Perubahan
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