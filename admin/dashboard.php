<?php
session_start();
require '../includes/db.php';
require '../includes/functions.php';

// Security Check
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Admin') {
    header("Location: ../login.php");
    exit();
}

// --- DATA FETCHING ---

// 1. Stat Cards Data
$custCount = sqlsrv_fetch_array(sqlsrv_query($conn, "SELECT COUNT(*) as C FROM Customers"))['C'];
$billCount = sqlsrv_fetch_array(sqlsrv_query($conn, "SELECT COUNT(*) as C FROM Bills WHERE Status='Unpaid'"))['C'];
$staffCount = sqlsrv_fetch_array(sqlsrv_query($conn, "SELECT COUNT(*) as C FROM Users WHERE UserRole='MeterReader'"))['C'];

// 2. Revenue Data (For Graph)
$chartSql = "SELECT m.MeterType, SUM(b.Amount) as Total 
             FROM Bills b 
             JOIN Meters m ON b.MeterID = m.MeterID 
             GROUP BY m.MeterType";
$chartStmt = sqlsrv_query($conn, $chartSql);
$elecTotal = 0; $waterTotal = 0; $gasTotal = 0;
while ($row = sqlsrv_fetch_array($chartStmt, SQLSRV_FETCH_ASSOC)) {
    if ($row['MeterType'] == 'Electricity') $elecTotal = $row['Total'];
    if ($row['MeterType'] == 'Water') $waterTotal = $row['Total'];
    if ($row['MeterType'] == 'Gas') $gasTotal = $row['Total'];
}
$grandTotal = $elecTotal + $waterTotal + $gasTotal;

// 3. Recent Activity Feed (New "University Level" Feature)
// Fetches the last 5 Paid Bills to show activity
$recentSql = "SELECT TOP 5 b.BillID, b.Amount, u.FullName, m.MeterType, p.PaymentDate 
              FROM Payments p 
              JOIN Bills b ON p.BillID = b.BillID
              JOIN Meters m ON b.MeterID = m.MeterID
              JOIN Customers c ON m.CustomerID = c.CustomerID
              JOIN Users u ON c.UserID = u.UserID
              ORDER BY p.PaymentDate DESC";
