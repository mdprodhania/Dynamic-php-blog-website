<?php
// Include config file
require_once "config.php";

// Define variables and initialize with empty values
$username = "admin"; // Default admin username
$password = "password"; // Default admin password

// Hash the password
$hashed_password = password_hash($password, PASSWORD_DEFAULT);

// Prepare an insert statement
$sql = "INSERT INTO admins (username, password) VALUES (?, ?)";

if($stmt = mysqli_prepare($link, $sql)){
    // Bind variables to the prepared statement as parameters
    mysqli_stmt_bind_param($stmt, "ss", $param_username, $param_password);

    // Set parameters
    $param_username = $username;
    $param_password = $hashed_password;

    // Attempt to execute the prepared statement
    if(mysqli_stmt_execute($stmt)){
        echo "Admin user '" . $username . "' added successfully!";
    } else{
        echo "ERROR: Could not execute query: " . mysqli_error($link);
    }

    // Close statement
    mysqli_stmt_close($stmt);
}

// Close connection
mysqli_close($link);
?>