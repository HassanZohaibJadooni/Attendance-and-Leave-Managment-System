<?php
require "config.php";

$user_name = trim($_POST['username'] ?? '');
$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';
$cpassword = $_POST['cpassword'] ?? '';
$department = $_POST['department'] ?? '';

try {
    if (!$user_name || !$email || !$password || !$cpassword) {
        echo "error: missing fields";
        exit;
    }

    // Check duplicate email
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = :email");
    $stmt->execute([':email' => $email]);

    if ($stmt->rowCount() > 0) {
        echo "exists";
    } elseif ($password !== $cpassword) {
        echo "mismatch";
    } elseif (strlen($password) < 8) {
        echo "short";
    } else {
        $hash = password_hash($password, PASSWORD_DEFAULT);

        $insert = $conn->prepare("
            INSERT INTO users (user_name, email, department, password)
            VALUES (:user_name, :email, :department, :password)
        ");

        $insert->execute([
            ':user_name' => $user_name,
            ':email' => $email,
            ':password' => $hash,
            ':department' => $department
        ]);

        echo "success";
    }

} catch (PDOException $e) {
    echo "error: " . $e->getMessage();
}
?>
