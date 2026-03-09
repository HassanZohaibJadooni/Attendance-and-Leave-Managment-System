<?php
include "config.php";

try {
    // Last month date
    $start = new DateTime('first day of last month');
    $end   = new DateTime('last day of last month');

    $start_str = $start->format('Y-m-d');
    $end_str   = $end->format('Y-m-d');

    // Fetch all employees
    $users = $conn->query("SELECT id, user_name FROM users WHERE role='employee'")->fetchAll(PDO::FETCH_ASSOC);
    $employees = [];

    foreach ($users as $u) {
        $uid = $u['id'];

        // Count only present days
        $att_stmt = $conn->prepare("SELECT COUNT(id) AS present_count 
            FROM attendance
            WHERE user_id = :uid
            AND date BETWEEN :start AND :end
            AND status = 'present'
        ");
        $att_stmt->execute([
            ':uid'   => $uid,
            ':start' => $start_str,
            ':end'   => $end_str
        ]);

        $row = $att_stmt->fetch(PDO::FETCH_ASSOC);
        $present_count = (int)$row['present_count'];

        // Push into result
        $employees[] = [
            "employee_name" => $u['user_name'],
            "present_count" => $present_count
        ];
    }

    echo json_encode([
        "employees" => $employees
    ]);
} catch (PDOException $e) {
    echo json_encode(["error" => $e->getMessage()]);
}
?>