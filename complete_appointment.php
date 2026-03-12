<?php
// complete_appointment.php - Mark an appointment as completed and create a payment record
session_start();
require_once "config.php";

// Check if the user is logged in and is a doctor
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["user_type"] != "doctor"){
    header("location: login.php");
    exit;
}

// Check if appointment ID is provided
if(!isset($_GET["id"]) || empty($_GET["id"])){
    header("location: appointments.php");
    exit;
}

$appointment_id = $_GET["id"];
$doctor_id = $_SESSION["id"];

// Verify this appointment belongs to the logged-in doctor
$sql = "SELECT a.*, p.name as patient_name, p.dob, p.gender FROM appointments a 
        JOIN patients p ON a.patient_id = p.patient_id 
        WHERE a.appointment_id = ? AND a.doctor_id = ? AND a.status = 'Scheduled'";

if($stmt = mysqli_prepare($conn, $sql)){
    mysqli_stmt_bind_param($stmt, "ii", $param_appointment_id, $param_doctor_id);
    $param_appointment_id = $appointment_id;
    $param_doctor_id = $doctor_id;
    
    if(mysqli_stmt_execute($stmt)){
        $result = mysqli_stmt_get_result($stmt);
        
        if(mysqli_num_rows($result) == 1){
            $appointment = mysqli_fetch_array($result);
            $patient_id = $appointment["patient_id"];
            $patient_name = $appointment["patient_name"];
        } else {
            // No valid appointment found
            $_SESSION["message"] = "Invalid appointment or you don't have permission to complete it.";
            $_SESSION["message_type"] = "danger";
            header("location: appointments.php");
            exit;
        }
    } else {
        echo "Oops! Something went wrong. Please try again later.";
        exit;
    }
    mysqli_stmt_close($stmt);
}

