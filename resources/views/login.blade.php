<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>ASCC-IT Login</title>
  <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
  <link rel="stylesheet" href="{{ asset('css/login.css') }}">
  <link rel="stylesheet" href="{{ asset('css/toast.css') }}">
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
      </div>
      <p class="brand-slogan">
        <em><b>C</b>atalyzing <b>C</b>hange <b>I</b>nnovating for <b>T</b>omorrow</em>
      </p>
      <form action="{{ route('login.submit') }}" method="post" id="unified-login-form" autocomplete="on">
        @csrf
        <div class="input-group float-stack">
          <input
            type="text"
            id="username"
            name="username"
            placeholder=" "
            value="{{ old('username') }}"
            required
            maxlength="32"
            autocomplete="username"
            class="{{ $errors->has('username') ? 'input-error' : '' }}"
          >
          <label for="username">Username</label>
        </div>
        <div class="input-group password-group float-stack">
          <input
            type="password"
            id="password"
            name="password"
            placeholder=" "
            required
            autocomplete="current-password"
            class="{{ $errors->has('password') ? 'input-error' : '' }}"
          />
          <label for="password">Password</label>
          <button type="button" class="toggle-password" id="toggle-password-btn" aria-label="Show password" aria-pressed="false"><i class='bx bx-hide'></i></button>
        </div>
        <div class="options-row" data-lock-until="{{ session('lock_until') ?? '' }}">
          @php
            $messages = [];
            foreach (['login', 'username', 'password'] as $field) {
              if ($errors->has($field)) {
                $messages = array_merge($messages, $errors->get($field));
              }
            }
            if (session('error')) {
              $messages[] = session('error');
            }
          @endphp
          @if(session('status'))
            <div class="field-success" role="status" aria-live="polite">{{ session('status') }}</div>
          @elseif(count($messages))
            <div class="login-error" role="alert" aria-live="assertive">
              @foreach($messages as $index => $msg)
                @if($index) <br> @endif {{ $msg }}
              @endforeach
            </div>
          @else
            <span class="login-error-placeholder"></span>
          @endif
          <label class="remember-inline"><input type="checkbox" name="remember" value="1" {{ old('remember') ? 'checked' : '' }}> <span>Remember me</span></label>
        </div>
        <button type="submit" class="login-btn" id="unified-login-btn">Log In</button>
        <div class="below-actions">
          <a class="forgot-bottom" href="{{ route('forgotpassword') }}">Forgot Password?</a>
        </div>
      </form>
    </div>
  </div>

  <div class="auth-loading-overlay" id="authLoading">
    <div class="auth-loading-spinner"></div>
    <div class="auth-loading-text">Signing you in...</div>
  </div>

  <script src="{{ asset('js/login.js') }}"></script>
  <script src="{{ asset('js/errors-auto-dismiss.js') }}"></script>
  @include('partials.toast')
</body>
</html>

