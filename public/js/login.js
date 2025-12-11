// Password visibility toggle for the unified login form.
(function () {
    const btn = document.getElementById('toggle-password-btn');
    const pwd = document.getElementById('password');
    if (!btn || !pwd) return;
    btn.addEventListener('click', function () {
        const showing = pwd.type === 'text';
        pwd.type = showing ? 'password' : 'text';
        const icon = btn.querySelector('i');
        if (icon) {
            icon.classList.remove('bx-hide', 'bx-show');
            icon.classList.add(showing ? 'bx-hide' : 'bx-show');
        }
        btn.setAttribute('aria-pressed', String(!showing));
        btn.setAttribute('aria-label', showing ? 'Show password' : 'Hide password');
    });
})();

// Floating label support for unified login inputs.
(function () {
    const form = document.getElementById('unified-login-form');
    if (!form) return;
    const inputs = form.querySelectorAll('.float-stack input');
    const apply = (el) => {
        if (el.value) el.classList.add('filled');
        else el.classList.remove('filled');
    };
    inputs.forEach((input) => {
        apply(input);
        ['input', 'change'].forEach((evt) => input.addEventListener(evt, () => apply(input)));
    });
    setTimeout(() => inputs.forEach(apply), 120);
    document.addEventListener('visibilitychange', () => {
        if (!document.hidden) {
            inputs.forEach(apply);
        }
    });
})();

// Loading overlay and submit throttle.
(function () {
    const form = document.getElementById('unified-login-form');
    const overlay = document.getElementById('authLoading');
    if (!form || !overlay) return;
    const MIN_LOADING_MS = 2000;
    form.addEventListener('submit', function (e) {
        if (form.dataset.submitting === '1') return;
        if (!form.checkValidity()) return; // let browser show validation UI
        e.preventDefault();
        overlay.classList.add('active');
        form.dataset.submitting = '1';
        setTimeout(() => form.submit(), MIN_LOADING_MS);
    });
    window.addEventListener('pageshow', (evt) => {
        if (evt.persisted) {
            overlay.classList.remove('active');
            form.dataset.submitting = '0';
        }
    });
})();

// Remember username locally (independent of server remember cookie).
(function () {
    const form = document.getElementById('unified-login-form');
    const userInput = document.getElementById('username');
    const rememberBox = form ? form.querySelector('input[name="remember"]') : null;
    if (!form || !userInput || !rememberBox) return;
    const KEY = 'login_username';
    if (!userInput.value) {
        const stored = localStorage.getItem(KEY);
        if (stored) {
            userInput.value = stored;
            userInput.classList.add('filled');
            rememberBox.checked = true;
        }
    }
    form.addEventListener('submit', () => {
        const value = userInput.value.trim();
        if (rememberBox.checked && value) {
            localStorage.setItem(KEY, value);
        } else {
            localStorage.removeItem(KEY);
        }
    });
})();

// Live lock countdown for unified login.
(function () {
    const row = document.querySelector('.options-row[data-lock-until]');
    if (!row) return;
    const lockAttr = parseInt(row.getAttribute('data-lock-until'), 10);
    if (!lockAttr) return;
    const btn = document.getElementById('unified-login-btn');
    let errBox = row.querySelector('.login-error');
    const placeholder = row.querySelector('.login-error-placeholder');
    if (!errBox && placeholder) {
        errBox = document.createElement('div');
        errBox.className = 'login-error';
        placeholder.replaceWith(errBox);
    }
    if (btn) btn.disabled = true;
    const tick = () => {
        const remain = lockAttr - Math.floor(Date.now() / 1000);
        if (remain > 0) {
            if (errBox) {
                errBox.textContent = 'Too many attempts. Try again in ' + remain + 's.';
            }
        } else {
            if (errBox) {
                errBox.textContent = '';
                errBox.classList.remove('login-error');
            }
            if (btn) btn.disabled = false;
            clearInterval(timer);
        }
    };
    const timer = setInterval(tick, 1000);
    tick();
})();

