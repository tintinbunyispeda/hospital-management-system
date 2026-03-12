<?php
// index.php or register_landing.php
session_start();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Registration</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.1.3/css/bootstrap.min.css">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f8f9fa;
            padding-top: 50px;
        }
        .container {
            max-width: 800px;
        }
        .card {
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s, box-shadow 0.3s;
            cursor: pointer;
            margin-bottom: 20px;
        }
        .card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.2);
        }
        .card-body {
            text-align: center;
            padding: 30px;
        }
        .icon-container {
            margin-bottom: 20px;
        }
        .icon-container svg {
            width: 80px;
            height: 80px;
            color: #3498db;
        }
        h5.card-title {
            font-size: 24px;
            margin-bottom: 20px;
        }
        .btn-register {
            background-color: #3498db;
            border-color: #3498db;
            padding: 10px 30px;
            font-size: 16px;
        }
        .btn-register:hover {
            background-color: #2980b9;
            border-color: #2980b9;
        }
        .header {
            text-align: center;
            margin-bottom: 50px;
        }
        .header h1 {
            color: #2c3e50;
            margin-bottom: 15px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Registration</h1>
            <p class="lead">Please select the type of account you want to create</p>
        </div>
        
        <div class="row">
            <div class="col-md-6">
                <div class="card h-100" onclick="window.location.href='register_patient.php'">
                    <div class="card-body">
                        <div class="icon-container">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                                <circle cx="12" cy="7" r="4"></circle>
                            </svg>
                        </div>
                        <h5 class="card-title">Patient</h5>
                        <p class="card-text">Register as a patient to book appointments and access medical services.</p>
                        <a href="register_patient.php" class="btn btn-primary btn-register">Register as Patient</a>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="card h-100" onclick="window.location.href='register_doctor.php'">
                    <div class="card-body">
                        <div class="icon-container">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M8 2v4"></path>
                                <path d="M16 2v4"></path>
                                <path d="M8 11h8"></path>
                                <path d="M8 16h8"></path>
                                <rect x="2" y="6" width="20" height="16" rx="2"></rect>
                            </svg>
                        </div>
                        <h5 class="card-title">Doctor</h5>
                        <p class="card-text">Register as a healthcare provider to manage appointments and patient records.</p>
                        <a href="register_doctor.php" class="btn btn-primary btn-register">Register as Doctor</a>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="text-center mt-4">
            <p>Already have an account? <a href="login.php">Login here</a></p>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.1.3/js/bootstrap.bundle.min.js"></script>
</body>
</html>