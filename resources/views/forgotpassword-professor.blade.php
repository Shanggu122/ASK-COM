<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Forgot Password</title>
  <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
  <link rel="stylesheet" href="{{ asset('css/fp.css') }}">
</head>
<body>
  <div class="container">
    <div class="left-panel">
      <img src="{{ asset('images/CCIT_logo2.png') }}" alt="Adamson Logo" class="left-logo"/>
      <h2 class="college-title">
        <div class="adamson-uni">Adamson University</div>
        <div class="college-bottom">College of Computing and Information Technology</div>
      </h2>
    </div>
    <div class="right-panel">
      <div class="brand">
        <img src="{{ asset('images/ASCCITlogo.png') }}" alt="ASCC-IT Logo" class="small-logo">
        <h1>ASCC-IT</h1>
        <p><em>Catalyzing Change Innovating for Tomorrow</em></p>
      </div>
  <form action="{{ route('verify-otp') }}" method="POST" id="fp-form-prof">
        @csrf
        <div class="input-group">
          <input type="email" name="email" placeholder="example@adamson.edu.ph" required>
        </div>
        <button type="submit" class="login-btn" id="fp-send-btn-prof">Send OTP</button>
      </form>
    </div>
  </div>
  <div class="otp-overlay" id="otpOverlay" aria-hidden="true">
    <div class="otp-modal">
      <div class="otp-spinner"></div>
      <div class="otp-text">Sending OTP...</div>
    </div>
  </div>
  <script>
    (function(){
      const form = document.getElementById('fp-form-prof');
      const btn = document.getElementById('fp-send-btn-prof');
      const overlay = document.getElementById('otpOverlay');
      if(!form || !btn || !overlay) return;
      let submitting=false;
      form.addEventListener('submit', e=>{
        if(submitting){ e.preventDefault(); return; }
        submitting=true;
        btn.disabled=true;
        overlay.classList.add('active');
        overlay.setAttribute('aria-hidden','false');
      });
    })();
  </script>
  <script src="{{ asset('js/errors-auto-dismiss.js') }}"></script>
</body>
</html> 