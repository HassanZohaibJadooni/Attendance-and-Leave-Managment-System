<?php
include "config.php";
// --- insert default employees ---
    $employees = [
        ["name" => "Syed Talha Bacha",     "email" => "talha@yopmail.com"],
        ["name" => "Zaid Ahmad",           "email" => "ziad@yopmail.com"],
        ["name" => "Hassan Zohaib Jadoon", "email" => "zohaib@yopmail.com"],
        ["name" => "Fawad Khan",           "email" => "fawad@yopmail.com"],
        ["name" => "Umair Khan Jadoon",    "email" => "umair@yopmail.com"],
        ["name" => "Noman Khan",           "email" => "noman@yopmail.com"],
        ["name" => "Haroon Bacha",         "email" => "haroon@yopmail.com"],
        ["name" => "Kashif Ahmad",         "email" => "kashif@yopmail.com"],
        ["name" => "Sanaullah Khan",       "email" => "sanaullah@yopmail.com"],
        ["name" => "Mohsin Naqvi",         "email" => "mohsin@yopmail.com"],
        ["name" => "Bilal Bahadar",        "email" => "bilal@yopmail.com"],
        ["name" => "Jamal Ahmad",          "email" => "jamal@yopmail.com"],
        ["name" => "Kamal Shah",           "email" => "kamal@yopmail.com"],
    ];

    $defaultPassword = "742100";

    foreach ($employees as $emp) {

        $email = $emp["email"];
        $name  = $emp["name"];

        // Check if user already exists
        $check = $conn->prepare("SELECT id FROM users WHERE email = :email");
        $check->execute([':email' => $email]);

        if ($check->rowCount() == 0) {

            // Hash password
            $hash = password_hash($defaultPassword, PASSWORD_DEFAULT);

            // Insert user
            $insert = $conn->prepare("
            INSERT INTO users (user_name, email, password, role, department)
            VALUES (:name, :email, :password, 'employee', 'IT')
        ");

            $insert->execute([
                ':name'     => $name,
                ':email'    => $email,
                ':password' => $hash
            ]);
        }
    }

    echo "<h2 style='color:red'>Users Insert Successfully..............!</h2><br><br>";

    // insert attendance
    // =================================================================
    // =================================================================
    // =================================================================
    // =================================================================
    // =================================================================
    $monthly = [
        "2025-11-03",
        "2025-11-04",
        "2025-11-05",
        "2025-11-06",
        "2025-11-07",
        "2025-11-10",
        "2025-11-11",
        "2025-11-12",
        "2025-11-13",
        "2025-11-14",
        "2025-11-17",
        "2025-11-18",
        "2025-11-19",
        "2025-11-20",
        "2025-11-21",
        "2025-11-24",
        "2025-11-25",
        "2025-11-26",
        "2025-11-27",
        "2025-11-28",
        "2025-12-01",
        "2025-12-02",
        "2025-12-03",
        "2025-12-04",
        "2025-12-05"
    ];

    $users = [
        ['Email' => 'talha@yopmail.com', 'user_id' => 2],
        ['Email' => 'Ziad@yopmail.com', 'user_id' => 3],
        ['Email' => 'zohaib@yopmail.com', 'user_id' => 4],
        ['Email' => 'fawad@yopmail.com', 'user_id' => 5],
        ['Email' => 'umair@yopmail.com', 'user_id' => 6],
        ['Email' => 'noman@yopmail.com', 'user_id' => 7],
        ['Email' => 'haroon@yopmail.com', 'user_id' => 8],
        ['Email' => 'kashif@yopmail.com', 'user_id' => 9],
        ['Email' => 'sanaullah@yopmail.com', 'user_id' => 10],
        ['Email' => 'mohsin@yopmail.com', 'user_id' => 11],
        ['Email' => 'bilal@yopmail.com', 'user_id' => 12],
        ['Email' => 'jamal@yopmail.com', 'user_id' => 13],
        ['Email' => 'kamal@yopmail.com', 'user_id' => 14],
    ];


    // RANDOM STATUS GENERATOR
    function generateStatus()
    {
        $statuses = ["Present", "Absent", "Leave", "Off Day", "Late"];
        return $statuses[array_rand($statuses)];
    }

    function randomTime($start, $end)
    {
        $min = strtotime($start);
        $max = strtotime($end);
        $rand = rand($min, $max);
        return date("H:i:s", $rand);
    }


    // INSERT LOGIC
    function insertAttendance($conn, $user_id, $dates)
    {

        foreach ($dates as $date) {

            // Check if already exists
            $check = $conn->prepare("SELECT id FROM attendance WHERE user_id = :uid AND date = :dt");
            $check->execute([':uid' => $user_id, ':dt' => $date]);

            if ($check->rowCount() == 0) {

                $status = generateStatus();

                // Default empty
                $check_in  = NULL;
                $check_out = NULL;
                $working   = NULL;

                // Present or Late
                if ($status == "Present") {

                    $check_in  = randomTime("08:45:00", "09:15:00");
                    $check_out = randomTime("16:50:00", "17:20:00");

                    $working_seconds = strtotime($check_out) - strtotime($check_in);
                    $working = gmdate("H:i:s", $working_seconds);
                } elseif ($status == "Late") {

                    $check_in  = randomTime("09:16:00", "10:00:00");
                    $check_out = randomTime("17:00:00", "17:30:00");

                    $working_seconds = strtotime($check_out) - strtotime($check_in);
                    $working = gmdate("H:i:s", $working_seconds);
                }

                // Insert
                $insert = $conn->prepare("
                INSERT INTO attendance (user_id, date, check_in, check_out, working_hours, status)
                VALUES (:uid, :dt, :in, :out, :wh, :st)
            ");

                $insert->execute([
                    ':uid' => $user_id,
                    ':dt'  => $date,
                    ':in'  => $check_in,
                    ':out' => $check_out,
                    ':wh'  => $working,
                    ':st'  => $status
                ]);
            }
        }
    }


    // RUN FOR ALL USERS
    foreach ($users as $user) {
        insertAttendance($conn, $user['user_id'], $monthly);
    }

?>


<h2 style='color:red'>Attendance Insert Successfully..............!</h2><br>