<!-- filepath: resources/views/admin-comsci.blade.php -->
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>Computer Science (Admin)</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
  <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
  <link rel="stylesheet" href="{{ asset('css/admin-navbar.css') }}">
  <link rel="stylesheet" href="{{ asset('css/admin-comsci.css') }}">
  <link rel="stylesheet" href="{{ asset('css/confirm-modal.css') }}">
  <link rel="stylesheet" href="{{ asset('css/logout-confirm.css') }}">
  <script src="{{ asset('js/logout-confirm.js') }}" defer></script>
  <style>
    /* View toggle styles (minimal, scoped) */
    .search-container{ display:flex; align-items:center; gap:12px; }
    .search-container input[type="text"]{ flex:1; }
    .view-toggle{ display:flex; gap:8px; align-items:center; }
    .view-toggle button{ background:transparent; border:1px solid #d1d5db; padding:6px 8px; border-radius:8px; cursor:pointer; color:#0f5132; display:inline-flex; align-items:center; justify-content:center; }
    .view-toggle button.active{ background:#0f5132; color:#fff; border-color:#0f5132; }
    .profile-cards-grid.list-view{ display:flex !important; flex-direction:column; gap:12px; }
    .profile-cards-grid.list-view .profile-card{ display:flex !important; flex-direction:row !important; align-items:center; gap:12px; padding:12px 16px; max-width:100%; }
    .profile-cards-grid.list-view .profile-card img{ width:48px; height:48px; object-fit:cover; border-radius:50%; }
    .profile-cards-grid.list-view .profile-name{ font-size:1rem; font-weight:600; }
  </style>
</head>
<body>
  @include('components.navbar-admin')

  <!-- Global full-screen loading overlay (reuses login styles) -->
  <div id="globalLoading" class="auth-loading-overlay" aria-hidden="true">
    <div class="auth-loading-spinner" role="status" aria-live="polite"></div>
    <div class="auth-loading-text">Please wait…</div>
  </div>

  <div class="main-content">
    <div class="header">
      <h1>Computer Science Faculty</h1>
    </div>

    <div class="search-container">
  <input type="text" id="searchInput" placeholder="Search..." autocomplete="off" spellcheck="false" maxlength="50" pattern="[A-Za-z0-9 ]{0,50}" aria-label="Search professors" oninput="this.value=this.value.replace(/[^A-Za-z0-9 ]/g,'')">
  <div class="view-toggle" role="tablist" aria-label="View Toggle">
    <button type="button" id="btnGridView" title="Grid view" aria-pressed="true" class="active"><i class='bx bx-grid-alt'></i></button>
    <button type="button" id="btnListView" title="List view" aria-pressed="false"><i class='bx bx-list-ul'></i></button>
  </div>
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
             data-sched="{{ $prof->Schedule ? str_replace('\n', '&#10;', $prof->Schedule) : 'No schedule set' }}">
          <img src="{{ $photoUrl }}" alt="Profile Picture">
          <div class="profile-name">{{ $prof->Name }}</div>
        </div>
      @endforeach
    </div>
  </div>

  <button id="addChooserBtn" class="add-fab">+</button>
  <!-- Centered chooser modal for Add options -->
    <div id="addChooserModal" class="mini-modal" aria-hidden="true">
      <div class="mini-modal-card" role="dialog" aria-modal="true" aria-labelledby="chooserTitle">
        <div class="mini-modal-header">
          <div class="mini-modal-title" id="chooserTitle">Add</div>
          <button type="button" class="mini-modal-close" data-close-chooser>&times;</button>
        </div>
        <div class="mini-modal-body">
          <div class="chooser-options">
            <button type="button" class="chooser-option" data-open-add="faculty">Add Faculty</button>
            <button type="button" class="chooser-option" data-open-add="student">Add Student</button>
          </div>
        </div>
        
      </div>
    </div>

  <!-- Subject Manager Modal -->
  <div id="subjectManagerModal" class="mini-modal" aria-hidden="true">
    <div class="mini-modal-card" role="dialog" aria-modal="true" aria-labelledby="subjectManagerTitle">
      <div class="mini-modal-header">
        <div class="mini-modal-title" id="subjectManagerTitle">Manage Subjects</div>
        <button type="button" class="mini-modal-close" data-close-subjects>&times;</button>
      </div>
      <div class="mini-modal-body">
        <form id="subjectManagerForm" autocomplete="off">
          <label class="input-label" for="subjectManagerInput">New Subject</label>
          <div class="subject-manager-input-row">
            <input type="text" id="subjectManagerInput" name="subject_name" placeholder="Enter subject name" maxlength="100" required>
            <button type="submit" class="btn-primary subject-manager-add">Add</button>
          </div>
        </form>
        <div class="subject-manager-list" data-manager-list></div>
      </div>
    </div>
  </div>

  <!-- Panel Overlay -->
  <div class="panel-overlay"></div>

  <!-- Add Faculty Side Panel -->
  <form id="addFacultyPanel" class="add-faculty-panel" method="POST" action="{{ route('admin.comsci.professor.add') }}" autocomplete="off">
    @csrf
    <div class="panel-header">
      <h2>Add Faculty Member</h2>
      <button type="button" id="closeAddFacultyPanel" class="close-panel-btn">&times;</button>
    </div>
    
    <div class="form-content">
      <!-- Left Column -->
      <div class="left-column">
        <div class="input-group">
          <label class="input-label">Faculty ID</label>
          <input type="text" id="addProfIdComsci" name="Prof_ID_display" placeholder="Enter faculty ID" required inputmode="numeric" maxlength="9" pattern="\d{1,9}" oninput="this.value=this.value.replace(/\D/g,'').slice(0,9)" autocomplete="off" autocapitalize="off" autocorrect="off" spellcheck="false" aria-autocomplete="none">
          <input type="hidden" name="Prof_ID" id="hiddenAddProfIdComsci" value="">
        </div>
        <div class="input-group">
          <label class="input-label">Full Name</label>
          <input type="text" name="Name" placeholder="Enter full name" required maxlength="50">
        </div>
        <div class="input-group">
          <label class="input-label">Email Address</label>
          <input type="email" name="Email" placeholder="Enter email address" required maxlength="100">
        </div>
  <input type="hidden" name="Dept_ID" value="2">
        <div class="input-group">
          <label class="input-label">Temporary Password</label>
          <div class="password-row" style="display:flex; gap:8px; align-items:center;">
            <input type="text" id="addTempPasswordComsci" name="Password" value="password1" required style="flex:1;">
            <button type="button" id="btnGenTempPwComsci" class="btn-secondary" style="white-space:nowrap; padding:4px 8px; font-size:12px; line-height:1;">Generate</button>
          </div>
        </div>

        <!-- Subject Assignment -->
        <div class="section-title" style="margin-top: 1rem; display:flex; align-items:center; gap:12px;">Subject Assignment
          <button type="button" class="btn-secondary manage-subjects-btn" data-manage-subjects>Manage Subjects</button>
        </div>
        <div class="subject-list" data-subject-list>
          @foreach($subjects as $subject)
          @php
            $sid = is_object($subject) ? ($subject->Subject_ID ?? '') : (is_array($subject) ? ($subject['Subject_ID'] ?? '') : '');
            $sname = is_object($subject) ? ($subject->Subject_Name ?? '') : (is_array($subject) ? ($subject['Subject_Name'] ?? '') : '');
          @endphp
          <div class="subject-item">
            <input type="checkbox" name="subjects[]" value="{{ $sid }}" id="subject_{{ $sid }}">
            <label for="subject_{{ $sid }}" class="subject-name">{{ $sname }}</label>
          </div>
          @endforeach
        </div>
        <div class="input-group" style="margin-top:10px;">
          <em style="font-size:12px;color:#475569;">Tip: Add the new "General Consultation" subject in this list and assign it to professors who accept general consults. When students pick that subject, consultation type selection is skipped automatically.</em>
        </div>
      </div>

      <!-- Right Column -->
      <div class="right-column">
        <div class="section-title">Schedule Configuration</div>
        <div class="schedule-info">Set up to 3 different time slots for faculty availability</div>
        <div class="schedule-guide">
          <div class="guide-item">• Select day of the week (Monday-Friday)</div>
          <div class="guide-item">• Choose start time and end time for each slot</div>
          <div class="guide-item">• Example: Monday 9:00 AM to 11:00 AM</div>
        </div>
        
        <div class="schedule-rows">
          <div class="schedule-label">Schedule 1 <button type="button" class="schedule-clear-btn" data-scope="add" data-index="1">Remove</button></div>
          <div class="schedule-row">
            <div class="day-selector">
              <label class="field-label">Day</label>
              <select name="day_1" class="schedule-day">
                <option value="">Select day</option>
                <option value="Monday">Monday</option>
                <option value="Tuesday">Tuesday</option>
                <option value="Wednesday">Wednesday</option>
                <option value="Thursday">Thursday</option>
                <option value="Friday">Friday</option>
              </select>
            </div>
            <div class="time-section">
              <div class="time-labels">
                <label class="field-label">Start Time</label>
                <span class="time-label-separator"></span>
                <label class="field-label">End Time</label>
              </div>
              <div class="time-inputs">
                <div class="time-field">
                  <input type="time" name="start_time_1" class="schedule-time">
                </div>
                <span class="time-separator">to</span>
                <div class="time-field">
                  <input type="time" name="end_time_1" class="schedule-time">
                </div>
              </div>
            </div>
          </div>
          
          <div class="schedule-label">Schedule 2 <button type="button" class="schedule-clear-btn" data-scope="add" data-index="2">Remove</button></div>
          <div class="schedule-row">
            <div class="day-selector">
              <label class="field-label">Day</label>
              <select name="day_2" class="schedule-day">
                <option value="">Select day</option>
                <option value="Monday">Monday</option>
                <option value="Tuesday">Tuesday</option>
                <option value="Wednesday">Wednesday</option>
                <option value="Thursday">Thursday</option>
                <option value="Friday">Friday</option>
              </select>
            </div>
            <div class="time-section">
              <div class="time-labels">
                <label class="field-label">Start Time</label>
                <span class="time-label-separator"></span>
                <label class="field-label">End Time</label>
              </div>
              <div class="time-inputs">
                <div class="time-field">
                  <input type="time" name="start_time_2" class="schedule-time">
                </div>
                <span class="time-separator">to</span>
                <div class="time-field">
                  <input type="time" name="end_time_2" class="schedule-time">
                </div>
              </div>
            </div>
          </div>
          
          <div class="schedule-label">Schedule 3 <button type="button" class="schedule-clear-btn" data-scope="add" data-index="3">Remove</button></div>
          <div class="schedule-row">
            <div class="day-selector">
              <label class="field-label">Day</label>
              <select name="day_3" class="schedule-day">
                <option value="">Select day</option>
                <option value="Monday">Monday</option>
                <option value="Tuesday">Tuesday</option>
                <option value="Wednesday">Wednesday</option>
                <option value="Thursday">Thursday</option>
                <option value="Friday">Friday</option>
              </select>
            </div>
            <div class="time-section">
              <div class="time-labels">
                <label class="field-label">Start Time</label>
                <span class="time-label-separator"></span>
                <label class="field-label">End Time</label>
              </div>
              <div class="time-inputs">
                <div class="time-field">
                  <input type="time" name="start_time_3" class="schedule-time">
                </div>
                <span class="time-separator">to</span>
                <div class="time-field">
                  <input type="time" name="end_time_3" class="schedule-time">
                </div>
              </div>
            </div>
          </div>
        </div>
        
        <!-- Panel Actions -->
        <div class="panel-actions">
          <button type="button" class="btn-secondary" onclick="ModalManager.close('addFaculty')">Cancel</button>
          <button type="submit" class="btn-primary">Add Faculty</button>
        </div>
      </div>
    </div>
  </form>

  <!-- Add Student Side Panel (compact) -->
    <form id="addStudentPanel" class="add-student-panel" method="POST" action="{{ route('admin.comsci.student.add') }}">
      @csrf
      <div class="panel-header">
        <h2>Add Student</h2>
        <button type="button" id="closeAddStudentPanel" class="close-panel-btn">&times;</button>
      </div>
      <div class="form-content">
        <div class="left-column">
          <div class="input-group">
            <label class="input-label">Student ID</label>
            <input type="text" name="Stud_ID" placeholder="Enter student ID" required inputmode="numeric" maxlength="9" pattern="\d{1,9}" oninput="this.value=this.value.replace(/\D/g,'').slice(0,9)">
          </div>
          <div class="input-group">
            <label class="input-label">Full Name</label>
            <input type="text" name="Name" placeholder="Enter full name" required maxlength="50">
          </div>
          <div class="input-group">
            <label class="input-label">Email Address</label>
            <input type="email" name="Email" placeholder="Enter email address" required maxlength="100">
          </div>
          <input type="hidden" name="Dept_ID" value="2">
          <div class="input-group">
            <label class="input-label">Temporary Password</label>
            <div class="password-row" style="display:flex; gap:8px; align-items:center;">
              <input type="text" id="addStudentTempPasswordComsci" name="Password" value="password1" required style="flex:1;">
              <button type="button" id="btnGenTempPwStudentComsci" class="btn-secondary" style="white-space:nowrap; padding:4px 8px; font-size:12px; line-height:1;">Generate</button>
            </div>
          </div>
        </div>
        <div class="right-column"></div>
      </div>
      <div class="panel-actions">
        <button type="button" class="btn-secondary" onclick="ModalManager.close('addStudent')">Cancel</button>
        <button type="submit" class="btn-primary">Add Student</button>
      </div>
    </form>

  <!-- Edit Faculty Panel Overlay -->
  <div class="edit-panel-overlay"></div>

  <!-- Edit Faculty Panel -->
  <div id="editFacultyPanel" class="edit-faculty-panel">
  <form id="editFacultyForm" method="POST" action="" autocomplete="off">
      @csrf
      <div class="panel-header">
        <h2>Edit Faculty Member</h2>
        <button type="button" id="closeEditFacultyPanel" class="close-panel-btn">&times;</button>
      </div>
      
      <div class="form-content">
        <!-- Left Column -->
        <div class="left-column">
          <div class="input-group">
            <label class="input-label">Faculty ID</label>
            <input type="text" name="Prof_ID" id="editProfId" placeholder="Enter faculty ID" required inputmode="numeric" maxlength="9" pattern="\d{1,9}" oninput="this.value=this.value.replace(/\D/g,'').slice(0,9)" autocomplete="off" autocapitalize="off" autocorrect="off" spellcheck="false" aria-autocomplete="none">
          </div>
          <div class="input-group">
            <label class="input-label">Full Name</label>
            <input type="text" name="Name" id="editName" placeholder="Enter full name" required maxlength="50">
          </div>

          <!-- Subject Assignment -->
          <div class="section-title" style="margin-top: 1rem; display:flex; align-items:center; gap:12px;">Subject Assignment
            <button type="button" class="btn-secondary manage-subjects-btn" data-manage-subjects>Manage Subjects</button>
          </div>
          <div class="subject-list" id="editSubjectList" data-edit-subject-list>
            @foreach($subjects as $subject)
            @php
              $sid = is_object($subject) ? ($subject->Subject_ID ?? '') : (is_array($subject) ? ($subject['Subject_ID'] ?? '') : '');
              $sname = is_object($subject) ? ($subject->Subject_Name ?? '') : (is_array($subject) ? ($subject['Subject_Name'] ?? '') : '');
            @endphp
            <div class="subject-item">
              <input type="checkbox" name="subjects[]" value="{{ $sid }}" id="edit_subject_{{ $sid }}">
              <label for="edit_subject_{{ $sid }}" class="subject-name">{{ $sname }}</label>
            </div>
            @endforeach
          </div>
        </div>

        <!-- Right Column -->
        <div class="right-column">
          <div class="section-title">Schedule Configuration</div>
          <div class="schedule-info">Set up to 3 different time slots for faculty availability</div>
          <div class="schedule-guide">
            <div class="guide-item">• Select day of the week (Monday-Friday)</div>
            <div class="guide-item">• Choose start time and end time for each slot</div>
            <div class="guide-item">• Example: Monday 9:00 AM to 11:00 AM</div>
          </div>
          
          <div class="schedule-rows">
            <div class="schedule-label">Schedule 1 <button type="button" class="schedule-clear-btn" data-scope="edit" data-index="1">Remove</button></div>
            <div class="schedule-row">
              <div class="day-selector">
                <label class="field-label">Day</label>
                <select name="edit_day_1" class="schedule-day">
                  <option value="">Select day</option>
                  <option value="Monday">Monday</option>
                  <option value="Tuesday">Tuesday</option>
                  <option value="Wednesday">Wednesday</option>
                  <option value="Thursday">Thursday</option>
                  <option value="Friday">Friday</option>
                </select>
              </div>
              <div class="time-section">
                <div class="time-labels">
                  <label class="field-label">Start Time</label>
                  <span class="time-label-separator"></span>
                  <label class="field-label">End Time</label>
                </div>
                <div class="time-inputs">
                  <div class="time-field">
                    <input type="time" name="edit_start_time_1" class="schedule-time">
                  </div>
                  <span class="time-separator">to</span>
                  <div class="time-field">
                    <input type="time" name="edit_end_time_1" class="schedule-time">
                  </div>
                </div>
              </div>
            </div>
            
            <div class="schedule-label">Schedule 2 <button type="button" class="schedule-clear-btn" data-scope="edit" data-index="2">Remove</button></div>
            <div class="schedule-row">
              <div class="day-selector">
                <label class="field-label">Day</label>
                <select name="edit_day_2" class="schedule-day">
                  <option value="">Select day</option>
                  <option value="Monday">Monday</option>
                  <option value="Tuesday">Tuesday</option>
                  <option value="Wednesday">Wednesday</option>
                  <option value="Thursday">Thursday</option>
                  <option value="Friday">Friday</option>
                </select>
              </div>
              <div class="time-section">
                <div class="time-labels">
                  <label class="field-label">Start Time</label>
                  <span class="time-label-separator"></span>
                  <label class="field-label">End Time</label>
                </div>
                <div class="time-inputs">
                  <div class="time-field">
                    <input type="time" name="edit_start_time_2" class="schedule-time">
                  </div>
                  <span class="time-separator">to</span>
                  <div class="time-field">
                    <input type="time" name="edit_end_time_2" class="schedule-time">
                  </div>
                </div>
              </div>
            </div>
            
            <div class="schedule-label">Schedule 3 <button type="button" class="schedule-clear-btn" data-scope="edit" data-index="3">Remove</button></div>
            <div class="schedule-row">
              <div class="day-selector">
                <label class="field-label">Day</label>
                <select name="edit_day_3" class="schedule-day">
                  <option value="">Select day</option>
                  <option value="Monday">Monday</option>
                  <option value="Tuesday">Tuesday</option>
                  <option value="Wednesday">Wednesday</option>
                  <option value="Thursday">Thursday</option>
                  <option value="Friday">Friday</option>
                </select>
              </div>
              <div class="time-section">
                <div class="time-labels">
                  <label class="field-label">Start Time</label>
                  <span class="time-label-separator"></span>
                  <label class="field-label">End Time</label>
                </div>
                <div class="time-inputs">
                  <div class="time-field">
                    <input type="time" name="edit_start_time_3" class="schedule-time">
                  </div>
                  <span class="time-separator">to</span>
                  <div class="time-field">
                    <input type="time" name="edit_end_time_3" class="schedule-time">
                  </div>
                </div>
              </div>
            </div>
          </div>
          
          <!-- Panel Actions -->
          <div class="panel-actions">
            <button type="button" class="btn-secondary" onclick="ModalManager.close('editFaculty')">Cancel</button>
            <button type="button" class="delete-prof-btn-modal btn-danger" style="margin-right: auto;">Delete</button>
            <button type="submit" class="btn-primary">Update Faculty</button>
          </div>
        </div>
      </div>
    </form>
  </div>

  <!-- Delete Professor Overlay -->
  <div id="deleteOverlay">
    <div class="delete-modal">
      <div class="delete-modal-text">
        Are you sure you want to <span class="delete-red">delete</span> this faculty member from the department?
      </div>
      <div class="delete-modal-sub">
        <span class="italic">
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

  </div>

  <div id="notification" class="notification" style="display:none;">
    <span id="notification-message"></span>
    <button onclick="hideNotification()" class="close-btn" type="button">&times;</button>
  </div>

  <div data-subject-manager
    data-index-url="{{ route('admin.subjects.index') }}"
    data-store-url="{{ route('admin.subjects.store') }}"
    data-destroy-base="{{ url('/admin/subjects') }}"
    style="display:none;"></div>

  <script>
    // Keep the most recently saved schedule per professor to reflect changes instantly on reopen
    const lastUpdatedSchedule = Object.create(null);
    // Generate a random, readable password (12 chars)
    function generatePassword(len=12){
      const upper = 'ABCDEFGHJKLMNPQRSTUVWXYZ';
      const lower = 'abcdefghijkmnopqrstuvwxyz';
      const digits = '23456789';
      const all = upper + lower + digits;
      let out = [upper[Math.floor(Math.random()*upper.length)], lower[Math.floor(Math.random()*lower.length)], digits[Math.floor(Math.random()*digits.length)]];
      while(out.length < len){ out.push(all[Math.floor(Math.random()*all.length)]); }
      for(let i=out.length-1;i>0;i--){ const j=Math.floor(Math.random()*(i+1)); [out[i],out[j]]=[out[j],out[i]]; }
      return out.join('');
    }
    (function bindGenPwComsci(){
      const btn = document.getElementById('btnGenTempPwComsci');
      const input = document.getElementById('addTempPasswordComsci');
      if(!btn||!input) return;
      btn.addEventListener('click', ()=>{ input.value = generatePassword(12); input.dispatchEvent(new Event('input',{bubbles:true})); });
    })();
    function showNotification(message, isError = false) {
      const notif = document.getElementById('notification');
      if(!notif) return;
      notif.classList.toggle('error', !!isError);
      document.getElementById('notification-message').textContent = message;
      notif.style.display = 'flex';
      clearTimeout(window.__notifTimer);
      window.__notifTimer = setTimeout(hideNotification, 4000);
    }
    function hideNotification(){
      const notif = document.getElementById('notification');
      if(notif) notif.style.display='none';
    }
    // --- Anti-spam helpers (prevent rapid double clicks/submits) ---
    function guardRapidClicks(selector, holdMs = 1000){
      document.addEventListener('click', function(e){
        const btn = e.target && e.target.closest ? e.target.closest(selector) : null;
        if(!btn) return;
        if(btn.dataset.clickLocked === '1'){
          e.preventDefault();
          e.stopPropagation();
          return;
        }
        btn.dataset.clickLocked = '1';
        if(typeof btn.disabled !== 'undefined') btn.disabled = true;
        setTimeout(()=>{ if(btn){ btn.dataset.clickLocked='0'; if(typeof btn.disabled !== 'undefined') btn.disabled = false; } }, holdMs);
      }, true);
    }
    function lockSubmitButton(formEl){
      const submitBtn = formEl.querySelector('.panel-actions .btn-primary[type="submit"], .panel-actions .btn-primary');
      if(submitBtn){
        submitBtn.disabled = true;
        submitBtn.setAttribute('aria-busy','true');
      }
      return submitBtn;
    }
    function unlockSubmitButton(btn){
      if(!btn) return;
      btn.disabled = false;
      btn.removeAttribute('aria-busy');
    }
    // Modal/Panel Management System
    const ModalManager = {
      activeModal: null,
      
      // Register all modals/panels
      modals: {
        addFaculty: {
          element: null,
          overlay: null,
          triggers: [],
          closers: ['closeAddFacultyPanel']
        },
        addStudent: {
          element: null,
          overlay: null,
          triggers: [],
          closers: []
        },
        editFaculty: {
          element: null,
          overlay: null,
          triggers: [],
          closers: ['closeEditFacultyPanel']
        },
        deleteConfirm: {
          element: null,
          overlay: null,
          triggers: [],
          closers: ['delete-cancel']
        }
      },
      
  init() {
        // Initialize modal elements
        this.modals.addFaculty.element = document.getElementById('addFacultyPanel');
        this.modals.addFaculty.overlay = document.querySelector('.panel-overlay');
  this.modals.editFaculty.element = document.getElementById('editFacultyPanel');
        this.modals.editFaculty.overlay = document.querySelector('.edit-panel-overlay');
        this.modals.deleteConfirm.element = document.getElementById('deleteOverlay');
    // Add Student is now a side panel, reuse panel overlay
    this.modals.addStudent.element = document.getElementById('addStudentPanel');
    this.modals.addStudent.overlay = document.querySelector('.panel-overlay');
    this.modals.addStudent.closers = ['closeAddStudentPanel'];
        
        // Bind events
        this.bindEvents();
        
        // Handle escape key
        document.addEventListener('keydown', (e) => {
          if (e.key === 'Escape') {
            this.closeAll();
          }
        });
      },
      
      bindEvents() {
        // Bind trigger buttons
        Object.keys(this.modals).forEach(modalKey => {
          const modal = this.modals[modalKey];
          
          // Bind trigger buttons
          modal.triggers.forEach(triggerId => {
            const trigger = document.getElementById(triggerId);
            if (trigger) {
              trigger.addEventListener('click', () => this.open(modalKey));
            }
          });
          
          // Bind close buttons
          modal.closers.forEach(closerId => {
            const closer = document.getElementById(closerId) || document.querySelector(`.${closerId}`);
            if (closer) {
              closer.addEventListener('click', () => this.close(modalKey));
            }
          });
        });
        
        // Bind overlay clicks
        if (this.modals.addFaculty.overlay) {
          this.modals.addFaculty.overlay.addEventListener('click', () => {
            this.close('addFaculty');
            this.close('addStudent');
          });
        }
        
        if (this.modals.editFaculty.overlay) {
          this.modals.editFaculty.overlay.addEventListener('click', () => this.close('editFaculty'));
        }

        // Student panel closers are auto-wired via closers
        
        // Bind other modal overlays
        const deleteModal = this.modals.deleteConfirm.element;
        if (deleteModal) {
          deleteModal.addEventListener('click', (e) => {
            if (e.target === deleteModal) this.close('deleteConfirm');
          });
        }
      },
      
      open(modalKey) {
        // Close any currently open modal first (except the one we're opening)
        Object.keys(this.modals).forEach(key => {
          if (key !== modalKey) {
            this.close(key);
          }
        });
        
        const modal = this.modals[modalKey];
        if (!modal || !modal.element) return;
        
        this.activeModal = modalKey;
        
        // Show the modal
        if (modalKey === 'addFaculty') {
          modal.overlay.classList.add('show');
          modal.element.classList.add('show');
          document.body.style.overflow = 'hidden';
          
          // Auto-focus first input
          setTimeout(() => {
            const firstInput = modal.element.querySelector('input');
            if (firstInput) firstInput.focus();
          }, 300);
        } else if (modalKey === 'editFaculty') {
          modal.overlay.classList.add('show');
          modal.element.classList.add('show');
          document.body.style.overflow = 'hidden';
          
          // Auto-focus first input
          setTimeout(() => {
            const firstInput = modal.element.querySelector('input');
            if (firstInput) firstInput.focus();
          }, 300);
        } else if (modalKey === 'addStudent') {
          if(modal.overlay) modal.overlay.classList.add('show');
          modal.element.classList.add('show');
          document.body.style.overflow = 'hidden';
          setTimeout(()=>{ const first = modal.element.querySelector('input'); if(first) first.focus(); }, 100);
        } else {
          modal.element.classList.add('show');
        }
      },
      
      close(modalKey) {
        const modal = this.modals[modalKey];
        if (!modal || !modal.element) return;
        
        if (modalKey === 'addFaculty') {
          modal.overlay.classList.remove('show');
          modal.element.classList.remove('show');
          document.body.style.overflow = 'auto';
          
          // Reset form
          const form = document.getElementById('addFacultyPanel');
          if (form) form.reset();
        } else if (modalKey === 'editFaculty') {
          modal.overlay.classList.remove('show');
          modal.element.classList.remove('show');
          document.body.style.overflow = 'auto';
          
          // Reset form
          const form = document.getElementById('editFacultyForm');
          if (form) form.reset();
        } else if (modalKey === 'addStudent') {
          if(modal.overlay) modal.overlay.classList.remove('show');
          modal.element.classList.remove('show');
          document.body.style.overflow = 'auto';
          const form = document.getElementById('addStudentPanel');
          if(form) form.reset();
        } else {
          modal.element.classList.remove('show');
        }
        
        if (this.activeModal === modalKey) {
          this.activeModal = null;
        }
      },
      
      closeAll() {
        Object.keys(this.modals).forEach(modalKey => {
          this.close(modalKey);
        });
      }
    };

    // Simple search filter for cards with basic sanitization
    function sanitize(input){
      if(!input) return '';
      // Keep letters, numbers, and spaces only (preserve spaces, just collapse runs)
      let cleaned = input.replace(/[^A-Za-z0-9 ]/g, '');
      cleaned = cleaned.replace(/\s{2,}/g,' ');
      return cleaned.slice(0,50);
    }
    document.getElementById('searchInput').addEventListener('input', function() {
      const raw = this.value;
      const cleaned = sanitize(raw);
      if(cleaned !== raw) this.value = cleaned; // keep spaces visible while typing
      const filter = cleaned.toLowerCase().trim(); // trim only for matching logic
      document.querySelectorAll('.profile-card').forEach(function(card) {
        const name = card.getAttribute('data-name').toLowerCase();
        card.style.display = name.includes(filter) ? '' : 'none';
      });
    });

    // Schedule Management
    let scheduleCount = 1;

    function addScheduleRow() {
        scheduleCount++;
        const scheduleRows = document.querySelector('.schedule-rows');
        const newRow = document.createElement('div');
        newRow.className = 'schedule-row';
        newRow.innerHTML = `
            <div class="day-selector">
                <label class="field-label">Day</label>
                <select class="schedule-day" name="schedule_day[]" required>
                    <option value="">Select Day</option>
                    <option value="Monday">Monday</option>
                    <option value="Tuesday">Tuesday</option>
                    <option value="Wednesday">Wednesday</option>
                    <option value="Thursday">Thursday</option>
                    <option value="Friday">Friday</option>
                    <option value="Saturday">Saturday</option>
                    <option value="Sunday">Sunday</option>
                </select>
            </div>
            <div class="time-inputs">
                <div class="time-field">
                    <label class="field-label">Start Time</label>
                    <input type="time" class="schedule-time" name="schedule_start[]" required>
                </div>
                <span class="time-separator">to</span>
                <div class="time-field">
                    <label class="field-label">End Time</label>
                    <input type="time" class="schedule-time" name="schedule_end[]" required>
                </div>
            </div>
        `;
        scheduleRows.appendChild(newRow);
    }

    // Clear a specific schedule slot (add/edit scope)
    function clearScheduleSlot(scope, idx){
      try{
        const prefix = scope === 'edit' ? 'edit_' : '';
        const daySel = document.querySelector(`select[name="${prefix}day_${idx}"]`);
        const startInp = document.querySelector(`input[name="${prefix}start_time_${idx}"]`);
        const endInp = document.querySelector(`input[name="${prefix}end_time_${idx}"]`);
        if(daySel){
          daySel.value = '';
          // Dispatch events so dirty tracking picks up the change
          daySel.dispatchEvent(new Event('input', { bubbles: true }));
          daySel.dispatchEvent(new Event('change', { bubbles: true }));
        }
        if(startInp){
          startInp.value = '';
          startInp.dispatchEvent(new Event('input', { bubbles: true }));
          startInp.dispatchEvent(new Event('change', { bubbles: true }));
        }
        if(endInp){
          endInp.value = '';
          endInp.dispatchEvent(new Event('input', { bubbles: true }));
          endInp.dispatchEvent(new Event('change', { bubbles: true }));
        }
        const label = document.querySelector(`.schedule-label button.schedule-clear-btn[data-scope="${scope}"][data-index="${idx}"]`);
        const row = label ? label.closest('.schedule-label')?.nextElementSibling : null;
        if(row && row.classList){ row.classList.add('cleared'); setTimeout(()=>row.classList.remove('cleared'), 800); }
        try{ showNotification(`Schedule ${idx} cleared${scope==='edit'?' (Edit)':''}`); }catch(_){ }
        // Ensure dirty state reflects the removal immediately
        try{ if(typeof refreshEditDirty === 'function') refreshEditDirty(); }catch(_){ }
      }catch(_){ }
    }

    // Attach handlers to Remove buttons
    document.addEventListener('click', function(e){
      const btn = e.target.closest && e.target.closest('.schedule-clear-btn');
      if(!btn) return;
      const scope = btn.getAttribute('data-scope');
      const idx = btn.getAttribute('data-index');
      clearScheduleSlot(scope, idx);
    });

    // --- Edit Modal Logic ---
    let currentProfId = null;
    let currentProfData = null;
    
    document.querySelectorAll('.profile-card').forEach(function(card) {
      card.onclick = function(e) {
        if (e.target.tagName === 'BUTTON') return;
        const name = card.getAttribute('data-name');
        const profId = card.getAttribute('data-prof-id');
        const sched = card.getAttribute('data-sched');
        currentProfId = profId;

        // Populate the edit form first
        populateEditForm(profId, name, sched);
        
        // Then open modal
        ModalManager.open('editFaculty');
      };
    });

    function populateEditForm(profId, name, schedule) {
      // Prefer the latest saved schedule if present
      if(Object.prototype.hasOwnProperty.call(lastUpdatedSchedule, String(profId))){
        schedule = lastUpdatedSchedule[String(profId)];
      }
      // Set the form action URL
      document.getElementById('editFacultyForm').action = `/admin-comsci/update-professor/${profId}`;
      
      // Populate Faculty ID field
      document.getElementById('editProfId').value = profId;
      
      // Populate name field
      console.log('Populating name field with:', name); // Debug log
      document.getElementById('editName').value = name;
      
      // Clear all schedule fields first
      clearScheduleFields();
      
      // Parse and populate schedule if available
      console.log('Raw schedule data received:', schedule); // Debug log
      if (schedule && schedule !== 'No schedule set' && schedule.trim() !== '') {
        // Handle both encoded newlines and actual newlines
        const formattedSchedule = schedule.replace(/&#10;/g, '\n').replace(/<br>/g, '\n');
        const scheduleLines = formattedSchedule.split('\n').filter(line => line.trim() !== '');
        console.log('Processed schedule lines:', scheduleLines); // Debug log
        
        scheduleLines.forEach((line, index) => {
          if (line.trim() && index < 3) {
            console.log(`Processing schedule line ${index + 1}:`, line); // Debug log
            
            // Parse format: "Day: StartTime-EndTime"
            const colonIndex = line.indexOf(':');
            if (colonIndex !== -1) {
              const day = line.substring(0, colonIndex).trim();
              const timeRange = line.substring(colonIndex + 1).trim();
              const times = timeRange.split('-');
              
              console.log(`Parsed - Day: "${day}", Time range: "${timeRange}", Times:`, times); // Debug log
              
              if (times.length === 2) {
                const startTime = convertTo24Hour(times[0].trim());
                const endTime = convertTo24Hour(times[1].trim());
                
                console.log(`Converted times - Start: ${startTime}, End: ${endTime}`); // Debug log
                
                // Populate the schedule fields
                const scheduleNum = index + 1;
                const daySelect = document.querySelector(`select[name="edit_day_${scheduleNum}"]`);
                const startInput = document.querySelector(`input[name="edit_start_time_${scheduleNum}"]`);
                const endInput = document.querySelector(`input[name="edit_end_time_${scheduleNum}"]`);
                
                if (daySelect && startInput && endInput) {
                  daySelect.value = day;
                  startInput.value = startTime;
                  endInput.value = endTime;
                  console.log(`✓ Set schedule ${scheduleNum}: ${day} ${startTime}-${endTime}`); // Debug log
                } else {
                  console.error(`✗ Could not find schedule fields for schedule ${scheduleNum}`); // Debug log
                }
              } else {
                console.error('Invalid time format:', timeRange);
              }
            } else {
              console.error('Invalid schedule line format:', line);
            }
          }
        });
      } else {
        console.log('No schedule data to populate - schedule value:', schedule); // Debug log
      }
      
      // Load and populate subject assignments, then snapshot initial state
      loadProfessorSubjects(profId).then(()=>{
        setEditInitialSnapshot();
      });
    }

    function clearScheduleFields() {
      for (let i = 1; i <= 3; i++) {
        document.querySelector(`select[name="edit_day_${i}"]`).value = '';
        document.querySelector(`input[name="edit_start_time_${i}"]`).value = '';
        document.querySelector(`input[name="edit_end_time_${i}"]`).value = '';
      }
      
      // Clear all subject checkboxes
      document.querySelectorAll('#editSubjectList input[type="checkbox"]').forEach(checkbox => {
        checkbox.checked = false;
      });
    }

    function convertTo24Hour(time12h) {
      try {
        const [time, modifier] = time12h.split(' ');
        let [hours, minutes] = time.split(':');
        
        hours = parseInt(hours, 10);
        minutes = minutes || '00';
        
        if (modifier === 'AM') {
          if (hours === 12) {
            hours = 0;
          }
        } else if (modifier === 'PM') {
          if (hours !== 12) {
            hours = hours + 12;
          }
        }
        
        return `${hours.toString().padStart(2, '0')}:${minutes}`;
      } catch (error) {
        console.error('Error converting time:', time12h, error);
        return '00:00';
      }
    }

    function loadProfessorSubjects(profId) {
      // Fetch professor's current subject assignments
      return fetch(`/admin-comsci/professor-subjects/${profId}`, {
        method: 'GET',
        headers: {
          'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        }
      })
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          // Check the appropriate subject checkboxes
          data.subjects.forEach(subjectId => {
            const checkbox = document.querySelector(`#edit_subject_${subjectId}`);
            if (checkbox) {
              checkbox.checked = true;
            }
          });
        }
      })
      .catch(error => {
        console.log('Could not load professor subjects:', error);
      });
    }

    // ---- Dirty tracking for Edit form ----
    function getEditFormSnapshot(){
      const form = document.getElementById('editFacultyForm');
      if(!form) return '';
      const snap = {
        Name: form.querySelector('#editName')?.value || '',
        sched: {
          d1: form.querySelector('select[name="edit_day_1"]')?.value || '',
          s1: form.querySelector('input[name="edit_start_time_1"]')?.value || '',
          e1: form.querySelector('input[name="edit_end_time_1"]')?.value || '',
          d2: form.querySelector('select[name="edit_day_2"]')?.value || '',
          s2: form.querySelector('input[name="edit_start_time_2"]')?.value || '',
          e2: form.querySelector('input[name="edit_end_time_2"]')?.value || '',
          d3: form.querySelector('select[name="edit_day_3"]')?.value || '',
          s3: form.querySelector('input[name="edit_start_time_3"]')?.value || '',
          e3: form.querySelector('input[name="edit_end_time_3"]')?.value || '',
        },
        subjects: Array.from(document.querySelectorAll('#editSubjectList input[type="checkbox"]:checked')).map(cb=>cb.value).sort()
      };
      try{ return JSON.stringify(snap); }catch(_){ return '' }
    }
    function toggleEditSubmitEnabled(enabled){
      const form = document.getElementById('editFacultyForm');
      const btn = form?.querySelector('.panel-actions .btn-primary');
      if(btn){ btn.disabled = !enabled; }
    }
    function setEditInitialSnapshot(){
      const form = document.getElementById('editFacultyForm');
      if(!form) return;
      form.dataset.initialSnapshot = getEditFormSnapshot();
      form.dataset.dirty = '0';
      toggleEditSubmitEnabled(false);
    }
    function refreshEditDirty(){
      const form = document.getElementById('editFacultyForm');
      if(!form) return;
      const cur = getEditFormSnapshot();
      const dirty = (cur !== (form.dataset.initialSnapshot||''));
      form.dataset.dirty = dirty ? '1' : '0';
      toggleEditSubmitEnabled(dirty);
    }
    // Bind change listeners once
    (function bindEditDirtyListeners(){
      const form = document.getElementById('editFacultyForm');
      if(!form) return;
      form.addEventListener('input', refreshEditDirty, true);
      form.addEventListener('change', refreshEditDirty, true);
    })();

    // --- Delete from Edit Modal ---
    document.querySelector('.delete-prof-btn-modal').onclick = function() {
      ModalManager.close('editFaculty');
      showDeleteModal(currentProfId);
    };
    // --- Delete Modal Logic ---
    function showDeleteModal(profId) {
      const form = document.getElementById('deleteForm');
      if(form){
        form.action = '/admin-comsci/delete-professor/' + profId;
      }
      ModalManager.open('deleteConfirm');
      try{ if(typeof refreshEditDirty === 'function') refreshEditDirty(); }catch(_){ }
    }

  // Removed legacy addFacultyPanel submit listener (handled by enhanceAddProfessorForm)

    // Handle edit form submission
    document.getElementById('editFacultyForm').addEventListener('submit', function(e) {
      e.preventDefault();
      if(this.dataset.dirty !== '1'){
        showNotification('No changes to update', true);
        return;
      }
      
      // Ensure hidden Prof_ID mirrors the visible field before validating
      try{
        const disp = this.querySelector('#editProfId');
        const hid  = this.querySelector('input[name="Prof_ID"][type="hidden"]');
        if(disp && hid){ hid.value = (disp.value||'').replace(/\D/g,'').slice(0,9); }
      }catch(_){ }

      const formData = new FormData(this);
      
      // Validate required fields
      const requiredFields = ['Prof_ID', 'Name'];
      let isValid = true;
      
      requiredFields.forEach(field => {
        const inputs = Array.from(this.querySelectorAll(`[name="${field}"]`));
        const anyFilled = inputs.some(i => (i && (i.value||'').trim() !== ''));
        if(!anyFilled){
          isValid = false;
          const vis = inputs.find(i => i.offsetParent !== null) || inputs[0];
          vis?.focus();
          return;
        }
      });
      
      if (!isValid) {
        // Use themed notification instead of default alert
        showNotification('Please fill in all required fields', true);
        return;
      }
      
      // Process schedule data
      const day1 = formData.get('edit_day_1');
      const startTime1 = formData.get('edit_start_time_1');
      const endTime1 = formData.get('edit_end_time_1');
      
      const day2 = formData.get('edit_day_2');
      const startTime2 = formData.get('edit_start_time_2');
      const endTime2 = formData.get('edit_end_time_2');
      
      const day3 = formData.get('edit_day_3');
      const startTime3 = formData.get('edit_start_time_3');
      const endTime3 = formData.get('edit_end_time_3');
      
      let scheduleData = [];
      const schedules = [
        {day: day1, start: startTime1, end: endTime1},
        {day: day2, start: startTime2, end: endTime2},
        {day: day3, start: startTime3, end: endTime3}
      ];
      
      for (let schedule of schedules) {
        if (schedule.day && schedule.start && schedule.end) {
          // Check if end time is after start time
          const startMinutes = parseInt(schedule.start.split(':')[0]) * 60 + parseInt(schedule.start.split(':')[1]);
          const endMinutes = parseInt(schedule.end.split(':')[0]) * 60 + parseInt(schedule.end.split(':')[1]);
          
          if (endMinutes <= startMinutes) {
            // Themed error notification
            showNotification(`End time must be after start time for ${schedule.day}`, true);
            return;
          }
          
          // Convert to 12-hour format for display
          const formatTime = (time) => {
            const [hour, minute] = time.split(':');
            const hourNum = parseInt(hour);
            const ampm = hourNum >= 12 ? 'PM' : 'AM';
            const displayHour = hourNum > 12 ? hourNum - 12 : (hourNum === 0 ? 12 : hourNum);
            return `${displayHour}:${minute} ${ampm}`;
          };
          
          scheduleData.push(`${schedule.day}: ${formatTime(schedule.start)}-${formatTime(schedule.end)}`);
        }
      }
      
      // Always include exactly one hidden Schedule input (reuse if exists)
      let scheduleInput = this.querySelector('input[name="Schedule"]');
      if(!scheduleInput){
        scheduleInput = document.createElement('input');
        scheduleInput.type = 'hidden';
        scheduleInput.name = 'Schedule';
        this.appendChild(scheduleInput);
      }
      scheduleInput.value = scheduleData.length > 0 ? scheduleData.join('\n') : '';
      // Debug log
      console.log('Schedule being sent:', scheduleInput.value);
      
  // Anti-spam: prevent duplicate submissions while request is in-flight
  if(this.dataset.submitting === '1') return;
  // 5-second cooldown per click
  if(this.dataset.cooldown === '1') return;
  this.dataset.cooldown = '1';
  setTimeout(()=>{ this.dataset.cooldown = '0'; }, 5000);
  this.dataset.submitting = '1';
  const submitBtnRef = lockSubmitButton(this);

      // Submit the form via fetch to handle the response
      fetch(this.action, {
        method: 'POST',
        body: new FormData(this),
        headers: {
          'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        }
      })
      .then(response => {
        console.log('Response status:', response.status); // Debug log
        return response.json();
      })
      .then(data => {
        console.log('Response data:', data); // Debug log
        if (data.success) {
          // Themed success notification
          showNotification('Professor updated successfully');
          // Update card locally (real-time event also handles others)
          const profId = document.getElementById('editProfId').value;
          const card = document.querySelector(`[data-prof-id="${profId}"]`);
          if(card){
            const newName = document.getElementById('editName').value;
            card.dataset.name = newName;
            if(card.querySelector('.profile-name')) card.querySelector('.profile-name').textContent = newName;
            // Schedule updated via event; keep local dataset for instant UX
            const newSched = scheduleInput ? scheduleInput.value : '';
            const schedString = newSched && newSched.trim() !== '' ? newSched : 'No schedule set';
            // Remember latest for immediate reopen
            lastUpdatedSchedule[String(profId)] = schedString === 'No schedule set' ? '' : schedString;
            // Update both attribute and dataset for safety
            card.setAttribute('data-sched', schedString);
            card.dataset.sched = schedString;
          }
          // Reset snapshot to the new clean state so button disables appropriately on reopen
          try{ setEditInitialSnapshot(); }catch(_){ }
          ModalManager.close('editFaculty');
        } else {
          showNotification('Error updating professor: ' + (data.message || 'Unknown error'), true);
        }
      })
      .catch(error => {
        console.error('Error:', error);
        showNotification('Error updating professor', true);
      })
      .finally(()=>{
        this.dataset.submitting = '0';
        unlockSubmitButton(submitBtnRef);
      });
    });

    // Initialize Modal Manager when page loads
    document.addEventListener('DOMContentLoaded', function() {
      ModalManager.init();
      initRealtimeAdminComsci();
      enhanceAddProfessorForm();
      setupAddChooserModal();
      enhanceAddStudentPanel();
      // Defensive: ensure no overlay is accidentally left open blocking clicks
      try {
        const gl = document.getElementById('globalLoading');
        if (gl) { gl.classList.remove('active'); gl.setAttribute('aria-hidden','true'); }
        document.body.style.overflow = 'auto';
        document.querySelectorAll('.panel-overlay.show, .edit-panel-overlay.show, #addChooserModal.show, #deleteOverlay.show').forEach(el=>{
          el.classList.remove('show');
          if(el.setAttribute) el.setAttribute('aria-hidden','true');
        });
        // Clear any lingering confirm overlays
        document.querySelectorAll('.confirm-overlay.active').forEach(el=>{
          el.classList.remove('active');
          setTimeout(()=>{ try{ el.remove(); }catch(_){ } }, 120);
        });
        // Safety: if an overlay is shown without its panel, remove it
        const addPanelShown = !!document.querySelector('.add-faculty-panel.show, .add-student-panel.show');
        const editPanelShown = !!document.querySelector('.edit-faculty-panel.show');
        const panelOverlay = document.querySelector('.panel-overlay');
        const editOverlay = document.querySelector('.edit-panel-overlay');
        if(panelOverlay && panelOverlay.classList.contains('show') && !addPanelShown){ panelOverlay.classList.remove('show'); panelOverlay.setAttribute('aria-hidden','true'); }
        if(editOverlay && editOverlay.classList.contains('show') && !editPanelShown){ editOverlay.classList.remove('show'); editOverlay.setAttribute('aria-hidden','true'); }
      } catch(_) { /* ignore */ }
      // Global Esc failsafe: close any open overlays/modals if stuck
      document.addEventListener('keydown', function(e){
        if(e.key !== 'Escape') return;
        try{
          ['#addChooserModal','#deleteOverlay'].forEach(sel=>{ const el=document.querySelector(sel); if(el&&el.classList.contains('show')){ el.classList.remove('show'); el.setAttribute('aria-hidden','true'); }});
          document.querySelectorAll('.panel-overlay.show, .edit-panel-overlay.show').forEach(el=>{ el.classList.remove('show'); el.setAttribute('aria-hidden','true'); });
          document.querySelectorAll('.confirm-overlay.active').forEach(el=>{ el.classList.remove('active'); setTimeout(()=>{ try{ el.remove(); }catch(_){ } }, 120); });
          document.body.style.overflow='auto';
        }catch(_){ }
      }, true);
      // Prevent browser history dropdown on Faculty ID and keep hidden field in sync (COMSCI)
      try{
        const addDisp = document.getElementById('addProfIdComsci');
        const addHid  = document.getElementById('hiddenAddProfIdComsci');
        if(addDisp && addHid){
          addDisp.setAttribute('autocomplete','one-time-code');
          addDisp.addEventListener('focus', ()=>{ addDisp.readOnly = true; setTimeout(()=>{ addDisp.readOnly = false; }, 80); });
          addDisp.addEventListener('input', ()=>{ addHid.value = (addDisp.value||'').replace(/\D/g,'').slice(0,9); });
        }
        const editDisp = document.getElementById('editProfId');
        if(editDisp){ editDisp.setAttribute('autocomplete','one-time-code'); editDisp.addEventListener('focus', ()=>{ editDisp.readOnly = true; setTimeout(()=>{ editDisp.readOnly = false; }, 80); }); }
      }catch(_){ }

      // View toggle: grid / list
      try{
        const gridBtn = document.getElementById('btnGridView');
        const listBtn = document.getElementById('btnListView');
        const container = document.querySelector('.profile-cards-grid');
        function applyView(v){
          if(!container) return;
          if(v === 'list'){
            container.classList.add('list-view');
            if(gridBtn) { gridBtn.classList.remove('active'); gridBtn.setAttribute('aria-pressed','false'); }
            if(listBtn) { listBtn.classList.add('active'); listBtn.setAttribute('aria-pressed','true'); }
          } else {
            container.classList.remove('list-view');
            if(gridBtn) { gridBtn.classList.add('active'); gridBtn.setAttribute('aria-pressed','true'); }
            if(listBtn) { listBtn.classList.remove('active'); listBtn.setAttribute('aria-pressed','false'); }
          }
          try{ localStorage.setItem('comsci_view', v); }catch(_){ }
        }
        if(gridBtn) gridBtn.addEventListener('click', ()=> applyView('grid'));
        if(listBtn) listBtn.addEventListener('click', ()=> applyView('list'));
        const saved = (localStorage.getItem('comsci_view') || 'grid');
        applyView(saved);
      }catch(_){ }
    });

    // Use fetch for add professor to prevent full reload (server event will update others)
    function enhanceAddProfessorForm(){
      const form = document.getElementById('addFacultyPanel');
      if(!form) return;
      form.addEventListener('submit', function(ev){
        // If schedule hidden input already appended by earlier handler, allow fetch else will be appended there.
        if(ev.defaultPrevented) return; // already custom-handled
      });
      // Intercept native submit (after schedule injection) using capturing
      form.addEventListener('submit', function(e){
        if(form.dataset.ajaxDone) return; // avoid double
        e.preventDefault();
        // Ensure hidden Prof_ID is synced from display before sending
        try{
          const disp = document.getElementById('addProfIdComsci');
          const hid = document.getElementById('hiddenAddProfIdComsci');
          if(disp && hid){ hid.value = (disp.value||'').replace(/\D/g,'').slice(0,9); }
        }catch(_){ }
        // Build Schedule from day/time inputs (Schedule 1-3) before sending
        try {
          const day1 = form.querySelector('[name="day_1"]').value;
          const st1 = form.querySelector('[name="start_time_1"]').value;
          const en1 = form.querySelector('[name="end_time_1"]').value;
          const day2 = form.querySelector('[name="day_2"]').value;
          const st2 = form.querySelector('[name="start_time_2"]').value;
          const en2 = form.querySelector('[name="end_time_2"]').value;
          const day3 = form.querySelector('[name="day_3"]').value;
          const st3 = form.querySelector('[name="start_time_3"]').value;
          const en3 = form.querySelector('[name="end_time_3"]').value;

          const rows = [
            {day: day1, start: st1, end: en1},
            {day: day2, start: st2, end: en2},
            {day: day3, start: st3, end: en3}
          ];
          const toMinutes = (t)=>{ if(!t) return 0; const [h,m] = t.split(':'); return parseInt(h||'0')*60 + parseInt(m||'0'); };
          const fmt12 = (t)=>{ const [h,m] = (t||'0:00').split(':'); const hn = parseInt(h||'0'); const ampm = hn>=12? 'PM':'AM'; const dh = hn>12? hn-12 : (hn===0?12:hn); return `${dh}:${m} ${ampm}`; };
          const out = [];
          for(const r of rows){
            if(r.day && r.start && r.end){
              if(toMinutes(r.end) <= toMinutes(r.start)){
                showNotification(`End time must be after start time for ${r.day}`, true);
                return; // abort submit
              }
              out.push(`${r.day}: ${fmt12(r.start)}-${fmt12(r.end)}`);
            }
          }
          let schedInput = form.querySelector('input[name="Schedule"][type="hidden"]');
          if(!schedInput){ schedInput = document.createElement('input'); schedInput.type = 'hidden'; schedInput.name = 'Schedule'; form.appendChild(schedInput); }
          schedInput.value = out.length ? out.join('\n') : '';
        } catch(_) { /* ignore build errors; server will validate */ }
        const fd = new FormData(form);
        const overlay = document.getElementById('globalLoading');
        if(overlay){ overlay.classList.add('active'); overlay.setAttribute('aria-hidden','false'); }
        // Allow the overlay to paint before starting the request so the spinner animates
        requestAnimationFrame(() => {
        fetch(form.action, {method:'POST', body: fd, headers:{
            'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]').content,
            'Accept':'application/json',
            'X-Requested-With':'XMLHttpRequest'
        }})
          .then(async r=>{ try{ return await r.json(); } catch(e){ showNotification('Unexpected server response', true); throw e; } })
          .then(data=>{
            if(data.success){
              showNotification('Professor added successfully');
              form.reset();
              ModalManager.close('addFaculty');
              if(data.professor){ addOrUpdateCard(data.professor); }
            } else {
              let msg = data.message || 'Failed to add professor';
              if(data.errors){
                try {
                  const all = Object.values(data.errors).flat();
                  if(all.length) msg = all.join(' • ');
                } catch(_) { /* ignore */ }
              }
              showNotification(msg, true);
            }
          })
          .catch(err=>{ console.error(err); showNotification('Request failed: '+(err&&err.message?err.message:'Unexpected error'), true); })
          .finally(()=>{ if(overlay){ overlay.classList.remove('active'); overlay.setAttribute('aria-hidden','true'); } });
        });
      }, true);
    }

    function enhanceAddStudentPanel(){
      const form = document.getElementById('addStudentPanel');
      if(!form) return;
      const genBtn = document.getElementById('btnGenTempPwStudentComsci');
      const pwInput = document.getElementById('addStudentTempPasswordComsci');
      if(genBtn && pwInput){ genBtn.addEventListener('click', ()=>{ pwInput.value = generatePassword(12); pwInput.dispatchEvent(new Event('input',{bubbles:true})); }); }
      // Intercept Cancel and Close to confirm when fields have values
      const cancelBtn = form.querySelector('.panel-actions .btn-secondary');
      const closeBtn = document.getElementById('closeAddStudentPanel');
      const hasEdits = ()=>{
        const id = (form.querySelector('input[name="Stud_ID"]')?.value||'').trim();
        const name = (form.querySelector('input[name="Name"]')?.value||'').trim();
        const email = (form.querySelector('input[name="Email"]')?.value||'').trim();
        const pwd = (form.querySelector('input[name="Password"]')?.value||'').trim();
        return !!(id || name || email || pwd);
      };
      async function onCancelAttempt(e){
        if(e){ e.preventDefault(); e.stopPropagation(); }
        if(!hasEdits()){ ModalManager.close('addStudent'); return; }
        const ok = await adminConfirm('Confirm cancel', 'Are you sure you want to cancel adding a student? Your changes will not be saved.', 'Yes, cancel', 'No, keep editing');
        if(ok) ModalManager.close('addStudent');
      }
      if(cancelBtn){
        // Override inline onclick to route through our confirm
        cancelBtn.onclick = onCancelAttempt;
      }
      if(closeBtn){
        // Capture before ModalManager listener to prevent immediate close
        closeBtn.addEventListener('click', onCancelAttempt, true);
      }
      form.addEventListener('submit', function(e){
        if(form.dataset.ajaxDone) return;
        e.preventDefault();
        (async ()=>{
          const studId = (form.querySelector('input[name="Stud_ID"]')?.value||'').trim();
          const name = (form.querySelector('input[name="Name"]')?.value||'').trim();
          const email = (form.querySelector('input[name="Email"]')?.value||'').trim();
          const msg = `<div>Please confirm the student details:</div><ul style="margin:10px 0 0 18px; padding:0; list-style:disc;">
            <li><strong>ID:</strong> ${studId || '<em>—</em>'}</li>
            <li><strong>Name:</strong> ${name || '<em>—</em>'}</li>
            <li><strong>Email:</strong> ${email || '<em>—</em>'}</li>
          </ul>`;
          const ok = await adminConfirm('Add Student', msg, 'Yes, add student', 'Keep editing');
          if(!ok) return;
          const overlay = document.getElementById('globalLoading');
          if(overlay){ overlay.classList.add('active'); overlay.setAttribute('aria-hidden','false'); }
          requestAnimationFrame(()=>{
            fetch(form.action,{method:'POST', body: new FormData(form), headers:{ 'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]').content, 'Accept':'application/json', 'X-Requested-With':'XMLHttpRequest' }})
              .then(async r=>{ try{ return await r.json(); } catch(e){ showNotification('Unexpected server response', true); throw e; } })
              .then(data=>{ if(data.success){ showNotification('Student added successfully'); ModalManager.close('addStudent'); } else { let msg = data.message || 'Failed to add student'; if(data.errors){ try{ const all = Object.values(data.errors).flat(); if(all.length) msg = all.join(' • ');}catch(_){ }} showNotification(msg, true); } })
              .catch(err=>{ console.error(err); showNotification('Request failed: '+(err&&err.message?err.message:'Unexpected error'), true); })
              .finally(()=>{ if(overlay){ overlay.classList.remove('active'); overlay.setAttribute('aria-hidden','true'); } });
          });
        })();
      }, true);
    }

    function setupAddChooserModal(){
      const fab = document.getElementById('addChooserBtn');
      const modal = document.getElementById('addChooserModal');
      if(!fab || !modal) return;
      function toggle(force){ const open = force!==undefined?force:!modal.classList.contains('show'); modal.classList.toggle('show', open); modal.setAttribute('aria-hidden', open? 'false':'true'); }
      fab.addEventListener('click', ()=> toggle(true));
      modal.querySelectorAll('[data-close-chooser]').forEach(btn=> btn.addEventListener('click', ()=> toggle(false)));
      modal.addEventListener('click', (e)=>{ if(e.target === modal) toggle(false); });
      modal.querySelectorAll('[data-open-add]').forEach(btn=>{
        btn.addEventListener('click', ()=>{
          const type = btn.getAttribute('data-open-add');
          toggle(false);
          if(type==='faculty') ModalManager.open('addFaculty');
          if(type==='student') ModalManager.open('addStudent');
        });
      });
    }

    function addOrUpdateCard(p){
        const grid = document.querySelector('.profile-cards-grid');
        if(!grid) return;
        const existing = grid.querySelector(`[data-prof-id="${p.Prof_ID}"]`);
        const fallbackAvatar = @json(asset('images/dprof.jpg'));
        const imgPath = p.profile_photo_url || (p.profile_picture ? `/storage/${p.profile_picture}` : fallbackAvatar);
      if(existing){
        existing.dataset.name = p.Name;
        existing.dataset.sched = p.Schedule || 'No schedule set';
        existing.dataset.img = imgPath;
        existing.querySelector('.profile-name').textContent = p.Name;
        const imgEl= existing.querySelector('img'); if(imgEl) imgEl.src=imgPath;
        bindCardEvents(existing);
        return;
      }
      const div = document.createElement('div');
      div.className='profile-card';
      div.dataset.name=p.Name; div.dataset.img=imgPath; div.dataset.profId=p.Prof_ID; div.setAttribute('data-prof-id',p.Prof_ID); div.dataset.sched=p.Schedule||'No schedule set';
      bindCardEvents(div);
      div.innerHTML=`<img src="${imgPath}" alt="Profile Picture"><div class='profile-name'>${p.Name}</div>`;
      // Insert alphabetically by name for consistency with server ordering
      const cards = Array.from(grid.querySelectorAll('.profile-card'));
      const newName = p.Name.toLowerCase();
      let inserted = false;
      for(const c of cards){
        const cname = (c.getAttribute('data-name')||'').toLowerCase();
        if(newName < cname){
          grid.insertBefore(div, c);
          inserted = true;
          break;
        }
      }
      if(!inserted) grid.appendChild(div);
    }

    function bindCardEvents(card){
      card.onclick = function(e){
        if (e.target.tagName === 'BUTTON') return;
        const profId = card.getAttribute('data-prof-id');
        const name = card.getAttribute('data-name');
        const sched = card.getAttribute('data-sched');
        currentProfId = profId;
        populateEditForm(profId, name, sched);
        ModalManager.open('editFaculty');
      };
    }
    function openEditPanelFromCard(id){ /* kept for compatibility */ }

    function initRealtimeAdminComsci(){
      const script = document.createElement('script'); script.src='https://js.pusher.com/7.0/pusher.min.js'; script.onload=subscribe; document.body.appendChild(script);
      function subscribe(){
        const pusher = new Pusher('{{ config('broadcasting.connections.pusher.key') }}',{cluster:'{{ config('broadcasting.connections.pusher.options.cluster') }}'});
        const channel = pusher.subscribe('professors.dept.2');
        channel.bind('ProfessorAdded', data=> addOrUpdateCard(data));
        channel.bind('ProfessorUpdated', data=> { addOrUpdateCard(data); try{ lastUpdatedSchedule[String(data.Prof_ID)] = data.Schedule || ''; }catch(_){ } });
        channel.bind('ProfessorDeleted', data=> {
          const card = document.querySelector(`[data-prof-id="${data.Prof_ID}"]`); if(card) card.remove();
          showNotification('Professor deleted successfully');
        });
      }
    }

    // Intercept delete form submit to show notice immediately without full reload (with anti-spam)
    document.addEventListener('submit', function(e){
      const form = e.target;
      if(form && form.id === 'deleteForm'){
  if(form.dataset.submitting === '1'){ e.preventDefault(); return; }
  // 5-second cooldown per click
  if(form.dataset.cooldown === '1'){ e.preventDefault(); return; }
  e.preventDefault();
  form.dataset.cooldown = '1';
  setTimeout(()=>{ form.dataset.cooldown = '0'; }, 5000);
  form.dataset.submitting = '1';
  const confirmBtn = form.querySelector('.delete-confirm');
  const cancelBtn = form.querySelector('.delete-cancel');
  if(confirmBtn){ confirmBtn.disabled = true; confirmBtn.setAttribute('aria-busy','true'); }
        if(cancelBtn){ cancelBtn.disabled = true; }
        fetch(form.action, {method:'POST', headers:{'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]').content,'X-Requested-With':'XMLHttpRequest','Accept':'application/json'}, body: new FormData(form)})
          .then(r=> r.ok ? r.json().catch(()=>({success:true})) : Promise.reject(r))
          .then(()=>{ 
            // Optimistic local removal
            const id = form.action.split('/').pop();
            const card = document.querySelector(`[data-prof-id="${id}"]`);
            if(card) card.remove();
            ModalManager.close('deleteConfirm'); showNotification('Professor deleted successfully');
          })
          .catch(()=>{ ModalManager.close('deleteConfirm'); showNotification('Deletion failed', true); })
          .finally(()=>{
            form.dataset.submitting = '0';
            if(confirmBtn){ confirmBtn.disabled = false; confirmBtn.removeAttribute('aria-busy'); }
            if(cancelBtn){ cancelBtn.disabled = false; }
          });
      }
    }, true);

    // Apply lightweight click guards for Cancel/Open Delete in edit panel and delete cancel
    guardRapidClicks('.panel-actions .btn-secondary', 5000);
    guardRapidClicks('.delete-prof-btn-modal', 5000);
    guardRapidClicks('#deleteForm .delete-cancel', 5000);

    // Reusable green-themed confirm modal
    function adminConfirm(title, html, okText='Confirm', cancelText='Cancel'){
      return new Promise(resolve=>{
        const overlay=document.createElement('div'); overlay.className='confirm-overlay';
        const dlg=document.createElement('div'); dlg.className='confirm-modal'; dlg.setAttribute('role','dialog'); dlg.setAttribute('aria-modal','true');
        dlg.innerHTML = `
          <div class="confirm-header"><i class='bx bx-help-circle'></i><div>${title||'Please confirm'}</div></div>
          <div class="confirm-body">${html||'Are you sure?'}</div>
          <div class="confirm-actions">
            <button type="button" class="btn-cancel-red" id="admCancelBtn">${cancelText}</button>
            <button type="button" class="btn-confirm-green" id="admOkBtn">${okText}</button>
          </div>`;
        overlay.appendChild(dlg); document.body.appendChild(overlay);
        requestAnimationFrame(()=> overlay.classList.add('active'));
        const okBtn = dlg.querySelector('#admOkBtn');
        const cancelBtn = dlg.querySelector('#admCancelBtn');
        function cleanup(){ overlay.classList.remove('active'); setTimeout(()=> overlay.remove(), 120); }
        okBtn.addEventListener('click', ()=>{ cleanup(); resolve(true); });
        cancelBtn.addEventListener('click', ()=>{ cleanup(); resolve(false); });
        overlay.addEventListener('click', (e)=>{ if(!dlg.contains(e.target)){ cleanup(); resolve(false); }});
        document.addEventListener('keydown', function esc(e){ if(e.key==='Escape'){ e.preventDefault(); cleanup(); resolve(false); document.removeEventListener('keydown', esc);} });
      });
    }
  </script>
  <script src="{{ asset('js/admin-subjects.js') }}" defer></script>
</body>
</html>