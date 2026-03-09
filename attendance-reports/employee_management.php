<?php

include "config.php";
include "header.php";
include "sidebar.php";
include "footer.php";

?>

<!DOCTYPE html>
<html>

<head>
    <!-- BOOTSTRAP CSS -->
    <link rel="stylesheet" href="bootstrap.css">

    <!-- DATATABLES CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.5/css/jquery.dataTables.min.css">

    <!-- DATATABLES BUTTONS CSS (CSV, PDF, PRINT) -->
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.dataTables.min.css">

    <!-- jQuery Library -->
    <script src="jquerylibrary.js"></script>

    <!-- Bootstrap JS -->
    <script src="bootstrap.js"></script>
    <script src="bootstrap.bundle.min.js"></script>

    <!-- DataTables JS -->
    <script src="https://cdn.datatables.net/1.13.5/js/jquery.dataTables.min.js"></script>

    <!-- DataTables Export Buttons JS -->
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.print.min.js"></script>
</head>

<body class="bg-light">
    <div class="container mt-4">
        <div class="col">
            <ol class="breadcrumb float-sm-end px-1">
                <li class="mb-2"><a href="admin_dashboard.php">Home</a></li>
            </ol>
            <button class="btn btn-success mb-3" data-bs-toggle="modal" data-bs-target="#employeeModal" id="addEmployeeBtn">
                Add New Employee
            </button>
        </div>

        <div class="table-responsive mt-1">
            <table id="employeeTable" class="table table-bordered table-striped dataTable dtr-inline">
                <thead class="text-center">
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Department</th>
                        <th>Role</th>
                        <th>Actions</th>
                    </tr>
                </thead>
            </table>
        </div>

        <!-- Modal -->
        <div class="modal fade" id="employeeModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form id="employeeForm" method="post">
                        <div class="modal-header bg-primary text-white">
                            <h5 class="modal-title">Add / Edit Employee</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <input type="hidden" id="employee_id" name="employee_id">
                            <input type="hidden" id="action" name="action" value="add">

                            <div class="mb-3">
                                <label>Name</label>
                                <input type="text" class="form-control" id="name" name="name" required>
                            </div>
                            <div class="mb-3">
                                <label>Email</label>
                                <input type="email" class="form-control" id="email" name="email" required>
                            </div>
                            <div class="mb-3" id="password-group">
                                <label>Password</label>
                                <input type="password" class="form-control" id="password" name="password" required>
                            </div>

                            <div class="mb-3">
                                <label>Department</label>
                                <select class="form-select" id="department" name="department" required>
                                    <option value="">Select Department</option>
                                    <option value="IT">IT</option>
                                    <option value="HR">HR</option>
                                    <option value="Finance">Finance</option>
                                    <option value="Sales">Sales</option>
                                    <option value="Marketing">Marketing</option>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label>Role</label>
                                <select class="form-select" id="role" name="role" required>
                                    <option value="">Select Role</option>
                                    <option value="admin">Admin</option>
                                    <option value="employee">Employee</option>
                                </select>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                            <button type="submit" class="btn btn-primary">Save Employee</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

    </div>

    <script>
        $(document).ready(function() {

            var table = $('#employeeTable').DataTable({
                "ajax": {
                    url: 'process_employee_management.php',
                    type: 'POST',
                    data: {
                        action: 'fetch'
                    }
                },
                "columns": [{
                        data: 'id'
                    },
                    {
                        data: 'user_name'
                    },
                    {
                        data: 'email'
                    },
                    {
                        data: 'department'
                    },
                    {
                        data: 'role'
                    },
                    {
                        data: 'actions'
                    }
                ],
                dom: 'Bfrtip',
                buttons: [{
                        extend: 'csv'
                    },
                    {
                        extend: 'pdf'
                    },
                    {
                        extend: 'print'
                    }
                ]
            });

            // Reset modal for Add
            $('#addEmployeeBtn').on('click', function() {
                $('#employeeForm')[0].reset();
                $('#action').val('add');
                $('.modal-title').text('Add New Employee');
                $('#password').attr('required', true);
                $('#password-help').text('');
            });

            // Submit Form
            $('#employeeForm').on('submit', function(e) {
                e.preventDefault();
                $.ajax({
                    url: 'process_employee_management.php',
                    method: 'POST',
                    data: $(this).serialize(),
                    dataType: 'json',
                    success: function(resp) {
                        if (resp.status === 'success') {

                            var myModalEl = document.getElementById('employeeModal');
                            var modal = bootstrap.Modal.getInstance(myModalEl);
                            modal.hide();

                            $('#employeeForm')[0].reset();
                            table.ajax.reload();

                            alert(resp.message); // SUCCESS MESSAGE
                        } else {
                            alert(resp.message);
                        }
                    }
                });
            });

            // Edit Button
            $(document).on('click', '.editBtn', function() {
                var id = $(this).data('id');

                $('.modal-title').text('Edit Employee');
                $('#action').val('update');
                $('#employee_id').val(id);

                $('#password').val('');
                $('#password').attr('required', false);

                $.ajax({
                    url: 'process_employee_management.php',
                    type: 'POST',
                    data: {
                        action: 'get',
                        employee_id: id
                    },
                    dataType: 'json',
                    success: function(resp) {
                        $('#name').val(resp.user_name);
                        $('#email').val(resp.email);
                        $('#department').val(resp.department);
                        $('#role').val(resp.role);
                        $('#employeeModal').modal('show');
                    }
                });
            });

            // Delete Button
            $(document).on('click', '.deleteBtn', function() {
                if (confirm('Are you sure to delete this employee?')) {
                    var id = $(this).data('id');

                    $.post('process_employee_management.php', {
                            action: 'delete',
                            employee_id: id
                        },
                        function(resp) {
                            alert(resp.message); // DELETE SUCCESS MESSAGE
                            table.ajax.reload();
                        }, 'json');
                }
            });

        });
    </script>

</body>

</html>