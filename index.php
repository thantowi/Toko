
<?php
require "koneksi.php";

$keyword = trim($_GET['keyword'] ?? '');
$kategori = trim($_GET['kategori'] ?? '');

$query = "SELECT * FROM products WHERE 1=1";
$params = [];
$types = "";

if ($keyword !== '') {
    $query .= " AND nama_produk LIKE ?";
    $params[] = "%{$keyword}%";
    $types .= "s";
}

if ($kategori !== '') {
    $query .= " AND kategori = ?";
    $params[] = $kategori;
    $types .= "s";
}

$stmt = $conn->prepare($query);

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$urut = $_GET['urut'] ?? '';

$daftarUrutan = [
    'nama' => 'nama_produk ASC',
    'harga_asc' => 'harga ASC',
    'harga_desc' => 'harga DESC',
    'stok' => 'stok DESC'
];

if (isset($daftarUrutan[$urut])) {
    $query .= " ORDER BY " . $daftarUrutan[$urut];
}
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Data Produk</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
          rel="stylesheet">
</head>
<body>
    
    <div class="container mt-5">
        <div class="d-flex justify-content-between align-items-center mb-3">
    <h2>Daftar Produk</h2>
    <a href="tambah.php" class="btn btn-primary">
        + Tambah Produk
    </a>
</div>

<form method="GET" action="index.php" class="mb-3">
    <div class="row g-2">


        <div class="col-md-5">
            <input
                type="text"
                name="keyword"
                class="form-control"
                placeholder="Cari nama produk..."
                value="<?= htmlspecialchars($_GET['keyword'] ?? '') ?>"
            >
        </div>
        

        <div class="col-md-4">
            <select
                name="kategori"
                class="form-select"
            >
                <option value="">Semua Kategori</option>

                <?php
                $kategoriQuery = $conn->query(
                    "SELECT DISTINCT kategori FROM products
                     WHERE kategori IS NOT NULL AND kategori != ''
                     ORDER BY kategori ASC"
                );

                while ($kat = $kategoriQuery->fetch_assoc()):
                ?>
                    <option
                        value="<?= htmlspecialchars($kat['kategori']) ?>"
                        <?= (($_GET['kategori'] ?? '') == $kat['kategori'])
                            ? 'selected' : '' ?>
                    >
                        <?= htmlspecialchars($kat['kategori']) ?>
                    </option>
                <?php endwhile; ?>
            </select>
        </div>
        
<div class="col-md-3">
    <select name="urut" class="form-select">
        <option value="">Urutkan berdasarkan...</option>
        <option value="nama" <?= ($_GET['urut'] ?? '') == 'nama' ? 'selected' : '' ?>>
            Nama A-Z
        </option>
        <option value="harga_asc" <?= ($_GET['urut'] ?? '') == 'harga_asc' ? 'selected' : '' ?>>
            Harga Termurah
        </option>
        <option value="harga_desc" <?= ($_GET['urut'] ?? '') == 'harga_desc' ? 'selected' : '' ?>>
            Harga Termahal
        </option>
        <option value="stok" <?= ($_GET['urut'] ?? '') == 'stok' ? 'selected' : '' ?>>
            Stok Terbanyak
        </option>
    </select>
</div>

        <div class="col-md-3">
            <button type="submit" class="btn btn-primary">
                Cari / Filter
            </button>

            <a href="index.php" class="btn btn-secondary">
                Reset
            </a>
        </div>

    </div>
</form>

        <table class="table table-bordered table-striped mt-3">
            <thead class="table-dark">
                <tr>
                    <th>ID</th>
                    <th>Nama Produk</th>
                    <th>Deskripsi</th>
                    <th>Gambar</th>
                    <th>Kategori</th>
                    <th>Harga</th>
                    <th>Stok</th>
                    <th>Aksi</th>

                </tr>
            </thead>

            <tbody>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?= htmlspecialchars($row["id"]) ?></td>
                        <td><?= htmlspecialchars($row["nama_produk"]) ?></td>
                        <td><?= htmlspecialchars($row["deskripsi"] ?? "") ?></td>
                        <td>
    <?php if (!empty($row["gambar"])): ?>
        <img
            src="uploads/<?= rawurlencode(basename($row["gambar"])) ?>"
            alt="<?= htmlspecialchars($row["nama_produk"]) ?>"
            width="80"
            height="80"
            style="object-fit: cover;"
        >
    <?php else: ?>
        Tidak ada gambar
    <?php endif; ?>
</td>   
                        <td><?= htmlspecialchars($row["kategori"] ?? "") ?></td>
                        <td>Rp <?= number_format((float)$row["harga"], 0, ",", ".") ?></td>
                        <td><?= htmlspecialchars($row["stok"]) ?></td>
                        
<td>
    <a href="edit.php?id=<?= (int)$row['id'] ?>"
       class="btn btn-warning btn-sm">Edit</a>

    <a href="hapus.php?id=<?= (int)$row['id'] ?>"
       class="btn btn-danger btn-sm"
       onclick="return confirm('Yakin ingin menghapus produk ini?')">
       Hapus
    </a>
</td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</body>
</html>