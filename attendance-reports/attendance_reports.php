<?php
require "config.php";

// Check for session and redirect if user is not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
include "header.php";
include "sidebar.php";

// Fetch list of employees
$employees = $conn->query("SELECT id, user_name FROM users WHERE role='employee' ORDER BY user_name")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <title>Attendance Reports</title>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/jquery.dataTables.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <style>
        .btn-m-top {
            margin-top: 32px;
        }
        #m{
            margin-top: 38px;
        }
    </style>
</head>

<body>
    <div class="content-wrapper p-4">
        <div class="wraper">
            <div class="col">
                <div class="float-sm-end ">
                    <div id="m"><a href="admin_dashboard.php">Home</a></div>
                </div>
            </div>
            <div class="mb-4">
                <div class="form-row">

                    <div class="col-md-4">
                        <label>Start Date:</label>
                        <input type="date" class="form-control" id="start_date">
                    </div>

                    <div class="col-md-4">
                        <label>End Date:</label>
                        <input type="date" class="form-control" id="end_date">
                    </div>

                    <div class="col-md-4">
                        <label>Employee:</label>
                        <select class="form-control" id="employee_id">
                            <option value="all">Employee All</option>
                            <?php foreach ($employees as $emp): ?>
                                <option value="<?= $emp['id'] ?>"><?= $emp['user_name'] ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <h2>Detailed Attendance by range</h2>
        <div class="dataTables_wrapper dt-bootstrap4">
            <table id="Table" class="table table-bordered table-hover dataTable dtr-inline">
                <thead>
                    <tr>
                        <th class="sorting" tabindex="0" aria-controls="example2" rowspan="1" colspan="1" aria-label="Browser: activate to sort column ascending">Date</th>
                        <th class="sorting" tabindex="0" aria-controls="example2" rowspan="1" colspan="1" aria-label="Browser: activate to sort column ascending">Employee</th>
                        <th class="sorting" tabindex="0" aria-controls="example2" rowspan="1" colspan="1" aria-label="Browser: activate to sort column ascending">Status</th>
                    </tr>
                </thead>
                <tbody>
                </tbody>
            </table>
        </div>
    </div>

    <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>

    <script>
        $(document).ready(function() {
            // Initialize DataTable
            let table = $('#Table').DataTable({
                ajax: {
                    url: "process_attendance_reports.php",
                    type: "POST",
                    // data function sends the filter values on each reload
                    data: function() {
                        return {
                            start_date: $('#start_date').val(),
                            end_date: $('#end_date').val(),
                            employee_id: $('#employee_id').val(),
                            reports_from: $('#reports_from').val()
                        };
                    },
                    dataSrc: "" // Data PHP se direct simple array form me aayega kisi nested object ke andar nahi
                },
                columns: [{
                        data: "date"
                    },
                    {
                        data: "user_name"
                    },
                    {
                        data: "status"
                    }
                ]
            });
            // Reload DataTable whenever a filter changes
            $("#start_date, #end_date, #employee_id").on("change", function() {
                table.ajax.reload();
            });

            //initial load when the page is ready
            table.ajax.reload();

        });
    </script>


</body>
<!-- for dropdown show hide months weeks  -->
<script>
    $('#reports_from').on('change', function() {
        if ($(this).val() === 'monthly') {
            $('#monthSelectorBox').show();
        } else if ($(this).val() === 'weekly') {
            $('#weekSelectorBox').show();
        } else if ($(this).val() === 'daily') {
            $('#dailySelectorBox').show();
        } else {
            $("#monthSelectorBox, #weekSelectorBox, #dailySelectorBox").hide();
        };
    });
</script>

</html>