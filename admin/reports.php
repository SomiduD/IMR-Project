<?php
session_start();
require '../includes/db.php';
require '../includes/functions.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Admin') {
    header("Location: ../login.php");
    exit();
}

$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01'); // Default to this month
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');
$status_filter = isset($_GET['status']) ? $_GET['status'] : 'All';

$sql = "SELECT b.BillID, b.BillMonth, b.Amount, b.Status, b.GeneratedDate, 
               c.NIC, u.FullName, m.MeterType 
        FROM Bills b
        JOIN Meters m ON b.MeterID = m.MeterID
        JOIN Customers c ON m.CustomerID = c.CustomerID
        JOIN Users u ON c.UserID = u.UserID
        WHERE b.GeneratedDate BETWEEN ? AND ? 
        " . ($status_filter != 'All' ? "AND b.Status = '$status_filter'" : "") . "
        ORDER BY b.GeneratedDate DESC";

$params = array($start_date . " 00:00:00", $end_date . " 23:59:59");
$stmt = sqlsrv_query($conn, $sql, $params);

$totalRevenue = 0;
$count = 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Executive Reports - UtilityOne</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .report-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; background: white; padding: 20px; border-radius: 10px; }
        .filter-form { display: flex; gap: 10px; align-items: end; }
        .filter-group label { font-size: 0.8rem; color: #666; display: block; margin-bottom: 5px; }
        .filter-group input, .filter-group select { padding: 8px; border: 1px solid #ddd; border-radius: 4px; }
        
        @media print {
            .sidebar, .filter-form, .btn-main { display: none !important; }
            .dashboard-container { grid-template-columns: 1fr; }
            .main-content { padding: 0; }
        }
    </style>
</head>
<body>

<div class="dashboard-container">
    <div class="sidebar">
        <h3>UtilityOne SL</h3>
        <a href="dashboard.php"> Dashboard</a>
        <a href="billing.php"> Billing Center</a>
        <a href="customers.php"> Manage Customers</a>
        <a href="reports.php" class="active"> Reports</a>
        <a href="staff.php"> Manage Staff</a>
        <a href="../staff/readings.php"> Generate Bill</a>
        <a href="../logout.php"> Logout</a>
    </div>

    <div class="main-content">
        <h1> Financial & Usage Reports</h1>
        
        <div class="report-header">
            <form method="GET" class="filter-form">
                <div class="filter-group">
                    <label>From Date:</label>
                    <input type="date" name="start_date" value="<?php echo $start_date; ?>">
                </div>
                <div class="filter-group">
                    <label>To Date:</label>
                    <input type="date" name="end_date" value="<?php echo $end_date; ?>">
                </div>
                <div class="filter-group">
                    <label>Status:</label>
                    <select name="status">
                        <option value="All" <?php if($status_filter=='All') echo 'selected'; ?>>All</option>
                        <option value="Paid" <?php if($status_filter=='Paid') echo 'selected'; ?>>Paid Only</option>
                        <option value="Unpaid" <?php if($status_filter=='Unpaid') echo 'selected'; ?>>Unpaid Only</option>
                    </select>
                </div>
                <button type="submit" class="btn-main" style="margin-bottom: 2px;">Filter</button>
            </form>
            <button onclick="window.print()" class="btn-main" style="background: #6c757d;">🖨️ Print Report</button>
        </div>

        <div class="table-container" style="background: white; padding: 20px;">
            <div style="text-align: center; margin-bottom: 20px;">
                <h2>Utility Usage Report</h2>
                <p>Period: <?php echo $start_date; ?> to <?php echo $end_date; ?></p>
            </div>

            <table style="width: 100%; border-collapse: collapse;">
                <thead>
                    <tr style="border-bottom: 2px solid #000;">
                        <th style="text-align: left; padding: 10px;">Date</th>
                        <th style="text-align: left; padding: 10px;">Customer</th>
                        <th style="text-align: left; padding: 10px;">Type</th>
                        <th style="text-align: left; padding: 10px;">Status</th>
                        <th style="text-align: right; padding: 10px;">Amount (LKR)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($stmt): while($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)): 
                        $totalRevenue += $row['Amount'];
                        $count++;
                    ?>
                    <tr style="border-bottom: 1px solid #eee;">
                        <td style="padding: 10px;"><?php echo $row['GeneratedDate']->format('Y-m-d'); ?></td>
                        <td style="padding: 10px;">
                            <?php echo $row['FullName']; ?><br>
                            <small style="color: #666;"><?php echo $row['NIC']; ?></small>
                        </td>
                        <td style="padding: 10px;"><?php echo $row['MeterType']; ?></td>
                        <td style="padding: 10px; font-weight: bold; color: <?php echo ($row['Status']=='Paid')?'green':'red'; ?>;">
                            <?php echo $row['Status']; ?>
                        </td>
                        <td style="padding: 10px; text-align: right;"><?php echo number_format($row['Amount'], 2); ?></td>
                    </tr>
                    <?php endwhile; endif; ?>
                    
                    <tr style="background: #f8f9fa; font-weight: bold; border-top: 2px solid #000;">
                        <td colspan="4" style="padding: 15px; text-align: right;">TOTAL REVENUE:</td>
                        <td style="padding: 15px; text-align: right; font-size: 1.1rem;"><?php echo number_format($totalRevenue, 2); ?> LKR</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>
</body>
</html>