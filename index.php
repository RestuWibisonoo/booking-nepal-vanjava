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

<!-- Modal Konfirmasi -->
<div class="modal fade" id="confirmModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Konfirmasi Booking</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Apakah data booking sudah benar?</p>
                <div class="booking-summary">
                    <strong>Nama:</strong> <span id="summaryName">-</span><br>
                    <strong>Telepon:</strong> <span id="summaryPhone">-</span><br>
                    <strong>Check-in:</strong> <span id="summaryCheckin">-</span><br>
                    <strong>Check-out:</strong> <span id="summaryCheckout">-</span><br>
                    <strong>Jumlah Tamu:</strong> <span id="summaryGuests">-</span><br>
                    <strong>Kamar:</strong> <span id="summaryRooms">-</span>
                </div>
                <div class="alert alert-warning mt-3">
                    <small>
                        <i class="fas fa-exclamation-triangle"></i>
                        Booking akan kadaluarsa otomatis dalam 24 jam jika belum melakukan pembayaran.
                    </small>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Periksa Kembali</button>
                <button type="button" class="btn btn-primary" onclick="proceedToPayment()">
                    <i class="fas fa-credit-card"></i> Lanjutkan ke Pembayaran
                </button>
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

// Form validation and modal
document.getElementById('bookingForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    // Validasi dasar
    const checkin = new Date(document.getElementById('checkin_date').value);
    const checkout = new Date(document.getElementById('checkout_date').value);
    const today = new Date();
    today.setHours(0, 0, 0, 0);
    
    if (checkin < today) {
        alert('Tanggal check-in tidak boleh kurang dari hari ini');
        return;
    }
    
    if (checkout <= checkin) {
        alert('Tanggal check-out harus setelah tanggal check-in');
        return;
    }
    
    const selectedRooms = document.querySelectorAll('.room-checkbox:checked');
    if (selectedRooms.length === 0) {
        alert('Pilih minimal 1 kamar');
        return;
    }
    
    // Tampilkan modal konfirmasi
    const modal = new bootstrap.Modal(document.getElementById('confirmModal'));
    modal.show();
});

// Fungsi untuk lanjutkan ke pembayaran
function proceedToPayment() {
    const submitBtn = document.getElementById('submitBtn');
    const form = document.getElementById('bookingForm');
    
    // Show loading
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Mengarahkan ke Pembayaran...';
    submitBtn.disabled = true;
    
    // Submit form
    form.submit();
}

// Update summary ketika modal dibuka
document.getElementById('confirmModal').addEventListener('show.bs.modal', function() {
    document.getElementById('summaryName').textContent = document.getElementById('user_name').value;
    document.getElementById('summaryPhone').textContent = document.getElementById('user_phone').value;
    document.getElementById('summaryCheckin').textContent = document.getElementById('checkin_date').value;
    document.getElementById('summaryCheckout').textContent = document.getElementById('checkout_date').value;
    document.getElementById('summaryGuests').textContent = document.getElementById('guests').value;
    
    // Summary rooms
    const selectedRooms = Array.from(document.querySelectorAll('.room-checkbox:checked'))
        .map(checkbox => {
            const label = checkbox.nextElementSibling.textContent.split('(')[0].trim();
            const code = checkbox.nextElementSibling.nextElementSibling.textContent;
            return `${label} (${code})`;
        });
    document.getElementById('summaryRooms').textContent = selectedRooms.join(', ');
});

// Format tanggal untuk display yang lebih baik
document.addEventListener('DOMContentLoaded', function() {
    const checkinInput = document.getElementById('checkin_date');
    const checkoutInput = document.getElementById('checkout_date');
    
    // Set min date to today
    const today = new Date().toISOString().split('T')[0];
    checkinInput.min = today;
    
    // Update checkout min date when checkin changes
    checkinInput.addEventListener('change', function() {
        checkoutInput.min = this.value;
        if (checkoutInput.value && checkoutInput.value < this.value) {
            checkoutInput.value = '';
        }
    });
});
</script>

<?php include 'layouts/footer.php'; ?>