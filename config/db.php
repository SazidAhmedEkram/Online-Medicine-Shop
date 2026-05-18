<?php

// database settings
$db_host = "localhost";
$db_user = "root";
$db_password = "";
$db_name = "online_medicine_shop";

// use simple true or false error checking
mysqli_report(MYSQLI_REPORT_OFF);

// connect to mysql server first
$conn = mysqli_connect($db_host, $db_user, $db_password);

// simple fallback for some xampp setups
if (!$conn) {
    $db_password = "root";
    $conn = mysqli_connect($db_host, $db_user, $db_password);
}

// stop if mysql connection fails
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// create database if it does not exist
mysqli_query($conn, "CREATE DATABASE IF NOT EXISTS " . $db_name . " CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");

// select project database
if (!mysqli_select_db($conn, $db_name)) {
    die("Database selection failed: " . mysqli_error($conn));
}

mysqli_set_charset($conn, "utf8mb4");

function db()
{
    global $conn;
    return $conn;
}
