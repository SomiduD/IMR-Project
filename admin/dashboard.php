<?php
session_start();
require '../includes/db.php';
require '../includes/functions.php';

if (!isset($_SESSION['role']) || ($_SESSION['role'] !== 'Admin' && $_SESSION['role'] !== 'Manager')) { header("Location: ../login.php"); exit(); }
$currentPage = 'dashboard';

$custCount = 0; $billCount = 0; $staffCount = 0;
$cRes = sqlsrv_query($conn, "SELECT COUNT(*) as C FROM Customers");
if ($cRes) $custCount = sqlsrv_fetch_array($cRes)['C'];

$bRes = sqlsrv_query($conn, "SELECT COUNT(*) as C FROM Bills WHERE Status='Unpaid'");
if ($bRes) $billCount = sqlsrv_fetch_array($bRes)['C'];

$sRes = sqlsrv_query($conn, "SELECT COUNT(*) as C FROM Users WHERE UserRole='MeterReader'");
if ($sRes) $staffCount = sqlsrv_fetch_array($sRes)['C'];

$chartSql = "SELECT m.MeterType, SUM(b.Amount) as Total 
             FROM Bills b 
             LEFT JOIN Readings r ON b.ReadingID = r.ReadingID
             LEFT JOIN Meters m ON r.MeterID = m.MeterID 
             GROUP BY m.MeterType";
$chartStmt = sqlsrv_query($conn, $chartSql);
$elecTotal = 0; $waterTotal = 0; $gasTotal = 0;
if ($chartStmt) {
    while ($row = sqlsrv_fetch_array($chartStmt, SQLSRV_FETCH_ASSOC)) {
        if ($row['MeterType'] == 'Electricity') $elecTotal = $row['Total'];
        if ($row['MeterType'] == 'Water') $waterTotal = $row['Total'];
        if ($row['MeterType'] == 'Gas') $gasTotal = $row['Total'];
    }
}
$grandTotal = $elecTotal + $waterTotal + $gasTotal;

$recentSql = "SELECT TOP 5 b.BillID, b.Amount, u.FullName, m.MeterType, p.PaymentDate 
              FROM Payments p 
              JOIN Bills b ON p.BillID = b.BillID
              LEFT JOIN Readings r ON b.ReadingID = r.ReadingID
              LEFT JOIN Meters m ON r.MeterID = m.MeterID
              LEFT JOIN Customers c ON m.CustomerID = c.CustomerID
              LEFT JOIN Users u ON c.UserID = u.UserID
              ORDER BY p.PaymentDate DESC";
$recentStmt = sqlsrv_query($conn, $recentSql);
if ($recentStmt === false) $recentStmt = null;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Executive Dashboard</title>
    <link rel="stylesheet" href="../assets/css/style.css?v=<?php echo time(); ?>">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>

<div class="dashboard-container">
    
    <?php include 'sidebar.php'; ?>

    <div class="main-content">
        
        <div class="top-header">
            <div>
                <h2 style="margin:0;"> Welcome back, <?php echo isset($_SESSION['name']) ? explode(' ', $_SESSION['name'])[0] : 'Admin'; ?></h2>
                <span>System Overview • <?php echo date("l, F j, Y"); ?></span>
            </div>
            <div>
                <a href="customers.php" class="btn-quick btn-gray">+ New Customer</a>
                <a href="../staff/readings.php" class="btn-quick btn-blue">+ New Bill</a>
            </div>
        </div>

        <div class="stats-grid">
            <div class="stat-card card-blue">
                <h3> Total Customers</h3>
                <p><?php echo $custCount; ?></p>
            </div>
            <div class="stat-card card-red">
                <h3> Unpaid Bills</h3>
                <p><?php echo $billCount; ?></p>
            </div>
            <div class="stat-card card-green">
                <h3> Total Revenue</h3>
                <p>LKR <?php echo number_format($grandTotal, 2); ?></p>
            </div>
            <div class="stat-card card-orange">
                <h3> Active Staff</h3>
                <p><?php echo $staffCount; ?></p>
            </div>
        </div>

        <div class="dashboard-main">
            
            <div class="content-box">
                <h3 style="margin-top:0;">📊 Revenue Breakdown</h3>
                <canvas id="revenueChart" style="max-height: 300px;"></canvas>
            </div>

            <div class="content-box">
                <h3 style="margin-top:0;"> Recent Payments</h3>
                <?php if ($recentStmt && sqlsrv_has_rows($recentStmt)): ?>
                    <?php while($row = sqlsrv_fetch_array($recentStmt, SQLSRV_FETCH_ASSOC)): ?>
                    <div class="recent-item">
                        <div>
                            <strong style="color:#0f172a;"><?php echo $row['FullName']; ?></strong><br>
                            <small style="color:#64748b;"><?php echo $row['MeterType']; ?> Bill</small>
                        </div>
                        <div style="text-align: right;">
                            <strong style="color:#0f172a;">LKR <?php echo number_format($row['Amount'], 2); ?></strong><br>
                            <small style="color:#22c55e; font-weight:bold;">Just Now</small>
                        </div>
                    </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <p style="text-align:center; color:#94a3b8; padding:20px;">No recent activity.</p>
                <?php endif; ?>
            </div>
        </div>

        <div class="footer">
            &copy; 2026 UtilityOne SL. University Project v2.0
        </div>

    </div>
</div>

<script>
    const ctx = document.getElementById('revenueChart').getContext('2d');
    new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: ['Electricity', 'Water', 'Gas'],
            datasets: [{
                data: [<?php echo $elecTotal; ?>, <?php echo $waterTotal; ?>, <?php echo $gasTotal; ?>],
                backgroundColor: ['#f59e0b', '#3b82f6', '#ef4444'],
                borderWidth: 0,
                hoverOffset: 4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { position: 'bottom' } }
        }
    });
</script>

</body>
</html>