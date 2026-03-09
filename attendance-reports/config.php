<?php
session_start();

$servername = "localhost";
$username   = "root";
$password   = "";
$dbname     = "registration";

try {
    // --- CREATE DATABASE IF NOT EXISTS ---
    $pdo = new PDO("mysql:host=$servername", $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbname`");

    // --- CONNECT TO DATABASE ---
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);

    // --- USERS TABLE ---
    $conn->exec("CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_name VARCHAR(100),
        email VARCHAR(100) UNIQUE,
        password VARCHAR(255),
        department VARCHAR(200),
        role ENUM('admin','employee') DEFAULT 'employee',
        profile_pic VARCHAR(255) DEFAULT 'default.png', 
        joining_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )
");


    // --- ATTENDANCE TABLE ---
    $conn->exec(" CREATE TABLE IF NOT EXISTS attendance (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            date DATE NOT NULL,
            check_in TIME NULL,
            check_out TIME NULL,
            working_hours  TIME NULL,
            status ENUM('Present','Late','Absent','Off Day', 'Leave') DEFAULT 'Present',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY unique_attendance (user_id, date),
            FOREIGN KEY (user_id) REFERENCES users(id)
        )
    ");

    // --- LEAVE APPLICATIONS ---
    $conn->exec("CREATE TABLE IF NOT EXISTS leave_applications (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            leave_type ENUM('Casual','Sick','Annual') NOT NULL,
            start_date DATE NOT NULL,
            end_date DATE NOT NULL,
            total_days INT NOT NULL,
            reason TEXT,
            status ENUM('Pending','Approved','Rejected') DEFAULT 'Pending',
            applied_on TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id)
        )
    ");

    // Notification Table
    $conn->exec("CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    message VARCHAR(255) NOT NULL,
    is_read INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    );
    ");

    // --- HOLIDAYS ---
    $conn->exec("CREATE TABLE IF NOT EXISTS holidays (
            id INT AUTO_INCREMENT PRIMARY KEY,
            holiday_name VARCHAR(255) NOT NULL,
            holiday_date DATE NOT NULL UNIQUE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");

    // --- INSERT DEFAULT ADMIN ACCOUNT ---
    $adminEmail = "admin@gmail.com";
    $check = $conn->prepare("SELECT id FROM users WHERE email = :email");
    $check->execute([':email' => $adminEmail]);
    if ($check->rowCount() == 0) {
        $hash = password_hash("123456", PASSWORD_DEFAULT);
        $insert = $conn->prepare("
            INSERT INTO users (user_name, email, password, role)
            VALUES (:name, :email, :password, 'admin')
        ");
        $insert->execute([
            ':name'     => "Admin",
            ':email'    => $adminEmail,
            ':password' => $hash
        ]);
    }

    $adminEmail = "employee@gmail.com";
    $check = $conn->prepare("SELECT id FROM users WHERE email = :email");
    $check->execute([':email' => $adminEmail]);
    if ($check->rowCount() == 0) {
        $hash = password_hash("123456", PASSWORD_DEFAULT);
        $insert = $conn->prepare("
            INSERT INTO users (user_name, email, password, role)
            VALUES (:name, :email, :password, 'employee')
        ");
        $insert->execute([
            ':name'     => "Employee",
            ':email'    => $adminEmail,
            ':password' => $hash
        ]);
    }
} catch (PDOException $e) {
    die("Database Error: " . $e->getMessage());
}
