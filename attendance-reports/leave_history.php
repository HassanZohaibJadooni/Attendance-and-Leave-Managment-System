<?php
require "config.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Header + Sidebar
include "header.php";
include "sidebar.php";

// Fetch Leave records of logged-in user
$stmt = $conn->prepare("SELECT * FROM leave_applications WHERE user_id = ? ORDER BY id DESC");
$stmt->execute([$user_id]);
$leave_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!doctype html>
<html lang="en">
<head>
    <title>Leave History</title>
</head>

<body class="layout-fixed sidebar-expand-lg sidebar-open bg-body-tertiary">

<div class="table-responsive mt-3 container">
            <table id="employeeTable" class="table table-bordered table-striped dataTable dtr-inline">
                <thead class="text-center">
                    <tr>
                        <th>Leave Type</th>
                        <th>Reason</th>
                        <th>Start Date</th>
                        <th>End Date</th>
                        <th>Status</th>
                    </tr>
                </thead>
    <?php foreach ($leave_data as $row): ?>
    <tr class="text-center">
        <td><?= $row['leave_type'] ?></td>
        <td><?= $row['reason'] ?></td>
        <td><?= date("d - m - Y", strtotime($row['start_date'])) ?></td>
        <td><?= date("d - m - Y", strtotime($row['end_date'])) ?></td>

        <td>
            <?php if ($row['status'] == "Approved"): ?>
                <span class="approved">Approved</span>

            <?php elseif ($row['status'] == "Pending"): ?>
                <span class="pending">Pending</span>

            <?php else: ?>
                <span class="rejected">Rejected</span>
            <?php endif; ?>
        </td>
    </tr>
    <?php endforeach; ?>

</table>
</div>


<?php include "footer.php"; ?>
<script src="jquerylibrary.js"></script>

</body>
</html>
