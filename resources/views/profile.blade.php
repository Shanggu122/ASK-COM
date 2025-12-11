<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>Information Technology and Information Systems Department</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
  <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
  <link rel="stylesheet" href="{{ asset('css/profile.css') }}">
  <link rel="stylesheet" href="{{ asset('css/profile-shared.css') }}">
  <link rel="stylesheet" href="{{ asset('css/confirm-modal.css') }}">
</head>
<body>
  @include('components.navbar')

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
         <div class="student-id">{{ $user->Stud_ID }}</div>
       </div>
    </div>

    <!-- Basic Information -->
    <div class="info-section">
      <div class="section-title">BASIC INFORMATION</div>
      <table class="info-table">
        <tr>
          <td class="info-label">Full name</td>
           <td>{{ $user->Name }}</td>
        </tr>
        <tr>
          <td class="info-label">Email</td>
           <td>{{ $user->Email }}</td>
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

    <!-- Password Change Panel -->
    <div class="side-panel" id="passwordPanel">
      <div class="panel-header">
        <span>Change Password</span>
      </div>
      <div class="panel-body">
        <p>
          You can change your ASCC-IT account password here.
        </p>
  
  <form id="changePasswordForm" action="{{ route('changePassword') }}" method="POST">
          @csrf <!-- CSRF token to protect from cross-site request forgery -->
          
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
          <button type="button" class="cancel-btn" id="pw-cancel-btn">Cancel</button>
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
        <form id="profilePicForm" action="{{ route('profile.uploadPicture') }}" method="POST" enctype="multipart/form-data" class="profile-pic-form">
          @csrf
          <label for="sidePanelInputFile" class="upload-label">
            Upload new profile picture
          </label>
          <input type="file" id="sidePanelInputFile" name="profile_picture" accept="image/jpeg, image/png, image/jpg">
          <button type="submit" class="save-btn" id="sidePanelSaveBtn">Save</button>
        </form>
      </div>
    </div>

    <button class="chat-button" onclick="togglePanel('chatOverlay')">
      <i class='bx bxs-message-rounded-dots'></i>
      Click to chat with me!
    </button>

    <div class="chat-overlay" id="chatOverlay">
      <div class="chat-header">
        <span>ASK-COM</span>
        <button class="close-btn" onclick="closePanel('chatOverlay')">×</button>
      </div>
      <div class="chat-body" id="chatBody">
        <div class="message bot">Hi! How can I help you today?</div>
        <div id="chatBox"></div>
      </div>
      <div id="quickReplies" class="quick-replies" role="group" aria-label="Common questions">
        <button type="button" class="quick-reply" data-message="How do I book a consultation?">How do I book?</button>
        <button type="button" class="quick-reply" data-message="What are the consultation statuses?">Statuses?</button>
        <button type="button" class="quick-reply" data-message="How can I reschedule my consultation?">Reschedule</button>
        <button type="button" class="quick-reply" data-message="Can I cancel my booking?">Cancel booking</button>
        <button type="button" class="quick-reply" data-message="How do I contact my professor after booking?">Contact professor</button>
        <button type="button" class="quick-reply" data-message="Are there available slots?">Check availability</button>
        <button type="button" class="quick-reply" data-message="Do I have a schedule this week?">This week’s schedule</button>
        <button type="button" class="quick-reply" data-message="my pending schedules this week">My pending this week</button>
      </div>
      <button type="button" id="quickRepliesToggle" class="quick-replies-toggle" style="display:none" title="Show FAQs">
        <i class='bx bx-help-circle'></i>
      </button>

      <form id="chatForm" novalidate>
        <input type="text" id="message" placeholder="Type your message" autocomplete="off" spellcheck="false" required>
        <button type="submit">Send</button>
      </form>
    </div>

    <!-- Notification Div -->
    <div id="notification" class="notification">
      <span id="notification-message"></span>
      <button onclick="hideNotification()" class="close-btn">&times;</button>
    </div>
</div>

<script src="{{ asset('js/profile.js') }}"></script>
<script>
function showNotification(message, isError = false) {
  const notif = document.getElementById('notification');
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
  const pwPanel = document.getElementById('passwordPanel');
  pwPanel.classList.remove('open');
  // Reset password form and rules whenever panel is closed (switching away)
  if (typeof resetPasswordForm === 'function') resetPasswordForm();
  document.getElementById('profilePicPanel').classList.remove('open');
  document.getElementById('chatOverlay').classList.remove('open');
  // Open the requested panel
  document.getElementById(panelId).classList.toggle('open');
}

function closePanel(panelId) {
  const p = document.getElementById(panelId);
  if (!p) return;
  p.classList.remove('open');
  if (panelId === 'passwordPanel' && typeof resetPasswordForm === 'function') {
    resetPasswordForm();
  }
}

