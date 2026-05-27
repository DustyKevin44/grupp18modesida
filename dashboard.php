<?php
session_start();
require_once("include/models/db.php");

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

// Handle lock/unlock user action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'toggle_lock') {
    if (!$isAdmin) {
        http_response_code(403);
        echo json_encode(['error' => 'Unauthorized']);
        exit;
    }
    
    $userId = (int)$_POST['user_id'] ?? null;
    if ($userId) {
        try {
            $stmt = $pdo->prepare("SELECT Locked FROM User WHERE ID = ?");
            $stmt->execute([$userId]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user) {
                $newLockStatus = (int)$user['Locked'] === 1 ? 0 : 1;
                $updateStmt = $pdo->prepare("UPDATE User SET Locked = ? WHERE ID = ?");
                $updateStmt->execute([$newLockStatus, $userId]);
                
                echo json_encode(['success' => true, 'locked' => $newLockStatus]);
                exit;
            }
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
            exit;
        }
    }
}

// Fetch all users for admin panel
$allUsers = [];
if ($isAdmin) {
    $usersStmt = $pdo->prepare("SELECT ID, Username, Mail, Permission, Locked FROM User ORDER BY Username");
    $usersStmt->execute();
    $allUsers = $usersStmt->fetchAll(PDO::FETCH_ASSOC);
}

require_once "include/views/_header.php"; 
?>
    <div class="center">
        <div class="dashboard">
            <h1>Dashboard</h1>
            <h2>Hello, <?= htmlspecialchars($_SESSION['username']); ?>!</h2>
            <p>You have successfully logged in</p>
            
            <p><a href="logout.php" class="btn btn-outline">Log Out</a></p>
        </div>

        <?php if ($isAdmin): ?>
        <div class="post-container">
            <h2>Account Management</h2>
            <div class="admin-users-section">
                <?php if (!empty($allUsers)): ?>
                    <table class="admin-users-table">
                        <thead>
                            <tr>
                                <th>Username</th>
                                <th>Email</th>
                                <th>Permission</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($allUsers as $user): ?>
                                <tr class="user-row" data-user-id="<?= $user['ID'] ?>">
                                    <td><?= htmlspecialchars($user['Username']) ?></td>
                                    <td><?= htmlspecialchars($user['Mail']) ?></td>
                                    <td><?= htmlspecialchars($user['Permission']) ?></td>
                                    <td class="status-cell">
                                        <span class="status-badge <?= $user['Locked'] ? 'locked' : 'active' ?>">
                                            <?= $user['Locked'] ? 'Locked' : 'Active' ?>
                                        </span>
                                    </td>
                                    <td>
                                        <button class="btn btn-lock-toggle" data-user-id="<?= $user['ID'] ?>" data-locked="<?= $user['Locked'] ?>">
                                            <?= $user['Locked'] ? 'Unlock' : 'Lock' ?>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

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
                            <button class="btn btn-delete" data-post-id="<?= htmlspecialchars($post['ID']) ?>">Delete</button>
                        </div>

                    </div>

                <?php endforeach; ?>
            <?php endif; ?>
            </div>
    </div>
    </div>

<script src="include/models/dashboard.js"></script>
<?php if ($isAdmin): ?>
<script src="include/models/dashboard-admin.js"></script>
<?php endif; ?>

<?php require_once "include/views/_footer.php"; ?>