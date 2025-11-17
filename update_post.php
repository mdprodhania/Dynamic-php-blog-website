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

// Define variables and initialize with empty values
$title = $content = "";
$category_id = 0;
$title_err = $content_err = "";

// Get categories for the dropdown
$sql_categories = "SELECT id, name FROM categories ORDER BY name ASC";
$result_categories = mysqli_query($link, $sql_categories);

// Processing form data when form is submitted
if(isset($_POST["id"]) && !empty($_POST["id"])){
    // Get hidden input value
    $id = $_POST["id"];
    $user_id = $_SESSION["id"];

    // Validate title
    if(empty(trim($_POST["title"]))){
        $title_err = "Please enter a title for the post.";
    } else{
        $title = trim($_POST["title"]);
    }

    // Validate content
    if(empty(trim($_POST["content"]))){
        $content_err = "Please enter content for the post.";
    } else{
        $content = trim($_POST["content"]);
    }

    // Get category ID
    $category_id = isset($_POST['category_id']) ? intval($_POST['category_id']) : NULL;

    // Check input errors before updating the database
    if(empty($title_err) && empty($content_err)){
        // Prepare an update statement
        $sql = "UPDATE posts SET title = ?, content = ?, category_id = ? WHERE id = ? AND user_id = ?";

        if($stmt = mysqli_prepare($link, $sql)){
            // Bind variables to the prepared statement as parameters
            mysqli_stmt_bind_param($stmt, "ssiii", $param_title, $param_content, $param_category_id, $param_id, $param_user_id);

            // Set parameters
            $param_title = $title;
            $param_content = $content;
            $param_category_id = $category_id;
            $param_id = $id;
            $param_user_id = $user_id;

            // Attempt to execute the prepared statement
            if(mysqli_stmt_execute($stmt)){
                // Records updated successfully. Redirect to author dashboard
                header("location: user_dashboard.php");
                exit();
            } else{
                echo "Oops! Something went wrong. Please try again later.";
            }
        }

        // Close statement
        mysqli_stmt_close($stmt);
    }

    // Close connection
    mysqli_close($link);
} else{
    // Check existence of id parameter before processing further
    if(isset($_GET["id"]) && !empty(trim($_GET["id"]))){
        // Get URL parameter
        $id =  trim($_GET["id"]);
        $user_id = $_SESSION["id"];

        // Prepare a select statement
        $sql = "SELECT posts.*, categories.name AS category_name FROM posts LEFT JOIN categories ON posts.category_id = categories.id WHERE posts.id = ? AND posts.user_id = ?";
        if($stmt = mysqli_prepare($link, $sql)){
            // Bind variables to the prepared statement as parameters
            mysqli_stmt_bind_param($stmt, "ii", $param_id, $param_user_id);

            // Set parameters
            $param_id = $id;
            $param_user_id = $user_id;

            // Attempt to execute the prepared statement
            if(mysqli_stmt_execute($stmt)){
                $result = mysqli_stmt_get_result($stmt);

                if(mysqli_num_rows($result) == 1){
                    $row = mysqli_fetch_array($result, MYSQLI_ASSOC);

                    // Retrieve individual field value
                    $title = $row["title"];
                    $content = $row["content"];
                    $category_id = $row["category_id"];
                } else{
                    // URL doesn't contain valid id or user doesn't own the post. Redirect to error page
                    header("location: error.php");
                    exit();
                }

            } else{
                echo "Oops! Something went wrong. Please try again later.";
            }
        }

        // Close statement
        mysqli_stmt_close($stmt);

        // Close connection
        mysqli_close($link);
    }  else{
        // URL doesn't contain id parameter. Redirect to error page
        header("location: error.php");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Update Post</title>
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
                    <h2 class="mt-5">Update Post</h2>
                    <p>Please edit the input values and submit to update the post.</p>
                    <form action="<?php echo htmlspecialchars(basename($_SERVER['REQUEST_URI'])); ?>" method="post">
                        <div class="form-group">
                            <label>Title</label>
                            <input type="text" name="title" class="form-control <?php echo (!empty($title_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $title; ?>">
                            <span class="invalid-feedback"><?php echo $title_err; ?></span>
                        </div>
                        <div class="form-group">
                            <label>Content</label>
                            <textarea name="content" class="form-control <?php echo (!empty($content_err)) ? 'is-invalid' : ''; ?>" rows="5"><?php echo $content; ?></textarea>
                            <span class="invalid-feedback"><?php echo $content_err; ?></span>
                        </div>
                        <div class="form-group">
                            <label>Category</label>
                            <select name="category_id" class="form-control">
                                <option value="NULL">Select Category</option>
                                <?php
                                if ($result_categories && mysqli_num_rows($result_categories) > 0) {
                                    while ($cat = mysqli_fetch_array($result_categories)) {
                                        echo '<option value="' . $cat['id'] . '" ' . (($category_id == $cat['id']) ? 'selected' : '') . '>' . htmlspecialchars($cat['name']) . '</option>';
                                    }
                                    mysqli_free_result($result_categories);
                                }
                                ?>
                            </select>
                        </div>
                        <input type="hidden" name="id" value="<?php echo $id; ?>"/>
                        <input type="submit" class="btn btn-primary" value="Submit">
                        <a href="user_dashboard.php" class="btn btn-secondary ml-2">Cancel</a>
                    </form>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
