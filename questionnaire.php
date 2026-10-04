<?php
session_start();
require_once 'config/database.php';

// Create questionnaire responses table
$create_table = "CREATE TABLE IF NOT EXISTS questionnaire_responses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    respondent_name VARCHAR(100),
    respondent_email VARCHAR(100),
    respondent_phone VARCHAR(20),
    submission_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    -- Section A: Respondent Information
    role VARCHAR(50),
    role_other VARCHAR(100),
    experience VARCHAR(50),
    
    -- Section B: Online Vehicle Booking System
    booking_method TEXT,
    double_booking VARCHAR(10),
    booking_efficiency VARCHAR(10),
    booking_challenges TEXT,
    
    -- Section C: Vehicle Availability Tracking
    tracking_method VARCHAR(50),
    tracking_difficulty VARCHAR(10),
    tracking_challenges TEXT,
    
    -- Section D: Digital Record Management
    record_storage TEXT,
    records_lost VARCHAR(10),
    record_problems TEXT,
    
    -- Section E: Payment Management System
    payment_recording TEXT,
    payment_errors VARCHAR(20),
    payment_suggestions TEXT
)";

mysqli_query($conn, $create_table);

$message = '';
$error = '';

if($_SERVER["REQUEST_METHOD"] == "POST") {
    // Sanitize and insert data
    $respondent_name = mysqli_real_escape_string($conn, $_POST['respondent_name'] ?? '');
    $respondent_email = mysqli_real_escape_string($conn, $_POST['respondent_email'] ?? '');
    $respondent_phone = mysqli_real_escape_string($conn, $_POST['respondent_phone'] ?? '');
    
    $role = mysqli_real_escape_string($conn, $_POST['role'] ?? '');
    $role_other = ($role == 'Other') ? mysqli_real_escape_string($conn, $_POST['role_other'] ?? '') : '';
    $experience = mysqli_real_escape_string($conn, $_POST['experience'] ?? '');
    
    $booking_method = mysqli_real_escape_string($conn, implode(', ', $_POST['booking_method'] ?? []));
    $double_booking = mysqli_real_escape_string($conn, $_POST['double_booking'] ?? '');
    $booking_efficiency = mysqli_real_escape_string($conn, $_POST['booking_efficiency'] ?? '');
    $booking_challenges = mysqli_real_escape_string($conn, $_POST['booking_challenges'] ?? '');
    
    $tracking_method = mysqli_real_escape_string($conn, $_POST['tracking_method'] ?? '');
    $tracking_difficulty = mysqli_real_escape_string($conn, $_POST['tracking_difficulty'] ?? '');
    $tracking_challenges = mysqli_real_escape_string($conn, $_POST['tracking_challenges'] ?? '');
    
    $record_storage = mysqli_real_escape_string($conn, implode(', ', $_POST['record_storage'] ?? []));
    $records_lost = mysqli_real_escape_string($conn, $_POST['records_lost'] ?? '');
    $record_problems = mysqli_real_escape_string($conn, $_POST['record_problems'] ?? '');
    
    $payment_recording = mysqli_real_escape_string($conn, implode(', ', $_POST['payment_recording'] ?? []));
    $payment_errors = mysqli_real_escape_string($conn, $_POST['payment_errors'] ?? '');
    $payment_suggestions = mysqli_real_escape_string($conn, $_POST['payment_suggestions'] ?? '');
    
    $insert = "INSERT INTO questionnaire_responses (
        respondent_name, respondent_email, respondent_phone,
        role, role_other, experience,
        booking_method, double_booking, booking_efficiency, booking_challenges,
        tracking_method, tracking_difficulty, tracking_challenges,
        record_storage, records_lost, record_problems,
        payment_recording, payment_errors, payment_suggestions
    ) VALUES (
        '$respondent_name', '$respondent_email', '$respondent_phone',
        '$role', '$role_other', '$experience',
        '$booking_method', '$double_booking', '$booking_efficiency', '$booking_challenges',
        '$tracking_method', '$tracking_difficulty', '$tracking_challenges',
        '$record_storage', '$records_lost', '$record_problems',
        '$payment_recording', '$payment_errors', '$payment_suggestions'
    )";
    
    if(mysqli_query($conn, $insert)) {
        $message = "Thank you! Your response has been submitted successfully.";
        $_POST = array();
    } else {
        $error = "Error: " . mysqli_error($conn);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Urban Wheels - System Evaluation Questionnaire</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 40px 20px;
            min-height: 100vh;
        }
        
        .container {
            max-width: 900px;
            margin: 0 auto;
        }
        
        .header {
            background: white;
            padding: 30px;
            border-radius: 15px;
            text-align: center;
            margin-bottom: 30px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }
        
        .header h1 {
            color: #667eea;
            margin-bottom: 10px;
            font-size: 28px;
        }
        
        .header .institution {
            color: #666;
            font-size: 14px;
            margin-top: 10px;
        }
        
        .instructions {
            background: #f0f7ff;
            padding: 15px 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            border-left: 4px solid #667eea;
        }
        
        .form-card {
            background: white;
            border-radius: 15px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }
        
        .form-card h2 {
            color: #667eea;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #667eea;
            font-size: 20px;
        }
        
        .form-group {
            margin-bottom: 25px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
        }
        
        .form-group input, .form-group select, .form-group textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-family: inherit;
            font-size: 14px;
        }
        
        .form-group input:focus, .form-group select:focus, .form-group textarea:focus {
            outline: none;
            border-color: #667eea;
        }
        
        .radio-group, .checkbox-group {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            margin-top: 10px;
        }
        
        .radio-group label, .checkbox-group label {
            display: flex;
            align-items: center;
            gap: 8px;
            font-weight: normal;
            cursor: pointer;
        }
        
        .radio-group input, .checkbox-group input {
            width: auto;
        }
        
        .btn-submit {
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
            padding: 14px 30px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            width: 100%;
            transition: transform 0.3s;
        }
        
        .btn-submit:hover {
            transform: translateY(-2px);
        }
        
        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .closing {
            background: #e7f3ff;
            padding: 20px;
            border-radius: 10px;
            text-align: center;
            margin-top: 20px;
        }
        
        .required {
            color: #dc3545;
        }
        
        @media print {
            body {
                background: white;
                padding: 0;
            }
            .btn-submit, .alert {
                display: none;
            }
            .form-card {
                break-inside: avoid;
                page-break-inside: avoid;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>URBAN WHEELS CAR RENTAL SYSTEM</h1>
            <p>System Evaluation Questionnaire</p>
            <div class="institution">
                <i class="fas fa-university"></i> Diploma in Information Technology<br>
                <i class="fas fa-car"></i> Online Car Rental Management System
            </div>
        </div>
        
        <div class="instructions">
            <i class="fas fa-info-circle"></i> 
            <strong>Instructions:</strong> Please answer all questions honestly. Tick (✓) where appropriate and provide explanations where required. 
            The information provided will be used strictly for academic purposes and will be treated with confidentiality.
        </div>
        
        <?php if($message): ?>
            <div class="alert alert-success"><?php echo $message; ?></div>
        <?php endif; ?>
        
        <?php if($error): ?>
            <div class="alert alert-error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <form method="POST" id="questionnaireForm">
            
            <!-- SECTION A: RESPONDENT INFORMATION -->
            <div class="form-card">
                <h2>SECTION A: RESPONDENT INFORMATION</h2>
                
                <div class="form-group">
                    <label>1. What is your role? <span class="required">*</span></label>
                    <div class="radio-group">
                        <label><input type="radio" name="role" value="Manager" required> Manager</label>
                        <label><input type="radio" name="role" value="Staff"> Staff</label>
                        <label><input type="radio" name="role" value="Customer"> Customer</label>
                        <label><input type="radio" name="role" value="Other"> Other</label>
                    </div>
                    <input type="text" name="role_other" placeholder="If Other, please specify" style="margin-top: 10px; display: none;" id="role_other_input">
                </div>
                
                <div class="form-group">
                    <label>2. How long have you been involved in car rental services?</label>
                    <div class="radio-group">
                        <label><input type="radio" name="experience" value="Less than 1 year"> Less than 1 year</label>
                        <label><input type="radio" name="experience" value="1-3 years"> 1–3 years</label>
                        <label><input type="radio" name="experience" value="4-6 years"> 4–6 years</label>
                        <label><input type="radio" name="experience" value="More than 6 years"> More than 6 years</label>
                    </div>
                </div>
            </div>
            
            <!-- SECTION B: ONLINE VEHICLE BOOKING SYSTEM -->
            <div class="form-card">
                <h2>SECTION B: ONLINE VEHICLE BOOKING SYSTEM</h2>
                
                <div class="form-group">
                    <label>3. How are bookings currently made in your organization?</label>
                    <div class="checkbox-group">
                        <label><input type="checkbox" name="booking_method[]" value="Manual record book"> Manual record book</label>
                        <label><input type="checkbox" name="booking_method[]" value="Phone calls"> Phone calls</label>
                        <label><input type="checkbox" name="booking_method[]" value="Social media"> Social media</label>
                        <label><input type="checkbox" name="booking_method[]" value="Website"> Website</label>
                        <label><input type="checkbox" name="booking_method[]" value="Combination"> Combination</label>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>4. Have you experienced double booking of vehicles?</label>
                    <div class="radio-group">
                        <label><input type="radio" name="double_booking" value="Yes"> Yes</label>
                        <label><input type="radio" name="double_booking" value="No"> No</label>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>5. Do you think an online booking system would improve efficiency?</label>
                    <div class="radio-group">
                        <label><input type="radio" name="booking_efficiency" value="Yes"> Yes</label>
                        <label><input type="radio" name="booking_efficiency" value="No"> No</label>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>6. What challenges do you face during the booking process?</label>
                    <textarea name="booking_challenges" rows="3" placeholder="Describe any challenges you face..."></textarea>
                </div>
            </div>
            
            <!-- SECTION C: VEHICLE AVAILABILITY TRACKING -->
            <div class="form-card">
                <h2>SECTION C: VEHICLE AVAILABILITY TRACKING</h2>
                
                <div class="form-group">
                    <label>7. How do you track available vehicles?</label>
                    <div class="radio-group">
                        <label><input type="radio" name="tracking_method" value="Manual records"> Manual records</label>
                        <label><input type="radio" name="tracking_method" value="Verbal communication"> Verbal communication</label>
                        <label><input type="radio" name="tracking_method" value="Spreadsheet"> Spreadsheet</label>
                        <label><input type="radio" name="tracking_method" value="System software"> System software</label>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>8. Do you experience difficulty in identifying available vehicles?</label>
                    <div class="radio-group">
                        <label><input type="radio" name="tracking_difficulty" value="Yes"> Yes</label>
                        <label><input type="radio" name="tracking_difficulty" value="No"> No</label>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>9. Explain the challenges faced in tracking vehicle availability:</label>
                    <textarea name="tracking_challenges" rows="3" placeholder="Describe challenges..."></textarea>
                </div>
            </div>
            
            <!-- SECTION D: DIGITAL RECORD MANAGEMENT -->
            <div class="form-card">
                <h2>SECTION D: DIGITAL RECORD MANAGEMENT</h2>
                
                <div class="form-group">
                    <label>10. How are records stored in your organization?</label>
                    <div class="checkbox-group">
                        <label><input type="checkbox" name="record_storage[]" value="Paper files"> Paper files</label>
                        <label><input type="checkbox" name="record_storage[]" value="Excel sheets"> Excel sheets</label>
                        <label><input type="checkbox" name="record_storage[]" value="Software system"> Software system</label>
                        <label><input type="checkbox" name="record_storage[]" value="Combination"> Combination</label>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>11. Have records ever been lost or misplaced?</label>
                    <div class="radio-group">
                        <label><input type="radio" name="records_lost" value="Yes"> Yes</label>
                        <label><input type="radio" name="records_lost" value="No"> No</label>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>12. What problems are associated with manual record keeping?</label>
                    <textarea name="record_problems" rows="3" placeholder="Describe problems..."></textarea>
                </div>
            </div>
            
            <!-- SECTION E: PAYMENT MANAGEMENT SYSTEM -->
            <div class="form-card">
                <h2>SECTION E: PAYMENT MANAGEMENT SYSTEM</h2>
                
                <div class="form-group">
                    <label>13. How are payments recorded?</label>
                    <div class="checkbox-group">
                        <label><input type="checkbox" name="payment_recording[]" value="Receipt book"> Receipt book</label>
                        <label><input type="checkbox" name="payment_recording[]" value="Excel"> Excel</label>
                        <label><input type="checkbox" name="payment_recording[]" value="Accounting software"> Accounting software</label>
                        <label><input type="checkbox" name="payment_recording[]" value="Manual ledger"> Manual ledger</label>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>14. Do payment errors occur in your system?</label>
                    <div class="radio-group">
                        <label><input type="radio" name="payment_errors" value="Frequently"> Frequently</label>
                        <label><input type="radio" name="payment_errors" value="Sometimes"> Sometimes</label>
                        <label><input type="radio" name="payment_errors" value="Rarely"> Rarely</label>
                        <label><input type="radio" name="payment_errors" value="Never"> Never</label>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>15. What improvements would you suggest for payment management?</label>
                    <textarea name="payment_suggestions" rows="3" placeholder="Your suggestions..."></textarea>
                </div>
            </div>
            
            <!-- Closing Statement -->
            <div class="closing">
                <i class="fas fa-heart" style="color: #dc3545;"></i>
                <p><strong>Closing Statement</strong></p>
                <p>Thank you for taking your time to complete this questionnaire. Your responses will greatly contribute to the successful development of the Online Car Rental Management System.</p>
                <p style="margin-top: 10px; font-size: 12px; color: #666;">
                    <i class="fas fa-lock"></i> All responses are confidential and will be used for academic purposes only.
                </p>
            </div>
            
            <button type="submit" class="btn-submit"><i class="fas fa-paper-plane"></i> Submit Response</button>
        </form>
    </div>
    
    <script>
        // Show/hide other role input
        document.querySelectorAll('input[name="role"]').forEach(radio => {
            radio.addEventListener('change', function() {
                const otherInput = document.getElementById('role_other_input');
                if(this.value === 'Other' && this.checked) {
                    otherInput.style.display = 'block';
                } else {
                    otherInput.style.display = 'none';
                }
            });
        });
    </script>
</body>
</html>