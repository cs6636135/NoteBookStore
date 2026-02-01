<?php
include dirname(__DIR__) . "/navbar.php";

if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login/login-form.php");
    exit;
}
// อัปเดต payment_status ถ้ามีการ submit
if (isset($_POST['update_status'])) {
    $id_payment = $_POST['id_payment'];
    $new_status = $_POST['payment_status'];

    // ดึง order_id และ username ลูกค้า
    $stmt = $pdo->prepare("SELECT id_order FROM payment WHERE id_payment = ?");
    $stmt->execute([$id_payment]);
    $payment_data = $stmt->fetch();
    $id_order = $payment_data['id_order'];

    $stmt = $pdo->prepare("SELECT username_cus FROM orders WHERE id_order = ?");
    $stmt->execute([$id_order]);
    $order_data = $stmt->fetch();
    $username_cus = $order_data['username_cus'];

    // อัปเดต Payment
    $stmt_update = $pdo->prepare("UPDATE payment SET payment_status = ? WHERE id_payment = ?");
    $stmt_update->execute([$new_status, $id_payment]);

     // 🔹 ดึง shipping ปัจจุบันก่อน
    $stmt_ship = $pdo->prepare("SELECT shipping_status FROM shipping WHERE id_order = ?");
    $stmt_ship->execute([$id_order]);
    $ship_data = $stmt_ship->fetch();
    $current_shipping = $ship_data['shipping_status'] ?? 'Pending';

    // 🔹 noti
    $message = json_encode([
        "text" => "สถานะการชำระเงินของคำสั่งซื้อ #$id_order ถูกเปลี่ยนเป็น $new_status",
        "order_id" => $id_order,
        "shipping_status" => $current_shipping,
        "payment_status" => $new_status
    ], JSON_UNESCAPED_UNICODE);

    $noti_type = "Payment";

    $stmt_noti = $pdo->prepare("INSERT INTO notification (username_cus, id_order, message, noti_type) VALUES (?, ?, ?, ?)");
    $stmt_noti->execute([$username_cus, $id_order, $message, $noti_type]);

    // รีเฟรชหน้า
    header("Location: admin-payment.php");
    exit;
}

// ดึงข้อมูล payment ทั้งหมด
$stmt = $pdo->prepare("SELECT * FROM payment ORDER BY id_payment ASC");
$stmt->execute();
$payments = $stmt->fetchAll();

// ดึงข้อมูล Orders
$stmt = $pdo->prepare("SELECT * FROM orders ORDER BY id_order ASC");
$stmt->execute();
$orders = $stmt->fetchAll();

$orders_map = [];
foreach ($orders as $order) {
    $orders_map[$order['id_order']] = $order;
}
// ดึงข้อมูล admin จาก session
$stmt = $pdo->prepare("SELECT * FROM admin WHERE username_ad = ?");
$stmt->execute([$_SESSION['username']]);
$admin = $stmt->fetch();
?>
<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <title>Admin - Manage Payments</title>
    <link rel="stylesheet" href="admin.css">
</head>


<body>

    <header>
        <h1 id="dashboard-header">ระบบผู้ดูแล</h1>
    </header>

    <aside class="sidebar">
        <ul class="menu-panel">
            <a class="nav-link" href="admin.php">แอดมิน</a>
            <a class="nav-link" href="admin-statistic.php">รายงานทางสถิติ</a>
            <a class="nav-link" href="admin-customer.php">จัดการผู้ใช้</a>
            <a class="nav-link" href="admin-product.php">จัดการสินค้า</a>
            <a class="nav-link" href="admin-payment.php">จัดการสถานะการชำระเงิน</a>
            <a class="nav-link" href="admin-shipping.php">จัดการสถานะการจัดส่ง</a>
        </ul>
    </aside>
    <aside class="spacer">
        <section class="admin-info">
            <h3>ข้อมูลแอดมิน</h3>
            <p><b>Username :</b> <?= $admin['username_ad'] ?></p>
            <p><b> ชื่อ :</b> <?= $admin['firstname_ad'] ?></p>
            <p><b>นามสกุล :</b> <?= $admin['lastname_ad'] ?></p>
        </section>

        <section class="payment-section">
            <h2>จัดการสถานะการชำระเงิน</h2>
            <table>
                <thead>
                    <tr>
                        <th>รหัสการชำระเงิน</th>
                        <th>รหัสการสั่งซื้อ</th>
                        <th>วิธีการชำระเงิน</th>
                        <th>หลักฐานการชำระเงิน</th>
                        <th>สถานะการชำระเงิน</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($payments as $payment) : ?>
                        <?php $order = $orders_map[$payment['id_order']] ?? null; ?>
                        <tr>
                            <td><?= $payment['id_payment'] ?></td>
                            <td><?= $payment['id_order'] ?></td>
                            <td><?= $payment['payment_method'] ?></td>
                            <td>
                                <?php
                                $provePath = "prove/" . $payment['id_order'] . ".jpg";
                                if (file_exists($provePath)) {
                                    echo '<a href="' . $provePath . '?' . time() . '" target="_blank">ดูหลักฐาน</a>';
                                } else {
                                    echo 'ไม่มีหลักฐาน';
                                }
                                ?>
                            </td>
                            <td>
                                <form method="post" style="margin:0;">
                                    <input type="hidden" name="id_payment" value="<?= $payment['id_payment'] ?>">
                                    <select name="payment_status">
                                        <option value="Pending" <?= $payment['payment_status'] == 'Pending' ? 'selected' : '' ?>>Pending</option>
                                        <option value="Paid" <?= $payment['payment_status'] == 'Paid' ? 'selected' : '' ?>>Paid</option>
                                        <option value="Failed" <?= $payment['payment_status'] == 'Failed' ? 'selected' : '' ?>>Failed</option>
                                    </select>
                                    <button type="submit" name="update_status">Update</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </section>
    </aside>
</body>

</html>