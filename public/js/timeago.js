// Lightweight timeago updater (no deps)
// Usage: <span class="notification-time" data-timeago data-ts="2025-10-03T06:12:00Z"></span>
// It updates innerText automatically: Just now, 10s ago, 1 min ago, 5 mins ago, 1 hr ago, 2 hrs ago, 1 day ago, 3 days ago
(function () {
    const SECOND = 1000;
    const MINUTE = 60 * SECOND;
    const HOUR = 60 * MINUTE;
    const DAY = 24 * HOUR;

    function format(ts) {
        if (!ts) return '';
        const d = new Date(ts);
        if (isNaN(d.getTime())) return '';
        const now = new Date();
        const diff = now - d;
        if (diff < 0) return 'Just now';
        const s = Math.floor(diff / SECOND);
        if (s < 10) return 'Just now';
        if (s < 60) return `${s}s ago`;
        const m = Math.floor(diff / MINUTE);
        if (m < 60) return `${m} ${m === 1 ? 'min' : 'mins'} ago`;
        const h = Math.floor(diff / HOUR);
        if (h < 24) return `${h === 1 ? '1 hr' : h + ' hrs'} ago`;
        const dys = Math.floor(diff / DAY);
        return `${dys} ${dys === 1 ? 'day' : 'days'} ago`;
    }

    function tick() {
        const nodes = document.querySelectorAll('[data-timeago]');
        nodes.forEach((el) => {
            const ts = el.getAttribute('data-ts') || el.getAttribute('datetime') || el.textContent;
            const txt = format(ts);
            if (txt) el.textContent = txt;
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => {
            tick();
            setInterval(tick, 1000);
        });
    } else {
        tick();
        setInterval(tick, 1000);
    }
})();
