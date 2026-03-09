<?php
require "config.php";
if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    session_regenerate_id(true);
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Header + Sidebar
include "header.php";
include "sidebar.php";

// === FETCH DATA ===
$today = date('Y-m-d');

// Today Status
$stmt = $conn->prepare("SELECT status FROM attendance WHERE user_id = :uid AND date = :today");
$stmt->bindParam(':uid', $user_id);
$stmt->bindParam(':today', $today);
$stmt->execute();
$today_record = $stmt->fetch(PDO::FETCH_ASSOC);
$today_status = $today_record['status'] ?? "No Checkin Today";

// FETCH UPCOMING HOLIDAYS
$holidays = $conn->prepare("SELECT * FROM holidays ORDER BY holiday_date ASC");
$holidays->execute();
$holidayData = $holidays->fetchAll(PDO::FETCH_ASSOC);


// LEAVE BALANCES 
$leave_types = [
    'Casual' => 10,
    'Sick' => 5,
    'Annual' => 15
];

$leave_balances = [];
foreach ($leave_types as $type => $quota) {
    $stmt = $conn->prepare("SELECT SUM(total_days) as used_days
        FROM leave_applications 
        WHERE user_id = :uid 
          AND leave_type = :type 
          AND status = 'Approved'
          AND YEAR(start_date) = YEAR(CURDATE())
    ");
    $stmt->execute([':uid' => $user_id, ':type' => $type]);
    $used = $stmt->fetchColumn() ?? 0;

    $leave_balances[$type] = [
        'total' => $quota,
        'used' => $used,
        'remaining' => $quota - $used
    ];
}
?>

<!doctype html>
<html lang="en">

<head>
    <title>Employee Dashboard</title>
</head>

<body class="layout-fixed sidebar-expand-lg sidebar-open bg-body-tertiary">
    <div class="wrapper">
        <main class="content-wrapper">

            <!-- Page Header -->
            <div class="content-header">
                <div class="container-fluid">
                    <div class="row mb-2">
                        <div class="col-sm-6">
                            <h1 class="m-0">Employee Dashboard</h1>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Main Content -->
            <div class="content">
                <div class="container-fluid">

                    <!-- Check-In / Check-Out Button -->
                    <div class="row mb-4">
                        <div class="col-12 text-center text-md-start">
                            <a id="checkin_checkout" class="btn btn-lg btn-primary">Check-In / Check-Out</a>
                        </div>
                    </div>

                    <!-- Attendance Summary -->
                    <h2 class="mt-4 mb-3">Today (<?= date("l") ?>)</h2>
                    <div class="row">
                        <div class="col-lg-12 col-6">
                            <div class="info-box bg-danger text-white shadow-sm">
                                <span class="info-box-icon"><i class="bi bi-calendar-check"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Today Status</span>
                                    <span class="info-box-number" id="presentToday">
                                        <?= ($today_status == "Present" || $today_status == "Late" || $today_status == "Absent" || $today_status == "Leave") ? $today_status : "No Checkin" ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Leave Balances -->
                    <div class="row">
                        <div class="col-lg-6">
                            <div class="card shadow-sm">
                                <div class="card-header">
                                    <h3 class="card-title"><i class="bi bi-envelope-open-fill me-2"></i>Current Leave Balance</h3>
                                    <div class="card-tools">
                                        <a href="apply_leave.php" class="btn btn-sm btn-outline-primary">
                                            <i class="bi bi-plus-circle"></i> Apply Now
                                        </a>
                                        <a href="leave_history.php" class="btn btn-sm btn-outline-dark">
                                            <i class="bi bi-clock-history"></i> Leave History
                                        </a>
                                    </div>

                                </div>
                                <div class="card-body p-0">
                                    <?php foreach ($leave_balances as $type => $balance): ?>
                                        <ul class="list-group list-group-flush">
                                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                                <span class="lead"><?= $type ?> Leave</span>
                                                <div class="d-flex justify-content-between">
                                                    <span class="badge bg-dark">Total: <?= $balance['total'] ?></span>
                                                    <span class="badge bg-secondary me-2">Used:
                                                        <span class="used-<?= strtolower($type) ?>"><?= $balance['used'] ?></span>
                                                    </span>
                                                    <span class="badge bg-success me-2">Remaining:
                                                        <span class="remaining-<?= strtolower($type) ?>"><?= $balance['remaining'] ?></span>
                                                    </span>
                                                </div>
                                            </li>
                                        </ul>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>

                        <!-- Upcoming Holidays -->
                        <div class="col-lg-6">
                            <div class="card shadow-sm">
                                <div class="card-header">
                                    <h3 class="card-title"><i class="bi bi-gift-fill me-2"></i>Upcoming Holidays</h3>
                                </div>
                                <div class="card-body p-0">
                                    <table class="table table-hover table-striped mb-0">
                                        <thead>
                                            <tr>
                                                <th>Date</th>
                                                <th>Holiday</th>
                                                <th>Day</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (!empty($holidayData)) {
                                                foreach ($holidayData as $h) {
                                                    $dayName = date("l", strtotime($h['holiday_date'])); ?>
                                                    <tr>
                                                        <td><span class="badge bg-dark"><?= date('M jS, Y', strtotime($h['holiday_date'])) ?></span></td>
                                                        <td><?= $h['holiday_name'] ?></td>
                                                        <td><?= $dayName ?></td>
                                                    </tr>
                                                <?php }
                                            } else { ?>
                                                <tr>
                                                    <td colspan="3" class="text-center text-muted">No upcoming holidays found.</td>
                                                </tr>
                                            <?php } ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <?php include "footer.php"; ?>
    <script src="jquerylibrary.js"></script>

    <script>
        $(function() {
            $("#checkin_checkout").click(function(e) {
                e.preventDefault();
                const d = new Date();
                let hour = d.getHours();

                if (hour >= 18) {
                    alert("You can't checkin because 6 PM is office off!");
                    return;
                }

                if (!confirm("Are you sure you want to perform this action?")) return;

                $.ajax({
                    url: "process_checkin_checkout.php",
                    type: "POST",
                    dataType: "json",
                    success: function(res) {
                        alert(res.message);
                        $("#presentToday").text(res.today_status);
                        location.reload();
                        // Update leave dynamically
                        $.each(res.leave_balances, function(type, balance) {
                            $(".used-" + type.toLowerCase()).text(balance.used);
                            $(".remaining-" + type.toLowerCase()).text(balance.remaining);
                        });
                    },
                    error: function() {
                        alert("Error connecting to server!");
                    }
                });
            });
        });
    </script>

</body>

</html>