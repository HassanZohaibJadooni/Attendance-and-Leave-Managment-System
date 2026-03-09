<?php
header('Content-Type: application/json');
require "config.php";

// Get filters from POST request
$employee_id = $_POST['employee_id'] ?? "all";
$input_start_date = $_POST['start_date'] ?? "";
$input_end_date   = $_POST['end_date'] ?? "";

$today = (new DateTime())->format('Y-m-d');

// If user provides no End Date, default to Today.
$filter_end_date = !empty($input_end_date) ? $input_end_date : $today;
$filter_start_date = !empty($input_start_date) ? $input_start_date : '1900-01-01'; //old date for barrraaaa range


// Fetch Public Holidays
$holidays = [];
$stmtHolidays = $conn->prepare("SELECT holiday_date FROM holidays WHERE holiday_date BETWEEN ? AND ?");
// Use query range for fetching holidays
$stmtHolidays->execute([$filter_start_date, $filter_end_date]);
$holiday_dates = $stmtHolidays->fetchAll(PDO::FETCH_COLUMN);


// Fetch Employee List AND their joining dates
$users = [];
$whereUsers = "role='employee'";
$paramsUsers = [];
if ($employee_id !== "all") {
    $whereUsers .= " AND id=?";
    $paramsUsers[] = $employee_id;
}
// Fetching joining_date here
$stmtUsers = $conn->prepare("SELECT id, user_name, joining_date FROM users WHERE $whereUsers ORDER BY user_name");
$stmtUsers->execute($paramsUsers);
$users = $stmtUsers->fetchAll(PDO::FETCH_ASSOC);


// Build and Execute Attendance Query
$whereAtt = [];
$paramsAtt = [];

// Apply Date Filter to Attendance Query based on provided input or broad range
if (!empty($input_start_date)) {
    $whereAtt[] = "a.date BETWEEN ? AND ?";
    $paramsAtt[] = $input_start_date;
    $paramsAtt[] = $filter_end_date;
} else {
    // If start date is NOT provided fetch attendance up to end date
    $whereAtt[] = "a.date <= ?";
    $paramsAtt[] = $filter_end_date;
}

// Employee Filter by dropdown
if ($employee_id !== "all") {
    $whereAtt[] = "a.user_id=?";
    $paramsAtt[] = $employee_id;
}

$whereSQL = !empty($whereAtt) ? "WHERE " . implode(" AND ", $whereAtt) : "";

// Select attendance records
$sql = "SELECT a.user_id, a.date, a.status FROM attendance a $whereSQL";

$stmtAtt = $conn->prepare($sql);
$stmtAtt->execute($paramsAtt);
$attDataDB = $stmtAtt->fetchAll(PDO::FETCH_ASSOC);


$final_report = [];
$attendance_look = [];

foreach ($attDataDB as $row) {
    $attendance_look[$row['user_id']][$row['date']] = $row['status'];
}

// Loop through every employee
foreach ($users as $user) { 
    $user_id = $user['id'];
    $user_name = $user['user_name'];
    $joining_date = $user['joining_date'];

    $default_start_date = $joining_date;
    if (!empty($input_start_date)) {
        $default_start_date = max($input_start_date, $joining_date);
    }

    // Now, generate the period specific to this employee
    $period_start = new DateTime($default_start_date);
    $period_end = (new DateTime($filter_end_date))->modify('+1 day');

    $period = new DatePeriod(
        $period_start,
        new DateInterval('P1D'),
        $period_end
    );

    foreach ($period as $date) {
        $current_date = $date->format('Y-m-d');
        $day_of_week = $date->format('N');

        $status = "Absent";

        // Attendance Status Logic
        // Check if attendance exists in the database Present, Late, Leave
        if (isset($attendance_look[$user_id][$current_date])) {
            $status = $attendance_look[$user_id][$current_date];
        }
        else {
            // Check for Weekend
            if ($day_of_week >= 6) {
                $status = "Off Day";
            }
            // Check for Public Holiday
            elseif (isset($holiday_dates[$current_date])) {
                $status = "Off Day";
            }
        }

        // Add to final report
        $final_report[] = [
            'date'      => $current_date,
            'user_name' => $user_name,
            'status'    => $status
        ];
    }
}

echo json_encode($final_report);



?>
