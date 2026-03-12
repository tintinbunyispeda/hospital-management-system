<?php
// doctor_dashboard.php - Main dashboard for doctors
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

// Get today's appointments
$today = date('Y-m-d');
$sql = "SELECT a.appointment_id, a.appointment_datetime, a.status, 
               p.name as patient_name, p.dob, p.gender, p.phone
        FROM appointments a
        JOIN patients p ON a.patient_id = p.patient_id
        WHERE a.doctor_id = ? AND DATE(a.appointment_datetime) = ?
        ORDER BY a.appointment_datetime";

if($stmt = mysqli_prepare($conn, $sql)){
    mysqli_stmt_bind_param($stmt, "is", $param_doctor_id, $param_today);
    $param_doctor_id = $doctor_id;
    $param_today = $today;
    
    if(mysqli_stmt_execute($stmt)){
        $today_appointments = mysqli_stmt_get_result($stmt);
    }
    mysqli_stmt_close($stmt);
}

// Get upcoming appointments (excluding today)
$tomorrow = date('Y-m-d', strtotime('+1 day'));
$sql = "SELECT a.appointment_id, a.appointment_datetime, a.status, 
               p.name as patient_name
        FROM appointments a
        JOIN patients p ON a.patient_id = p.patient_id
        WHERE a.doctor_id = ? AND DATE(a.appointment_datetime) >= ? AND DATE(a.appointment_datetime) != ?
        ORDER BY a.appointment_datetime
        LIMIT 5";

if($stmt = mysqli_prepare($conn, $sql)){
    mysqli_stmt_bind_param($stmt, "iss", $param_doctor_id, $param_tomorrow, $param_today);
    $param_doctor_id = $doctor_id;
    $param_tomorrow = $tomorrow;
    $param_today = $today;
    
    if(mysqli_stmt_execute($stmt)){
        $upcoming_appointments = mysqli_stmt_get_result($stmt);
    }
    mysqli_stmt_close($stmt);
}

// Get recent patients (based on appointments)
$sql = "SELECT DISTINCT p.patient_id, p.name, p.dob, p.gender, MAX(a.appointment_datetime) as last_visit
        FROM appointments a
        JOIN patients p ON a.patient_id = p.patient_id
        WHERE a.doctor_id = ?
        GROUP BY p.patient_id, p.name, p.dob, p.gender
        ORDER BY last_visit DESC
        LIMIT 5";

if($stmt = mysqli_prepare($conn, $sql)){
    mysqli_stmt_bind_param($stmt, "i", $param_doctor_id);
    $param_doctor_id = $doctor_id;
    
    if(mysqli_stmt_execute($stmt)){
        $recent_patients = mysqli_stmt_get_result($stmt);
    }
    mysqli_stmt_close($stmt);
}

