<?php
session_start();
require_once("include/models/db.php");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Your description here">
    <meta name="author" content="Your name">
    <title>Modesidan</title>

    <!-- Favicon -->
    <link rel="icon" href="favicon.ico" type="image/x-icon">

    <!-- CSS -->
    <link rel="stylesheet" href="css/styles.css">
</head>

<body>
<header>
    <nav class="navbar">
        <div class="navbar-title">My Website</div>

        <div class="nav-links">
            <a href="index.php">Home</a>
            <a href="post.php">Create a post</a>
            <a href="search.php">Search</a>

            <?php if (isset($_SESSION['user_id'])): ?>

                <a href="dashboard.php">Dashboard</a>

                <span class="username">
                   <a>Welcome, <?= htmlspecialchars($_SESSION['username']) ?></a> 
                </span>

                <a href="logout.php">Logout</a>

            <?php else: ?>

                <a href="login.php">Login</a>

            <?php endif; ?>
        </div>
    </nav>
</header>