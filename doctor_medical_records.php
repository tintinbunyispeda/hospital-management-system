<?php
// doctor_medical_records.php - View and manage patient medical records for doctors
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

// Process filter
$filter_patient = "";
$filter_year = "";
$filter_query = "";
$filter_params = array($doctor_id);
$filter_types = "i";

if($_SERVER["REQUEST_METHOD"] == "GET" && isset($_GET["filter"])){
    if(!empty($_GET["patient_id"])){
        $filter_patient = $_GET["patient_id"];
        $filter_query .= " AND mr.patient_id = ?";
        $filter_params[] = $filter_patient;
        $filter_types .= "i";
    }
    
    if(!empty($_GET["year"])){
        $filter_year = $_GET["year"];
        $filter_query .= " AND YEAR(mr.created_at) = ?";
        $filter_params[] = $filter_year;
        $filter_types .= "i";
    }
}

// Get medical records with filter
$sql = "SELECT mr.record_id, mr.diagnosis, mr.prescription, mr.notes, mr.created_at, 
               p.name as patient_name, p.patient_id, p.dob, p.gender,
               a.appointment_datetime
        FROM medical_records mr
        JOIN patients p ON mr.patient_id = p.patient_id
        JOIN appointments a ON mr.appointment_id = a.appointment_id
        WHERE mr.doctor_id = ?" . $filter_query . "
        ORDER BY mr.created_at DESC
        LIMIT 50";

$medical_records = false;
if($stmt = mysqli_prepare($conn, $sql)){
    mysqli_stmt_bind_param($stmt, $filter_types, ...$filter_params);
    
    if(mysqli_stmt_execute($stmt)){
        $medical_records = mysqli_stmt_get_result($stmt);
    }
    mysqli_stmt_close($stmt);
}

// Get years for filter dropdown
$sql = "SELECT DISTINCT YEAR(created_at) as year 
        FROM medical_records 
        WHERE doctor_id = ? 
        ORDER BY year DESC";

$years = array();
if($stmt = mysqli_prepare($conn, $sql)){
    mysqli_stmt_bind_param($stmt, "i", $doctor_id);
    
    if(mysqli_stmt_execute($stmt)){
        $result = mysqli_stmt_get_result($stmt);
        while($row = mysqli_fetch_array($result)){
            $years[] = $row['year'];
        }
    }
    mysqli_stmt_close($stmt);
}

// Get patients for filter dropdown
$sql = "SELECT DISTINCT p.patient_id, p.name
        FROM medical_records mr
        JOIN patients p ON mr.patient_id = p.patient_id
        WHERE mr.doctor_id = ?
        ORDER BY p.name";

