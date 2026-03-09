<?php
require "config.php";

$user_id = $_SESSION['user_id'];

$stmt = $conn->prepare("UPDATE notifications 
    SET is_read = 1 
    WHERE user_id = :uid
");
$stmt->execute([':uid' => $user_id]);

echo json_encode(["success" => true]);
exit;

?>