function toggleChat() {
    const chat = document.getElementById('chatOverlay');
    chat.classList.toggle('open');
}

function sendMessage() {
    const input = document.getElementById('userInput');
    const message = input.value.trim();
    if (message === '') return;

    const chatBody = document.getElementById('chatBody');

    // Add user's message
    const userMsg = document.createElement('div');
    userMsg.className = 'message user';
    userMsg.textContent = message;
    chatBody.appendChild(userMsg);

    // Fake bot reply
    const botMsg = document.createElement('div');
    botMsg.className = 'message bot';
    botMsg.textContent = 'Thank you for your message. I’ll get back to you soon!';
    setTimeout(() => {
        chatBody.appendChild(botMsg);
        chatBody.scrollTop = chatBody.scrollHeight;
    }, 600);

    input.value = '';
    chatBody.scrollTop = chatBody.scrollHeight;
}

function showError(message) {
    let errorDiv = document.createElement('div');
    errorDiv.className = 'alert alert-danger';
    errorDiv.textContent = message;
    document.body.prepend(errorDiv);
    setTimeout(() => errorDiv.remove(), 3000);
}
// Usage: showError('Incorrect Student ID or Password.');

// Numeric-only enforcement (for ID fields). Applies to any input with class 'numeric-only'.
(function () {
    const inputs = document.querySelectorAll('input.numeric-only');
    if (!inputs.length) return;
    const allowedControl = ['Backspace', 'Delete', 'ArrowLeft', 'ArrowRight', 'Tab', 'Home', 'End'];
    inputs.forEach((inp) => {
        // Block non-digit key presses (except control keys)
        inp.addEventListener('keydown', (e) => {
            if (allowedControl.includes(e.key) || e.ctrlKey || e.metaKey) return;
            if (e.key === 'Enter') return; // allow submit
            if (/^[0-9]$/.test(e.key)) return;
            e.preventDefault();
        });
        // Sanitize on input (covers paste, drag-drop, autofill anomalies)
        inp.addEventListener('input', () => {
            const max = inp.getAttribute('maxlength')
                ? parseInt(inp.getAttribute('maxlength'), 10)
                : null;
            let v = inp.value.replace(/\D+/g, '');
            if (max) v = v.slice(0, max);
            if (inp.value !== v) inp.value = v;
            // Maintain filled class for floating label consistency
            if (v) inp.classList.add('filled');
            else inp.classList.remove('filled');
        });
        // Initial clean (in case old stored value has stray chars)
        inp.value = inp.value.replace(/\D+/g, '');
    });
})();

// Auto-dismiss non-lockout login error messages after 5 seconds.
// Criteria: target elements with .login-error that contain text not including 'Too many attempts'.
// We don't remove lockout messages because they are replaced dynamically by countdown scripts.
(function () {
    const DISMISS_MS = 5000;
    // Use a slight delay so server-rendered errors are in DOM, and countdown scripts (if any) can attach.
    window.addEventListener('DOMContentLoaded', () => {
        const nodes = document.querySelectorAll('.login-error');
        if (!nodes.length) return;
        nodes.forEach((node) => {
            // If this element will be updated by a lock countdown (contains substring or data-lock present in parent) skip
            const parent = node.closest('.options-row');
            const isLocked =
                parent &&
                parent.hasAttribute('data-lock-until') &&
                parent.getAttribute('data-lock-until');
            const text = (node.textContent || '').toLowerCase();
            if (isLocked || text.includes('too many attempts')) return; // don't auto-hide lockout countdown
            if (!text.trim()) return; // nothing to hide
            setTimeout(() => {
                // Add fading class; keep element height so layout (Remember me position) doesn't shift.
                node.classList.add('fade-out-login-error');
                // After fade completes, clear text but keep an invisible placeholder character to preserve height precisely
                setTimeout(() => {
                    if (node.classList.contains('fade-out-login-error')) {
                        node.textContent = '\u200B'; // zero-width space retains height
                    }
                }, 400);
            }, DISMISS_MS);
        });
    });
})();
