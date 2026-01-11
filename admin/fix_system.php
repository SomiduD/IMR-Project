<?php
require '../includes/db.php';

echo "<h1> UtilityOne System Repair Tool</h1>";

$testNIC = "TEST" . rand(1000,9999);
$sqlUser = "INSERT INTO Users (Username, PasswordHash, FullName, UserRole) VALUES ('$testNIC', '123', 'System Test User', 'Customer')";
sqlsrv_query($conn, $sqlUser);
echo " Test User Created ($testNIC)<br>";

$uidStmt = sqlsrv_query($conn, "SELECT TOP 1 UserID FROM Users WHERE Username = '$testNIC'");
$uid = sqlsrv_fetch_array($uidStmt)['UserID'];

$sqlCust = "INSERT INTO Customers (UserID, NIC, Address, City, Phone, CustomerType) VALUES ($uid, '$testNIC', 'Repair Road', 'FixCity', '000', 'Residential')";
sqlsrv_query($conn, $sqlCust);
echo " Test Customer Profile Created<br>";

$cidStmt = sqlsrv_query($conn, "SELECT TOP 1 CustomerID FROM Customers WHERE NIC = '$testNIC'");
$cid = sqlsrv_fetch_array($cidStmt)['CustomerID'];

$serial = "FIX-" . rand(1000,9999);
$sqlMeter = "INSERT INTO Meters (CustomerID, MeterType, SerialNumber) VALUES ($cid, 'Water', '$serial')";
sqlsrv_query($conn, $sqlMeter);
echo " Test Meter Installed ($serial)<br>";

$midStmt = sqlsrv_query($conn, "SELECT TOP 1 MeterID FROM Meters WHERE SerialNumber = '$serial'");
$mid = sqlsrv_fetch_array($midStmt)['MeterID'];

$billSQL = "INSERT INTO Bills (MeterID, ReadingID, BillMonth, Amount, DueDate, Status, IsEmailSent) 
            VALUES ($mid, 0, 'Test Month', 5000.00, GETDATE(), 'Unpaid', 0)";
$stmt = sqlsrv_query($conn, $billSQL);

if ($stmt) {
    echo "<h2 style='color:green;'> SUCCESS: A Test Bill was forced into the database.</h2>";
    echo "<a href='billing.php'><h3> Click Here to Go to Billing Page and See It</h3></a>";
} else {
    echo "<h2 style='color:red;'> FAILURE: Database rejected the bill.</h2>";
    echo "SQL Error: ";
    print_r(sqlsrv_errors());
}
?>