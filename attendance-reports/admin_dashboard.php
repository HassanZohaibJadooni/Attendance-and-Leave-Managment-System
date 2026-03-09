<?php
require "config.php";

if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    session_regenerate_id(true); 
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

include "header.php";
include "sidebar.php";

$today = date('Y-m-d');

// TOTAL STAFF
$stmt = $conn->prepare("SELECT COUNT(*) FROM users WHERE role = 'employee'");
$stmt->execute();
$total_staff = $stmt->fetchColumn();

// PRESENT TODAY
$stmt = $conn->prepare("SELECT COUNT(*) FROM attendance WHERE date = :today AND status IN ('Present', 'Late')");
$stmt->bindParam(':today', $today);
$stmt->execute();
$present_today = $stmt->fetchColumn();

// ON LEAVE TODAY
$stmt_leave = $conn->prepare("SELECT COUNT(user_id) FROM leave_applications WHERE status = 'Approved' AND :today BETWEEN start_date AND end_date");
$stmt_leave->bindParam(':today', $today);
$stmt_leave->execute();
$on_leave_today = $stmt_leave->fetchColumn();

// ABSENT (UNAUTHORIZED)
$absent_unauthorized = $total_staff - $present_today - $on_leave_today;

// PENDING LEAVE REQUESTS
$stmt_pending_leaves = $conn->prepare("SELECT l.id, u.user_name AS name, l.leave_type AS type, l.start_date AS start, l.end_date AS end, l.total_days AS days 
    FROM leave_applications l
    JOIN users u ON l.user_id = u.id
    WHERE l.status = 'Pending'
    ORDER BY l.applied_on
");
$stmt_pending_leaves->execute();
$pending_leaves_data = $stmt_pending_leaves->fetchAll(PDO::FETCH_ASSOC);

// Department Summary
$stmt = $conn->prepare("SELECT COUNT(*) FROM users WHERE role = 'employee' AND department = 'IT'");
$stmt->execute();
$it = $stmt->fetchColumn();
$stmt = $conn->prepare("SELECT COUNT(*) FROM users WHERE role = 'employee' AND department = 'Finance'");
$stmt->execute();
$finance = $stmt->fetchColumn();
$stmt = $conn->prepare("SELECT COUNT(*) FROM users WHERE role = 'employee' AND department = 'HR'");
$stmt->execute();
$hr = $stmt->fetchColumn();
$stmt = $conn->prepare("SELECT COUNT(*) FROM users WHERE role = 'employee' AND department = 'Sales'");
$stmt->execute();
$sales = $stmt->fetchColumn();
$stmt = $conn->prepare("SELECT COUNT(*) FROM users WHERE role = 'employee' AND department = 'Marketing'");
$stmt->execute();
$marketing = $stmt->fetchColumn();
?>

<!doctype html>
<html lang="en">

<head>
    <title>Admin Dashboard</title>
</head>

<body class="layout-fixed sidebar-expand-lg sidebar-open bg-body-tertiary">
    <div class="wrapper">
        <main class="content-wrapper">
            <div class="content-header">
                <div class="container-fluid">
                    <div class="row mb-2">
                        <div class="col-sm-6">
                            <h1 class="m-0">Admin Dashboard</h1>
                        </div>
                    </div>
                </div>
            </div>

            <div class="content">
                <div class="container-fluid">
                    <!-- Add Holiday & Employee Management -->
                    <div class="d-flex mb-4 gap-3">
                        <a href="add_holiday.php" class="btn btn-primary shadow-sm">
                            <i class="bi bi-calendar me-1"></i> Add Holidays
                        </a>
                        <a href="employee_management.php" class="btn btn-primary shadow-sm">
                            <i class="bi bi-people-fill me-1"></i> Employee Management
                        </a>
                        <a href="attendance_reports.php" class="btn btn-info text-white shadow-sm">
                            <i class="bi bi-clipboard-data-fill me-1"></i> Attendance Reports
                        </a>
                    </div>

                    <h2 class="mt-4 mb-3">Daily Attendance Overview</h2>
                    <div class="row">
                        <div class="col-lg-3 col-6">
                            <div class="info-box bg-dark text-white shadow-sm">
                                <span class="info-box-icon"><i class="bi bi-people-fill"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Total Staff</span>
                                    <span class="info-box-number"><?= $total_staff ?></span>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-3 col-6">
                            <div class="info-box bg-success text-white shadow-sm">
                                <span class="info-box-icon"><i class="bi bi-calendar-check"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Present Today</span>
                                    <span class="info-box-number"><?= $present_today ?></span>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-3 col-6">
                            <div class="info-box bg-warning text-dark shadow-sm">
                                <span class="info-box-icon"><i class="bi bi-person-bounding-box"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">On Leave</span>
                                    <span class="info-box-number"><?= $on_leave_today ?></span>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-3 col-6">
                            <div class="info-box bg-danger text-white shadow-sm">
                                <span class="info-box-icon"><i class="bi bi-person-x-fill"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Absent</span>
                                    <span class="info-box-number"><?= $absent_unauthorized ?></span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Pending Leave Requests & Department Summary -->
                    <div class="row">
                        <div class="col-lg-7">
                            <div class="card shadow-sm">
                                <div class="card-header">
                                    <h3 class="card-title"><i class="bi bi-inboxes-fill me-2"></i>Pending Leave Requests</h3>
                                </div>
                                <div class="card-body p-0">
                                    <div class="table-responsive">
                                        <table class="table table-hover table-striped mb-0">
                                            <thead>
                                                <tr>
                                                    <th>ID</th>
                                                    <th>Employee</th>
                                                    <th>Type</th>
                                                    <th>Start Date</th>
                                                    <th>Days</th>
                                                    <th>Action</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php if (count($pending_leaves_data) > 0): ?>
                                                    <?php foreach ($pending_leaves_data as $leave): ?>
                                                        <tr>
                                                            <td><?= $leave['id'] ?></td>
                                                            <td><span class="text-primary"><?= $leave['name'] ?></span></td>
                                                            <td><span class="badge text-bg-secondary"><?= $leave['type'] ?></span></td>
                                                            <td><?= date('M d, Y', strtotime($leave['start'])) ?></td>
                                                            <td><?= $leave['days'] ?></td>
                                                            <td>
                                                                <button class="btn btn-sm btn-success me-1 approve-btn" data-id="<?= $leave['id'] ?>" title="Approve"><i class="bi bi-check-lg"></i></button>
                                                                <button class="btn btn-sm btn-danger reject-btn" data-id="<?= $leave['id'] ?>" title="Reject"><i class="bi bi-x-lg"></i></button>
                                                            </td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                <?php else: ?>
                                                    <tr>
                                                        <td colspan="6" class="text-center text-muted">No pending leave requests found.</td>
                                                    </tr>
                                                <?php endif; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-5">
                            <div class="card shadow-sm">
                                <div class="card-header">
                                    <h3 class="card-title"><i class="bi bi-building-fill me-2"></i>Staff Summary by Department</h3>
                                </div>
                                <div class="card-body p-0">
                                    <ul class="list-group list-group-flush">
                                        <li class="list-group-item d-flex justify-content-between align-items-center">
                                            IT
                                            <span class="badge bg-primary rounded-pill">
                                                <?= $it ?> Staff
                                            </span>
                                        </li>
                                        <li class="list-group-item d-flex justify-content-between align-items-center">
                                            Finance
                                            <span class="badge bg-primary rounded-pill">
                                                <?= $finance ?> Staff
                                            </span>
                                        </li>
                                        <li class="list-group-item d-flex justify-content-between align-items-center">
                                            HR
                                            <span class="badge bg-primary rounded-pill">
                                                <?= $hr ?> Staff
                                            </span>
                                        </li>
                                        <li class="list-group-item d-flex justify-content-between align-items-center">
                                            Marketing
                                            <span class="badge bg-primary rounded-pill">
                                                <?= $marketing ?> Staff
                                            </span>
                                        </li>
                                        <li class="list-group-item d-flex justify-content-between align-items-center">
                                            Sales
                                            <span class="badge bg-primary rounded-pill">
                                                <?= $sales ?> Staff
                                            </span>
                                        </li>
                                    </ul>
                                </div>
                                <div class="card-footer text-center">
                                    <small class="text-muted">Total Active Staff: <?= $total_staff ?></small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script src="jquerylibrary.js"></script>
    <script>
        $(function() {
            $(document).on('click', '.approve-btn, .reject-btn', function(e) {
                e.preventDefault();
                const button = $(this);
                const leaveId = button.data('id');
                const action = button.hasClass('approve-btn') ? 'approve' : 'reject';

                if (!confirm(`Are you sure you want to ${action} this leave request?`)) {
                    return;
                }

                $.ajax({
                    url: "process_leave_action_adminside.php",
                    type: "POST",
                    data: {
                        action: action,
                        leave_id: leaveId
                    },
                    dataType: "json",
                    success: function(res) {
                        alert(res.message);
                        if (res.success) location.reload();
                    },
                    error: function() {
                        alert("Communication error with the server.");
                    }
                });
            });
        });
    </script>

    <?php include "footer.php" ?>
</body>

</html>