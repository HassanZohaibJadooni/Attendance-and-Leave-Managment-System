<?php
require "config.php";
header('Content-Type: application/json');
// ================================================================================================================
// ================================================================================================================ 
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'src/PHPMailer.php';
require 'src/SMTP.php';
require 'src/Exception.php';

// send leave email function start
function sendLeaveEmail($toEmail, $subject, $body)
{
    $mail = new PHPMailer(true);

    try {
        // SMTP Settings
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'hassanzohaibjadooni@gmail.com';
        $mail->Password   = 'lhwv tnxz uuqi adjs'; // Gmail App Password
        $mail->SMTPSecure = 'tls';
        $mail->Port       = 587;

        // Sender & Receiver
        $mail->setFrom('hassanzohaibjadooni@gmail.com');
        $mail->addAddress($toEmail);

        // Email Content
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $body;

        $mail->send();
        return true;
    } catch (Exception $e) {
        return false;
    }
}

// send leave email function end
// ================================================================================================================
// ================================================================================================================ 
$response = [
    'success' => false,
    'message' => ''
];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $action = $_POST['action']; // approve or reject
    $leave_id = $_POST['leave_id'];


    // Agar approve hae to Approved warna Rejected
    $new_status = ($action === 'approve') ? 'Approved' : 'Rejected';

    try {
        // LEAVE DETAILS FETCH
        $stmt_fetch = $conn->prepare("SELECT user_id, start_date, end_date, reason FROM leave_applications WHERE id = :id");
        $stmt_fetch->execute([':id' => $leave_id]);
        $leave_details = $stmt_fetch->fetch(PDO::FETCH_ASSOC);
        // ================================================================================================================
        // ================================================================================================================
        // fetch user email
        $stmt_user = $conn->prepare("SELECT email, user_name FROM users WHERE id = :id");
        $stmt_user->execute([':id' => $leave_details['user_id']]);
        $user_email_row = $stmt_user->fetch(PDO::FETCH_ASSOC);
        $user_email = $user_email_row['email'];
        $employee_name = $user_email_row['user_name'];
        // ================================================================================================================ 
        // ================================================================================================================ 
        if (!$leave_details) {
            $response['message'] = "Leave request not found.";
            echo json_encode($response);
            exit;
        }

        // UPDATE LEAVE STATUS
        $stmt = $conn->prepare("UPDATE leave_applications SET status = :status WHERE id = :id");
        $stmt->execute([
            ':status' => $new_status,
            ':id' => $leave_id
        ]);

        if ($stmt->rowCount() > 0) {
            $response['success'] = true;
            $response['message'] = "Leave successfully marked as {$new_status}.";

            $userId = $leave_details['user_id'];
            $startDate = new DateTime($leave_details['start_date']);
            $endDate = new DateTime($leave_details['end_date']);

            // LOOP THROUGH DATES
            while ($startDate <= $endDate) {

                $currentDate = $startDate->format('Y-m-d');

                // WEEKEND CHECK Saturday Sunday
                $dayName = $startDate->format('l');
                $isWeekend = ($dayName === 'Saturday' || $dayName === 'Sunday');

                // HOLIDAY TABLE CHECK
                $holidayQuery = $conn->prepare("SELECT id FROM holidays WHERE holiday_date = :date");
                $holidayQuery->execute([':date' => $currentDate]);
                $isHoliday = $holidayQuery->rowCount() > 0;

                // STATUS LOGIC
                if ($new_status === 'Approved') {
                    // Leave approved Attendance status "Leave"
                    $attendance_status = 'Leave';
                } else {
                    // Pehle check karo holiday ya weekend hai
                    if ($isHoliday || $isWeekend) {
                        $attendance_status = 'Off Day';
                    } else {
                        // Check attendance entry exists
                        $check_present = $conn->prepare("SELECT check_in FROM attendance WHERE user_id = :user_id AND date = :date");
                        $check_present->execute([':user_id' => $userId, ':date' => $currentDate]);
                        $result = $check_present->fetch(PDO::FETCH_ASSOC);

                        if ($result && $result['check_in'] !== null) {
                            $attendance_status = 'Present';  // reject hua but user aya tha
                        } else {
                            $attendance_status = 'Absent'; // reject no attendance
                        }
                    }
                }

                // INSERT UPDATE RECORD
                $check_att = $conn->prepare("SELECT id FROM attendance WHERE user_id = :user_id AND date = :date");
                $check_att->execute([':user_id' => $userId, ':date' => $currentDate]);

                if ($check_att->rowCount() > 0) {
                    // Record mil gaya Update karo
                    $update_att = $conn->prepare("UPDATE attendance 
                        SET status = :status, check_in = NULL, check_out = NULL, working_hours = NULL
                        WHERE user_id = :user_id AND date = :date
                    ");
                    $update_att->execute([
                        ':status' => $attendance_status,
                        ':user_id' => $userId,
                        ':date' => $currentDate
                    ]);
                } else {
                    // Record nahi mila Insert karo
                    $insert_att = $conn->prepare("INSERT INTO attendance (user_id, date, status)
                        VALUES (:user_id, :date, :status)
                    ");
                    $insert_att->execute([
                        ':user_id' => $userId,
                        ':date' => $currentDate,
                        ':status' => $attendance_status
                    ]);
                }

                $startDate->modify('+1 day'); // Next date
            }


            // ================================================================================================================
            // ================================================================================================================
            // INSERT NOTIFICATION
            $notif_msg = "Your leave from {$leave_details['start_date']} to {$leave_details['end_date']} has been {$new_status}.";
            $notif_stmt = $conn->prepare("INSERT INTO notifications (user_id, message)  VALUES (:uid, :msg) ");
            $notif_stmt->execute([
                ':uid' => $leave_details['user_id'],
                ':msg' => $notif_msg
            ]);
            // ================================================================================================================ 
            // ================================================================================================================ 
            // EMAIL SEND
            $subject = "Leave Reply by Admin";
            $body = "
            <h3>Dear, {$employee_name}</h3>
            <p>Your leave request which a reason is <b>{$leave_details['reason']}</b> and date start from <b>{$leave_details['start_date']}</b> to <b>{$leave_details['end_date']}</b> has been <strong style='color:blue;'>{$new_status}</strong>.</p>
            ";

            sendLeaveEmail($user_email, $subject, $body);
            // ================================================================================================================
            // ================================================================================================================ 
            // PENDING COUNT RETURN
            $stmt_count = $conn->prepare("SELECT COUNT(*) FROM leave_applications WHERE status = 'Pending'");
            $stmt_count->execute();
            $response['pending_count'] = $stmt_count->fetchColumn();
        } else {
            $response['message'] = "Leave request not found or status already set.";
        }
    } catch (PDOException $e) {
        $response['message'] = 'Database Error: ' . $e->getMessage();
    }
} else {
    $response['message'] = 'Invalid request method.';
}

echo json_encode($response);
