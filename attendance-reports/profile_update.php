<?php
require "config.php";

$user_id = $_POST['id'];

// Fetch old user data
$stmt = $conn->prepare("SELECT * FROM users WHERE id = :id");
$stmt->execute([':id' => $user_id]);
$old = $stmt->fetch();

if (!$old) {
    echo "error: user not found";
    exit;
}

// Form data
$name     = $_POST['fullName'];
$email    = $_POST['email'];
$password = $_POST['password'];

// check if email exist 
$check = $conn->prepare("SELECT id FROM users WHERE email = :email AND id != :id");
$check->execute([':email' => $email, ':id' => $user_id]);

if ($check->rowCount() > 0) {
    echo "email_exists";
    exit;
}

// HANDLE PROFILE IMAGE
$profile_pic = $old['profile_pic']; // by default same

if (!empty($_FILES['profile_image']['name'])) {

    $ext = pathinfo($_FILES['profile_image']['name'], PATHINFO_EXTENSION);
    $newName = "profile_" . $user_id . "." . $ext;
    $uploadPath = "assets/img/" . $newName;

    if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $uploadPath)) {
        $profile_pic = $newName;
    }
}

// HANDLE PASSWORD
if (empty($password)) {
    $finalPassword = $old['password'];
} else {
    $finalPassword = password_hash($password, PASSWORD_DEFAULT);
}

// UPDATE QUERY
try {
    $update = $conn->prepare("UPDATE users SET 
            user_name   = :name,
            email       = :email,
            password    = :password,
            profile_pic = :profile_pic
        WHERE id = :id
    ");

    $update->execute([
        ':name'        => $name,
        ':email'       => $email,
        ':password'    => $finalPassword,
        ':profile_pic' => $profile_pic,
        ':id'          => $user_id
    ]);

    // Update session
    $_SESSION['user_name']   = $name;
    $_SESSION['user_email']  = $email;
    $_SESSION['profile_pic'] = $profile_pic;
    echo "success";
} catch (PDOException $e) {
    echo "error: " . $e->getMessage();
}
