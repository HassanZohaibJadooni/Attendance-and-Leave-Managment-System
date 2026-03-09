<?php
include "config.php";

// This query fetches a list of employees to populate the dropdown
$sql = "SELECT user_id, name FROM users ORDER BY name";

try {
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    
    $employees = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $employees[] = [
            "user_id" => $row['user_id'],
            "name" => $row['name']
        ];
    }
    
    echo json_encode($employees);

} catch (PDOException $e) {
    http_response_code(500);
    error_log("Database error: " . $e->getMessage());
    echo json_encode(["error" => "Failed to fetch employee list."]);
}
?>