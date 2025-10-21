<?php
require_once '../config.php';
session_start();

// 🛡️ Restrict access to admin only
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(["status" => "error", "message" => "Unauthorized access."]);
    exit;
}

$userId = $_POST['user_id'] ?? null;

if (!$userId) {
    echo json_encode(["status" => "error", "message" => "Invalid user ID."]);
    exit;
}

try {
    // 🟤 Remove group action
    if (isset($_POST['remove_group'])) {
        $stmt = $pdo->prepare("UPDATE users SET staff_group = NULL WHERE id = ?");
        $stmt->execute([$userId]);
        echo json_encode(["status" => "success", "message" => "Group removed successfully!"]);
        exit;
    }

    // 🟢 Assign group action
    if (isset($_POST['group'])) {
        $group = $_POST['group'];

        // 🔍 Check current group first
        $check = $pdo->prepare("SELECT staff_group FROM users WHERE id = ?");
        $check->execute([$userId]);
        $current = $check->fetchColumn();

        if ($current === $group) {
            echo json_encode(["status" => "info", "message" => "No changes made — user is already in this group."]);
            exit;
        }

        $stmt = $pdo->prepare("UPDATE users SET staff_group = ? WHERE id = ?");
        $stmt->execute([$group, $userId]);
        echo json_encode(["status" => "success", "message" => "User group updated successfully!"]);
        exit;
    }

    echo json_encode(["status" => "error", "message" => "No group action provided."]);
} catch (Exception $e) {
    echo json_encode(["status" => "error", "message" => "Database error: " . $e->getMessage()]);
}
?>
