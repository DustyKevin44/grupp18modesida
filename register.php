<?php 
session_start();

// Process registration form ONLY on POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    require_once("include/models/db.php");

    // Safely grab and sanitize input
    $username = trim($_POST['username'] ?? '');
    $email = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'] ?? '';

    // Validation
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

    if (strlen($password) < 8) {
        $_SESSION['reg_error'] = "Password must be at least 8 characters long.";
        header("Location: register.php");
        exit();
    }

    try {

        // Check if email already exists
        $checkEmail = $pdo->prepare("
            SELECT ID 
            FROM User 
            WHERE Mail = :email 
            LIMIT 1
        ");

        $checkEmail->execute([
            ':email' => $email
        ]);

        if ($checkEmail->fetch()) {
            $_SESSION['reg_error'] = "An account with that email already exists.";
            header("Location: register.php");
            exit();
        }

        // Check if username already exists
        $checkUsername = $pdo->prepare("
            SELECT ID
            FROM User
            WHERE Username = :username
            LIMIT 1
        ");

        $checkUsername->execute([
            ':username' => $username
        ]);

        if ($checkUsername->fetch()) {
            $_SESSION['reg_error'] = "Username is already taken.";
            header("Location: register.php");
            exit();
        }

        // Hash password securely
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        // Insert new user
        $insertStmt = $pdo->prepare("
            INSERT INTO User (
                Username,
                Mail,
                Password,
                Permission
            )
            VALUES (
                :username,
                :email,
                :password,
                'user'
            )
        ");

        $insertStmt->execute([
            ':username' => $username,
            ':email'    => $email,
            ':password' => $hashedPassword
        ]);

        $_SESSION['reg_success'] = "Registration successful! You can now log in.";
        header("Location: register.php");
        exit();

    } catch (PDOException $e) {

    die("Database Error: " . $e->getMessage());
}
}

// Load page header
require_once "include/views/_header.php";
?>

<main style="max-width: 400px; margin: 2rem auto; padding: 1rem; border: 1px solid #ccc; border-radius: 5px;">
    
    <h2>Register An Account</h2>

    <!-- Error Message -->
    <?php if (isset($_SESSION['reg_error'])): ?>
        <div style="color: red; margin-bottom: 1rem;">
            <?= htmlspecialchars($_SESSION['reg_error']); ?>
            <?php unset($_SESSION['reg_error']); ?>
        </div>
    <?php endif; ?>

    <!-- Success Message -->
    <?php if (isset($_SESSION['reg_success'])): ?>
        <div style="color: green; margin-bottom: 1rem;">
            <?= htmlspecialchars($_SESSION['reg_success']); ?>
            <?php unset($_SESSION['reg_success']); ?>
        </div>
    <?php endif; ?>

    <!-- Registration Form -->
    <form action="register.php" method="POST">

        <div style="margin-bottom: 1rem;">
            <label for="username" style="display: block; margin-bottom: 0.5rem;">
                Username:
            </label>

            <input 
                type="text" 
                id="username" 
                name="username" 
                required
                maxlength="50"
                style="width: 100%; padding: 0.5rem;"
            >
        </div>

        <div style="margin-bottom: 1rem;">
            <label for="email" style="display: block; margin-bottom: 0.5rem;">
                Email Address:
            </label>

            <input 
                type="email" 
                id="email" 
                name="email" 
                required
                maxlength="255"
                style="width: 100%; padding: 0.5rem;"
            >
        </div>

        <div style="margin-bottom: 1rem;">
            <label for="password" style="display: block; margin-bottom: 0.5rem;">
                Password:
            </label>

            <input 
                type="password" 
                id="password" 
                name="password" 
                required
                minlength="8"
                style="width: 100%; padding: 0.5rem;"
            >
        </div>

        <button 
            type="submit"
            style="
                padding: 0.5rem 1rem;
                background-color: #28a745;
                color: white;
                border: none;
                border-radius: 3px;
                cursor: pointer;
            "
        >
            Register
        </button>

    </form>

    <p style="margin-top: 1rem; font-size: 0.9rem;">
        Already have an account?
        <a href="login.php">Log in here</a>.
    </p>

</main>

<script src="script.js"></script>

<?php require_once "include/views/_footer.php"; ?>