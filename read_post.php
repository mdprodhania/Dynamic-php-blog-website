<?php
// Initialize the session
session_start();

// Check if the user is logged in, if not then redirect him to login page
// This read_post.php can be accessed by both logged in users and visitors, so no redirect here.

// Check existence of id parameter before processing further
if(isset($_GET["id"]) && !empty(trim($_GET["id"]))){
    // Include config file
    require_once "config.php";

    $post_id = trim($_GET["id"]);

    // Increment view count if not already viewed in this session
    if(!isset($_SESSION["viewed_posts"])){
        $_SESSION["viewed_posts"] = array();
    }

    if(!in_array($post_id, $_SESSION["viewed_posts"])){
        $sql_increment_view = "UPDATE posts SET view_count = view_count + 1 WHERE id = ?";
        if($stmt_view = mysqli_prepare($link, $sql_increment_view)){
            mysqli_stmt_bind_param($stmt_view, "i", $param_post_id_view);
            $param_post_id_view = $post_id;
            mysqli_stmt_execute($stmt_view);
            mysqli_stmt_close($stmt_view);
            $_SESSION["viewed_posts"][] = $post_id;
        }
    }

    // Prepare a select statement for the post
    $sql_post = "SELECT posts.id, posts.title, posts.content, posts.likes_count, posts.view_count, posts.created_at, posts.updated_at, users.username FROM posts JOIN users ON posts.user_id = users.id WHERE posts.id = ?";

    if($stmt_post = mysqli_prepare($link, $sql_post)){
        mysqli_stmt_bind_param($stmt_post, "i", $param_post_id);
        $param_post_id = $post_id;

        if(mysqli_stmt_execute($stmt_post)){
            $result_post = mysqli_stmt_get_result($stmt_post);

            if(mysqli_num_rows($result_post) == 1){
                $row_post = mysqli_fetch_array($result_post, MYSQLI_ASSOC);
                $title = $row_post["title"];
                $content = $row_post["content"];
                $created_at = $row_post["created_at"];
                $updated_at = $row_post["updated_at"];
                $author_username = $row_post["username"];
                $likes_count = $row_post["likes_count"];
                $view_count = $row_post["view_count"];

                $is_liked_by_user = false;
                if(isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true){
                    $user_id = $_SESSION["id"];
                    $sql_check_like = "SELECT id FROM likes WHERE post_id = ? AND user_id = ?";
                    if($stmt_check_like = mysqli_prepare($link, $sql_check_like)){
                        mysqli_stmt_bind_param($stmt_check_like, "ii", $post_id, $user_id);
                        mysqli_stmt_execute($stmt_check_like);
                        mysqli_stmt_store_result($stmt_check_like);
                        if(mysqli_stmt_num_rows($stmt_check_like) > 0){
                            $is_liked_by_user = true;
                        }
                        mysqli_stmt_close($stmt_check_like);
                    }
                }
            } else{
                header("location: error.php");
                exit();
            }
        } else{
            echo "Oops! Something went wrong with fetching the post. Please try again later.";
        }
        mysqli_stmt_close($stmt_post);
    }

    // Fetch approved comments for this post
    $sql_comments = "SELECT comments.comment_content, comments.created_at, comments.author_name FROM comments WHERE comments.post_id = ? AND comments.is_approved = TRUE ORDER BY comments.created_at ASC";
    $comments_result = null;
    if($stmt_comments = mysqli_prepare($link, $sql_comments)){
        mysqli_stmt_bind_param($stmt_comments, "i", $param_post_id_comments);
        $param_post_id_comments = $post_id;
        if(mysqli_stmt_execute($stmt_comments)){
            $comments_result = mysqli_stmt_get_result($stmt_comments);
        } else {
            echo "Oops! Something went wrong with fetching comments. Please try again later.";
        }
        mysqli_stmt_close($stmt_comments);
    }

    // Handle comment submission
    $comment_content = $author_name = "";
    $comment_content_err = $author_name_err = "";

    if($_SERVER["REQUEST_METHOD"] == "POST"){
        if(empty(trim($_POST["comment_content"]))){
            $comment_content_err = "Please enter your comment.";
        } else{
            $comment_content = trim($_POST["comment_content"]);
        }

        if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
            // Visitor is not logged in, require author name
            if(empty(trim($_POST["author_name"]))){
                $author_name_err = "Please enter your name.";
            } else{
                $author_name = trim($_POST["author_name"]);
            }
        } else {
            // User is logged in, use their username as author name
            $author_name = $_SESSION["username"];
        }

        if(empty($comment_content_err) && empty($author_name_err)){
            $sql_insert_comment = "INSERT INTO comments (post_id, user_id, author_name, comment_content) VALUES (?, ?, ?, ?)";
            if($stmt_insert = mysqli_prepare($link, $sql_insert_comment)){
                $param_user_id = (isset($_SESSION["id"]) && $_SESSION["loggedin"] === true) ? $_SESSION["id"] : NULL;
                mysqli_stmt_bind_param($stmt_insert, "isss", $post_id, $param_user_id, $author_name, $comment_content);

                if(mysqli_stmt_execute($stmt_insert)){
                    // Redirect to refresh page and clear form
                    header("location: read_post.php?id=" . $post_id);
                    exit();
                } else{
                    echo "Oops! Something went wrong with submitting your comment. Please try again later.";
                }
                mysqli_stmt_close($stmt_insert);
            }
        }
    }

    mysqli_close($link);
} else{
    header("location: error.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>View Post</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .wrapper{
            width: 80%;
            margin: 0 auto;
        }
        .comment-section {
            margin-top: 40px;
            border-top: 1px solid #eee;
            padding-top: 20px;
        }
        .comment {
            background-color: #f9f9f9;
            border: 1px solid #ddd;
            padding: 15px;
            margin-bottom: 15px;
            border-radius: 5px;
        }
        .comment-author {
            font-weight: bold;
            color: #333;
        }
        .comment-date {
            font-size: 0.8em;
            color: #777;
            margin-left: 10px;
        }
        .like-button {
            background-color: #007bff;
            color: white;
            padding: 8px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 1em;
            margin-right: 10px;
        }
        .like-button.liked {
            background-color: #28a745; /* Green for liked state */
        }
        .like-count, .view-count {
            font-size: 0.9em;
            color: #555;
            margin-right: 15px;
        }
        .comment-form {
            margin-top: 30px;
        }
        .form-group.error input, .form-group.error textarea {
            border-color: #dc3545;
        }
        .invalid-feedback {
            color: #dc3545;
            font-size: 0.8em;
            display: block;
            margin-top: 5px;
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-12">
                    <h1 class="mt-5 mb-3">View Post</h1>
                    <div class="form-group">
                        <label>Title</label>
                        <p><b><?php echo $title; ?></b></p>
                    </div>
                    <div class="form-group">
                        <label>Content</label>
                        <p><?php echo nl2br($content); ?></p>
                    </div>
                    <div class="form-group">
                        <label>Author</label>
                        <p><b><?php echo $author_username; ?></b></p>
                    </div>
                    <div class="form-group">
                        <label>Created At</label>
                        <p><b><?php echo $created_at; ?></b></p>
                    </div>
                    <div class="form-group">
                        <label>Last Updated At</label>
                        <p><b><?php echo $updated_at; ?></b></p>
                    </div>

                    <div class="post-actions">
                        <?php if(isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true): ?>
                            <button id="likeBtn" class="like-button <?php echo $is_liked_by_user ? 'liked' : ''; ?>" data-post-id="<?php echo $post_id; ?>">
                                <?php echo $is_liked_by_user ? 'Liked' : 'Like'; ?>
                            </button>
                        <?php else: // Not logged in, can't like, but still show count ?>
                             <button class="like-button" disabled>Login to Like</button>
                        <?php endif; ?>
                        <span class="like-count">Likes: <span id="likesCountDisplay"><?php echo $likes_count; ?></span></span>
                        <span class="view-count">Views: <?php echo $view_count; ?></span>
                    </div>

                    <p>
                        <?php if(isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true && $_SESSION["is_admin"] === true): ?>
                            <a href="admin_dashboard.php" class="btn btn-primary">Back to Admin Dashboard</a>
                        <?php elseif(isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true && $_SESSION["is_admin"] === false): ?>
                            <a href="user_dashboard.php" class="btn btn-primary">Back to Your Posts</a>
                        <?php else: // Visitor ?>
                            <a href="index.php" class="btn btn-secondary">Back to Home</a>
                        <?php endif; ?>
                    </p>

                    <div class="comment-section">
                        <h3>Comments</h3>
                        <?php
                        if($comments_result && mysqli_num_rows($comments_result) > 0){
                            while($comment = mysqli_fetch_array($comments_result, MYSQLI_ASSOC)){
                                echo '<div class="comment">';
                                    echo '<span class="comment-author">' . htmlspecialchars($comment['author_name']) . '</span>';
                                    echo '<span class="comment-date">' . htmlspecialchars($comment['created_at']) . '</span>';
                                    echo '<p>' . nl2br(htmlspecialchars($comment['comment_content'])) . '</p>';
                                echo '</div>';
                            }
                        } else {
                            echo '<p>No comments yet. Be the first to comment!</p>';
                        }
                        ?>

                        <div class="comment-form">
                            <h4>Leave a Comment</h4>
                            <form action="<?php echo htmlspecialchars($_SERVER["REQUEST_URI"]); ?>" method="post">
                                <?php if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true): ?>
                                    <div class="form-group <?php echo (!empty($author_name_err)) ? 'error' : ''; ?>">
                                        <label>Your Name</label>
                                        <input type="text" name="author_name" class="form-control" value="<?php echo $author_name; ?>">
                                        <span class="invalid-feedback"><?php echo $author_name_err; ?></span>
                                    </div>
                                <?php endif; ?>
                                <div class="form-group <?php echo (!empty($comment_content_err)) ? 'error' : ''; ?>">
                                    <label>Comment</label>
                                    <textarea name="comment_content" class="form-control" rows="5"><?php echo $comment_content; ?></textarea>
                                    <span class="invalid-feedback"><?php echo $comment_content_err; ?></span>
                                </div>
                                <input type="submit" class="btn btn-primary" value="Submit Comment">
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const likeBtn = document.getElementById('likeBtn');
        const likesCountDisplay = document.getElementById('likesCountDisplay');

        if (likeBtn) {
            likeBtn.addEventListener('click', function() {
                const postId = this.dataset.postId;
                
                fetch('toggle_like.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'post_id=' + postId
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        likesCountDisplay.textContent = data.new_likes_count;
                        if (data.is_liked) {
                            likeBtn.classList.add('liked');
                            likeBtn.textContent = 'Liked';
                        } else {
                            likeBtn.classList.remove('liked');
                            likeBtn.textContent = 'Like';
                        }
                        console.log(data.message);
                    } else {
                        alert(data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred while processing your like.');
                });
            });
        }
    });
    </script>
</body>
</html>
