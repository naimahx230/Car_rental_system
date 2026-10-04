<?php
require_once '../config/database.php';

header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="questionnaire_responses_' . date('Y-m-d') . '.csv"');

$output = fopen('php://output', 'w');

// Headers
fputcsv($output, [
    'ID', 'Name', 'Email', 'Phone', 'Role', 'Role Other', 'Experience',
    'Booking Method', 'Double Booking', 'Booking Efficiency', 'Booking Challenges',
    'Tracking Method', 'Tracking Difficulty', 'Tracking Challenges',
    'Record Storage', 'Records Lost', 'Record Problems',
    'Payment Recording', 'Payment Errors', 'Payment Suggestions',
    'M-Pesa Usage', 'M-Pesa Challenges', 'Deposit Experience',
    'Cancellation Experience', 'Cancellation Satisfaction', 'Cancellation Suggestions',
    'Support Rating', 'Support Feedback', 'Notification Preference',
    'Favorite Feature', 'Missing Features', 'Ease of Use', 'Improvement Suggestions',
    'Additional Comments', 'Submission Date'
]);

$result = mysqli_query($conn, "SELECT * FROM questionnaire_responses ORDER BY submission_date DESC");

while($row = mysqli_fetch_assoc($result)) {
    fputcsv($output, [
        $row['id'],
        $row['respondent_name'],
        $row['respondent_email'],
        $row['respondent_phone'],
        $row['role'],
        $row['role_other'],
        $row['experience'],
        $row['booking_method'],
        $row['double_booking'],
        $row['booking_efficiency'],
        $row['booking_challenges'],
        $row['tracking_method'],
        $row['tracking_difficulty'],
        $row['tracking_challenges'],
        $row['record_storage'],
        $row['records_lost'],
        $row['record_problems'],
        $row['payment_recording'],
        $row['payment_errors'],
        $row['payment_suggestions'],
        $row['mpesa_usage'],
        $row['mpesa_challenges'],
        $row['deposit_experience'],
        $row['cancellation_experience'],
        $row['cancellation_satisfaction'],
        $row['cancellation_suggestions'],
        $row['support_rating'],
        $row['support_feedback'],
        $row['notification_preference'],
        $row['favorite_feature'],
        $row['missing_features'],
        $row['ease_of_use'],
        $row['improvement_suggestions'],
        $row['additional_comments'],
        $row['submission_date']
    ]);
}

fclose($output);
?>