<?php
require "config.php";

//  Agar employee login nahi hai toh wapis login page par bhej do.
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'employee') {
    header("Location: login.php");
    exit;
}

// Header + Sidebar
include "header.php";
include "sidebar.php";
?>

<!doctype html>
<html lang="en">

<head>
    <title>Apply Leave</title>
</head>

<body class="layout-fixed sidebar-expand-lg sidebar-open bg-body-tertiary">

    <div class="wrapper">
        <main class="content-wrapper">
            <div class="content-header">
                <div class="container-fluid">
                    <div class="row mb-2">
                        <div class="col-sm-6">
                            <h1 class="m-0">Apply for Leave</h1>
                        </div>
                        <div class="col-sm-6">
                            <ol class="breadcrumb float-sm-end">
                                <li class="breadcrumb-item"><a href="employee_dashboard.php">Home</a></li>
                            </ol>
                        </div>
                    </div>
                </div>
            </div>

            <div class="content">
                <div class="container-fluid">
                    <div class="row">
                        <div class="col-lg-8 offset-lg-2">
                            <div class="card shadow-sm">
                                <div class="card-header">
                                    <h3 class="card-title">New Leave Application</h3>
                                </div>
                                <div class="card-body">
                                    <form method="POST" action="" id="leave_form">
                                        <div class="mb-3">
                                            <label for="leave_type" class="form-label">Leave Type</label>
                                            <select name="leave_type" id="leave_type" class="form-control" required>
                                                <option value="">Select Leave Type</option>
                                                <?php
                                                $leave_types = ['Casual', 'Sick', 'Annual'];
                                                $available_quotas = ['Casual' => 10, 'Sick' => 5, 'Annual' => 15];
                                                foreach ($leave_types as $type):
                                                ?>
                                                    <option value="<?= $type ?>">
                                                        <?= $type ?> (Limit: <?= $available_quotas[$type] ?> days)
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label for="start_date" class="form-label">Start Date</label>
                                                <input type="date" name="start_date" id="start_date" class="form-control" required>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label for="end_date" class="form-label">End Date</label>
                                                <input type="date" name="end_date" id="end_date" class="form-control" required>
                                             </div>
                                        </div>
                                        <div class="mb-3">
                                            <label for="reason" class="form-label">Reason</label>
                                            <textarea name="reason" id="reason" rows="4" class="form-control" required></textarea>
                                        </div>
                                        <button type="submit" class="btn btn-primary">Submit Application</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <?php include "footer.php" ?>
    <script src="jquerylibrary.js"></script>

    <script>
        $(function() {
            $("#leave_form").on('submit', function(e) {
                e.preventDefault();
                let formData = $(this).serialize();
                $.ajax({
                    url: "process_leave.php",
                    type: "POST",
                    data: formData,
                    dataType: "json",
                    success: function(res) {
                        alert(res.message);
                        if (res.success) {
                            // Agar submit ho jaye, toh form ko clear kar do
                            $("#leave_form")[0].reset();
                            window.location.href = "employee_dashboard.php";
                        }
                    },
                    error: function(xhr, status, error) {
                        alert("Server error. Please try again.");
                    }
                });
            });
        });
    </script>
</body>

</html>