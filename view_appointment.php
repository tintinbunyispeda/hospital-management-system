<?php
// view_appointment.php - View details of a specific appointment
session_start();
require_once "config.php";

// Check if the user is logged in
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    header("location: login.php");
    exit;
}

// Check if appointment ID is provided
if(!isset($_GET["id"]) || empty($_GET["id"])){
    header("location: appointments.php");
    exit;
}

$appointment_id = $_GET["id"];
$user_type = $_SESSION["user_type"];
$user_id = $_SESSION["id"];

// Fetch the appointment details with JOINs for comprehensive data
// Removed 'a.reason' from the query as it doesn't exist in the database
$sql = "SELECT a.appointment_id, a.appointment_datetime, a.status, a.notes,
               p.patient_id, p.name as patient_name, p.email as patient_email, p.phone as patient_phone, p.dob as patient_dob, p.gender as patient_gender,
               d.doctor_id, d.name as doctor_name, d.email as doctor_email, d.phone as doctor_phone, d.room_number,
               s.name as specialty
        FROM appointments a
        JOIN patients p ON a.patient_id = p.patient_id
        JOIN doctors d ON a.doctor_id = d.doctor_id
        JOIN specialties s ON d.specialty_id = s.specialty_id
        WHERE a.appointment_id = ?";

// Add security check based on user type
if($user_type == "patient"){
    $sql .= " AND a.patient_id = ?";
} elseif($user_type == "doctor"){
    $sql .= " AND a.doctor_id = ?";
}

if($stmt = mysqli_prepare($conn, $sql)){
    if($user_type == "patient" || $user_type == "doctor"){
        mysqli_stmt_bind_param($stmt, "ii", $appointment_id, $user_id);
    } else {
        mysqli_stmt_bind_param($stmt, "i", $appointment_id);
    }
    
    if(mysqli_stmt_execute($stmt)){
        $result = mysqli_stmt_get_result($stmt);
        
        if(mysqli_num_rows($result) == 1){
            $appointment = mysqli_fetch_array($result);
        } else {
            // Appointment not found or not authorized
            header("location: appointments.php");
            exit;
        }
    } else{
        echo "Oops! Something went wrong. Please try again later.";
    }
    mysqli_stmt_close($stmt);
}

// Check for associated payment
$payment = null;
$sql = "SELECT payment_id, amount, payment_date, payment_method, status 
        FROM payments 
        WHERE appointment_id = ?";

if($stmt = mysqli_prepare($conn, $sql)){
    mysqli_stmt_bind_param($stmt, "i", $appointment_id);
    
    if(mysqli_stmt_execute($stmt)){
        $payment_result = mysqli_stmt_get_result($stmt);
        
        if(mysqli_num_rows($payment_result) == 1){
            $payment = mysqli_fetch_array($payment_result);
        }
    }
    mysqli_stmt_close($stmt);
}

// Check for medical records
$medical_record = null;
$sql = "SELECT record_id, diagnosis, treatment, prescription, notes
        FROM medical_records
        WHERE appointment_id = ?";

