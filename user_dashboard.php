<?php
// Initialize the session
session_start();

// Check if the user is logged in, if not then redirect him to login page
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    header("location: login.php");
    exit;
}

// Include config file
require_once "config.php";

// Fetch posts for the current user
$user_id = $_SESSION["id"];
$sql = "SELECT posts.id, posts.title, posts.created_at, posts.likes_count, posts.view_count, categories.name AS category_name FROM posts LEFT JOIN categories ON posts.category_id = categories.id WHERE user_id = ? ORDER BY created_at DESC";

if($stmt = mysqli_prepare($link, $sql)){
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
} else {
    echo "Oops! Something went wrong. Please try again later.";
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Author Dashboard</title>
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
            <h1>Hi, <b><?php echo htmlspecialchars($_SESSION["username"]); ?></b>. Welcome to your author dashboard.</h1>
        </div>
        <p>
            <a href="logout.php" class="btn btn-danger">Sign Out of Your Account</a>
        </p>

        <h3>Your Posts</h3>
        <p><a href="create_post.php" class="btn btn-success">Create New Post</a></p>

        <?php
        if(isset($result) && mysqli_num_rows($result) > 0){
            echo "<table class=\"table table-bordered table-striped\">";
                echo "<thead>";
                    echo "<tr>";
                        echo "<th>#</th>";
                        echo "<th>Title</th>";
                        echo "<th>Category</th>";
                        echo "<th>Created At</th>";
                        echo "<th>Likes</th>";
                        echo "<th>Views</th>";
                        echo "<th>Action</th>";
                    echo "</tr>";
                echo "</thead>";
                echo "<tbody>";
                while($row = mysqli_fetch_array($result)){
                    echo "<tr>";
                        echo "<td>" . $row['id'] . "</td>";
                        echo "<td>" . htmlspecialchars($row['title']) . "</td>";
                        echo "<td>" . (empty($row['category_name']) ? 'Uncategorized' : htmlspecialchars($row['category_name'])) . "</td>";
                        echo "<td>" . $row['created_at'] . "</td>";
                        echo "<td>" . $row['likes_count'] . "</td>";
                        echo "<td>" . $row['view_count'] . "</td>";
                        echo "<td>";
                            echo "<a href=\"read_post.php?id=". $row['id'] ."\" title=\"View Post\" data-toggle=\"tooltip\"><span class=\"glyphicon glyphicon-eye-open\">View</span></a>";
                            echo "<a href=\"update_post.php?id=". $row['id'] ."\" title=\"Update Post\" data-toggle=\"tooltip\"><span class=\"glyphicon glyphicon-pencil\">Edit</span></a>";
                            echo "<a href=\"delete_post.php?id=". $row['id'] ."\" title=\"Delete Post\" data-toggle=\"tooltip\"><span class=\"glyphicon glyphicon-trash\">Delete</span></a>";
                        echo "</td>";
                    echo "</tr>";
                }
                echo "</tbody>";
            echo "</table>";
            // Free result set
            mysqli_free_result($result);
        } else{
            echo '<div class="alert alert-info"><em>No posts were found.</em></div>';
        }
        mysqli_close($link);
        ?>

    </div>
</body>
</html>
