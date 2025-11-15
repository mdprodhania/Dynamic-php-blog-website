<?php
// Initialize the session
session_start();

// Check if the user is logged in, if not, return error
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    echo json_encode(['success' => false, 'message' => 'Please log in to like posts.']);
    exit;
}

// Include config file
require_once "config.php";

// Get post ID and user ID
$post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
$user_id = $_SESSION['id'];

$response = ['success' => false, 'message' => 'An unknown error occurred.', 'new_likes_count' => 0, 'is_liked' => false];

if($post_id > 0){
    // Check if user has already liked this post
    $sql_check = "SELECT id FROM likes WHERE post_id = ? AND user_id = ?";
    if($stmt_check = mysqli_prepare($link, $sql_check)){
        mysqli_stmt_bind_param($stmt_check, "ii", $post_id, $user_id);
        mysqli_stmt_execute($stmt_check);
        mysqli_stmt_store_result($stmt_check);

        if(mysqli_stmt_num_rows($stmt_check) > 0){
            // User has liked, so unlike (delete like)
            $sql_action = "DELETE FROM likes WHERE post_id = ? AND user_id = ?";
            $is_liked = false;
        } else {
            // User has not liked, so like (insert like)
            $sql_action = "INSERT INTO likes (post_id, user_id) VALUES (?, ?)";
            $is_liked = true;
        }
        mysqli_stmt_close($stmt_check);

        if($stmt_action = mysqli_prepare($link, $sql_action)){
            mysqli_stmt_bind_param($stmt_action, "ii", $post_id, $user_id);
            if(mysqli_stmt_execute($stmt_action)){
                // Update likes_count in posts table
                $sql_update_post = "UPDATE posts SET likes_count = (SELECT COUNT(*) FROM likes WHERE post_id = ?) WHERE id = ?";
                if($stmt_update = mysqli_prepare($link, $sql_update_post)){
                    mysqli_stmt_bind_param($stmt_update, "ii", $post_id, $post_id);
                    mysqli_stmt_execute($stmt_update);
                    mysqli_stmt_close($stmt_update);

                    // Fetch new likes count
                    $sql_fetch_count = "SELECT likes_count FROM posts WHERE id = ?";
                    if($stmt_fetch_count = mysqli_prepare($link, $sql_fetch_count)){
                        mysqli_stmt_bind_param($stmt_fetch_count, "i", $post_id);
                        mysqli_stmt_execute($stmt_fetch_count);
                        mysqli_stmt_bind_result($stmt_fetch_count, $new_likes_count);
                        mysqli_stmt_fetch($stmt_fetch_count);
                        mysqli_stmt_close($stmt_fetch_count);

                        $response['success'] = true;
                        $response['message'] = $is_liked ? 'Post liked successfully.' : 'Post unliked successfully.';
                        $response['new_likes_count'] = $new_likes_count;
                        $response['is_liked'] = $is_liked;
                    }
                }
            } else {
                $response['message'] = 'Could not process like action.';
            }
            mysqli_stmt_close($stmt_action);
        }
    }
}

mysqli_close($link);

header('Content-Type: application/json');
echo json_encode($response);
?>
