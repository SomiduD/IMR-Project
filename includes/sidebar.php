<div class="sidebar">
    <h3>UtilityOne SL 🇱🇰</h3>
    <p style="text-align: center; font-size: 0.8rem; color: #aaa; margin-bottom: 20px;">
        Role: <?php echo $_SESSION['role']; ?>
    </p>

    <?php if ($_SESSION['role'] == 'Admin'): ?>
        <a href="../admin/dashboard.php">📊 Dashboard</a>
        <a href="../admin/customers.php">👥 Manage Customers</a>
        <a href="../admin/reports.php">📑 System Reports</a>
        <a href="../tariff_calculator.php">🧮 Tariff Calculator</a>
    <?php endif; ?>

    <?php if ($_SESSION['role'] == 'MeterReader'): ?>
        <a href="../staff/readings.php">📝 Enter Readings</a>
        <a href="../staff/history.php">clock History</a>
    <?php endif; ?>

    <?php if ($_SESSION['role'] == 'Cashier'): ?>
        <a href="../staff/payments.php">💰 Process Payments</a>
    <?php endif; ?>

    <?php if ($_SESSION['role'] == 'Customer'): ?>
        <a href="../customer/my_usage.php">📉 My Usage</a>
        <a href="../customer/pay_bill.php">💳 Pay Bills</a>
        <a href="../tariff_calculator.php">🧮 Bill Estimator</a>
    <?php endif; ?>

    <div style="margin-top: auto; padding-top: 20px; border-top: 1px solid rgba(255,255,255,0.1);">
        <a href="../logout.php" style="color: #ff6b6b; font-weight: bold;">🚪 Logout</a>
    </div>
</div>