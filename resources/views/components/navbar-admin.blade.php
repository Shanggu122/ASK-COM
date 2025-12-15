@php($current = request()->path())
<!-- Hamburger button for mobile -->
<button class="hamburger" id="hamburger">&#9776;</button>
<div class="sidebar" id="sidebar">
        <img src="{{ asset('images/Comsci.png') }}" alt="Logo">
        <!-- Simple role indicator -->
        <div class="simple-role-indicator">
                <span class="role-line admin-line"></span>
                <span class="role-label">Admin Portal</span>
        </div>
        <ul class="nav-links">
            <li>
                <a class="nav-link {{ str_starts_with($current,'admin-dashboard') ? 'active' : '' }}" href="{{ route('admin.dashboard') }}">
                    <i class='bx bxs-bank nav-icon'></i>
                    <span>Dashboard</span>
                </a>
            </li>
            <li>
                <a class="nav-link {{ str_contains($current,'admin-comsci') ? 'active' : '' }}" href="{{ url('/admin-comsci') }}">
                    <i class='bx bx-building-house nav-icon'></i>
                    <span>Faculty</span>
                </a>
            </li>
            <li>
                <a class="nav-link {{ str_contains($current,'admin-analytics') ? 'active' : '' }}" href="{{ url('/admin-analytics') }}">
                    <i class='bx bx-bar-chart-alt-2 nav-icon'></i>
                    <span>Analytics</span>
                </a>
            </li>
            <li>
                <x-logout-link
                    guard="admin"
                    label="Sign Out"
                    class="nav-link nav-link-logout logout-btn sidebar-link"
                    icon="bx bx-log-out-circle"
                />
            </li>
        </ul>
</div>
<script>
    (function(){
        const hamburger = document.getElementById('hamburger');
        const sidebar = document.getElementById('sidebar');
        if(hamburger){
            hamburger.addEventListener('click',()=>{
                sidebar.classList.toggle('active');
                hamburger.classList.toggle('active');
            });
        }
    })();
</script>