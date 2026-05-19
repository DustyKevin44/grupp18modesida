<?php 
session_start();

// 1. Process registration data ONLY if the form was POSTed
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    require_once("include/models/db.php");

    // Grab and sanitize inputs
    $username = trim($_POST['username']); 
    $email = filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'];

    // Basic validation
    if (empty($username) || empty($email) || empty($password)) {
        $_SESSION['reg_error'] = "All fields are required.";
        header("Location: register.php");
        exit();
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['reg_error'] = "Invalid email format.";
        header("Location: register.php");
        exit();
    }

    try {
        // Check if the email already exists in the database
        $checkStmt = $db->prepare("SELECT ID FROM User WHERE Mail = :email LIMIT 1");
        $checkStmt->execute([':email' => $email]);
        
        if ($checkStmt->fetch()) {
            $_SESSION['reg_error'] = "An account with that email already exists.";
            header("Location: register.php");
            exit();
        }

        // Hash the password safely
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        // Insert user into the database
        $insertStmt = $db->prepare("INSERT INTO User (Mail, Password, Permission) VALUES (:email, :password, :username)");
        $insertStmt->execute([
            ':email'    => $email,
            ':password' => $hashedPassword,
            ':username' => $username 
        ]);

        $_SESSION['reg_success'] = "Registration successful! You can now log in.";
        header("Location: register.php");
        exit();

    } catch (PDOException $e) {
        $_SESSION['reg_error'] = "An error occurred during registration. Please try again.";
        header("Location: register.php");
        exit();
    }
}

// 2. If it's a GET request, skip processing and safely include headers and view layout
require_once "include/views/_header.php"; 
?>

<body>
    <header>
        <h1>Create an Account</h1>
    </header>

    <main style="max-width: 400px; margin: 2rem auto; padding: 1rem; border: 1px solid #ccc; border-radius: 5px;">
        <h2>Register</h2>
        
        <!-- Display success or error messages -->
        <?php if (isset($_SESSION['reg_error'])): ?>
            <div style="color: red; margin-bottom: 1rem;">
                <?= htmlspecialchars($_SESSION['reg_error']); ?>
                <?php unset($_SESSION['reg_error']); ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['reg_success'])): ?>
            <div style="color: green; margin-bottom: 1rem;">
                <?= htmlspecialchars($_SESSION['reg_success']); ?>
                <?php unset($_SESSION['reg_success']); ?>
            </div>
        <?php endif; ?>

        <!-- Form action points back to this file now -->
        <form action="register.php" method="POST">
            <div style="margin-bottom: 1rem;">
                <label for="username" style="display: block; margin-bottom: 0.5rem;">Username:</label>
                <input type="text" id="username" name="username" required style="width: 100%; padding: 0.5rem;">
            </div>

            <div style="margin-bottom: 1rem;">
                <label for="email" style="display: block; margin-bottom: 0.5rem;">Email Address:</label>
                <input type="email" id="email" name="email" required style="width: 100%; padding: 0.5rem;">
            </div>

            <div style="margin-bottom: 1rem;">
                <label for="password" style="display: block; margin-bottom: 0.5rem;">Password:</label>
                <input type="password" id="password" name="password" required style="width: 100%; padding: 0.5rem;">
            </div>

            <button type="submit" style="padding: 0.5rem 1rem; background-color: #28a745; color: white; border: none; border-radius: 3px; cursor: pointer;">
                Register
            </button>
        </form>
        
        <p style="margin-top: 1rem; font-size: 0.9rem;">
            Already have an account? <a href="login.php">Log in here</a>.
        </p>
    </main>

    <script src="script.js"></script>
</body>
</html>

<?php require_once "include/views/_footer.php"; ?>