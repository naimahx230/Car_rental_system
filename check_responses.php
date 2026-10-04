<?php
require_once '../config/database.php';

$result = mysqli_query($conn, "SELECT COUNT(*) as total FROM questionnaire_responses");
$row = mysqli_fetch_assoc($result);

echo "Total responses received: " . $row['total'];
?>