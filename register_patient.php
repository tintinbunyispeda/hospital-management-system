<?php
// register_patient.php
session_start();
require_once "config.php";

// Define variables and initialize with empty values
$name = $dob = $gender = $phone = $email = $email_confirm = $address = $username = $password = $confirm_password = "";
$name_err = $dob_err = $gender_err = $phone_err = $email_err = $email_confirm_err = $address_err = $username_err = $password_err = $confirm_password_err = "";

// Processing form data when form is submitted
if($_SERVER["REQUEST_METHOD"] == "POST"){
    
    // Validate name
    if(empty(trim($_POST["name"]))){
        $name_err = "Please enter your full name.";
    } else {
        $name = trim($_POST["name"]);
    }
    
    // Validate date of birth
    if(empty(trim($_POST["dob"]))){
        $dob_err = "Please enter your date of birth.";
    } else {
        $dob = trim($_POST["dob"]);
        // Check if date is valid and not in the future
        $today = date("Y-m-d");
        if($dob > $today){
            $dob_err = "Date of birth cannot be in the future.";
        }
    }
    
    // Validate gender
    if(empty($_POST["gender"])){
        $gender_err = "Please select your gender.";
    } else {
        $gender = $_POST["gender"];
    }
    
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
        $sql = "SELECT patient_id FROM patients WHERE email = ?";
        
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
    
    // Validate address
    if(empty(trim($_POST["address"]))){
        $address_err = "Please enter your address.";
    } else {
        $address = trim($_POST["address"]);
    }
    
    // Validate username
    if(empty(trim($_POST["username"]))){
        $username_err = "Please enter a username.";
    } else{
        // Prepare a select statement
        $sql = "SELECT patient_id FROM patients WHERE username = ?";
        
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
    if(empty($name_err) && empty($dob_err) && empty($gender_err) && empty($phone_err) && 
       empty($email_err) && empty($email_confirm_err) && empty($address_err) && 
       empty($username_err) && empty($password_err) && empty($confirm_password_err)){
        
        // Prepare an insert statement for patients
        $sql = "INSERT INTO patients (name, dob, gender, phone, email, address, username, password) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        
        if($stmt = mysqli_prepare($conn, $sql)){
            // Bind variables to the prepared statement as parameters
            mysqli_stmt_bind_param($stmt, "ssssssss", $param_name, $param_dob, $param_gender, $param_phone, $param_email, $param_address, $param_username, $param_password);
            
            // Set parameters
            $param_name = $name;
            $param_dob = $dob;
            $param_gender = $gender;
            $param_phone = $phone;
            $param_email = $email;
            $param_address = $address;
            $param_username = $username;
            $param_password = password_hash($password, PASSWORD_DEFAULT); // Creates a password hash
            
            // Attempt to execute the prepared statement
            if(mysqli_stmt_execute($stmt)){
                // Redirect to login page
                $_SESSION['registration_success'] = true;
                $_SESSION['user_type'] = 'patient';
                header("location: login.php");
                exit();
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
    <title>Patient Registration</title>
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
            background-color: #3498db;
            border-color: #3498db;
        }
        .btn-primary:hover {
            background-color: #2980b9;
            border-color: #2980b9;
        }
        .form-control:focus {
            border-color: #3498db;
            box-shadow: 0 0 0 0.25rem rgba(52, 152, 219, 0.25);
        }
        .user-type-header {
            text-align: center;
            margin-bottom: 30px;
        }
        .user-type-header svg {
            width: 60px;
            height: 60px;
            color: #3498db;
            margin-bottom: 15px;
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="user-type-header">
            <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                <circle cx="12" cy="7" r="4"></circle>
            </svg>
            <h2>Patient Registration</h2>
        </div>
        <p class="text-center">Please fill this form to create your patient account.</p>
        
        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" id="registration_form">            
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Full Name</label>
                        <input type="text" name="name" class="form-control <?php echo (!empty($name_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $name; ?>">
                        <span class="invalid-feedback"><?php echo $name_err; ?></span>
                    </div>    
                </div>
                
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Date of Birth</label>
                        <input type="date" name="dob" class="form-control <?php echo (!empty($dob_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $dob; ?>">
                        <span class="invalid-feedback"><?php echo $dob_err; ?></span>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Gender</label>
                        <div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input <?php echo (!empty($gender_err)) ? 'is-invalid' : ''; ?>" type="radio" name="gender" id="male" value="Male" <?php if($gender == "Male") echo "checked"; ?>>
                                <label class="form-check-label" for="male">Male</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input <?php echo (!empty($gender_err)) ? 'is-invalid' : ''; ?>" type="radio" name="gender" id="female" value="Female" <?php if($gender == "Female") echo "checked"; ?>>
                                <label class="form-check-label" for="female">Female</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input <?php echo (!empty($gender_err)) ? 'is-invalid' : ''; ?>" type="radio" name="gender" id="other" value="Other" <?php if($gender == "Other") echo "checked"; ?>>
                                <label class="form-check-label" for="other">Other</label>
                            </div>
                            <?php if(!empty($gender_err)): ?>
                                <div class="invalid-feedback d-block"><?php echo $gender_err; ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Phone Number</label>
                        <input type="text" name="phone" class="form-control <?php echo (!empty($phone_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $phone; ?>">
                        <span class="invalid-feedback"><?php echo $phone_err; ?></span>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" name="email" class="form-control <?php echo (!empty($email_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $email; ?>">
                        <span class="invalid-feedback"><?php echo $email_err; ?></span>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Confirm Email</label>
                        <input type="email" name="email_confirm" class="form-control <?php echo (!empty($email_confirm_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $email_confirm; ?>">
                        <span class="invalid-feedback"><?php echo $email_confirm_err; ?></span>
                    </div>
                </div>
            </div>
            
            <div class="form-group">
                <label>Address</label>
                <textarea name="address" class="form-control <?php echo (!empty($address_err)) ? 'is-invalid' : ''; ?>" rows="3"><?php echo $address; ?></textarea>
                <span class="invalid-feedback"><?php echo $address_err; ?></span>
            </div>
            
            <div class="row">
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Username</label>
                        <input type="text" name="username" class="form-control <?php echo (!empty($username_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $username; ?>">
                        <span class="invalid-feedback"><?php echo $username_err; ?></span>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Password</label>
                        <input type="password" name="password" class="form-control <?php echo (!empty($password_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $password; ?>">
                        <span class="invalid-feedback"><?php echo $password_err; ?></span>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Confirm Password</label>
                        <input type="password" name="confirm_password" class="form-control <?php echo (!empty($confirm_password_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $confirm_password; ?>">
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
            <p class="text-center">Not a patient? <a href="register_doctor.php">Register as doctor</a>.</p>
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