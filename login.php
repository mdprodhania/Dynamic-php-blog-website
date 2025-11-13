<?php
// Initialize the session
session_start();

// Include config file
require_once "config.php";

// Define variables and initialize with empty values
$username = $password = "";
$username_err = $password_err = $login_err = "";

// Store the original username input before potential modification
$input_username = "";

// Processing form data when form is submitted
if($_SERVER["REQUEST_METHOD"] == "POST"){

    // Check if username is empty
    if(empty(trim($_POST["username"]))){
        $username_err = "Please enter username.";
    } else{
        $username = trim($_POST["username"]);
        $input_username = $username; // Store the original input
    }

    // Check if password is empty
    if(empty(trim($_POST["password"]))){
        $password_err = "Please enter your password.";
    } else{
        $password = trim($_POST["password"]);
    }

    // Validate credentials
    if(empty($username_err) && empty($password_err)){
        // Check in admins table
        $sql_admin = "SELECT id, username, password FROM admins WHERE username = ?";
        if($stmt_admin = mysqli_prepare($link, $sql_admin)){
            mysqli_stmt_bind_param($stmt_admin, "s", $param_username);
            $param_username = $username;
            if(mysqli_stmt_execute($stmt_admin)){
                mysqli_stmt_store_result($stmt_admin);
                if(mysqli_stmt_num_rows($stmt_admin) == 1){
                    mysqli_stmt_bind_result($stmt_admin, $id, $username, $hashed_password);
                    if(mysqli_stmt_fetch($stmt_admin)){
                        if(password_verify($password, $hashed_password)){
                            session_start();
                            $_SESSION["loggedin"] = true;
                            $_SESSION["id"] = $id;
                            $_SESSION["username"] = $username;
                            $_SESSION["is_admin"] = true; // Mark as admin
                            header("location: admin_dashboard.php");
                            exit();
                        }
                    }
                }
            }
            mysqli_stmt_close($stmt_admin);
        }

        // If not an admin, check in users table
        $sql_user = "SELECT id, username, password FROM users WHERE username = ?";
        if($stmt_user = mysqli_prepare($link, $sql_user)){
            mysqli_stmt_bind_param($stmt_user, "s", $param_username);
            $param_username = $input_username; // Use the original input username
            if(mysqli_stmt_execute($stmt_user)){
                mysqli_stmt_store_result($stmt_user);
                if(mysqli_stmt_num_rows($stmt_user) == 1){
                    mysqli_stmt_bind_result($stmt_user, $id, $username, $hashed_password);
                    if(mysqli_stmt_fetch($stmt_user)){
                        if(password_verify($password, $hashed_password)){
                            session_start();
                            $_SESSION["loggedin"] = true;
                            $_SESSION["id"] = $id;
                            $_SESSION["username"] = $username;
                            $_SESSION["is_admin"] = false; // Mark as regular user
                            header("location: user_dashboard.php"); // Redirect to user dashboard
                            exit();
                        }
                    }
                }
            }
            mysqli_stmt_close($stmt_user);
        }

        // If no user found in either table or password incorrect
        $login_err = "Invalid username or password.";
    }

    // Close connection
    mysqli_close($link);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="login-container">
        <h2>Login</h2>
        <?php
        if(!empty($login_err)){
            echo '<div class="alert alert-danger">' . $login_err . '</div>';
        }
        ?>
        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
            <div class="form-group">
                <label for="username">Username:</label>
                <input type="text" id="username" name="username" class="<?php echo (!empty($username_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $username; ?>" required>
                <span class="invalid-feedback"><?php echo $username_err; ?></span>
            </div>
            <div class="form-group">
                <label for="password">Password:</label>
                <input type="password" id="password" name="password" class="<?php echo (!empty($password_err)) ? 'is-invalid' : ''; ?>" required>
                <span class="invalid-feedback"><?php echo $password_err; ?></span>
            </div>
            <button type="submit">Login</button>
        </form>
        <p>Don't have an account? <a href="register.php">Sign up now</a>.</p>
    </div>
</body>
</html>
