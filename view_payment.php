<?php
// view_payment.php - View payment details
session_start();
require_once "config.php";

// Check if the user is logged in
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    header("location: login.php");
    exit;
}

// Check if payment ID is provided
if(!isset($_GET["id"]) || empty($_GET["id"])){
    header("location: payments.php");
    exit;
}

$payment_id = $_GET["id"];
$user_id = $_SESSION["id"];
$user_type = $_SESSION["user_type"];

// For patients: Verify this payment belongs to them
if($user_type == "patient"){
    $sql = "SELECT p.*, a.appointment_id, a.appointment_datetime, a.notes,
                   d.name as doctor_name, s.name as specialty, a.patient_id
            FROM payments p
            JOIN appointments a ON p.appointment_id = a.appointment_id
            JOIN doctors d ON a.doctor_id = d.doctor_id
            JOIN specialties s ON d.specialty_id = s.specialty_id
            WHERE p.payment_id = ? AND a.patient_id = ?";
    
    if($stmt = mysqli_prepare($conn, $sql)){
        mysqli_stmt_bind_param($stmt, "ii", $param_payment_id, $param_patient_id);
        $param_payment_id = $payment_id;
        $param_patient_id = $user_id;
        
        if(mysqli_stmt_execute($stmt)){
            $result = mysqli_stmt_get_result($stmt);
            
            if(mysqli_num_rows($result) == 1){
                $payment = mysqli_fetch_array($result);
            } else {
                $_SESSION["message"] = "Invalid payment or you don't have permission to view it.";
                $_SESSION["message_type"] = "danger";
                header("location: payments.php");
                exit;
            }
        } else {
            echo "Oops! Something went wrong. Please try again later.";
            exit;
        }
        mysqli_stmt_close($stmt);
    }
}

// For admin access, you can add this later
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Payment Details</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.1.3/css/bootstrap.min.css">
    <style>
        body{ font: 14px sans-serif; padding: 20px; }
    </style>
</head>
<body>
    <div class="container">
        <h2>Payment Details</h2>
        
        <?php if(isset($payment)): ?>
            <div class="card mb-4">
                <div class="card-header">
                    Payment #<?php echo $payment['payment_id']; ?>
                    <?php 
                    if($payment['status'] == 'Pending') echo '<span class="badge bg-warning float-end">Pending</span>';
                    elseif($payment['status'] == 'Completed') echo '<span class="badge bg-success float-end">Completed</span>';
                    else echo '<span class="badge bg-danger float-end">Cancelled</span>';
                    ?>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h5>Payment Information</h5>
                            <p><strong>Amount:</strong> $<?php echo number_format($payment['amount'], 2); ?></p>
                            <p><strong>Date:</strong> <?php echo date('M d, Y', strtotime($payment['payment_date'])); ?></p>
                            <p><strong>Method:</strong> <?php echo $payment['payment_method']; ?></p>
                            <p><strong>Status:</strong> <?php echo $payment['status']; ?></p>
                        </div>
                        <div class="col-md-6">
                            <h5>Appointment Information</h5>
                            <p><strong>Appointment Date:</strong> <?php echo date('M d, Y h:i A', strtotime($payment['appointment_datetime'])); ?></p>
                            <p><strong>Doctor:</strong> <?php echo $payment['doctor_name']; ?></p>
                            <p><strong>Specialty:</strong> <?php echo $payment['specialty']; ?></p>
                        </div>
                    </div>
                    
                    <?php if(isset($payment['notes']) && !empty($payment['notes'])): ?>
                        <div class="mt-4">
                            <h5>Appointment Notes</h5>
                            <p><?php echo nl2br(htmlspecialchars($payment['notes'])); ?></p>
                        </div>
                    <?php endif; ?>
                    
                    <?php if($payment['status'] == 'Pending'): ?>
                        <div class="mt-4">
                            <a href="make_payment.php?id=<?php echo $payment['payment_id']; ?>" class="btn btn-success">Make Payment</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php else: ?>
            <div class="alert alert-danger">Payment details not found.</div>
        <?php endif; ?>
        
        <p><a href="payments.php" class="btn btn-secondary">Back to Payments</a></p>
    </div>
</body>
</html>