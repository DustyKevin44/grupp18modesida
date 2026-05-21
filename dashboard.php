<?php
session_start();
require_once("include/models/db.php");
// If the session variable isn't set, kick them back to the login page
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
$currentUserId = $_SESSION['user_id'] ?? null;

if ($currentUserId) {

    $stmt = $pdo->prepare("
        SELECT * FROM Post
        WHERE UserID = ?
        ORDER BY ID DESC
    ");

    $stmt->execute([$currentUserId]);
    $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

require_once "include/views/_header.php"; 
?>
    <div class="center">
        <div class="dashboard">
            <h1>Dashboard</h1>
            <h2>Hello, <?= htmlspecialchars($_SESSION['username']); ?>!</h2>
            <p>You have successfully logged in</p>
            
            <p><a href="logout.php" style="color: red;">Log Out</a></p>
        </div>

        <div class="post-container">
            <h2>My posts</h2>

            <div class="posts-section">
            <?php if (!empty($posts)): ?>
                <?php foreach ($posts as $post): ?>

                    <div class="post-card">

                        <div class="post-header">
                            <strong><?= htmlspecialchars($post['Type']) ?></strong>
                            <span><?= htmlspecialchars($post['Adress']) ?></span>
                        </div>

                        <p class="post-desc">
                            <?= nl2br(htmlspecialchars($post['Description'])) ?>
                        </p>

                        <div class="post-meta">
                            <span>🌤 <?= htmlspecialchars($post['Weather']) ?></span>
                            <span>🌡 <?= htmlspecialchars($post['Temperature']) ?>°C</span>

                            <?php if ($post['Private']): ?>
                                <span class="private-tag">Private</span>
                            <?php endif; ?>
                        </div>

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

                    </div>

                <?php endforeach; ?>
            <?php else: ?>
                <p>No posts yet.</p>
            <?php endif; ?>
            </div>
    </div>
    </div>

<?php require_once "include/views/_footer.php"; ?>