<?php
session_start();

// If the session variable isn't set, kick them back to the login page
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

require_once "include/views/_header.php"; 
?>

<body>
    <header>
        <h1>Dashboard</h1>
    </header>

    <main style="max-width: 600px; margin: 2rem auto;">
        <h2>Hello, <?= htmlspecialchars($_SESSION['user_email']); ?>!</h2>
        <p>You have successfully logged in. Your role level is: <strong><?= htmlspecialchars($_SESSION['user_permission']); ?></strong></p>
        
        <p><a href="logout.php" style="color: red;">Log Out</a></p>
    </main>
</body>
</html>

<?php require_once "include/views/_footer.php"; ?>