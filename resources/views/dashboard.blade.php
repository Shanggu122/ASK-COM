<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>Consultation Activity</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
  <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/pikaday/css/pikaday.css">
  <link rel="stylesheet" href="{{ asset('css/dashboard.css') }}">
  <link rel="stylesheet" href="{{ asset('css/notifications.css') }}">
  <link rel="stylesheet" href="{{ asset('css/legend.css') }}">
</head>
<body>
  @include('components.navbar')

  <div class="main-content">
    <div class="header">
      <h1>Consultation Activity</h1> <!-- Changed to a more descriptive title -->
    </div>
    <div class="flex-layout">
      <div class="calendar-box">
        <div class="calendar-wrapper-container">
          <input id="calendar" type="text" placeholder="Select Date" name="booking_date" required>
        </div>
        <!-- Collapsible legend (bottom-left FAB to avoid chatbot at bottom-right) -->
        <button id="legendToggle" class="legend-toggle" aria-haspopup="dialog" aria-controls="legendBackdrop" aria-label="View Legend" title="View Legend">
          <svg width="22" height="22" viewBox="0 0 24 24" aria-hidden="true" focusable="false" style="color:#fff">
            <path fill="currentColor" d="M12 2a10 10 0 1 0 0 20a10 10 0 0 0 0-20zm0 7a1.25 1.25 0 1 1 0-2.5a1.25 1.25 0 0 1 0 2.5zM11 11h2v6h-2z"/>
          </svg>
        </button>
        <div id="legendBackdrop" class="legend-backdrop" aria-hidden="true">
          <div class="legend-panel" role="dialog" aria-modal="true" aria-labelledby="legendTitle">
            <div class="legend-header">
              <h3 id="legendTitle">Legend</h3>
              <button id="legendClose" class="legend-close" aria-label="Close">✖</button>
            </div>
            <div class="legend-content">
              <div class="legend-section">
                <div class="legend-section-title">Consultation Status</div>
                <div class="legend-grid">
                  <div class="legend-item"><span class="legend-swatch swatch-pending"></span>Pending <i class='bx bx-time legend-icon' aria-hidden="true"></i></div>
                  <div class="legend-item"><span class="legend-swatch swatch-approved"></span>Approved <i class='bx bx-check-circle legend-icon' aria-hidden="true"></i></div>
                  <div class="legend-item"><span class="legend-swatch swatch-completed"></span>Completed <i class='bx bx-badge-check legend-icon' aria-hidden="true"></i></div>
                  <div class="legend-item"><span class="legend-swatch swatch-rescheduled"></span>Rescheduled <i class='bx bx-calendar-edit legend-icon' aria-hidden="true"></i></div>
                  <div class="legend-item"><span class="legend-swatch swatch-suspended"></span>Suspension of class <i class='bx bx-block legend-icon' aria-hidden="true"></i></div>
                </div>
              </div>
              <div class="legend-section">
                <div class="legend-section-title">Day Types</div>
                <div class="legend-grid">
                  <div class="legend-item"><span class="legend-swatch swatch-today"></span>Today <i class='bx bx-sun legend-icon' aria-hidden="true"></i></div>
                  <div class="legend-item"><span class="legend-swatch swatch-online"></span>Online Day <i class='bx bx-video legend-icon' aria-hidden="true"></i></div>
                  <div class="legend-item"><span class="legend-swatch swatch-forced"></span>Forced Online <i class='bx bx-switch legend-icon' aria-hidden="true"></i></div>
                  <div class="legend-item"><span class="legend-swatch swatch-holiday"></span>Holiday <i class='bx bx-party legend-icon' aria-hidden="true"></i></div>
                  <div class="legend-item"><span class="legend-swatch swatch-leave"></span>Leave Day <i class='bx bx-coffee legend-icon' aria-hidden="true"></i></div>
                  <div class="legend-item"><span class="legend-swatch swatch-endyear"></span>End of School Year <i class='bx bx-calendar-x legend-icon' aria-hidden="true"></i></div>
                  
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
      <div class="box">
        <div class="inbox-notifications">
          <div class="inbox-header">
            <h3>Notifications</h3>
            <div class="inbox-actions">
              <button id="mark-all-read" class="mark-all-btn" title="Mark all as read">
                <i class='bx bx-check-double'></i>
              </button>
              <span id="unread-count" class="unread-count">0</span>
            </div>
          </div>
          <div class="inbox-content" id="inbox-content">
            <div class="loading-notifications">
              <i class='bx bx-loader-alt bx-spin'></i>
              <span>Loading notifications...</span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <button class="chat-button" onclick="toggleChat()">
      <i class='bx bxs-message-rounded-dots'></i>
      Click to chat with me!
    </button>

    <div class="chat-overlay" id="chatOverlay">
      <div class="chat-header">
        <span>ASK-COM</span>
        <button class="close-btn" onclick="toggleChat()">×</button>
      </div>
      <div class="chat-body" id="chatBody">
        <div class="message bot">Hi! How can I help you today?</div>
        <div id="chatBox"></div>
      </div>
      <div id="quickReplies" class="quick-replies">
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

      <form id="chatForm">
        <input type="text" id="message" placeholder="Type your message" required>
        <button type="submit">Send</button>
      </form>
    </div>
    <!-- Consultation Tooltip (student) -->
    <div id="consultationTooltip" style="display:none; position:absolute; z-index:9999; background:#fff; border:1px solid #e1e5e9; border-radius:8px; padding:12px; max-width:320px; max-height:400px; overflow-y:auto; box-shadow:0 4px 12px rgba(0,0,0,0.15); font-family:'Poppins',sans-serif; line-height:1.4;"></div>
  </div>

  <!-- Reschedule decision modal (student) -->
  <div id="rescheduleModal" style="display:none; position:fixed; inset:0; z-index:1500000; background:rgba(0,0,0,0.4);">
    <div style="position:absolute; left:50%; top:50%; transform:translate(-50%,-50%); background:#fff; border-radius:10px; width:min(520px, 92vw); box-shadow:0 10px 30px rgba(0,0,0,0.2); font-family:'Poppins',sans-serif;">
      <div style="padding:14px 16px; border-bottom:1px solid #eee; display:flex; align-items:center; justify-content:space-between;">
        <h3 style="margin:0; font-size:18px; color:#12372a;">Consultation Rescheduled</h3>
        <button id="resModalClose" style="border:none; background:#f3f4f6; color:#374151; padding:6px 10px; border-radius:6px; cursor:pointer;">✖</button>
      </div>
      <div style="padding:16px;">
        <div style="font-size:14px; color:#374151; margin-bottom:8px;">Please review the updated consultation details:</div>
        <div id="resModalBody" style="background:#f9fafb; border:1px solid #e5e7eb; border-radius:8px; padding:12px; font-size:14px; color:#111827;"></div>
        <div id="resModalError" style="display:none; color:#b91c1c; font-size:13px; margin-top:8px;"></div>
      </div>
      <div style="display:flex; gap:8px; justify-content:flex-end; padding:12px 16px; border-top:1px solid #eee;">
        <button id="resModalCancelBtn" style="background:#b91c1c; color:#fff; border:none; padding:10px 14px; border-radius:8px; cursor:pointer;">Cancel consultation</button>
        <button id="resModalAcceptBtn" style="background:#047857; color:#fff; border:none; padding:10px 14px; border-radius:8px; cursor:pointer;">Accept reschedule</button>
      </div>
    </div>
  </div>

  <!-- Student confirmation dialog -->
  <div id="studentConfirmDialog" aria-hidden="true" style="display:none; position:fixed; inset:0; z-index:1600000; background:rgba(0,0,0,0.55); align-items:center; justify-content:center;">
    <div class="confirm-card">
      <div class="confirm-header">
        <i class='bx bx-help-circle' aria-hidden="true"></i>
        <span>Confirm Action</span>
      </div>
      <div class="confirm-body">
        <p id="confirmMessage">Are you sure you want to continue?</p>
      </div>
      <div class="confirm-actions">
        <button type="button" class="confirm-secondary" id="confirmCancelBtn">Keep consultation</button>
        <button type="button" class="confirm-primary" id="confirmProceedBtn">Cancel consultation</button>
      </div>
    </div>
  </div>

  <!-- Completion review modal (shared with conlog styling) -->
  <div class="completion-review-overlay" id="completionReviewOverlay" aria-hidden="true">
    <div class="completion-review-modal" role="dialog" aria-modal="true" aria-labelledby="completionReviewTitle">
      <div class="modal-header">
        <div class="title">
          <i class='bx bx-clipboard-check'></i>
          <span id="completionReviewTitle">Review completion request</span>
        </div>
        <button type="button" class="completion-review-close" id="completionReviewClose" aria-label="Close review modal">&times;</button>
      </div>
      <div class="modal-body">
        <div class="completion-review-remarks" id="completionReviewRemarks" style="display:none;">
          <strong>Professor remarks</strong>
          <p id="completionReviewRemarksText"></p>
        </div>
        <div id="completionReviewInfo" class="completion-review-meta"></div>
        <div class="completion-review-error" id="completionReviewError"></div>
      </div>
      <div class="completion-review-actions">
        <button type="button" class="btn-outline" id="completionReviewDecline">Needs revision</button>
        <button type="button" class="btn-solid" id="completionReviewApprove">Confirm completion</button>
      </div>
    </div>
  </div>

  <script src="{{ asset('js/dashboard.js') }}"></script>
  <script src="https://cdn.jsdelivr.net/npm/pikaday/pikaday.js"></script>
  <link rel="stylesheet" href="{{ asset('css/student-modal.css') }}">
  <script>
  // Mid-width (tablet/small desktop) notification panel toggle
  (function(){
    const notifPanelSelector = '.inbox-notifications';
    function applyNotifMode(){
      const w = window.innerWidth;
      const panel = document.querySelector(notifPanelSelector);
      const bell = document.getElementById('mobileNotificationBell');
      if(!panel) return;
    if(w <= 1450 && w >= 769){
        panel.style.display = 'none';
        if(bell){ bell.style.display = 'block'; bell.style.opacity = '1'; }
    } else if (w >= 1451) {
        panel.style.display = '';
        if(bell){ bell.style.display = 'none'; }
      } else { // real mobile keeps existing mobile styles
        if(bell){ bell.style.display = 'block'; }
      }
    }
    window.addEventListener('resize', applyNotifMode);
    document.addEventListener('DOMContentLoaded', applyNotifMode);
  })();

  // Legend panel interactions
  (function legendPanelInit(){
    const btn = document.getElementById('legendToggle');
    const backdrop = document.getElementById('legendBackdrop');
    const closeBtn = document.getElementById('legendClose');
    if(!btn || !backdrop) return;
    const open = () => {
      backdrop.classList.add('open');
      backdrop.setAttribute('aria-hidden','false');
      document.body.classList.add('legend-open');
    };
    const close = () => {
      backdrop.classList.remove('open');
      backdrop.setAttribute('aria-hidden','true');
      document.body.classList.remove('legend-open');
    };
    btn.addEventListener('click', open);
    closeBtn && closeBtn.addEventListener('click', close);
    backdrop.addEventListener('click', (e)=>{ if(e.target === backdrop) close(); });
    document.addEventListener('keydown', (e)=>{ if(e.key === 'Escape') close(); });
  })();
    
  const bookingMap = new Map();

  const completionReviewState = {
    bookingId: null,
    notificationId: null,
    pending: false,
  };

  function formatCompletionDate(iso){
    if(!iso) return '';
    const d = new Date(iso);
    if(Number.isNaN(d.getTime())) return '';
    return d.toLocaleString('en-US', { month:'short', day:'numeric', year:'numeric', hour:'numeric', minute:'2-digit' });
  }

  function populateCompletionModal(details){
    const overlay = document.getElementById('completionReviewOverlay');
    const remarksEl = document.getElementById('completionReviewRemarks');
    const remarksTextEl = document.getElementById('completionReviewRemarksText');
    const infoEl = document.getElementById('completionReviewInfo');
    const errorEl = document.getElementById('completionReviewError');
    const approveBtn = document.getElementById('completionReviewApprove');
    if(!overlay || !remarksEl || !infoEl || !errorEl){ return; }
    if(details.reason){
      remarksEl.style.display = 'block';
      if(remarksTextEl){ remarksTextEl.textContent = details.reason; }
    } else {
      remarksEl.style.display = 'none';
      if(remarksTextEl){ remarksTextEl.textContent = ''; }
    }
    const requested = formatCompletionDate(details.requestedAt);
    infoEl.textContent = requested ? `Requested on ${requested}${details.professor ? ` by ${details.professor}` : ''}.` : (details.professor ? `Professor ${details.professor} sent this request.` : '');
    errorEl.textContent = '';
    errorEl.style.display = 'none';
    overlay.style.display = 'flex';
    overlay.setAttribute('aria-hidden','false');
    setTimeout(()=>{ try{ approveBtn?.focus(); }catch(_){} }, 15);
  }

  function closeCompletionReview(){
    const overlay = document.getElementById('completionReviewOverlay');
    const errorEl = document.getElementById('completionReviewError');
    const approveBtn = document.getElementById('completionReviewApprove');
    const declineBtn = document.getElementById('completionReviewDecline');
    const closeBtn = document.getElementById('completionReviewClose');
    if(overlay){ overlay.style.display='none'; overlay.setAttribute('aria-hidden','true'); }
    if(errorEl){ errorEl.style.display='none'; errorEl.textContent=''; }
    approveBtn && (approveBtn.disabled = false);
    declineBtn && (declineBtn.disabled = false);
    closeBtn && (closeBtn.disabled = false);
    completionReviewState.bookingId = null;
    completionReviewState.notificationId = null;
    completionReviewState.pending = false;
  }

  function openCompletionReviewWithFetch(bookingId, notificationId){
    completionReviewState.bookingId = bookingId;
    completionReviewState.notificationId = notificationId || null;
    const overlay = document.getElementById('completionReviewOverlay');
    const infoEl = document.getElementById('completionReviewInfo');
    const remarksEl = document.getElementById('completionReviewRemarks');
    const remarksTextEl = document.getElementById('completionReviewRemarksText');
    const errorEl = document.getElementById('completionReviewError');
    if(overlay){ overlay.style.display='flex'; overlay.setAttribute('aria-hidden','false'); }
    if(infoEl){ infoEl.textContent = 'Loading details…'; }
    if(remarksEl){ remarksEl.style.display='none'; }
    if(remarksTextEl){ remarksTextEl.textContent=''; }
    if(errorEl){ errorEl.style.display='none'; errorEl.textContent=''; }
    fetch(`/api/student/consultation-details/${bookingId}`)
      .then(r=>r.json())
      .then(data=>{
        if(!data || !data.success){ throw new Error(data?.message || 'Unable to load details'); }
        const c = data.consultation || {};
        populateCompletionModal({
          professor: c.professor_name || '',
          reason: c.completion_reason || '',
          requestedAt: c.completion_requested_at || '',
        });
        if(notificationId){
          try { markNotificationAsRead(notificationId); } catch(_){ }
        }
      })
      .catch(err=>{
        if(errorEl){ errorEl.textContent = err?.message || 'Unable to load completion details.'; errorEl.style.display='block'; }
      });
  }

  function submitCompletionDecision(decision){
    if(completionReviewState.pending || !completionReviewState.bookingId){ return; }
    const errorEl = document.getElementById('completionReviewError');
    const approveBtn = document.getElementById('completionReviewApprove');
    const declineBtn = document.getElementById('completionReviewDecline');
    const closeBtn = document.getElementById('completionReviewClose');
    completionReviewState.pending = true;
    approveBtn && (approveBtn.disabled = true);
    declineBtn && (declineBtn.disabled = true);
    closeBtn && (closeBtn.disabled = true);
    if(errorEl){ errorEl.style.display='none'; errorEl.textContent=''; }
    fetch('/api/consultations/update-status', {
      method:'POST',
      headers:{
        'Content-Type':'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
      },
      body: JSON.stringify({
        id: Number(completionReviewState.bookingId),
        status: decision
      })
    }).then(r=>r.json()).then(data=>{
      if(!data || !data.success){ throw new Error(data?.message || 'Request failed'); }
      closeCompletionReview();
      if(completionReviewState.notificationId){
        try { markNotificationAsRead(completionReviewState.notificationId); } catch(_){ }
      }
      loadNotifications();
      loadBookingData();
      if(typeof loadStudentDetails === 'function'){ loadStudentDetails(); }
    }).catch(err=>{
      if(errorEl){ errorEl.textContent = err?.message || 'Unable to submit your response.'; errorEl.style.display='block'; }
    }).finally(()=>{
      completionReviewState.pending = false;
      approveBtn && (approveBtn.disabled = false);
      declineBtn && (declineBtn.disabled = false);
      closeBtn && (closeBtn.disabled = false);
    });
  }

  document.getElementById('completionReviewApprove')?.addEventListener('click', ()=>submitCompletionDecision('completed'));
  document.getElementById('completionReviewDecline')?.addEventListener('click', ()=>submitCompletionDecision('completion_declined'));
  document.getElementById('completionReviewClose')?.addEventListener('click', closeCompletionReview);
  document.getElementById('completionReviewOverlay')?.addEventListener('click', (e)=>{
    if(e.target && e.target.id === 'completionReviewOverlay' && !completionReviewState.pending){ closeCompletionReview(); }
  });
  
  function loadBookingData() {
    fetch('/api/consul')
      .then(response => response.json())
      .then(data => {
        // Store previous booking map for comparison
        const previousBookings = new Map(bookingMap);
        bookingMap.clear(); // Clear existing data
        
        data.forEach(entry => {
          const statusLower = (entry.Status || '').toLowerCase();
          // Skip cancelled so student cells don't get marked as having consultations
          if (statusLower === 'cancelled') return;
          const date = new Date(entry.Booking_Date);
          const key = date.toDateString();
          bookingMap.set(key, statusLower);
        });
        
        // Only update calendar if there are actual changes
        let hasChanges = false;
        
        // Check for new or changed bookings
        for (const [dateStr, status] of bookingMap) {
          if (!previousBookings.has(dateStr) || previousBookings.get(dateStr) !== status) {
            hasChanges = true;
            break;
          }
        }
        
        // Check for removed bookings
        if (!hasChanges) {
          for (const [dateStr] of previousBookings) {
            if (!bookingMap.has(dateStr)) {
              hasChanges = true;
              break;
            }
          }
        }
        
        // Only update calendar cells if there are changes
        if (hasChanges && window.picker) {
          const cells = document.querySelectorAll('.pika-button');
          cells.forEach(cell => {
            const cellDate = new Date(cell.getAttribute('data-pika-year'), cell.getAttribute('data-pika-month'), cell.getAttribute('data-pika-day'));
            const dateStr = cellDate.toDateString();
            const status = bookingMap.get(dateStr);
            const previousStatus = previousBookings.get(dateStr);
            
            // Only update if status changed for this specific date
            if (status !== previousStatus) {
              // Remove existing status classes
              cell.classList.remove('status-pending', 'status-approved', 'status-completed', 'status-rescheduled');
              
              if (status) {
                cell.classList.add(`status-${status}`);
              }
            }
          });
          // Keep dashboard overrides global-only; no extra fetch needed here
        }
      })
      .catch((err) => {
        // Error loading booking data
      });
  }
  
  // Initial load
  loadBookingData();

  // Initialize Pikaday AFTER data is loaded
  const picker = new Pikaday({
    field: document.getElementById('calendar'),
    format: 'ddd, MMM DD YYYY',
    showDaysInNextAndPreviousMonths: true,
    firstDay: 1,
    bound: false,
    onDraw: function() {
  const cells = document.querySelectorAll('.pika-button');
      cells.forEach(cell => {
        // Remove existing status classes and override visuals
        cell.classList.remove('has-booking', 'status-pending', 'status-approved', 'status-completed', 'status-rescheduled');
        cell.classList.remove('day-holiday','day-blocked','day-force','day-online','day-endyear');
        const oldBadge = cell.querySelector('.ov-badge');
        if (oldBadge) oldBadge.remove();
        cell.removeAttribute('data-status');
        cell.removeAttribute('data-consultation-key');
        cell.removeAttribute('data-has-consultations');
        
        const day = cell.getAttribute('data-pika-day');
        const month = cell.getAttribute('data-pika-month');
        const year = cell.getAttribute('data-pika-year');
        if (day && month && year) {
          const cellDate = new Date(year, month, day);
          const key = cellDate.toDateString();
          const isoKey = `${cellDate.getFullYear()}-${String(cellDate.getMonth()+1).padStart(2,'0')}-${String(cellDate.getDate()).padStart(2,'0')}`;
          // Render overrides (global-only; no professor leave shown here)
          if (window.studentOverrides && window.studentOverrides[isoKey] && window.studentOverrides[isoKey].length > 0) {
            const items = window.studentOverrides[isoKey];
            // Priority: holiday > block_all > force_mode
            let chosen = null;
            for (const ov of items) { if (ov.effect === 'holiday') { chosen = ov; break; } }
            if (!chosen) { for (const ov of items) { if (ov.effect === 'block_all') { chosen = ov; break; } } }
            if (!chosen) { chosen = items[0]; }
            // Skip professor leave in dashboard calendar
            if (chosen && chosen.effect === 'block_all' && chosen.reason_key === 'prof_leave') {
              chosen = null;
            }
            if (!chosen) { return; }
            const badge = document.createElement('span');
            // Badge class: distinguish Online Day vs Forced Online; End Year distinct from Suspension
            let chosenCls;
            if (chosen.effect === 'holiday') {
              chosenCls = 'ov-holiday';
            } else if (chosen.effect === 'block_all') {
              const isEndYear = (chosen.reason_key === 'end_year') || /end\s*year/i.test(chosen.label || '') || /end\s*year/i.test(chosen.reason_text || '');
              chosenCls = isEndYear ? 'ov-endyear' : 'ov-blocked';
            } else if (chosen.effect === 'force_mode') {
              chosenCls = (chosen.reason_key === 'online_day') ? 'ov-online' : 'ov-force';
            } else {
              chosenCls = 'ov-force';
            }
            badge.className = 'ov-badge ' + chosenCls;
            const forceLabel = (chosen.effect === 'force_mode' && (chosen.reason_key === 'online_day')) ? 'Online Day' : 'Forced Online';
            badge.title = chosen.label || chosen.reason_text || (chosen.effect === 'force_mode' ? forceLabel : chosen.effect);
            const isEndYearLbl = (chosen.effect === 'block_all') && ((chosen.reason_key === 'end_year') || /end\s*year/i.test(chosen.label || '') || /end\s*year/i.test(chosen.reason_text || ''));
            badge.textContent = chosen.effect === 'holiday' ? (chosen.reason_text || 'Holiday') : (chosen.effect === 'block_all' ? (isEndYearLbl ? 'End Year' : 'Suspension') : forceLabel);
            cell.style.position = 'relative';
            cell.appendChild(badge);
            // Cell background class, with Online Day distinct from Forced Online
            let dayCls;
            if (chosen.effect === 'holiday') {
              dayCls = 'day-holiday';
            } else if (chosen.effect === 'block_all') {
              const isEndYear = (chosen.reason_key === 'end_year') || /end\s*year/i.test(chosen.label || '') || /end\s*year/i.test(chosen.reason_text || '');
              dayCls = isEndYear ? 'day-endyear' : 'day-blocked';
            } else if (chosen.effect === 'force_mode') {
              dayCls = (chosen.reason_key === 'online_day') ? 'day-online' : 'day-force';
            } else {
              dayCls = 'day-force';
            }
            cell.classList.add(dayCls);
          }
          if (bookingMap.has(key)) {
            const status = bookingMap.get(key);
            const classMap = {
              pending: 'status-pending',
              approved: 'status-approved',
              completed: 'status-completed',
              rescheduled: 'status-rescheduled'
            };
            cell.classList.add('has-booking');
            cell.classList.add(classMap[status]);
            cell.setAttribute('data-status', status);
            // Mark for tooltip hover
            cell.setAttribute('data-consultation-key', key);
            cell.setAttribute('data-has-consultations', 'true');
          }
        }
      });
    }
  });
  
  // Store picker globally for refresh
  window.picker = picker;
  picker.show();
  picker.draw();
  // Immediately load overrides for the visible month after initial draw
  try { if (typeof fetchStudentOverridesForMonth === 'function' && typeof getVisibleMonthBaseDate === 'function') { fetchStudentOverridesForMonth(getVisibleMonthBaseDate()); } } catch (_) {}
  // ---- Overrides: fetch month data and react to month navigation ----
  function getVisibleMonthBaseDate() {
    try {
      const selMonth = document.querySelector('.pika-select-month');
      const selYear = document.querySelector('.pika-select-year');
      if (selMonth && selYear) {
        const m = parseInt(selMonth.value, 10);
        const y = parseInt(selYear.value, 10);
        if (!isNaN(m) && !isNaN(y)) {
          const d = new Date(y, m, 1);
          if (!isNaN(d.getTime())) return d;
        }
      }
      const labelEl = document.querySelector('.pika-label');
      if (labelEl) {
        const text = (labelEl.textContent || '').trim();
        const parts = text.split(/\s+/);
        if (parts.length === 2) {
          const monthMap = { January:0, February:1, March:2, April:3, May:4, June:5, July:6, August:7, September:8, October:9, November:10, December:11, Jan:0, Feb:1, Mar:2, Apr:3, Jun:5, Jul:6, Aug:7, Sep:8, Oct:9, Nov:10, Dec:11 };
          const m = monthMap[parts[0]];
          const y = parseInt(parts[1], 10);
          if (!isNaN(m) && !isNaN(y)) {
            const d = new Date(y, m, 1);
            if (!isNaN(d.getTime())) return d;
          }
        }
      }
      const cur = document.querySelector('.pika-table .pika-button:not(.is-outside-current-month)');
      if (cur) {
        const y = parseInt(cur.getAttribute('data-pika-year'), 10);
        const m = parseInt(cur.getAttribute('data-pika-month'), 10);
        if (!isNaN(y) && !isNaN(m)) {
          const d = new Date(y, m, 1);
          if (!isNaN(d.getTime())) return d;
        }
      }
    } catch (_) {}
    const today = new Date();
    return new Date(today.getFullYear(), today.getMonth(), 1);
  }

  function fetchStudentOverridesForMonth(dateObj) {
    try {
      if (!dateObj || !(dateObj instanceof Date) || isNaN(dateObj.getTime())) return;
      if (window.__studentOvLoading) return; // prevent overlapping requests
      window.__studentOvLoading = true;
      // widen range to cover adjacent-month cells that are visible
      const start = new Date(dateObj.getFullYear(), dateObj.getMonth() - 1, 1);
      const end = new Date(dateObj.getFullYear(), dateObj.getMonth() + 2, 0);
      const toIso = (d) => `${d.getFullYear()}-${String(d.getMonth()+1).padStart(2,'0')}-${String(d.getDate()).padStart(2,'0')}`;
      const startStr = toIso(start);
      const endStr = toIso(end);
      const bust = Date.now();
      // LocalStorage helpers
      function lsGetOv(key){ try{ const raw=localStorage.getItem(key); if(!raw) return null; const obj=JSON.parse(raw); if(!obj||!obj.exp||Date.now()>obj.exp){ localStorage.removeItem(key); return null; } return obj.data; }catch(_){ return null; } }
      function lsSetOv(key, data, ttlMs){ try{ localStorage.setItem(key, JSON.stringify({ exp: Date.now()+(ttlMs||6*3600*1000), data })); }catch(_){ }
      }
      const lsKey = `ov:student:${startStr}-${endStr}`;
      // Instant paint from LS snapshot if present
      try { const ls = lsGetOv(lsKey); if(ls){ window.studentOverrides = ls; if (window.picker) window.picker.draw(); } } catch(_) {}
      // Global-only overrides for student dashboard (network refresh)
      fetch(`/api/calendar/overrides?start_date=${startStr}&end_date=${endStr}&_=${bust}`, { headers: { 'Accept':'application/json' } })
        .then(r=>r.json())
        .then(data => {
          if (data && data.success) {
            const incoming = data.overrides || {};
            const prev = window.studentOverrides || {};
            const changed = JSON.stringify(incoming) !== JSON.stringify(prev);
            if (changed) {
              window.studentOverrides = incoming;
              try{ lsSetOv(lsKey, incoming, 6*3600*1000); }catch(_){ }
              if (window.picker) window.picker.draw();
            }
          }
        })
        .catch(()=>{})
        .finally(()=>{ window.__studentOvLoading = false; });
    } catch(_){}
  }

  // Initial overrides load and month navigation observation
  (function observeMonthNavigation(){
    const run = () => fetchStudentOverridesForMonth(getVisibleMonthBaseDate());
    setTimeout(run, 100);
    document.addEventListener('click', (e)=>{
      const t = e.target;
      if (t.closest && (t.closest('.pika-prev') || t.closest('.pika-next'))) {
        setTimeout(run, 150);
      }
    });
    // Lightweight real-time: refresh overrides periodically and on focus
    setInterval(run, 5000); // every 5s
    window.addEventListener('focus', () => setTimeout(run, 200));
    document.addEventListener('visibilitychange', () => { if (!document.hidden) setTimeout(run, 200); });
  })();
  
  // Real-time refresh booking data every 3 seconds (reduced for smoother updates)
  setInterval(loadBookingData, 3000);
  
  // --- Student tooltip: detailed data fetch and hover handlers ---
  const detailsMap = new Map();
  let tooltipTimeout = null;
  let currentHoveredCell = null;

  function loadStudentDetails() {
    fetch('/api/student/consultation-logs', { headers: { 'Accept': 'application/json' } })
      .then(r => r.json())
      .then(entries => {
        // rebuild details map
        detailsMap.clear();
        (entries || []).forEach(entry => {
          try {
            // Filter out cancelled entries from tooltip details
            if ((entry.Status || '').toLowerCase() === 'cancelled') return;
            const d = new Date(entry.Booking_Date);
            const key = d.toDateString();
            if (!detailsMap.has(key)) detailsMap.set(key, []);
            detailsMap.get(key).push(entry);
          } catch(_) {}
        });
        // If currently hovered cell became empty, hide tooltip
        try {
          if (currentHoveredCell) {
            const y = currentHoveredCell.getAttribute('data-pika-year');
            const m = currentHoveredCell.getAttribute('data-pika-month');
            const d = currentHoveredCell.getAttribute('data-pika-day');
            if (y!=null && m!=null && d!=null) {
              const dateObj = new Date(y, m, d);
              const key = dateObj.toDateString();
              const list = detailsMap.get(key) || [];
              if (list.length === 0) {
                const tooltip = document.getElementById('consultationTooltip');
                if (tooltip) tooltip.style.display = 'none';
                currentHoveredCell = null;
              }
            }
          }
        } catch(_) {}
      })
      .catch(() => {});
  }

  // initial and periodic refresh
  loadStudentDetails();
  setInterval(loadStudentDetails, 5000);

  // Periodically sync data attributes for tooltip based on bookingMap
  setInterval(() => {
    try {
      const cells = document.querySelectorAll('.pika-button');
      cells.forEach(cell => {
        const y = cell.getAttribute('data-pika-year');
        const m = cell.getAttribute('data-pika-month');
        const d = cell.getAttribute('data-pika-day');
        if (!y||!m||!d) return;
        const dateObj = new Date(y, m, d);
        const key = dateObj.toDateString();
        if (bookingMap.has(key)) {
          cell.setAttribute('data-consultation-key', key);
          cell.setAttribute('data-has-consultations', 'true');
        } else {
          cell.removeAttribute('data-consultation-key');
          cell.removeAttribute('data-has-consultations');
        }
      });
    } catch(_) {}
  }, 3000);

  function formatTo12Hour(ts) {
    if (!ts) return '';
    // Try to parse common formats
    const parts = String(ts).split(' ');
    if (parts.length < 2) return String(ts);
    const datePart = parts[0];
    const timePart = parts[1];
    const tPieces = timePart.split(':');
    if (tPieces.length < 2) return String(ts);
    let hour = parseInt(tPieces[0], 10);
    const minute = tPieces[1];
    const second = tPieces[2] || '00';
    if (isNaN(hour)) return String(ts);
    const suffix = hour >= 12 ? 'PM' : 'AM';
    const hour12 = ((hour + 11) % 12) + 1;
    const hourStr = hour12.toString().padStart(2, '0');
    return `${datePart} ${hourStr}:${minute}:${second} ${suffix}`;
  }

  // Global hover delegation for student tooltip (simplified)
  document.addEventListener('mouseover', function(e) {
    const target = e.target;
    if (tooltipTimeout) { clearTimeout(tooltipTimeout); tooltipTimeout = null; }
    if (target && target.classList && target.classList.contains('pika-button') && target.hasAttribute('data-consultation-key')) {
      const key = target.getAttribute('data-consultation-key');
      const tooltip = document.getElementById('consultationTooltip');
      if (!tooltip) return;
      const consultations = detailsMap.get(key) || [];
      if (consultations.length === 0) return;

      // Build simplified content (no count header)
      let html = '';
      consultations.forEach((entry, idx) => {
        html += `
          <div class="consultation-entry" style="${idx>0 ? 'border-top:1px solid #eee; padding-top:6px; margin-top:6px;' : ''}">
            <div class="student-name" style="font-weight:600; color:#2c5f4f; margin-bottom:4px; font-size:14px;">Professor: ${entry.Professor || ''}</div>
            <div class="detail-row" style="font-size:12px; color:#666;">Subject: ${entry.subject || ''}</div>
            <div class="detail-row" style="font-size:12px; color:#666;">Type: ${entry.type || entry.Type || ''}</div>
            <div class="detail-row" style="font-size:12px; color:#666;">Mode: ${entry.Mode || ''}</div>
            <div class="status-row" style="font-size:12px; font-weight:600; color:#666;">Status: ${entry.Status || ''}</div>
            <div class="booking-time" style="font-size:11px; color:#999; font-style:italic;">Booked: ${formatTo12Hour(entry.Created_At)}</div>
          </div>`;
      });
      tooltip.innerHTML = html;
      tooltip.style.display = 'block';

      // Position to the right of the cell with viewport guards
      const cellRect = target.getBoundingClientRect();
      const tooltipRect = tooltip.getBoundingClientRect();
      const viewportHeight = window.innerHeight;
      const scrollY = window.scrollY || document.documentElement.scrollTop;
      const scrollX = window.scrollX || document.documentElement.scrollLeft;
      const GAP = 12;
      let left = cellRect.right + GAP + scrollX;
      let top = cellRect.top + scrollY;
      if (top + tooltipRect.height > scrollY + viewportHeight - 10) {
        top = scrollY + viewportHeight - tooltipRect.height - 10;
      }
      if (top < scrollY + 10) {
        top = scrollY + 10;
      }
      const maxRight = scrollX + window.innerWidth - 10;
      if (left + tooltipRect.width > maxRight) {
        left = Math.min(left, maxRight - tooltipRect.width);
      }
      tooltip.style.left = left + 'px';
      tooltip.style.top = top + 'px';
      currentHoveredCell = target;
    } else {
      if (currentHoveredCell && !target.closest('#consultationTooltip')) {
        tooltipTimeout = setTimeout(() => {
          const tooltip = document.getElementById('consultationTooltip');
          if (tooltip) tooltip.style.display = 'none';
          currentHoveredCell = null;
        }, 250);
      }
    }
  });

  document.addEventListener('mouseout', function(e){
    const target = e.target;
    const related = e.relatedTarget;
    if (target && target.classList && target.classList.contains('pika-button') && target.hasAttribute('data-consultation-key')) {
      if (!related || !related.closest('#consultationTooltip')) {
        const tooltip = document.getElementById('consultationTooltip');
        if (tooltip) tooltip.style.display = 'none';
        currentHoveredCell = null;
      }
    }
  });

  // Keep tooltip visible on hover; hide after leaving
  (function bindTooltipHover(){
    const tip = document.getElementById('consultationTooltip');
    if (!tip) return;
    tip.addEventListener('mouseenter', function(){ if (tooltipTimeout) { clearTimeout(tooltipTimeout); tooltipTimeout = null; } });
    tip.addEventListener('mouseleave', function(){ tooltipTimeout = setTimeout(()=>{ tip.style.display='none'; currentHoveredCell=null; }, 200); });
  })();

  // Prevent date selection/tinting like professor behavior
  function preventCalendarClicks(e) {
    const target = e.target;
    if (target && target.classList && target.classList.contains('pika-button') && target.closest('.pika-table')) {
      if (e.type === 'click' || e.type === 'mousedown' || e.type === 'touchstart' || e.type === 'touchend') {
        e.preventDefault();
        e.stopPropagation();
        e.stopImmediatePropagation();
        return false;
      }
    }
  }
  ['click','mousedown','touchstart','touchend'].forEach(type => {
    document.addEventListener(type, preventCalendarClicks, { capture:true, passive:false });
    document.addEventListener(type, preventCalendarClicks, { capture:false, passive:false });
  });
        
    // Initialize inbox notifications
    loadNotifications();
    
    // Real-time load notifications every 3 seconds (reduced for smoother updates)
    setInterval(loadNotifications, 3000);
    
    // Mark all as read functionality
    document.getElementById('mark-all-read').addEventListener('click', function() {
      markAllNotificationsAsRead();
    });
    
    let lastNotificationHash = '';
    
    function loadNotifications() {
      fetch('/api/notifications')
        .then(response => response.json())
        .then(data => {
          // Create a simple hash of the notifications to detect changes
          const notificationHash = JSON.stringify(data.notifications.map(n => ({id: n.id, is_read: n.is_read, message: n.message})));
          
          // Only update if notifications actually changed
          if (notificationHash !== lastNotificationHash) {
            displayNotifications(data.notifications);
            updateUnreadCount();
            lastNotificationHash = notificationHash;
          }
        })
        .catch(error => {
          document.getElementById('inbox-content').innerHTML = 
            '<div class="no-notifications"><i class="bx bx-error"></i><p>Error loading notifications</p></div>';
        });
    }
    
    function displayNotifications(notifications) {
      const inboxContent = document.getElementById('inbox-content');
      const mobileContainer = document.getElementById('mobileNotificationsContainer');
      
      if (notifications.length === 0) {
        const noNotificationsHtml = `
          <div class="no-notifications">
            <i class='bx bx-bell-off'></i>
            <p>No notifications yet</p>
          </div>
        `;
        inboxContent.innerHTML = noNotificationsHtml;
        if (mobileContainer) {
          mobileContainer.innerHTML = noNotificationsHtml;
        }
        return;
      }
      
      const computeTimeago = (ts) => {
        if (!ts) return '';
        const d = new Date(ts);
        if (Number.isNaN(d.getTime())) return '';
        const diff = Date.now() - d.getTime();
        if (diff < 0) return 'Just now';
        const seconds = Math.floor(diff / 1000);
        if (seconds < 10) return 'Just now';
        if (seconds < 60) return `${seconds}s ago`;
        const minutes = Math.floor(diff / 60000);
        if (minutes < 60) return `${minutes} ${minutes === 1 ? 'min' : 'mins'} ago`;
        const hours = Math.floor(diff / 3600000);
        if (hours < 24) return hours === 1 ? '1 hr ago' : `${hours} hrs ago`;
        const days = Math.floor(diff / 86400000);
        return `${days} ${days === 1 ? 'day' : 'days'} ago`;
      };

      const notificationsHtml = notifications.map(notification => {
        const typeKey = notification.type || '';
        const isSuspention = typeKey === 'suspention_day';
        const isReschedAccepted = typeKey === 'reschedule_accepted';

        // Choose badge label and style class
        const badgeLabel = isSuspention
          ? 'SUSPENSION'
          : isReschedAccepted
            ? 'ACCEPTED'
            : typeKey.replace(/_/g, ' ').toUpperCase();
        const badgeClass = isReschedAccepted ? 'accepted' : typeKey;

        // For suspension system notice, show a clear title; otherwise keep existing rule (guard against null title)
        const rawTitle = notification.title || '';
        const cleanTitle = isSuspention
          ? 'Suspension of Class'
          : (rawTitle.includes('Consultation') ? 'Consultation' : rawTitle);

        return `
          <div class="notification-item ${notification.is_read ? '' : 'unread'}" 
               data-id="${notification.id || ''}"
               data-type="${typeKey}"
               data-booking-id="${notification.booking_id || ''}">
            <div class="notification-type ${badgeClass}">${badgeLabel}</div>
            <div class="notification-title">${cleanTitle}</div>
            <div class="notification-message">${notification.message}</div>
            <div class="notification-time" data-timeago data-ts="${notification.created_at}">${computeTimeago(notification.created_at)}</div>
          </div>
        `;
      }).join('');
      
      inboxContent.innerHTML = notificationsHtml;
      if (mobileContainer) {
        mobileContainer.innerHTML = notificationsHtml;
      }
    }
    
    function updateUnreadCount() {
      fetch('/api/notifications/unread-count')
        .then(response => response.json())
        .then(data => {
          const countElement = document.getElementById('unread-count');
          const mobileCountElement = document.getElementById('mobileNotificationBadge');
          
          // Update desktop notification count
          countElement.textContent = data.count;
          countElement.style.display = data.count > 0 ? 'inline-block' : 'none';
          
          // Update mobile notification badge
          if (mobileCountElement) {
            mobileCountElement.textContent = data.count;
            mobileCountElement.style.display = data.count > 0 ? 'flex' : 'none';
          }
        })
        .catch(error => {
          // Error updating unread count
        });
    }
    
    function markNotificationAsRead(notificationId) {
      fetch('/api/notifications/mark-read', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify({ notification_id: notificationId })
      })
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          // Reset hash to force notification update
          lastNotificationHash = '';
          loadNotifications();
        }
      })
      .catch(error => {
        // Error marking notification as read
      });
    }
    
    function markAllNotificationsAsRead() {
      fetch('/api/notifications/mark-all-read', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        }
      })
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          // Reset hash to force notification update
          notificationsHash = '';
          loadNotifications();
        }
      })
      .catch(error => {
        // Error marking all notifications as read
      });
    }
    
    // Live timeago handled by public/js/timeago.js

    function handleNotificationTrigger(e) {
      const item = e.target.closest('.notification-item');
      if (!item) return;
      const id = item.getAttribute('data-id');
      const type = item.getAttribute('data-type');
      const bookingId = item.getAttribute('data-booking-id');
      if (type === 'rescheduled' && bookingId) {
        openRescheduleModal(bookingId, id);
      } else if (type === 'completion_pending' && bookingId) {
        openCompletionReviewWithFetch(bookingId, id);
      } else if (id) {
        markNotificationAsRead(id);
      }
    }

    const inboxContentEl = document.getElementById('inbox-content');
    inboxContentEl?.addEventListener('click', handleNotificationTrigger);

    // Mobile drawer uses its own container; mirror the same behavior.
    const mobileNotificationsEl = document.getElementById('mobileNotificationsContainer');
    mobileNotificationsEl?.addEventListener('click', handleNotificationTrigger);

    // Keyboard activation for accessibility (desktop + mobile overlays with keyboard support)
    function handleNotificationKeydown(e) {
      if (e.key !== 'Enter' && e.key !== ' ') return;
      const item = e.target.closest('.notification-item');
      if (!item) return;
      e.preventDefault();
      handleNotificationTrigger({ target: item });
    }
    inboxContentEl?.addEventListener('keydown', handleNotificationKeydown);
    mobileNotificationsEl?.addEventListener('keydown', handleNotificationKeydown);

    // Reschedule modal logic
    let __resModal = {
      bookingId: null,
      notificationId: null,
    };
    const resModalEl = document.getElementById('rescheduleModal');
    const resClose = document.getElementById('resModalClose');
    const resBody = document.getElementById('resModalBody');
    const resErr = document.getElementById('resModalError');
    const btnAccept = document.getElementById('resModalAcceptBtn');
    const btnCancel = document.getElementById('resModalCancelBtn');

    function showResModal(){ if(resModalEl) resModalEl.style.display='block'; }
    function hideResModal(){ if(resModalEl) resModalEl.style.display='none'; __resModal.bookingId=null; __resModal.notificationId=null; }
    resClose?.addEventListener('click', hideResModal);
    resModalEl?.addEventListener('click', (e)=>{ if (e.target === resModalEl) hideResModal(); });

    function openRescheduleModal(bookingId, notificationId){
      __resModal.bookingId = bookingId;
      __resModal.notificationId = notificationId;
      resBody.innerHTML = '<div style="display:flex; align-items:center; gap:8px; color:#374151;"><i class="bx bx-loader bx-spin" style="font-size:18px;"></i> Loading…</div>';
      resErr.style.display='none';
      showResModal();
      // Mark as read upon opening
      try { if (notificationId) markNotificationAsRead(notificationId); } catch(_){}
      fetch(`/api/student/consultation-details/${bookingId}`)
        .then(r => r.json())
        .then(data => {
          if (!data || !data.success) { throw new Error(data?.message || 'Failed'); }
          const c = data.consultation;
          const rows = [
            ['Professor', c.professor_name],
            ['Subject', c.subject],
            ['Type', c.type],
            ['Mode', c.mode],
            ['New date', c.booking_date],
          ];
          if (c.reschedule_reason) rows.push(['Reason', c.reschedule_reason]);
          let html = '<div style="display:grid; grid-template-columns:120px 1fr; gap:8px 12px;">';
          rows.forEach(([k,v]) => { html += `<div style="color:#6b7280;">${k}</div><div style="color:#111827; font-weight:600;">${(v||'')}</div>`; });
          html += '</div>';
          resBody.innerHTML = html;
        })
        .catch(()=>{
          resBody.innerHTML = '';
          resErr.textContent = 'Could not load details. Please try again later.';
          resErr.style.display='block';
        });
    }

    btnAccept?.addEventListener('click', function(){
      if (!__resModal.bookingId) return;
      btnAccept.disabled = true; btnCancel.disabled = true;
      fetch('/api/student/consultations/accept-reschedule', {
        method:'POST',
        headers:{ 'Content-Type':'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content') },
        body: JSON.stringify({ id: __resModal.bookingId })
      }).then(r=>r.json()).then(data=>{
        if (!data || !data.success) throw new Error(data?.message || 'Failed');
        hideResModal();
        // Refresh lists
        loadNotifications();
        loadBookingData();
        loadStudentDetails();
      }).catch(err=>{
        resErr.textContent = err?.message || 'Failed to accept reschedule.';
        resErr.style.display='block';
      }).finally(()=>{ btnAccept.disabled = false; btnCancel.disabled = false; });
    });

    btnCancel?.addEventListener('click', function(){
      if (!__resModal.bookingId) return;
      const confirmOverlay = document.getElementById('studentConfirmDialog');
      if (!confirmOverlay) return;
      confirmOverlay.dataset.action = 'cancel';
      confirmOverlay.querySelector('#confirmMessage').textContent = 'Cancel this consultation?';
      confirmOverlay.setAttribute('aria-hidden', 'false');
      confirmOverlay.style.display = 'flex';
    });

    const confirmOverlayEl = document.getElementById('studentConfirmDialog');
    const confirmCancelBtn = document.getElementById('confirmCancelBtn');
    const confirmProceedBtn = document.getElementById('confirmProceedBtn');

    function closeConfirmOverlay(){
      if (!confirmOverlayEl) return;
      confirmOverlayEl.style.display = 'none';
      confirmOverlayEl.setAttribute('aria-hidden', 'true');
      delete confirmOverlayEl.dataset.action;
    }

    confirmCancelBtn?.addEventListener('click', () => {
      closeConfirmOverlay();
    });

    confirmOverlayEl?.addEventListener('click', (e) => {
      if (e.target === confirmOverlayEl) {
        closeConfirmOverlay();
      }
    });

    confirmProceedBtn?.addEventListener('click', function(){
      if (!confirmOverlayEl || confirmOverlayEl.dataset.action !== 'cancel') return;
      if (!__resModal.bookingId) return;
      closeConfirmOverlay();
      btnAccept.disabled = true; btnCancel.disabled = true;
      fetch('/api/student/consultations/cancel-rescheduled', {
        method:'POST',
        headers:{ 'Content-Type':'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content') },
        body: JSON.stringify({ id: __resModal.bookingId })
      }).then(r=>r.json()).then(data=>{
        if (!data || !data.success) throw new Error(data?.message || 'Failed');
        hideResModal();
        loadNotifications();
        loadBookingData();
        loadStudentDetails();
      }).catch(err=>{
        resErr.textContent = err?.message || 'Failed to cancel consultation.';
        resErr.style.display='block';
      }).finally(()=>{ btnAccept.disabled = false; btnCancel.disabled = false; });
    });
        
    
  </script>
  <script src="{{ asset('js/timeago.js') }}"></script>
</body>
</html>
