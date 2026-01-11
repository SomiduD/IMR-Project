<?php
session_start();
require '../includes/db.php';
require '../includes/functions.php';

if (!isset($_SESSION['role'])) { header("Location: ../login.php"); exit(); }

$msg = "";

$customers = sqlsrv_query($conn, "SELECT c.CustomerID, u.FullName, c.NIC FROM Customers c JOIN Users u ON c.UserID = u.UserID ORDER BY u.FullName ASC");

if (isset($_GET['get_meters'])) {
    $cid = intval($_GET['get_meters']);
    $sql = "SELECT MeterID, MeterType, SerialNumber FROM Meters WHERE CustomerID = ?";
    $stmt = sqlsrv_query($conn, $sql, array($cid));
    
    if ($stmt && sqlsrv_has_rows($stmt)) {
        echo "<option value=''>-- Select Meter --</option>";
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            echo "<option value='".$row['MeterID']."'>".$row['MeterType']." (".$row['SerialNumber'].")</option>";
        }
    } else {
        echo "<option value=''>❌ No Meters Found for this Customer</option>";
    }
    exit; 
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $meterID = $_POST['meter_id'];
    $current = $_POST['reading'];
    $userID = $_SESSION['user_id']; 

    if (empty($meterID) || empty($current)) {
        $msg = "<div class='alert error'>❌ Please select a meter and enter a reading.</div>";
    } else {

        $sql = "{CALL sp_GenerateBill(?, ?, ?)}";
        $params = array($meterID, $current, $userID);
     
        
        if (@sqlsrv_query($conn, $sql, $params)) {
             $msg = "<div class='alert success'>✅ Bill Generated Successfully!</div>";
        } else {
             $errors = sqlsrv_errors();
             $errorText = ($errors) ? $errors[0]['message'] : "Unknown Error";
            
             if (strpos($errorText, 'CK_Reading_Logic') !== false) {
                 $msg = "<div class='alert error'>❌ Error: New reading cannot be lower than previous reading.</div>";
             } else {
                 $msg = "<div class='alert error'>❌ System Error: $errorText</div>";
             }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Enter Readings</title>
    <link rel="stylesheet" href="../assets/css/style.css?v=<?php echo time(); ?>">
    <script>
        function fetchMeters(cid) {
            if (cid == "") {
                document.getElementById('meter_select').innerHTML = "<option value=''>Select Customer First</option>";
                return;
            }
            fetch('readings.php?get_meters=' + cid)
                .then(response => response.text())
                .then(data => document.getElementById('meter_select').innerHTML = data);
        }
    </script>
</head>
<body>

<div class="dashboard-container">
    <div class="sidebar">
        <h3>UtilityOne SL</h3>
        <a href="../admin/dashboard.php">📊 Dashboard</a>
        <a href="../logout.php">🚪 Logout</a>
    </div>

    <div class="main-content">
        <div class="form-card" style="max-width: 600px; margin: 0 auto;">
            <h2 style="margin-top:0; color:#1e293b;">📝 New Meter Reading</h2>
            <?php echo $msg; ?>

            <form method="POST">
                <label>Select Customer:</label>
                <select name="customer_id" onchange="fetchMeters(this.value)" required>
                    <option value="">-- Choose Customer --</option>
                    <?php while($row = sqlsrv_fetch_array($customers, SQLSRV_FETCH_ASSOC)): ?>
                        <option value="<?php echo $row['CustomerID']; ?>"><?php echo $row['FullName']; ?> (<?php echo $row['NIC']; ?>)</option>
                    <?php endwhile; ?>
                </select>

                <label>Select Meter:</label>
                <select name="meter_id" id="meter_select" required>
                    <option value="">Select Customer First</option>
                </select>

                <label>Current Reading:</label>
                <input type="number" name="reading" required placeholder="Enter new value">

                <button type="submit" class="btn-main">Submit & Generate Bill</button>
            </form>
        </div>
    </div>
</div>
</body>
</html>