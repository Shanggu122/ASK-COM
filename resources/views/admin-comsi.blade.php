<!-- filepath: c:\Users\Admin\ASCC-ITv1-studentV1\ASCC-ITv1-student\resources\views\admin-comsi.blade.php -->
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>Computer Science Department (Admin)</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
  <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
  <link rel="stylesheet" href="{{ asset('css/admin-comsci.css') }}">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/pikaday/css/pikaday.css">
    <link rel="stylesheet" href="{{ asset('css/logout-confirm.css') }}">
    <script src="{{ asset('js/logout-confirm.js') }}" defer></script>
</head>
<body>
  @include('components.navbar-admin')

  <div class="main-content">
    <div class="header">
      <h1>Computer Science</h1>
    </div>

    <div class="search-container">
      <input type="text" id="searchInput" placeholder="Search...">
    </div>

    <div class="profile-cards-grid">
                    @foreach($professors as $prof)
                        @php
                            $photoUrl = isset($prof->profile_photo_url)
                                    ? $prof->profile_photo_url
                                    : \App\Support\ProfilePhotoPath::url($prof->profile_picture ?? null);
                        @endphp
                        <div class="profile-card"
                                 data-name="{{ $prof->Name }}"
                                 data-img="{{ $photoUrl }}"
                                 data-prof-id="{{ $prof->Prof_ID }}"
                                 style="width: 300px;">
                            <img src="{{ $photoUrl }}" alt="Profile Picture">
                            <div class="profile-name">{{ $prof->Name }}</div>
                            <button type="button" class="assign-subjects-btn" data-prof-id="{{ $prof->Prof_ID }}">Assign Subjects</button>
                        </div>
                    @endforeach
    </div>
  </div>

  <!-- Add Faculty Member Button -->
  <button id="addFacultyBtn" style="position:fixed; bottom:40px; right:60px; background:#194d36; color:#fff; border:none; border-radius:50%; width:50px; height:50px; font-size:2rem; cursor:pointer; display:flex; align-items:center; justify-content:center;">
      +
  </button>

  <!-- Modal Overlay -->
  <div id="addFacultyModal" style="display:none; position:fixed; top:0; left:0; width:100vw; height:100vh; background:rgba(0,0,0,0.3); z-index:9999; align-items:center; justify-content:center;">
      <form id="addFacultyForm" method="POST" action="{{ route('admin.professor.add') }}" style="background:#fff; padding:2rem; border-radius:12px; min-width:320px; display:flex; flex-direction:column; gap:1rem;">
          @csrf
          <h2>Add Faculty Member</h2>
          <input type="text" name="Prof_ID" placeholder="Faculty ID" required>
          <input type="text" name="Name" placeholder="Full Name" required maxlength="50">
          <input type="email" name="Email" placeholder="Email" required maxlength="100">
          <input type="hidden" name="Dept_ID" value="2">
                    <div style="display:flex; gap:8px; align-items:center;">
                        <input type="text" id="addTempPasswordComsi" name="Password" placeholder="Dummy Password" value="password1" required style="flex:1;">
                        <button type="button" id="btnGenTempPwComsi" style="background:#ccc; color:#222; border:none; border-radius:8px; padding:4px 8px; font-size:12px; line-height:1; white-space:nowrap;">Generate</button>
                    </div>
          <button type="submit" style="background:#194d36; color:#fff; border:none; border-radius:8px; padding:0.5rem 1rem;">Add</button>
          <button type="button" id="closeFacultyModal" style="background:#ccc; color:#222; border:none; border-radius:8px; padding:0.5rem 1rem;">Cancel</button>
      </form>
  </div>

  <!-- Delete Professor Overlay -->
  <div id="deleteOverlay">
      <div class="delete-modal">
          <div class="delete-modal-text">
              Are you sure you want to <span class="delete-red">delete</span> this faculty member from the department?
          </div>
          <div class="delete-modal-sub">
              <span style="font-style: italic;">
                  All associated records and data may be permanently removed.
              </span>
          </div>
          <form id="deleteForm" method="POST" action="">
              @csrf
              @method('DELETE')
              <div class="delete-modal-btns">
                  <button type="submit" class="delete-confirm">Confirm</button>
                  <button type="button" class="delete-cancel">Cancel</button>
              </div>
          </form>
      </div>
  </div>

  <!-- Assign Subjects Modal -->
  <div id="assignSubjectsModal" style="display:none; position:fixed; top:0; left:0; width:100vw; height:100vh; background:rgba(0,0,0,0.3); z-index:9999; align-items:center; justify-content:center;">
      <form id="assignSubjectsForm" method="POST" action="{{ route('admin.professor.assignSubjects') }}" style="background:#fff; padding:2rem; border-radius:12px; min-width:320px; display:flex; flex-direction:column; gap:1rem;">
          @csrf
          <input type="hidden" name="Prof_ID" id="assignProfId">
          <h2>Assign Subjects</h2>
          <div>
              @foreach($subjects as $subject)
                  <label style="display:block;">
                      <input type="checkbox" name="subjects[]" value="{{ $subject->Subject_ID }}">
                      {{ $subject->Subject_Name }}
                  </label>
              @endforeach
          </div>
          <button type="submit" style="background:#194d36; color:#fff; border:none; border-radius:8px; padding:0.5rem 1rem;">Save</button>
          <button type="button" id="closeAssignSubjectsModal" style="background:#ccc; color:#222; border:none; border-radius:8px; padding:0.5rem 1rem;">Cancel</button>
      </form>
  </div>

  <script>
        // Password generator for legacy view
        (function(){
                    function generatePassword(len=12){
                        const upper='ABCDEFGHJKLMNPQRSTUVWXYZ', lower='abcdefghijkmnopqrstuvwxyz', digits='23456789';
                        const all=upper+lower+digits; let out=[upper[Math.floor(Math.random()*upper.length)],lower[Math.floor(Math.random()*lower.length)],digits[Math.floor(Math.random()*digits.length)]];
                        while(out.length<len){ out.push(all[Math.floor(Math.random()*all.length)]); }
                for(let i=out.length-1;i>0;i--){ const j=Math.floor(Math.random()*(i+1)); [out[i],out[j]]=[out[j],out[i]]; }
                return out.join('');
            }
            const btn=document.getElementById('btnGenTempPwComsi');
            const input=document.getElementById('addTempPasswordComsi');
            if(btn&&input){ btn.addEventListener('click', ()=>{ input.value=generatePassword(12); }); }
        })();
    // Simple search filter for cards
    document.getElementById('searchInput').addEventListener('input', function() {
      const filter = this.value.toLowerCase();
      document.querySelectorAll('.profile-card').forEach(function(card) {
        const name = card.getAttribute('data-name').toLowerCase();
        card.style.display = name.includes(filter) ? '' : 'none';
      });
    });

    // Show modal
    document.getElementById('addFacultyBtn').onclick = function() {
        document.getElementById('addFacultyModal').style.display = 'flex';
    };
    // Hide modal
    document.getElementById('closeFacultyModal').onclick = function() {
        document.getElementById('addFacultyModal').style.display = 'none';
    };
    // Hide modal when clicking outside the form
    document.getElementById('addFacultyModal').onclick = function(e) {
        if (e.target === this) this.style.display = 'none';
    };

    // Show delete overlay on card click
    document.querySelectorAll('.profile-card').forEach(function(card) {
        card.onclick = function() {
            var profId = card.getAttribute('data-prof-id');
            var form = document.getElementById('deleteForm');
            form.action = '/admin-comsci/delete-professor/' + profId;
            document.getElementById('deleteOverlay').classList.add('show');
        };
    });
    // Cancel button
    document.querySelector('.delete-cancel').onclick = function() {
        document.getElementById('deleteOverlay').classList.remove('show');
    };
    // Hide overlay when clicking outside modal
    document.getElementById('deleteOverlay').onclick = function(e) {
        if (e.target === this) this.classList.remove('show');
    };

    // Open assign subjects modal
    document.querySelectorAll('.assign-subjects-btn').forEach(function(btn) {
        btn.onclick = function(e) {
            e.stopPropagation();
            var profId = btn.getAttribute('data-prof-id');
            document.getElementById('assignProfId').value = profId;
            document.getElementById('assignSubjectsModal').style.display = 'flex';
        };
    });
    document.getElementById('closeAssignSubjectsModal').onclick = function() {
        document.getElementById('assignSubjectsModal').style.display = 'none';
    };
    document.getElementById('assignSubjectsModal').onclick = function(e) {
        if (e.target === this) this.style.display = 'none';
    };
  </script>
</body>
</html>