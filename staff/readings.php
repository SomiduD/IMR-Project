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
           
            echo "<option value='".$row['MeterID']."' data-type='".$row['MeterType']."'>".$row['MeterType']." (".$row['SerialNumber'].")</option>";
        }
    } else {
        echo "<option value=''>No Meters Found</option>";
    }
    exit; 
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $meterID = $_POST['meter_id'];
    
    $current = floatval($_POST['reading']); 
    $meterType = isset($_POST['meter_type_hidden']) ? $_POST['meter_type_hidden'] : 'Electricity'; // Default if missing
    
    $prev = 0.0; 
    $checkSql = "SELECT TOP 1 CurrentReading FROM Readings WHERE MeterID = ? ORDER BY ReadingID DESC";
    $checkStmt = sqlsrv_query($conn, $checkSql, array($meterID));
    
    if ($checkStmt && sqlsrv_has_rows($checkStmt)) {
        $row = sqlsrv_fetch_array($checkStmt, SQLSRV_FETCH_ASSOC);
        $prev = floatval($row['CurrentReading']); // Force Float
    }

    if ($current < $prev) {
        $msg = "<div class='alert error'> Error: New reading ($current) cannot be lower than previous ($prev).</div>";
    } else {
        
        $units = floatval($current - $prev);
        $amount = 0.0;
        
        if ($meterType == 'Electricity') {
            $amount += 400; 
            
            if ($units <= 30) {
                $amount += $units * 15;
            } elseif ($units <= 60) {
                $amount += (30 * 15) + (($units - 30) * 30);
            } else {
                $amount += (30 * 15) + (30 * 30) + (($units - 60) * 45);
            }

        } elseif ($meterType == 'Water') {
            $amount += 200; 
            if ($units <= 15) {
                $amount += $units * 8;
            } elseif ($units <= 30) {
                $amount += (15 * 8) + (($units - 15) * 16);
            } else {
                $amount += (15 * 8) + (15 * 16) + (($units - 30) * 24);
            }

        } else { 
            $amount += 100; 
            $amount += $units * 50; 
        }

        $sqlRead = "INSERT INTO Readings (MeterID, CurrentReading, PreviousReading) VALUES (?, ?, ?)";
        if (sqlsrv_query($conn, $sqlRead, array($meterID, $current, $prev))) {
            
            $rIDQuery = sqlsrv_query($conn, "SELECT @@IDENTITY AS ID");
            $rID = sqlsrv_fetch_array($rIDQuery)['ID'];
            
            $sqlBill = "INSERT INTO Bills (ReadingID, BillMonth, Amount, DueDate, Status) 
                        VALUES (?, ?, ?, DATEADD(day, 30, GETDATE()), 'Unpaid')";
            
            $params = array($rID, date('F'), $amount);
            
            if(sqlsrv_query($conn, $sqlBill, $params)) {
                $msg = "<div class='alert success'> Bill Generated!<br>Type: $meterType | Units: $units | <strong>LKR " . number_format($amount, 2) . "</strong></div>";
            } else {
                // Debug Info
                $e = sqlsrv_errors();
                $err = $e ? $e[0]['message'] : "Unknown";
                $msg = "<div class='alert error'> Bill Error: $err</div>";
            }
        } else {
             $e = sqlsrv_errors();
             $err = $e ? $e[0]['message'] : "Unknown";
             $msg = "<div class='alert error'> Reading Error: $err</div>";
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
            if (cid == "") return;
            fetch('readings.php?get_meters=' + cid)
                .then(response => response.text())
                .then(data => {
                    document.getElementById('meter_select').innerHTML = data;
                    updateMeterType(); 
                });
        }

        function updateMeterType() {
            var select = document.getElementById('meter_select');
            if (select.selectedIndex >= 0) {
                var type = select.options[select.selectedIndex].getAttribute('data-type');
                if(type) {
                    document.getElementById('meter_type_hidden').value = type;
                } else {
                    document.getElementById('meter_type_hidden').value = "Electricity"; // Default
                }
            }
        }
    </script>
</head>
<body>

<div class="dashboard-container">
    <div class="sidebar">
        <h3>UtilityOne SL</h3>
        <a href="../admin/dashboard.php"> Dashboard</a>
        <a href="../logout.php"> Logout</a>
    </div>

    <div class="main-content">
        <div class="form-card" style="max-width: 600px; margin: 0 auto;">
            <h2 style="margin-top:0; color:#1e293b;">📝 New Meter Reading</h2>
            <?php echo $msg; ?>

            <form method="POST">
                <input type="hidden" name="meter_type_hidden" id="meter_type_hidden" value="Electricity">

                <label>Select Customer:</label>
                <select name="customer_id" onchange="fetchMeters(this.value)" required>
                    <option value="">-- Choose Customer --</option>
                    <?php while($row = sqlsrv_fetch_array($customers, SQLSRV_FETCH_ASSOC)): ?>
                        <option value="<?php echo $row['CustomerID']; ?>"><?php echo $row['FullName']; ?> (<?php echo $row['NIC']; ?>)</option>
                    <?php endwhile; ?>
                </select>

                <label>Select Meter:</label>
                <select name="meter_id" id="meter_select" onchange="updateMeterType()" required>
                    <option value="">Select Customer First</option>
                </select>

                <label>Current Reading:</label>
                <input type="number" name="reading" required placeholder="Enter new value">

                <button type="submit" class="btn-main">Submit & Calculate</button>
            </form>
        </div>
    </div>
</div>
</body>
</html>