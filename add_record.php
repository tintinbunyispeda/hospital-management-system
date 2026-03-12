<?php
// add_record.php - Add new medical record for patients
session_start();
require_once "config.php";

// Check if the user is logged in as a doctor
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["user_type"] !== "doctor"){
    header("location: login.php");
    exit;
}

$doctor_id = $_SESSION["id"];

// Get doctor information
$sql = "SELECT name FROM doctors WHERE doctor_id = ?";
if($stmt = mysqli_prepare($conn, $sql)){
    mysqli_stmt_bind_param($stmt, "i", $doctor_id);
    
    if(mysqli_stmt_execute($stmt)){
        $result = mysqli_stmt_get_result($stmt);
        if(mysqli_num_rows($result) == 1){
            $doctor = mysqli_fetch_array($result);
        }
    }
    mysqli_stmt_close($stmt);
}

// Initialize variables
$patient_id = $appointment_id = "";
$diagnosis = $prescription = $notes = "";
$patient_id_err = $diagnosis_err = "";

// Check if appointment_id is provided in URL
if(isset($_GET["appointment_id"]) && !empty(trim($_GET["appointment_id"]))){
    // Get appointment details
    $appointment_id = trim($_GET["appointment_id"]);
    
    $sql = "SELECT a.appointment_id, a.patient_id, a.appointment_datetime, a.status,
                  p.name as patient_name, p.dob, p.gender
           FROM appointments a
           JOIN patients p ON a.patient_id = p.patient_id
           WHERE a.appointment_id = ? AND a.doctor_id = ?";
    
    if($stmt = mysqli_prepare($conn, $sql)){
        mysqli_stmt_bind_param($stmt, "ii", $appointment_id, $doctor_id);
        
        if(mysqli_stmt_execute($stmt)){
            $result = mysqli_stmt_get_result($stmt);
            
            if(mysqli_num_rows($result) == 1){
                $appointment = mysqli_fetch_array($result);
                $patient_id = $appointment["patient_id"];
            } else {
                // Appointment not found or doesn't belong to this doctor
                header("location: error.php");
                exit();
            }
        } else {
            echo "Oops! Something went wrong. Please try again later.";
        }
        
        mysqli_stmt_close($stmt);
    }
} elseif(isset($_GET["patient_id"]) && !empty(trim($_GET["patient_id"]))){
    // If patient_id is provided but no appointment_id
    $patient_id = trim($_GET["patient_id"]);
    
    // Get patient details
    $sql = "SELECT patient_id, name, dob, gender FROM patients WHERE patient_id = ?";
    
    if($stmt = mysqli_prepare($conn, $sql)){
        mysqli_stmt_bind_param($stmt, "i", $patient_id);
        
        if(mysqli_stmt_execute($stmt)){
            $result = mysqli_stmt_get_result($stmt);
            
            if(mysqli_num_rows($result) == 1){
                $patient = mysqli_fetch_array($result);
            } else {
                // Patient not found
                header("location: error.php");
                exit();
            }
        } else {
            echo "Oops! Something went wrong. Please try again later.";
        }
        
        mysqli_stmt_close($stmt);
    }
    
    // Get patient's appointments
    $sql = "SELECT appointment_id, appointment_datetime, status 
            FROM appointments 
            WHERE patient_id = ? AND doctor_id = ? AND (status = 'Scheduled' OR status = 'Completed')
            ORDER BY appointment_datetime DESC
            LIMIT 10";
    
    if($stmt = mysqli_prepare($conn, $sql)){
        mysqli_stmt_bind_param($stmt, "ii", $patient_id, $doctor_id);
        
        if(mysqli_stmt_execute($stmt)){
            $patient_appointments = mysqli_stmt_get_result($stmt);
        }
        
        mysqli_stmt_close($stmt);
    }
}

