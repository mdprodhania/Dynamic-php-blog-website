<?php
require_once "config.php";

$username = "admin"; 
$password = "password"; 

$hashed_password = password_hash($password, PASSWORD_DEFAULT);


$sql = "INSERT INTO admins (username, password) VALUES (?, ?)";

if($stmt = mysqli_prepare($link, $sql)){
   
    mysqli_stmt_bind_param($stmt, "ss", $param_username, $param_password);

    $param_username = $username;
    $param_password = $hashed_password;

    if(mysqli_stmt_execute($stmt)){
        echo "Admin user '" . $username . "' added successfully!";
    } else{
        echo "ERROR: Could not execute query: " . mysqli_error($link);
    }

    mysqli_stmt_close($stmt);
}

mysqli_close($link);
?>