$recentStmt = sqlsrv_query($conn, $recentSql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Executive Dashboard - UtilityOne SL</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        /* DASHBOARD SPECIFIC HIGH-END STYLES */
        
        /* 1. Header & Footer */
        .top-header {
            background: white;
            padding: 15px 30px;
            border-bottom: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        }
        .footer {
            margin-top: 50px;
            text-align: center;
            color: #888;
            font-size: 0.9rem;
            padding: 20px;
            border-top: 1px solid #eee;
        }

        /* 2. Horizontal Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr); /* 4 Columns */
            gap: 20px;
            margin-bottom: 30px;
        }
        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
            text-align: center;
            transition: transform 0.2s;
            border-bottom: 4px solid transparent;
        }
        .stat-card:hover { transform: translateY(-5px); }
        
        /* Card Colors */
        .card-blue { border-color: #007bff; }
        .card-red { border-color: #dc3545; }
        .card-green { border-color: #28a745; }
        .card-orange { border-color: #fd7e14; }

        .stat-card h3 { font-size: 0.9rem; color: #666; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 10px; }
        .stat-card p { font-size: 2rem; font-weight: bold; color: #333; margin: 0; }

        /* 3. Main Layout Grid (Chart + Recent Activity) */
        .dashboard-main {
            display: grid;
            grid-template-columns: 2fr 1fr; /* 2/3 Graph, 1/3 Activity */
            gap: 25px;
        }
        .content-box {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
        }
        
        /* Recent List */
        .recent-item {
            display: flex;
            justify-content: space-between;
            padding: 12px 0;
            border-bottom: 1px solid #f0f0f0;
            font-size: 0.9rem;
        }
        .recent-item:last-child { border: none; }
        .status-badge { background: #e8f5e9; color: green; padding: 2px 8px; border-radius: 10px; font-size: 0.8rem; }
        
        /* Quick Actions */
        .quick-actions { display: flex; gap: 10px; margin-bottom: 20px; }
        .btn-quick { background: #6c757d; color: white; text-decoration: none; padding: 10px 15px; border-radius: 5px; font-size: 0.9rem; transition: 0.3s; }
        .btn-quick:hover { background: #5a6268; }

    </style>
</head>
<body>

<div class="dashboard-container">
    <div class="sidebar">
        <h3>UtilityOne SL</h3>
        <p style="color: #6c757d; font-size: 0.8rem; margin-bottom: 20px;">v2.0 University Edition</p>
        <a href="dashboard.php" class="active">📊 Dashboard</a>
        <a href="billing.php">💳 Billing Center</a>
        <a href="customers.php">👥 Manage Customers</a>
        <a href="staff.php">👷 Manage Staff</a>
        <a href="../staff/readings.php">📝 Generate Bill</a>
        <a href="../logout.php" style="color:#ff6b6b; margin-top:20px;">🚪 Logout</a>
    </div>

    <div class="main-content">
        
        <div class="top-header">
            <div>
                <h2 style="margin:0;">👋 Welcome back, <?php echo explode(' ', $_SESSION['name'])[0]; ?></h2>
                <span style="color: #888; font-size: 0.9rem;">System Admin • <?php echo date("l, F j, Y"); ?></span>
            </div>
            <div>
                <a href="customers.php" class="btn-quick">+ New Customer</a>
                <a href="../staff/readings.php" class="btn-quick" style="background: #007bff;">+ New Bill</a>
            </div>
        </div>

        <div class="stats-grid">
            <div class="stat-card card-blue">
                <h3>👥 Total Customers</h3>
                <p><?php echo $custCount; ?></p>
            </div>
            <div class="stat-card card-red">
                <h3>⚠️ Unpaid Bills</h3>
                <p><?php echo $billCount; ?></p>
            </div>
            <div class="stat-card card-green">
                <h3>💰 Total Revenue</h3>
                <p><?php echo formatCurrency($grandTotal); ?></p>
            </div>
            <div class="stat-card card-orange">
                <h3>👷 Active Staff</h3>
                <p><?php echo $staffCount; ?></p>
            </div>
        </div>

        <div class="dashboard-main">
            
            <div class="content-box">
                <h3 style="margin-bottom: 20px;">📊 Revenue Breakdown by Utility</h3>
                <canvas id="revenueChart" style="max-height: 300px;"></canvas>
            </div>

            <div class="content-box">
                <h3 style="margin-bottom: 20px;">⚡ Recent Payments</h3>
                
                <?php if(sqlsrv_has_rows($recentStmt)): ?>
                    <?php while($row = sqlsrv_fetch_array($recentStmt, SQLSRV_FETCH_ASSOC)): ?>
                    <div class="recent-item">
                        <div>
                            <strong><?php echo $row['FullName']; ?></strong><br>
                            <span style="color:#888; font-size:0.8rem;"><?php echo $row['MeterType']; ?> Bill</span>
                        </div>
                        <div style="text-align: right;">
                            <span style="font-weight:bold;"><?php echo formatCurrency($row['Amount']); ?></span><br>
                            <span class="status-badge">Just Now</span>
                        </div>
                    </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <p style="color:#999; text-align:center; padding:20px;">No recent activity.</p>
                <?php endif; ?>
                
                <div style="text-align: center; margin-top: 15px;">
                    <a href="billing.php" style="font-size: 0.9rem; color: #007bff; text-decoration: none;">View All Transactions →</a>
                </div>
            </div>
        </div>

        <div class="footer">
            &copy; 2026 UtilityOne SL. University Project v2.0 <br>
            Designed for High-End Utility Management.
        </div>

    </div>
</div>

<script>
    const ctx = document.getElementById('revenueChart').getContext('2d');
    new Chart(ctx, {
        type: 'doughnut', // Changed to Doughnut for a more modern look
        data: {
            labels: ['Electricity ⚡', 'Water 💧', 'Gas 🔥'],
            datasets: [{
                data: [<?php echo $elecTotal; ?>, <?php echo $waterTotal; ?>, <?php echo $gasTotal; ?>],
                backgroundColor: ['#ffcd56', '#36a2eb', '#ff6384'],
                hoverOffset: 4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { position: 'bottom' }
            }
        }
    });
</script>

</body>
</html>