<?php
require "config.php";
$user_id = $_SESSION['user_id'];

$stmt_count = $conn->prepare("SELECT COUNT(*) 
    FROM notifications 
    WHERE user_id = :uid AND is_read = 0
");
$stmt_count->execute([':uid' => $user_id]);
$unread = $stmt_count->fetchColumn();

$stmt_data = $conn->prepare("SELECT id, message, created_at 
    FROM notifications 
    WHERE user_id = :uid 
    ORDER BY created_at DESC
");
$stmt_data->execute([':uid' => $user_id]);
$list = $stmt_data->fetchAll(PDO::FETCH_ASSOC);

echo json_encode([
    "unread" => $unread,
    "list" => $list
]);
exit;

?>