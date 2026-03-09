<?php
require "config.php";


// CHECK IF HOLIDAY DATE ALREADY EXISTS
if (isset($_POST['action']) && $_POST['action'] === "check_date") {

    $date = $_POST['holiday_date'];

    $stmt = $conn->prepare("SELECT COUNT(*) FROM holidays WHERE holiday_date = :date");
    $stmt->execute([":date" => $date]);

    echo json_encode([
        "exists" => $stmt->fetchColumn() > 0
    ]);
    exit;
}



// INSERT NEW HOLIDAY
if (isset($_POST['action']) && $_POST['action'] === "insert_holiday") {

    $hname = trim($_POST['holiday_name']);
    $hdate = $_POST['holiday_date'];

    if (!$hname || !$hdate) {
        echo "Please fill all fields!";
        exit;
    }

    // Check duplicate backend
    $check = $conn->prepare("SELECT COUNT(*) FROM holidays WHERE holiday_date = :date");
    $check->execute([":date" => $hdate]);

    if ($check->fetchColumn() > 0) {
        echo "Holiday already exists!";
        exit;
    }

    try {

        $stmt = $conn->prepare(
            "INSERT INTO holidays (holiday_name, holiday_date)
             VALUES (:hname, :hdate)"
        );

        $stmt->execute([
            ":hname" => $hname,
            ":hdate" => $hdate
        ]);

        echo "Holiday Added Successfully!";

    } catch (PDOException $e) {
        echo "Database Error: " . $e->getMessage();
    }

    exit;
}

?>