// Student profile: Ask for confirmation only if all three password fields are filled
document.addEventListener('DOMContentLoaded', function(){
  const cancelBtn = document.getElementById('pw-cancel-btn');
  if(!cancelBtn) return;
  // inject themed modal container once
  let overlay = document.getElementById('confirmOverlay');
  if(!overlay){
    overlay = document.createElement('div');
    overlay.id = 'confirmOverlay';
    overlay.className = 'confirm-overlay';
    overlay.innerHTML = `
      <div class="confirm-modal">
        <div class="confirm-header"><i class='bx bx-help-circle'></i> Confirm cancel</div>
        <div class="confirm-body">Are you sure you want to cancel changing your password? Your changes will not be saved.</div>
        <div class="confirm-actions">
          <button type="button" class="btn-cancel-red" id="confirmNo">No, keep editing</button>
          <button type="button" class="btn-confirm-green" id="confirmYes">Yes, cancel</button>
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
      // open modal
      overlay.classList.add('active');
      const onNo = ()=>{ overlay.classList.remove('active'); };
      const onYes = ()=>{ overlay.classList.remove('active'); closePanel('passwordPanel'); };
      overlay.querySelector('#confirmNo').onclick = onNo;
      overlay.querySelector('#confirmYes').onclick = onYes;
      // click outside to close (acts like cancel)
      overlay.addEventListener('click', (e)=>{ if(e.target === overlay) onNo(); }, { once:true });
      return;
    }
    closePanel('passwordPanel');
  });
});

// Profile picture panel file input and preview
const sidePanelInputFile = document.getElementById('sidePanelInputFile');
const sidePanelProfilePic = document.getElementById('sidePanelProfilePic');
const sidePanelSaveBtn = document.getElementById('sidePanelSaveBtn');
const profilePicForm = document.getElementById('profilePicForm');
let uploadSubmittingStudent = false; // anti-spam guard

// Validate file client-side (type and size) before previewing or submitting
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
      sidePanelSaveBtn.style.display = 'inline-block';
      sidePanelSaveBtn.disabled = false;
      sidePanelSaveBtn.textContent = 'Save';
    } else {
      // Reset invalid selection and keep current preview
      sidePanelInputFile.value = '';
      sidePanelSaveBtn.style.display = 'none';
      sidePanelSaveBtn.disabled = false;
      sidePanelSaveBtn.textContent = 'Save';
    }
  });
}

// Guard form submit in case of manual trigger
if (profilePicForm) {
  profilePicForm.addEventListener('submit', function(e){
    if (uploadSubmittingStudent) { e.preventDefault(); return; }
    const file = sidePanelInputFile && sidePanelInputFile.files && sidePanelInputFile.files[0];
    if(!file){
      e.preventDefault();
      showNotification('Please choose an image file to upload.', true);
      return;
    }
    if(!isValidProfileImage(file)){
      e.preventDefault();
      return;
    }
    uploadSubmittingStudent = true;
    sidePanelSaveBtn.disabled = true;
    sidePanelSaveBtn.textContent = 'Saving...';
  });
}

function deleteProfilePicture() {
  // Themed confirmation overlay
  let overlay = document.getElementById('confirmOverlayDeleteStud');
  if(!overlay){
    overlay = document.createElement('div');
    overlay.id = 'confirmOverlayDeleteStud';
    overlay.className = 'confirm-overlay';
    overlay.innerHTML = `
      <div class="confirm-modal">
        <div class="confirm-header"><i class='bx bx-trash'></i> Delete profile picture?</div>
        <div class="confirm-body">This will remove your current profile photo and revert to the default avatar.</div>
        <div class="confirm-actions">
          <button type="button" class="btn-cancel-red" id="delNoStud">Cancel</button>
          <button type="button" class="btn-confirm-green" id="delYesStud">Delete</button>
        </div>
      </div>`;
    document.body.appendChild(overlay);
  }
  const close = ()=> overlay.classList.remove('active');
  overlay.classList.add('active');
  overlay.querySelector('#delNoStud').onclick = close;
  overlay.querySelector('#delYesStud').onclick = function(){
    fetch("{{ route('profile.deletePicture') }}", {
      method: "POST",
      headers: { "X-CSRF-TOKEN": "{{ csrf_token() }}", "Accept": "application/json" }
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

// === Chatbot (dashboard parity) ===
const csrfToken = document
    .querySelector('meta[name="csrf-token"]')
    .getAttribute("content");
const chatForm = document.getElementById("chatForm");
const input = document.getElementById("message");
if(input){
  input.setAttribute('maxlength','250');
  input.setAttribute('autocomplete','off');
  input.setAttribute('spellcheck','false');
}
const chatBody = document.getElementById("chatBody");
const quickReplies = document.getElementById('quickReplies');
const quickRepliesToggle = document.getElementById('quickRepliesToggle');

function sendQuick(text){ if(!text) return; input.value = text; chatForm.dispatchEvent(new Event('submit')); }
quickReplies?.addEventListener('click', (e)=>{ const btn=e.target.closest('.quick-reply'); if(btn){ sendQuick(btn.dataset.message); } });
quickRepliesToggle?.addEventListener('click', ()=>{ if(quickReplies){ quickReplies.style.display='flex'; quickRepliesToggle.style.display='none'; } });

function sanitize(raw){
  if(!raw) return '';
  return raw
    .replace(/\/*.*?\*\//g,'')
    .replace(/--+/g,' ')
    .replace(/[;`'"<>]/g,' ')
    .replace(/\s+/g,' ')
    .trim()
    .slice(0,250);
}

chatForm.addEventListener("submit", async function (e) {
    e.preventDefault();
    const text = sanitize(input.value);
    if (!text) return;

    // hide quick replies on first interaction
    if (quickReplies && quickReplies.style.display !== 'none') {
      quickReplies.style.display = 'none';
      if (quickRepliesToggle) quickRepliesToggle.style.display = 'flex';
    }

    const um = document.createElement("div");
    um.classList.add("message", "user");
    um.innerText = text;
    chatBody.appendChild(um);
    chatBody.scrollTop = chatBody.scrollHeight;
    input.value = "";

    const res = await fetch("/chat", {
        method: "POST",
        credentials: "same-origin",
        headers: {
            Accept: "application/json",
            "Content-Type": "application/json",
            "X-CSRF-TOKEN": csrfToken,
        },
        body: JSON.stringify({ message: text }),
    });

    if (!res.ok) {
        const err = await res.json();
        const bm = document.createElement("div");
        bm.classList.add("message", "bot");
        bm.innerText = err.message || "Server error.";
        chatBody.appendChild(bm);
        return;
    }

    const { reply } = await res.json();
    const bm = document.createElement("div");
    bm.classList.add("message", "bot");
    bm.innerText = reply;
    chatBody.appendChild(bm);
    chatBody.scrollTop = chatBody.scrollHeight;
});

// ===== Live password requirements (student profile) =====
document.addEventListener('DOMContentLoaded', function(){
  const pw = document.getElementById('newPassword');
  const pw2 = document.getElementById('newPassword_confirmation');
  const form = document.getElementById('changePasswordForm');
  if(!pw || !form) return;
  const rules = {
    len: document.getElementById('rule-len'),
    low: document.getElementById('rule-low'),
    up:  document.getElementById('rule-up'),
    num: document.getElementById('rule-num'),
    spec:document.getElementById('rule-spec'),
    match:document.getElementById('rule-match')
  };
  function setState(el, ok){ if(!el) return; el.classList.toggle('pass', !!ok); el.classList.toggle('fail', !ok); const ic=el.querySelector('.icon'); if(ic) ic.textContent = ok ? '✓' : '✖'; }
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
      showNotification('New password and confirmation do not match.', true);
      return false;
    }
  });

  // Reset helper to clear inputs and checklist when panel is closed
  window.resetPasswordForm = function(){
    try {
      const formEl = document.getElementById('changePasswordForm');
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
  };
});
</script>
@php
  $pwHasErrors = session('error') || $errors->has('oldPassword') || $errors->has('newPassword') || $errors->has('newPassword_confirmation');
