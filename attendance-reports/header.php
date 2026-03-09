<?php

$username = $_SESSION['user_name'];

?>
<!DOCTYPE html>
<html lang="en">

<head>
  <link rel="stylesheet" href="bootstrap.css">
  <script src="boostrap.js"></script>
  <script src="bootstrap.bundle.min.js"></script>
  <link rel="stylesheet" href="profile.css">
</head>

<body class="layout-fixed sidebar-expand-lg sidebar-open bg-body-tertiary">
  <!-- App Wrapper-->
  <div class="app-wrapper">
    <!-- Header-->
    <nav class="app-header navbar navbar-expand bg-body">
      <!-- Container-->
      <div class="container-fluid">
        <!-- Start Navbar Links-->
        <ul class="navbar-nav">
          <li class="nav-item">
            <a class="nav-link" data-lte-toggle="sidebar" href="#" role="button">
              <i class="bi bi-list"></i>
            </a>
          </li>
        </ul>
        <ul class="navbar-nav ms-auto">
          <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'employee'): ?>
          <!-- Notifications Dropdown Menu-->
          <li class="nav-item dropdown">
            <a class="nav-link" data-bs-toggle="dropdown" id="notifBtn" href="#">
              <i class="bi bi-bell-fill"></i>
              <span class="navbar-badge badge text-bg-warning" id="notifCount">0</span>
            </a>
            <div class="dropdown-menu dropdown-menu-lg dropdown-menu-end" id="notifList">
              <div class="dropdown-divider"></div>
            </div>
          </li>
          <?php endif ?>
          <!-- Fullscreen Toggle-->
          <li class="nav-item">
            <a class="nav-link" href="#" data-lte-toggle="fullscreen">
              <i data-lte-icon="maximize" class="bi bi-arrows-fullscreen"></i>
              <i data-lte-icon="minimize" class="bi bi-fullscreen-exit" style="display: none"></i>
            </a>
          </li>
          <!-- User Menu Dropdown-->
          <li class="nav-item dropdown user-menu">
            <a href="#" class="nav-link dropdown-toggle" data-bs-toggle="dropdown">
              <img
                src="assets/img/<?= $_SESSION['profile_pic'] ?>"
                class="user-image rounded-circle shadow"
                alt="User Image" />
              <span class="d-none d-md-inline"><?= $username ?></span>
            </a>
            <ul class="dropdown-menu dropdown-menu-lg dropdown-menu-end">
              <li class="user-header text-bg-primary">
                <img src="assets/img/<?= $_SESSION['profile_pic'] ?>" class="rounded-circle shadow" alt="User Image">
                <span><?= $_SESSION['user_name'] ?></span>

                <p><?= $username ?> - Web Developer <small>Member since <?= date("F - Y", strtotime($_SESSION['joining_date']))?></small></p>
              </li>
              <li class="user-footer">
                <a href="logout.php" class="btn btn-default btn-flat float-end">Log out</a>
                <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'employee'): ?>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#profileModal">
                  Edit Profile
                </button>
                <?php endif; ?>
              </li>
            </ul>
          </li>
        </ul>
      </div>
    </nav>

    <!-- PROFILE UPDATE MODAL -->
    <div class="modal fade" id="profileModal" tabindex="-1">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4">

          <form id="profileUpdateForm" method="post" enctype="multipart/form-data">

            <!-- HEADER -->
            <div class="modal-header bg-warning text-white">
              <h5 class="modal-title">Edit Profile</h5>
              <button type="button" class="btn btn-close btn-danger" data-bs-dismiss="modal"></button>
            </div>

            <!-- BODY -->
            <div class="modal-body">

              <!-- Profile Image -->
              <div class="profile-pic-container">
                <img id="previewImg" src="assets/img/<?= $_SESSION['profile_pic'] ?>"
                  class="rounded-circle shadow" style="width:120px; height:120px; object-fit:cover;">
                <div class="add-icon" onclick="document.getElementById('profile_image').click();">+</div>
              </div>

              <input type="file" id="profile_image" name="profile_image" accept="image/*" hidden>


              <input type="hidden" name="id" value="<?= $_SESSION['user_id'] ?>">

              <!-- Name -->
              <div class="mb-3 d-flex">
                <label class="form-label" style="width: 30%;">Name</label>
                <div class="input-group">
                  <input type="text" class="form-control" id="fullName" name="fullName" value="<?= $_SESSION['user_name'] ?>">
                  <span class="input-group-text bg-white"><i class="bi bi-person"></i></span>
                </div>
              </div>

              <!-- Email -->
              <div class="mb-3 d-flex">
                <label class="form-label" style="width: 30%;">Email</label>
                <div class="input-group">
                  <input type="email" class="form-control" id="email" name="email" value="<?= $_SESSION['user_email'] ?>">
                  <span class="input-group-text bg-white"><i class="bi bi-envelope"></i></span>
                </div>
              </div>

              <!-- password -->
              <div class="mb-3 d-flex">
                <label class="form-label" style="width: 30%;">Password</label>
                <div class="input-group">
                  <input type="password" class="form-control" id="password" name="password">
                  <span class="input-group-text bg-white"><i class="bi bi-upc-scan"></i></span>
                </div>
              </div>
            </div>
            <!-- FOOTER -->
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
              <button type="submit" class="btn btn-success">Update Profile</button>
            </div>

          </form>
        </div>
      </div>
    </div>

