<?php
session_start();
require '../includes/db.php';
require '../includes/functions.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Customer') { header("Location: ../login.php"); exit(); }
$currentPage = 'history';
$userID = $_SESSION['user_id'];

$sql = "SELECT p.PaymentDate, p.AmountPaid, p.PaymentMethod, b.BillID, m.MeterType 
        FROM Payments p
        JOIN Bills b ON p.BillID = b.BillID
        JOIN Readings r ON b.ReadingID = r.ReadingID
        JOIN Meters m ON r.MeterID = m.MeterID
        JOIN Customers c ON m.CustomerID = c.CustomerID
        WHERE c.UserID = ?
        ORDER BY p.PaymentDate DESC";
$stmt = sqlsrv_query($conn, $sql, array($userID));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Payment History</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<div class="dashboard-container">
    <?php include 'sidebar.php'; ?>
    <div class="main-content">
        <div class="content-box">
            <h2> Payment History</h2>
            <table>
                <thead><tr><th>Date</th><th>Bill ID</th><th>Utility</th><th>Amount Paid</th><th>Method</th></tr></thead>
                <tbody>
                    <?php if ($stmt && sqlsrv_has_rows($stmt)): ?>
                        <?php while($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)): ?>
                        <tr>
                            <td><?php echo $row['PaymentDate']->format('Y-m-d'); ?></td>
                            <td>#<?php echo $row['BillID']; ?></td>
                            <td><?php echo $row['MeterType']; ?></td>
                            <td><?php echo formatCurrency($row['AmountPaid']); ?></td>
                            <td><?php echo $row['PaymentMethod']; ?></td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="5">No payments found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
</body>
</html>