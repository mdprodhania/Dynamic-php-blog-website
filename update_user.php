<?php
require_once "config.php";

$username = $password = "";
$username_err = $password_err = "";

if(isset($_POST["id"]) && !empty($_POST["id"])){
    $id = $_POST["id"];

    if(empty(trim($_POST["username"]))){
        $username_err = "Please enter a username.";
    } else{
        $sql = "SELECT id FROM users WHERE username = ? AND id <> ?";

        if($stmt = mysqli_prepare($link, $sql)){
            mysqli_stmt_bind_param($stmt, "si", $param_username, $param_id);

            $param_username = trim($_POST["username"]);
            $param_id = $id;

            if(mysqli_stmt_execute($stmt)){
                mysqli_stmt_store_result($stmt);

                if(mysqli_stmt_num_rows($stmt) == 1){
                    $username_err = "This username is already taken.";
                } else{
                    $username = trim($_POST["username"]);
                }
            } else{
                echo "Oops! Something went wrong. Please try again later.";
            }

            mysqli_stmt_close($stmt);
        }
    }

    if(!empty(trim($_POST["password"])) && strlen(trim($_POST["password"])) < 6){
        $password_err = "Password must have at least 6 characters.";
    } else if (empty(trim($_POST["password"])) ){
         $password = $_POST["current_password"];
    } else {
         $password = trim($_POST["password"]);
    }

    if(empty($username_err) && empty($password_err)){
        $sql = "UPDATE users SET username = ?, password = ? WHERE id = ?";

        if($stmt = mysqli_prepare($link, $sql)){
            mysqli_stmt_bind_param($stmt, "ssi", $param_username, $param_password, $param_id);

            $param_username = $username;
            $param_password = (!empty(trim($_POST["password"]))) ? password_hash($password, PASSWORD_DEFAULT) : $_POST["current_password_hash"];
            $param_id = $id;

            if(mysqli_stmt_execute($stmt)){
                header("location: admin_dashboard.php");
                exit();
            } else{
                echo "Oops! Something went wrong. Please try again later.";
            }
        }

        mysqli_stmt_close($stmt);
    }

    mysqli_close($link);
} else{
    if(isset($_GET["id"]) && !empty(trim($_GET["id"]))){
        $id =  trim($_GET["id"]);

        $sql = "SELECT * FROM users WHERE id = ?";
        if($stmt = mysqli_prepare($link, $sql)){
            mysqli_stmt_bind_param($stmt, "i", $param_id);

            $param_id = $id;

            if(mysqli_stmt_execute($stmt)){
                $result = mysqli_stmt_get_result($stmt);

                if(mysqli_num_rows($result) == 1){
                    $row = mysqli_fetch_array($result, MYSQLI_ASSOC);

                    $username = $row["username"];
                    $password = ""; 
                    $current_password_hash = $row["password"]; 
                } else{
                    
                    header("location: error.php");
                    exit();
                }

            } else{
                echo "Oops! Something went wrong. Please try again later.";
            }
        }

        mysqli_stmt_close($stmt);

        mysqli_close($link);
    }  else{
        header("location: error.php");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Update User</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .wrapper{
            width: 600px;
            margin: 0 auto;
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-12">
                    <h2 class="mt-5">Update User Record</h2>
                    <p>Please edit the input values and submit to update the user record.</p>
                    <form action="<?php echo htmlspecialchars(basename($_SERVER['REQUEST_URI'])); ?>" method="post">
                        <div class="form-group">
                            <label>Username</label>
                            <input type="text" name="username" class="form-control <?php echo (!empty($username_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $username; ?>">
                            <span class="invalid-feedback"><?php echo $username_err; ?></span>
                        </div>
                        <div class="form-group">
                            <label>Password (leave blank to keep current password)</label>
                            <input type="password" name="password" class="form-control <?php echo (!empty($password_err)) ? 'is-invalid' : ''; ?>">
                            <span class="invalid-feedback"><?php echo $password_err; ?></span>
                        </div>
                        <input type="hidden" name="id" value="<?php echo $id; ?>"/>
                        <input type="hidden" name="current_password_hash" value="<?php echo $current_password_hash; ?>"/>
                        <input type="submit" class="btn btn-primary" value="Submit">
                        <a href="admin_dashboard.php" class="btn btn-secondary ml-2">Cancel</a>
                    </form>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
