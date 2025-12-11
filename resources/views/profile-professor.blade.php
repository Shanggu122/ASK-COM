<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Profile</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
  <link rel="stylesheet" href="{{ asset('css/profile-professor.css') }}">
    <link rel="stylesheet" href="{{ asset('css/profile-shared.css') }}">
  <link rel="stylesheet" href="{{ asset('css/confirm-modal.css') }}">
    <link rel="stylesheet" href="{{ asset('css/logout-confirm.css') }}">
    <script src="{{ asset('js/logout-confirm.js') }}" defer></script>
</head>
<body>
  @include('components.navbarprof')

  <div class="main-content">
    <!-- Header -->
    <div class="header-info">
      <div class="profile-pic-wrapper">
    <img src="{{ $user->profile_photo_url }}" alt="Profile Picture" class="profile-picture" id="profilePicture">
        <button type="button" class="edit-profile-pic-btn" onclick="togglePanel('profilePicPanel')">
          <i class='bx bx-camera'></i>
        </button>
      </div>
      <div>
        <h2 class="user-name">{{ $user->Name }}</h2>
        <div class="prof-id">{{ $user->Prof_ID }}</div>
      </div>
    </div>

    <!-- Basic Information -->
    <div class="info-section">
      <div class="section-title">BASIC INFORMATION</div>
      <table class="info-table">
        <tr>
          <td class="info-label">Full name</td>
          <td>{{ $user->Name ?? '' }}</td>
        </tr>
        <tr>
          <td class="info-label">Email</td>
          <td>{{ $user->Email ?? '' }}</td>
        </tr>
        <tr>
          <td class="info-label">Password</td>
          <td>
            <a href="javascript:void(0)" onclick="togglePanel('passwordPanel')" class="change-link">Change Password</a>
            <i class='bx bx-edit-alt edit-icon' title="Edit Password"></i>
          </td>
        </tr>
      </table>
    </div>

    <!-- Schedule Section -->
    <div class="info-section">
      <div class="section-title">SCHEDULE</div>
      <table class="info-table info-table--schedule">
        <tr>
          <td class="info-label info-label--schedule"><span class="schedule-label-text">Weekly schedule</span></td>
          <td>
            @php
              $scheduleText = trim((string)($user->Schedule ?? ''));
              $lines = $scheduleText !== '' ? preg_split('/\r\n|\r|\n/', $scheduleText) : [];
            @endphp
            @if(!empty($lines))
              <ul class="schedule-list">
                @foreach($lines as $line)
                  @if(trim($line) !== '')
                    <li>{{ $line }}</li>
                  @else
                    <li class="schedule-empty-line"></li>
                  @endif
                @endforeach
              </ul>
            @else
              <span class="schedule-no-data">No schedule set</span>
            @endif
            <!-- Editing schedule is admin-only; no edit UI for professors -->
          </td>
        </tr>
      </table>
    </div>

    <!-- Chat Overlay Panel -->
    <div class="chat-overlay" id="chatOverlay">
      <div class="chat-header">
        <span>ASK-COM</span>
        <button class="close-btn" onclick="closePanel('chatOverlay')">×</button>
      </div>
      <div class="chat-body" id="chatBody">
        <div class="message bot">Hi! How can I help you today?</div>
      </div>
      <div id="quickReplies" class="quick-replies">
        <button type="button" class="quick-reply" data-message="What are my consultations for today?">Today's consultations</button>
        <button type="button" class="quick-reply" data-message="Who are the students scheduled for consultation today?">Students today</button>
        <button type="button" class="quick-reply" data-message="What are my consultations for this week?">This week</button>
        <button type="button" class="quick-reply" data-message="How many consultation slots are still available today?">Slots today</button>
        <button type="button" class="quick-reply" data-message="What is my schedule?">My schedule</button>
      </div>
      <button type="button" id="quickRepliesToggle" class="quick-replies-toggle" style="display:none" title="Show FAQs">
        <i class='bx bx-help-circle'></i>
      </button>
      <form id="chatForm">
        <input type="text" id="userInput" placeholder="Type your message..." required>
        <button type="submit">Send</button>
      </form>
    </div>

    <!-- Password Change Panel -->
    <div class="side-panel" id="passwordPanel">
      <div class="panel-header">
        <span>Change Password</span>
      </div>
      <div class="panel-body">
        <p>
          You can change your ASCC-IT account password here.
        </p>
        <form id="changePasswordFormProf" action="{{ route('changePassword.professor') }}" method="POST">
          @csrf
          <!-- Old Password -->
          <label for="oldPassword">Old Password</label>
          <div class="password-field">
            <input type="password" id="oldPassword" name="oldPassword" placeholder="Enter current password" required>
            <i class='bx bx-hide eye-icon' onclick="togglePasswordVisibility('oldPassword', this)"></i>
          </div>
          <!-- New Password -->
          <label for="newPassword">New Password</label>
          <div class="password-field">
            <input type="password" id="newPassword" name="newPassword" placeholder="Enter new password" required
                   minlength="12" autocomplete="new-password"
                   pattern="(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@#$%])[A-Za-z\d@#$%]{12,}"
                   title="At least 12 characters, with at least 1 lowercase, 1 uppercase, 1 number, and 1 of @,#,$,%">
            <i class='bx bx-hide eye-icon' onclick="togglePasswordVisibility('newPassword', this)"></i>
          </div>
          <ul class="pw-rules" id="pw-rules" aria-live="polite">
            <li id="rule-len" class="fail"><span class="icon">✖</span><span>Password must be at least 12 characters long</span></li>
            <li id="rule-low" class="fail"><span class="icon">✖</span><span>Must include at least one lowercase letter</span></li>
            <li id="rule-up"  class="fail"><span class="icon">✖</span><span>Must include at least one uppercase letter</span></li>
            <li id="rule-num" class="fail"><span class="icon">✖</span><span>Must include at least one number</span></li>
            <li id="rule-spec" class="fail"><span class="icon">✖</span><span>Must include at least one special character (@, #, $, %)</span></li>
          </ul>
          <!-- Confirm New Password -->
          <label for="newPassword_confirmation">Confirm New Password</label>
          <div class="password-field">
            <input type="password" id="newPassword_confirmation" name="newPassword_confirmation" placeholder="Confirm new password" required minlength="12" autocomplete="new-password">
            <i class='bx bx-hide eye-icon' onclick="togglePasswordVisibility('newPassword_confirmation', this)"></i>
          </div>
          <small class="confirm-password-hint">
            Please re-enter your new password to confirm.
          </small>
          <ul class="pw-rules pw-rules--match" aria-live="polite">
            <li id="rule-match" class="fail"><span class="icon">✖</span><span>Passwords do not match</span></li>
          </ul>
          <div class="panel-footer">
            <button type="button" class="cancel-btn" id="pw-cancel-btn-prof">Cancel</button>
            <button type="submit" class="save-btn">Save</button>
          </div>
        </form>
      </div>
    </div>

    <!-- Profile Picture Change Panel -->
    <div class="side-panel" id="profilePicPanel">
      <div class="panel-header">
        <span>Change Profile Picture</span>
        <button class="close-btn" type="button" onclick="closePanel('profilePicPanel')">&times;</button>
      </div>
      <div class="panel-body profile-pic-panel-body">
        <div class="profile-pic-container">
    <img id="sidePanelProfilePic"
      src="{{ $user->profile_photo_url }}"
      alt="Profile Picture"
      class="side-panel-profile-pic">
          <button class="delete-pic-btn" type="button" onclick="deleteProfilePicture()" title="Delete Profile Picture">
            <i class='bx bx-trash'></i>
          </button>
        </div>
        <form id="profilePicForm" action="{{ route('profile.uploadPicture.professor') }}" method="POST" enctype="multipart/form-data" class="profile-pic-form">
          @csrf
          <label for="sidePanelInputFile" class="upload-label">
            Upload new profile picture
          </label>
          <input type="file" id="sidePanelInputFile" name="profile_picture" accept="image/jpeg, image/png, image/jpg">
          <button type="submit" class="save-btn" id="sidePanelSaveBtn">Save</button>
        </form>
      </div>
    </div>

    <!-- Schedule editor removed for professors -->

    <button class="chat-button" onclick="togglePanel('chatOverlay')">
      <i class='bx bxs-message-rounded-dots'></i>
      Click to chat with me!
    </button>

    <!-- Notification Div -->
    <div id="notification" class="notification" style="display:none;">
      <span id="notification-message"></span>
      <button onclick="hideNotification()" class="close-btn">&times;</button>
    </div>
  </div>

  <script src="{{ asset('js/profileProf.js') }}"></script>
  <script>
function showNotification(message, isError = false) {
  let notif = document.getElementById('notification');
  notif.classList.toggle('error', isError);
  document.getElementById('notification-message').textContent = message;
  notif.style.display = 'flex';
  setTimeout(hideNotification, 4000);
}
function hideNotification() {
  document.getElementById('notification').style.display = 'none';
}

function togglePanel(panelId) {
  // Close all panels first
  let panels = ['passwordPanel', 'profilePicPanel', 'chatOverlay'];
  panels.forEach(id => {
    let el = document.getElementById(id);
    if (el) {
      const wasOpen = el.classList.contains('open');
      el.classList.remove('open');
      // If password panel was open and is being closed due to switching, reset it
      if (id === 'passwordPanel' && wasOpen && typeof resetPasswordFormProf === 'function') {
        resetPasswordFormProf();
      }
    }
  });
  // Open the requested panel
  let panel = document.getElementById(panelId);
  if (panel) panel.classList.toggle('open');
}

function closePanel(panelId) {
  let panel = document.getElementById(panelId);
  if (!panel) return;
  const wasOpen = panel.classList.contains('open');
  panel.classList.remove('open');
  if (panelId === 'passwordPanel' && wasOpen && typeof resetPasswordFormProf === 'function') {
    resetPasswordFormProf();
  }
}

// Profile picture panel file input and preview
const sidePanelInputFile = document.getElementById('sidePanelInputFile');
const sidePanelProfilePic = document.getElementById('sidePanelProfilePic');
const sidePanelSaveBtn = document.getElementById('sidePanelSaveBtn');
const profilePicForm = document.getElementById('profilePicForm');
let uploadSubmittingProf = false; // anti-spam guard

function isValidProfileImage(file){
  if(!file) return false;
  const allowedTypes = ['image/jpeg','image/png','image/jpg'];
  const maxBytes = 2 * 1024 * 1024; // 2MB
  if(!allowedTypes.includes(file.type)){
    showNotification('Invalid file. Please select a JPG or PNG image only.', true);
    return false;
  }
  if(file.size > maxBytes){
    showNotification('File too large. Max size is 2 MB.', true);
    return false;
  }
  return true;
}

if (sidePanelInputFile) {
  sidePanelInputFile.addEventListener('change', function(){
    const file = sidePanelInputFile.files && sidePanelInputFile.files[0];
    if(file && isValidProfileImage(file)){
      sidePanelProfilePic.src = URL.createObjectURL(file);
      if (sidePanelSaveBtn){
        sidePanelSaveBtn.style.display = 'inline-block';
        sidePanelSaveBtn.disabled = false;
        sidePanelSaveBtn.textContent = 'Save';
      }
    } else {
      sidePanelInputFile.value = '';
      if (sidePanelSaveBtn){
        sidePanelSaveBtn.style.display = 'none';
        sidePanelSaveBtn.disabled = false;
        sidePanelSaveBtn.textContent = 'Save';
      }
    }
  });
}

if (profilePicForm) {
  profilePicForm.addEventListener('submit', function(e){
    if (uploadSubmittingProf) { e.preventDefault(); return; }
    const file = sidePanelInputFile && sidePanelInputFile.files && sidePanelInputFile.files[0];
    if(!file){ e.preventDefault(); showNotification('Please choose an image file to upload.', true); return; }
    if(!isValidProfileImage(file)) { e.preventDefault(); return; }
    // Passed validation: lock button and prevent double-submit
    uploadSubmittingProf = true;
    if (sidePanelSaveBtn){
      sidePanelSaveBtn.disabled = true;
      sidePanelSaveBtn.textContent = 'Saving...';
    }
  });
}

function deleteProfilePicture() {
  // Themed confirmation overlay
  let overlay = document.getElementById('confirmOverlayDeleteProf');
  if(!overlay){
    overlay = document.createElement('div');
    overlay.id = 'confirmOverlayDeleteProf';
    overlay.className = 'confirm-overlay';
    overlay.innerHTML = `
      <div class="confirm-modal">
        <div class="confirm-header"><i class='bx bx-trash'></i> Delete profile picture?</div>
        <div class="confirm-body">This action will remove your current profile photo and revert to the default avatar.</div>
        <div class="confirm-actions">
          <button type="button" class="btn-cancel-red" id="delNoProf">Cancel</button>
          <button type="button" class="btn-confirm-green" id="delYesProf">Delete</button>
        </div>
      </div>`;
    document.body.appendChild(overlay);
  }
  const close = ()=> overlay.classList.remove('active');
  overlay.classList.add('active');
  // wire handlers
  overlay.querySelector('#delNoProf').onclick = close;
  overlay.querySelector('#delYesProf').onclick = function(){
    fetch("{{ route('profile.deletePicture.professor') }}", {
      method: "POST",
      headers: {
        "X-CSRF-TOKEN": "{{ csrf_token() }}",
        "Accept": "application/json"
      }
    })
    .then(r => r.json())
    .then(data => {
      if(data.success){
        document.getElementById('profilePicture').src = "{{ asset('images/dprof.jpg') }}";
        document.getElementById('sidePanelProfilePic').src = "{{ asset('images/dprof.jpg') }}";
        showNotification('Profile picture deleted.');
      } else {
        showNotification('Failed to delete profile picture.', true);
      }
    })
    .catch(() => showNotification('Error deleting profile picture.', true))
    .finally(close);
  };
  overlay.addEventListener('click', (e)=>{ if(e.target === overlay) close(); }, { once:true });
}

// Initialize chat functionality exactly like dashboard
document.addEventListener('DOMContentLoaded', function() {
  const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
  const chatForm = document.getElementById('chatForm');
  const input = document.getElementById('userInput');  // Profile uses userInput instead of message
  const chatBody = document.getElementById('chatBody');
  const quickReplies = document.getElementById('quickReplies');
  const quickRepliesToggle = document.getElementById('quickRepliesToggle');

  if (chatForm && input && chatBody && csrfToken) {
    // input hardening
    input.setAttribute('maxlength','250');
    input.setAttribute('autocomplete','off');
    input.setAttribute('spellcheck','false');

    function sendQuick(text){ if(!text) return; input.value = text; chatForm.dispatchEvent(new Event('submit')); }
    quickReplies?.addEventListener('click',(e)=>{ const btn=e.target.closest('.quick-reply'); if(btn){ sendQuick(btn.dataset.message); } });
    quickRepliesToggle?.addEventListener('click',()=>{ if(quickReplies){ quickReplies.style.display='flex'; quickRepliesToggle.style.display='none'; } });
    chatForm.addEventListener('submit', async function(e) {
      e.preventDefault();
      const text = input.value.trim();
      if (!text) return;

      // hide quick replies on first interaction
      if(quickReplies && quickReplies.style.display !== 'none'){
        quickReplies.style.display = 'none';
        if(quickRepliesToggle) quickRepliesToggle.style.display = 'flex';
      }

      // show user message
      const um = document.createElement('div');
      um.classList.add('message', 'user');
      um.innerText = text;
      chatBody.appendChild(um);

      chatBody.scrollTop = chatBody.scrollHeight;
      input.value = '';

      // send request to server
      const res = await fetch('/chat', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': csrfToken,  
        },
        body: JSON.stringify({ message: text }),
      });

      if (!res.ok) {
        const err = await res.json();
        const bm = document.createElement('div');
        bm.classList.add('message', 'bot');
        bm.innerText = err.message || 'Server error.';
        chatBody.appendChild(bm);
        return;
      }

      // render bot reply
      const { reply } = await res.json();
      const bm = document.createElement('div');
      bm.classList.add('message', 'bot');
      bm.innerText = reply;
      chatBody.appendChild(bm);
      chatBody.scrollTop = chatBody.scrollHeight;
    });
  }
});

