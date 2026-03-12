<?php
// make_payment.php - Process a payment
session_start();
require_once "config.php";

// Check if the user is logged in and is a patient
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["user_type"] != "patient"){
    header("location: login.php");
    exit;
}

// Check if payment ID is provided
if(!isset($_GET["id"]) || empty($_GET["id"])){
    header("location: payments.php");
    exit;
}

$payment_id = $_GET["id"];
$patient_id = $_SESSION["id"];

// Verify this payment belongs to the logged-in patient and is pending
$sql = "SELECT p.*, a.appointment_id, a.appointment_datetime,
               d.name as doctor_name, s.name as specialty
        FROM payments p
        JOIN appointments a ON p.appointment_id = a.appointment_id
        JOIN doctors d ON a.doctor_id = d.doctor_id
        JOIN specialties s ON d.specialty_id = s.specialty_id
        WHERE p.payment_id = ? AND a.patient_id = ? AND p.status = 'Pending'";

if($stmt = mysqli_prepare($conn, $sql)){
    mysqli_stmt_bind_param($stmt, "ii", $param_payment_id, $param_patient_id);
    $param_payment_id = $payment_id;
    $param_patient_id = $patient_id;
    
    if(mysqli_stmt_execute($stmt)){
        $result = mysqli_stmt_get_result($stmt);
        
        if(mysqli_num_rows($result) == 1){
            $payment = mysqli_fetch_array($result);
        } else {
            // No valid payment found
            $_SESSION["message"] = "Invalid payment or it has already been processed.";
            $_SESSION["message_type"] = "danger";
            header("location: payments.php");
            exit;
        }
    } else {
        echo "Oops! Something went wrong. Please try again later.";
        exit;
    }
    mysqli_stmt_close($stmt);
}

// Process form submission
if($_SERVER["REQUEST_METHOD"] == "POST"){
    // Validate payment method
    $payment_method = trim($_POST["payment_method"]);
    if(empty($payment_method)){
        $payment_method_err = "Please select a payment method.";
    }
    
    // If no errors, process payment
    if(empty($payment_method_err)){
        // Update payment status to completed
        $update_sql = "UPDATE payments SET status = 'Completed', payment_method = ?, payment_date = NOW() WHERE payment_id = ?";
        
        if($update_stmt = mysqli_prepare($conn, $update_sql)){
            mysqli_stmt_bind_param($update_stmt, "si", $param_method, $param_payment_id);
            $param_method = $payment_method;
            $param_payment_id = $payment_id;
            
            if(mysqli_stmt_execute($update_stmt)){
                $_SESSION["message"] = "Payment processed successfully!";
                $_SESSION["message_type"] = "success";
                header("location: payments.php");
                exit;
            } else {
                $_SESSION["message"] = "Error processing payment.";
                $_SESSION["message_type"] = "danger";
                header("location: payments.php");
                exit;
            }
            mysqli_stmt_close($update_stmt);
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Make Payment</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.1.3/css/bootstrap.min.css">
    <style>
        body{ font: 14px sans-serif; padding: 20px; }
    </style>
</head>
<body>
    <div class="container">
        <h2>Make Payment</h2>
        
        <div class="card mb-4">
            <div class="card-header">
                Payment Details
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <p><strong>Payment ID:</strong> <?php echo $payment['payment_id']; ?></p>
                        <p><strong>Amount Due:</strong> $<?php echo number_format($payment['amount'], 2); ?></p>
                    </div>
                    <div class="col-md-6">
                        <p><strong>For Appointment:</strong> <?php echo date('M d, Y h:i A', strtotime($payment['appointment_datetime'])); ?></p>
                        <p><strong>Doctor:</strong> <?php echo $payment['doctor_name']; ?> (<?php echo $payment['specialty']; ?>)</p>
                    </div>
                </div>
            </div>
        </div>
        
        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]) . "?id=" . $payment_id; ?>" method="post">
            <div class="form-group mb-3">
                <label for="payment_method">Payment Method:</label>
                <select class="form-control <?php echo (!empty($payment_method_err)) ? 'is-invalid' : ''; ?>" id="payment_method" name="payment_method">
                    <option value="">Select payment method</option>
                    <option value="Credit Card">Credit Card</option>
                    <option value="Debit Card">Debit Card</option>
                    <option value="PayPal">PayPal</option>
                    <option value="Bank Transfer">Bank Transfer</option>
                    <option value="Insurance">Insurance</option>
                </select>
                <span class="invalid-feedback"><?php echo $payment_method_err; ?></span>
            </div>
            
            <!-- In a real application, we would have proper payment processing integration here -->
            <div class="alert alert-info">
                <p>Note: This is a demo application. In a real system, this would connect to a payment processor.</p>
            </div>
            
            <div class="form-group">
                <input type="submit" class="btn btn-success" value="Process Payment">
                <a href="payments.php" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</body>
</html>