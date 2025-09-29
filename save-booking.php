<?php
include 'config.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $homestay_id = intval($_POST['homestay_id']);
    $user_name = $conn->real_escape_string($_POST['user_name']);
    $user_phone = $conn->real_escape_string($_POST['user_phone']);
    $checkin_date = $conn->real_escape_string($_POST['checkin_date']);
    $checkout_date = $conn->real_escape_string($_POST['checkout_date']);
    $guests = intval($_POST['guests']);
    $rooms = isset($_POST['rooms']) ? $_POST['rooms'] : [];

    // Validation
    if (empty($rooms)) {
        die("Pilih minimal 1 kamar");
    }

    // Check if rooms are available
    $rooms_json = json_encode($rooms);

    // Insert booking
    $query = "INSERT INTO bookings (homestay_id, user_name, user_phone, rooms, checkin_date, checkout_date, guests, status) 
              VALUES (?, ?, ?, ?, ?, ?, ?, 'pending')";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("isssssi", $homestay_id, $user_name, $user_phone, $rooms_json, $checkin_date, $checkout_date, $guests);
    
    if ($stmt->execute()) {
        $booking_id = $stmt->insert_id;
        
        // Get homestay data for redirect
        $homestay_query = "SELECT payment_url FROM homestays WHERE id = ?";
        $stmt2 = $conn->prepare($homestay_query);
        $stmt2->bind_param("i", $homestay_id);
        $stmt2->execute();
        $result = $stmt2->get_result();
        $homestay = $result->fetch_assoc();
        
        // Redirect to payment page with booking info
        $redirect_url = $homestay['payment_url'] . "?booking_id=" . $booking_id . "&name=" . urlencode($user_name);
        header("Location: " . $redirect_url);
        exit();
    } else {
        die("Error: " . $stmt->error);
    }
} else {
    header("Location: booking.php");
    exit();
}
?>