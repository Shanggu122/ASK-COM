@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">{{ __('Reset Password') }}</div>

                <div class="card-body">
                    <form method="POST" action="{{ route('password.update') }}">
                        @csrf

                        <input type="hidden" name="token" value="{{ $token }}">

                        <div class="row mb-3">
                            <label for="email" class="col-md-4 col-form-label text-md-end">{{ __('Email Address') }}</label>

                            <div class="col-md-6">
                                <input id="email" type="email" class="form-control @error('email') is-invalid @enderror" name="email" value="{{ $email ?? old('email') }}" required autocomplete="email" autofocus>

                                @error('email')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                        </div>

                        <div class="row mb-3">
                            <label for="password" class="col-md-4 col-form-label text-md-end">{{ __('Password') }}</label>

                            <div class="col-md-6">
                                <div class="pw-field-wrapper">
                                    <input id="password" type="password" class="form-control @error('password') is-invalid @enderror" name="password" required autocomplete="new-password" aria-describedby="pwRules">

                                    <!-- Compact popover: only visible while typing -->
                                    <div id="pwRules" class="pw-rules-popover" role="status" aria-live="polite" aria-hidden="true">
                                        <ul class="pw-rules-list">
                                            <li data-rule="len">Password must be at least 12 characters long</li>
                                            <li data-rule="lower">Must include at least one lowercase letter</li>
                                            <li data-rule="upper">Must include at least one uppercase letter</li>
                                            <li data-rule="num">Must include at least one number</li>
                                            <li data-rule="special">Must include at least one special character (@, #, $, %)</li>
                                        </ul>
                                    </div>
                                </div>

                                @error('password')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                        </div>

                        <div class="row mb-3">
                            <label for="password-confirm" class="col-md-4 col-form-label text-md-end">{{ __('Confirm Password') }}</label>

                            <div class="col-md-6">
                                <input id="password-confirm" type="password" class="form-control" name="password_confirmation" required autocomplete="new-password" aria-describedby="pwMatchMsg">
                                <div id="pwMatchMsg" class="pw-match-msg" aria-live="polite" style="display:none;">Passwords do not match</div>
                            </div>
                        </div>

                        <div class="row mb-0">
                            <div class="col-md-6 offset-md-4">
                                <button type="submit" class="btn btn-primary">
                                    {{ __('Reset Password') }}
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
                <style>
                    /* Minimal, compact rules popover. Visible only while typing in the password field. */
                    .pw-field-wrapper { position: relative; }
                                .pw-rules-popover {
                        position: absolute;
                        top: 100%;
                        left: 0;
                                    margin-top: 0px; /* flush under input */
                        background: #ffffff;
                        color: #333;
                        border: 1px solid #dfe5e1;
                        border-radius: 8px;
                        box-shadow: 0 8px 20px rgba(0,0,0,0.12);
                        padding: 10px 12px;
                        width: 100%;
                        max-width: 420px;
                        z-index: 1050; /* above inputs */
                        display: none; /* hidden by default */
                    }
                    .pw-rules-popover:before {
                        content: '';
                        position: absolute;
                        top: -6px; left: 18px;
                        border-left: 6px solid transparent;
                        border-right: 6px solid transparent;
                        border-bottom: 6px solid #dfe5e1; /* small arrow border */
                    }
                    .pw-rules-popover:after {
                        content: '';
                        position: absolute;
                        top: -5px; left: 18px;
                        border-left: 5px solid transparent;
                        border-right: 5px solid transparent;
                        border-bottom: 5px solid #ffffff; /* arrow fill */
                    }
                    .pw-rules-list {
                        margin: 0;
                        padding-left: 18px;
                    }
                    .pw-rules-list li {
                        font-size: 12px;
                        line-height: 1.4;
                        margin: 4px 0;
                        color: #b23b3b; /* unmet: red */
                        list-style: none; /* we'll draw our own bullets */
                        position: relative;
                        padding-left: 18px;
                    }
                    .pw-rules-list li::before {
                        content: '✕';
                        position: absolute; left: 0; top: 0;
                        color: #c0392b;
                        font-weight: 700;
                    }
                    .pw-rules-list li.ok { color: #1e8f5a; }
                    .pw-rules-list li.ok::before { content: '✓'; color: #1e8f5a; }

                    .pw-match-msg {
                        margin-top: 6px;
                        font-size: 12px;
                        color: #c0392b;
                    }
                </style>

                <script>
                    (function(){
                        const pw = document.getElementById('password');
                        const confirmPw = document.getElementById('password-confirm');
                        const pop = document.getElementById('pwRules');
                        const matchMsg = document.getElementById('pwMatchMsg');
                        if(!pw || !pop) return;

                        function evaluateRules(v){
                            return {
                                len: v.length >= 12,
                                lower: /[a-z]/.test(v),
                                upper: /[A-Z]/.test(v),
                                num: /\d/.test(v),
                                special: /[@#$%]/.test(v)
                            };
                        }
                        function updatePopover(){
                            const v = pw.value || '';
                            // Show only when user has typed something
                            if(v.length > 0){ pop.style.display = 'block'; pop.setAttribute('aria-hidden','false'); }
                            else { pop.style.display = 'none'; pop.setAttribute('aria-hidden','true'); }
                            const rules = evaluateRules(v);
                            Object.keys(rules).forEach(key=>{
                                const li = pop.querySelector(`li[data-rule="${key}"]`);
                                if(li){ li.classList.toggle('ok', !!rules[key]); }
                            });
                            // Also refresh match message if confirm has content
                            if(confirmPw && confirmPw.value.length > 0){ updateMatchMsg(); }
                        }
                        function updateMatchMsg(){
                            if(!confirmPw || !matchMsg) return;
                            if(confirmPw.value.length === 0){ matchMsg.style.display = 'none'; return; }
                            if(confirmPw.value !== pw.value){ matchMsg.style.display = 'block'; matchMsg.textContent = 'Passwords do not match'; }
                            else { matchMsg.style.display = 'none'; }
                        }

                        pw.addEventListener('input', updatePopover);
                        pw.addEventListener('blur', ()=>{ pop.style.display='none'; pop.setAttribute('aria-hidden','true'); });
                        if(confirmPw){ confirmPw.addEventListener('input', updateMatchMsg); }
                    })();
                </script>
@endsection
