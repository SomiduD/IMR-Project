<?php
session_start();
require 'includes/db.php';

$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $password = $_POST['password'];

    $passHash = md5($password); 

    $sql = "SELECT UserID, FullName, UserRole FROM Users WHERE Username = ? AND PasswordHash = ?";
    $params = array($username, $passHash);
    $stmt = sqlsrv_query($conn, $sql, $params);

    if ($stmt && sqlsrv_has_rows($stmt)) {
        $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
        
        $_SESSION['user_id'] = $row['UserID'];
        $_SESSION['name'] = $row['FullName'];
        $_SESSION['role'] = $row['UserRole'];

        if ($row['UserRole'] == 'Admin') {
            header("Location: admin/dashboard.php");
        } 
        elseif ($row['UserRole'] == 'Customer') {
            header("Location: customer/dashboard.php");
        } 
        elseif ($row['UserRole'] == 'MeterReader') {
            header("Location: staff/readings.php"); 
        }
        else {
            header("Location: index.php"); 
        }
        exit();
    } else {
        $error = "❌ Invalid Username or Password";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login - UtilityOne SL</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        body { 
            background: url('assets/img/01.jpeg') no-repeat center center fixed; 
            background-size: cover; 
            
            display: flex; 
            justify-content: center; 
            align-items: center; 
            height: 100vh; 
            margin: 0; 
            font-family: Arial, sans-serif;
        }
        .login-card { 
            background: rgba(255, 255, 255, 0.95); 
            padding: 40px; 
            border-radius: 10px; 
            box-shadow: 0 4px 15px rgba(0,0,0,0.2); 
            width: 100%; 
            max-width: 400px; 
            text-align: center; 
        }
        input { 
            width: 100%; 
            padding: 12px; 
            margin: 10px 0; 
            border: 1px solid #ddd; 
            border-radius: 5px; 
            box-sizing: border-box; 
        }
        .btn-main { 
            width: 100%; 
            padding: 12px; 
            margin-top: 10px;
            background-color: #007bff; 
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1rem;
        }
        .btn-main:hover {
            background-color: #0056b3;
        }
        .logo { 
            font-size: 1.5rem; 
            font-weight: bold; 
            color: #333; 
            margin-bottom: 20px; 
            display: block; 
        }
    </style>
</head>
<body>

<div class="login-card">
    <span class="logo">UtilityOne SL</span>
    <h3 style="color: #666; font-weight: normal; margin-bottom: 30px;">Sign in to your account</h3>
    
    <?php if($error): ?>
        <div style="background: #f8d7da; color: #721c24; padding: 10px; border-radius: 5px; margin-bottom: 20px; font-size: 0.9rem;">
            <?php echo $error; ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="">
        <input type="text" name="username" placeholder="Username / NIC" required>
        <input type="password" name="password" placeholder="Password" required>
        <button type="submit" class="btn-main">Login</button>
    </form>
    
    <p style="margin-top: 20px; font-size: 0.8rem; color: #888;">
        Use the credentials provided by the Admin.
    </p>
</div>

</body>
</html>