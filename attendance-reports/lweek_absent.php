<?php
include "config.php";

try {

    // last week dates
    $start = new DateTime('monday last week');
    $end   = new DateTime('sunday last week');

    $start_str = $start->format('Y-m-d');
    $end_str   = $end->format('Y-m-d');

    // total days in last week
    $interval = $start->diff($end);
    $total_days = $interval->days + 1;  

    //  fetch holidays 
    $holiday_stmt = $conn->query("SELECT holiday_date 
        FROM holidays 
        WHERE holiday_date BETWEEN '$start_str' AND '$end_str'
    ");
    $holidays = $holiday_stmt->fetchAll(PDO::FETCH_COLUMN);

    // remove holidays and weekend_days 
    $current = clone $start;
    $valid_days = $total_days;

    while ($current <= $end) {
        $weekend_days  = $current->format('w');
        $date = $current->format('Y-m-d');

        // Remove if holiday
        if (in_array($date, $holidays)) {
            $valid_days--;
        }
        // Remove Sat/Sun
        else if ($weekend_days == 0 || $weekend_days == 6) {
            $valid_days--;
        }

        $current->modify('+1 day');
    }


    // fetch users 
    $users = $conn->query("SELECT id, user_name FROM users WHERE role='employee'")->fetchAll(PDO::FETCH_ASSOC);
    $employees = [];

    //  For each user calculate FINAL ABSENT count 
    foreach ($users as $u) {

        $uid = $u['id'];

        // Count all attendance statuses to subtract
        $att_stmt = $conn->prepare(" SELECT COUNT(id) AS total_status 
            FROM attendance
            WHERE user_id = :uid
            AND date BETWEEN :start AND :end
            AND status IN ('Leave','Off Day','Present','Late')
        ");

        $att_stmt->execute([
            ':uid'   => $uid,
            ':start' => $start_str,
            ':end'   => $end_str
        ]);

        $row = $att_stmt->fetch(PDO::FETCH_ASSOC);
        $minus_count = $row['total_status'];

        // Final Absent
        $final_absent = $valid_days - $minus_count;

        // Push into result
        $employees[] = [
            "employee_name" => $u['user_name'],
            "absent_count"  => $final_absent
        ];
    }

    echo json_encode([
        "working_days" => $valid_days,
        "employees"    => $employees
    ]);
} catch (PDOException $e) {
    echo json_encode(["error" => $e->getMessage()]);
}
