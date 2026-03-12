<?php
// find_doctors.php - Find doctors by specialty
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

// Get all specialties for the filter sidebar
$sql = "SELECT specialty_id, name FROM specialties ORDER BY name";
if($stmt = mysqli_prepare($conn, $sql)){
    if(mysqli_stmt_execute($stmt)){
        $specialties_result = mysqli_stmt_get_result($stmt);
    }
    mysqli_stmt_close($stmt);
}

// Check if a specialty is selected
$selected_specialty = null;
$specialty_name = "All Specialties";
$doctors = [];

if(isset($_GET['specialty']) && !empty($_GET['specialty'])){
    $selected_specialty = $_GET['specialty'];
    
    // Get the selected specialty name
    $sql = "SELECT name FROM specialties WHERE specialty_id = ?";
    if($stmt = mysqli_prepare($conn, $sql)){
        mysqli_stmt_bind_param($stmt, "i", $selected_specialty);
        
        if(mysqli_stmt_execute($stmt)){
            $specialty_result = mysqli_stmt_get_result($stmt);
            if(mysqli_num_rows($specialty_result) == 1){
                $specialty_row = mysqli_fetch_array($specialty_result);
                $specialty_name = $specialty_row['name'];
            }
        }
        mysqli_stmt_close($stmt);
    }
    
    // Get doctors in the selected specialty
    $sql = "SELECT d.doctor_id, d.name, s.name as specialty_name, s.specialty_id, 
                   d.room_number, d.phone, d.email
            FROM doctors d
            JOIN specialties s ON d.specialty_id = s.specialty_id
            WHERE d.specialty_id = ?
            ORDER BY d.name";
            
    if($stmt = mysqli_prepare($conn, $sql)){
        mysqli_stmt_bind_param($stmt, "i", $selected_specialty);
        
        if(mysqli_stmt_execute($stmt)){
            $doctors_result = mysqli_stmt_get_result($stmt);
            while($doctor = mysqli_fetch_array($doctors_result)){
                $doctors[] = $doctor;
            }
        }
        mysqli_stmt_close($stmt);
    }
} else {
    // Get all doctors if no specialty is selected
    $sql = "SELECT d.doctor_id, d.name, s.name as specialty_name, s.specialty_id, 
                   d.room_number, d.phone, d.email
            FROM doctors d
            JOIN specialties s ON d.specialty_id = s.specialty_id
            ORDER BY s.name, d.name";
            
    if($stmt = mysqli_prepare($conn, $sql)){
        if(mysqli_stmt_execute($stmt)){
            $doctors_result = mysqli_stmt_get_result($stmt);
            while($doctor = mysqli_fetch_array($doctors_result)){
                $doctors[] = $doctor;
            }
        }
        mysqli_stmt_close($stmt);
    }
}

