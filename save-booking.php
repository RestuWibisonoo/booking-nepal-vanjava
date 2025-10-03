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

    // Validate dates
    $checkin = new DateTime($checkin_date);
    $checkout = new DateTime($checkout_date);
    $today = new DateTime();
    $today->setTime(0, 0, 0);

    if ($checkin < $today) {
        die("Tanggal check-in tidak boleh kurang dari hari ini");
    }

    if ($checkout <= $checkin) {
        die("Tanggal check-out harus setelah tanggal check-in");
    }

    // Check room availability
    foreach ($rooms as $room_id) {
        $room_id = intval($room_id);
        $availability_query = "SELECT COUNT(*) as count FROM bookings 
                              WHERE JSON_CONTAINS(rooms, ?) 
                              AND status IN ('pending', 'confirmed')
                              AND ((checkin_date BETWEEN ? AND DATE_SUB(?, INTERVAL 1 DAY)) 
                              OR (checkout_date BETWEEN DATE_ADD(?, INTERVAL 1 DAY) AND ?))";
        
        $stmt = $conn->prepare($availability_query);
        $room_json = json_encode($room_id);
        $stmt->bind_param("sssss", $room_json, $checkin_date, $checkout_date, $checkin_date, $checkout_date);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        
        if ($row['count'] > 0) {
            die("Maaf, salah satu kamar yang dipilih tidak tersedia pada tanggal tersebut.");
        }
    }

    // Convert rooms array to JSON
    $rooms_json = json_encode($rooms);

    // Insert booking
    $query = "INSERT INTO bookings (homestay_id, user_name, user_phone, rooms, checkin_date, checkout_date, guests, status) 
              VALUES (?, ?, ?, ?, ?, ?, ?, 'pending')";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("isssssi", $homestay_id, $user_name, $user_phone, $rooms_json, $checkin_date, $checkout_date, $guests);
    
    if ($stmt->execute()) {
        $booking_id = $stmt->insert_id;
        
        // Get homestay payment URL
        $homestay_query = "SELECT payment_url FROM homestays WHERE id = ?";
        $stmt2 = $conn->prepare($homestay_query);
        $stmt2->bind_param("i", $homestay_id);
        $stmt2->execute();
        $result = $stmt2->get_result();
        $homestay = $result->fetch_assoc();
        
        if ($homestay && !empty($homestay['payment_url'])) {
            // Redirect langsung ke payment URL tanpa parameter
            header("Location: " . $homestay['payment_url']);
            exit();
        } else {
            // Fallback jika tidak ada payment URL
            echo "<script>
                alert('Booking berhasil dengan ID: {$booking_id}! Silakan lanjutkan pembayaran.');
                window.location.href = 'index.php?id={$homestay_id}';
            </script>";
        }
    } else {
        die("Error: " . $stmt->error);
    }
} else {
    header("Location: index.php");
    exit();
}
?>