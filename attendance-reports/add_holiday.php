<?php
include "config.php";
include "header.php";
include "sidebar.php";

// FETCH UPCOMING HOLIDAYS FROM DATABASE
$holidays = $conn->prepare("SELECT * FROM holidays ORDER BY holiday_date ASC");
$holidays->execute();
$holidayData = $holidays->fetchAll(PDO::FETCH_ASSOC);
?>

<!doctype html>
<html lang="en">

<head>
    <title>Holiday Management</title>
    <script src="jquerylibrary.js"></script>
</head>

<body class="layout-fixed sidebar-expand-lg sidebar-open bg-body-tertiary">
    <div class="wrapper">

        <main class="content-wrapper">

            <div class="content-header">
                <div class="container-fluid">
                    <div class="row mb-2">
                        <div class="col-sm-6">
                            <h1 class="m-0">Holiday And Weekends</h1>
                        </div>
                        <div class="col-sm-6">
                            <ol class="breadcrumb float-sm-end">
                                <li class="mt-3"><a href="admin_dashboard.php">Home</a></li>
                            </ol>
                        </div>
                    </div>
                </div>
            </div>

            <div class="content">
                <div class="container-fluid">
                    <div class="row">

                        <!-- Add Holiday Form -->
                        <div class="col-lg-6">
                            <div class="card card-primary card-outline shadow-sm">
                                <div class="card-header">
                                    <h3 class="card-title">Add New Holiday</h3>
                                </div>

                                <form id="addHolidayForm">
                                    <div class="card-body">

                                        <div class="mb-3">
                                            <label class="form-label">Holiday Name</label>
                                            <input type="text" class="form-control" id="holiday_name"
                                                name="holiday_name" required placeholder="e.g., Eid-ul-Fitr">
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label">Date</label>
                                            <input type="date" class="form-control" id="holiday_date"
                                                name="holiday_date" required>
                                        </div>
                                    </div>
                                    <div class="card-footer">
                                        <button type="submit" class="btn btn-primary">Add Holiday</button>
                                    </div>

                                </form>

                            </div>
                        </div>

                        <!-- Weekly Off days -->
                        <div class="col-lg-6">
                            <div class="row">

                                <!-- WEEKENDS BOX -->
                                <div class="col-lg-12">
                                    <div class="card card-info card-outline shadow-sm mb-4">
                                        <div class="card-header">
                                            <h3 class="card-title">Weekly Off Days</h3>
                                        </div>
                                        <div class="card-body">
                                            <strong>Saturday</strong><br>
                                            <strong>Sunday</strong>
                                        </div>
                                    </div>
                                </div>

                                <!-- UPCOMING HOLIDAYS BOX -->
                                <div class="col-lg-12">
                                    <div class="card shadow-sm card-primary card-outline">
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
                                                    <?php if (!empty($holidayData)) { foreach ($holidayData as $h) { $dayName = date("l", strtotime($h['holiday_date']));?>
                                                            <t r><td><span class="badge bg-dark"><?= date('M jS, Y', strtotime($h['holiday_date'])) ?></span>
                                                                </td><td><?= $h['holiday_name'] ?></td><td><?= $dayName ?></td></tr>
                                                    <?php }
                                                    } else { ?>
                                                        <tr>
                                                            <td colspan="3" class="text-center text-muted">
                                                                No upcoming holidays found.
                                                            </td>
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
                </div>
            </div>

        </main>

    </div>

    <?php include "footer.php"; ?>

    <script>
        $(document).ready(function () {

            let today = "<?= date('Y-m-d') ?>";

            // CHECK DUPLICATE DATE PAST DATE
            $("#holiday_date").on("change", function () {

                let selected = $(this).val();

                // AJAX check duplicate holiday
                $.post("process_holiday.php", {
                    action: "check_date",
                    holiday_date: selected
                }, function (response) {
                    let res = JSON.parse(response);
                    if (res.exists) {
                        alert("A holiday already exists on this date!");
                        $("#holiday_date").val("");
                    }
                });

            });

            // INSERT HOLIDAY USING AJAX
            $("#addHolidayForm").on("submit", function (e) {
                e.preventDefault();

                let name = $("#holiday_name").val();
                let date = $("#holiday_date").val();

                $.post("process_holiday.php", {
                    action: "insert_holiday",
                    holiday_name: name,
                    holiday_date: date
                }, function (response) {
                    alert(response);
                    $("#addHolidayForm")[0].reset();
                    location.reload();
                });

            });

        });
    </script>

</body>
</html>
