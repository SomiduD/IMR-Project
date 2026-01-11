<?php
session_start();
require '../includes/db.php';
require '../includes/functions.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Customer') { 
    header("Location: ../login.php"); exit(); 
}

$currentPage = 'dashboard';
$userID = $_SESSION['user_id'];
$custID = 0;

$custSql = "SELECT CustomerID, OutstandingBalance FROM Customers WHERE UserID = ?";
$custStmt = sqlsrv_query($conn, $custSql, array($userID));

if ($custStmt && sqlsrv_has_rows($custStmt)) {
    $custRow = sqlsrv_fetch_array($custStmt, SQLSRV_FETCH_ASSOC);
    $custID = $custRow['CustomerID'];
    $balance = $custRow['OutstandingBalance'];
} else {
    echo "Error: Customer profile not linked properly.";
    exit();
}

$meterSql = "SELECT COUNT(*) as Count FROM Meters WHERE CustomerID = ?";
$mRes = sqlsrv_query($conn, $meterSql, array($custID));
$meterCount = sqlsrv_fetch_array($mRes)['Count'];

$billSql = "SELECT TOP 5 b.BillID, b.BillMonth, b.Amount, b.Status, b.DueDate, m.MeterType, m.SerialNumber 
            FROM Bills b 
            JOIN Readings r ON b.ReadingID = r.ReadingID 
            JOIN Meters m ON r.MeterID = m.MeterID 
            WHERE m.CustomerID = ? 
            ORDER BY b.BillID DESC";
$billStmt = sqlsrv_query($conn, $billSql, array($custID));
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Dashboard - UtilityOne</title>
    <link rel="stylesheet" href="../assets/css/style.css?v=<?php echo time(); ?>">
</head>
<body>

<div class="dashboard-container">
    
    <?php include 'sidebar.php'; ?>

    <div class="main-content">
        
        <div class="top-header">
            <div>
                <h2 style="margin:0;">👋 Hello, <?php echo isset($_SESSION['name']) ? explode(' ', $_SESSION['name'])[0] : 'Customer'; ?></h2>
                <span style="color: #64748b; font-size: 0.9rem;">My Account Overview</span>
            </div>
            <div style="text-align:right;">
                <span style="display:block; font-size:0.8rem; color:#64748b;">Current Balance</span>
                <span style="font-size:1.5rem; font-weight:bold; color: <?php echo ($balance > 0) ? '#ef4444' : '#22c55e'; ?>">
                    <?php echo formatCurrency($balance); ?>
                </span>
            </div>
        </div>

        <div class="stats-grid">
            <div class="stat-card card-blue">
                <h3>🏠 My Meters</h3>
                <p><?php echo $meterCount; ?></p>
            </div>
            <div class="stat-card card-green">
                <h3>✅ Account Status</h3>
                <p style="font-size:1.2rem; margin-top:5px;">Active</p>
            </div>
            <div class="stat-card card-red">
                <h3>⚠️ Due Amount</h3>
                <p><?php echo formatCurrency($balance); ?></p>
            </div>
        </div>

        <div class="content-box">
            <h3 style="margin-top:0;">🧾 My Recent Bills</h3>
            <table>
                <thead>
                    <tr>
                        <th>Bill ID</th>
                        <th>Month</th>
                        <th>Meter</th>
                        <th>Amount</th>
                        <th>Due Date</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($billStmt && sqlsrv_has_rows($billStmt)): ?>
                        <?php while($row = sqlsrv_fetch_array($billStmt, SQLSRV_FETCH_ASSOC)): ?>
                        <tr>
                            <td>#<?php echo $row['BillID']; ?></td>
                            <td><?php echo $row['BillMonth']; ?></td>
                            <td>
                                <strong><?php echo $row['MeterType']; ?></strong><br>
                                <small style="color:#64748b;"><?php echo $row['SerialNumber']; ?></small>
                            </td>
                            <td><strong><?php echo formatCurrency($row['Amount']); ?></strong></td>
                            <td><?php echo $row['DueDate']->format('Y-m-d'); ?></td>
                            <td>
                                <?php if($row['Status'] == 'Paid'): ?>
                                    <span class="status-badge" style="background:#dcfce7; color:#166534; padding:5px 10px; border-radius:15px; font-size:0.8rem;">Paid</span>
                                <?php else: ?>
                                    <span class="status-badge" style="background:#fee2e2; color:#991b1b; padding:5px 10px; border-radius:15px; font-size:0.8rem;">Unpaid</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="6" style="text-align:center; padding:20px;">No bills found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

    </div>
</div>
</body>
</html>