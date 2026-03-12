<?php
// payments.php - View and manage payments
session_start();
require_once "config.php";

// Check if the user is logged in
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    header("location: login.php");
    exit;
}

// For patients: Show their payments
if($_SESSION["user_type"] == "patient"){
    $patient_id = $_SESSION["id"];
    
    // Fetch all payments for this patient using multiple JOINs
    $sql = "SELECT p.payment_id, p.amount, p.payment_date, p.payment_method, p.status,
                   a.appointment_datetime, d.name as doctor_name, s.name as specialty
            FROM payments p
            JOIN appointments a ON p.appointment_id = a.appointment_id
            JOIN doctors d ON a.doctor_id = d.doctor_id
            JOIN specialties s ON d.specialty_id = s.specialty_id
            WHERE a.patient_id = ?
            ORDER BY p.payment_date DESC";
    
    if($stmt = mysqli_prepare($conn, $sql)){
        mysqli_stmt_bind_param($stmt, "i", $param_patient_id);
        $param_patient_id = $patient_id;
        
        if(mysqli_stmt_execute($stmt)){
            $result = mysqli_stmt_get_result($stmt);
        } else{
            echo "Oops! Something went wrong. Please try again later.";
        }
        mysqli_stmt_close($stmt);
    }
}

// For doctors: Show payments related to their appointments
if($_SESSION["user_type"] == "doctor"){
    $doctor_id = $_SESSION["id"];
    
    // Fetch all payments for this doctor's appointments
    $sql = "SELECT p.payment_id, p.amount, p.payment_date, p.payment_method, p.status,
                   a.appointment_datetime, pt.name as patient_name
            FROM payments p
            JOIN appointments a ON p.appointment_id = a.appointment_id
            JOIN patients pt ON a.patient_id = pt.patient_id
            WHERE a.doctor_id = ?
            ORDER BY p.payment_date DESC";
    
    if($stmt = mysqli_prepare($conn, $sql)){
        mysqli_stmt_bind_param($stmt, "i", $param_doctor_id);
        $param_doctor_id = $doctor_id;
        
        if(mysqli_stmt_execute($stmt)){
            $result = mysqli_stmt_get_result($stmt);
        } else{
            echo "Oops! Something went wrong. Please try again later.";
        }
        mysqli_stmt_close($stmt);
    }
}

// For admin (you can add this later): Show all payments
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Payment History</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.1.3/css/bootstrap.min.css">
    <style>
        body{ font: 14px sans-serif; padding: 20px; }
    </style>
</head>
<body>
    <div class="container">
        <h2>Payment History</h2>
        
        <?php 
        // Display messages if set
        if(isset($_SESSION["message"]) && isset($_SESSION["message_type"])): 
        ?>
            <div class="alert alert-<?php echo $_SESSION["message_type"]; ?> alert-dismissible fade show" role="alert">
                <?php 
                echo $_SESSION["message"]; 
                // Clear the message after displaying
                unset($_SESSION["message"]);
                unset($_SESSION["message_type"]);
                ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>Payment ID</th>
                    <th>Amount</th>
                    <th>Date</th>
                    <th>Appointment</th>
                    <?php if($_SESSION["user_type"] == "patient"): ?>
                        <th>Doctor</th>
                        <th>Specialty</th>
                    <?php else: ?>
                        <th>Patient</th>
                    <?php endif; ?>
                    <th>Method</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if(isset($result) && mysqli_num_rows($result) > 0): ?>
                    <?php while($row = mysqli_fetch_array($result)): ?>
                        <tr>
                            <td><?php echo $row['payment_id']; ?></td>
                            <td>$<?php echo number_format($row['amount'], 2); ?></td>
                            <td><?php echo date('M d, Y', strtotime($row['payment_date'])); ?></td>
                            <td><?php echo date('M d, Y h:i A', strtotime($row['appointment_datetime'])); ?></td>
                            
                            <?php if($_SESSION["user_type"] == "patient"): ?>
                                <td><?php echo $row['doctor_name']; ?></td>
                                <td><?php echo $row['specialty']; ?></td>
                            <?php else: ?>
                                <td><?php echo $row['patient_name']; ?></td>
                            <?php endif; ?>
                            
                            <td><?php echo $row['payment_method']; ?></td>
                            <td>
                                <?php 
                                if($row['status'] == 'Pending') echo '<span class="badge bg-warning">Pending</span>';
                                elseif($row['status'] == 'Completed') echo '<span class="badge bg-success">Completed</span>';
                                else echo '<span class="badge bg-danger">Cancelled</span>';
                                ?>
                            </td>
                            <td>
                                <a href="view_payment.php?id=<?php echo $row['payment_id']; ?>" class="btn btn-sm btn-info">Details</a>
                                <?php if($row['status'] == 'Pending' && $_SESSION["user_type"] == "patient"): ?>
                                    <a href="make_payment.php?id=<?php echo $row['payment_id']; ?>" class="btn btn-sm btn-success">Pay Now</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="9" class="text-center">No payment records found.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
        
        <p>
            <?php if($_SESSION["user_type"] == "patient"): ?>
                <a href="patient_dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
                <a href="appointments.php" class="btn btn-primary">View Appointments</a>
            <?php else: ?>
                <a href="doctor_dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
                <a href="appointments.php" class="btn btn-primary">View Appointments</a>
            <?php endif; ?>
        </p>
    </div>
    
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.1.3/js/bootstrap.bundle.min.js"></script>
</body>
</html>