@endphp
@if($pwHasErrors)
<script>
  // Keep the password panel open when there are server-side errors
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
      // Close the password panel first
      closePanel('passwordPanel');
      // Build confirmation modal (reuse confirm-overlay styles)
      let overlay = document.getElementById('pwChangedConfirmStud');
      if(!overlay){
        overlay = document.createElement('div');
        overlay.id = 'pwChangedConfirmStud';
        overlay.className = 'confirm-overlay';
        overlay.innerHTML = `
          <div class="confirm-modal" role="dialog" aria-modal="true" aria-labelledby="pwChangedTitleStud">
            <div class="confirm-header" id="pwChangedTitleStud"><i class='bx bx-check-circle'></i> Password changed</div>
            <div class="confirm-body">Your password was changed successfully. Do you want to log out now?</div>
            <div class="confirm-actions">
              <button type="button" class="btn-cancel-red" id="pwStayStud">Stay signed in</button>
              <button type="button" class="btn-confirm-green" id="pwLogoutStud">Log out</button>
            </div>
          </div>`;
        document.body.appendChild(overlay);
      }
      const close = ()=> overlay.classList.remove('active');
      overlay.classList.add('active');
      // Wire buttons
      overlay.querySelector('#pwStayStud').onclick = close;
      overlay.querySelector('#pwLogoutStud').onclick = function(){ window.location.href = @json(route('logout')); };
      // Click outside closes
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
        showNotification('New password and confirmation password do not match.', true);
      @else
        showNotification(@json($errors->first()), true);
      @endif
    });
  </script>
@endif
</body>
</html>