$patients = array();
if($stmt = mysqli_prepare($conn, $sql)){
    mysqli_stmt_bind_param($stmt, "i", $doctor_id);
    
    if(mysqli_stmt_execute($stmt)){
        $result = mysqli_stmt_get_result($stmt);
        while($row = mysqli_fetch_array($result)){
            $patients[] = $row;
        }
    }
    mysqli_stmt_close($stmt);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Medical Records - Doctor Portal</title>
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
        }
        
        .table th, .table td {
            vertical-align: middle;
        }
        
        .record-info {
            font-size: 0.85rem;
            color: #6c757d;
        }
        
        .modal-header {
            background-color: var(--secondary-color);
            color: white;
        }
        
        .diagnosis-badge {
            background-color: rgba(52, 152, 219, 0.1);
            color: var(--primary-color);
            border: 1px solid var(--primary-color);
            font-size: 0.8rem;
            padding: 3px 8px;
            border-radius: 12px;
        }
        
        .btn-view {
            background-color: var(--primary-color);
            color: white;
        }
        
        .btn-view:hover {
            background-color: #2980b9;
            color: white;
        }
        
        .btn-edit {
            background-color: var(--warning-color);
            color: white;
        }
        
        .btn-edit:hover {
            background-color: #d35400;
            color: white;
        }
        
        .filter-section {
            background-color: #f8f9fa;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Dashboard Header -->
        <div class="dashboard-header">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <h1><i class="fas fa-file-medical me-3"></i>Medical Records</h1>
                    <p class="mb-0">View and manage your patients' medical records</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <a href="doctor_dashboard.php" class="btn btn-light"><i class="fas fa-home me-2"></i>Dashboard</a>
                    <a href="add_record.php" class="btn btn-success ms-2"><i class="fas fa-plus me-2"></i>Add New Record</a>
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
                        <i class="fas fa-filter me-2"></i> Filter Records
                    </div>
                    <div class="card-body">
                        <form method="get" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                            <div class="mb-3">
                                <label for="patient_id" class="form-label">Patient</label>
                                <select name="patient_id" id="patient_id" class="form-select">
                                    <option value="">All Patients</option>
                                    <?php foreach($patients as $patient): ?>
                                        <option value="<?php echo $patient['patient_id']; ?>" <?php echo ($filter_patient == $patient['patient_id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($patient['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label for="year" class="form-label">Year</label>
                                <select name="year" id="year" class="form-select">
                                    <option value="">All Years</option>
                                    <?php foreach($years as $year): ?>
                                        <option value="<?php echo $year; ?>" <?php echo ($filter_year == $year) ? 'selected' : ''; ?>>
                                            <?php echo $year; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <button type="submit" name="filter" class="btn btn-primary w-100">
                                <i class="fas fa-filter me-2"></i>Apply Filter
                            </button>
                            
                            <?php if(!empty($filter_patient) || !empty($filter_year)): ?>
                                <a href="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" class="btn btn-outline-secondary w-100 mt-2">
                                    <i class="fas fa-times me-2"></i>Clear Filters
                                </a>
                            <?php endif; ?>
                        </form>
                    </div>
                </div>
            </div>
            
            <!-- Main Content -->
            <div class="col-lg-9">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <span><i class="fas fa-list me-2"></i>Medical Records List</span>
                        
                        <?php
                        // Display active filters if any
                        if(!empty($filter_patient) || !empty($filter_year)):
                            echo '<div class="badge bg-info text-white">';
                            echo '<i class="fas fa-filter me-1"></i> Filtered: ';
                            
                            $filters = array();
                            
                            if(!empty($filter_patient)){
                                foreach($patients as $patient){
                                    if($patient['patient_id'] == $filter_patient){
                                        $filters[] = 'Patient: ' . htmlspecialchars($patient['name']);
                                        break;
                                    }
                                }
                            }
                            
                            if(!empty($filter_year)){
                                $filters[] = 'Year: ' . $filter_year;
                            }
                            
                            echo implode(' | ', $filters);
                            echo '</div>';
                        endif;
                        ?>
                    </div>
                    <div class="card-body p-0">
                        <?php if(isset($medical_records) && mysqli_num_rows($medical_records) > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Record ID</th>
                                            <th>Patient</th>
                                            <th>Diagnosis</th>
                                            <th>Created</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while($row = mysqli_fetch_array($medical_records)): ?>
                                            <tr>
                                                <td>#<?php echo $row['record_id']; ?></td>
                                                <td>
                                                    <div>
                                                        <a href="view_patient_details.php?id=<?php echo $row['patient_id']; ?>" class="text-decoration-none">
                                                            <?php echo htmlspecialchars($row['patient_name']); ?>
                                                        </a>
                                                    </div>
                                                    <small class="text-muted">
                                                        <?php echo date_diff(date_create($row['dob']), date_create('now'))->y; ?> yrs, 
                                                        <?php echo htmlspecialchars($row['gender']); ?>
                                                    </small>
                                                </td>
                                                <td>
                                                    <span class="diagnosis-badge">
                                                        <?php 
                                                        $diagnosis = $row['diagnosis'];
                                                        echo (strlen($diagnosis) > 50) ? htmlspecialchars(substr($diagnosis, 0, 50) . '...') : htmlspecialchars($diagnosis);
                                                        ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <div><?php echo date('M d, Y', strtotime($row['created_at'])); ?></div>
                                                    <small class="text-muted"><?php echo date('h:i A', strtotime($row['created_at'])); ?></small>
                                                </td>
                                                <td>
                                                    <a href="view_medical_record.php?id=<?php echo $row['record_id']; ?>" class="btn btn-sm btn-view">
                                                        <i class="fas fa-eye"></i> View
                                                    </a>
                                                    <a href="edit_medical_record.php?id=<?php echo $row['record_id']; ?>" class="btn btn-sm btn-edit">
                                                        <i class="fas fa-edit"></i> Edit
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-info m-4">
                                <i class="fas fa-info-circle me-2"></i> No medical records found.
                                <?php if(!empty($filter_patient) || !empty($filter_year)): ?>
                                    <br>
                                    <a href="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" class="alert-link">
                                        <i class="fas fa-times me-1"></i>Clear filters to see all records
                                    </a>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Quick Stats -->
                <div class="row mt-4">
                    <div class="col-md-6 mb-4">
                        <div class="card h-100">
                            <div class="card-header">
                                <i class="fas fa-chart-bar me-2"></i> Records Statistics
                            </div>
                            <div class="card-body">
                                <?php
                                // Get records statistics
                                $stats = array();
                                
                                // Records by month (current year)
                                $sql = "SELECT 
                                           MONTH(created_at) as month, 
                                           COUNT(*) as count 
                                       FROM medical_records 
                                       WHERE doctor_id = ? AND YEAR(created_at) = YEAR(CURRENT_DATE) 
                                       GROUP BY MONTH(created_at)
                                       ORDER BY month";
                                
                                $monthly_stats = array_fill(1, 12, 0); // Initialize all months with 0
                                
                                if($stmt = mysqli_prepare($conn, $sql)){
                                    mysqli_stmt_bind_param($stmt, "i", $doctor_id);
                                    
                                    if(mysqli_stmt_execute($stmt)){
                                        $result = mysqli_stmt_get_result($stmt);
                                        while($row = mysqli_fetch_array($result)){
                                            $monthly_stats[$row['month']] = $row['count'];
                                        }
                                    }
                                    mysqli_stmt_close($stmt);
                                }
                                
                                // Most common diagnoses
                                $sql = "SELECT 
                                           diagnosis, 
                                           COUNT(*) as count 
                                       FROM medical_records 
                                       WHERE doctor_id = ? 
                                       GROUP BY diagnosis 
                                       ORDER BY count DESC 
                                       LIMIT 5";
                                
                                $diagnoses_stats = array();
                                
                                if($stmt = mysqli_prepare($conn, $sql)){
                                    mysqli_stmt_bind_param($stmt, "i", $doctor_id);
                                    
                                    if(mysqli_stmt_execute($stmt)){
                                        $result = mysqli_stmt_get_result($stmt);
                                        while($row = mysqli_fetch_array($result)){
                                            $diagnoses_stats[] = $row;
                                        }
                                    }
                                    mysqli_stmt_close($stmt);
                                }
                                
                                // Total records count
                                $sql = "SELECT COUNT(*) as total FROM medical_records WHERE doctor_id = ?";
                                $total_records = 0;
                                
                                if($stmt = mysqli_prepare($conn, $sql)){
                                    mysqli_stmt_bind_param($stmt, "i", $doctor_id);
                                    
                                    if(mysqli_stmt_execute($stmt)){
                                        $result = mysqli_stmt_get_result($stmt);
                                        if($row = mysqli_fetch_array($result)){
                                            $total_records = $row['total'];
                                        }
                                    }
                                    mysqli_stmt_close($stmt);
                                }
                                ?>
                                
                                <div class="text-center mb-4">
                                    <h2 class="display-5 fw-bold text-primary"><?php echo $total_records; ?></h2>
                                    <p class="lead">Total Medical Records</p>
                                </div>
                                
                                <h6 class="fw-bold">Records by Month (Current Year)</h6>
                                <div class="progress-stacked mb-4">
                                    <?php 
                                    $colors = ['#3498db', '#2ecc71', '#9b59b6', '#e74c3c', '#f39c12', '#1abc9c', '#34495e', '#d35400', '#27ae60', '#2980b9', '#8e44ad', '#c0392b'];
                                    $max_records = max($monthly_stats);
                                    
                                    for($i = 1; $i <= 12; $i++): 
                                        if($monthly_stats[$i] > 0):
                                            $percentage = ($monthly_stats[$i] / $max_records) * 100;
                                    ?>
                                        <div class="progress" role="progressbar" style="width: <?php echo $percentage; ?>%;" data-bs-toggle="tooltip" title="<?php echo date('F', mktime(0, 0, 0, $i, 1)); ?>: <?php echo $monthly_stats[$i]; ?> records">
                                            <div class="progress-bar" style="background-color: <?php echo $colors[$i-1]; ?>; width: 100%;"><?php echo $i; ?></div>
                                        </div>
                                    <?php 
                                        endif;
                                    endfor; 
                                    ?>
                                </div>
                                
                                <?php if(count($diagnoses_stats) > 0): ?>
                                    <h6 class="fw-bold">Most Common Diagnoses</h6>
                                    <ul class="list-group list-group-flush">
                                        <?php foreach($diagnoses_stats as $index => $diagnosis): ?>
                                            <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                                                <span class="text-truncate" style="max-width: 70%;" title="<?php echo htmlspecialchars($diagnosis['diagnosis']); ?>">
                                                    <?php echo htmlspecialchars(substr($diagnosis['diagnosis'], 0, 40)); ?>
                                                    <?php echo (strlen($diagnosis['diagnosis']) > 40) ? '...' : ''; ?>
                                                </span>
                                                <span class="badge bg-primary rounded-pill"><?php echo $diagnosis['count']; ?></span>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6 mb-4">
                        <div class="card h-100">
                            <div class="card-header">
                                <i class="fas fa-lightbulb me-2"></i> Quick Actions
                            </div>
                            <div class="card-body">
                                <div class="d-grid gap-3">
                                    <a href="add_record.php" class="btn btn-success">
                                        <i class="fas fa-plus-circle me-2"></i> Create New Medical Record
                                    </a>
                                    
                                    <a href="doctor_schedule.php" class="btn btn-info text-white">
                                        <i class="fas fa-calendar-alt me-2"></i> View Upcoming Appointments
                                    </a>
                                    
                                    <a href="#" data-bs-toggle="modal" data-bs-target="#searchPatientModal" class="btn btn-primary">
                                        <i class="fas fa-search me-2"></i> Search Patient Records
                                    </a>
                                    
                                    <a href="#" class="btn btn-outline-secondary">
                                        <i class="fas fa-file-export me-2"></i> Export Records (PDF)
                                    </a>
                                </div>
                                
                                <hr>
                                
                                <div class="alert alert-info">
                                    <h6 class="alert-heading fw-bold"><i class="fas fa-info-circle me-2"></i>Tip</h6>
                                    <p class="mb-0">Remember to maintain patient confidentiality when handling medical records. All access to these records is logged for security purposes.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Patient Search Modal -->
    <div class="modal fade" id="searchPatientModal" tabindex="-1" aria-labelledby="searchPatientModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="searchPatientModalLabel"><i class="fas fa-search me-2"></i> Search Patient Records</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="input-group mb-3">
                        <input type="text" id="searchInput" class="form-control" placeholder="Enter patient name, ID, or diagnosis...">
                        <button class="btn btn-primary" type="button" id="searchButton">
                            <i class="fas fa-search"></i> Search
                        </button>
                    </div>
                    
                    <div id="searchResults" class="mt-3">
                        <!-- Results will be displayed here -->
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.1.3/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize tooltips
            const tooltips = document.querySelectorAll('[data-bs-toggle="tooltip"]');
            tooltips.forEach(tooltip => {
                new bootstrap.Tooltip(tooltip);
            });
            
            // Patient search functionality
            const searchInput = document.getElementById('searchInput');
            const searchButton = document.getElementById('searchButton');
            const searchResults = document.getElementById('searchResults');
            
            searchButton.addEventListener('click', function() {
                const searchTerm = searchInput.value.trim();
                
                if(searchTerm.length < 2) {
                    searchResults.innerHTML = '<div class="alert alert-warning">Please enter at least 2 characters to search.</div>';
                    return;
                }
                
                // Show loading indicator
                searchResults.innerHTML = '<div class="text-center"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div><p class="mt-2">Searching records...</p></div>';
                
                // AJAX call to search patients (simplified for demo)
                setTimeout(() => {
                    // This would normally be an AJAX call to a search endpoint
                    searchResults.innerHTML = `
                        <h6 class="fw-bold mb-3">Search Results for "${searchTerm}":</h6>
                        <div class="list-group">
                            <a href="view_patient_details.php?id=1" class="list-group-item list-group-item-action">
                                <div class="d-flex w-100 justify-content-between">
                                    <h6 class="mb-1">John Smith</h6>
                                    <small>3 records</small>
                                </div>
                                <p class="mb-1 small">DOB: 1985-04-12 | Last Visit: 2023-10-15</p>
                            </a>
                            <a href="view_patient_details.php?id=2" class="list-group-item list-group-item-action">
                                <div class="d-flex w-100 justify-content-between">
                                    <h6 class="mb-1">Sarah Johnson</h6>
                                    <small>1 record</small>
                                </div>
                                <p class="mb-1 small">DOB: 1992-07-23 | Last Visit: 2023-09-28</p>
                            </a>
                        </div>
                    `;
                }, 1000);
            });
            
            // Allow search on Enter key
            searchInput.addEventListener('keyup', function(event) {
                if (event.key === 'Enter') {
                    searchButton.click();
                }
            });
        });
    </script>
</body>
</html>