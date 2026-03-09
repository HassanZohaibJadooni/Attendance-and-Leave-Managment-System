<?php
include "config.php";

try {
    $start = new DateTime('monday last week');
    $end   = new DateTime('sunday last week');

    $start_str = $start->format('Y-m-d');
    $end_str   = $end->format('Y-m-d');

    // Fetch all employees
    $users = $conn->query("SELECT id, user_name FROM users WHERE role='employee'")->fetchAll(PDO::FETCH_ASSOC);
    $employees = [];

    foreach ($users as $u) {
        $uid = $u['id'];

        // Count only leave days
        $att_stmt = $conn->prepare("SELECT COUNT(id) AS leave_count 
            FROM attendance
            WHERE user_id = :uid
            AND date BETWEEN :start AND :end
            AND status = 'leave'
        ");
        $att_stmt->execute([
            ':uid'   => $uid,
            ':start' => $start_str,
            ':end'   => $end_str
        ]);

        $row = $att_stmt->fetch(PDO::FETCH_ASSOC);
        $leave_count = (int)$row['leave_count'];

        // Push into result
        $employees[] = [
            "employee_name" => $u['user_name'],
            "leave_count" => $leave_count
        ];
    }

    echo json_encode([
        "employees" => $employees
    ]);

} catch (PDOException $e) {
    echo json_encode(["error" => $e->getMessage()]);
}
?>
