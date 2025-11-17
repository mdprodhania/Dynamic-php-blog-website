<?php
// Initialize the session
session_start();

// Check if the user is logged in, if not then redirect him to login page
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    header("location: login.php");
    exit;
}

// Check if the logged-in user is an admin. If not, redirect to user dashboard.
if(!isset($_SESSION["is_admin"]) || $_SESSION["is_admin"] !== true){
    header("location: user_dashboard.php");
    exit;
}

// Include config file
require_once "config.php";

// Fetch users from the database (assuming a 'users' table exists)
$sql = "SELECT id, username, created_at FROM users";
$result = mysqli_query($link, $sql);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .wrapper{
            width: 80%;
            margin: 0 auto;
        }
        .page-header h2{
            margin-top: 0;
        }
        table tr td:last-child a{
            margin-right: 15px;
        }
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border: 1px solid transparent;
            border-radius: 4px;
        }
        .alert-danger {
            color: #a94442;
            background-color: #f2dede;
            border-color: #ebccd1;
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="page-header">
            <h1>Hi, <b><?php echo htmlspecialchars($_SESSION["username"]); ?></b>. Welcome to your admin dashboard.</h1>
        </div>
        <p>
            <a href="logout.php" class="btn btn-danger">Sign Out of Your Account</a>
        </p>

        <h3>User Management</h3>
        <p>This is where you can manage users. <a href="create_user.php" class="btn btn-success">Add New User</a></p>

        <?php
        if($result) {
            if(mysqli_num_rows($result) > 0){
                echo "<table class=\"table table-bordered table-striped\">";
                    echo "<thead>";
                        echo "<tr>";
                            echo "<th>#</th>";
                            echo "<th>Username</th>";
                            echo "<th>Created At</th>";
                            echo "<th>Action</th>";
                        echo "</tr>";
                    echo "</thead>";
                    echo "<tbody>";
                    while($row = mysqli_fetch_array($result)){
                        echo "<tr>";
                            echo "<td>" . $row['id'] . "</td>";
                            echo "<td>" . $row['username'] . "</td>";
                            echo "<td>" . $row['created_at'] . "</td>";
                            echo "<td>";
                                echo "<a href=\"read_user.php?id=". $row['id'] ."\" title=\"View Record\" data-toggle=\"tooltip\"><span class=\"glyphicon glyphicon-eye-open\">View</span></a>";
                                echo "<a href=\"update_user.php?id=". $row['id'] ."\" title=\"Update Record\" data-toggle=\"tooltip\"><span class=\"glyphicon glyphicon-pencil\">Edit</span></a>";
                                echo "<a href=\"delete_user.php?id=". $row['id'] ."\" title=\"Delete Record\" data-toggle=\"tooltip\"><span class=\"glyphicon glyphicon-trash\">Delete</span></a>";
                            echo "</td>";
                        echo "</tr>";
                    }
                    echo "</tbody>";
                echo "</table>";
                // Free result set
                mysqli_free_result($result);
            } else{
                echo '<div class="alert alert-danger"><em>No records were found.</em></div>';
            }
        } else{
            echo "Oops! Something went wrong. Please try again later.";
        }

        // Close connection
        mysqli_close($link);
        ?>

        <h3>Post Management</h3>
        <p>This is where you can manage posts.</p>

        <h3>Comment Management</h3>
        <p>This is where you can <a href="admin_comments.php">manage comments</a>.</p>

        <h3>Category Management</h3>
        <p>This is where you can <a href="admin_categories.php">manage post categories</a>.</p>

    </div>
</body>
</html>
