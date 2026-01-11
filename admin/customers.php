<?php
session_start();
require '../includes/db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Admin') { header("Location: ../login.php"); exit(); }
$currentPage = 'customers';

$msg = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_customer'])) {
    $name = $_POST['name'];
    $nic = $_POST['nic'];
    $phone = $_POST['phone'];
    $address = $_POST['address'];
    $city = $_POST['city'];
    $custType = $_POST['cust_type']; 
    $username = $_POST['username'];
    $password = md5($_POST['password']); 

    $sqlUser = "INSERT INTO Users (Username, PasswordHash, FullName, UserRole) VALUES (?, ?, ?, 'Customer')";
    $userStmt = sqlsrv_query($conn, $sqlUser, array($username, $password, $name));

    if ($userStmt) {
        $uidRes = sqlsrv_query($conn, "SELECT @@IDENTITY as ID");
        $uid = sqlsrv_fetch_array($uidRes)['ID'];

        $sqlCust = "INSERT INTO Customers (UserID, NIC, Phone, Address, City, CustomerType) VALUES (?, ?, ?, ?, ?, ?)";
        $custStmt = sqlsrv_query($conn, $sqlCust, array($uid, $nic, $phone, $address, $city, $custType));
        
        if ($custStmt) {
            $cidRes = sqlsrv_query($conn, "SELECT @@IDENTITY as ID");
            $cid = sqlsrv_fetch_array($cidRes)['ID'];

            $serialElec = "ELE-" . rand(10000,99999);
            sqlsrv_query($conn, "INSERT INTO Meters (CustomerID, MeterType, SerialNumber) VALUES (?, 'Electricity', ?)", array($cid, $serialElec));

            $serialWater = "WAT-" . rand(10000,99999);
            sqlsrv_query($conn, "INSERT INTO Meters (CustomerID, MeterType, SerialNumber) VALUES (?, 'Water', ?)", array($cid, $serialWater));

            $serialGas = "GAS-" . rand(10000,99999);
            sqlsrv_query($conn, "INSERT INTO Meters (CustomerID, MeterType, SerialNumber) VALUES (?, 'Gas', ?)", array($cid, $serialGas));

            $msg = "<div class='alert success'>✅ Customer Added with Electricity, Water, and Gas meters!</div>";
        } else {
            $errors = sqlsrv_errors();
            $msg = "<div class='alert error'>❌ Failed to create Customer profile: " . $errors[0]['message'] . "</div>";
        }
    } else {
        $errors = sqlsrv_errors();
        $msg = "<div class='alert error'>❌ Failed to create User login: " . $errors[0]['message'] . "</div>";
    }
}

// --- DELETE LOGIC ---
if (isset($_GET['del'])) {
    $id = intval($_GET['del']);
    sqlsrv_query($conn, "DELETE FROM Meters WHERE CustomerID = ?", array($id));
    sqlsrv_query($conn, "DELETE FROM Customers WHERE CustomerID = ?", array($id));
    $msg = "<div class='alert success'>🗑️ Customer Deleted.</div>";
}

$sql = "SELECT c.CustomerID, u.FullName, c.NIC, c.City, c.CustomerType 
        FROM Customers c 
        JOIN Users u ON c.UserID = u.UserID 
        ORDER BY c.CustomerID DESC";
$stmt = sqlsrv_query($conn, $sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Customers</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<div class="dashboard-container">
    <?php include 'sidebar.php'; ?>
    <div class="main-content">
        <div class="top-header">
            <h2>👥 Manage Customers</h2>
        </div>
        <?php echo $msg; ?>

        <div class="content-box">
            <h3 style="margin-top:0;">+ Add New Customer</h3>
            <form method="POST" style="display:grid; grid-template-columns: 1fr 1fr; gap:15px;">
                <input type="text" name="name" placeholder="Full Name" required>
                <input type="text" name="nic" placeholder="NIC Number" required>
                <input type="text" name="phone" placeholder="Phone Number" required>
                <input type="text" name="address" placeholder="Address" required>
                <input type="text" name="city" placeholder="City" required>

                <select name="cust_type" required>
                    <option value="">-- Select Type --</option>
                    <option value="Domestic">Domestic (Household)</option>
                    <option value="Commercial">Commercial (Business)</option>
                </select>

                <input type="text" name="username" placeholder="Create Username" required>
                <input type="password" name="password" placeholder="Create Password" required>
                
                <button type="submit" name="add_customer" class="btn-main" style="grid-column: span 2;">Create Customer & Assign 3 Meters</button>
            </form>
        </div>

        <div class="table-container" style="margin-top:20px;">
            <table>
                <thead>
                    <tr><th>ID</th><th>Name</th><th>NIC</th><th>City</th><th>Type</th><th>Action</th></tr>
                </thead>
                <tbody>
                    <?php if ($stmt && sqlsrv_has_rows($stmt)): ?>
                        <?php while($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)): ?>
                        <tr>
                            <td>#<?php echo $row['CustomerID']; ?></td>
                            <td><strong><?php echo $row['FullName']; ?></strong></td>
                            <td><?php echo $row['NIC']; ?></td>
                            <td><?php echo $row['City']; ?></td>
                            <td><span class="status-badge"><?php echo $row['CustomerType']; ?></span></td>
                            <td>
                                <a href="customers.php?del=<?php echo $row['CustomerID']; ?>" style="color:red;" onclick="return confirm('Are you sure?');">Delete</a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="6" style="text-align:center;">No customers found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
</body>
</html>