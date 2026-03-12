<?php
// view_medical_record.php - View specific medical record details
session_start();
require_once "config.php";

// Check if the user is logged in as a doctor
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["user_type"] !== "doctor"){
    header("location: login.php");
    exit;
}

$doctor_id = $_SESSION["id"];

// Check if record ID is provided in the URL
if(!isset($_GET["id"]) || empty(trim($_GET["id"]))){
    header("location: error.php");
    exit();
}

// Get the record ID from URL
$record_id = trim($_GET["id"]);

// Retrieve medical record details
$sql = "SELECT mr.record_id, mr.patient_id, mr.doctor_id, mr.appointment_id, 
               mr.diagnosis, mr.prescription, mr.notes, mr.created_at,
               p.name as patient_name, p.dob, p.gender, p.phone, p.email,
               d.name as doctor_name, s.name as specialty,
               a.appointment_datetime
        FROM medical_records mr
        JOIN patients p ON mr.patient_id = p.patient_id
        JOIN doctors d ON mr.doctor_id = d.doctor_id
        JOIN specialties s ON d.specialty_id = s.specialty_id
        JOIN appointments a ON mr.appointment_id = a.appointment_id
        WHERE mr.record_id = ? AND (mr.doctor_id = ? OR 1=?)";
        
// The 1=? condition allows for potential admin access in the future
// In a real-world application, you would implement proper role-based permissions

if($stmt = mysqli_prepare($conn, $sql)){
    // Add a value that will make the condition true only for the record's doctor or admins (value = 0 by default)
    $is_admin = 0; // Change this based on your admin role implementation
    
    mysqli_stmt_bind_param($stmt, "iii", $record_id, $doctor_id, $is_admin);
    
    if(mysqli_stmt_execute($stmt)){
        $result = mysqli_stmt_get_result($stmt);
        
        if(mysqli_num_rows($result) == 1){
            $record = mysqli_fetch_array($result);
        } else {
            // Record not found or access denied
            header("location: error.php");
            exit();
        }
    } else {
        echo "Oops! Something went wrong. Please try again later.";
    }
    
    mysqli_stmt_close($stmt);
}

// Get patient's previous medical records (excluding current one)
$sql = "SELECT mr.record_id, mr.diagnosis, mr.created_at, d.name as doctor_name
        FROM medical_records mr
        JOIN doctors d ON mr.doctor_id = d.doctor_id
        WHERE mr.patient_id = ? AND mr.record_id != ?
        ORDER BY mr.created_at DESC
        LIMIT 5";