// Process form submission
if($_SERVER["REQUEST_METHOD"] == "POST"){
    // Update appointment status to completed
    $update_sql = "UPDATE appointments SET status = 'Completed' WHERE appointment_id = ?";
    
    if($update_stmt = mysqli_prepare($conn, $update_sql)){
        mysqli_stmt_bind_param($update_stmt, "i", $param_appointment_id);
        $param_appointment_id = $appointment_id;
        
        if(mysqli_stmt_execute($update_stmt)){
            // Now create a payment record
            $payment_sql = "INSERT INTO payments (appointment_id, amount, payment_date, payment_method, status) 
                           VALUES (?, ?, NOW(), 'Not Specified', 'Pending')";
            
            if($payment_stmt = mysqli_prepare($conn, $payment_sql)){
                // Set standard consultation fee - in a real system, this could be dynamic based on specialty, doctor, etc.
                $consultation_fee = 100.00; 
                
                mysqli_stmt_bind_param($payment_stmt, "id", $param_appointment_id, $param_amount);
                $param_appointment_id = $appointment_id;
                $param_amount = $consultation_fee;
                
                if(mysqli_stmt_execute($payment_stmt)){
                    // Check if medical record information was provided
                    if(!empty(trim($_POST["diagnosis"]))){
                        // Create medical record
                        $record_sql = "INSERT INTO medical_records (patient_id, doctor_id, appointment_id, diagnosis, prescription, notes) 
                                      VALUES (?, ?, ?, ?, ?, ?)";
                        
                        if($record_stmt = mysqli_prepare($conn, $record_sql)){
                            mysqli_stmt_bind_param($record_stmt, "iiisss", 
                                $param_patient_id, 
                                $param_doctor_id, 
                                $param_appointment_id, 
                                $param_diagnosis, 
                                $param_prescription, 
                                $param_notes
                            );
                            
                            $param_patient_id = $patient_id;
                            $param_doctor_id = $doctor_id;
                            $param_appointment_id = $appointment_id;
                            $param_diagnosis = trim($_POST["diagnosis"]);
                            $param_prescription = trim($_POST["prescription"]);
                            $param_notes = trim($_POST["notes"]);
                            
                            if(mysqli_stmt_execute($record_stmt)){
                                $medical_record_id = mysqli_insert_id($conn);
                                $_SESSION["message"] = "Appointment marked as completed. Payment record and medical record have been created.";
                                $_SESSION["message_type"] = "success";
                                header("location: view_medical_record.php?id=" . $medical_record_id);
                                exit;
                            } else {
                                $_SESSION["message"] = "Appointment completed but error creating medical record.";
                                $_SESSION["message_type"] = "warning";
                                header("location: appointments.php");
                                exit;
                            }
                            mysqli_stmt_close($record_stmt);
                        }
                    } else {
                        // No medical record, just redirect to appointments
                        $_SESSION["message"] = "Appointment marked as completed. A payment record has been created.";
                        $_SESSION["message_type"] = "success";
                        header("location: appointments.php");
                        exit;
                    }
                } else {
                    $_SESSION["message"] = "Appointment completed but error creating payment record.";
                    $_SESSION["message_type"] = "warning";
                    header("location: appointments.php");
                    exit;
                }
                mysqli_stmt_close($payment_stmt);
            }
        } else {
            $_SESSION["message"] = "Error updating appointment status.";
            $_SESSION["message_type"] = "danger";
            header("location: appointments.php");
            exit;
        }
        mysqli_stmt_close($update_stmt);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Complete Appointment</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.1.3/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        body{ font: 14px sans-serif; padding: 20px; }
        .patient-info {
            background-color: rgba(52, 152, 219, 0.1);
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 20px;
        }
        .required-field::after {
            content: '*';
            color: #e74c3c;
            margin-left: 4px;
        }
        .card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.08);
            margin-bottom: 20px;
        }
        .card-header {
            font-weight: 600;
            background-color: rgba(44, 62, 80, 0.1);
            border-bottom: none;
            padding: 15px 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2><i class="fas fa-check-circle me-2"></i>Complete Appointment</h2>
        
        <div class="card mb-4">
            <div class="card-header">
                <i class="fas fa-info-circle me-2"></i>Appointment Details
            </div>
            <div class="card-body">
                <div class="patient-info">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <i class="fas fa-user-circle fa-3x text-primary"></i>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h5 class="mb-1"><?php echo htmlspecialchars($patient_name); ?></h5>
                            <p class="mb-0 text-muted">
                                Age: <?php echo date_diff(date_create($appointment["dob"]), date_create('now'))->y; ?> years | 
                                Gender: <?php echo htmlspecialchars($appointment["gender"]); ?>
                            </p>
                        </div>
                    </div>
                </div>
                <p><strong>Appointment ID:</strong> <?php echo $appointment_id; ?></p>
                <p><strong>Date & Time:</strong> <?php echo date('M d, Y h:i A', strtotime($appointment["appointment_datetime"])); ?></p>
                <p><strong>Current Status:</strong> <?php echo $appointment["status"]; ?></p>
            </div>
        </div>
        
        <div class="alert alert-info">
            <p><i class="fas fa-clipboard-list me-2"></i>Completing this appointment will:</p>
            <ul>
                <li>Mark the appointment as "Completed"</li>
                <li>Create a pending payment record for the patient</li>
                <li>Create a medical record (if diagnosis is provided)</li>
            </ul>
        </div>
        
        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]) . "?id=" . $appointment_id; ?>" method="post">
            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-file-medical me-2"></i>Medical Record Information
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label for="diagnosis" class="form-label required-field">Diagnosis</label>
                        <textarea class="form-control" id="diagnosis" name="diagnosis" rows="3" required></textarea>
                        <div class="form-text">Enter the primary diagnosis and any additional findings.</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="prescription" class="form-label">Prescription</label>
                        <textarea class="form-control" id="prescription" name="prescription" rows="3"></textarea>
                        <div class="form-text">Enter medication name, dosage, frequency, and duration. Each medication on a new line.</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="notes" class="form-label">Notes</label>
                        <textarea class="form-control" id="notes" name="notes" rows="3"></textarea>
                        <div class="form-text">Additional notes, recommendations, or follow-up instructions.</div>
                    </div>
                </div>
            </div>
            
            <div class="d-flex justify-content-between">
                <a href="appointments.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left me-2"></i>Back to Appointments
                </a>
                <button type="submit" class="btn btn-success">
                    <i class="fas fa-check me-2"></i>Complete Appointment
                </button>
            </div>
        </form>
        
        <div class="mt-4 text-center">
            <a href="add_record.php?appointment_id=<?php echo $appointment_id; ?>" class="btn btn-outline-primary">
                <i class="fas fa-plus-circle me-2"></i>Use Advanced Medical Record Form
            </a>
            <div class="form-text mt-2">For more comprehensive medical record creation with additional fields.</div>
        </div>
    </div>
    
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.1.3/js/bootstrap.bundle.min.js"></script>
</body>
</html>