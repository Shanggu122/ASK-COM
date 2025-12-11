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
        <ul>
                <li><a class="{{ str_starts_with($current,'admin-dashboard') ? 'active' : '' }}" href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                <li><a class="{{ str_contains($current,'admin-comsci') ? 'active' : '' }}" href="{{ url('/admin-comsci') }}">Computer Science</a></li>
                <li><a class="{{ str_contains($current,'admin-analytics') ? 'active' : '' }}" href="{{ url('/admin-analytics') }}">Analytics</a></li>
                <li style="margin:0;padding:0;">
                        <x-logout-link guard="admin" label="Sign Out" class="logout-btn sidebar-link" style="background:none;border:none;padding:1rem 0 1rem 2rem;width:100%;color:inherit;text-align:left;font-family:inherit;font-size:inherit;cursor:pointer;" />
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