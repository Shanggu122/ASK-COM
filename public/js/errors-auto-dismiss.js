// Auto-dismiss field, success, and generic login messages after a delay (default 5s)
// Preserves layout (keeps element height) and skips dynamic countdown / OTP resend messages.
(function () {
    const DISMISS_MS = 5000;
    function shouldSkip(el) {
        if (!el) return true;
        const txt = (el.textContent || '').toLowerCase();
        if (!txt.trim()) return true; // empty
        // Skip messages that involve cooldowns or attempts
        if (txt.includes('too many attempts')) return true;
        if (txt.includes('wait') && txt.includes('s)')) return true; // likely resend countdown
        return false;
    }
    function schedule(el) {
        if (shouldSkip(el)) return;
        setTimeout(() => {
            el.classList.add('fade-out-field-error');
            setTimeout(() => {
                if (el.classList.contains('fade-out-field-error')) el.textContent = '\u200B';
            }, 420);
        }, DISMISS_MS);
    }
    window.addEventListener('DOMContentLoaded', () => {
        document.querySelectorAll('.field-error, .login-error, .field-success').forEach(schedule);
    });
})();
