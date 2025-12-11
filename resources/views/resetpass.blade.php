<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Create New Password</title>
  <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
  <link rel="stylesheet" href="{{ asset('css/reset.css') }}">
  <style>
    /* Compact rules popover shown only while typing */
    .pw-field-wrapper { position: relative; }
    .pw-rules-popover {
      position: absolute;
      top: 100%;
      left: 0;
      margin-top: -6px; /* overlap slightly for tighter attach */
      background: #ffffff;
      color: #333;
      border: 1px solid #dfe5e1;
      border-radius: 8px;
      box-shadow: 0 8px 20px rgba(0,0,0,0.12);
      padding: 10px 12px;
      width: 100%;
      max-width: 420px;
      z-index: 1050;
      display: none;
    }
    .pw-rules-popover:before { content:''; position:absolute; top:-6px; left:18px; border-left:6px solid transparent; border-right:6px solid transparent; border-bottom:6px solid #dfe5e1; }
    .pw-rules-popover:after { content:''; position:absolute; top:-5px; left:18px; border-left:5px solid transparent; border-right:5px solid transparent; border-bottom:5px solid #ffffff; }
    .pw-rules-list { margin:0; padding-left:18px; }
    .pw-rules-list li { font-size:12px; line-height:1.4; margin:4px 0; color:#b23b3b; list-style:none; position:relative; padding-left:18px; }
    .pw-rules-list li::before { content:'✕'; position:absolute; left:0; top:0; color:#c0392b; font-weight:700; }
    .pw-rules-list li.ok { color:#1e8f5a; }
    .pw-rules-list li.ok::before { content:'✓'; color:#1e8f5a; }
    .pw-match-msg { margin-top:6px; font-size:12px; color:#c0392b; display:none; }
    /* Ensure popover doesn't overflow narrow layouts */
    @media (max-width: 480px){ .pw-rules-popover { max-width:none; } }
  </style>
  
</head>
<body>
  <div class="container">
    <!-- Left Panel -->
    <div class="left-panel">
      <!-- Update image path using asset() -->
      <img src="{{ asset('images/CCIT_logo2.png') }}" alt="Adamson Logo" class="left-logo"/>
      <h2 class="college-title">
        <div class="adamson-uni">Adamson University</div>
        <div class="college-bottom">College of Computing and Information Technology</div>
      </h2>
    </div>

    <!-- Right Panel -->
    <div class="right-panel">
      <div class="fp-header">
  @php $roleParam = request('role') ?? session('password_reset_role_param'); @endphp
  <a href="{{ route('otp.verify.form', ['role'=>$roleParam]) }}" class="back-btn">
          <i class='bx bx-chevron-left'></i>
        </a>
        <span class="fp-title">New Password</span>
      </div>
      <form action="{{ route('password.update') }}" method="POST" novalidate>
        @csrf
  <div class="input-group pw-field-wrapper">
          <input type="password" name="new_password" id="new-password" placeholder="New Password" required aria-describedby="pwRules pwError">
          <i class='bx bx-hide toggle-password' data-target="new-password"></i>
          <!-- On-type rules popover -->
          <div id="pwRules" class="pw-rules-popover" role="status" aria-live="polite" aria-hidden="true">
            <ul class="pw-rules-list">
              <li data-rule="len">Password must be at least 12 characters long</li>
              <li data-rule="lower">Must include at least one lowercase letter</li>
              <li data-rule="upper">Must include at least one uppercase letter</li>
              <li data-rule="num">Must include at least one number</li>
              <li data-rule="special">Must include at least one special character (@, #, $, %)</li>
            </ul>
          </div>
          @php $pwErr = $errors->first('new_password'); @endphp
          <div class="field-error {{ $pwErr ? '' : 'hidden' }}" id="pwError">{{ $pwErr }}</div>
        </div>
        <div class="input-group">
          <input type="password" name="new_password_confirmation" id="confirm-password" placeholder="Confirm New Password" required aria-describedby="pwMatchMsg pwConfirmError"/>
          <i class='bx bx-hide toggle-password' data-target="confirm-password"></i>
          <div id="pwMatchMsg" class="field-error hidden" aria-live="polite">Passwords do not match</div>
          @php $pwCErr = $errors->first('new_password_confirmation'); @endphp
          <div class="field-error {{ $pwCErr ? '' : 'hidden' }}" id="pwConfirmError">{{ $pwCErr }}</div>
        </div>
        <button type="submit" class="login-btn">Reset Password</button>
      </form>
    </div>
  </div>
 <script >
      // On-type rules + confirm match indicator + custom client-side validation
      (function(){
        const pw = document.getElementById('new-password');
        const confirmPw = document.getElementById('confirm-password');
        const pop = document.getElementById('pwRules');
        const matchMsg = document.getElementById('pwMatchMsg');
        const pwError = document.getElementById('pwError');
        const pwConfirmError = document.getElementById('pwConfirmError');
      const form = document.querySelector('form[action="{{ route('password.update') }}"]');
        if(!pw || !pop) return;
        const evalRules = (v)=>({ len: v.length>=12, lower: /[a-z]/.test(v), upper: /[A-Z]/.test(v), num: /\d/.test(v), special: /[@#$%]/.test(v) });
        function paint(){
          const v = pw.value || '';
          if(v.length>0){ pop.style.display='block'; pop.setAttribute('aria-hidden','false'); }
          else { pop.style.display='none'; pop.setAttribute('aria-hidden','true'); }
          const r = evalRules(v);
          Object.keys(r).forEach(k=>{ const li = pop.querySelector(`li[data-rule="${k}"]`); if(li) li.classList.toggle('ok', !!r[k]); });
          // If all rules are satisfied while typing, hide any prior requirement error
          const allOk = r.len && r.lower && r.upper && r.num && r.special;
          if(allOk && pwError){ pwError.textContent=''; pwError.classList.add('hidden'); }
          if(confirmPw && confirmPw.value.length>0){ match(); }
        }
        function match(){
          if(!confirmPw || !matchMsg) return;
          if(confirmPw.value.length===0){
            matchMsg.textContent = 'Passwords do not match';
            matchMsg.className = 'field-error hidden';
            confirmPw.classList.remove('input-invalid');
            return;
          }
          if(confirmPw.value!==pw.value){
            matchMsg.textContent = 'Passwords do not match';
            matchMsg.className = 'field-error';
            confirmPw.classList.add('input-invalid');
          } else {
            // Show green confirmation text without moving layout
            matchMsg.textContent = 'Passwords match';
            matchMsg.className = 'field-error field-ok';
            confirmPw.classList.remove('input-invalid');
          }
        }
        pw.addEventListener('input', paint);
        pw.addEventListener('blur', ()=>{ pop.style.display='none'; pop.setAttribute('aria-hidden','true'); });
        if(confirmPw){
          ['input','keyup','change','blur'].forEach(evt=> confirmPw.addEventListener(evt, match));
          // Initial check in case browser autofills
          setTimeout(match, 0);
        }

        // Replace browser tooltips with inline errors
        if(form){
          form.addEventListener('submit', function(e){
            let hasError = false;
            // Clear client error placeholders first
            if(pwError){ pwError.textContent=''; pwError.classList.add('hidden'); }
            if(pwConfirmError){ pwConfirmError.textContent=''; pwConfirmError.classList.add('hidden'); }
            if(matchMsg){ matchMsg.classList.add('hidden'); }

            // Required checks
            if((pw.value||'').trim()===''){
              if(pwError){ pwError.textContent = 'Please enter a new password.'; pwError.classList.remove('hidden'); }
              pw.focus();
              hasError = true;
            }
            if((confirmPw.value||'').trim()===''){
              if(pwConfirmError){ pwConfirmError.textContent = 'Please confirm your new password.'; pwConfirmError.classList.remove('hidden'); }
              if(!hasError) confirmPw.focus();
              hasError = true;
            }
            // Requirements check (only if pw present)
            if(!hasError){
              const r = evalRules(pw.value||'');
              const allOk = r.len && r.lower && r.upper && r.num && r.special;
              if(!allOk){
                if(pwError){ pwError.textContent = 'Please meet all password requirements.'; pwError.classList.remove('hidden'); }
                pw.focus();
                hasError = true;
              }
            }
            // Match check
            if(!hasError && confirmPw.value !== pw.value){
              if(matchMsg){ matchMsg.classList.remove('hidden'); }
              if(confirmPw){ confirmPw.classList.add('input-invalid'); }
              if(pwConfirmError){ pwConfirmError.textContent = ''; pwConfirmError.classList.add('hidden'); }
              confirmPw.focus();
              hasError = true;
            } else if(matchMsg && confirmPw.value === pw.value){
              matchMsg.textContent = 'Passwords match';
              matchMsg.className = 'field-error field-ok';
              if(confirmPw){ confirmPw.classList.remove('input-invalid'); }
            }
            if(hasError){ e.preventDefault(); e.stopPropagation(); }
          }, true);
        }
      })();

  document.querySelectorAll('.toggle-password').forEach(function(icon) {
    icon.addEventListener('click', function () {
        const inputId = this.getAttribute('data-target');
        const passwordInput = document.getElementById(inputId);
        if (passwordInput.type === "password") {
            passwordInput.type = "text";
            this.classList.replace("bx-hide", "bx-show");
        } else {
            passwordInput.type = "password";
            this.classList.replace("bx-show", "bx-hide");
        }
    });
});
  </script>
</body>
</html>
<script>
// Prevent copying the new password and pasting into confirmation without altering placeholders
(function() {
  const newPwd = document.getElementById('new-password');
  const confirmPwd = document.getElementById('confirm-password');
  if(!newPwd || !confirmPwd) return;
  ['copy','cut'].forEach(evt => newPwd.addEventListener(evt, e => e.preventDefault()));
  ['paste','drop'].forEach(evt => confirmPwd.addEventListener(evt, e => { e.preventDefault(); confirmPwd.value=''; }));
  newPwd.addEventListener('dragstart', e => e.preventDefault());
  confirmPwd.addEventListener('contextmenu', e => e.preventDefault());
})();
</script>
<script src="{{ asset('js/errors-auto-dismiss.js') }}"></script>
