<?php
// register_doctor.php
session_start();
require_once "config.php";

// Define variables and initialize with empty values
$name = $specialty = $room_number = $phone = $email = $email_confirm = $username = $password = $confirm_password = "";
$name_err = $specialty_err = $phone_err = $email_err = $email_confirm_err = $username_err = $password_err = $confirm_password_err = "";

// Get available specialties from database
$specialties = array();
$sql = "SELECT specialty_id, name FROM specialties ORDER BY name";
if($result = mysqli_query($conn, $sql)){
    while($row = mysqli_fetch_assoc($result)){
        $specialties[] = $row;
    }
    mysqli_free_result($result);
}

// Processing form data when form is submitted
if($_SERVER["REQUEST_METHOD"] == "POST"){
    
    // Validate name
    if(empty(trim($_POST["name"]))){
        $name_err = "Please enter your full name.";
    } else {
        $name = trim($_POST["name"]);
    }
    
    // Validate specialty
    if(empty($_POST["specialty"])){
        $specialty_err = "Please select your specialty.";
    } else {
        $specialty = $_POST["specialty"];
    }
    
    // Get room number (optional)
    $room_number = trim($_POST["room_number"]);
    
    // Validate phone
    if(empty(trim($_POST["phone"]))){
        $phone_err = "Please enter your phone number.";
    } else {
        $phone = trim($_POST["phone"]);
        // Check if phone number format is valid
        if(!preg_match("/^[0-9\-\(\)\/\+\s]*$/", $phone)){
            $phone_err = "Please enter a valid phone number.";
        }
    }
    
    // Validate email
    if(empty(trim($_POST["email"]))){
        $email_err = "Please enter your email address.";     
    } else {
        // Prepare a select statement to check email
        $sql = "SELECT doctor_id FROM doctors WHERE email = ?";
        
        if($stmt = mysqli_prepare($conn, $sql)){
            // Bind variables to the prepared statement as parameters
            mysqli_stmt_bind_param($stmt, "s", $param_email);
            
            // Set parameters
            $param_email = trim($_POST["email"]);
            
            // Attempt to execute the prepared statement
            if(mysqli_stmt_execute($stmt)){
                /* store result */
                mysqli_stmt_store_result($stmt);
                
                if(mysqli_stmt_num_rows($stmt) == 1){
                    $email_err = "This email is already registered.";
                } else{
                    $email = trim($_POST["email"]);
                    // Validate email format
                    if(!filter_var($email, FILTER_VALIDATE_EMAIL)){
                        $email_err = "Please enter a valid email address.";
                    }
                }
            } else{
                echo "Oops! Something went wrong. Please try again later.";
            }

            // Close statement
            mysqli_stmt_close($stmt);
        }
    }
    
    // Validate email confirmation
    if(empty(trim($_POST["email_confirm"]))){
        $email_confirm_err = "Please confirm your email address.";     
    } else {
        $email_confirm = trim($_POST["email_confirm"]);
        if(empty($email_err) && ($email != $email_confirm)){
            $email_confirm_err = "Email confirmation does not match.";
        }
    }
    
    // Validate username
    if(empty(trim($_POST["username"]))){
        $username_err = "Please enter a username.";
    } else{
        // Prepare a select statement
        $sql = "SELECT doctor_id FROM doctors WHERE username = ?";
        
        if($stmt = mysqli_prepare($conn, $sql)){
            // Bind variables to the prepared statement as parameters
            mysqli_stmt_bind_param($stmt, "s", $param_username);
            
            // Set parameters
            $param_username = trim($_POST["username"]);
            
            // Attempt to execute the prepared statement
            if(mysqli_stmt_execute($stmt)){
                /* store result */
                mysqli_stmt_store_result($stmt);
                
                if(mysqli_stmt_num_rows($stmt) == 1){
                    $username_err = "This username is already taken.";
                } else{
                    $username = trim($_POST["username"]);
                }
            } else{
                echo "Oops! Something went wrong. Please try again later.";
            }

            // Close statement
            mysqli_stmt_close($stmt);
        }
    }
    
    // Validate password
    if(empty(trim($_POST["password"]))){
        $password_err = "Please enter a password.";     
    } elseif(strlen(trim($_POST["password"])) < 6){
        $password_err = "Password must have at least 6 characters.";
    } else{
        $password = trim($_POST["password"]);
    }
    
    // Validate confirm password
    if(empty(trim($_POST["confirm_password"]))){
        $confirm_password_err = "Please confirm password.";     
    } else{
        $confirm_password = trim($_POST["confirm_password"]);
        if(empty($password_err) && ($password != $confirm_password)){
            $confirm_password_err = "Password confirmation does not match.";
        }
    }
    
    // Check input errors before inserting in database
    if(empty($name_err) && empty($specialty_err) && empty($phone_err) && 
       empty($email_err) && empty($email_confirm_err) && empty($username_err) && 
       empty($password_err) && empty($confirm_password_err)){
        
        // Prepare an insert statement for doctors
        $sql = "INSERT INTO doctors (name, specialty_id, room_number, phone, email, username, password) VALUES (?, ?, ?, ?, ?, ?, ?)";
        
        if($stmt = mysqli_prepare($conn, $sql)){
            // Bind variables to the prepared statement as parameters
            mysqli_stmt_bind_param($stmt, "sisssss", $param_name, $param_specialty, $param_room_number, $param_phone, $param_email, $param_username, $param_password);
            
            // Set parameters
            $param_name = $name;
            $param_specialty = $specialty;
            $param_room_number = $room_number;
            $param_phone = $phone;
            $param_email = $email;
            $param_username = $username;
            $param_password = password_hash($password, PASSWORD_DEFAULT); // Creates a password hash
            
            // Attempt to execute the prepared statement
            if(mysqli_stmt_execute($stmt)){
                // Set success message and redirect to login page
                $_SESSION['registration_success'] = true;
                $_SESSION['user_type'] = 'doctor';
                header("location: login.php");
                exit();
            } else{
                echo "Oops! Something went wrong. Please try again later.";
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
    <title>Doctor Registration</title>
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
            max-width: 800px;
            margin: 0 auto;
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
            padding: 30px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        h2 {
            color: #2c3e50;
            margin-bottom: 30px;
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
        .user-type-header {
            text-align: center;
            margin-bottom: 30px;
        }
        .user-type-header svg {
            width: 60px;
            height: 60px;
            color: #2980b9;
            margin-bottom: 15px;
        }
        .required-field::after {
            content: " *";
            color: red;
        }
        .optional-field::after {
            content: " (optional)";
            font-size: 0.85em;
            color: #6c757d;
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="user-type-header">
            <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                <polyline points="16 17 21 12 16 7"></polyline>
                <line x1="21" y1="12" x2="9" y2="12"></line>
            </svg>
            <h2>Doctor Registration</h2>
        </div>
        <p class="text-center">Please fill this form to create your doctor account.</p>
        
        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" id="registration_form">            
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label class="required-field">Full Name</label>
                        <input type="text" name="name" class="form-control <?php echo (!empty($name_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $name; ?>" required>
                        <span class="invalid-feedback"><?php echo $name_err; ?></span>
                    </div>    
                </div>
                
                <div class="col-md-6">
                    <div class="form-group">
                        <label class="required-field">Specialty</label>
                        <select name="specialty" class="form-select <?php echo (!empty($specialty_err)) ? 'is-invalid' : ''; ?>" required>
                            <option value="">Select Specialty</option>
                            <?php
                            foreach($specialties as $specialty_option){
                                $selected = ($specialty == $specialty_option['specialty_id']) ? 'selected' : '';
                                echo '<option value="' . $specialty_option['specialty_id'] . '" ' . $selected . '>' . $specialty_option['name'] . '</option>';
                            }
                            ?>
                        </select>
                        <span class="invalid-feedback"><?php echo $specialty_err; ?></span>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label class="optional-field">Room Number</label>
                        <input type="text" name="room_number" class="form-control" value="<?php echo $room_number; ?>">
                        <small class="form-text text-muted">You can assign this later if needed</small>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="form-group">
                        <label class="required-field">Phone Number</label>
                        <input type="text" name="phone" class="form-control <?php echo (!empty($phone_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $phone; ?>" required>
                        <span class="invalid-feedback"><?php echo $phone_err; ?></span>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label class="required-field">Email</label>
                        <input type="email" name="email" class="form-control <?php echo (!empty($email_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $email; ?>" required>
                        <span class="invalid-feedback"><?php echo $email_err; ?></span>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label class="required-field">Confirm Email</label>
                        <input type="email" name="email_confirm" class="form-control <?php echo (!empty($email_confirm_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $email_confirm; ?>" required>
                        <span class="invalid-feedback"><?php echo $email_confirm_err; ?></span>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="required-field">Username</label>
                        <input type="text" name="username" class="form-control <?php echo (!empty($username_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $username; ?>" required>
                        <span class="invalid-feedback"><?php echo $username_err; ?></span>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="required-field">Password</label>
                        <input type="password" name="password" class="form-control <?php echo (!empty($password_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $password; ?>" required>
                        <span class="invalid-feedback"><?php echo $password_err; ?></span>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="required-field">Confirm Password</label>
                        <input type="password" name="confirm_password" class="form-control <?php echo (!empty($confirm_password_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $confirm_password; ?>" required>
                        <span class="invalid-feedback"><?php echo $confirm_password_err; ?></span>
                    </div>
                </div>
            </div>
            
            <div class="form-group mt-4">
                <div class="d-grid gap-2">
                    <input type="submit" class="btn btn-primary" value="Register">
                </div>
            </div>
            <p class="text-center mt-3">Already have an account? <a href="login.php">Login here</a>.</p>
            <p class="text-center">Not a doctor? <a href="register_patient.php">Register as patient</a>.</p>
        </form>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.1.3/js/bootstrap.bundle.min.js"></script>
    <script>
    $(document).ready(function() {
        // Add confirmation before form submission
        $('#registration_form').on('submit', function(e) {
            if (!confirm('Are you sure you want to register with the provided information?')) {
                e.preventDefault();
            }
        });
    });
    </script>
</body>
</html>