// Process form data when form is submitted
if($_SERVER["REQUEST_METHOD"] == "POST"){
    
    // Validate patient ID
    if(empty(trim($_POST["patient_id"]))){
        $patient_id_err = "Please select a patient.";
    } else {
        $patient_id = trim($_POST["patient_id"]);
    }
    
    // Validate diagnosis
    if(empty(trim($_POST["diagnosis"]))){
        $diagnosis_err = "Please enter a diagnosis.";
    } else {
        $diagnosis = trim($_POST["diagnosis"]);
    }
    
    // Optional fields
    $prescription = trim($_POST["prescription"]);
    $notes = trim($_POST["notes"]);
    
    // Get appointment ID if provided (make it optional)
    $appointment_id = !empty($_POST["appointment_id"]) ? trim($_POST["appointment_id"]) : NULL;
    
    // Check input errors before inserting in database
    if(empty($patient_id_err) && empty($diagnosis_err)){
        
        // Prepare an insert statement
        $sql = "INSERT INTO medical_records (patient_id, doctor_id, appointment_id, diagnosis, prescription, notes) 
                VALUES (?, ?, ?, ?, ?, ?)";
        
        if($stmt = mysqli_prepare($conn, $sql)){
            // Bind variables to the prepared statement as parameters
            mysqli_stmt_bind_param($stmt, "iiisss", $param_patient_id, $param_doctor_id, $param_appointment_id, $param_diagnosis, $param_prescription, $param_notes);
            
            // Set parameters
            $param_patient_id = $patient_id;
            $param_doctor_id = $doctor_id;
            $param_appointment_id = $appointment_id;
            $param_diagnosis = $diagnosis;
            $param_prescription = $prescription;
            $param_notes = $notes;
            
            // Attempt to execute the prepared statement
            if(mysqli_stmt_execute($stmt)){
                // Records created successfully
                
                // If an appointment was provided and it was scheduled, mark it as completed
                if(!empty($appointment_id)){
                    $update_sql = "UPDATE appointments SET status = 'Completed' WHERE appointment_id = ? AND status = 'Scheduled'";
                    
                    if($update_stmt = mysqli_prepare($conn, $update_sql)){
                        mysqli_stmt_bind_param($update_stmt, "i", $appointment_id);
                        mysqli_stmt_execute($update_stmt);
                        mysqli_stmt_close($update_stmt);
                    }
                }
                
                // Redirect to the medical record view page
                $last_id = mysqli_insert_id($conn);
                header("location: view_medical_record.php?id=" . $last_id);
                exit();
            } else {
                echo "Oops! Something went wrong. Please try again later. Error: " . mysqli_error($conn);
            }
            
            // Close statement
            mysqli_stmt_close($stmt);
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Medical Record</title>
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
        
        .dashboard-header {
            background: linear-gradient(135deg, var(--secondary-color), #34495e);
            color: white;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 25px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        
        .card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.08);
            transition: transform 0.3s ease;
            margin-bottom: 20px;
            overflow: hidden;
        }
        
        .card-header {
            font-weight: 600;
            background-color: rgba(44, 62, 80, 0.1);
            border-bottom: none;
            padding: 15px 20px;
        }
        
        .patient-info {
            background-color: rgba(52, 152, 219, 0.1);
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 20px;
        }
        
        .appointment-info {
            background-color: rgba(46, 204, 113, 0.1);
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 20px;
        }
        
        .btn-primary {
            background-color: var(--secondary-color);
            border-color: var(--secondary-color);
        }
        
        .btn-primary:hover {
            background-color: #34495e;
            border-color: #34495e;
        }
        
        .form-label {
            font-weight: 500;
        }
        
        .required-field::after {
            content: '*';
            color: var(--danger-color);
            margin-left: 4px;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Dashboard Header -->
        <div class="dashboard-header">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <h1><i class="fas fa-file-medical me-3"></i>Add Medical Record</h1>
                    <p class="mb-0">Create a new medical record for your patient</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <a href="doctor_medical_records.php" class="btn btn-light">
                        <i class="fas fa-arrow-left me-2"></i>Back to Records
                    </a>
                    <a href="doctor_dashboard.php" class="btn btn-outline-light ms-2">
                        <i class="fas fa-home me-2"></i>Dashboard
                    </a>
                </div>
            </div>
        </div>
        
        <div class="row">
            <div class="col-lg-8 mx-auto">
                <div class="card">
                    <div class="card-header">
                        <i class="fas fa-plus-circle me-2"></i> New Medical Record Form
                    </div>
                    <div class="card-body">
                        <?php if(isset($appointment)): ?>
                            <!-- Display appointment info if coming from appointment -->
                            <div class="patient-info">
                                <div class="d-flex align-items-center">
                                    <div class="flex-shrink-0">
                                        <i class="fas fa-user-circle fa-3x text-primary"></i>
                                    </div>
                                    <div class="flex-grow-1 ms-3">
                                        <h5 class="mb-1"><?php echo htmlspecialchars($appointment["patient_name"]); ?></h5>
                                        <p class="mb-0 text-muted">
                                            Age: <?php echo date_diff(date_create($appointment["dob"]), date_create('now'))->y; ?> years | 
                                            Gender: <?php echo htmlspecialchars($appointment["gender"]); ?>
                                        </p>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="appointment-info">
                                <div class="d-flex align-items-center">
                                    <div class="flex-shrink-0">
                                        <i class="fas fa-calendar-check fa-3x text-success"></i>
                                    </div>
                                    <div class="flex-grow-1 ms-3">
                                        <h5 class="mb-1">Appointment Details</h5>
                                        <p class="mb-0">
                                            Date: <?php echo date('F d, Y', strtotime($appointment["appointment_datetime"])); ?> | 
                                            Time: <?php echo date('h:i A', strtotime($appointment["appointment_datetime"])); ?> | 
                                            Status: <span class="badge bg-<?php echo ($appointment["status"] == "Completed") ? "success" : "primary"; ?>">
                                                <?php echo htmlspecialchars($appointment["status"]); ?>
                                            </span>
                                        </p>
                                    </div>
                                </div>
                            </div>
                        <?php elseif(isset($patient)): ?>
                            <!-- Display patient info if coming from patient page -->
                            <div class="patient-info">
                                <div class="d-flex align-items-center">
                                    <div class="flex-shrink-0">
                                        <i class="fas fa-user-circle fa-3x text-primary"></i>
                                    </div>
                                    <div class="flex-grow-1 ms-3">
                                        <h5 class="mb-1"><?php echo htmlspecialchars($patient["name"]); ?></h5>
                                        <p class="mb-0 text-muted">
                                            Age: <?php echo date_diff(date_create($patient["dob"]), date_create('now'))->y; ?> years | 
                                            Gender: <?php echo htmlspecialchars($patient["gender"]); ?>
                                        </p>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" id="recordForm">
                            <!-- Hidden patient ID field -->
                            <input type="hidden" name="patient_id" value="<?php echo $patient_id; ?>">
                            
                            <?php if(isset($patient) && !isset($appointment) && isset($patient_appointments) && mysqli_num_rows($patient_appointments) > 0): ?>
                                <!-- Appointment selection if multiple appointments exist -->
                                <div class="mb-4">
                                    <label for="appointment_id" class="form-label">Select Appointment (Optional)</label>
                                    <select class="form-select" id="appointment_id" name="appointment_id">
                                        <option value="">No appointment / Ad-hoc visit</option>
                                        <?php while($app = mysqli_fetch_array($patient_appointments)): ?>
                                            <option value="<?php echo $app['appointment_id']; ?>">
                                                <?php echo date('M d, Y h:i A', strtotime($app['appointment_datetime'])); ?> - 
                                                <?php echo $app['status']; ?>
                                            </option>
                                        <?php endwhile; ?>
                                    </select>
                                    <div class="form-text">Select an appointment or choose "No appointment" for ad-hoc visits.</div>
                                </div>
                            <?php elseif(isset($appointment)): ?>
                                <!-- Hidden appointment ID field if appointment is pre-selected -->
                                <input type="hidden" name="appointment_id" value="<?php echo $appointment_id; ?>">
                            <?php else: ?>
                                <!-- No appointments found, but we'll allow creation without an appointment -->
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle me-2"></i>
                                    No scheduled appointments found for this patient. Creating an ad-hoc medical record.
                                </div>
                            <?php endif; ?>
                            
                            <div class="mb-4">
                                <label for="diagnosis" class="form-label required-field">Diagnosis</label>
                                <textarea class="form-control <?php echo (!empty($diagnosis_err)) ? 'is-invalid' : ''; ?>" id="diagnosis" name="diagnosis" rows="3" required><?php echo $diagnosis; ?></textarea>
                                <span class="invalid-feedback"><?php echo $diagnosis_err; ?></span>
                                <div class="form-text">Enter the primary diagnosis and any additional findings.</div>
                            </div>
                            
                            <div class="mb-4">
                                <label for="prescription" class="form-label">Prescription</label>
                                <textarea class="form-control" id="prescription" name="prescription" rows="3"><?php echo $prescription; ?></textarea>
                                <div class="form-text">Enter medication name, dosage, frequency, and duration. Each medication on a new line.</div>
                            </div>
                            
                            <div class="mb-4">
                                <label for="notes" class="form-label">Notes</label>
                                <textarea class="form-control" id="notes" name="notes" rows="3"><?php echo $notes; ?></textarea>
                                <div class="form-text">Additional notes, recommendations, or follow-up instructions.</div>
                            </div>
                            
                            <hr class="my-4">
                            
                            <div class="d-flex justify-content-between">
                                <a href="<?php echo isset($appointment) ? "view_patient.php?id=" . $appointment_id : "doctor_medical_records.php"; ?>" class="btn btn-outline-secondary">
                                    <i class="fas fa-times me-2"></i>Cancel
                                </a>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-2"></i>Save Medical Record
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
                
                <!-- Additional guidance card -->
                <div class="card mt-4">
                    <div class="card-header">
                        <i class="fas fa-info-circle me-2"></i> Guidance
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <h6 class="fw-bold">Diagnosis Tips</h6>
                                <ul class="mb-0">
                                    <li>Be specific and use standard medical terminology</li>
                                    <li>Include primary and secondary diagnoses</li>
                                    <li>Note any chronic conditions relevant to treatment</li>
                                </ul>
                            </div>
                            <div class="col-md-6">
                                <h6 class="fw-bold">Prescription Guidelines</h6>
                                <ul class="mb-0">
                                    <li>Include medication name, strength, and form</li>
                                    <li>Specify dosage, frequency, and duration</li>
                                    <li>Note any special instructions (with meals, etc.)</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.1.3/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Form validation
            const form = document.getElementById('recordForm');
            
            form.addEventListener('submit', function(event) {
                const diagnosisField = document.getElementById('diagnosis');
                
                if(!diagnosisField.value.trim()) {
                    diagnosisField.classList.add('is-invalid');
                    event.preventDefault();
                } else {
                    diagnosisField.classList.remove('is-invalid');
                }
            });
        });
    </script>
</body>
</html>