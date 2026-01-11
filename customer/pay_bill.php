<?php
session_start();
require '../includes/db.php';
require '../includes/functions.php';

if ($_SESSION['role'] !== 'Customer') {
    header("Location: ../login.php");
    exit();
}

$billID = isset($_GET['id']) ? intval($_GET['id']) : 0;
$msg = "";
$sql = "SELECT b.*, m.MeterType 
        FROM Bills b 
        JOIN Meters m ON b.MeterID = m.MeterID 
        WHERE b.BillID = ? AND m.CustomerID = (SELECT CustomerID FROM Customers WHERE UserID = ?)";
$stmt = sqlsrv_query($conn, $sql, array($billID, $_SESSION['user_id']));
$bill = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);

if (!$bill) die("Invalid Bill or Access Denied.");
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $amount = $bill['Amount'];
    $custIDSql = "SELECT CustomerID FROM Customers WHERE UserID = ?";
    $custStmt = sqlsrv_query($conn, $custIDSql, array($_SESSION['user_id']));
    $custRow = sqlsrv_fetch_array($custStmt, SQLSRV_FETCH_ASSOC);
    $customerID = $custRow['CustomerID'];

    $paySql = "INSERT INTO Payments (BillID, CustomerID, AmountPaid, PaymentMethod) VALUES (?, ?, ?, 'Card')";
    $payStmt = sqlsrv_query($conn, $paySql, array($billID, $customerID, $amount));

    if ($payStmt) {
        
        header("Location: my_usage.php?success=1");
        exit();
    } else {
        $msg = "<div class='alert error'>Payment Failed. Try again.</div>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Secure Payment - UtilityOne SL</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .pay-card { max-width: 500px; margin: 50px auto; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); }
        .input-group { margin-bottom: 15px; }
        .input-group label { display: block; margin-bottom: 5px; font-weight: bold; }
        .amount-display { font-size: 2rem; color: #28a745; font-weight: bold; text-align: center; margin: 20px 0; }
    </style>
</head>
<body>

<div class="dashboard-container">
    <div class="main-content" style="margin-left: 0; background: #f4f6f9;">
        <div class="pay-card">
            <h2 style="text-align: center;">🔒 Secure Payment Gateway</h2>
            <p style="text-align: center;">Paying for: <strong><?php echo $bill['MeterType']; ?> Bill</strong></p>
            
            <div class="amount-display"><?php echo formatCurrency($bill['Amount']); ?></div>

            <?php echo $msg; ?>

            <form method="POST" action="">
                <div class="input-group">
                    <label>Cardholder Name</label>
                    <input type="text" placeholder="As on card" required>
                </div>
                <div class="input-group">
                    <label>Card Number</label>
                    <input type="text" placeholder="xxxx-xxxx-xxxx-xxxx" required>
                </div>
                <div style="display: flex; gap: 10px;">
                    <div class="input-group" style="flex: 1;">
                        <label>Expiry</label>
                        <input type="text" placeholder="MM/YY" required>
                    </div>
                    <div class="input-group" style="flex: 1;">
                        <label>CVV</label>
                        <input type="text" placeholder="123" required>
                    </div>
                </div>

                <button type="submit" class="btn-main" style="background: #28a745;">Confirm Payment</button>
                <a href="my_usage.php" style="display: block; text-align: center; margin-top: 15px; color: #666; text-decoration: none;">Cancel</a>
            </form>
        </div>
    </div>
</div>

</body>
</html>