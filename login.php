<?php 
session_start();

// 1. Process the login form only if it's a POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    require_once("include/models/db.php");

    // Sanitize and grab inputs
    $email = filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'];

    if (empty($email) || empty($password)) {
        $_SESSION['login_error'] = "Please fill in all fields.";
        header("Location: login.php");
        exit();
    }

    try {
        // Fetch the user details by email
$stmt = $pdo->prepare("SELECT ID, Username, Password, Locked, Permission FROM User WHERE Mail = :email LIMIT 1");        $stmt->execute([':email' => $email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            // Check if account is locked
            if ((int)$user['Locked'] === 1) {
                $_SESSION['login_error'] = "This account has been locked. Please contact support.";
                header("Location: login.php");
                exit();
            }

            // Verify password
            if (password_verify($password, $user['Password'])) {
                // Regenerate session ID to prevent session fixation attacks
                session_regenerate_id(true);

                // Store user data in session variables
                $_SESSION['user_id'] = $user['ID'];
                $_SESSION['username'] = $user['Username'];
                $_SESSION['user_permission'] = $user['Permission'];

                // Redirect to a secure dashboard area
                header("Location: dashboard.php");
                exit();
            }
        }

        // Generic error message for security
        $_SESSION['login_error'] = "Invalid email or password.";
        header("Location: login.php");
        exit();

    } catch (PDOException $e) {
        $_SESSION['login_error'] = "An error occurred. Please try again later.";
        header("Location: login.php");
        exit();
    }
}

// 2. If it's a GET request, skip processing and render the view below
require_once "include/views/_header.php"; 
?>

<div class="center">
    <div class="post-container">
        <h2>Login</h2>
        
        <!-- Display error messages if they exist in the session -->
        <?php if (isset($_SESSION['login_error'])): ?>
            <div class="message-error">
                <?= htmlspecialchars($_SESSION['login_error']); ?>
                <?php unset($_SESSION['login_error']); ?>
            </div>
        <?php endif; ?>

        <!-- Note: action points back to this same file (login.php) -->
        <form action="login.php" method="POST">
            <div>
                <label for="email">Email Address:</label>
                <input type="email" id="email" name="email" required>
            </div>

            <div>
                <label for="password">Password:</label>
                <input type="password" id="password" name="password" required>
            </div>

            <button type="submit" class="btn btn-submit">Sign In</button>
        </form>
        
        <p class="auth-section-text">
            No account? <a href="register.php" class="auth-link">Register here</a>
        </p>
    </div>
</div>

<?php require_once "include/views/_footer.php"; ?>