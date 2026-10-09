
<?php
include "koneksi.php";

if (isset($_GET["id"])) {
    $id = (int) $_GET["id"];

    $stmt = $conn->prepare("DELETE FROM products WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
}

header("Location: index.php");
exit;
?>