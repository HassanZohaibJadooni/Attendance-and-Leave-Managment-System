<?php
require "config.php";
header('Content-Type: application/json');

$response = [
    'success' => false,
    'message' => ''
];

// Agar user login nahi hai toh error do.
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'employee') {
    $response['message'] = 'Authentication Error. Please log in again.';
    echo json_encode($response);
    exit;
}

$user_id = $_SESSION['user_id'];

// Quotas 
$available_quotas = [
    'Casual' => 10,
    'Sick' => 5,
    'Annual' => 15
];
$today = date("d-m-Y");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $leave_type = trim($_POST['leave_type'] ?? '');
    $start_date = trim($_POST['start_date'] ?? '');
    $end_date = trim($_POST['end_date'] ?? '');
    $reason = trim($_POST['reason'] ?? '');

    // --- Simple Input Validation ---
    if (empty($leave_type) || empty($start_date) || empty($end_date) || empty($reason)) {
        $response['message'] = "Please fill in all required fields.";
        echo json_encode($response);
        exit;
    }

    if ($start_date > $end_date) {
        $response['message'] = "Start Date cannot be after End Date.";
        echo json_encode($response);
        exit;
    }

    if (!array_key_exists($leave_type, $available_quotas)) {
        $response['message'] = "Invalid leave type selected.";
        echo json_encode($response);
        exit;
    }

    try {
        // --- Approved Leave Overlap Check ---
        // Check if there's any APPROVED leave that overlaps with the current request period.
        $stmt_overlap = $conn->prepare("SELECT leave_type, start_date, end_date FROM leave_applications 
            WHERE user_id = :uid 
            AND status = 'Approved' 
            AND (
                (start_date <= :end_date AND end_date >= :start_date) -- Standard overlap logic
            )
            LIMIT 1
        ");
        $stmt_overlap->execute([
            ':uid' => $user_id,
            ':start_date' => $start_date,
            ':end_date' => $end_date
        ]);

        $overlapping_leave = $stmt_overlap->fetch(PDO::FETCH_ASSOC);

        if ($overlapping_leave) {
            $response['message'] = "You already on the leave to this dates.";
            echo json_encode($response);
            exit;
        }

        // --- Calculate Leave Days (Excluding Weekends/Holidays) ---
        $start = new DateTime($start_date);
        $end = new DateTime($end_date);
        $end->modify('+1 day'); // Interval ko end date shamil karne ke liye
        $period = new DatePeriod($start, new DateInterval('P1D'), $end);

        $total_leave_days = 0;

        // Holiday dates database se fetch karo
        $holiday_dates = [];
        $stmt_holidays = $conn->query("SELECT holiday_date FROM holidays");
        while ($row = $stmt_holidays->fetch(PDO::FETCH_COLUMN)) {
            $holiday_dates[] = $row;
        }

        foreach ($period as $date) {
            $currentDate = $date->format('Y-m-d');
            $dayOfWeek = $date->format('w'); // 0 Sunday through 6 Saturday numeric representation of the week

            $isWeekend = ($dayOfWeek == 0 || $dayOfWeek == 6); // Sunday or Saturday
            $isHoliday = in_array($currentDate, $holiday_dates);

            // Sirf working days ko hi leave days mein count karo
            if (!$isWeekend && !$isHoliday) {
                $total_leave_days++;
            }
        }

        // Agar total leave days zero hain (sirf weekend/holiday ki leave apply ki hai)
        if ($total_leave_days === 0) {
            $response['message'] = "Your selected duration only contains non-working days (Weekends/Holidays).";
            echo json_encode($response);
            exit;
        }

        // --- Check if already checked in today ---
        $stmt_checked = $conn->prepare("SELECT id FROM attendance 
        WHERE user_id = :uid 
        AND status IN ('present', 'late') 
        AND check_in IS NOT NULL
        AND DATE(check_in) = CURDATE()
        LIMIT 1
        ");
        $stmt_checked->execute([':uid' => $user_id]);
        $checked_in = $stmt_checked->fetch(PDO::FETCH_ASSOC);

        if ($checked_in) {
            $response['message'] = "You have already checked in today. You cannot apply for leave.";
            echo json_encode($response);
            exit;
        }


        // --- Quota Check ---
        $current_year = date('Y'); // Current year ki leaves check karo
        $quota = $available_quotas[$leave_type];

        // Abhi tak Approved ho chuki leaves ka sum nikaalo
        $stmt_used = $conn->prepare("SELECT SUM(total_days) FROM leave_applications 
            WHERE user_id = ? AND leave_type = ? AND status = 'Approved' AND YEAR(start_date) = ?
        ");
        $stmt_used->execute([$user_id, $leave_type, $current_year]);
        $used_days = $stmt_used->fetchColumn() ?? 0;

        $remaining_balance = $quota - $used_days;

        if ($total_leave_days > $remaining_balance) {
            $response['message'] = "Leave rejected! Remaining balance for " . $leave_type . " is only " . $remaining_balance . " day(s).";
        } else {
            // --- Insert into Database ---
            $stmt = $conn->prepare("INSERT INTO leave_applications 
                (user_id, leave_type, start_date, end_date, total_days, reason, status)
                VALUES (:uid, :type, :start, :end, :total, :reason, 'Pending')
            ");

            $stmt->execute([
                ':uid' => $user_id,
                ':type' => $leave_type,
                ':start' => $start_date,
                ':end' => $end_date,
                ':total' => $total_leave_days, // total_days yahan pass kar rahe hain (Only working days)
                ':reason' => $reason
            ]);

            $response['success'] = true;
            $response['message'] = "Leave application submitted successfully for " . $total_leave_days . " working day(s).";
        }
    } catch (PDOException $e) {
        $response['message'] = 'Database error: ' . $e->getMessage();
    } catch (Exception $e) {
        $response['message'] = 'Error processing dates: ' . $e->getMessage();
    }
} else {
    $response['message'] = 'Invalid request method.';
}

echo json_encode($response);
