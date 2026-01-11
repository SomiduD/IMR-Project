<div class="sidebar">
    <h3>UtilityOne SL</h3>
    
    <a href="dashboard.php" class="<?php echo ($currentPage == 'dashboard') ? 'active' : ''; ?>">📊 Dashboard</a>
    <a href="billing.php" class="<?php echo ($currentPage == 'billing') ? 'active' : ''; ?>">💳 Billing Center</a>
    <a href="customers.php" class="<?php echo ($currentPage == 'customers') ? 'active' : ''; ?>">👥 Manage Customers</a>
    <a href="staff.php" class="<?php echo ($currentPage == 'staff') ? 'active' : ''; ?>">👷 Manage Staff</a>
    <a href="reports.php" class="<?php echo ($currentPage == 'reports') ? 'active' : ''; ?>">📑 Reports</a>
    
    <hr style="border: 0; border-top: 1px solid #334155; margin: 20px 0;">
    
    <a href="../staff/readings.php"> New Reading</a>
    <a href="../logout.php" style="color:#ef4444;"> Logout</a>
</div>