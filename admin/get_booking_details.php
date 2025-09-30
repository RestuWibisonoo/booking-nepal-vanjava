<?php
session_start();
include '../config.php';

// Check login
if (!isset($_SESSION['admin_logged_in'])) {
    die("Unauthorized");
}

$booking_id = intval($_GET['id']);

// Get booking details
$query = "SELECT b.*, h.name as homestay_name, h.address 
          FROM bookings b 
          JOIN homestays h ON b.homestay_id = h.id 
          WHERE b.id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $booking_id);
$stmt->execute();
$result = $stmt->get_result();
$booking = $result->fetch_assoc();

if (!$booking) {
    die("Booking tidak ditemukan");
}

// Get room details
$rooms_arr = json_decode($booking['rooms'], true);
$rooms_details = [];
foreach ($rooms_arr as $room_id) {
    $room_query = "SELECT * FROM rooms WHERE id = ?";
    $stmt = $conn->prepare($room_query);
    $stmt->bind_param("i", $room_id);
    $stmt->execute();
    $room_result = $stmt->get_result();
    if ($room = $room_result->fetch_assoc()) {
        $rooms_details[] = $room;
    }
}
?>

<div class="row">
    <div class="col-md-6">
        <h6>Informasi Pemesan</h6>
        <p>
            <strong>Nama:</strong> <?php echo htmlspecialchars($booking['user_name']); ?><br>
            <strong>Telepon:</strong> <?php echo htmlspecialchars($booking['user_phone']); ?><br>
            <strong>Jumlah Tamu:</strong> <?php echo $booking['guests']; ?> orang
        </p>
    </div>
    <div class="col-md-6">
        <h6>Informasi Homestay</h6>
        <p>
            <strong>Nama:</strong> <?php echo htmlspecialchars($booking['homestay_name']); ?><br>
            <strong>Alamat:</strong> <?php echo htmlspecialchars($booking['address']); ?>
        </p>
    </div>
</div>

<div class="row mt-3">
    <div class="col-md-6">
        <h6>Periode Menginap</h6>
        <p>
            <strong>Check-in:</strong> <?php echo date('d F Y', strtotime($booking['checkin_date'])); ?><br>
            <strong>Check-out:</strong> <?php echo date('d F Y', strtotime($booking['checkout_date'])); ?><br>
            <strong>Lama Menginap:</strong> 
            <?php 
                $checkin = new DateTime($booking['checkin_date']);
                $checkout = new DateTime($booking['checkout_date']);
                $interval = $checkin->diff($checkout);
                echo $interval->days . " malam";
            ?>
        </p>
    </div>
    <div class="col-md-6">
        <h6>Status Booking</h6>
        <p>
            <strong>Status:</strong> 
            <span class="badge bg-<?php 
                echo $booking['status'] == 'confirmed' ? 'success' : 
                     ($booking['status'] == 'pending' ? 'warning' : 'secondary'); 
            ?>">
                <?php echo ucfirst($booking['status']); ?>
            </span><br>
            <strong>Tanggal Booking:</strong> <?php echo date('d F Y H:i', strtotime($booking['created_at'])); ?>
        </p>
    </div>
</div>

<div class="row mt-3">
    <div class="col-12">
        <h6>Kamar yang Dipesan</h6>
        <div class="table-responsive">
            <table class="table table-sm">
                <thead>
                    <tr>
                        <th>Kode Kamar</th>
                        <th>Nama Kamar</th>
                        <th>Kapasitas</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rooms_details as $room): ?>
                    <tr>
                        <td><?php echo $room['code_room']; ?></td>
                        <td><?php echo htmlspecialchars($room['room_name']); ?></td>
                        <td><?php echo $room['capacity']; ?> orang</td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>