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

    <header>
        <h1>Dashboard</h1>
    </header>

    <main style="max-width: 600px; margin: 2rem auto;">
        <h2>Hello, <?= htmlspecialchars($_SESSION['username']); ?>!</h2>
        <p>You have successfully logged in</p>
        
        <p><a href="logout.php" style="color: red;">Log Out</a></p>
    </main>
    <div class="posts-section">
    <h2>My Posts</h2>

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
<style>.posts-section {
    max-width: 800px;
    margin: 40px auto;
}

.post-card {
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 12px;
    padding: 15px;
    margin-bottom: 20px;
    box-shadow: 0 2px 6px rgba(0,0,0,0.05);
}

.post-header {
    display: flex;
    justify-content: space-between;
    font-weight: bold;
    margin-bottom: 10px;
}

.post-desc {
    margin-bottom: 10px;
}

.post-meta {
    display: flex;
    gap: 10px;
    font-size: 14px;
    color: #555;
    margin-bottom: 10px;
}

.private-tag {
    background: #ffdddd;
    color: #a00;
    padding: 2px 6px;
    border-radius: 6px;
    font-size: 12px;
}

.post-images img {
    width: 120px;
    height: 120px;
    object-fit: cover;
    border-radius: 8px;
    margin-right: 5px;
}</style>

<?php require_once "include/views/_footer.php"; ?>