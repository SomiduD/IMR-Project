<?php

function checkLogin() {
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    if (!isset($_SESSION['user_id'])) {
        header("Location: ../login.php");
        exit();
    }
}


function auditLog($conn, $actionText) {
    $sql = "INSERT INTO AuditLog (ActionText, LogDate) VALUES (?, GETDATE())";
    $params = array($actionText);
    sqlsrv_query($conn, $sql, $params);
}

function formatCurrency($amount) {
    if (!is_numeric($amount)) {
        return "LKR 0.00";
    }
    return 'LKR ' . number_format($amount, 2);
}

function cleanInput($data) {
    return htmlspecialchars(stripslashes(trim($data)));
}
?>