<?php
require "config.php";
header('Content-Type: application/json');

// Authentication Check
if (!isset($_SESSION['user_id'])) {
    echo json_encode(["message" => "Not logged in"]);
    exit;
}

$user_id = $_SESSION['user_id'];

date_default_timezone_set("Asia/Karachi");
$today = date('Y-m-d');
$current_time = date('H:i:s');

// Office Timings
$start_time = '10:00:00';
$late_time = '10:00:01';
$absent_time = '10:30:00';

try {
    // --- CHECK IF USER IS ON APPROVED LEAVE TODAY ---
    $stmt_leave = $conn->prepare("SELECT id FROM leave_applications 
        WHERE user_id = :uid 
        AND status = 'Approved'
        AND start_date <= :today 
        AND end_date >= :today
    ");
    $stmt_leave->execute([':uid' => $user_id, ':today' => $today]);
    $on_leave = $stmt_leave->fetch(PDO::FETCH_ASSOC);

    if ($on_leave) {
        $stmt_att_check = $conn->prepare("SELECT status FROM attendance WHERE user_id = :uid AND date = :today");
        $stmt_att_check->execute([':uid' => $user_id, ':today' => $today]);
        $current_att = $stmt_att_check->fetch(PDO::FETCH_ASSOC);

        if ($current_att && $current_att['status'] !== 'Leave') {
            // Agar attendance record mein status 'Leave' nahi hai, toh update karo
            $update_att_status = $conn->prepare("UPDATE attendance SET status = 'Leave' WHERE user_id = :uid AND date = :today");
            $update_att_status->execute([':uid' => $user_id, ':today' => $today]);
        } elseif (!$current_att) {
            // Agar koi record nahi hai toh 'Leave' status ke saath insert karo
             $insert_leave = $conn->prepare("INSERT INTO attendance (user_id, date, status) VALUES (:uid, :date, 'Leave')");
             $insert_leave->execute([':uid' => $user_id, ':date' => $today]);
        }
        
        // Response mein message bhejo aur check-in nahi hone do
        $message = "You are currently on Approved Leave today. Check-in is not permitted.";
        
        goto return_summary; // Direct summary section par jump karo
    }
    // --- END LEAVE CHECK ---

    
    // Fetch Todays Attendance Record
    $stmt = $conn->prepare("SELECT id, check_in, check_out FROM attendance WHERE user_id = :uid AND date = :today");
    $stmt->execute([':uid' => $user_id, ':today' => $today]);
    $record = $stmt->fetch(PDO::FETCH_ASSOC);

    $message = "";

    // Check-In / Check-Out Logic
    
    if (!$record) {
        
        $status = 'Absent';

        if ($current_time < $late_time) {
            $status = 'Present';
        } elseif ($current_time >= $late_time && $current_time <= $absent_time) {
            $status = 'Late';
        } 
        
        // --- Insert Check-In ---
        $insert = $conn->prepare("INSERT INTO attendance (user_id, date, check_in, status)
            VALUES (:uid, :date, :chk_in, :status)
        ");
        $insert->execute([
            ':uid' => $user_id,
            ':date' => $today,
            ':chk_in' => $current_time,
            ':status' => $status
        ]);

        $message = "Check-in successful! Status: " . $status;

    } elseif ($record['check_out'] === NULL) {
        // --- Perform Check-Out and Calculate Working Hours ---
        
        $check_in_time = $record['check_in'];
        
        // Create DateTime objects for working hour calculation
        $datetime1 = new DateTime($today . ' ' . $check_in_time);
        $datetime2 = new DateTime($today . ' ' . $current_time);
        
        // Calculate the difference
        $interval = $datetime1->diff($datetime2);
        $working_hours_str = $interval->format('%H:%I:%S');

        // Update the record
        $update = $conn->prepare("UPDATE attendance 
            SET check_out = :chk_out, working_hours = :w_hours 
            WHERE id = :id
        ");
        $update->execute([
            ':chk_out' => $current_time,
            ':w_hours' => $working_hours_str, // Storing the calculated working hours
            ':id' => $record['id']
        ]);

        $message = "Check-out successful! Total Working Hours: " . $working_hours_str;

    } else {
        // --- Already Checked Out ---
        $message = "You have already checked out today.";
    }

    // --- Recalculate Attendance Summary for Response ---
    return_summary:

    // Count Present Days
    $stmt = $conn->prepare("SELECT COUNT(*) FROM attendance WHERE user_id = :uid AND status='Present'");
    $stmt->execute([':uid' => $user_id]);
    $total_present = $stmt->fetchColumn();

    // Count Late Days
    $stmt = $conn->prepare("SELECT COUNT(*) FROM attendance WHERE user_id = :uid AND status='Late'");
    $stmt->execute([':uid' => $user_id]);
    $total_late = $stmt->fetchColumn();

    // Today's Status
    $stmt = $conn->prepare("SELECT status, check_in FROM attendance WHERE user_id = :uid AND date = :today");
    $stmt->execute([':uid' => $user_id, ':today' => $today]);
    $today_rec = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Agar record nahi mila aur user leave par bhi nahi tha, toh default 'Unmarked'
    $today_status = ($today_rec) ? $today_rec['status'] : "Unmarked/Holiday";


    // Approved Leave Days (Current Month)
    $stmt = $conn->prepare("SELECT SUM(total_days) 
        FROM leave_applications 
        WHERE user_id = :uid 
          AND status = 'Approved'
          AND MONTH(start_date) = MONTH(CURDATE())
          AND YEAR(start_date) = YEAR(CURDATE())
    ");
    $stmt->execute([':uid' => $user_id]);
    $approved_leave = $stmt->fetchColumn() ?? 0;

    $tdays_month = date('j');

    // Attendance days count karo (Present + Late + Leave)
    $attended_days_count = (int)$total_present + (int)$total_late;
    
    // Agar aaj ka status 'Leave' hai, toh usko total present/late days mein shamil mat karo,
    // woh approved_leave mein shamil ho chuka hai.

    // Absent Calculation: Mahine ke guzre hue din - (Present + Late + Approved Leave)
    $absent = $tdays_month - ($attended_days_count + (int)$approved_leave);

    if ($absent < 0) $absent = 0; 

    // --- Return JSON Response ---
    echo json_encode([
        "message"        => $message,
        "total_present"  => (int)$total_present,
        "total_late"     => (int)$total_late,
        "approved_leave" => (int)$approved_leave,
        "absent"         => (int)$absent,
        "today_status"   => $today_status
    ]);

    exit;

} catch (PDOException $e) {
    echo json_encode(["message" => "Database Error: " . $e->getMessage()]);
    exit;
}

?>