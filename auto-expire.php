<?php
include 'config.php';

// Hapus booking pending yang lebih dari 24 jam
$query = "UPDATE bookings SET status = 'expired' 
          WHERE status = 'pending' 
          AND created_at < DATE_SUB(NOW(), INTERVAL 24 HOUR)";

if ($conn->query($query)) {
    $affected_rows = $conn->affected_rows;
    file_put_contents('expire_log.txt', date('Y-m-d H:i:s') . " - Expired {$affected_rows} bookings\n", FILE_APPEND);
} else {
    file_put_contents('expire_log.txt', date('Y-m-d H:i:s') . " - Error: " . $conn->error . "\n", FILE_APPEND);
}
?>