<?php
session_start();
require_once("include/models/db.php");
// If the session variable isn't set, kick them back to the login page
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
$currentUserId = $_SESSION['user_id'] ?? null;

$posts = [];
$isAdmin = false;
if ($currentUserId) {
    $permStmt = $pdo->prepare("SELECT Permission FROM User WHERE ID = ?");
    $permStmt->execute([$currentUserId]);
    $permRow = $permStmt->fetch(PDO::FETCH_ASSOC);
    $isAdmin = ($permRow && isset($permRow['Permission']) && $permRow['Permission'] === 'admin');

    if ($isAdmin) {
        $stmt = $pdo->prepare("SELECT * FROM Post ORDER BY ID DESC");
        $stmt->execute();
    } else {
        $stmt = $pdo->prepare("SELECT * FROM Post WHERE UserID = ? ORDER BY ID DESC");
        $stmt->execute([$currentUserId]);
    }

    $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

require_once "include/views/_header.php"; 
?>
    <div class="center">
        <div class="dashboard">
            <h1>Dashboard</h1>
            <h2>Hello, <?= htmlspecialchars($_SESSION['username']); ?>!</h2>
            <p>You have successfully logged in</p>
            
            <p><a href="logout.php">Log Out</a></p>
        </div>

        <div class="post-container<?php echo empty($posts) ? ' hidden' : ''; ?>">
            <h2><?= $isAdmin ? 'All posts' : 'My posts' ?></h2>

            <div class="posts-section">
            <?php if (!empty($posts)): ?>
                <?php foreach ($posts as $post): ?>

                    <div class="post-card" data-post-id="<?= htmlspecialchars($post['ID']) ?>">

                        <div class="post-header">
                            <strong><?= htmlspecialchars($post['Type']) ?></strong>
                            <span><?= htmlspecialchars($post['Adress']) ?></span>
                        </div>

                        <div class="post-main">
                            <!-- IMAGES -->
                            <div class="post-images">
                                <?php
                                $imgStmt = $pdo->prepare("SELECT FilePath FROM Image WHERE PostID = ?");
                                $imgStmt->execute([$post['ID']]);
                                $images = $imgStmt->fetchAll(PDO::FETCH_ASSOC);
                                ?>

                                <?php foreach ($images as $img): ?>
                                    <img src="<?= htmlspecialchars($img['FilePath']) ?>" alt="Post image">
                                <?php endforeach; ?>
                            </div>

                            <div class="post-meta">
                                <span>🌤 <?= htmlspecialchars($post['Weather']) ?></span>
                                <span>🌡 <?= htmlspecialchars($post['Temperature']) ?>°C</span>

                                <?php if ($post['Private']): ?>
                                    <span class="private-tag">Private</span>
                                <?php endif; ?>
                            </div>

                            <p class="post-desc">
                                <?= nl2br(htmlspecialchars($post['Description'])) ?>
                            </p>
                        </div>

                        <div class="post-action-buttons">
                            <button class="delete-btn" data-post-id="<?= htmlspecialchars($post['ID']) ?>">Delete</button>
                        </div>

                    </div>

                <?php endforeach; ?>
            <?php endif; ?>
            </div>
    </div>
    </div>

<script src="include/models/dashboard.js"></script>

<?php require_once "include/views/_footer.php"; ?>