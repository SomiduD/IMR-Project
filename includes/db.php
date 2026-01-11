<?php

$serverName = ".\SQLEXPRESS";
$connectionOptions = array(
    "Database" => "UOSL",
    "Uid" => "",
    "PWD" => "",
    "TrustServerCertificate" => true
);

$conn = sqlsrv_connect($serverName, $connectionOptions);

if (!$conn) {
    die(print_r(sqlsrv_errors(), true));
}
?>