<?php
// edit_medical_record.php - Edit patient medical records
session_start();
require_once "config.php";

// Check if the user is logged in as a doctor
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["user_type"] !== "doctor"){
    header("location: login.php");
    exit;
}

$doctor_id = $_SESSION["id"];

// Define variables and initialize with empty values
$diagnosis = $prescription = $notes = "";
$diagnosis_err = $prescription_err = $notes_err = "";
$success_message = "";

// Processing form data when form is submitted
if($_SERVER["REQUEST_METHOD"] == "POST"){
    
    // Check if record_id is in the URL
    if(empty(trim($_POST["record_id"]))){
        header("location: doctor_medical_records.php");
        exit;
    } else {
        $record_id = trim($_POST["record_id"]);
    }
    
    // Validate diagnosis
    if(empty(trim($_POST["diagnosis"]))){
        $diagnosis_err = "Please enter a diagnosis.";
    } else {
        $diagnosis = trim($_POST["diagnosis"]);
    }
    
    // Validate prescription (can be empty)
    $prescription = trim($_POST["prescription"]);
    
    // Validate notes (can be empty)
    $notes = trim($_POST["notes"]);
    
    // Check input errors before updating the record
    if(empty($diagnosis_err)){
        
        // Prepare an update statement
        $sql = "UPDATE medical_records SET diagnosis = ?, prescription = ?, notes = ? WHERE record_id = ? AND doctor_id = ?";
        
        if($stmt = mysqli_prepare($conn, $sql)){
            // Bind variables to the prepared statement as parameters
            mysqli_stmt_bind_param($stmt, "sssii", $param_diagnosis, $param_prescription, $param_notes, $param_record_id, $param_doctor_id);
            
            // Set parameters
            $param_diagnosis = $diagnosis;
            $param_prescription = $prescription;
            $param_notes = $notes;
            $param_record_id = $record_id;
            $param_doctor_id = $doctor_id;
            
            // Attempt to execute the prepared statement
            if(mysqli_stmt_execute($stmt)){
                $success_message = "Medical record updated successfully!";
            } else{
                echo "Oops! Something went wrong. Please try again later.";
            }
            
            // Close statement
            mysqli_stmt_close($stmt);
        }
    }
} else {
    // Check if record_id is in the URL
    if(empty(trim($_GET["id"]))){
        header("location: doctor_medical_records.php");
        exit;
    } else {
        $record_id = trim($_GET["id"]);
    }
    
    // Fetch the medical record data
    $sql = "SELECT mr.record_id, mr.diagnosis, mr.prescription, mr.notes, mr.created_at, 
                   p.name as patient_name, p.patient_id, p.dob, p.gender,
                   a.appointment_id, a.appointment_datetime, a.status
            FROM medical_records mr
            JOIN patients p ON mr.patient_id = p.patient_id
            JOIN appointments a ON mr.appointment_id = a.appointment_id
            WHERE mr.record_id = ? AND mr.doctor_id = ?";
    
    if($stmt = mysqli_prepare($conn, $sql)){
        // Bind variables to the prepared statement as parameters
        mysqli_stmt_bind_param($stmt, "ii", $param_record_id, $param_doctor_id);
        
        // Set parameters
        $param_record_id = $record_id;
        $param_doctor_id = $doctor_id;
        
        // Attempt to execute the prepared statement
        if(mysqli_stmt_execute($stmt)){
            $result = mysqli_stmt_get_result($stmt);
            
            if(mysqli_num_rows($result) == 1){
                // Fetch the record
                $record = mysqli_fetch_array($result);
                
                // Set values to variables
                $diagnosis = $record["diagnosis"];
                $prescription = $record["prescription"];
                $notes = $record["notes"];
                $patient_name = $record["patient_name"];
                $patient_id = $record["patient_id"];
                $dob = $record["dob"];
                $gender = $record["gender"];
                $created_at = $record["created_at"];
                $appointment_id = $record["appointment_id"];
                $appointment_datetime = $record["appointment_datetime"];
                $appointment_status = $record["status"];
            } else{
                // Record not found
                header("location: doctor_medical_records.php");
                exit;
            }
        } else{
            echo "Oops! Something went wrong. Please try again later.";
        }
        
        // Close statement
        mysqli_stmt_close($stmt);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Medical Record - Doctor Portal</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.1.3/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        :root {
            --primary-color: #3498db;
            --secondary-color: #2c3e50;
            --success-color: #2ecc71;
            --warning-color: #f39c12;
            --danger-color: #e74c3c;
            --light-color: #ecf0f1;
            --dark-color: #2c3e50;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f8f9fa;
            color: #333;
            padding-top: 20px;
            padding-bottom: 40px;
        }
        
        .page-header {
            background: linear-gradient(135deg, var(--secondary-color), #34495e);
            color: white;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 25px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        
        .nav-pills .nav-link.active {
            background-color: var(--secondary-color);
        }
        
        .nav-pills .nav-link {
            color: var(--secondary-color);
            border-radius: 5px;
            padding: 10px 15px;
            margin: 5px 0;
            transition: all 0.3s ease;
        }
        
        .nav-pills .nav-link:hover {
            background-color: rgba(44, 62, 80, 0.1);
        }
        
        .nav-icon {
            margin-right: 10px;
            width: 20px;
            text-align: center;
        }
        
        .card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.08);
            transition: transform 0.3s ease;
            margin-bottom: 20px;
            overflow: hidden;
        }
        
        .card:hover {
            transform: translateY(-5px);
        }
        
        .card-header {
            font-weight: 600;
            background-color: rgba(44, 62, 80, 0.1);
            border-bottom: none;
            padding: 15px 20px;
        }
        
        .alert-success {
            background-color: #d4edda;
            border-color: #c3e6cb;
            color: #155724;
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }
        
        .btn-primary:hover {
            background-color: #2980b9;
            border-color: #2980b9;
        }
        
        .btn-success {
            background-color: var(--success-color);
            border-color: var(--success-color);
        }
        
        .btn-success:hover {
            background-color: #27ae60;
            border-color: #27ae60;
        }
        
        .patient-info {
            background-color: rgba(52, 152, 219, 0.1);
            border-left: 4px solid var(--primary-color);
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        
        .record-timestamp {
            color: #6c757d;
            font-size: 0.9rem;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Page Header -->
        <div class="page-header">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <h1><i class="fas fa-edit me-3"></i>Edit Medical Record</h1>
                    <p class="mb-0">Update patient medical information</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <a href="doctor_medical_records.php" class="btn btn-light">
                        <i class="fas fa-arrow-left me-2"></i>Back to Records
                    </a>
                    <a href="doctor_dashboard.php" class="btn btn-light ms-2">
                        <i class="fas fa-home me-2"></i>Dashboard
                    </a>
                </div>
            </div>
        </div>
        
        <div class="row">
            <!-- Left Sidebar - Navigation -->
            <div class="col-lg-3 mb-4">
                <div class="card">
                    <div class="card-header">
                        <i class="fas fa-th-large me-2"></i> Navigation
                    </div>
                    <div class="card-body p-2">
                        <div class="nav flex-column nav-pills">
                            <a class="nav-link" href="doctor_dashboard.php">
                                <i class="fas fa-home nav-icon"></i> Dashboard
                            </a>
                            <a class="nav-link" href="doctor_schedule.php">
                                <i class="fas fa-calendar-week nav-icon"></i> My Schedule
                            </a>
                            <a class="nav-link" href="appointments.php">
                                <i class="fas fa-calendar-check nav-icon"></i> Appointments
                            </a>
                            <a class="nav-link active" href="doctor_medical_records.php">
                                <i class="fas fa-file-medical nav-icon"></i> Medical Records
                            </a>
                        </div>
                    </div>
                </div>
                
                <div class="card mt-4">
                    <div class="card-header">
                        <i class="fas fa-info-circle me-2"></i> Record Information
                    </div>
                    <div class="card-body">
                        <p><strong>Record ID:</strong> #<?php echo $record_id; ?></p>
                        <p><strong>Created on:</strong> <?php echo date('M d, Y h:i A', strtotime($created_at)); ?></p>
                        <p><strong>Appointment:</strong> <?php echo date('M d, Y h:i A', strtotime($appointment_datetime)); ?></p>
                        <p>
                            <strong>Status:</strong> 
                            <?php 
                            if($appointment_status == 'Scheduled') echo '<span class="badge bg-primary">Scheduled</span>';
                            elseif($appointment_status == 'Completed') echo '<span class="badge bg-success">Completed</span>';
                            else echo '<span class="badge bg-danger">Cancelled</span>';
                            ?>
                        </p>
                        
                        <hr>
                        
                        <?php if($appointment_status == 'Scheduled'): ?>
                            <a href="complete_appointment.php?id=<?php echo $appointment_id; ?>" class="btn btn-success w-100">
                                <i class="fas fa-check-circle me-2"></i>Complete Appointment
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Main Content -->
            <div class="col-lg-9">
                <?php if(!empty($success_message)): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="fas fa-check-circle me-2"></i><?php echo $success_message; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>
                
                <div class="card">
                    <div class="card-header">
                        <i class="fas fa-user-edit me-2"></i>Edit Medical Record
                    </div>
                    <div class="card-body">
                        <!-- Patient Info Section -->
                        <div class="patient-info mb-4">
                            <div class="row">
                                <div class="col-md-6">
                                    <h5><?php echo htmlspecialchars($patient_name); ?></h5>
                                    <p class="mb-0">
                                        <span class="badge bg-secondary me-2"><?php echo date_diff(date_create($dob), date_create('now'))->y; ?> years</span>
                                        <span class="badge bg-secondary"><?php echo htmlspecialchars($gender); ?></span>
                                    </p>
                                </div>
                                <div class="col-md-6 text-md-end">
                                    <a href="view_patient_details.php?id=<?php echo $patient_id; ?>" class="btn btn-sm btn-outline-primary">
                                        <i class="fas fa-user me-1"></i> View Patient Profile
                                    </a>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Medical Record Form -->
                        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                            <input type="hidden" name="record_id" value="<?php echo $record_id; ?>">
                            
                            <div class="mb-3">
                                <label for="diagnosis" class="form-label fw-bold">Diagnosis <span class="text-danger">*</span></label>
                                <textarea name="diagnosis" id="diagnosis" class="form-control <?php echo (!empty($diagnosis_err)) ? 'is-invalid' : ''; ?>" rows="3" required><?php echo htmlspecialchars($diagnosis); ?></textarea>
                                <span class="invalid-feedback"><?php echo $diagnosis_err; ?></span>
                            </div>
                            
                            <div class="mb-3">
                                <label for="prescription" class="form-label fw-bold">Prescription</label>
                                <textarea name="prescription" id="prescription" class="form-control" rows="5"><?php echo htmlspecialchars($prescription); ?></textarea>
                                <small class="text-muted">Include medication name, dosage, frequency, and duration.</small>
                            </div>
                            
                            <div class="mb-3">
                                <label for="notes" class="form-label fw-bold">Additional Notes</label>
                                <textarea name="notes" id="notes" class="form-control" rows="5"><?php echo htmlspecialchars($notes); ?></textarea>
                                <small class="text-muted">Include any additional observations, follow-up recommendations, or special instructions.</small>
                            </div>
                            
                            <div class="d-flex justify-content-between mt-4">
                                <a href="doctor_medical_records.php" class="btn btn-secondary">
                                    <i class="fas fa-times me-2"></i>Cancel
                                </a>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-2"></i>Save Changes
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.1.3/js/bootstrap.bundle.min.js"></script>
</body>
</html>