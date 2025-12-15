<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sidebar</title>
    <link rel="stylesheet" href="{{ asset('css/navbar.css') }}">
    <link rel="stylesheet" href="{{ asset('css/logout-confirm.css') }}">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <script src="{{ asset('js/logout-confirm.js') }}" defer></script>
</head>
<body>
    <!-- Mobile header with hamburger and bell icon -->
    <div class="mobile-header">
        <button class="hamburger" id="hamburger">&#9776;</button>
        @if (Request::is('dashboard-professor'))
            <button class="mobile-notification-bell" id="mobileNotificationBell" onclick="toggleMobileNotifications()">
                <i class='bx bx-bell'></i>
                <span class="mobile-notification-badge" id="mobileNotificationBadge" style="display: none;">0</span>
            </button>
        @endif
    </div>
    
    <div class="sidebar" id="sidebar">
        <img src="{{ asset('images/Comsci.png') }}" alt="Logo">
        
        <!-- Simple role indicator -->
        <div class="simple-role-indicator">
            <span class="role-line professor-line"></span>
            <span class="role-label">Professor Portal</span>
        </div>
        
                <ul class="nav-links">
                    <li>
                        <a class="nav-link" href="{{ url('/dashboard-professor') }}">
                            <i class='bx bxs-bank nav-icon'></i>
                            <span>Dashboard</span>
                        </a>
                    </li>
                    <li>
                        <a class="nav-link" href="{{ url('/comsci-professor') }}">
                            <i class='bx bx-group nav-icon'></i>
                            <span>Faculty</span>
                        </a>
                    </li>
                    <li>
                        <a class="nav-link" href="{{ url('/profile-professor') }}">
                            <i class='bx bx-user-circle nav-icon'></i>
                            <span>Profile</span>
                        </a>
                    </li>
                    <li>
                        <a class="nav-link" href="{{ url('/conlog-professor') }}">
                            <i class='bx bx-notepad nav-icon'></i>
                            <span>Consultation Log</span>
                        </a>
                    </li>
                    <li>
                        <a class="nav-link" href="{{ url('/messages-professor') }}">
                            <i class='bx bx-envelope nav-icon'></i>
                            <span>Messages</span>
                        </a>
                    </li>
                    <li>
                        <x-logout-link guard="professor" label="Sign Out" class="nav-link nav-link-logout" icon="bx bx-log-out-circle" />
                    </li>
                </ul>
      </div>

    @if (Request::is('dashboard-professor'))
        <!-- Mobile Notifications Dropdown (dashboard only) -->
        <div class="mobile-notifications-dropdown" id="mobileNotificationDropdown">
            <div class="mobile-notifications-header">
                <h3>Notifications</h3>
                <button class="close-mobile-notifications" onclick="toggleMobileNotifications()">×</button>
            </div>
            <div class="mobile-notifications-content" id="mobileNotificationsContainer">
                <div class="loading-notifications">
                    <i class='bx bx-loader-alt bx-spin'></i>
                    <p>Loading notifications...</p>
                </div>
            </div>
            <div class="mobile-notifications-footer">
                <button class="mark-all-read-mobile" onclick="markAllNotificationsAsRead()">Mark All as Read</button>
            </div>
        </div>
    @endif

    <!-- Grey overlay shown when mobile sidebar is open (professor pages) -->
    <div id="sidebarOverlay" class="sidebar-overlay" aria-hidden="true"></div>

    <script>
        // Only run if hamburger exists (mobile)
        const hamburger = document.getElementById('hamburger');
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('sidebarOverlay');
        if (hamburger) {
            hamburger.addEventListener('click', () => {
                sidebar.classList.toggle('active');
                hamburger.classList.toggle('active');
                if(overlay){ overlay.classList.toggle('active', sidebar.classList.contains('active')); }
                if(document.body){
                    if(sidebar.classList.contains('active')){ document.body.classList.add('no-scroll'); }
                    else { document.body.classList.remove('no-scroll'); }
                }
            });
        }
        if(overlay){
            overlay.addEventListener('click', () => {
                sidebar.classList.remove('active');
                hamburger && hamburger.classList.remove('active');
                overlay.classList.remove('active');
                document.body && document.body.classList.remove('no-scroll');
            });
        }

    // Mobile notifications toggle (only exists on dashboard-professor)
        function toggleMobileNotifications() {
            const dropdown = document.getElementById('mobileNotificationDropdown');
            if (dropdown && dropdown.classList) {
                dropdown.classList.toggle('active');
                
                // Close sidebar if open
                if (sidebar && sidebar.classList && sidebar.classList.contains('active')) {
                    sidebar.classList.remove('active');
                    hamburger.classList.remove('active');
                    overlay && overlay.classList.remove('active');
                    document.body && document.body.classList.remove('no-scroll');
                }
            }
        }

    // Close mobile notifications when clicking outside (dashboard-professor only)
        document.addEventListener('click', function(event) {
            const dropdown = document.getElementById('mobileNotificationDropdown');
            const bell = document.getElementById('mobileNotificationBell');
            
            if (dropdown && dropdown.classList && bell && !dropdown.contains(event.target) && !bell.contains(event.target)) {
                dropdown.classList.remove('active');
            }
        });
    </script>
</body>
</html>