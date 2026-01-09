<?php
session_start();
require '../includes/db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Admin') {
    header("Location: ../login.php");
    exit();
}

$msg = "";

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_customer'])) {
    
    $nic = $_POST['nic'];
    $name = $_POST['fullname'];
    $address = $_POST['address'];
    $city = $_POST['city'];
    $phone = $_POST['phone'];
    $type = $_POST['type'];
    
    // 1. GENERATE RANDOM PASSWORD
    $randomPass = rand(100000, 999999); 
    $passHash = md5($randomPass);

    // 2. Insert User (Login)
    $userSql = "INSERT INTO Users (Username, PasswordHash, FullName, UserRole) VALUES (?, ?, ?, 'Customer')";
    if (sqlsrv_query($conn, $userSql, array($nic, $passHash, $name))) {
        
        $uidStmt = sqlsrv_query($conn, "SELECT @@IDENTITY AS NewID");
        $newUserID = sqlsrv_fetch_array($uidStmt)['NewID'];

        // 3. Insert Customer Profile
        $custSql = "INSERT INTO Customers (UserID, NIC, Address, City, Phone, CustomerType) VALUES (?, ?, ?, ?, ?, ?)";
        sqlsrv_query($conn, $custSql, array($newUserID, $nic, $address, $city, $phone, $type));
        
        $cidStmt = sqlsrv_query($conn, "SELECT @@IDENTITY AS NewCustID");
        $newCustID = sqlsrv_fetch_array($cidStmt)['NewCustID'];

        // 4. Install 3 Meters
        sqlsrv_query($conn, "INSERT INTO Meters (CustomerID, MeterType, SerialNumber) VALUES (?, 'Electricity', ?)", array($newCustID, 'ELE-'.rand(1000,9999)));
        sqlsrv_query($conn, "INSERT INTO Meters (CustomerID, MeterType, SerialNumber) VALUES (?, 'Water', ?)", array($newCustID, 'WTR-'.rand(1000,9999)));
        sqlsrv_query($conn, "INSERT INTO Meters (CustomerID, MeterType, SerialNumber) VALUES (?, 'Gas', ?)", array($newCustID, 'GAS-'.rand(1000,9999)));

        // 5. SHOW CREDENTIALS
        $msg = "<div class='alert success'>
                    ✅ <strong>Customer Registered Successfully!</strong><br>
                    Please give them these login details:<br>
                    Username: <strong>$nic</strong><br>
                    Password: <strong>$randomPass</strong>
                </div>";
    } else {
        $msg = "<div class='alert error'>❌ Error: NIC likely already registered.</div>";
    }
}

// Fetch List
$listSql = "SELECT c.CustomerID, c.NIC, c.City, c.CustomerType, u.FullName 
            FROM Customers c JOIN Users u ON c.UserID = u.UserID ORDER BY c.CustomerID DESC";
$listStmt = sqlsrv_query($conn, $listSql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Customers</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .alert { padding: 15px; margin-bottom: 20px; border-radius: 5px; }
        .success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .error { background: #f8d7da; color: #721c24; }
    </style>
</head>
<body>
<div class="dashboard-container">
    <div class="sidebar">
        <h3>UtilityOne SL</h3>
        <a href="dashboard.php">📊 Dashboard</a>
        <a href="billing.php">💳 Billing Center</a>
        <a href="customers.php" class="active">👥 Manage Customers</a>
        <a href="staff.php">👷 Manage Staff</a>
        <a href="../staff/readings.php">📝 Generate Bill</a>
        <a href="../logout.php">🚪 Logout</a>
    </div>

    <div class="main-content">
        <h1>Register New Customer</h1>
        <?php echo $msg; ?>

        <div class="stat-card">
            <form method="POST" action="">
                <input type="hidden" name="add_customer" value="1">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                    <input type="text" name="fullname" required placeholder="Full Name">
                    <input type="text" name="nic" required placeholder="NIC Number (This will be Username)">
                    <input type="text" name="phone" required placeholder="Phone Number">
                    <select name="type">
                        <option value="Residential">Residential</option>
                        <option value="Business">Business</option>
                        <option value="Government">Government</option>
                    </select>
                </div>
                <input type="text" name="address" required placeholder="Address" style="width: 96%; margin-top: 10px;">
                <input type="text" name="city" required placeholder="City" style="width: 96%; margin-top: 10px;">
                <button type="submit" class="btn-main" style="margin-top: 20px;">Register User</button>
            </form>
        </div>

        <h3 style="margin-top: 30px;">Customer Database</h3>
        <div style="background: white; padding: 20px; border-radius: 10px;">
            <table style="width:100%; text-align:left;">
                <thead><tr><th>Name</th><th>NIC</th><th>City</th><th>Type</th></tr></thead>
                <tbody>
                    <?php while($row = sqlsrv_fetch_array($listStmt, SQLSRV_FETCH_ASSOC)): ?>
                    <tr>
                        <td><?php echo $row['FullName']; ?></td>
                        <td><?php echo $row['NIC']; ?></td>
                        <td><?php echo $row['City']; ?></td>
                        <td><?php echo $row['CustomerType']; ?></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
</body>
</html>