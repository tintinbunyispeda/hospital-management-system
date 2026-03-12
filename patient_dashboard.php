<?php
// patient_dashboard.php - Dashboard for patients
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

// Get all specialties
$sql = "SELECT specialty_id, name FROM specialties ORDER BY name";
if($stmt = mysqli_prepare($conn, $sql)){
    if(mysqli_stmt_execute($stmt)){
        $specialties_result = mysqli_stmt_get_result($stmt);
    }
    mysqli_stmt_close($stmt);
}

// Get upcoming appointments
$sql = "SELECT a.appointment_id, a.appointment_datetime, a.status, 
               d.name as doctor_name, s.name as specialty 
        FROM appointments a
        JOIN doctors d ON a.doctor_id = d.doctor_id
        JOIN specialties s ON d.specialty_id = s.specialty_id
        WHERE a.patient_id = ? AND a.status = 'Scheduled'
        ORDER BY a.appointment_datetime
        LIMIT 5";

if($stmt = mysqli_prepare($conn, $sql)){
    mysqli_stmt_bind_param($stmt, "i", $patient_id);
    
    if(mysqli_stmt_execute($stmt)){
        $appointments = mysqli_stmt_get_result($stmt);
    }
    mysqli_stmt_close($stmt);
}

// Get health tips (in a real system, these would come from a database)
$health_tips = [
    [
        "title" => "Maintaining Heart Health",
        "content" => "Regular exercise, a balanced diet, and avoiding smoking can significantly reduce your risk of heart disease.",
        "icon" => "fa-heart"
    ],
    [
        "title" => "Diabetes Prevention",
        "content" => "Maintain a healthy weight, stay physically active, and limit processed foods to help prevent type 2 diabetes.",
        "icon" => "fa-apple-alt"
    ],
    [
        "title" => "Mental Wellness",
        "content" => "Practice mindfulness, ensure adequate sleep, and maintain social connections to support mental health.",
        "icon" => "fa-brain"
    ],
    [
        "title" => "COVID-19 Safety",
        "content" => "Stay up to date with vaccinations, wash hands frequently, and follow local health guidelines.",
        "icon" => "fa-virus-slash"
    ]
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patient Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.1.3/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/slick-carousel/1.8.1/slick.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/slick-carousel/1.8.1/slick-theme.min.css">
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
        
        .appointment-date {
            background-color: var(--primary-color);
            color: white;
            text-align: center;
            padding: 10px;
            border-radius: 5px;
        }
        
        .doctor-card {
            text-align: center;
            padding: 15px;
            cursor: pointer;
        }
        
        .doctor-card .doctor-img {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            margin: 0 auto 15px;
            border: 4px solid var(--light-color);
            padding: 2px;
        }
        
        .doctor-specialty {
            color: var(--primary-color);
            font-size: 0.9rem;
            margin-bottom: 5px;
        }
        
        .doctor-time {
            font-size: 0.85rem;
            color: #777;
        }
        
        .specialty-card {
            padding: 15px;
            text-align: center;
            background-color: white;
            border-radius: 10px;
            margin: 0 10px;
            cursor: pointer;
            transition: all 0.3s ease;
            height: 200px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
        }
        
        .specialty-card:hover {
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            transform: translateY(-5px);
        }
        
        .specialty-icon {
            font-size: 2.5rem;
            margin-bottom: 15px;
            color: var(--primary-color);
        }
        
        .health-tip-icon {
            font-size: 2rem;
            margin-bottom: 10px;
            color: var(--primary-color);
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
        
        .quick-action {
            padding: 15px;
            border-radius: 10px;
            text-align: center;
            transition: all 0.3s ease;
            margin-bottom: 15px;
        }
        
        .quick-action:hover {
            transform: translateY(-3px);
        }
        
        .action-icon {
            font-size: 2rem;
            margin-bottom: 10px;
        }
        
        .specialty-slider .slick-prev, 
        .specialty-slider .slick-next {
            z-index: 10;
            width: 40px;
            height: 40px;
            background: var(--primary-color);
            border-radius: 50%;
            opacity: 0.8;
        }
        
        .specialty-slider .slick-prev:hover, 
        .specialty-slider .slick-next:hover {
            opacity: 1;
            background: var(--primary-color);
        }
        
        .specialty-slider .slick-prev:before, 
        .specialty-slider .slick-next:before {
            font-family: 'Font Awesome 6 Free';
            font-weight: 900;
        }
        
        .specialty-slider .slick-prev:before {
            content: '\f053';
        }
        
        .specialty-slider .slick-next:before {
            content: '\f054';
        }
        
        .specialty-slider .slick-prev {
            left: -10px;
        }
        
        .specialty-slider .slick-next {
            right: -10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Dashboard Header -->
        <div class="dashboard-header">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <h1>Welcome, <?php echo isset($patient) ? htmlspecialchars($patient['name']) : 'Patient'; ?></h1>
                    <p class="mb-0">Your health is our priority. How can we help you today?</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <div class="btn-group">
                        <a href="profile.php" class="btn btn-light"><i class="fas fa-user-circle me-2"></i>My Profile</a>
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
                            <a class="nav-link active" href="patient_dashboard.php">
                                <i class="fas fa-home nav-icon"></i> Dashboard
                            </a>
                            <a class="nav-link" href="appointments.php">
                                <i class="fas fa-calendar-check nav-icon"></i> My Appointments
                            </a>
                            <a class="nav-link" href="book_appointment.php">
                                <i class="fas fa-calendar-plus nav-icon"></i> Book Appointment
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
                
                <!-- Quick Actions -->
                <div class="card mt-4">
                    <div class="card-header">
                        <i class="fas fa-bolt me-2"></i> Quick Actions
                    </div>
                    <div class="card-body">
                        <div class="text-center">
                            <div class="quick-action">
                                <div class="action-icon text-primary">
                                    <i class="fas fa-calendar-plus"></i>
                                </div>
                                <a href="book_appointment.php" class="btn btn-primary">Book Appointment</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Main Content -->
            <div class="col-lg-9">
                <div class="row">
                    <!-- Upcoming Appointments -->
                    <div class="col-12 mb-4">
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <span><i class="fas fa-calendar-alt me-2"></i> Upcoming Appointments</span>
                                <a href="appointments.php" class="btn btn-sm btn-outline-primary">View All</a>
                            </div>
                            <div class="card-body p-0">
                                <?php if(isset($appointments) && mysqli_num_rows($appointments) > 0): ?>
                                    <div class="table-responsive">
                                        <table class="table table-hover mb-0">
                                            <thead class="table-light">
                                                <tr>
                                                    <th>Date & Time</th>
                                                    <th>Doctor</th>
                                                    <th>Specialty</th>
                                                    <th>Status</th>
                                                    <th>Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php while($row = mysqli_fetch_array($appointments)): ?>
                                                    <tr>
                                                        <td>
                                                            <strong><?php echo date('M d, Y', strtotime($row['appointment_datetime'])); ?></strong><br>
                                                            <?php echo date('h:i A', strtotime($row['appointment_datetime'])); ?>
                                                        </td>
                                                        <td><?php echo htmlspecialchars($row['doctor_name']); ?></td>
                                                        <td><?php echo htmlspecialchars($row['specialty']); ?></td>
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
                                                            <a href="view_appointment.php?id=<?php echo $row['appointment_id']; ?>" class="btn btn-sm btn-outline-info">
                                                                <i class="fas fa-eye"></i>
                                                            </a>
                                                            <?php if($row['status'] == 'Scheduled'): ?>
                                                                <a href="cancel_appointment.php?id=<?php echo $row['appointment_id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Are you sure you want to cancel this appointment?')">
                                                                    <i class="fas fa-times"></i>
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
                                        <p class="mb-0">No upcoming appointments. Would you like to book one now?</p>
                                        <a href="book_appointment.php" class="btn btn-primary mt-3">Book an Appointment</a>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Find Doctors by Specialty -->
                <div class="card mb-4">
                    <div class="card-header">
                        <i class="fas fa-user-md me-2"></i> Find Doctors by Specialty
                    </div>
                    <div class="card-body">
                        <div class="specialty-slider">
                            <?php 
                            // Icons for specialties
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
                            
                            if(isset($specialties_result) && mysqli_num_rows($specialties_result) > 0): 
                                while($specialty = mysqli_fetch_array($specialties_result)):
                                    $icon = isset($specialty_icons[$specialty['name']]) ? $specialty_icons[$specialty['name']] : 'fa-user-md';
                            ?>
                                <div class="specialty-slide">
                                    <a href="find_doctor.php" class="text-decoration-none">
                                        <div class="specialty-card">
                                            <div class="specialty-icon">
                                                <i class="fas <?php echo $icon; ?>"></i>
                                            </div>
                                            <h5><?php echo htmlspecialchars($specialty['name']); ?></h5>
                                            <span class="text-muted">Find Specialists</span>
                                        </div>
                                    </a>
                                </div>
                            <?php 
                                endwhile; 
                            endif; 
                            ?>
                        </div>
                    </div>
                </div>
                
                <!-- Health Tips -->
                <div class="card">
                    <div class="card-header">
                        <i class="fas fa-heartbeat me-2"></i> Health Tips
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <?php foreach($health_tips as $tip): ?>
                                <div class="col-lg-6 col-md-6 mb-4">
                                    <div class="card h-100">
                                        <div class="card-body text-center">
                                            <div class="health-tip-icon">
                                                <i class="fas <?php echo $tip['icon']; ?>"></i>
                                            </div>
                                            <h5 class="card-title"><?php echo $tip['title']; ?></h5>
                                            <p class="card-text"><?php echo $tip['content']; ?></p>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.1.3/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/slick-carousel/1.8.1/slick.min.js"></script>
    <script>
        // Initialize Slick Carousel for specialties
        $(document).ready(function(){
            $('.specialty-slider').slick({
                dots: true,
                infinite: true,
                speed: 300,
                slidesToShow: 3,
                slidesToScroll: 1,
                autoplay: true,
                autoplaySpeed: 3000,
                responsive: [
                    {
                        breakpoint: 992,
                        settings: {
                            slidesToShow: 2,
                            slidesToScroll: 1
                        }
                    },
                    {
                        breakpoint: 576,
                        settings: {
                            slidesToShow: 1,
                            slidesToScroll: 1
                        }
                    }
                ]
            });
        });
    </script>
</body>
</html>