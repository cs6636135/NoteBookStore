<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
include "../login/connect.php";

// ตรวจสอบสิทธิ์ admin
if (!isset($_SESSION["role"]) || $_SESSION["role"] !== "admin") {
    echo "<script>
        alert('🚫 คุณไม่มีสิทธิ์เข้าหน้านี้');
        window.location.href='../login/login-form.php';
    </script>";
    exit;
}

// รับค่า id_product จาก URL
$id_product = isset($_GET['id_product']) ? (int) $_GET['id_product'] : 0;

if ($id_product <= 0) {
    echo "<script>
        alert('⚠️ ไม่พบสินค้าเพื่อทำการลบ');
        window.location.href='admin-product.php';
    </script>";
    exit;
}

try {
    // ตรวจสอบว่าสินค้ามีจริง
    $checkStmt = $pdo->prepare("SELECT * FROM Product WHERE id_product = :id_product");
    $checkStmt->execute([':id_product' => $id_product]);
    $product = $checkStmt->fetch(PDO::FETCH_ASSOC);

    if (!$product) {
        echo "<script>
            alert('❌ ไม่พบข้อมูลสินค้าในระบบ (id_product: $id_product)');
            window.location.href='admin-product.php';
        </script>";
        exit;
    }

    // เริ่ม Transaction
    $pdo->beginTransaction();

    // ลบ Order_detail ที่เกี่ยวข้อง
    $pdo->prepare("DELETE FROM Order_detail WHERE id_product = :id_product")
        ->execute([':id_product' => $id_product]);

    // ลบสินค้า
    $deleteStmt = $pdo->prepare("DELETE FROM Product WHERE id_product = :id_product");
    $deleteStmt->execute([':id_product' => $id_product]);

    // ลบรูปสินค้าถ้ามี
    $filePath = "../product/product_photo/" . $id_product . ".jpg";
    if (file_exists($filePath)) {
        unlink($filePath);
    }

    $pdo->commit();

    echo "<script>
        alert('✅ ลบสินค้าสำเร็จ: " . addslashes($product['name_product']) . "');
        window.location.href='admin-product.php';
    </script>";
} catch (PDOException $e) {
    $pdo->rollBack();
    $msg = addslashes($e->getMessage());
    echo "<script>
        alert('❌ เกิดข้อผิดพลาดในการลบสินค้า: $msg');
        window.location.href='admin-product.php';
    </script>";
}
?>
