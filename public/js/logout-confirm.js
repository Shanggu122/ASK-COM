(function () {
    const guardCopy = {
        web: {
            title: 'Sign out of the student portal?',
            body: "You're about to sign out from the student portal. Unsaved changes will be lost. Do you want to continue?",
        },
        professor: {
            title: 'Sign out of the professor portal?',
            body: "You're about to sign out from the professor portal. Please make sure your work is saved before you continue.",
        },
        admin: {
            title: 'Sign out of the admin portal?',
            body: "You're about to sign out from the admin portal. Are you sure you want to proceed?",
        },
    };

    function ready(callback) {
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', callback, { once: true });
        } else {
            callback();
        }
    }

    function buildOverlay() {
        let overlay = document.getElementById('logoutConfirmOverlay');
        if (overlay) {
            return overlay;
        }

        overlay = document.createElement('div');
        overlay.id = 'logoutConfirmOverlay';
        overlay.className = 'logout-confirm-overlay';
        overlay.innerHTML = [
            '<div class="logout-confirm-modal" role="dialog" aria-modal="true" aria-labelledby="logoutConfirmTitle" aria-describedby="logoutConfirmBody">',
            '<div class="logout-confirm-header">',
            '<i class="bx bx-log-out-circle" aria-hidden="true"></i>',
            '<span id="logoutConfirmTitle" class="logout-confirm-title">Sign out?</span>',
            '</div>',
            '<div class="logout-confirm-body" id="logoutConfirmBody">Are you sure you want to sign out?</div>',
            '<div class="logout-confirm-actions">',
            '<button type="button" class="logout-confirm-cancel" data-logout-cancel>Stay signed in</button>',
            '<button type="button" class="logout-confirm-confirm" data-logout-confirm>Sign out</button>',
            '</div>',
            '</div>',
        ].join('');
        document.body.appendChild(overlay);
        return overlay;
    }

    ready(function () {
        const triggers = Array.from(document.querySelectorAll('[data-logout-guard]'));
        if (!triggers.length) {
            return;
        }

        const overlay = buildOverlay();
        const modal = overlay.querySelector('.logout-confirm-modal');
        const titleEl = overlay.querySelector('#logoutConfirmTitle');
        const bodyEl = overlay.querySelector('#logoutConfirmBody');
        const cancelBtn = overlay.querySelector('[data-logout-cancel]');
        const confirmBtn = overlay.querySelector('[data-logout-confirm]');
        let resolveAction = null;
        let activeTrigger = null;

        function closeOverlay() {
            overlay.classList.remove('active');
            modal.setAttribute('aria-hidden', 'true');
            overlay.removeAttribute('data-active-guard');
            resolveAction = null;
            activeTrigger = null;
        }

        function openOverlay(trigger, action) {
            const guard = trigger.getAttribute('data-logout-guard') || 'web';
            const copy = guardCopy[guard] || guardCopy.web;
            titleEl.textContent = copy.title;
            bodyEl.textContent = copy.body;
            overlay.setAttribute('data-active-guard', guard);
            resolveAction = action;
            activeTrigger = trigger;
            overlay.classList.add('active');
            modal.removeAttribute('aria-hidden');
            window.setTimeout(function () {
                confirmBtn.focus();
            }, 10);
        }

        triggers.forEach(function (trigger) {
            const tagName = trigger.tagName.toLowerCase();
            if (tagName === 'a') {
                trigger.addEventListener('click', function (event) {
                    event.preventDefault();
                    const href = trigger.getAttribute('href');
                    openOverlay(trigger, function () {
                        window.location.href = href;
                    });
                });
            } else if (tagName === 'form') {
                trigger.addEventListener('submit', function (event) {
                    event.preventDefault();
                    openOverlay(trigger, function () {
                        trigger.submit();
                    });
                });
            }
        });

        cancelBtn.addEventListener('click', function () {
            closeOverlay();
            if (activeTrigger) {
                const tag = activeTrigger.tagName.toLowerCase();
                if (tag === 'a') {
                    activeTrigger.focus();
                }
            }
        });

        confirmBtn.addEventListener('click', function () {
            if (typeof resolveAction === 'function') {
                const action = resolveAction;
                closeOverlay();
                action();
            }
        });

        overlay.addEventListener('click', function (event) {
            if (event.target === overlay) {
                closeOverlay();
            }
        });

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape' && overlay.classList.contains('active')) {
                closeOverlay();
            }
        });
    });
})();
