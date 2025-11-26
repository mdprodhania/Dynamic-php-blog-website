<?php
session_start();

if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || !isset($_SESSION["is_admin"]) || $_SESSION["is_admin"] !== true){
    header("location: login.php");
    exit;
}

require_once "config.php";

$sql = "SELECT comments.id, comments.comment_content, comments.created_at, comments.is_approved, comments.author_name, posts.title AS post_title, posts.id AS post_id FROM comments JOIN posts ON comments.post_id = posts.id ORDER BY comments.created_at DESC";
$result = mysqli_query($link, $sql);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Comments</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .wrapper{
            width: 90%;
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
            <h1>Comment Management</h1>
        </div>
        <p>
            <a href="admin_dashboard.php" class="btn btn-primary">Back to Dashboard</a>
            <a href="logout.php" class="btn btn-danger float-right">Sign Out</a>
        </p>

        <?php
        if(isset($_SESSION["comment_action_message"])){ 
            echo '<div class="alert alert-success">' . $_SESSION["comment_action_message"] . '</div>';
            unset($_SESSION["comment_action_message"]);
        }

        if($result) {
            if(mysqli_num_rows($result) > 0){
                echo "<table class=\"table table-bordered table-striped\">";
                    echo "<thead>";
                        echo "<tr>";
                            echo "<th>#</th>";
                            echo "<th>Post Title</th>";
                            echo "<th>Author</th>";
                            echo "<th>Comment</th>";
                            echo "<th>Created At</th>";
                            echo "<th>Approved</th>";
                            echo "<th>Action</th>";
                        echo "</tr>";
                    echo "</thead>";
                    echo "<tbody>";
                    while($row = mysqli_fetch_array($result)){
                        echo "<tr>";
                            echo "<td>" . $row['id'] . "</td>";
                            echo "<td><a href=\"read_post.php?id=". $row['post_id'] ."\" target=\"_blank\">" . htmlspecialchars($row['post_title']) . "</a></td>";
                            echo "<td>" . htmlspecialchars($row['author_name']) . "</td>";
                            echo "<td>" . htmlspecialchars(substr($row['comment_content'], 0, 50)) . (strlen($row['comment_content']) > 50 ? '...' : '') . "</td>";
                            echo "<td>" . $row['created_at'] . "</td>";
                            echo "<td>" . ($row['is_approved'] ? 'Yes' : 'No') . "</td>";
                            echo "<td>";
                                if($row['is_approved'] == FALSE) {
                                    echo "<a href=\"approve_comment.php?id=". $row['id'] ."\" title=\"Approve Comment\" data-toggle=\"tooltip\"><span class=\"glyphicon glyphicon-check\">Approve</span></a>";
                                }
                                echo "<a href=\"delete_comment.php?id=". $row['id'] ."\" title=\"Delete Comment\" data-toggle=\"tooltip\"><span class=\"glyphicon glyphicon-trash\">Delete</span></a>";
                            echo "</td>";
                        echo "</tr>";
                    }
                    echo "</tbody>";
                echo "</table>";
                mysqli_free_result($result);
            } else{
                echo '<div class="alert alert-info"><em>No comments were found.</em></div>';
            }
        } else{
            echo "Oops! Something went wrong. Please try again later.";
        }

        mysqli_close($link);
        ?>

    </div>
</body>
</html>
