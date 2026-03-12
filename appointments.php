<?php
// appointments.php - List and manage appointments
session_start();
require_once "config.php";

// Check if the user is logged in
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    header("location: login.php");
    exit;
}

// For patients: Show their appointments
if($_SESSION["user_type"] == "patient"){
    $patient_id = $_SESSION["id"];
    
    // Fetch all appointments for this patient using JOIN
    $sql = "SELECT a.appointment_id, a.appointment_datetime, a.status, 
                   d.name as doctor_name, s.name as specialty 
            FROM appointments a
            JOIN doctors d ON a.doctor_id = d.doctor_id
            JOIN specialties s ON d.specialty_id = s.specialty_id
            WHERE a.patient_id = ?
            ORDER BY a.appointment_datetime DESC";
    
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

// For doctors: Show their appointments
if($_SESSION["user_type"] == "doctor"){
    $doctor_id = $_SESSION["id"];
    
    // Fetch all appointments for this doctor using JOIN
    $sql = "SELECT a.appointment_id, a.appointment_datetime, a.status, 
                   p.name as patient_name, p.dob, p.gender 
            FROM appointments a
            JOIN patients p ON a.patient_id = p.patient_id
            WHERE a.doctor_id = ?
            ORDER BY a.appointment_datetime";
    
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
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Appointments</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.1.3/css/bootstrap.min.css">
    <style>
        body{ font: 14px sans-serif; padding: 20px; }
    </style>
</head>
<body>
    <div class="container">
        <h2>My Appointments</h2>
        
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
        
        <?php if($_SESSION["user_type"] == "patient"): ?>
            <p>Book a new appointment: <a href="book_appointment.php" class="btn btn-primary">Book Now</a></p>
        <?php endif; ?>
        
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>Appointment ID</th>
                    <th>Date & Time</th>
                    <?php if($_SESSION["user_type"] == "patient"): ?>
                        <th>Doctor</th>
                        <th>Specialty</th>
                    <?php else: ?>
                        <th>Patient</th>
                        <th>Age</th>
                        <th>Gender</th>
                    <?php endif; ?>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if(isset($result) && mysqli_num_rows($result) > 0): ?>
                    <?php while($row = mysqli_fetch_array($result)): ?>
                        <tr>
                            <td><?php echo $row['appointment_id']; ?></td>
                            <td><?php echo date('M d, Y h:i A', strtotime($row['appointment_datetime'])); ?></td>
                            
                            <?php if($_SESSION["user_type"] == "patient"): ?>
                                <td><?php echo $row['doctor_name']; ?></td>
                                <td><?php echo $row['specialty']; ?></td>
                            <?php else: ?>
                                <td><?php echo $row['patient_name']; ?></td>
                                <td><?php echo date_diff(date_create($row['dob']), date_create('now'))->y; ?> years</td>
                                <td><?php echo $row['gender']; ?></td>
                            <?php endif; ?>
                            
                            <td>
                                <?php 
                                if($row['status'] == 'Scheduled') echo '<span class="badge bg-primary">Scheduled</span>';
                                elseif($row['status'] == 'Completed') echo '<span class="badge bg-success">Completed</span>';
                                else echo '<span class="badge bg-danger">Cancelled</span>';
                                ?>
                            </td>
                            <td>
                                <?php if($row['status'] == 'Scheduled'): ?>
                                    <a href="view_appointment.php?id=<?php echo $row['appointment_id']; ?>" class="btn btn-sm btn-info">View</a>
                                    <?php if($_SESSION["user_type"] == "patient"): ?>
                                        <a href="cancel_appointment.php?id=<?php echo $row['appointment_id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to cancel this appointment?')">Cancel</a>
                                    <?php else: ?>
                                        <a href="complete_appointment.php?id=<?php echo $row['appointment_id']; ?>" class="btn btn-sm btn-success">Complete</a>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <a href="view_appointment.php?id=<?php echo $row['appointment_id']; ?>" class="btn btn-sm btn-info">View</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" class="text-center">No appointments found.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
        
        <p>
            <?php if($_SESSION["user_type"] == "patient"): ?>
                <a href="patient_dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
                <a href="payments.php" class="btn btn-primary">View Payment History</a>
            <?php else: ?>
                <a href="doctor_dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
            <?php endif; ?>
        </p>
    </div>
    
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.1.3/js/bootstrap.bundle.min.js"></script>
</body>
</html>