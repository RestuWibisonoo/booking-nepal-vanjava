<?php
include 'config.php';
include 'layouts/header.php';

$homestay_id = isset($_GET['homestay_id']) ? intval($_GET['homestay_id']) : 0;
$checkin = isset($_GET['checkin']) ? $_GET['checkin'] : '';
$checkout = isset($_GET['checkout']) ? $_GET['checkout'] : '';

// Fetch homestay data
$homestay = null;
if ($homestay_id > 0) {
    $query = "SELECT * FROM homestays WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $homestay_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $homestay = $result->fetch_assoc();
}

if (!$homestay) {
    die("Homestay tidak ditemukan!");
}

// Fetch rooms
$rooms_query = "SELECT * FROM rooms WHERE homestay_id = ?";
$stmt = $conn->prepare($rooms_query);
$stmt->bind_param("i", $homestay_id);
$stmt->execute();
$rooms_result = $stmt->get_result();
$rooms = [];
while ($room = $rooms_result->fetch_assoc()) {
    $rooms[] = $room;
}

// Fetch bookings for the next 30 days
$start_date = date('Y-m-d');
$end_date = date('Y-m-d', strtotime('+30 days'));

$bookings_query = "SELECT * FROM bookings 
                   WHERE homestay_id = ? 
                   AND ((checkin_date BETWEEN ? AND ?) OR (checkout_date BETWEEN ? AND ?))
                   AND status IN ('pending', 'confirmed')";
$stmt = $conn->prepare($bookings_query);
$stmt->bind_param("issss", $homestay_id, $start_date, $end_date, $start_date, $end_date);
$stmt->execute();
$bookings_result = $stmt->get_result();
$bookings = [];
while ($booking = $bookings_result->fetch_assoc()) {
    $bookings[] = $booking;
}
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4>Kalender Ketersediaan - <?php echo htmlspecialchars($homestay['name']); ?></h4>
                    <a href="booking.php?id=<?php echo $homestay_id; ?>" class="btn btn-primary">
                        <i class="fas fa-arrow-left"></i> Kembali ke Form Booking
                    </a>
                </div>
                <div class="card-body">
                    <?php if ($checkin && $checkout): ?>
                    <div class="alert alert-info">
                        Menampilkan ketersediaan untuk periode: 
                        <strong><?php echo date('d M Y', strtotime($checkin)); ?></strong> - 
                        <strong><?php echo date('d M Y', strtotime($checkout)); ?></strong>
                    </div>
                    <?php endif; ?>

                    <div class="row mb-4">
                        <div class="col-md-8">
                            <div id="calendar"></div>
                        </div>
                        <div class="col-md-4">
                            <div class="card">
                                <div class="card-header">
                                    <h6>Legenda</h6>
                                </div>
                                <div class="card-body">
                                    <div class="d-flex align-items-center mb-2">
                                        <span class="badge bg-success me-2" style="width: 20px; height: 20px;"></span>
                                        <span>Tersedia</span>
                                    </div>
                                    <div class="d-flex align-items-center mb-2">
                                        <span class="badge bg-warning me-2" style="width: 20px; height: 20px;"></span>
                                        <span>Pending (Booking baru)</span>
                                    </div>
                                    <div class="d-flex align-items-center mb-2">
                                        <span class="badge bg-danger me-2" style="width: 20px; height: 20px;"></span>
                                        <span>Dikonfirmasi (Sudah bayar)</span>
                                    </div>
                                    <div class="d-flex align-items-center">
                                        <span class="badge bg-secondary me-2" style="width: 20px; height: 20px;"></span>
                                        <span>Tidak Tersedia</span>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="card mt-3">
                                <div class="card-header">
                                    <h6>Detail Kamar</h6>
                                </div>
                                <div class="card-body">
                                    <?php foreach ($rooms as $room): ?>
                                        <div class="mb-2">
                                            <strong><?php echo $room['room_name']; ?></strong>
                                            <br>
                                            <small class="text-muted">Kode: <?php echo $room['code_room']; ?></small>
                                            <br>
                                            <small>Kapasitas: <?php echo $room['capacity']; ?> orang</small>
                                        </div>
                                        <hr>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<link href='https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.css' rel='stylesheet' />
<script src='https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.js'></script>
<script src='https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/locales/id.js'></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var calendarEl = document.getElementById('calendar');
    var calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: 'dayGridMonth',
        locale: 'id',
        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: 'dayGridMonth,timeGridWeek,timeGridDay'
        },
        events: [
            <?php
            foreach ($bookings as $booking) {
                $rooms_arr = json_decode($booking['rooms'], true);
                $room_codes = [];
                foreach ($rooms_arr as $room_id) {
                    foreach ($rooms as $room) {
                        if ($room['id'] == $room_id) {
                            $room_codes[] = $room['code_room'];
                            break;
                        }
                    }
                }
                
                $title = "Kamar: " . implode(", ", $room_codes) . " - " . $booking['user_name'];
                $color = $booking['status'] == 'pending' ? '#ffc107' : '#dc3545';
                
                echo "{
                    title: '" . addslashes($title) . "',
                    start: '" . $booking['checkin_date'] . "',
                    end: '" . date('Y-m-d', strtotime($booking['checkout_date'] . ' +1 day')) . "',
                    backgroundColor: '" . $color . "',
                    borderColor: '" . $color . "',
                    textColor: '#000'
                },";
            }
            ?>
        ],
        dateClick: function(info) {
            alert('Tanggal: ' + info.dateStr + '\nKlik "Kembali ke Form Booking" untuk memesan.');
        }
    });
    calendar.render();
});
</script>

<?php include 'layouts/footer.php'; ?>