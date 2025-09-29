<?php
include 'config.php';
include 'layouts/header.php';

// Get homestay ID from URL
$homestay_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

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

// Fetch rooms for this homestay
$rooms_query = "SELECT * FROM rooms WHERE homestay_id = ?";
$stmt = $conn->prepare($rooms_query);
$stmt->bind_param("i", $homestay_id);
$stmt->execute();
$rooms_result = $stmt->get_result();
$rooms = [];
while ($room = $rooms_result->fetch_assoc()) {
    $rooms[] = $room;
}
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h4>Form Pemesanan - <?php echo htmlspecialchars($homestay['name']); ?></h4>
                </div>
                <div class="card-body">
                    <form id="bookingForm" action="save-booking.php" method="POST">
                        <input type="hidden" name="homestay_id" value="<?php echo $homestay_id; ?>">
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="user_name" class="form-label">Nama Lengkap</label>
                                    <input type="text" class="form-control" id="user_name" name="user_name" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="user_phone" class="form-label">Nomor WhatsApp</label>
                                    <input type="tel" class="form-control" id="user_phone" name="user_phone" required>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="checkin_date" class="form-label">Tanggal Check-in</label>
                                    <input type="date" class="form-control" id="checkin_date" name="checkin_date" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="checkout_date" class="form-label">Tanggal Check-out</label>
                                    <input type="date" class="form-control" id="checkout_date" name="checkout_date" required>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="guests" class="form-label">Jumlah Tamu</label>
                            <input type="number" class="form-control" id="guests" name="guests" min="1" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Pilih Kamar</label>
                            <div id="roomsContainer">
                                <?php foreach ($rooms as $room): ?>
                                    <div class="form-check mb-2">
                                        <input class="form-check-input room-checkbox" type="checkbox" 
                                               name="rooms[]" value="<?php echo $room['id']; ?>" 
                                               id="room_<?php echo $room['id']; ?>"
                                               data-capacity="<?php echo $room['capacity']; ?>">
                                        <label class="form-check-label" for="room_<?php echo $room['id']; ?>">
                                            <?php echo htmlspecialchars($room['room_name']); ?> 
                                            (Maks. <?php echo $room['capacity']; ?> orang)
                                        </label>
                                        <span class="badge bg-secondary ms-2 room-code"><?php echo $room['code_room']; ?></span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <div class="mb-3">
                            <button type="button" class="btn btn-info btn-sm" onclick="checkAvailability()">
                                <i class="fas fa-calendar-check"></i> Cek Ketersediaan Kamar
                            </button>
                        </div>

                        <div class="alert alert-info">
                            <strong>Perhatian:</strong> Booking akan kadaluarsa otomatis dalam 24 jam jika belum dikonfirmasi pembayaran.
                        </div>

                        <button type="submit" class="btn btn-primary w-100" id="submitBtn">
                            <i class="fas fa-check"></i> Konfirmasi Booking
                        </button>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h5>Kalender Ketersediaan</h5>
                </div>
                <div class="card-body">
                    <div id="calendar"></div>
                    <div class="mt-3">
                        <small class="text-muted">
                            <span class="badge bg-warning me-1">●</span> Pending
                            <span class="badge bg-success me-1">●</span> Dikonfirmasi
                            <span class="badge bg-secondary me-1">●</span> Tidak Tersedia
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function checkAvailability() {
    const checkin = document.getElementById('checkin_date').value;
    const checkout = document.getElementById('checkout_date').value;
    const homestayId = <?php echo $homestay_id; ?>;
    
    if (!checkin || !checkout) {
        alert('Harap pilih tanggal check-in dan check-out terlebih dahulu');
        return;
    }
    
    // Redirect to calendar page with dates
    window.location.href = `calendar.php?homestay_id=${homestayId}&checkin=${checkin}&checkout=${checkout}`;
}

// Calendar functionality
document.addEventListener('DOMContentLoaded', function() {
    const calendarEl = document.getElementById('calendar');
    // Simple calendar implementation - you can integrate with FullCalendar later
    calendarEl.innerHTML = '<p class="text-center"><a href="calendar.php?homestay_id=<?php echo $homestay_id; ?>" class="btn btn-outline-primary btn-sm">Lihat Kalender Lengkap</a></p>';
});

// Form validation
document.getElementById('bookingForm').addEventListener('submit', function(e) {
    const checkin = new Date(document.getElementById('checkin_date').value);
    const checkout = new Date(document.getElementById('checkout_date').value);
    const today = new Date();
    today.setHours(0, 0, 0, 0);
    
    if (checkin < today) {
        e.preventDefault();
        alert('Tanggal check-in tidak boleh kurang dari hari ini');
        return;
    }
    
    if (checkout <= checkin) {
        e.preventDefault();
        alert('Tanggal check-out harus setelah tanggal check-in');
        return;
    }
    
    const selectedRooms = document.querySelectorAll('.room-checkbox:checked');
    if (selectedRooms.length === 0) {
        e.preventDefault();
        alert('Pilih minimal 1 kamar');
        return;
    }
    
    // Show loading
    document.getElementById('submitBtn').innerHTML = '<i class="fas fa-spinner fa-spin"></i> Memproses...';
    document.getElementById('submitBtn').disabled = true;
});
</script>

<?php include 'layouts/footer.php'; ?>