// Process patient search
$search_results = null;
if($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["search_patient"])){
    $search_term = trim($_POST["search_term"]);
    
    if(!empty($search_term)){
        $sql = "SELECT patient_id, name, dob, gender, phone, email
                FROM patients 
                WHERE name LIKE ? OR email LIKE ? OR phone LIKE ?
                ORDER BY name
                LIMIT 10";
        
        if($stmt = mysqli_prepare($conn, $sql)){
            $search_param = "%" . $search_term . "%";
            mysqli_stmt_bind_param($stmt, "sss", $search_param, $search_param, $search_param);
            
            if(mysqli_stmt_execute($stmt)){
                $search_results = mysqli_stmt_get_result($stmt);
            }
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
    <title>Doctor Dashboard</title>
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
            color: var(--secondary-color);
        }
        
        .appointment-date {
            background-color: var(--primary-color);
            color: white;
            text-align: center;
            padding: 10px;
            border-radius: 5px;
        }
        
        .patient-age {
            font-size: 0.85rem;
            color: #555;
        }
        
        .patient-gender {
            font-size: 0.85rem;
            color: #555;
        }
        
        .btn-primary {
            background-color: var(--secondary-color);
            border-color: var(--secondary-color);
        }
        
        .btn-primary:hover {
            background-color: #34495e;
            border-color: #34495e;
        }
        
        .btn-outline-primary {
            color: var(--secondary-color);
            border-color: var(--secondary-color);
        }
        
        .btn-outline-primary:hover {
            background-color: var(--secondary-color);
            color: white;
        }
        
        .badge-custom {
            font-size: 0.75rem;
            padding: 5px 10px;
            border-radius: 20px;
        }
        
        .badge-scheduled {
            background-color: var(--primary-color);
        }
        
        .badge-completed {
            background-color: var(--success-color);
        }
        
        .badge-cancelled {
            background-color: var(--danger-color);
        }
        
        .doctor-profile {
            display: flex;
            align-items: center;
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
        }
        
        .doctor-stats {
            margin-top: 20px;
            display: flex;
            justify-content: space-between;
        }
        
        .stat-card {
            flex: 1;
            text-align: center;
            padding: 10px;
            border-right: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .stat-card:last-child {
            border-right: none;
        }
        
        .stat-value {
            font-size: 1.8rem;
            font-weight: 600;
            margin-bottom: 5px;
        }
        
        .stat-label {
            font-size: 0.85rem;
            opacity: 0.8;
        }
        
        .search-container {
            margin-bottom: 20px;
        }
        
        .table th, .table td {
            vertical-align: middle;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Dashboard Header -->
        <div class="dashboard-header">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <div class="doctor-profile">
                        <div class="doctor-avatar">
                            <i class="fas fa-user-md"></i>
                        </div>
                        <div>
                            <h1>Dr. <?php echo isset($doctor_info) ? htmlspecialchars($doctor_info['name']) : 'Doctor'; ?></h1>
                            <p class="mb-0">
                                <span class="badge bg-light text-dark">
                                    <?php echo isset($doctor_info) ? htmlspecialchars($doctor_info['specialty']) : 'Specialist'; ?>
                                </span>
                                <span class="badge bg-light text-dark ms-2">
                                    <i class="fas fa-door-open me-1"></i>Room <?php echo isset($doctor_info) ? htmlspecialchars($doctor_info['room_number']) : 'N/A'; ?>
                                </span>
                            </p>
                        </div>
                    </div>
                    
                    <!-- Doctor Stats -->
                    <div class="doctor-stats">
                        <div class="stat-card">
                            <div class="stat-value text-warning">
                                <i class="fas fa-calendar-day"></i>
                                <?php 
                                echo isset($today_appointments) ? mysqli_num_rows($today_appointments) : '0'; 
                                ?>
                            </div>
                            <div class="stat-label">Today's Appointments</div>
                        </div>
                        
                        <div class="stat-card">
                            <div class="stat-value text-info">
                                <i class="fas fa-calendar-alt"></i>
                                <?php 
                                // Count total upcoming appointments (query for this specific stat)
                                $upcoming_count = 0;
                                $sql = "SELECT COUNT(*) as count FROM appointments WHERE doctor_id = ? AND status = 'Scheduled'";
                                if($stmt = mysqli_prepare($conn, $sql)){
                                    mysqli_stmt_bind_param($stmt, "i", $doctor_id);
                                    if(mysqli_stmt_execute($stmt)){
                                        $result = mysqli_stmt_get_result($stmt);
                                        if($row = mysqli_fetch_assoc($result)){
                                            $upcoming_count = $row['count'];
                                        }
                                    }
                                    mysqli_stmt_close($stmt);
                                }
                                echo $upcoming_count;
                                ?>
                            </div>
                            <div class="stat-label">Total Appointments</div>
                        </div>
                        
                        <div class="stat-card">
                            <div class="stat-value text-success">
                                <i class="fas fa-user-check"></i>
                                <?php 
                                // Count total patients seen (query for this specific stat)
                                $patients_count = 0;
                                $sql = "SELECT COUNT(DISTINCT patient_id) as count FROM appointments WHERE doctor_id = ?";
                                if($stmt = mysqli_prepare($conn, $sql)){
                                    mysqli_stmt_bind_param($stmt, "i", $doctor_id);
                                    if(mysqli_stmt_execute($stmt)){
                                        $result = mysqli_stmt_get_result($stmt);
                                        if($row = mysqli_fetch_assoc($result)){
                                            $patients_count = $row['count'];
                                        }
                                    }
                                    mysqli_stmt_close($stmt);
                                }
                                echo $patients_count;
                                ?>
                            </div>
                            <div class="stat-label">Patients Seen</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 text-md-end">
                    <div class="btn-group">
                        <a href="profile.php" class="btn btn-light"><i class="fas fa-user-circle me-2"></i>My Profile</a>
                        <a href="logout.php" class="btn btn-outline-light"><i class="fas fa-sign-out-alt me-2"></i>Logout</a>
                    </div>
                    <p class="mt-3 mb-0 text-light">
                        <i class="fas fa-clock me-1"></i> <?php echo date('l, F d, Y'); ?>
                    </p>
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
                            <a class="nav-link active" href="doctor_dashboard.php">
                                <i class="fas fa-home nav-icon"></i> Dashboard
                            </a>
                            <a class="nav-link" href="doctor_schedule.php">
                                <i class="fas fa-calendar-week nav-icon"></i> My Schedule
                            </a>
                            <a class="nav-link" href="appointments.php">
                                <i class="fas fa-calendar-check nav-icon"></i> Appointments
                            </a>
                            <a class="nav-link" href="doctor_medical_records.php">
                                <i class="fas fa-file-medical nav-icon"></i> Medical Records
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Main Content -->
            <div class="col-lg-9">
                <div class="row">
                    <!-- Today's Appointments -->
                    <div class="col-12 mb-4">
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <span><i class="fas fa-calendar-day me-2"></i> Today's Appointments</span>
                                <a href="doctor_schedule.php" class="btn btn-sm btn-outline-primary">Full Schedule</a>
                            </div>
                            <div class="card-body p-0">
                                <?php if(isset($today_appointments) && mysqli_num_rows($today_appointments) > 0): ?>
                                    <div class="table-responsive">
                                        <table class="table table-hover mb-0">
                                            <thead class="table-light">
                                                <tr>
                                                    <th>Time</th>
                                                    <th>Patient</th>
                                                    <th>Age/Gender</th>
                                                    <th>Status</th>
                                                    <th>Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php while($row = mysqli_fetch_array($today_appointments)): ?>
                                                    <tr>
                                                        <td>
                                                            <strong><?php echo date('h:i A', strtotime($row['appointment_datetime'])); ?></strong>
                                                        </td>
                                                        <td><?php echo htmlspecialchars($row['patient_name']); ?></td>
                                                        <td>
                                                            <?php echo date_diff(date_create($row['dob']), date_create('now'))->y; ?> yrs,
                                                            <?php echo htmlspecialchars($row['gender']); ?>
                                                        </td>
                                                        <td>
                                                            <?php if($row['status'] == 'Scheduled'): ?>
                                                                <span class="badge badge-custom badge-scheduled">Scheduled</span>
                                                            <?php elseif($row['status'] == 'Completed'): ?>
                                                                <span class="badge badge-custom badge-completed">Completed</span>
                                                            <?php else: ?>
                                                                <span class="badge badge-custom badge-cancelled">Cancelled</span>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td>
                                                            <a href="view_patient.php?id=<?php echo $row['appointment_id']; ?>" class="btn btn-sm btn-outline-info">
                                                                <i class="fas fa-eye"></i> View
                                                            </a>
                                                            <?php if($row['status'] == 'Scheduled'): ?>
                                                                <a href="complete_appointment.php?id=<?php echo $row['appointment_id']; ?>" class="btn btn-sm btn-outline-success">
                                                                    <i class="fas fa-check"></i> Complete
                                                                </a>
                                                            <?php endif; ?>
                                                            <?php if($row['status'] == 'Scheduled'): ?>
                                                                <a href="add_record.php?appointment_id=<?php echo $row['appointment_id']; ?>" class="btn btn-sm btn-outline-primary">
                                                                    <i class="fas fa-plus"></i> Record
                                                                </a>
                                                            <?php endif; ?>
                                                        </td>
                                                    </tr>
                                                <?php endwhile; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php else: ?>
                                    <div class="p-4 text-center">
                                        <i class="fas fa-calendar-times text-muted mb-3" style="font-size: 3rem;"></i>
                                        <p class="mb-0">No appointments scheduled for today.</p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <!-- Patient Search -->
                    <div class="col-md-12 mb-4" id="patient-search">
                        <div class="card">
                            <div class="card-header">
                                <i class="fas fa-search me-2"></i> Patient Search
                            </div>
                            <div class="card-body">
                                <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" class="mb-4">
                                    <div class="input-group">
                                        <input type="text" name="search_term" class="form-control" placeholder="Search by name, email or phone...">
                                        <button type="submit" name="search_patient" class="btn btn-primary">
                                            <i class="fas fa-search"></i> Search
                                        </button>
                                    </div>
                                </form>
                                
                                <?php if(isset($search_results) && mysqli_num_rows($search_results) > 0): ?>
                                    <h5>Search Results:</h5>
                                    <div class="table-responsive">
                                        <table class="table table-hover">
                                            <thead>
                                                <tr>
                                                    <th>Name</th>
                                                    <th>Age</th>
                                                    <th>Gender</th>
                                                    <th>Contact</th>
                                                    <th>Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php while($row = mysqli_fetch_array($search_results)): ?>
                                                    <tr>
                                                        <td><?php echo htmlspecialchars($row['name']); ?></td>
                                                        <td><?php echo date_diff(date_create($row['dob']), date_create('now'))->y; ?> years</td>
                                                        <td><?php echo htmlspecialchars($row['gender']); ?></td>
                                                        <td>
                                                            <i class="fas fa-phone me-1"></i> <?php echo htmlspecialchars($row['phone']); ?><br>
                                                            <i class="fas fa-envelope me-1"></i> <?php echo htmlspecialchars($row['email']); ?>
                                                        </td>
                                                        <td>
                                                            <a href="view_patient_details.php?id=<?php echo $row['patient_id']; ?>" class="btn btn-sm btn-outline-info">
                                                                <i class="fas fa-user"></i> View Profile
                                                            </a>
                                                            <a href="patient_records.php?id=<?php echo $row['patient_id']; ?>" class="btn btn-sm btn-outline-secondary">
                                                                <i class="fas fa-file-medical"></i> Records
                                                            </a>
                                                            <a href="create_appointment.php?patient_id=<?php echo $row['patient_id']; ?>" class="btn btn-sm btn-outline-primary">
                                                                <i class="fas fa-calendar-plus"></i> Schedule
                                                            </a>
                                                        </td>
                                                    </tr>
                                                <?php endwhile; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php elseif(isset($search_results)): ?>
                                    <div class="alert alert-info">
                                        <i class="fas fa-info-circle me-2"></i> No patients found matching your search criteria.
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <!-- Upcoming Appointments -->
                    <div class="col-md-6 mb-4">
                        <div class="card h-100">
                            <div class="card-header">
                                <i class="fas fa-calendar-alt me-2"></i> Upcoming Appointments
                            </div>
                            <div class="card-body">
                                <?php if(isset($upcoming_appointments) && mysqli_num_rows($upcoming_appointments) > 0): ?>
                                    <div class="list-group">
                                        <?php while($row = mysqli_fetch_array($upcoming_appointments)): ?>
                                            <div class="list-group-item list-group-item-action">
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <div>
                                                        <div class="fw-bold"><?php echo htmlspecialchars($row['patient_name']); ?></div>
                                                        <div class="text-muted small">
                                                            <i class="fas fa-calendar me-1"></i> 
                                                            <?php echo date('M d, Y', strtotime($row['appointment_datetime'])); ?>
                                                            <i class="fas fa-clock ms-2 me-1"></i>
                                                            <?php echo date('h:i A', strtotime($row['appointment_datetime'])); ?>
                                                        </div>
                                                    </div>
                                                    <span class="badge bg-primary rounded-pill">
                                                        <?php echo htmlspecialchars($row['status']); ?>
                                                    </span>
                                                </div>
                                            </div>
                                        <?php endwhile; ?>
                                    </div>
                                <?php else: ?>
                                    <div class="text-center p-3">
                                        <i class="fas fa-calendar text-muted mb-3" style="font-size: 2rem;"></i>
                                        <p>No upcoming appointments scheduled.</p>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="card-footer text-end">
                                <a href="doctor_schedule.php" class="btn btn-sm btn-primary">View All</a>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Recent Patients -->
                    <div class="col-md-6 mb-4">
                        <div class="card h-100">
                            <div class="card-header">
                                <i class="fas fa-users me-2"></i> Recent Patients
                            </div>
                            <div class="card-body">
                                <?php if(isset($recent_patients) && mysqli_num_rows($recent_patients) > 0): ?>
                                    <div class="list-group">
                                        <?php while($row = mysqli_fetch_array($recent_patients)): ?>
                                            <div class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                                                <div>
                                                    <div class="fw-bold"><?php echo htmlspecialchars($row['name']); ?></div>
                                                    <div class="text-muted small">
                                                        <span class="me-2">
                                                            <i class="fas fa-birthday-cake me-1"></i> 
                                                            <?php echo date_diff(date_create($row['dob']), date_create('now'))->y; ?> years
                                                        </span>
                                                        <span>
                                                            <i class="fas fa-venus-mars me-1"></i> 
                                                            <?php echo htmlspecialchars($row['gender']); ?>
                                                        </span>
                                                    </div>
                                                </div>
                                                <a href="view_patient_details.php?id=<?php echo $row['patient_id']; ?>" class="btn btn-sm btn-outline-primary">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                            </div>
                                        <?php endwhile; ?>
                                    </div>
                                <?php else: ?>
                                    <div class="text-center p-3">
                                        <i class="fas fa-user-injured text-muted mb-3" style="font-size: 2rem;"></i>
                                        <p>No recent patients found.</p>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="card-footer text-end">
                                <a href="appointments.php" class="btn btn-sm btn-primary">View All Appointments</a>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Medical Records Access -->
                <div class="card mb-4">
                    <div class="card-header">
                        <i class="fas fa-file-medical me-2"></i> Recent Medical Records
                    </div>
                    <div class="card-body">
                        <?php
                        // Get recent medical records
                        $sql = "SELECT mr.record_id, mr.diagnosis, mr.created_at, 
                                       p.name as patient_name, p.patient_id, a.appointment_id
                                FROM medical_records mr
                                JOIN patients p ON mr.patient_id = p.patient_id
                                JOIN appointments a ON mr.appointment_id = a.appointment_id
                                WHERE mr.doctor_id = ?
                                ORDER BY mr.created_at DESC
                                LIMIT 5";
                        
                        if($stmt = mysqli_prepare($conn, $sql)){
                            mysqli_stmt_bind_param($stmt, "i", $doctor_id);
                            
                            if(mysqli_stmt_execute($stmt)){
                                $medical_records = mysqli_stmt_get_result($stmt);
                            }
                            mysqli_stmt_close($stmt);
                        }
                        ?>
                        
                        <?php if(isset($medical_records) && mysqli_num_rows($medical_records) > 0): ?>
                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Record ID</th>
                                            <th>Patient</th>
                                            <th>Diagnosis</th>
                                            <th>Date</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while($row = mysqli_fetch_array($medical_records)): ?>
                                            <tr>
                                                <td>#<?php echo $row['record_id']; ?></td>
                                                <td>
                                                    <a href="view_patient_details.php?id=<?php echo $row['patient_id']; ?>">
                                                        <?php echo htmlspecialchars($row['patient_name']); ?>
                                                    </a>
                                                </td>
                                                <td>
                                                    <?php 
                                                    // Truncate long diagnoses
                                                    $diagnosis = $row['diagnosis'];
                                                    echo (strlen($diagnosis) > 50) ? htmlspecialchars(substr($diagnosis, 0, 50) . '...') : htmlspecialchars($diagnosis);
                                                    ?>
                                                </td>
                                                <td><?php echo date('M d, Y', strtotime($row['created_at'])); ?></td>
                                                <td>
                                                    <a href="view_medical_record.php?id=<?php echo $row['record_id']; ?>" class="btn btn-sm btn-outline-info">
                                                        <i class="fas fa-eye"></i> View
                                                    </a>
                                                    <a href="edit_medical_record.php?id=<?php echo $row['record_id']; ?>" class="btn btn-sm btn-outline-primary">
                                                        <i class="fas fa-edit"></i> Edit
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i> No recent medical records found.
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="card-footer text-end">
                        <a href="medical_records.php" class="btn btn-primary">View All Records</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.1.3/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Display current time
            function updateClock() {
                const now = new Date();
                const hours = now.getHours().toString().padStart(2, '0');
                const minutes = now.getMinutes().toString().padStart(2, '0');
                const seconds = now.getSeconds().toString().padStart(2, '0');
                const timeString = `${hours}:${minutes}:${seconds}`;
                
                // Update a clock element if you add one to the HTML
                // document.getElementById('clock').textContent = timeString;
            }
            
            // Update clock every second
            setInterval(updateClock, 1000);
            updateClock(); // Initial call
        });
    </script>
</body>
</html>