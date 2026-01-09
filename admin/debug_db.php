<?php
require '../includes/db.php';

echo "<h1>🛠️ Database Raw Data Viewer</h1>";

// 1. Check READINGS (Did the reading get saved?)
$sql1 = "SELECT TOP 5 * FROM Readings ORDER BY ReadingID DESC";
$stmt1 = sqlsrv_query($conn, $sql1);
echo "<h3>Recent Readings (Check if your reading is here)</h3>";
echo "<table border='1'><tr><th>ID</th><th>MeterID</th><th>CurrentReading</th></tr>";
while($row = sqlsrv_fetch_array($stmt1, SQLSRV_FETCH_ASSOC)) {
    echo "<tr><td>".$row['ReadingID']."</td><td>".$row['MeterID']."</td><td>".$row['CurrentReading']."</td></tr>";
}
echo "</table>";

// 2. Check BILLS (Did the bill get created?)
$sql2 = "SELECT TOP 5 * FROM Bills ORDER BY BillID DESC";
$stmt2 = sqlsrv_query($conn, $sql2);
echo "<h3>Recent Bills (If empty, the Procedure failed)</h3>";
echo "<table border='1'><tr><th>BillID</th><th>Amount</th><th>Status</th></tr>";
$hasBills = false;
while($row = sqlsrv_fetch_array($stmt2, SQLSRV_FETCH_ASSOC)) {
    $hasBills = true;
    echo "<tr><td>".$row['BillID']."</td><td>".$row['Amount']."</td><td>".$row['Status']."</td></tr>";
}
echo "</table>";

if (!$hasBills) {
    echo "<h2 style='color:red;'>⚠️ CONCLUSION: Readings are saving, but BILLS are failing!</h2>";
    echo "<p>Run the SQL Fix below in SSMS immediately.</p>";
}
?>