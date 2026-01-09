<?php
session_start();
require '../includes/db.php';
require '../includes/functions.php';

if (!isset($_SESSION['role']) || ($_SESSION['role'] !== 'Admin' && $_SESSION['role'] !== 'MeterReader')) {
    header("Location: ../login.php");
    exit();
}

$msg = "";

// --- DELETE FUNCTIONALITY (Undo Mistake) ---
if (isset($_GET['delete_id']) && $_SESSION['role'] == 'Admin') {
    $delID = intval($_GET['delete_id']);
    
    // 1. Get ReadingID linked to this Bill
    $getReadSql = "SELECT ReadingID FROM Bills WHERE BillID = ?";
    $stmtR = sqlsrv_query($conn, $getReadSql, array($delID));
    $readingID = sqlsrv_fetch_array($stmtR)['ReadingID'];

    // 2. Delete Payments -> Bill -> Reading
    sqlsrv_query($conn, "DELETE FROM Payments WHERE BillID = ?", array($delID));
    sqlsrv_query($conn, "DELETE FROM Bills WHERE BillID = ?", array($delID));
    sqlsrv_query($conn, "DELETE FROM Readings WHERE ReadingID = ?", array($readingID));

    $msg = "<div class='alert error'>🗑️ Bill & Reading Deleted Successfully.</div>";
}

// Payment & Email Actions (Same as before)
if (isset($_GET['pay_id'])) {
    $billID = intval($_GET['pay_id']);
    sqlsrv_query($conn, "UPDATE Bills SET Status = 'Paid' WHERE BillID = ?", array($billID));
    $msg = "<div class='alert success'>✅ Bill Marked as Paid.</div>";
}
if (isset($_GET['email_id'])) {
    sqlsrv_query($conn, "UPDATE Bills SET IsEmailSent = 1 WHERE BillID = ?", array(intval($_GET['email_id'])));
    $msg = "<div class='alert success'>📧 Email Sent.</div>";
}

// Fetch Logic
$search = "";
$params = array();
$whereClause = "";
if (isset($_GET['search']) && !empty($_GET['search'])) {
    $search = $_GET['search'];
    $whereClause = "WHERE (c.NIC LIKE ? OR u.FullName LIKE ?)";
    $term = "%$search%";
    $params = array($term, $term);
}

$sql = "SELECT TOP 50 b.BillID, b.Amount, b.Status, b.IsEmailSent, ISNULL(c.NIC, 'Unknown') as NIC, ISNULL(u.FullName, 'Unknown') as FullName, ISNULL(m.MeterType, 'Unknown') as MeterType 
        FROM Bills b LEFT JOIN Meters m ON b.MeterID = m.MeterID LEFT JOIN Customers c ON m.CustomerID = c.CustomerID LEFT JOIN Users u ON c.UserID = u.UserID 
        $whereClause ORDER BY b.BillID DESC";
$stmt = sqlsrv_query($conn, $sql, $params);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Billing Center</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .action-btn { padding: 4px 8px; border-radius: 4px; color: white; text-decoration: none; font-size: 0.8rem; display:inline-block; margin-right:3px; }
        .btn-pay { background: #28a745; }
        .btn-email { background: #17a2b8; }
        .btn-del { background: #dc3545; } /* Red Delete Button */
        .alert { padding: 15px; margin-bottom: 20px; border-radius: 5px; }
        .success { background: #d4edda; color: #155724; }
        .error { background: #f8d7da; color: #721c24; }
    </style>
</head>
<body>
<div class="dashboard-container">
    <div class="sidebar">
        <h3>UtilityOne SL</h3>
        <a href="dashboard.php">📊 Dashboard</a>
        <a href="billing.php" class="active">💳 Billing & Payments</a>
        <a href="customers.php">👥 Manage Customers</a>
        <a href="staff.php">👷 Manage Staff</a>
        <a href="../staff/readings.php">📝 Generate New Bill</a>
        <a href="../logout.php">🚪 Logout</a>
    </div>

    <div class="main-content">
        <h1>💳 Billing Control Center</h1>
        <?php echo $msg; ?>
        
        <div class="stat-card" style="padding:15px; margin-bottom:20px;">
            <form method="GET" style="display:flex; gap:10px;">
                <input type="text" name="search" placeholder="Search..." value="<?php echo htmlspecialchars($search); ?>" style="flex:1; padding:10px;">
                <button type="submit" class="btn-main" style="width:auto;">Search</button>
            </form>
        </div>

        <div class="table-container" style="background: white; padding: 20px; border-radius: 10px;">
            <table style="width: 100%; border-collapse: collapse;">
                <thead><tr style="background: #f8f9fa;"><th style="padding:10px;">ID</th><th style="padding:10px;">Details</th><th style="padding:10px;">Amount</th><th style="padding:10px;">Actions</th></tr></thead>
                <tbody>
                    <?php if ($stmt): while($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)): ?>
                    <tr style="border-bottom: 1px solid #eee;">
                        <td style="padding:12px;">#<?php echo $row['BillID']; ?></td>
                        <td style="padding:12px;"><strong><?php echo $row['FullName']; ?></strong><br><small><?php echo $row['MeterType']; ?></small></td>
                        <td style="padding:12px; font-weight:bold;"><?php echo formatCurrency($row['Amount']); ?></td>
                        <td style="padding:12px;">
                            <a href="billing.php?email_id=<?php echo $row['BillID']; ?>" class="action-btn btn-email">✉️</a>
                            <?php if($row['Status'] == 'Unpaid'): ?>
                                <a href="billing.php?pay_id=<?php echo $row['BillID']; ?>" class="action-btn btn-pay">💵</a>
                            <?php endif; ?>
                            <a href="billing.php?delete_id=<?php echo $row['BillID']; ?>" class="action-btn btn-del" onclick="return confirm('⚠️ Are you sure? This will DELETE the reading and bill permanently.');">🗑️</a>
                        </td>
                    </tr>
                    <?php endwhile; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
</body>
</html>