<?php
// login.php - Unified login form for both patients and doctors
session_start();
require_once "config.php";

$username = $password = "";
$username_err = $password_err = $login_err = "";

// Check if there's a registration success message
if (isset($_SESSION['registration_success'])) {
    $success_message = "Registration successful! You can now log in with your credentials.";
    // Clear the session variable
    unset($_SESSION['registration_success']);
}

// Process form data when form is submitted
if($_SERVER["REQUEST_METHOD"] == "POST"){
 
    // Check if username is empty
    if(empty(trim($_POST["username"]))){
        $username_err = "Please enter username.";
    } else{
        $username = trim($_POST["username"]);
    }
    
    // Check if password is empty
    if(empty(trim($_POST["password"]))){
        $password_err = "Please enter your password.";
    } else{
        $password = trim($_POST["password"]);
    }
    
    // Get user type selection
    $user_type = isset($_POST["user_type"]) ? $_POST["user_type"] : "patient";
    
    // Validate credentials
    if(empty($username_err) && empty($password_err)){
        // Determine which table to check based on user type
        $table = ($user_type == "doctor") ? "doctors" : "patients";
        $id_field = ($user_type == "doctor") ? "doctor_id" : "patient_id";
        $dashboard = ($user_type == "doctor") ? "doctor_dashboard.php" : "patient_dashboard.php";
        
        // Prepare a select statement
        $sql = "SELECT $id_field, username, password FROM $table WHERE username = ?";
        
        if($stmt = mysqli_prepare($conn, $sql)){
            // Bind variables to the prepared statement as parameters
            mysqli_stmt_bind_param($stmt, "s", $param_username);
            
            // Set parameters
            $param_username = $username;
            
            // Attempt to execute the prepared statement
            if(mysqli_stmt_execute($stmt)){
                // Store result
                mysqli_stmt_store_result($stmt);
                
                // Check if username exists, if yes then verify password
                if(mysqli_stmt_num_rows($stmt) == 1){                    
                    // Bind result variables
                    mysqli_stmt_bind_result($stmt, $id, $username, $hashed_password);
                    if(mysqli_stmt_fetch($stmt)){
                        if(password_verify($password, $hashed_password)){
                            // Password is correct, so start a new session
                            session_start();
                            
                            // Store data in session variables
                            $_SESSION["loggedin"] = true;
                            $_SESSION["id"] = $id;
                            $_SESSION["username"] = $username;
                            $_SESSION["user_type"] = $user_type;
                            
                            // Redirect user to appropriate dashboard
                            header("location: " . $dashboard);
                            exit;
                        } else{
                            // Password is not valid
                            $login_err = "Invalid username or password.";
                        }
                    }
                } else{
                    // Username doesn't exist in the selected user type
                    $login_err = "Invalid username or password.";
                }
            } else{
                echo "Oops! Something went wrong. Please try again later.";
            }

            // Close statement
            mysqli_stmt_close($stmt);
        }
    }
    
    // Close connection
    mysqli_close($conn);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login - Hospital Management System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.1.3/css/bootstrap.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f8f9fa;
            padding-top: 20px;
            padding-bottom: 20px;
        }
        .wrapper {
            width: 100%;
            max-width: 450px;
            margin: 0 auto;
            margin-top: 50px;
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
            padding: 40px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        h2 {
            color: #2c3e50;
            margin-bottom: 20px;
            text-align: center;
        }
        .btn-primary {
            background-color: #2980b9;
            border-color: #2980b9;
        }
        .btn-primary:hover {
            background-color: #1c638b;
            border-color: #1c638b;
        }
        .form-control:focus {
            border-color: #2980b9;
            box-shadow: 0 0 0 0.25rem rgba(41, 128, 185, 0.25);
        }
        .user-type-toggle {
            display: flex;
            margin-bottom: 25px;
            border-radius: 5px;
            overflow: hidden;
        }
        .user-type-toggle label {
            flex: 1;
            text-align: center;
            padding: 10px 15px;
            background-color: #e9ecef;
            cursor: pointer;
            transition: background-color 0.3s;
            margin: 0;
        }
        .user-type-toggle input[type="radio"] {
            display: none;
        }
        .user-type-toggle input[type="radio"]:checked + label {
            background-color: #2980b9;
            color: white;
            font-weight: 500;
        }
        .login-icon {
            display: block;
            margin: 0 auto 20px;
            width: 70px;
            height: 70px;
            background-color: #e9f5fe;
            border-radius: 50%;
            padding: 15px;
            color: #2980b9;
        }
        .register-links {
            margin-top: 30px;
            text-align: center;
            border-top: 1px solid #dee2e6;
            padding-top: 20px;
        }
        .register-links p {
            margin-bottom: 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="wrapper">
            <div class="text-center mb-4">
                <svg class="login-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                    <circle cx="12" cy="7" r="4"></circle>
                </svg>
                <h2>Welcome</h2>
                <p>Please log in to your account</p>
            </div>

            <?php 
            if(!empty($login_err)){
                echo '<div class="alert alert-danger">' . $login_err . '</div>';
            }
            if(isset($success_message)){
                echo '<div class="alert alert-success">' . $success_message . '</div>';
            }
            ?>

            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                <div class="user-type-toggle">
                    <input type="radio" name="user_type" id="patient-radio" value="patient" checked>
                    <label for="patient-radio">Patient</label>
                    <input type="radio" name="user_type" id="doctor-radio" value="doctor">
                    <label for="doctor-radio">Doctor</label>
                </div>

                <div class="form-group mb-3">
                    <label>Username</label>
                    <input type="text" name="username" class="form-control <?php echo (!empty($username_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $username; ?>">
                    <span class="invalid-feedback"><?php echo $username_err; ?></span>
                </div>    
                <div class="form-group mb-3">
                    <label>Password</label>
                    <input type="password" name="password" class="form-control <?php echo (!empty($password_err)) ? 'is-invalid' : ''; ?>">
                    <span class="invalid-feedback"><?php echo $password_err; ?></span>
                </div>
                <div class="form-group mb-3">
                    <div class="d-grid">
                        <input type="submit" class="btn btn-primary btn-lg" value="Login">
                    </div>
                </div>
                <div class="register-links">
                    <p>Don't have an account? Register as:</p>
                    <div class="row">
                        <div class="col-6">
                            <a href="register_patient.php" class="btn btn-outline-primary d-block">Patient</a>
                        </div>
                        <div class="col-6">
                            <a href="register_doctor.php" class="btn btn-outline-primary d-block">Doctor</a>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</body>
</html>