if($stmt = mysqli_prepare($conn, $sql)){
    mysqli_stmt_bind_param($stmt, "i", $appointment_id);
    
    if(mysqli_stmt_execute($stmt)){
        $record_result = mysqli_stmt_get_result($stmt);
        
        if(mysqli_num_rows($record_result) == 1){
            $medical_record = mysqli_fetch_array($record_result);
        }
    }
    mysqli_stmt_close($stmt);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>View Appointment</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.1.3/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        body{ font: 14px sans-serif; padding: 20px; }
        .appointment-details { background-color: #f8f9fa; padding: 20px; border-radius: 10px; }
        .section-header { border-bottom: 1px solid #dee2e6; padding-bottom: 10px; margin-bottom: 20px; }
        .info-item { margin-bottom: 15px; }
        .label { font-weight: bold; color: #495057; }
        .status-badge { font-size: 1rem; padding: 5px 10px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="row mb-4">
            <div class="col">
                <h2><i class="fas fa-calendar-check me-2"></i>Appointment Details</h2>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="<?php echo $user_type == 'patient' ? 'patient_dashboard.php' : 'doctor_dashboard.php'; ?>">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="appointments.php">Appointments</a></li>
                        <li class="breadcrumb-item active" aria-current="page">View Appointment</li>
                    </ol>
                </nav>
            </div>
        </div>
        
        <div class="row">
            <div class="col-md-8">
                <div class="card appointment-details mb-4">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center section-header">
                            <h3>Appointment #<?php echo $appointment['appointment_id']; ?></h3>
                            <span class="status-badge badge <?php 
                                if($appointment['status'] == 'Scheduled') echo 'bg-primary';
                                elseif($appointment['status'] == 'Completed') echo 'bg-success';
                                else echo 'bg-danger';
                            ?>">
                                <?php echo $appointment['status']; ?>
                            </span>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="info-item">
                                    <div class="label"><i class="fas fa-calendar me-2"></i>Date</div>
                                    <div><?php echo date('l, F d, Y', strtotime($appointment['appointment_datetime'])); ?></div>
                                </div>
                                
                                <div class="info-item">
                                    <div class="label"><i class="fas fa-clock me-2"></i>Time</div>
                                    <div><?php echo date('h:i A', strtotime($appointment['appointment_datetime'])); ?></div>
                                </div>
                                
                                <?php if($user_type == 'patient'): ?>
                                <div class="info-item">
                                    <div class="label"><i class="fas fa-user-md me-2"></i>Doctor</div>
                                    <div><?php echo htmlspecialchars($appointment['doctor_name']); ?></div>
                                </div>
                                
                                <div class="info-item">
                                    <div class="label"><i class="fas fa-stethoscope me-2"></i>Specialty</div>
                                    <div><?php echo htmlspecialchars($appointment['specialty']); ?></div>
                                </div>
                                
                                <div class="info-item">
                                    <div class="label"><i class="fas fa-door-open me-2"></i>Room</div>
                                    <div><?php echo htmlspecialchars($appointment['room_number']); ?></div>
                                </div>
                                <?php endif; ?>
                                
                                <?php if($user_type == 'doctor'): ?>
                                <div class="info-item">
                                    <div class="label"><i class="fas fa-user me-2"></i>Patient</div>
                                    <div><?php echo htmlspecialchars($appointment['patient_name']); ?></div>
                                </div>
                                
                                <div class="info-item">
                                    <div class="label"><i class="fas fa-birthday-cake me-2"></i>Age</div>
                                    <div><?php echo date_diff(date_create($appointment['patient_dob']), date_create('now'))->y; ?> years</div>
                                </div>
                                
                                <div class="info-item">
                                    <div class="label"><i class="fas fa-venus-mars me-2"></i>Gender</div>
                                    <div><?php echo htmlspecialchars($appointment['patient_gender']); ?></div>
                                </div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="col-md-6">
                                <?php if($user_type == 'doctor'): ?>
                                <div class="info-item">
                                    <div class="label"><i class="fas fa-phone me-2"></i>Contact</div>
                                    <div><?php echo htmlspecialchars($appointment['patient_phone']); ?></div>
                                </div>
                                
                                <div class="info-item">
                                    <div class="label"><i class="fas fa-envelope me-2"></i>Email</div>
                                    <div><?php echo htmlspecialchars($appointment['patient_email']); ?></div>
                                </div>
                                <?php endif; ?>
                                
                                <?php if($user_type == 'patient'): ?>
                                <div class="info-item">
                                    <div class="label"><i class="fas fa-phone me-2"></i>Doctor Contact</div>
                                    <div><?php echo htmlspecialchars($appointment['doctor_phone']); ?></div>
                                </div>
                                
                                <div class="info-item">
                                    <div class="label"><i class="fas fa-envelope me-2"></i>Doctor Email</div>
                                    <div><?php echo htmlspecialchars($appointment['doctor_email']); ?></div>
                                </div>
                                <?php endif; ?>
                                
                                <!-- Removed the "Reason for Visit" section as there's no 'reason' column -->
                                
                                <?php if(!empty($appointment['notes'])): ?>
                                <div class="info-item">
                                    <div class="label"><i class="fas fa-sticky-note me-2"></i>Additional Notes</div>
                                    <div><?php echo htmlspecialchars($appointment['notes']); ?></div>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                
                <?php if($medical_record): ?>
                <div class="card mb-4">
                    <div class="card-header bg-info text-white">
                        <h4><i class="fas fa-file-medical me-2"></i>Medical Record</h4>
                    </div>
                    <div class="card-body">
                        <div class="info-item">
                            <div class="label">Diagnosis</div>
                            <div><?php echo htmlspecialchars($medical_record['diagnosis']); ?></div>
                        </div>
                        
                        <div class="info-item">
                            <div class="label">Treatment</div>
                            <div><?php echo htmlspecialchars($medical_record['treatment']); ?></div>
                        </div>
                        
                        <?php if(!empty($medical_record['prescription'])): ?>
                        <div class="info-item">
                            <div class="label">Prescription</div>
                            <div><?php echo htmlspecialchars($medical_record['prescription']); ?></div>
                        </div>
                        <?php endif; ?>
                        
                        <?php if(!empty($medical_record['notes'])): ?>
                        <div class="info-item">
                            <div class="label">Doctor's Notes</div>
                            <div><?php echo htmlspecialchars($medical_record['notes']); ?></div>
                        </div>
                        <?php endif; ?>
                        
                        <div class="text-end mt-3">
                            <a href="view_medical_record.php?id=<?php echo $medical_record['record_id']; ?>" class="btn btn-info">
                                <i class="fas fa-eye me-1"></i> View Full Record
                            </a>
                            
                            <?php if($user_type == 'doctor' && $appointment['status'] == 'Completed'): ?>
                            <a href="edit_medical_record.php?id=<?php echo $medical_record['record_id']; ?>" class="btn btn-primary">
                                <i class="fas fa-edit me-1"></i> Edit Record
                            </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
            
            <div class="col-md-4">
                <!-- Action Panel -->
                <div class="card mb-4">
                    <div class="card-header bg-primary text-white">
                        <h4><i class="fas fa-cogs me-2"></i>Actions</h4>
                    </div>
                    <div class="card-body">
                        <?php if($appointment['status'] == 'Scheduled'): ?>
                            <?php if($user_type == 'patient'): ?>
                                <a href="cancel_appointment.php?id=<?php echo $appointment['appointment_id']; ?>" class="btn btn-danger w-100 mb-3" onclick="return confirm('Are you sure you want to cancel this appointment?')">
                                    <i class="fas fa-times-circle me-2"></i>Cancel Appointment
                                </a>
                                <a href="edit_appointment.php?id=<?php echo $appointment['appointment_id']; ?>" class="btn btn-warning w-100 mb-3">
                                    <i class="fas fa-edit me-2"></i>Reschedule Appointment
                                </a>
                            <?php endif; ?>
                            
                            <?php if($user_type == 'doctor'): ?>
                                <a href="complete_appointment.php?id=<?php echo $appointment['appointment_id']; ?>" class="btn btn-success w-100 mb-3">
                                    <i class="fas fa-check-circle me-2"></i>Complete Appointment
                                </a>
                                <?php if(!$medical_record): ?>
                                    <a href="add_record.php?appointment_id=<?php echo $appointment['appointment_id']; ?>" class="btn btn-primary w-100 mb-3">
                                        <i class="fas fa-file-medical me-2"></i>Add Medical Record
                                    </a>
                                <?php endif; ?>
                            <?php endif; ?>
                        <?php endif; ?>
                        
                        <a href="appointments.php" class="btn btn-secondary w-100">
                            <i class="fas fa-arrow-left me-2"></i>Back to Appointments
                        </a>
                    </div>
                </div>
                
                <!-- Payment Information -->
                <?php if($payment): ?>
                <div class="card mb-4">
                    <div class="card-header bg-success text-white">
                        <h4><i class="fas fa-credit-card me-2"></i>Payment Details</h4>
                    </div>
                    <div class="card-body">
                        <div class="info-item">
                            <div class="label">Payment ID</div>
                            <div>#<?php echo $payment['payment_id']; ?></div>
                        </div>
                        
                        <div class="info-item">
                            <div class="label">Amount</div>
                            <div class="h4">$<?php echo number_format($payment['amount'], 2); ?></div>
                        </div>
                        
                        <div class="info-item">
                            <div class="label">Status</div>
                            <div>
                                <span class="badge <?php 
                                    if($payment['status'] == 'Completed') echo 'bg-success';
                                    elseif($payment['status'] == 'Pending') echo 'bg-warning';
                                    else echo 'bg-danger';
                                ?>">
                                    <?php echo $payment['status']; ?>
                                </span>
                            </div>
                        </div>
                        
                        <?php if(!empty($payment['payment_date']) && $payment['status'] == 'Completed'): ?>
                        <div class="info-item">
                            <div class="label">Payment Date</div>
                            <div><?php echo date('M d, Y', strtotime($payment['payment_date'])); ?></div>
                        </div>
                        
                        <div class="info-item">
                            <div class="label">Payment Method</div>
                            <div><?php echo htmlspecialchars($payment['payment_method']); ?></div>
                        </div>
                        <?php endif; ?>
                        
                        <div class="text-center mt-3">
                            <a href="view_payment.php?id=<?php echo $payment['payment_id']; ?>" class="btn btn-info">
                                <i class="fas fa-eye me-1"></i> View Details
                            </a>
                            
                            <?php if($payment['status'] == 'Pending' && $user_type == 'patient'): ?>
                            <a href="make_payment.php?id=<?php echo $payment['payment_id']; ?>" class="btn btn-success">
                                <i class="fas fa-money-bill-wave me-1"></i> Pay Now
                            </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.1.3/js/bootstrap.bundle.min.js"></script>
</body>
</html>