// OVERRIDE function for backward compatibility (if called directly)
async function sendMessage() {
    const input = document.getElementById("userInput");
    if (!input) return;
    
    const form = document.getElementById('chatForm');
    if (form) {
        // Trigger the form submit which will handle everything
        form.dispatchEvent(new Event('submit'));
    }
}

// Add Enter key functionality for forms
document.addEventListener('DOMContentLoaded', function() {
    // Password change form inputs
    const passwordInputs = ['oldPassword', 'newPassword', 'newPassword_confirmation'];
    passwordInputs.forEach(inputId => {
        const input = document.getElementById(inputId);
        if (input) {
            input.addEventListener('keydown', function(event) {
                if (event.key === 'Enter') {
                    event.preventDefault();
                    const form = input.closest('form');
                    if (form) {
                        form.requestSubmit();
                    }
                }
            });
        }
    });
    
    // Add Enter key functionality for the chat input (exactly like dashboard)
    const messageInput = document.getElementById('userInput');
    if (messageInput) {
        messageInput.addEventListener('keydown', function(event) {
            if (event.key === 'Enter' && !event.shiftKey) {
                event.preventDefault();
                const form = document.getElementById('chatForm');
                if (form) {
                    form.dispatchEvent(new Event('submit'));
                }
            }
        });
    }
});
  // Live password requirements (professor profile)
  document.addEventListener('DOMContentLoaded', function(){
    const pw = document.getElementById('newPassword');
    const pw2 = document.getElementById('newPassword_confirmation');
    const form = document.getElementById('changePasswordFormProf');
    if(!pw || !form) return;
    const rules = {
      len: document.getElementById('rule-len'),
      low: document.getElementById('rule-low'),
      up:  document.getElementById('rule-up'),
      num: document.getElementById('rule-num'),
      spec:document.getElementById('rule-spec'),
      match:document.getElementById('rule-match')
    };
    function setState(el, ok){
      if(!el) return;
      el.classList.toggle('pass', !!ok);
      el.classList.toggle('fail', !ok);
      const ic = el.querySelector('.icon');
      if(ic) ic.textContent = ok ? '✓' : '✖';
    }
    function evalPw(v){
      const tests = {
        len: v.length >= 12,
        low: /[a-z]/.test(v),
        up:  /[A-Z]/.test(v),
        num: /\d/.test(v),
        spec:/[@#$%]/.test(v)
      };
      setState(rules.len, tests.len);
      setState(rules.low, tests.low);
      setState(rules.up,  tests.up);
      setState(rules.num, tests.num);
      setState(rules.spec,tests.spec);
      return tests;
    }
    function evalMatch(){
      if(!rules.match) return true;
      const ok = (pw2?.value?.length||0) > 0 && pw2.value === pw.value;
      setState(rules.match, ok);
      const label = rules.match.querySelector('span:not(.icon)');
      if(label){ label.textContent = ok ? 'Passwords match' : 'Passwords do not match'; }
      return ok;
    }
    function allOk(t){ return t.len && t.low && t.up && t.num && t.spec && evalMatch(); }
    pw.addEventListener('input', ()=>{ evalPw(pw.value); evalMatch(); });
    pw2 && pw2.addEventListener('input', ()=> evalMatch());
    // Initialize on load (in case of autofill)
    evalPw(pw.value||''); evalMatch();
    // Block submit if requirements not met or confirmation mismatch
    form.addEventListener('submit', function(ev){
      const t = evalPw(pw.value||'');
      if(!allOk(t)){
        ev.preventDefault();
        showNotification('Please meet all password requirements before saving.', true);
        return false;
      }
      if(pw2 && pw2.value !== pw.value){
        ev.preventDefault();
        showNotification('Your new password and confirmation password do not match. Please re-enter them correctly.', true);
        return false;
      }
    });
  });
  // Reset helper to clear inputs and checklist when panel is closed
  function resetPasswordFormProf(){
    try {
      const formEl = document.getElementById('changePasswordFormProf');
      if (formEl) formEl.reset();
      const ids = ['oldPassword','newPassword','newPassword_confirmation'];
      ids.forEach(id => { const el = document.getElementById(id); if (el) el.value = ''; });
      const ruleIds = ['rule-len','rule-low','rule-up','rule-num','rule-spec','rule-match'];
      ruleIds.forEach(rid => {
        const li = document.getElementById(rid);
        if (!li) return;
        li.classList.remove('pass');
        li.classList.add('fail');
        const ic = li.querySelector('.icon'); if (ic) ic.textContent = '✖';
        if (rid === 'rule-match') {
          const label = li.querySelector('span:not(.icon)');
          if (label) label.textContent = 'Passwords do not match';
        }
      });
    } catch(_) {}
  }
  </script>
  @php
    $pwHasErrorsProf = session('error') || $errors->has('oldPassword') || $errors->has('newPassword') || $errors->has('newPassword_confirmation');
  @endphp
  @if($pwHasErrorsProf)
  <script>
    document.addEventListener('DOMContentLoaded', function(){
      const p = document.getElementById('passwordPanel');
      if(p) p.classList.add('open');
    });
  </script>
  @endif

  <!-- Blade logic to trigger notification -->
  @if (session('status'))
    <script>
      document.addEventListener('DOMContentLoaded', function() {
        showNotification(@json(session('status')), false);
      });
    </script>
  @endif

  @if (session('password_status'))
    <script>
      document.addEventListener('DOMContentLoaded', function() {
        closePanel('passwordPanel');
        // Build confirmation modal asking to logout or stay
        let overlay = document.getElementById('pwChangedConfirmProf');
        if(!overlay){
          overlay = document.createElement('div');
          overlay.id = 'pwChangedConfirmProf';
          overlay.className = 'confirm-overlay';
          overlay.innerHTML = `
            <div class="confirm-modal" role="dialog" aria-modal="true" aria-labelledby="pwChangedTitleProf">
              <div class="confirm-header" id="pwChangedTitleProf"><i class='bx bx-check-circle'></i> Password changed</div>
              <div class="confirm-body">Your password was changed successfully. Do you want to log out now?</div>
              <div class="confirm-actions">
                <button type="button" class="btn-cancel-red" id="pwStayProf">Stay signed in</button>
                <button type="button" class="btn-confirm-green" id="pwLogoutProf">Log out</button>
              </div>
            </div>`;
          document.body.appendChild(overlay);
        }
        const close = ()=> overlay.classList.remove('active');
        overlay.classList.add('active');
        overlay.querySelector('#pwStayProf').onclick = close;
        overlay.querySelector('#pwLogoutProf').onclick = function(){ window.location.href = @json(route('logout-professor')); };
        overlay.addEventListener('click', (e)=>{ const m = overlay.querySelector('.confirm-modal'); if(m && !m.contains(e.target)) close(); }, { once:true });
      });
    </script>
  @endif

  @if ($errors->any())
    <script>
      document.addEventListener('DOMContentLoaded', function() {
        // Handle password-specific errors
        @if ($errors->has('oldPassword'))
          showNotification(@json($errors->first('oldPassword')), true);
        @elseif ($errors->has('newPassword'))
          showNotification(@json($errors->first('newPassword')), true);
        @elseif ($errors->has('newPassword_confirmation'))
          showNotification('Your new password and confirmation password do not match. Please re-enter them correctly.', true);
        @else
          showNotification(@json($errors->first()), true);
        @endif
      });
    </script>
  @endif

    <script>
    // Professor: confirmation modal for cancel (only if all fields filled)
    document.addEventListener('DOMContentLoaded', function(){
      const cancelBtn = document.getElementById('pw-cancel-btn-prof');
      if(!cancelBtn) return;
      let overlay = document.getElementById('confirmOverlayProf');
      if(!overlay){
        overlay = document.createElement('div');
        overlay.id = 'confirmOverlayProf';
        overlay.className = 'confirm-overlay';
        overlay.innerHTML = `
          <div class="confirm-modal">
            <div class="confirm-header"><i class='bx bx-help-circle'></i> Confirm cancel</div>
            <div class="confirm-body">Are you sure you want to cancel changing your password? Your changes will not be saved.</div>
            <div class="confirm-actions">
              <button type="button" class="btn-cancel-red" id="confirmNoProf">No, keep editing</button>
              <button type="button" class="btn-confirm-green" id="confirmYesProf">Yes, cancel</button>
            </div>
          </div>`;
        document.body.appendChild(overlay);
      }
      cancelBtn.addEventListener('click', function(){
        const oldP = document.getElementById('oldPassword');
        const newP = document.getElementById('newPassword');
        const confP = document.getElementById('newPassword_confirmation');
        const allFilled = [oldP, newP, confP].every(el => el && el.value.trim().length > 0);
        if(allFilled){
          overlay.classList.add('active');
          const onNo = ()=>{ overlay.classList.remove('active'); };
          const onYes = ()=>{ overlay.classList.remove('active'); closePanel('passwordPanel'); };
          document.getElementById('confirmNoProf').onclick = onNo;
          document.getElementById('confirmYesProf').onclick = onYes;
          overlay.addEventListener('click', (e)=>{ if(e.target === overlay) onNo(); }, { once:true });
          return;
        }
        closePanel('passwordPanel');
      });
    });
    </script>

</body>
</html>