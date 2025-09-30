<?php
session_start();
include '../config.php';
include 'sidebar.php';

// Check login
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: index.php');
    exit();
}

// Get statistics
$user_id = $_SESSION['admin_id'];
$user_role = $_SESSION['admin_role'];

// Query based on role
if ($user_role == 'admin') {
    // Admin can see all
    $total_bookings_query = "SELECT COUNT(*) as total FROM bookings";
    $pending_bookings_query = "SELECT COUNT(*) as total FROM bookings WHERE status = 'pending'";
    $confirmed_bookings_query = "SELECT COUNT(*) as total FROM bookings WHERE status = 'confirmed'";
    $total_homestays_query = "SELECT COUNT(*) as total FROM homestays";
} else {
    // Owner can only see their homestays
    $total_bookings_query = "SELECT COUNT(*) as total FROM bookings b 
                            JOIN homestays h ON b.homestay_id = h.id 
                            WHERE h.owner_id = $user_id";
    $pending_bookings_query = "SELECT COUNT(*) as total FROM bookings b 
                              JOIN homestays h ON b.homestay_id = h.id 
                              WHERE h.owner_id = $user_id AND b.status = 'pending'";
    $confirmed_bookings_query = "SELECT COUNT(*) as total FROM bookings b 
                                JOIN homestays h ON b.homestay_id = h.id 
                                WHERE h.owner_id = $user_id AND b.status = 'confirmed'";
    $total_homestays_query = "SELECT COUNT(*) as total FROM homestays WHERE owner_id = $user_id";
}

// Execute queries
$total_bookings = $conn->query($total_bookings_query)->fetch_assoc()['total'];
$pending_bookings = $conn->query($pending_bookings_query)->fetch_assoc()['total'];
$confirmed_bookings = $conn->query($confirmed_bookings_query)->fetch_assoc()['total'];
$total_homestays = $conn->query($total_homestays_query)->fetch_assoc()['total'];

// Recent bookings
if ($user_role == 'admin') {
    $recent_bookings_query = "SELECT b.*, h.name as homestay_name 
                             FROM bookings b 
                             JOIN homestays h ON b.homestay_id = h.id 
                             ORDER BY b.created_at DESC 
                             LIMIT 5";
} else {
    $recent_bookings_query = "SELECT b.*, h.name as homestay_name 
                             FROM bookings b 
                             JOIN homestays h ON b.homestay_id = h.id 
                             WHERE h.owner_id = $user_id 
                             ORDER BY b.created_at DESC 
                             LIMIT 5";
}
$recent_bookings = $conn->query($recent_bookings_query);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Admin Nepal Van Java</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <div class="main-content">
        <div class="container-fluid">
            <!-- Header -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2>Dashboard</h2>
                <div class="text-muted">
                    <i class="fas fa-calendar"></i> <?php echo date('d F Y'); ?>
                </div>
            </div>

            <!-- Statistics Cards -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card bg-primary text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h4><?php echo $total_bookings; ?></h4>
                                    <p>Total Booking</p>
                                </div>
                                <div class="align-self-center">
                                    <i class="fas fa-calendar-check fa-2x"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-warning text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h4><?php echo $pending_bookings; ?></h4>
                                    <p>Pending</p>
                                </div>
                                <div class="align-self-center">
                                    <i class="fas fa-clock fa-2x"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-success text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h4><?php echo $confirmed_bookings; ?></h4>
                                    <p>Dikonfirmasi</p>
                                </div>
                                <div class="align-self-center">
                                    <i class="fas fa-check-circle fa-2x"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-info text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h4><?php echo $total_homestays; ?></h4>
                                    <p>Homestay</p>
                                </div>
                                <div class="align-self-center">
                                    <i class="fas fa-hotel fa-2x"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Bookings -->
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5>Booking Terbaru</h5>
                            <a href="bookings.php" class="btn btn-sm btn-primary">Lihat Semua</a>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Homestay</th>
                                            <th>Nama Pemesan</th>
                                            <th>Check-in</th>
                                            <th>Check-out</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while ($booking = $recent_bookings->fetch_assoc()): ?>
                                        <tr>
                                            <td>#<?php echo $booking['id']; ?></td>
                                            <td><?php echo htmlspecialchars($booking['homestay_name']); ?></td>
                                            <td><?php echo htmlspecialchars($booking['user_name']); ?></td>
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
                                        </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</body>
</html>