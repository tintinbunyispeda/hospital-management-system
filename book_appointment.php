<?php
// book_appointment.php - Book a new appointment
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

// Process specialty selection or show all doctors
$selected_specialty = null;
$doctors = [];

if(isset($_GET['specialty']) && !empty($_GET['specialty'])){
    $selected_specialty = $_GET['specialty'];
    
    // Get doctors in the selected specialty - SIMPLIFIED QUERY
    $sql = "SELECT d.doctor_id, d.name, s.name as specialty_name, s.specialty_id
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
    // Get all doctors - SIMPLIFIED QUERY
    $sql = "SELECT d.doctor_id, d.name, s.name as specialty_name, s.specialty_id
            FROM doctors d
            JOIN specialties s ON d.specialty_id = s.specialty_id
            ORDER BY d.name";
            
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

// Handle form submission
$success_message = "";
$error_message = "";

if($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['schedule_appointment'])){
    $doctor_id = $_POST['doctor_id'];
    $appointment_date = $_POST['appointment_date'];
    $appointment_time = $_POST['appointment_time'];
    $notes = $_POST['notes'];
    
    // Combine date and time
    $appointment_datetime = $appointment_date . ' ' . $appointment_time . ':00';
    
    // Insert new appointment
    $sql = "INSERT INTO appointments (patient_id, doctor_id, appointment_datetime, notes, status) 
            VALUES (?, ?, ?, ?, 'Scheduled')";
            
    if($stmt = mysqli_prepare($conn, $sql)){
        mysqli_stmt_bind_param($stmt, "iiss", $patient_id, $doctor_id, $appointment_datetime, $notes);
        
        if(mysqli_stmt_execute($stmt)){
            $success_message = "Appointment scheduled successfully!";
        } else {
            $error_message = "Something went wrong. Please try again later.";
        }
        
        mysqli_stmt_close($stmt);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book Appointment</title>
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
            border: 1px solid #e0e0e0;
            border-radius: 10px;
            margin-bottom: 20px;
            padding: 15px;
            transition: all 0.3s ease;
        }
        
        .doctor-card:hover {
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }
        
        .doctor-info {
            margin-bottom: 15px;
        }
        
        .doctor-name {
            font-weight: 600;
            font-size: 1.2rem;
            color: var(--secondary-color);
            margin-bottom: 5px;
        }
        
        .doctor-specialty {
            color: var(--primary-color);
            font-size: 0.9rem;
            margin-bottom: 10px;
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }
        
        .btn-primary:hover {
            background-color: #2980b9;
            border-color: #2980b9;
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
        
        .modal-header {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
        }
        
        .form-label {
            font-weight: 500;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Page Header -->
        <div class="dashboard-header">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <h1>Book an Appointment</h1>
                    <p class="mb-0">Find a doctor and schedule your appointment</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <a href="patient_dashboard.php" class="btn btn-light"><i class="fas fa-home me-2"></i>Back to Dashboard</a>
                </div>
            </div>
        </div>
        
        <div class="row">
            <!-- Left Sidebar - Specialties Filter -->
            <div class="col-lg-3 mb-4">
                <div class="card">
                    <div class="card-header">
                        <i class="fas fa-filter me-2"></i> Filter by Specialty
                    </div>
                    <div class="card-body">
                        <div class="nav flex-column nav-pills specialty-filter">
                            <a class="nav-link <?php echo !$selected_specialty ? 'active' : ''; ?>" href="book_appointment.php">
                                All Specialties
                            </a>
                            <?php 
                            // Reset specialties result set
                            mysqli_data_seek($specialties_result, 0);
                            while($specialty = mysqli_fetch_array($specialties_result)): 
                            ?>
                                <a class="nav-link <?php echo $selected_specialty == $specialty['specialty_id'] ? 'active' : ''; ?>" 
                                   href="book_appointment.php?specialty=<?php echo $specialty['specialty_id']; ?>">
                                    <?php echo htmlspecialchars($specialty['name']); ?>
                                </a>
                            <?php endwhile; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Main Content - Doctor Listings -->
            <div class="col-lg-9">
                <?php if(!empty($success_message)): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?php echo $success_message; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>
                
                <?php if(!empty($error_message)): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?php echo $error_message; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>
                
                <div class="card mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <span>
                            <i class="fas fa-user-md me-2"></i> 
                            <?php 
                            if($selected_specialty) {
                                $specialty_name = "";
                                mysqli_data_seek($specialties_result, 0);
                                while($specialty = mysqli_fetch_array($specialties_result)) {
                                    if($specialty['specialty_id'] == $selected_specialty) {
                                        $specialty_name = $specialty['name'];
                                        break;
                                    }
                                }
                                echo htmlspecialchars($specialty_name) . " Doctors";
                            } else {
                                echo "All Doctors";
                            }
                            ?>
                        </span>
                        <span class="text-muted"><?php echo count($doctors); ?> doctors found</span>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <?php if(count($doctors) > 0): ?>
                                <?php foreach($doctors as $doctor): ?>
                                    <div class="col-lg-6">
                                        <div class="doctor-card">
                                            <div class="doctor-info">
                                                <div class="doctor-name">Dr. <?php echo htmlspecialchars($doctor['name']); ?></div>
                                                <div class="doctor-specialty"><?php echo htmlspecialchars($doctor['specialty_name']); ?></div>
                                            </div>
                                            <button type="button" class="btn btn-primary book-appointment-btn w-100" 
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#appointmentModal" 
                                                    data-doctor-id="<?php echo $doctor['doctor_id']; ?>"
                                                    data-doctor-name="<?php echo htmlspecialchars($doctor['name']); ?>"
                                                    data-specialty="<?php echo htmlspecialchars($doctor['specialty_name']); ?>">
                                                <i class="fas fa-calendar-plus me-2"></i> Book Appointment
                                            </button>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="col-12 text-center py-5">
                                    <i class="fas fa-user-md text-muted mb-3" style="font-size: 4rem;"></i>
                                    <h4>No doctors found</h4>
                                    <p class="text-muted">Please try selecting a different specialty</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Appointment Booking Modal -->
    <div class="modal fade" id="appointmentModal" tabindex="-1" aria-labelledby="appointmentModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="appointmentModalLabel">Book an Appointment</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="post" action="book_appointment.php<?php echo $selected_specialty ? '?specialty=' . $selected_specialty : ''; ?>">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Doctor</label>
                            <input type="text" class="form-control doctor-name-display" readonly>
                            <input type="hidden" name="doctor_id" id="doctor_id">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Specialty</label>
                            <input type="text" class="form-control specialty-display" readonly>
                        </div>
                        <div class="mb-3">
                            <label for="appointment_date" class="form-label">Date</label>
                            <input type="date" class="form-control" id="appointment_date" name="appointment_date" min="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="appointment_time" class="form-label">Time</label>
                            <select class="form-select" id="appointment_time" name="appointment_time" required>
                                <option value="">Select a time</option>
                                <option value="09:00">9:00 AM</option>
                                <option value="10:00">10:00 AM</option>
                                <option value="11:00">11:00 AM</option>
                                <option value="13:00">1:00 PM</option>
                                <option value="14:00">2:00 PM</option>
                                <option value="15:00">3:00 PM</option>
                                <option value="16:00">4:00 PM</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="notes" class="form-label">Notes (optional)</label>
                            <textarea class="form-control" id="notes" name="notes" rows="3" placeholder="Please provide any additional information about your visit"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="schedule_appointment" class="btn btn-primary">Schedule Appointment</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.1.3/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Handle appointment modal
            const appointmentModal = document.getElementById('appointmentModal');
            if (appointmentModal) {
                appointmentModal.addEventListener('show.bs.modal', function(event) {
                    const button = event.relatedTarget;
                    const doctorId = button.getAttribute('data-doctor-id');
                    const doctorName = button.getAttribute('data-doctor-name');
                    const specialty = button.getAttribute('data-specialty');
                    
                    // Update modal content
                    this.querySelector('.doctor-name-display').value = 'Dr. ' + doctorName;
                    this.querySelector('.specialty-display').value = specialty;
                    this.querySelector('#doctor_id').value = doctorId;
                });
            }
        });
    </script>
</body>
</html>