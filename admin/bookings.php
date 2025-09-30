<?php
session_start();
include '../config.php';
include 'sidebar.php';

// Check login
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: index.php');
    exit();
}

$user_id = $_SESSION['admin_id'];
$user_role = $_SESSION['admin_role'];

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

// Delete booking
if (isset($_GET['delete'])) {
    $booking_id = intval($_GET['delete']);
    
    $delete_query = "DELETE FROM bookings WHERE id = ?";
    $stmt = $conn->prepare($delete_query);
    $stmt->bind_param("i", $booking_id);
    
    if ($stmt->execute()) {
        $success = "Booking berhasil dihapus!";
    } else {
        $error = "Error deleting booking: " . $stmt->error;
    }
}

// Get bookings based on role
if ($user_role == 'admin') {
    $bookings_query = "SELECT b.*, h.name as homestay_name 
                      FROM bookings b 
                      JOIN homestays h ON b.homestay_id = h.id 
                      ORDER BY b.created_at DESC";
} else {
    $bookings_query = "SELECT b.*, h.name as homestay_name 
                      FROM bookings b 
                      JOIN homestays h ON b.homestay_id = h.id 
                      WHERE h.owner_id = $user_id 
                      ORDER BY b.created_at DESC";
}

$bookings_result = $conn->query($bookings_query);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Booking - Admin Nepal Van Java</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <div class="main-content">
        <div class="container-fluid">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2>Kelola Booking</h2>
                <div class="btn-group">
                    <a href="dashboard.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Kembali
                    </a>
                </div>
            </div>

            <?php if (isset($success)): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <?php if (isset($error)): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>

            <div class="card">
                <div class="card-header">
                    <h5>Daftar Semua Booking</h5>
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
                                    <th>Tamu</th>
                                    <th>Status</th>
                                    <th>Tanggal Booking</th>
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
                                    <td>#<?php echo $booking['id']; ?></td>
                                    <td><?php echo htmlspecialchars($booking['homestay_name']); ?></td>
                                    <td><?php echo htmlspecialchars($booking['user_name']); ?></td>
                                    <td><?php echo htmlspecialchars($booking['user_phone']); ?></td>
                                    <td><?php echo implode(", ", $room_codes); ?></td>
                                    <td><?php echo date('d M Y', strtotime($booking['checkin_date'])); ?></td>
                                    <td><?php echo date('d M Y', strtotime($booking['checkout_date'])); ?></td>
                                    <td><?php echo $booking['guests']; ?> orang</td>
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
                                    <td><?php echo date('d M Y H:i', strtotime($booking['created_at'])); ?></td>
                                    <td>
                                        <button class="btn btn-sm btn-info" onclick="viewBooking(<?php echo $booking['id']; ?>)">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <a href="?delete=<?php echo $booking['id']; ?>" class="btn btn-sm btn-danger" 
                                           onclick="return confirm('Yakin hapus booking ini?')">
                                            <i class="fas fa-trash"></i>
                                        </a>
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

    <!-- Modal View Booking -->
    <div class="modal fade" id="viewBookingModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Detail Booking</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="bookingDetails">
                    <!-- Details will be loaded via AJAX -->
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    function viewBooking(bookingId) {
        fetch(`get_booking_details.php?id=${bookingId}`)
            .then(response => response.text())
            .then(data => {
                document.getElementById('bookingDetails').innerHTML = data;
                new bootstrap.Modal(document.getElementById('viewBookingModal')).show();
            });
    }
    </script>
</body>
</html>