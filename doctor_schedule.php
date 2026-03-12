<?php
// doctor_schedule.php - Doctor schedule view
session_start();
require_once "config.php";

// Check if the user is logged in as a doctor
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["user_type"] !== "doctor"){
    header("location: login.php");
    exit;
}

$doctor_id = $_SESSION["id"];

// Get doctor information
$sql = "SELECT d.name, d.room_number, s.name as specialty 
        FROM doctors d
        JOIN specialties s ON d.specialty_id = s.specialty_id
        WHERE d.doctor_id = ?";

if($stmt = mysqli_prepare($conn, $sql)){
    mysqli_stmt_bind_param($stmt, "i", $param_doctor_id);
    $param_doctor_id = $doctor_id;
    
    if(mysqli_stmt_execute($stmt)){
        $result = mysqli_stmt_get_result($stmt);
        if(mysqli_num_rows($result) == 1){
            $doctor_info = mysqli_fetch_array($result);
        }
    }
    mysqli_stmt_close($stmt);
}

// Get upcoming appointments (today and future)
$today = date('Y-m-d');
$sql = "SELECT a.appointment_id, a.appointment_datetime, a.status, 
               p.name as patient_name, p.dob, p.gender, p.phone
        FROM appointments a
        JOIN patients p ON a.patient_id = p.patient_id
        WHERE a.doctor_id = ? AND DATE(a.appointment_datetime) >= ?
        ORDER BY a.appointment_datetime";

if($stmt = mysqli_prepare($conn, $sql)){
    mysqli_stmt_bind_param($stmt, "is", $param_doctor_id, $param_today);
    $param_doctor_id = $doctor_id;
    $param_today = $today;
    
    if(mysqli_stmt_execute($stmt)){
        $appointments = mysqli_stmt_get_result($stmt);
    }
    mysqli_stmt_close($stmt);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Doctor Schedule</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.1.3/css/bootstrap.min.css">
    <style>
        body{ font: 14px sans-serif; padding: 20px; }
    </style>
</head>
<body>
    <div class="container">
        <h2>My Schedule</h2>
        
        <?php if(isset($doctor_info)): ?>
        <div class="card mb-4">
            <div class="card-body">
                <h4><?php echo $doctor_info['name']; ?></h4>
                <p><strong>Specialty:</strong> <?php echo $doctor_info['specialty']; ?></p>
                <p><strong>Room Number:</strong> <?php echo $doctor_info['room_number']; ?></p>
            </div>
        </div>
        <?php endif; ?>
        
        <h3>Upcoming Appointments</h3>
        
        <?php
        // Group appointments by date
        $appointments_by_date = [];
        if(isset($appointments) && mysqli_num_rows($appointments) > 0){
            while($row = mysqli_fetch_array($appointments)){
                $date = date('Y-m-d', strtotime($row['appointment_datetime']));
                if(!isset($appointments_by_date[$date])){
                    $appointments_by_date[$date] = [];
                }
                $appointments_by_date[$date][] = $row;
            }
        }
        ?>
        
        <?php if(!empty($appointments_by_date)): ?>
            <?php foreach($appointments_by_date as $date => $daily_appointments): ?>
                <div class="card mb-3">
                    <div class="card-header bg-primary text-white">
                        <h5><?php echo date('l, F d, Y', strtotime($date)); ?></h5>
                    </div>
                    <div class="card-body">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Time</th>
                                    <th>Patient</th>
                                    <th>Age</th>
                                    <th>Gender</th>
                                    <th>Contact</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($daily_appointments as $appointment): ?>
                                    <tr>
                                        <td><?php echo date('h:i A', strtotime($appointment['appointment_datetime'])); ?></td>
                                        <td><?php echo $appointment['patient_name']; ?></td>
                                        <td><?php echo date_diff(date_create($appointment['dob']), date_create('now'))->y; ?> years</td>
                                        <td><?php echo $appointment['gender']; ?></td>
                                        <td><?php echo $appointment['phone']; ?></td>
                                        <td>
                                            <?php 
                                            if($appointment['status'] == 'Scheduled') echo '<span class="badge bg-primary">Scheduled</span>';
                                            elseif($appointment['status'] == 'Completed') echo '<span class="badge bg-success">Completed</span>';
                                            else echo '<span class="badge bg-danger">Cancelled</span>';
                                            ?>
                                        </td>
                                        <td>
                                            <a href="view_patient.php?id=<?php echo $appointment['appointment_id']; ?>" class="btn btn-sm btn-info">View Patient</a>
                                            <?php if($appointment['status'] == 'Scheduled'): ?>
                                                <a href="complete_appointment.php?id=<?php echo $appointment['appointment_id']; ?>" class="btn btn-sm btn-success">Complete</a>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="alert alert-info">No upcoming appointments scheduled.</div>
        <?php endif; ?>
        
        <p><a href="doctor_dashboard.php" class="btn btn-secondary">Back to Dashboard</a></p>
    </div>
</body>
</html>