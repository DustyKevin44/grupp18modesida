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
            
            <p><a href="logout.php" style="color: red;">Log Out</a></p>
        </div>

        <div class="post-container">
            <h2><?= $isAdmin ? 'All posts' : 'My posts' ?></h2>

            <div class="posts-section">
            <?php if (!empty($posts)): ?>
                <?php foreach ($posts as $post): ?>

                    <div class="post-card" data-post-id="<?= htmlspecialchars($post['ID']) ?>">

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

                        <div style="margin-top:8px; display:flex; justify-content:flex-end; gap:8px;">
                            <button class="delete-btn" data-post-id="<?= htmlspecialchars($post['ID']) ?>">Delete</button>
                        </div>

                    </div>

                <?php endforeach; ?>
            <?php else: ?>
                <p>No posts yet.</p>
            <?php endif; ?>
            </div>
    </div>
    </div>

<script>
document.addEventListener('click', async function (e) {
    if (!e.target.matches('.delete-btn')) return;
    const btn = e.target;
    const postId = btn.getAttribute('data-post-id');
    if (!postId) return;
    if (!confirm('Are you sure you want to delete this post? This cannot be undone.')) return;

    btn.disabled = true;
    try {
        const form = new FormData();
        form.append('post_id', postId);

        const res = await fetch('delete_post.php', {
            method: 'POST',
            body: form,
        });
        const data = await res.json();
        if (data && data.success) {
            // remove card from DOM
            const card = btn.closest('.post-card');
            if (card) card.remove();
        } else {
            alert('Delete failed: ' + (data.message || 'unknown error'));
            btn.disabled = false;
        }
    } catch (err) {
        console.error(err);
        alert('Network error while deleting post');
        btn.disabled = false;
    }
});
</script>

<?php require_once "include/views/_footer.php"; ?>