<?php
// medical_records.php - Display patient medical records
session_start();
require_once "config.php";

// Check if the user is logged in as a patient
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["user_type"] !== "patient"){
    header("location: login.php");
    exit;
}

$patient_id = $_SESSION["id"];

// Get patient information
$sql = "SELECT name FROM patients WHERE patient_id = ?";
if($stmt = mysqli_prepare($conn, $sql)){
    mysqli_stmt_bind_param($stmt, "i", $patient_id);
    
    if(mysqli_stmt_execute($stmt)){
        $result = mysqli_stmt_get_result($stmt);
        if(mysqli_num_rows($result) == 1){
            $patient = mysqli_fetch_array($result);
        }
    }
    mysqli_stmt_close($stmt);
}

// Get all medical records for this patient
$sql = "SELECT mr.record_id, mr.diagnosis, mr.notes, mr.prescription, mr.created_at, 
               d.name as doctor_name, s.name as specialty
        FROM medical_records mr
        JOIN doctors d ON mr.doctor_id = d.doctor_id
        JOIN specialties s ON d.specialty_id = s.specialty_id
        WHERE mr.patient_id = ?
        ORDER BY mr.created_at DESC";

if($stmt = mysqli_prepare($conn, $sql)){
    mysqli_stmt_bind_param($stmt, "i", $patient_id);
    
    if(mysqli_stmt_execute($stmt)){
        $medical_records = mysqli_stmt_get_result($stmt);
    }
    mysqli_stmt_close($stmt);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Medical Records</title>
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
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 25px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        
        .nav-pills .nav-link.active {
            background-color: var(--primary-color);
        }
        
        .nav-pills .nav-link {
            color: var(--secondary-color);
            border-radius: 5px;
            padding: 10px 15px;
            margin: 5px 0;
            transition: all 0.3s ease;
        }
        
        .nav-pills .nav-link:hover {
            background-color: rgba(52, 152, 219, 0.1);
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
            background-color: rgba(52, 152, 219, 0.1);
            border-bottom: none;
            padding: 15px 20px;
        }
        
        .record-card {
            border-left: 4px solid var(--primary-color);
            margin-bottom: 20px;
            transition: all 0.3s ease;
        }
        
        .record-card:hover {
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }
        
        .record-header {
            background-color: rgba(52, 152, 219, 0.05);
            padding: 15px;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
        }
        
        .record-date {
            color: #777;
            font-size: 0.9rem;
        }
        
        .record-doctor {
            color: var(--primary-color);
            font-weight: 500;
        }
        
        .record-specialty {
            font-size: 0.85rem;
            color: #666;
            font-style: italic;
        }
        
        .record-section {
            margin-bottom: 15px;
        }
        
        .record-label {
            font-weight: 600;
            color: #555;
            margin-bottom: 5px;
        }

        .record-content {
            background-color: white;
            padding: 15px;
            border-radius: 5px;
            border: 1px solid rgba(0, 0, 0, 0.05);
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }
        
        .btn-primary:hover {
            background-color: #2980b9;
            border-color: #2980b9;
        }
        
        .no-records {
            text-align: center;
            padding: 50px 0;
        }
        
        .no-records-icon {
            font-size: 4rem;
            color: #ddd;
            margin-bottom: 20px;
        }
        
        .filter-section {
            background-color: white;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Page Header -->
        <div class="page-header">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <h1>Medical Records</h1>
                    <p class="mb-0">View your complete medical history</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <div class="btn-group">
                        <a href="patient_dashboard.php" class="btn btn-light"><i class="fas fa-home me-2"></i>Dashboard</a>
                        <a href="logout.php" class="btn btn-outline-light"><i class="fas fa-sign-out-alt me-2"></i>Logout</a>
                    </div>
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
                            <a class="nav-link" href="patient_dashboard.php">
                                <i class="fas fa-home nav-icon"></i> Dashboard
                            </a>
                            <a class="nav-link" href="appointments.php">
                                <i class="fas fa-calendar-check nav-icon"></i> My Appointments
                            </a>
                            <a class="nav-link" href="book_appointment.php">
                                <i class="fas fa-calendar-plus nav-icon"></i> Book Appointment
                            </a>
                            <a class="nav-link active" href="medical_records.php">
                                <i class="fas fa-file-medical nav-icon"></i> Medical Records
                            </a>
                            <a class="nav-link" href="payments.php">
                                <i class="fas fa-credit-card nav-icon"></i> Payments
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Main Content -->
            <div class="col-lg-9">
                <!-- Filter Section -->
                <div class="filter-section">
                    <form method="get" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" class="row g-3 align-items-end">
                        <div class="col-md-4">
                            <label for="doctor" class="form-label">Doctor</label>
                            <select name="doctor" id="doctor" class="form-select">
                                <option value="">All Doctors</option>
                                <?php
                                // Get all doctors who have treated this patient
                                $sql = "SELECT DISTINCT d.doctor_id, d.name 
                                        FROM medical_records mr
                                        JOIN doctors d ON mr.doctor_id = d.doctor_id
                                        WHERE mr.patient_id = ?
                                        ORDER BY d.name";
                                
                                if($stmt = mysqli_prepare($conn, $sql)){
                                    mysqli_stmt_bind_param($stmt, "i", $patient_id);
                                    
                                    if(mysqli_stmt_execute($stmt)){
                                        $doctors_result = mysqli_stmt_get_result($stmt);
                                        while($doctor = mysqli_fetch_array($doctors_result)):
                                            $selected = (isset($_GET['doctor']) && $_GET['doctor'] == $doctor['doctor_id']) ? 'selected' : '';
                                            echo '<option value="' . $doctor['doctor_id'] . '" ' . $selected . '>' . htmlspecialchars($doctor['name']) . '</option>';
                                        endwhile;
                                    }
                                    mysqli_stmt_close($stmt);
                                }
                                ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="date_from" class="form-label">Date From</label>
                            <input type="date" name="date_from" id="date_from" class="form-control" value="<?php echo isset($_GET['date_from']) ? $_GET['date_from'] : ''; ?>">
                        </div>
                        <div class="col-md-4">
                            <label for="date_to" class="form-label">Date To</label>
                            <input type="date" name="date_to" id="date_to" class="form-control" value="<?php echo isset($_GET['date_to']) ? $_GET['date_to'] : ''; ?>">
                        </div>
                        <div class="col-12 text-end">
                            <button type="submit" class="btn btn-primary"><i class="fas fa-filter me-2"></i>Filter</button>
                            <?php if(isset($_GET['doctor']) || isset($_GET['date_from']) || isset($_GET['date_to'])): ?>
                                <a href="medical_records.php" class="btn btn-outline-secondary"><i class="fas fa-undo me-2"></i>Reset</a>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
                
                <!-- Medical Records List -->
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <span><i class="fas fa-file-medical me-2"></i> Your Medical Records</span>
                        <?php if(isset($medical_records) && mysqli_num_rows($medical_records) > 0): ?>
                            <button class="btn btn-sm btn-outline-primary" onclick="window.print()">
                                <i class="fas fa-print me-1"></i> Print All
                            </button>
                        <?php endif; ?>
                    </div>
                    <div class="card-body">
                        <?php if(isset($medical_records) && mysqli_num_rows($medical_records) > 0): ?>
                            <?php while($record = mysqli_fetch_array($medical_records)): ?>
                                <div class="record-card card mb-4">
                                    <div class="record-header d-md-flex justify-content-between align-items-center">
                                        <div>
                                            <h5 class="mb-0">Medical Record #<?php echo $record['record_id']; ?></h5>
                                            <div class="record-date mt-1">
                                                <i class="fas fa-calendar-alt me-1"></i> 
                                                <?php echo date('F d, Y', strtotime($record['created_at'])); ?>
                                            </div>
                                        </div>
                                        <div class="mt-2 mt-md-0">
                                            <div class="record-doctor">
                                                <i class="fas fa-user-md me-1"></i> 
                                                Dr. <?php echo htmlspecialchars($record['doctor_name']); ?>
                                            </div>
                                            <div class="record-specialty">
                                                <?php echo htmlspecialchars($record['specialty']); ?> Specialist
                                            </div>
                                        </div>
                                    </div>
                                    <div class="card-body">
                                        <div class="record-section">
                                            <div class="record-label">Diagnosis</div>
                                            <div class="record-content">
                                                <?php echo nl2br(htmlspecialchars($record['diagnosis'])); ?>
                                            </div>
                                        </div>
                                        
                                        <?php if(!empty($record['notes'])): ?>
                                            <div class="record-section">
                                                <div class="record-label">Notes</div>
                                                <div class="record-content">
                                                    <?php echo nl2br(htmlspecialchars($record['notes'])); ?>
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <?php if(!empty($record['prescription'])): ?>
                                            <div class="record-section">
                                                <div class="record-label">Prescription</div>
                                                <div class="record-content">
                                                    <?php echo nl2br(htmlspecialchars($record['prescription'])); ?>
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <div class="text-end mt-3">
                                            <a href="view_medical_record.php?id=<?php echo $record['record_id']; ?>" class="btn btn-sm btn-outline-primary">
                                                <i class="fas fa-eye me-1"></i> View Details
                                            </a>
                                            <a href="download_record.php?id=<?php echo $record['record_id']; ?>" class="btn btn-sm btn-outline-secondary">
                                                <i class="fas fa-download me-1"></i> Download
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <div class="no-records">
                                <div class="no-records-icon">
                                    <i class="fas fa-file-medical"></i>
                                </div>
                                <h4>No Medical Records Found</h4>
                                <p class="text-muted">
                                    You don't have any medical records in our system yet.<br>
                                    Records will appear here after your appointments with doctors.
                                </p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.1.3/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Add any JavaScript functionality here
        });
    </script>
</body>
</html>