// Get specialty icons
$specialty_icons = [
    'Cardiology' => 'fa-heart',
    'Neurology' => 'fa-brain',
    'Orthopedics' => 'fa-bone',
    'Pediatrics' => 'fa-child',
    'Dermatology' => 'fa-allergies',
    'Internal Medicine' => 'fa-stethoscope',
    'Ophthalmology' => 'fa-eye',
    'ENT' => 'fa-ear-deaf',
    'General' => 'fa-user-md'
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Find Doctors<?php echo $selected_specialty ? ' - ' . htmlspecialchars($specialty_name) : ''; ?></title>
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
        
        .doctor-card {
            padding: 20px;
            border-radius: 10px;
            background-color: white;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.08);
            transition: transform 0.3s ease;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
        }
        
        .doctor-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }
        
        .doctor-avatar {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background-color: var(--secondary-color);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            margin-right: 20px;
            flex-shrink: 0;
        }
        
        .doctor-info {
            flex-grow: 1;
        }
        
        .doctor-name {
            font-weight: 600;
            font-size: 1.2rem;
            color: var(--secondary-color);
            margin-bottom: 5px;
        }
        
        .doctor-specialty {
            color: var(--primary-color);
            font-weight: 500;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
        }
        
        .doctor-specialty i {
            margin-right: 5px;
        }
        
        .doctor-details {
            color: #777;
            font-size: 0.9rem;
            margin-bottom: 5px;
        }
        
        .doctor-actions {
            margin-left: 20px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: flex-end;
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }
        
        .btn-primary:hover {
            background-color: #2980b9;
            border-color: #2980b9;
        }
        
        .btn-outline-primary {
            color: var(--primary-color);
            border-color: var(--primary-color);
        }
        
        .btn-outline-primary:hover {
            background-color: var(--primary-color);
            color: white;
        }
        
        .specialty-filter {
            margin-bottom: 20px;
        }
        
        .specialty-badge {
            background-color: var(--primary-color);
            color: white;
            font-size: 0.8rem;
            padding: 5px 10px;
            border-radius: 20px;
            margin-right: 5px;
            display: inline-block;
        }
        
        .specialty-icon {
            display: inline-block;
            width: 30px;
            height: 30px;
            background-color: rgba(52, 152, 219, 0.1);
            color: var(--primary-color);
            border-radius: 50%;
            text-align: center;
            line-height: 30px;
            margin-right: 10px;
        }
        
        .badge-count {
            background-color: var(--primary-color);
            color: white;
            border-radius: 20px;
            padding: 3px 8px;
            font-size: 0.7rem;
            margin-left: 5px;
        }
        
        @media (max-width: 767.98px) {
            .doctor-card {
                flex-direction: column;
                text-align: center;
            }
            
            .doctor-avatar {
                margin-right: 0;
                margin-bottom: 15px;
            }
            
            .doctor-actions {
                margin-left: 0;
                margin-top: 15px;
                align-items: center;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Page Header -->
        <div class="dashboard-header">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <h1>Find Doctors</h1>
                    <p class="mb-0">Explore our specialists <?php echo $selected_specialty ? 'in ' . htmlspecialchars($specialty_name) : ''; ?></p>
                </div>
                <div class="col-md-6 text-md-end">
                    <a href="patient_dashboard.php" class="btn btn-light"><i class="fas fa-home me-2"></i>Back to Dashboard</a>
                </div>
            </div>
        </div>
        
        <div class="row">
            <!-- Left Sidebar - Navigation and Filters -->
            <div class="col-lg-3 mb-4">
                <!-- Navigation -->
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
                            <a class="nav-link active" href="find_doctors.php">
                                <i class="fas fa-user-md nav-icon"></i> Find Doctors
                            </a>
                            <a class="nav-link" href="medical_records.php">
                                <i class="fas fa-file-medical nav-icon"></i> Medical Records
                            </a>
                            <a class="nav-link" href="payments.php">
                                <i class="fas fa-credit-card nav-icon"></i> Payments
                            </a>
                        </div>
                    </div>
                </div>
                
                <!-- Specialty Filter -->
                <div class="card mt-4">
                    <div class="card-header">
                        <i class="fas fa-filter me-2"></i> Filter by Specialty
                    </div>
                    <div class="card-body p-2">
                        <div class="nav flex-column nav-pills specialty-filter">
                            <a class="nav-link <?php echo !$selected_specialty ? 'active' : ''; ?>" href="find_doctors.php">
                                <i class="fas fa-users-medical me-2"></i> All Specialties
                                <span class="badge bg-primary rounded-pill float-end"><?php echo count($doctors); ?></span>
                            </a>
                            <?php 
                            if(isset($specialties_result) && mysqli_num_rows($specialties_result) > 0):
                                mysqli_data_seek($specialties_result, 0);
                                while($specialty = mysqli_fetch_array($specialties_result)):
                                    $icon = isset($specialty_icons[$specialty['name']]) ? $specialty_icons[$specialty['name']] : 'fa-user-md';
                                    
                                    // Count doctors in this specialty
                                    $count_sql = "SELECT COUNT(*) as count FROM doctors WHERE specialty_id = ?";
                                    $doctor_count = 0;
                                    if($count_stmt = mysqli_prepare($conn, $count_sql)){
                                        mysqli_stmt_bind_param($count_stmt, "i", $specialty['specialty_id']);
                                        if(mysqli_stmt_execute($count_stmt)){
                                            $count_result = mysqli_stmt_get_result($count_stmt);
                                            if($count_row = mysqli_fetch_assoc($count_result)){
                                                $doctor_count = $count_row['count'];
                                            }
                                        }
                                        mysqli_stmt_close($count_stmt);
                                    }
                            ?>
                                <a class="nav-link <?php echo $selected_specialty == $specialty['specialty_id'] ? 'active' : ''; ?>" href="find_doctors.php?specialty=<?php echo $specialty['specialty_id']; ?>">
                                    <i class="fas <?php echo $icon; ?> me-2"></i> <?php echo htmlspecialchars($specialty['name']); ?>
                                    <span class="badge bg-primary rounded-pill float-end"><?php echo $doctor_count; ?></span>
                                </a>
                            <?php 
                                endwhile;
                            endif; 
                            ?>
                        </div>
                    </div>
                </div>
                
                <!-- Quick Book -->
                <div class="card mt-4">
                    <div class="card-header">
                        <i class="fas fa-calendar-plus me-2"></i> Quick Book
                    </div>
                    <div class="card-body text-center">
                        <p>Need to schedule an appointment quickly?</p>
                        <a href="book_appointment.php" class="btn btn-primary w-100">Book an Appointment</a>
                    </div>
                </div>
            </div>
            
            <!-- Main Content - Doctor Listings -->
            <div class="col-lg-9">
                <!-- Doctors Header -->
                <div class="card mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <span>
                            <i class="fas fa-user-md me-2"></i> <?php echo htmlspecialchars($specialty_name); ?> Doctors
                        </span>
                        <span class="text-muted"><?php echo count($doctors); ?> doctors found</span>
                    </div>
                </div>
                
                <!-- Doctor Listings -->
                <?php if(count($doctors) > 0): ?>
                    <?php foreach($doctors as $doctor): 
                        $icon = isset($specialty_icons[$doctor['specialty_name']]) ? $specialty_icons[$doctor['specialty_name']] : 'fa-user-md';
                    ?>
                        <div class="doctor-card">
                            <div class="doctor-avatar">
                                <i class="fas fa-user-md"></i>
                            </div>
                            <div class="doctor-info">
                                <div class="doctor-name">Dr. <?php echo htmlspecialchars($doctor['name']); ?></div>
                                <div class="doctor-specialty">
                                    <i class="fas <?php echo $icon; ?>"></i>
                                    <?php echo htmlspecialchars($doctor['specialty_name']); ?>
                                </div>
                                <div class="doctor-details">
                                    <i class="fas fa-door-open me-1"></i> Room <?php echo htmlspecialchars($doctor['room_number']); ?>
                                </div>
                                <?php if(!empty($doctor['phone'])): ?>
                                <div class="doctor-details">
                                    <i class="fas fa-phone me-1"></i> <?php echo htmlspecialchars($doctor['phone']); ?>
                                </div>
                                <?php endif; ?>
                                <?php if(!empty($doctor['email'])): ?>
                                <div class="doctor-details">
                                    <i class="fas fa-envelope me-1"></i> <?php echo htmlspecialchars($doctor['email']); ?>
                                </div>
                                <?php endif; ?>
                            </div>
                            <div class="doctor-actions">
                                <a href="book_appointment.php?doctor=<?php echo $doctor['doctor_id']; ?>" class="btn btn-primary mb-2">
                                    <i class="fas fa-calendar-plus me-2"></i> Book Appointment
                                </a>
                                <a href="doctor_profile.php?id=<?php echo $doctor['doctor_id']; ?>" class="btn btn-outline-primary">
                                    <i class="fas fa-user-md me-2"></i> View Profile
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i> No doctors found for this specialty. Please select another specialty.
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.1.3/js/bootstrap.bundle.min.js"></script>
</body>
</html>