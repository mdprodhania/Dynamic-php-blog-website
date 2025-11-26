<?php

require_once "config.php";

$sql_categories = "SELECT id, name FROM categories ORDER BY name ASC";
$result_categories = mysqli_query($link, $sql_categories);

$filter_category_id = isset($_GET['category_id']) ? intval($_GET['category_id']) : 0;
$search_keyword = isset($_GET['search']) ? trim($_GET['search']) : '';

$where_clauses = [];
$param_types = '';
$param_values = [];

if ($filter_category_id > 0) {
    $where_clauses[] = "posts.category_id = ?";
    $param_types .= 'i';
    $param_values[] = $filter_category_id;
}

if (!empty($search_keyword)) {
    $where_clauses[] = "(posts.title LIKE ? OR posts.content LIKE ?)";
    $param_types .= 'ss';
    $param_values[] = '%' . $search_keyword . '%';
    $param_values[] = '%' . $search_keyword . '%';
}

$sql_posts = "SELECT posts.id, posts.title, posts.content, posts.created_at, posts.likes_count, posts.view_count, categories.name AS category_name, users.username AS author_name FROM posts LEFT JOIN categories ON posts.category_id = categories.id JOIN users ON posts.user_id = users.id";

if (!empty($where_clauses)) {
    $sql_posts .= " WHERE " . implode(" AND ", $where_clauses);
}
$sql_posts .= " ORDER BY posts.created_at DESC";

$result_posts = null;
if ($stmt_posts = mysqli_prepare($link, $sql_posts)) {
    if (!empty($param_types)) {
        mysqli_stmt_bind_param($stmt_posts, $param_types, ...$param_values);
    }
    mysqli_stmt_execute($stmt_posts);
    $result_posts = mysqli_stmt_get_result($stmt_posts);
}

mysqli_close($link);

function truncate($text, $chars = 100) {
    if (strlen($text) <= $chars) {
        return $text;
    }
    $text = substr($text, 0, $chars);
    $text = substr($text, 0, strrpos($text, ' '));
    return $text . '...';
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dynamic Blog - Home</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .container {
            width: 80%;
            margin: 20px auto;
            padding: 20px;
            background-color: #fff;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            border-radius: 8px;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
        }
        .post-card {
            border: 1px solid #ddd;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 8px;
            background-color: #f9f9f9;
        }
        .post-card h3 {
            margin-top: 0;
            color: #007bff;
        }
        .post-meta {
            font-size: 0.9em;
            color: #777;
            margin-bottom: 10px;
        }
        .post-meta span {
            margin-right: 15px;
        }
        .post-content p {
            line-height: 1.6;
        }
        .read-more {
            display: inline-block;
            margin-top: 10px;
            color: #007bff;
            text-decoration: none;
        }
        .read-more:hover {
            text-decoration: underline;
        }
        .filter-search-bar {
            margin-bottom: 20px;
            padding-bottom: 20px;
            border-bottom: 1px solid #eee;
            display: flex;
            gap: 15px;
            align-items: center;
        }
        .filter-search-bar select, .filter-search-bar input[type="text"] {
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .filter-search-bar button {
            padding: 8px 15px;
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        .filter-search-bar button:hover {
            background-color: #0056b3;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Welcome to the Dynamic Blog!</h1>
            <p><a href="login.php">Admin/Author Login</a> | <a href="register.php">Register as Author</a></p>
        </div>

        <div class="filter-search-bar">
            <form action="index.php" method="get" style="display:flex; gap:15px;">
                <select name="category_id" onchange="this.form.submit()">
                    <option value="0">All Categories</option>
                    <?php
                    if ($result_categories && mysqli_num_rows($result_categories) > 0) {
                        while ($category = mysqli_fetch_array($result_categories)) {
                            $selected = ($filter_category_id == $category['id']) ? 'selected' : '';
                            echo '<option value="' . $category['id'] . '" ' . $selected . '>' . htmlspecialchars($category['name']) . '</option>';
                        }
                        mysqli_free_result($result_categories);
                    }
                    ?>
                </select>
                <input type="text" name="search" placeholder="Search posts..." value="<?php echo htmlspecialchars($search_keyword); ?>">
                <button type="submit">Search</button>
            </form>
        </div>

        <div class="posts-list">
            <?php
            if ($result_posts && mysqli_num_rows($result_posts) > 0) {
                while ($post = mysqli_fetch_array($result_posts)) {
                    echo '<div class="post-card">';
                        echo '<h3><a href="read_post.php?id=' . $post['id'] . '">' . htmlspecialchars($post['title']) . '</a></h3>';
                        echo '<p class="post-meta">';
                            echo '<span>By: ' . htmlspecialchars($post['author_name']) . '</span>';
                            echo '<span>Category: ' . (empty($post['category_name']) ? 'Uncategorized' : htmlspecialchars($post['category_name'])) . '</span>';
                            echo '<span>Date: ' . htmlspecialchars($post['created_at']) . '</span>';
                            echo '<span>Likes: ' . htmlspecialchars($post['likes_count']) . '</span>';
                            echo '<span>Views: ' . htmlspecialchars($post['view_count']) . '</span>';
                        echo '</p>';
                        echo '<div class="post-content"><p>' . nl2br(htmlspecialchars(truncate($post['content']))) . '</p></div>';
                        echo '<a href="read_post.php?id=' . $post['id'] . '" class="read-more">Read More</a>';
                    echo '</div>';
                }
                mysqli_free_result($result_posts);
            } else {
                echo '<div class="alert alert-info"><em>No posts found.</em></div>';
            }
            ?>
        </div>
    </div>
</body>
</html>
