<?php
session_start();
require_once("include/models/db.php");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modesidan</title>

    <link rel="icon" href="favicon.ico" type="image/x-icon">

    <link rel="stylesheet" href="css/styles.css">
</head>

<body>
<header>
    <nav class="navbar">
        <div class="navbar-title">Clothing Forecast</div>

        <div class="nav-links">
            <div class="main-nav">
                <a href="index.php" class="btn btn-nav">Home</a>
                <a href="search.php" class="btn btn-nav">Search</a>
                <a href="clothingForecast.php" class="btn btn-nav">Forecast</a>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <a href="post.php" class="btn btn-nav">Create a post</a>
                <?php endif; ?>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <a href="dashboard.php" class="btn btn-nav">Dashboard</a>
                <?php endif; ?>
            </div>

            <div class="auth-nav">
                <?php if (isset($_SESSION['user_id'])): ?>
                    <span class="username">
                        Welcome, <?= htmlspecialchars($_SESSION['username']) ?>
                    </span>
                    <a href="logout.php" class="btn btn-nav">Logout</a>
                <?php else: ?>
                    <a href="login.php" class="btn btn-nav">Login</a>
                <?php endif; ?>
            </div>
        </div>
    </nav>
</header>