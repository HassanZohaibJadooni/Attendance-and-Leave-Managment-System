<?php
require "config.php";

$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';

try {
    $stmt = $conn->prepare("SELECT * FROM users WHERE email = :email");
    $stmt->execute([':email' => $email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {

        // --- SESSION CREATE ---
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['user_name'] = $user['user_name'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['joining_date'] = $user['joining_date'];
        $_SESSION['password'] = $user['password'];
        $_SESSION['profile_pic'] = $user['profile_pic'] ?? 'default.png';

        // --- REDIRECT OR RESPONSE ---
        if ($user['role'] === 'admin') {
            echo "admin"; 
        } else {
            echo "user";  
        }

    } else {
        echo "invalid";  // Wrong email/password
    }

} catch(PDOException $e) {
    echo "error: " . $e->getMessage();
}
?>