if($stmt = mysqli_prepare($conn, $sql)){
    mysqli_stmt_bind_param($stmt, "ii", $record["patient_id"], $record_id);
    
    if(mysqli_stmt_execute($stmt)){
        $previous_records = mysqli_stmt_get_result($stmt);
    }
    
    mysqli_stmt_close($stmt);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Medical Record</title>
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
        
        .record-section {
            background-color: #fff;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
        }
        
        .record-section h5 {
            color: var(--secondary-color);
            border-bottom: 1px solid #eee;
            padding-bottom: 10px;
            margin-bottom: 15px;
        }
        
        .btn-primary {
            background-color: var(--secondary-color);
            border-color: var(--secondary-color);
        }
        
        .btn-primary:hover {
            background-color: #34495e;
            border-color: #34495e;
        }
        
        .record-meta {
            color: #6c757d;
            font-size: 0.9rem;
        }
        
        .record-content {
            white-space: pre-line;
            line-height: 1.6;
        }
        
        .prescription-item {
            padding: 8px 15px;
            margin-bottom: 5px;
            background-color: #f8f9fa;
            border-radius: 5px;
            border-left: 3px solid var(--primary-color);
        }
        
        .history-record {
            cursor: pointer;
            transition: background-color 0.2s;
        }
        
        .history-record:hover {
            background-color: rgba(52, 152, 219, 0.05);
        }
        
        .print-section {
            display: none;
        }
        
        @media print {
            .no-print {
                display: none !important;
            }
            
            .print-section {
                display: block;
            }
            
            .container {
                width: 100%;
                max-width: 100%;
            }
            
            body {
                background-color: #fff;
                padding: 0;
                margin: 0;
            }
            
            .card {
                box-shadow: none;
                border: 1px solid #ddd;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Dashboard Header -->
        <div class="dashboard-header no-print">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <h1><i class="fas fa-file-medical me-3"></i>Medical Record</h1>
                    <p class="mb-0">Viewing record #<?php echo htmlspecialchars($record_id); ?></p>
                </div>
                <div class="col-md-6 text-md-end">
                    <button onclick="window.print();" class="btn btn-light">
                        <i class="fas fa-print me-2"></i>Print Record
                    </button>
                    <a href="edit_medical_record.php?id=<?php echo $record_id; ?>" class="btn btn-warning">
                        <i class="fas fa-edit me-2"></i>Edit Record
                    </a>
                    <a href="doctor_medical_records.php" class="btn btn-outline-light ms-2">
                        <i class="fas fa-arrow-left me-2"></i>Back to Records
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Print Header (Only visible when printing) -->
        <div class="print-section mb-4">
            <h2 class="text-center">Hospital Management System</h2>
            <h3 class="text-center">Medical Record #<?php echo htmlspecialchars($record_id); ?></h3>
            <p class="text-center"><?php echo date('F d, Y', strtotime($record["created_at"])); ?></p>
            <hr>
        </div>
        
        <div class="row">
            <div class="col-lg-8">
                <!-- Patient and Appointment Info -->
                <div class="card mb-4">
                    <div class="card-body">
                        <!-- Patient Info -->
                        <div class="patient-info">
                            <div class="d-flex align-items-center">
                                <div class="flex-shrink-0">
                                    <i class="fas fa-user-circle fa-3x text-primary"></i>
                                </div>
                                <div class="flex-grow-1 ms-3">
                                    <h5 class="mb-1"><?php echo htmlspecialchars($record["patient_name"]); ?></h5>
                                    <p class="mb-0 text-muted">
                                        Age: <?php echo date_diff(date_create($record["dob"]), date_create('now'))->y; ?> years | 
                                        Gender: <?php echo htmlspecialchars($record["gender"]); ?> | 
                                        Phone: <?php echo htmlspecialchars($record["phone"]); ?>
                                    </p>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Appointment Info -->
                        <div class="appointment-info">
                            <div class="d-flex align-items-center">
                                <div class="flex-shrink-0">
                                    <i class="fas fa-calendar-check fa-3x text-success"></i>
                                </div>
                                <div class="flex-grow-1 ms-3">
                                    <h5 class="mb-1">Appointment Details</h5>
                                    <p class="mb-0">
                                        Date: <?php echo date('F d, Y', strtotime($record["appointment_datetime"])); ?> | 
                                        Time: <?php echo date('h:i A', strtotime($record["appointment_datetime"])); ?> | 
                                        Doctor: Dr. <?php echo htmlspecialchars($record["doctor_name"]); ?> (<?php echo htmlspecialchars($record["specialty"]); ?>)
                                    </p>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Record Meta Information -->
                        <div class="text-end mt-3">
                            <span class="badge bg-secondary">
                                <i class="fas fa-clock me-1"></i> Created: <?php echo date('M d, Y h:i A', strtotime($record["created_at"])); ?>
                            </span>
                            <span class="badge bg-info ms-2">
                                <i class="fas fa-hashtag me-1"></i> Record ID: <?php echo htmlspecialchars($record_id); ?>
                            </span>
                        </div>
                    </div>
                </div>
                
                <!-- Medical Record Content -->
                <div class="card">
                    <div class="card-header">
                        <i class="fas fa-clipboard-list me-2"></i> Medical Record Details
                    </div>
                    <div class="card-body">
                        <!-- Diagnosis Section -->
                        <div class="record-section">
                            <h5><i class="fas fa-stethoscope me-2"></i> Diagnosis</h5>
                            <div class="record-content">
                                <?php echo nl2br(htmlspecialchars($record["diagnosis"])); ?>
                            </div>
                        </div>
                        
                        <!-- Prescription Section -->
                        <div class="record-section">
                            <h5><i class="fas fa-prescription me-2"></i> Prescription</h5>
                            <?php if(!empty(trim($record["prescription"]))): ?>
                                <div class="record-content">
                                    <?php 
                                    // Parse prescription into separate medication items
                                    $medications = explode("\n", $record["prescription"]);
                                    foreach($medications as $medication):
                                        if(!empty(trim($medication))):
                                    ?>
                                        <div class="prescription-item">
                                            <i class="fas fa-capsules me-2"></i>
                                            <?php echo htmlspecialchars(trim($medication)); ?>
                                        </div>
                                    <?php 
                                        endif;
                                    endforeach; 
                                    ?>
                                </div>
                            <?php else: ?>
                                <p class="text-muted">No prescriptions provided.</p>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Notes Section -->
                        <div class="record-section">
                            <h5><i class="fas fa-sticky-note me-2"></i> Additional Notes</h5>
                            <?php if(!empty(trim($record["notes"]))): ?>
                                <div class="record-content">
                                    <?php echo nl2br(htmlspecialchars($record["notes"])); ?>
                                </div>
                            <?php else: ?>
                                <p class="text-muted">No additional notes provided.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-4 no-print">
                <!-- Patient Medical History -->
                <div class="card">
                    <div class="card-header">
                        <i class="fas fa-history me-2"></i> Patient's Medical History
                    </div>
                    <div class="card-body">
                        <?php if(isset($previous_records) && mysqli_num_rows($previous_records) > 0): ?>
                            <div class="list-group">
                                <?php while($prev_record = mysqli_fetch_array($previous_records)): ?>
                                    <a href="view_medical_record.php?id=<?php echo $prev_record['record_id']; ?>" class="list-group-item list-group-item-action history-record">
                                        <div class="d-flex w-100 justify-content-between">
                                            <h6 class="mb-1">Record #<?php echo $prev_record['record_id']; ?></h6>
                                            <small><?php echo date('M d, Y', strtotime($prev_record['created_at'])); ?></small>
                                        </div>
                                        <p class="mb-1">
                                            <?php 
                                            $short_diagnosis = (strlen($prev_record['diagnosis']) > 50) ? 
                                                substr($prev_record['diagnosis'], 0, 50) . '...' : 
                                                $prev_record['diagnosis'];
                                            echo htmlspecialchars($short_diagnosis); 
                                            ?>
                                        </p>
                                        <small>Dr. <?php echo htmlspecialchars($prev_record['doctor_name']); ?></small>
                                    </a>
                                <?php endwhile; ?>
                            </div>
                            
                            <div class="text-center mt-3">
                                <a href="patient_records.php?id=<?php echo $record['patient_id']; ?>" class="btn btn-outline-primary btn-sm">
                                    <i class="fas fa-list me-1"></i> View All Records
                                </a>
                            </div>
                        <?php else: ?>
                            <div class="text-center p-3">
                                <i class="fas fa-folder-open text-muted mb-3" style="font-size: 2rem;"></i>
                                <p>No previous medical records found for this patient.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Quick Actions -->
                <div class="card mt-4">
                    <div class="card-header">
                        <i class="fas fa-tools me-2"></i> Quick Actions
                    </div>
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <a href="add_record.php?patient_id=<?php echo $record['patient_id']; ?>" class="btn btn-outline-primary">
                                <i class="fas fa-plus-circle me-2"></i> New Medical Record
                            </a>
                            <a href="create_appointment.php?patient_id=<?php echo $record['patient_id']; ?>" class="btn btn-outline-success">
                                <i class="fas fa-calendar-plus me-2"></i> Schedule Appointment
                            </a>
                    
                            <?php if($record['doctor_id'] == $doctor_id): ?>
                                <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#deleteRecordModal">
                                    <i class="fas fa-trash-alt me-2"></i> Delete Record
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Delete Record Modal -->
    <?php if($record['doctor_id'] == $doctor_id): ?>
    <div class="modal fade" id="deleteRecordModal" tabindex="-1" aria-labelledby="deleteRecordModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title" id="deleteRecordModalLabel">Confirm Delete</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete this medical record? This action cannot be undone.</p>
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i> Warning: Deleting medical records may violate healthcare regulations and data retention policies.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <a href="delete_medical_record.php?id=<?php echo $record_id; ?>" class="btn btn-danger">Delete Record</a>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
    
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.1.3/js/bootstrap.bundle.min.js"></script>
</body>
</html>