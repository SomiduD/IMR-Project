<?php
session_start();
require '../includes/db.php';

// Security: Admin Only
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Admin') {
    header("Location: ../login.php");
    exit();
}

$msg = "";

// HANDLE ADD STAFF
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = $_POST['fullname'];
    $username = $_POST['username'];
    $password = $_POST['password'];
    $role = 'MeterReader'; // Hardcoded role

    $passHash = md5($password); // Simple MD5 for this project

    $sql = "INSERT INTO Users (Username, PasswordHash, FullName, UserRole) VALUES (?, ?, ?, ?)";
    $params = array($username, $passHash, $name, $role);

    if (sqlsrv_query($conn, $sql, $params)) {
        $msg = "<div class='alert success'>✅ Staff Member Created! <br>Username: <strong>$username</strong><br>Password: <strong>$password</strong></div>";
    } else {
        $msg = "<div class='alert error'>❌ Error: Username likely taken.</div>";
    }
}

// FETCH STAFF LIST
$staffSql = "SELECT UserID, FullName, Username, CreatedAt FROM Users WHERE UserRole = 'MeterReader' ORDER BY UserID DESC";
$staffStmt = sqlsrv_query($conn, $staffSql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Staff - UtilityOne SL</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
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
        <a href="billing.php">💳 Billing Center</a>
        <a href="customers.php">👥 Manage Customers</a>
        <a href="staff.php" class="active">👷 Manage Staff</a>
        <a href="../staff/readings.php">📝 Generate Bill</a>
        <a href="../logout.php">🚪 Logout</a>
    </div>

    <div class="main-content">
        <h1>👷 Manage Meter Readers</h1>
        <?php echo $msg; ?>

        <div class="stat-card" style="max-width: 500px;">
            <h3>Add New Reader</h3>
            <form method="POST" action="">
                <input type="text" name="fullname" required placeholder="Full Name (e.g. Nimal Perera)" style="width: 100%; padding: 10px; margin-bottom: 10px;">
                <input type="text" name="username" required placeholder="Username (e.g. reader1)" style="width: 100%; padding: 10px; margin-bottom: 10px;">
                <input type="text" name="password" required placeholder="Password" style="width: 100%; padding: 10px; margin-bottom: 10px;">
                <button type="submit" class="btn-main">Create Account</button>
            </form>
        </div>

        <h3 style="margin-top: 30px;">Current Staff</h3>
        <div class="table-container" style="background: white; padding: 20px; border-radius: 10px;">
            <table style="width: 100%; border-collapse: collapse;">
                <thead>
                    <tr style="background: #eee; text-align: left;">
                        <th style="padding: 10px;">Name</th>
                        <th style="padding: 10px;">Username</th>
                        <th style="padding: 10px;">Joined Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($row = sqlsrv_fetch_array($staffStmt, SQLSRV_FETCH_ASSOC)): ?>
                    <tr>
                        <td style="padding: 10px; border-bottom: 1px solid #eee;"><?php echo $row['FullName']; ?></td>
                        <td style="padding: 10px; border-bottom: 1px solid #eee;"><?php echo $row['Username']; ?></td>
                        <td style="padding: 10px; border-bottom: 1px solid #eee;"><?php echo $row['CreatedAt']->format('Y-m-d'); ?></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
</body>
</html>