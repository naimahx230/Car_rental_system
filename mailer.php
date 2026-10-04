<?php
require_once __DIR__ . '/../config/email.php';

function sendPasswordResetEmail($to_email, $to_name, $reset_link) {
    $subject = "Password Reset Request - " . SITE_NAME;
    
    // Email body HTML
    $message = '
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <title>Password Reset Request</title>
        <style>
            body {
                font-family: Arial, sans-serif;
                line-height: 1.6;
                color: #333;
            }
            .container {
                max-width: 600px;
                margin: 0 auto;
                padding: 20px;
            }
            .header {
                background: linear-gradient(135deg, #667eea, #764ba2);
                padding: 30px;
                text-align: center;
                border-radius: 10px 10px 0 0;
            }
            .header h1 {
                color: #FFD700;
                margin: 0;
                font-size: 28px;
            }
            .header p {
                color: white;
                margin: 5px 0 0;
            }
            .content {
                background: #f9f9f9;
                padding: 30px;
                border-radius: 0 0 10px 10px;
            }
            .button {
                display: inline-block;
                padding: 12px 30px;
                background: linear-gradient(135deg, #667eea, #764ba2);
                color: white;
                text-decoration: none;
                border-radius: 5px;
                margin: 20px 0;
                font-weight: bold;
            }
            .button:hover {
                background: linear-gradient(135deg, #5a67d8, #6b46c1);
            }
            .footer {
                text-align: center;
                padding: 20px;
                font-size: 12px;
                color: #666;
                border-top: 1px solid #eee;
                margin-top: 20px;
            }
            .warning {
                background: #fff3cd;
                border: 1px solid #ffc107;
                color: #856404;
                padding: 10px;
                border-radius: 5px;
                font-size: 12px;
                margin: 15px 0;
            }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="header">
                <h1>Urban Wheels</h1>
                <p>Premium Car Rental Services</p>
            </div>
            <div class="content">
                <h2>Hello ' . htmlspecialchars($to_name) . ',</h2>
                <p>We received a request to reset your password for your Urban Wheels account.</p>
                <p>Click the button below to create a new password:</p>
                <div style="text-align: center;">
                    <a href="' . $reset_link . '" class="button">Reset Password</a>
                </div>
                <p>Or copy and paste this link into your browser:</p>
                <p style="background: #eee; padding: 10px; border-radius: 5px; word-break: break-all;">' . $reset_link . '</p>
                <div class="warning">
                    <strong>⚠️ Security Notice:</strong><br>
                    This link will expire in <strong>1 hour</strong>.<br>
                    If you didn\'t request this, please ignore this email.
                </div>
                <p>Best regards,<br>
                <strong>Urban Wheels Team</strong></p>
            </div>
            <div class="footer">
                <p>&copy; ' . date('Y') . ' Urban Wheels Car Rental. All rights reserved.</p>
                <p>Nairobi, Kenya | +254 700 000 000</p>
            </div>
        </div>
    </body>
    </html>';
    
    // Plain text version for email clients that don't support HTML
    $alt_message = "Hello " . $to_name . ",\n\n";
    $alt_message .= "We received a request to reset your password for your Urban Wheels account.\n\n";
    $alt_message .= "Click this link to reset your password: " . $reset_link . "\n\n";
    $alt_message .= "This link will expire in 1 hour.\n\n";
    $alt_message .= "If you didn't request this, please ignore this email.\n\n";
    $alt_message .= "Best regards,\nUrban Wheels Team";
    
    // Headers
    $headers = "MIME-Version: 1.0\r\n";
    $headers .= "Content-type: text/html; charset=UTF-8\r\n";
    $headers .= "From: " . SMTP_FROM_NAME . " <" . SMTP_FROM . ">\r\n";
    $headers .= "Reply-To: " . SMTP_FROM . "\r\n";
    
    // Send email
    if (USE_MAIL_FUNCTION) {
        // Use PHP mail() function
        return mail($to_email, $subject, $message, $headers);
    } else {
        // Try to use PHPMailer if available
        if (file_exists(__DIR__ . '/PHPMailer/PHPMailer.php')) {
            require_once __DIR__ . '/PHPMailer/PHPMailer.php';
            require_once __DIR__ . '/PHPMailer/SMTP.php';
            require_once __DIR__ . '/PHPMailer/Exception.php';
            
            $mail = new PHPMailer\PHPMailer\PHPMailer(true);
            
            try {
                $mail->isSMTP();
                $mail->Host = SMTP_HOST;
                $mail->SMTPAuth = true;
                $mail->Username = SMTP_USER;
                $mail->Password = SMTP_PASS;
                $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port = SMTP_PORT;
                
                $mail->setFrom(SMTP_FROM, SMTP_FROM_NAME);
                $mail->addAddress($to_email, $to_name);
                
                $mail->isHTML(true);
                $mail->Subject = $subject;
                $mail->Body = $message;
                $mail->AltBody = $alt_message;
                
                return $mail->send();
            } catch (Exception $e) {
                error_log("Email sending failed: " . $mail->ErrorInfo);
                return false;
            }
        } else {
            // Fallback to mail function
            error_log("PHPMailer not found, falling back to mail() function");
            return mail($to_email, $subject, $message, $headers);
        }
    }
}

function sendBookingConfirmationEmail($to_email, $to_name, $booking_details) {
    $subject = "Booking Confirmation - " . SITE_NAME;
    
    $message = '
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <title>Booking Confirmation</title>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: linear-gradient(135deg, #667eea, #764ba2); padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
            .header h1 { color: #FFD700; margin: 0; }
            .header p { color: white; margin: 5px 0 0; }
            .content { background: #f9f9f9; padding: 30px; border-radius: 0 0 10px 10px; }
            .details { background: white; padding: 15px; border-radius: 8px; margin: 15px 0; border: 1px solid #eee; }
            .detail-row { padding: 8px 0; border-bottom: 1px solid #eee; }
            .detail-row:last-child { border-bottom: none; }
            .detail-label { font-weight: bold; display: inline-block; width: 140px; }
            .status-confirmed { color: #28a745; font-weight: bold; }
            .footer { text-align: center; padding: 20px; font-size: 12px; color: #666; border-top: 1px solid #eee; margin-top: 20px; }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="header">
                <h1>Urban Wheels</h1>
                <p>Premium Car Rental Services</p>
            </div>
            <div class="content">
                <h2>Dear ' . htmlspecialchars($to_name) . ',</h2>
                <p>Your booking has been <span class="status-confirmed">successfully confirmed</span>! Here are your booking details:</p>
                
                <div class="details">
                    <div class="detail-row">
                        <span class="detail-label">Booking Number:</span>
                        <span>' . htmlspecialchars($booking_details['booking_number']) . '</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Vehicle:</span>
                        <span>' . htmlspecialchars($booking_details['vehicle']) . '</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Registration:</span>
                        <span>' . htmlspecialchars($booking_details['registration_number'] ?? 'N/A') . '</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Pickup Date:</span>
                        <span>' . date('F d, Y', strtotime($booking_details['pickup_date'])) . '</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Return Date:</span>
                        <span>' . date('F d, Y', strtotime($booking_details['return_date'])) . '</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Total Amount:</span>
                        <span><strong>KES ' . number_format($booking_details['total_amount'], 2) . '</strong></span>
                    </div>
                </div>
                
                <p>You can view and manage your booking by logging into your account.</p>
                <p>If you have any questions or need to modify your booking, please contact our support team.</p>
                
                <p>Thank you for choosing Urban Wheels! We look forward to serving you.</p>
                
                <p>Best regards,<br>
                <strong>Urban Wheels Team</strong></p>
            </div>
            <div class="footer">
                <p>&copy; ' . date('Y') . ' Urban Wheels Car Rental. All rights reserved.</p>
                <p>Nairobi, Kenya | +254 700 000 000 | info@urbanwheels.com</p>
            </div>
        </div>
    </body>
    </html>';
    
    $alt_message = "Dear " . $to_name . ",\n\n";
    $alt_message .= "Your booking has been successfully confirmed!\n\n";
    $alt_message .= "Booking Details:\n";
    $alt_message .= "Booking Number: " . $booking_details['booking_number'] . "\n";
    $alt_message .= "Vehicle: " . $booking_details['vehicle'] . "\n";
    $alt_message .= "Pickup Date: " . $booking_details['pickup_date'] . "\n";
    $alt_message .= "Return Date: " . $booking_details['return_date'] . "\n";
    $alt_message .= "Total Amount: KES " . number_format($booking_details['total_amount'], 2) . "\n\n";
    $alt_message .= "Thank you for choosing Urban Wheels!\n\n";
    $alt_message .= "Best regards,\nUrban Wheels Team";
    
    $headers = "MIME-Version: 1.0\r\n";
    $headers .= "Content-type: text/html; charset=UTF-8\r\n";
    $headers .= "From: " . SMTP_FROM_NAME . " <" . SMTP_FROM . ">\r\n";
    $headers .= "Reply-To: " . SMTP_FROM . "\r\n";
    
    if (USE_MAIL_FUNCTION) {
        return mail($to_email, $subject, $message, $headers);
    } else {
        // Similar PHPMailer implementation as above
        return mail($to_email, $subject, $message, $headers); // Fallback
    }
}

// Additional email functions
function sendPaymentReceiptEmail($to_email, $to_name, $payment_details) {
    $subject = "Payment Receipt - " . SITE_NAME;
    
    $message = '
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <title>Payment Receipt</title>
        <style>
            body { font-family: Arial, sans-serif; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: linear-gradient(135deg, #28a745, #20c997); padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
            .header h1 { color: white; margin: 0; }
            .content { background: #f9f9f9; padding: 30px; border-radius: 0 0 10px 10px; }
            .receipt { background: white; padding: 20px; border-radius: 8px; margin: 15px 0; }
            .footer { text-align: center; padding: 20px; font-size: 12px; color: #666; }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="header">
                <h1>Payment Received ✓</h1>
                <p>Thank you for your payment</p>
            </div>
            <div class="content">
                <h2>Dear ' . htmlspecialchars($to_name) . ',</h2>
                <p>We have received your payment successfully.</p>
                <div class="receipt">
                    <p><strong>Receipt Number:</strong> ' . $payment_details['receipt_number'] . '</p>
                    <p><strong>Amount Paid:</strong> KES ' . number_format($payment_details['amount'], 2) . '</p>
                    <p><strong>Payment Method:</strong> ' . $payment_details['payment_method'] . '</p>
                    <p><strong>Transaction ID:</strong> ' . $payment_details['transaction_id'] . '</p>
                    <p><strong>Date:</strong> ' . date('F d, Y H:i:s') . '</p>
                </div>
                <p>Thank you for choosing Urban Wheels!</p>
            </div>
            <div class="footer">
                <p>&copy; ' . date('Y') . ' Urban Wheels Car Rental</p>
            </div>
        </div>
    </body>
    </html>';
    
    $headers = "MIME-Version: 1.0\r\n";
    $headers .= "Content-type: text/html; charset=UTF-8\r\n";
    $headers .= "From: " . SMTP_FROM_NAME . " <" . SMTP_FROM . ">\r\n";
    
    return mail($to_email, $subject, $message, $headers);
}
?>