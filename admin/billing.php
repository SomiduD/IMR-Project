<?php
session_start();
require '../includes/db.php';
require '../includes/functions.php';

if (!isset($_SESSION['role']) || ($_SESSION['role'] !== 'Admin' && $_SESSION['role'] !== 'Cashier' && $_SESSION['role'] !== 'Manager')) { 
    header("Location: ../login.php"); exit(); 
}
$currentPage = 'billing';
$msg = "";

if (isset($_GET['pay_id']) && isset($_GET['amt'])) {
    $billID = intval($_GET['pay_id']);
    $amount = floatval($_GET['amt']);
    $userID = $_SESSION['user_id'];
    
    $sqlSP = "{CALL sp_PayBill(?, ?, ?, ?)}";
    $params = array($billID, $amount, 'Cash', $userID);
    
    if(sqlsrv_query($conn, $sqlSP, $params)) {
        $msg = "<div class='alert success'>✅ Payment Recorded via System!</div>";
    } else {
        $msg = "<div class='alert error'>❌ Payment Failed.</div>";
    }
}

$search = ""; $params = array(); $whereClause = "";
if (isset($_GET['search'])) {
    $search = $_GET['search'];
    $whereClause = "WHERE (c.NIC LIKE ? OR u.FullName LIKE ?)";
    $params = array("%$search%", "%$search%");
}

$sql = "SELECT TOP 50 b.BillID, b.Amount, b.Status, b.BillMonth,
        ISNULL(c.NIC, 'Unknown') as NIC, 
        ISNULL(u.FullName, 'Unknown') as FullName, 
        ISNULL(m.MeterType, 'Unknown') as MeterType 
        FROM Bills b 
        LEFT JOIN Readings r ON b.ReadingID = r.ReadingID
        LEFT JOIN Meters m ON r.MeterID = m.MeterID 
        LEFT JOIN Customers c ON m.CustomerID = c.CustomerID 
        LEFT JOIN Users u ON c.UserID = u.UserID 
        $whereClause ORDER BY b.BillID DESC";
$stmt = sqlsrv_query($conn, $sql, $params);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Billing Center</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<div class="dashboard-container">
    <?php include 'sidebar.php'; ?>
    <div class="main-content">
        <h2 style="color: white; margin-bottom: 20px;">💳 Billing Center</h2>
        <?php echo $msg; ?>

        <div class="content-box">
            <form method="GET" style="display:flex; gap:10px; margin-bottom: 20px;">
                <input type="text" name="search" placeholder="Search..." value="<?php echo htmlspecialchars($search); ?>" style="flex:1; margin-bottom:0;">
                <button type="submit" class="btn-main" style="width:auto;">Search</button>
            </form>

            <table>
                <thead>
                    <tr><th>ID</th><th>Customer</th><th>Month</th><th>Amount</th><th>Status</th><th>Action</th></tr>
                </thead>
                <tbody>
                    <?php if ($stmt && sqlsrv_has_rows($stmt)): ?>
                        <?php while($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)): ?>
                        <tr>
                            <td>#<?php echo $row['BillID']; ?></td>
                            <td><strong><?php echo $row['FullName']; ?></strong><br><small><?php echo $row['MeterType']; ?></small></td>
                            <td><?php echo $row['BillMonth']; ?></td>
                            <td>LKR <?php echo number_format($row['Amount'], 2); ?></td>
                            <td><?php echo ($row['Status'] == 'Paid') ? "<span style='color:green'>Paid</span>" : "<span style='color:red'>Unpaid</span>"; ?></td>
                            <td>
                                <?php if($row['Status'] == 'Unpaid'): ?>
                                    <a href="billing.php?pay_id=<?php echo $row['BillID']; ?>&amt=<?php echo $row['Amount']; ?>" style="color:green; font-weight:bold;">Pay Full</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
</body>
</html>