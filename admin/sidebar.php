<?php
// Check if user is logged in
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: index.php');
    exit();
}
?>

<!-- Sidebar -->
<div class="sidebar bg-dark text-white">
    <div class="sidebar-header p-3">
        <h5 class="text-center">
            <i class="fas fa-home"></i><br>
            Nepal Van Java
        </h5>
        <hr class="bg-light">
        <div class="user-info text-center">
            <small>
                <i class="fas fa-user"></i> <?php echo $_SESSION['admin_name']; ?><br>
                <span class="badge bg-<?php echo $_SESSION['admin_role'] == 'admin' ? 'danger' : 'info'; ?>">
                    <?php echo ucfirst($_SESSION['admin_role']); ?>
                </span>
            </small>
        </div>
    </div>
    
    <nav class="sidebar-nav">
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link text-white <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active bg-primary' : ''; ?>" 
                   href="dashboard.php">
                    <i class="fas fa-tachometer-alt"></i> Dashboard
                </a>
            </li>
            
            <?php if ($_SESSION['admin_role'] == 'admin'): ?>
            <li class="nav-item">
                <a class="nav-link text-white" href="homestays.php">
                    <i class="fas fa-hotel"></i> Kelola Homestay
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link text-white" href="users.php">
                    <i class="fas fa-users"></i> Kelola User
                </a>
            </li>
            <?php endif; ?>
            
            <li class="nav-item">
                <a class="nav-link text-white" href="bookings.php">
                    <i class="fas fa-calendar-check"></i> Kelola Booking
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link text-white" href="rooms.php">
                    <i class="fas fa-bed"></i> Kelola Kamar
                </a>
            </li>
            <li class="nav-item mt-3">
                <a class="nav-link text-warning" href="logout.php">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </li>
        </ul>
    </nav>
</div>

<style>
.sidebar {
    width: 250px;
    height: 100vh;
    position: fixed;
    left: 0;
    top: 0;
    z-index: 1000;
}

.sidebar-nav .nav-link {
    padding: 12px 20px;
    border-left: 3px solid transparent;
    transition: all 0.3s;
}

.sidebar-nav .nav-link:hover {
    background-color: #343a40;
    border-left-color: #007bff;
}

.sidebar-nav .nav-link.active {
    background-color: #007bff;
    border-left-color: #0056b3;
}

.main-content {
    margin-left: 250px;
    padding: 20px;
}

@media (max-width: 768px) {
    .sidebar {
        width: 100%;
        height: auto;
        position: relative;
    }
    .main-content {
        margin-left: 0;
    }
}
</style>