</body>

</html>

<body>
  <script src="jquerylibrary.js"></script>
  <script>
    $('[data-bs-dismiss="modal"]').on("click", function(e) {
      if (!confirm("Are you sure you don't want to update the profile?")) {
        return;
      }
      window.location.reload();
    });

    function loadNotifications() {
      $.ajax({
        url: "fetch_notifications.php",
        type: "POST",
        dataType: "json",
        success: function(data) {

          $("#notifCount").text(data.unread);

          let html = ``;

          if (data.list.length > 0) {
            $.each(data.list, function(data, item) {
              html += `<div class="notif-item" data-id="${item.id}">
                            <p class="dropdown-item single-msg">
                                ${item.message}<br>
                                <small class="text-secondary">${item.created_at}</small>
                            </p>
                        </div>
                      <hr>`});

            html += `<button class="dropdown-item text-center btn btn-sm btn-primary" id="readAllBtn">Read All</button>`;
          } else {
            html += `<a class="dropdown-item text-center">No Notifications</a>`;
          }

          $("#notifList").html(html);
        }
      });
    }

    loadNotifications();

    //hide + badge update
    $(document).on("click", ".single-msg", function() {
      $(".notif-item").hide();
      let count = parseInt($("#notifCount").text());
      $("#notifCount").text(count > 0 ? count - 1 : 0);
    });

    //Read All
    $(document).on("click", "#readAllBtn", function() {
      $(".notif-item").hide();
      $("#notifCount").text(0);
    });


    // profile jquery
    $(document).ready(function() {

      $("#profileUpdateForm").on("submit", function(e) {
        e.preventDefault();

        let formData = new FormData(this);

        $.ajax({
          url: "profile_update.php",
          type: "POST",
          data: formData,
          contentType: false,
          processData: false,
          success: function(res) {

            if (res.includes("email_exists")) {
              alert("This email is already registered. Please use a different email.");
              return;
            }

            if (res.includes("success")) {
              alert("Profile updated successfully!");
              window.location.reload();
            }
          },
          error: function() {
            alert("Request failed.");
          }
        });
      });

    });

    $("#profile_image").on("change", function() {
      let file = this.files[0];

      if (file) {
        let reader = new FileReader();

        reader.onload = function(e) {
          $("#previewImg").attr("src", e.target.result);
        };

        reader.readAsDataURL(file);
      }
    });
  </script>