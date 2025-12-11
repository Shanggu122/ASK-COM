<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Verify OTP</title>
  <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
  <link rel="stylesheet" href="{{ asset('css/verify.css') }}">
  <link rel="stylesheet" href="{{ asset('css/toast.css') }}">
</head>
<body>
  <div class="container">
    <!-- Left Panel -->
    <div class="left-panel">
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
  <a href="{{ route('forgotpassword', ['role'=>$roleParam]) }}" class="back-btn">
          <i class='bx bx-chevron-left'></i>
        </a>
        <span class="fp-title">Email Verification</span>
      </div>
  <form id="otpForm" action="{{ route('otp.verify') }}" method="POST" style="margin-top:0;">
        @csrf
        <div class="input-group">
          <h3 class="fp-instruction">Enter Verification Code</h3>
          <div class="otp-boxes" id="otpBoxes" aria-label="4 digit code" role="group">
            <input type="text" inputmode="numeric" pattern="[0-9]*" maxlength="1" class="otp-digit" autocomplete="one-time-code" aria-label="Digit 1">
            <input type="text" inputmode="numeric" pattern="[0-9]*" maxlength="1" class="otp-digit" aria-label="Digit 2">
            <input type="text" inputmode="numeric" pattern="[0-9]*" maxlength="1" class="otp-digit" aria-label="Digit 3">
            <input type="text" inputmode="numeric" pattern="[0-9]*" maxlength="1" class="otp-digit" aria-label="Digit 4">
          </div>
          <input type="hidden" name="otp" id="otpHidden" required>
          @error('otp')<div class="field-error">{{ $message }}</div>@enderror
        </div>
        <button type="submit" class="login-btn" style="margin-top:10px;">Verify OTP</button>
    @php
      $cooldownSeconds = 20;
      $remaining = null;
      if(session()->has('otp_last_resend_at')){
        $raw = session('otp_last_resend_at');
        if($raw instanceof \Carbon\Carbon){
          // Legacy value before we switched to unix timestamp
            $lastUnix = $raw->getTimestamp();
            session(['otp_last_resend_at' => $lastUnix]); // normalize
        } else {
            $lastUnix = (int) $raw;
        }
        $elapsed = time() - $lastUnix; // seconds since last send
        if($elapsed < $cooldownSeconds){
          $remaining = $cooldownSeconds - $elapsed;
        } else {
          $remaining = 0;
        }
      }
    @endphp
    <div style="margin-top:14px;text-align:center;font-size:13px;" id="resendWrapper">
      Didn't get the code? <a href="{{ route('otp.resend', ['role'=>$roleParam]) }}" id="resendLink" style="color:#0d5c46;font-weight:600;{{ ($remaining && $remaining>0)?'pointer-events:none;opacity:0.45;':'' }}">Resend OTP</a>
      <span id="resendCountdown" style="margin-left:6px;color:#555;">
        @if($remaining!==null && $remaining>0)
          (wait {{ $remaining }}s)
        @endif
      </span>
    </div>
      </form>
    </div>
  </div>
  @include('partials.toast')
  <div class="auth-loading-overlay" id="verifyLoading">
    <div class="auth-loading-spinner"></div>
    <div class="auth-loading-text">Verifying code...</div>
  </div>
  <script>
    // Resend OTP cooldown handler
    (function(){
      const link = document.getElementById('resendLink');
      const countdownEl = document.getElementById('resendCountdown');
      if(!link || !countdownEl) return;
      const COOLDOWN = 20; // seconds (must match backend)
      let timer = null;
      function render(rem){
        if(rem > 0){
          countdownEl.textContent = `(wait ${rem}s)`;
        } else {
          countdownEl.textContent = '';
        }
      }
      function start(rem){
        link.style.pointerEvents='none';
        link.style.opacity='0.45';
        render(rem);
        timer = setInterval(()=>{
          rem--;
          if(rem <= 0){
            clearInterval(timer);
            link.style.pointerEvents='';
            link.style.opacity='';
            render(0);
          } else {
            render(rem);
          }
        },1000);
      }
      // Initialize from existing text if present
      const match = countdownEl.textContent.match(/wait\s+(\d+)s/i);
      if(match){
        const startRemain = parseInt(match[1],10);
        if(startRemain>0) start(startRemain);
      }
      link.addEventListener('click', (e)=>{
        if(link.dataset.resending==='1') { e.preventDefault(); return; }
        // Immediately lock to stop spam before navigation
        link.dataset.resending='1';
        link.style.pointerEvents='none';
        link.style.opacity='0.45';
        if(!timer) start(COOLDOWN);
        // Optional: if user has JS disabled this won't run anyway; for JS case, allow normal navigation.
      });
    })();

    // Segmented OTP input logic
    (function(){
      const digitInputs = Array.from(document.querySelectorAll('.otp-digit'));
      if(!digitInputs.length) return;
      const hidden = document.getElementById('otpHidden');
      const form = document.getElementById('otpForm');
      const overlay = document.getElementById('verifyLoading');
      const MAX_LEN = 4;
      const MIN_LOADING_MS = 2000; // keep overlay visible for ~2s per user request
      let submitting = false;

      function triggerSubmit(){
        if(submitting) return; // guard double
        submitting = true;
        if(overlay) overlay.classList.add('active');
        setTimeout(()=> form.submit(), MIN_LOADING_MS);
      }

      function updateFilledClasses(){
        digitInputs.forEach(inp => inp.classList.toggle('filled', !!inp.value));
      }
      function rebuild(){
        hidden.value = digitInputs.map(i=>i.value).join('');
        updateFilledClasses();
        if(hidden.value.length === MAX_LEN){
          // full code entered automatically -> trigger delayed submit
          triggerSubmit();
        }
      }
      function vibrate(pattern){
        if(navigator.vibrate){
          try { navigator.vibrate(pattern); } catch(_) {}
        }
      }
      digitInputs.forEach((inp, idx) => {
        inp.addEventListener('input', () => {
          const v = inp.value.replace(/\D/g,'');
          inp.value = v.slice(0,1);
          if(v && idx < digitInputs.length - 1){
            digitInputs[idx+1].focus();
          }
          rebuild();
        });
        inp.addEventListener('keydown', e => {
          if(e.key === 'Backspace' && inp.value === '' && idx > 0){
            digitInputs[idx-1].focus();
          } else if(e.key === 'ArrowLeft' && idx > 0){
            digitInputs[idx-1].focus(); e.preventDefault();
          } else if(e.key === 'ArrowRight' && idx < digitInputs.length -1){
            digitInputs[idx+1].focus(); e.preventDefault();
          }
        });
        inp.addEventListener('paste', e => {
          e.preventDefault();
            const paste = (e.clipboardData.getData('text') || '').replace(/\D/g,'');
            if(!paste) return;
            if(paste.length !== MAX_LEN){
              vibrate([20,30,20]);
              inp.classList.add('error-shake');
              setTimeout(()=> inp.classList.remove('error-shake'), 400);
            }
            for(let i=0;i<MAX_LEN;i++){
              digitInputs[i].value = paste[i] || '';
            }
            digitInputs[Math.min(paste.length-1, MAX_LEN-1)].focus();
            rebuild();
        });
      });
      // Hook manual form submit (button) to show overlay
      if(form){
        form.addEventListener('submit', (e)=>{
          // Always prevent immediate submit to enforce minimum overlay duration
          e.preventDefault();
          triggerSubmit();
        });
      }

      // Initial state (autofill) & focus
      updateFilledClasses();
      setTimeout(()=>{ try { digitInputs[0].focus(); } catch(_){} }, 120);
    })();
  </script>
  <script src="{{ asset('js/errors-auto-dismiss.js') }}"></script>
</body>
</html>
