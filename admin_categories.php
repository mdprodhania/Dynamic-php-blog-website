<?php
// Initialize the session
session_start();

// Check if the user is logged in and is an admin, otherwise redirect
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || !isset($_SESSION["is_admin"]) || $_SESSION["is_admin"] !== true){
    header("location: login.php");
    exit;
}

// Include config file
require_once "config.php";

// Fetch all categories from the database
$sql = "SELECT id, name, created_at FROM categories ORDER BY name ASC";
$result = mysqli_query($link, $sql);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Categories</title>
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
        .alert-info {
            color: #31708f;
            background-color: #d9edf7;
            border-color: #bce8f1;
        }
        .alert-success {
            color: #155724;
            background-color: #d4edda;
            border-color: #c3e6cb;
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="page-header">
            <h1>Category Management</h1>
        </div>
        <p>
            <a href="admin_dashboard.php" class="btn btn-primary">Back to Dashboard</a>
            <a href="create_category.php" class="btn btn-success">Add New Category</a>
            <a href="logout.php" class="btn btn-danger float-right">Sign Out</a>
        </p>

        <?php
        if(isset($_SESSION["category_action_message"])){ 
            echo '<div class="alert alert-success">' . $_SESSION["category_action_message"] . '</div>';
            unset($_SESSION["category_action_message"]);
        }

        if($result) {
            if(mysqli_num_rows($result) > 0){
                echo "<table class=\"table table-bordered table-striped\">";
                    echo "<thead>";
                        echo "<tr>";
                            echo "<th>#</th>";
                            echo "<th>Category Name</th>";
                            echo "<th>Created At</th>";
                            echo "<th>Action</th>";
                        echo "</tr>";
                    echo "</thead>";
                    echo "<tbody>";
                    while($row = mysqli_fetch_array($result)){
                        echo "<tr>";
                            echo "<td>" . $row['id'] . "</td>";
                            echo "<td>" . htmlspecialchars($row['name']) . "</td>";
                            echo "<td>" . $row['created_at'] . "</td>";
                            echo "<td>";
                                echo "<a href=\"update_category.php?id=". $row['id'] ."\" title=\"Update Record\" data-toggle=\"tooltip\"><span class=\"glyphicon glyphicon-pencil\">Edit</span></a>";
                                echo "<a href=\"delete_category.php?id=". $row['id'] ."\" title=\"Delete Record\" data-toggle=\"tooltip\"><span class=\"glyphicon glyphicon-trash\">Delete</span></a>";
                            echo "</td>";
                        echo "</tr>";
                    }
                    echo "</tbody>";
                echo "</table>";
                mysqli_free_result($result);
            } else{
                echo '<div class="alert alert-info"><em>No categories were found.</em></div>';
            }
        } else{
            echo "Oops! Something went wrong. Please try again later.";
        }

        mysqli_close($link);
        ?>

    </div>
</body>
</html>
