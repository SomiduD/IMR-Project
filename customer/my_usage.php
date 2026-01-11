<?php
session_start();
require '../includes/db.php';
require '../includes/functions.php';

// 1. Security Check
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Customer') {
    header("Location: ../login.php");
    exit();
}

$userID = $_SESSION['user_id'];

// 2. Get Customer Profile
$custSql = "SELECT * FROM Customers WHERE UserID = ?";
$custStmt = sqlsrv_query($conn, $custSql, array($userID));
$customer = sqlsrv_fetch_array($custStmt, SQLSRV_FETCH_ASSOC);

if (!$customer) {
    die("Error: Customer profile not linked. Please contact Admin.");
}
$customerID = $customer['CustomerID'];

// 3. Fetch Unpaid Bills (For Actions)
$dueSql = "SELECT b.BillID, b.BillMonth, b.Amount, b.DueDate, m.MeterType 
           FROM Bills b
           JOIN Meters m ON b.MeterID = m.MeterID
           WHERE m.CustomerID = ? AND b.Status = 'Unpaid'
           ORDER BY b.DueDate ASC";
$dueStmt = sqlsrv_query($conn, $dueSql, array($customerID));

// 4. Fetch Usage History (For Graph)
$histSql = "SELECT TOP 6 ReadingDate, UnitsConsumed FROM Readings r
            JOIN Meters m ON r.MeterID = m.MeterID
            WHERE m.CustomerID = ?
            ORDER BY ReadingDate DESC";
$histStmt = sqlsrv_query($conn, $histSql, array($customerID));

$dates = [];
$units = [];
while ($row = sqlsrv_fetch_array($histStmt, SQLSRV_FETCH_ASSOC)) {
    $dates[] = $row['ReadingDate']->format('M d');
    $units[] = $row['UnitsConsumed'];
}
$dates = array_reverse($dates);
$units = array_reverse($units);

// 5. Fetch Payment History
$paySql = "SELECT TOP 5 PaymentDate, AmountPaid, PaymentMethod 
           FROM Payments 
           WHERE CustomerID = ? 
           ORDER BY PaymentDate DESC";
$payStmt = sqlsrv_query($conn, $paySql, array($customerID));
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Dashboard - UtilityOne SL</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .bill-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
            margin-bottom: 15px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-left: 5px solid #dc3545; /* Red for Unpaid */
        }
        .bill-info h4 { margin: 0; color: #333; }
        .bill-info span { color: #666; font-size: 0.9rem; }
        .bill-amount { font-size: 1.2rem; font-weight: bold; color: #dc3545; }
        .btn-pay {
            background: #28a745;
            color: white;
            padding: 8px 20px;
            text-decoration: none;
            border-radius: 5px;
            font-weight: bold;
            transition: 0.3s;
        }
        .btn-pay:hover { background: #218838; }
        
        .history-table { width: 100%; border-collapse: collapse; background: white; border-radius: 10px; overflow: hidden; margin-top: 10px; }
        .history-table th, .history-table td { padding: 12px; text-align: left; border-bottom: 1px solid #eee; }
        .history-table th { background: #f8f9fa; color: #555; }
    </style>
</head>
<body>

<div class="dashboard-container">
    <div class="sidebar">
        <h3>My Account</h3>
        <p style="text-align: center; color: #888; font-size: 0.8rem;">
            <?php echo $customer['NIC']; ?>
        </p>
        <a href="my_usage.php" class="active">📉 My Usage & Bills</a>
        <a href="../tariff_calculator.php">🧮 Bill Estimator</a>
        <a href="../logout.php" style="color: #ff6b6b; margin-top: 20px;">🚪 Logout</a>
    </div>

    <div class="main-content">
        
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
            <div>
                <h1>Hello, <?php echo $_SESSION['name']; ?> 👋</h1>
                <p style="color: #666;">Here is your utility summary for <strong><?php echo $customer['Address']; ?></strong></p>
            </div>
            <div>
                <span class="badge" style="background:#e8f5e9; color:#2e7d32; padding:8px 15px;">Active Customer</span>
            </div>
        </div>

        <h3 style="color: #444;">⚠️ Pending Bills</h3>
        <?php if (sqlsrv_has_rows($dueStmt)): ?>
            <?php while($bill = sqlsrv_fetch_array($dueStmt, SQLSRV_FETCH_ASSOC)): ?>
            <div class="bill-card">
                <div class="bill-info">
                    <h4><?php echo $bill['MeterType']; ?> Bill</h4>
                    <span>Month: <?php echo $bill['BillMonth']; ?></span><br>
                    <span style="color: #d35400;">Due: <?php echo $bill['DueDate']->format('Y-m-d'); ?></span>
                </div>
                <div class="bill-amount">
                    <?php echo formatCurrency($bill['Amount']); ?>
                </div>
                <div>
                    <a href="pay_bill.php?id=<?php echo $bill['BillID']; ?>" class="btn-pay">Pay Now</a>
                </div>
            </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div style="background: #d4edda; color: #155724; padding: 20px; border-radius: 10px; margin-bottom: 20px;">
                ✅ Great! You have no pending bills.
            </div>
        <?php endif; ?>

        <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 20px; margin-top: 30px;">
            
            <div class="stat-card">
                <h4>📊 Your Consumption Trend</h4>
                <canvas id="usageChart"></canvas>
            </div>

            <div class="stat-card">
                <h4>🕒 Recent Payments</h4>
                <table class="history-table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (sqlsrv_has_rows($payStmt)): ?>
                            <?php while($pay = sqlsrv_fetch_array($payStmt, SQLSRV_FETCH_ASSOC)): ?>
                            <tr>
                                <td><?php echo $pay['PaymentDate']->format('M d'); ?></td>
                                <td style="color: green; font-weight: bold;"><?php echo formatCurrency($pay['AmountPaid']); ?></td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="2">No history yet.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</div>

<script>
    
    const ctx = document.getElementById('usageChart').getContext('2d');
    new Chart(ctx, {
        type: 'bar', 
        data: {
            labels: <?php echo json_encode($dates); ?>,
            datasets: [{
                label: 'Units Consumed',
                data: <?php echo json_encode($units); ?>,
                backgroundColor: '#00d2ff',
                borderRadius: 5
            }]
        },
        options: {
            responsive: true,
            scales: {
                y: { beginAtZero: true }
            }
        }
    });
</script>

</body>
</html>s