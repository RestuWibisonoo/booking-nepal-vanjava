<?php
session_start();
include '../config.php';
include 'sidebar.php';

// Check login
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: index.php');
    exit();
}

// Get homestays for this owner (if role is owner)
if ($_SESSION['admin_role'] === 'owner') {
    $homestay_query = "SELECT * FROM homestays WHERE owner_id = ?";
    $stmt = $conn->prepare($homestay_query);
    $stmt->bind_param("i", $_SESSION['admin_id']);
    $stmt->execute();
    $homestays_result = $stmt->get_result();
    $homestays = [];
    while ($homestay = $homestays_result->fetch_assoc()) {
        $homestays[] = $homestay;
    }
    
    // If owner has homestays, get bookings for their homestays
    if (!empty($homestays)) {
        $homestay_ids = array_column($homestays, 'id');
        $placeholders = str_repeat('?,', count($homestay_ids) - 1) . '?';
        
        $bookings_query = "SELECT b.*, h.name as homestay_name 
                          FROM bookings b 
                          JOIN homestays h ON b.homestay_id = h.id 
                          WHERE b.homestay_id IN ($placeholders) 
                          ORDER BY b.created_at DESC";
        $stmt = $conn->prepare($bookings_query);
        $stmt->bind_param(str_repeat('i', count($homestay_ids)), ...$homestay_ids);
        $stmt->execute();
        $bookings_result = $stmt->get_result();
    }
} else {
    // Admin can see all bookings
    $bookings_query = "SELECT b.*, h.name as homestay_name 
                      FROM bookings b 
                      JOIN homestays h ON b.homestay_id = h.id 
                      ORDER BY b.created_at DESC";
    $bookings_result = $conn->query($bookings_query);
}

// Update booking status
if (isset($_POST['update_status'])) {
    $booking_id = intval($_POST['booking_id']);
    $status = $conn->real_escape_string($_POST['status']);
    
    $update_query = "UPDATE bookings SET status = ? WHERE id = ?";
    $stmt = $conn->prepare($update_query);
    $stmt->bind_param("si", $status, $booking_id);
    
    if ($stmt->execute()) {
        $success = "Status booking berhasil diupdate!";
    } else {
        $error = "Error updating status: " . $stmt->error;
    }
}
?>

<div class="main-content">
    <div class="container-fluid">
        <h2 class="my-4">Dashboard Booking</h2>
        
        <?php if (isset($success)): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>
        
        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>

        <div class="card">
            <div class="card-header">
                <h5>Daftar Booking</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Homestay</th>
                                <th>Nama Pemesan</th>
                                <th>Telepon</th>
                                <th>Kamar</th>
                                <th>Check-in</th>
                                <th>Check-out</th>
                                <th>Status</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($booking = $bookings_result->fetch_assoc()): 
                                $rooms_arr = json_decode($booking['rooms'], true);
                                $room_codes = [];
                                foreach ($rooms_arr as $room_id) {
                                    $room_query = "SELECT code_room FROM rooms WHERE id = ?";
                                    $stmt = $conn->prepare($room_query);
                                    $stmt->bind_param("i", $room_id);
                                    $stmt->execute();
                                    $room_result = $stmt->get_result();
                                    if ($room = $room_result->fetch_assoc()) {
                                        $room_codes[] = $room['code_room'];
                                    }
                                }
                            ?>
                            <tr>
                                <td><?php echo $booking['id']; ?></td>
                                <td><?php echo htmlspecialchars($booking['homestay_name']); ?></td>
                                <td><?php echo htmlspecialchars($booking['user_name']); ?></td>
                                <td><?php echo htmlspecialchars($booking['user_phone']); ?></td>
                                <td><?php echo implode(", ", $room_codes); ?></td>
                                <td><?php echo date('d M Y', strtotime($booking['checkin_date'])); ?></td>
                                <td><?php echo date('d M Y', strtotime($booking['checkout_date'])); ?></td>
                                <td>
                                    <span class="badge bg-<?php 
                                        echo $booking['status'] == 'confirmed' ? 'success' : 
                                             ($booking['status'] == 'pending' ? 'warning' : 'secondary'); 
                                    ?>">
                                        <?php echo ucfirst($booking['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <form method="POST" class="d-inline">
                                        <input type="hidden" name="booking_id" value="<?php echo $booking['id']; ?>">
                                        <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                                            <option value="pending" <?php echo $booking['status'] == 'pending' ? 'selected' : ''; ?>>Pending</option>
                                            <option value="confirmed" <?php echo $booking['status'] == 'confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                                            <option value="expired" <?php echo $booking['status'] == 'expired' ? 'selected' : ''; ?>>Expired</option>
                                        </select>
                                        <input type="hidden" name="update_status" value="1">
                                    </form>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'sidebar.php'; ?>