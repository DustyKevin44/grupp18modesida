<?php
session_start();
header('Content-Type: application/json');
require_once "include/models/db.php";

$currentUserId = $_SESSION['user_id'] ?? null;
if (!$currentUserId) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

$data = $_POST ?? $_GET ?? [];
$postId = isset($data['post_id']) ? (int)$data['post_id'] : null;
if (!$postId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing post_id']);
    exit;
}

try {
    // Fetch post owner
    $stmt = $pdo->prepare("SELECT UserID FROM Post WHERE ID = ?");
    $stmt->execute([$postId]);
    $post = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$post) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Post not found']);
        exit;
    }

    // Check permissions: owner or admin
    $permStmt = $pdo->prepare("SELECT Permission FROM User WHERE ID = ?");
    $permStmt->execute([$currentUserId]);
    $permRow = $permStmt->fetch(PDO::FETCH_ASSOC);
    $isAdmin = ($permRow && isset($permRow['Permission']) && $permRow['Permission'] === 'admin');

    if (!$isAdmin && $post['UserID'] != $currentUserId) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Permission denied']);
        exit;
    }

    // Delete image files associated with post
    $imgStmt = $pdo->prepare("SELECT FilePath FROM Image WHERE PostID = ?");
    $imgStmt->execute([$postId]);
    $images = $imgStmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($images as $img) {
        $fp = $img['FilePath'];
        if ($fp && file_exists($fp)) {
            @unlink($fp);
        }
    }

    // Delete post (Images rows are cascade-deleted by DB)
    $del = $pdo->prepare("DELETE FROM Post WHERE ID = ?");
    $del->execute([$postId]);

    echo json_encode(['success' => true]);
    exit;

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    exit;
}

?>
