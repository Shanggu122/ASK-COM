<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>Professors</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
  <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
  <link rel="stylesheet" href="{{ asset('css/comsci.css') }}">
  <link rel="stylesheet" href="{{ asset('css/confirm.css') }}">
  <link rel="stylesheet" href="{{ asset('css/confirm-modal.css') }}">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/pikaday/css/pikaday.css">

  <style>
  /* Grey-out helper for disabled consultation types */
  #consultTypeSection label.type-disabled {
    opacity: 0.45;
    filter: grayscale(1);
    cursor: not-allowed;
  }
  #consultTypeSection label.type-disabled input[type="checkbox"] {
    pointer-events: none;
  }

  #calendar {
    /* Calendar input field styling */
    border: 1px solid #ccc;
    padding: 8px;
    border-radius: 4px;
    width: 100%;
    display: none !important; /* Hide the calendar input field */
   }

  /* Unified arrow styling to match IT&IS */
  .pika-prev, .pika-next {
    background-color: #0d2b20; /* darker fill */
    border-radius: 50%;
    color: #ffffff;
    border: 2px solid #071a13; /* darker edge */
    font-size: 18px;
    padding: 10px;
    width: 38px !important;
    height: 38px;
    display: flex; align-items:center; justify-content:center;
    opacity: 100%;
    text-indent: -9999px; /* hide default */
    position: relative;
    background-image:none !important;
  }
  .pika-prev:after, .pika-next:after { content:''; position:absolute; top:46%; left:50%; transform:translate(-50%,-50%); font-size:24px; font-weight:700; color:#fff; text-indent:0; }
  .pika-prev:after { content:'\2039'; }
  .pika-next:after { content:'\203A'; }

  /* Weekday header styling (dynamic classes applied via JS) */
  .pika-table th { 
    background-color:#12372a; /* default Tue-Sat */
    color:#fff;
    border-radius:4px;
    padding:5px;
    transition:background-color .25s, opacity .25s;
  }
  .pika-table th.weekday-mon, .pika-table th.weekday-sun { background-color:#01703c; padding:10px; }
  .pika-table th.allowed-day { }
  .pika-table th.disallowed-day { }
  .pika-table th.weekend-day { }

  /* Disabled (blocked) days clearer */
  .is-disabled .pika-button, .pika-button.is-disabled { background:#e5f0ed !important; color:#94a5a0 !important; border:1px solid #d0dbd8; opacity:1 !important; cursor:not-allowed; }
  .is-disabled .pika-button:hover { background:#f1f4f6 !important; color:#b3bcc3 !important; }


  .pika-single {
    display: block !important;  /* Make sure the calendar is always visible */
    /* height: 300px; */
    border: none;
  }

  .pika-table {
    border-radius: 3px;
    width: 100%;
    /* height: 264px; */
    border-collapse: separate;
    border-spacing: 3px;
  }

  .pika-label {
    color: #12372a;
    font-size: 25px
  }

  .pika-day {
    text-align: center;
  }

  .pika-lendar{
    width: 100%;
    display: flex;
    flex-direction: column;
  }

  /* Available day buttons: unified green theme */
  .pika-button{
    background-color:#01703c; /* was grey */
    border-radius:4px;
    color:#ffffff;
    padding:10px;
    height:40px;
    margin:5px 0;
    /* Remove background transition to avoid flicker on selection */
    transition: transform .18s;
  }
  /* Availability states */
  .slot-free .pika-button { background:#01703c !important; }
  .slot-low .pika-button { background:#e6a100 !important; }
  .slot-full .pika-button { background:#b30000 !important; cursor:not-allowed; }
  .slot-low .pika-button:hover { background:#cc8f00 !important; }
  .slot-full .pika-button:hover { background:#990000 !important; }
  .slot-full .pika-button[disabled] { pointer-events:none; opacity:0.95; }
  .availability-legend { display:flex; gap:14px; font-size:12px; margin:6px 0 4px; flex-wrap:wrap; }
  .availability-legend span { display:flex; align-items:center; gap:6px; }
  .availability-legend i { width:14px; height:14px; border-radius:3px; display:inline-block; }
  .legend-free { background:#01703c; }
  .legend-low { background:#e6a100; }
  .legend-full { background:#b30000; }

  .pika-button:hover,
  .pika-row.pick-whole-week:hover .pika-button {
    color:#fff;
    background:#0d2b20; /* darker hover */
    box-shadow:none;
    border-radius:4px;
  }

  .is-selected .pika-button, .has-event .pika-button{
    color: #ffffff;
    background-color: #12372a !important;
    box-shadow: none;
  }
  /* Ensure selected state applies instantly */
  .is-selected .pika-button { transition: none !important; }
  .pika-button:active { background:#12372a !important; color:#ffffff !important; }
  .pika-button:focus  { background:#12372a !important; color:#ffffff !important; outline:none; }

  .is-today .pika-button {
    color: #fff;
    background-color:#5fb9d4;
    font-weight: bold;
  }

  .is-today .pika-button  { 
    color: #ffffff
  }

  .calendar-wrapper-container {
    display: block !important;
    visibility: visible !important;
  }
  
  .pika-single {
    display: block !important;
    visibility: visible !important;
  }

  

  /* Full-screen loading overlay (match login look) */
  .auth-loading-overlay {
    position: fixed;
    inset: 0;
    /* Dark translucent overlay to match login */
    background: rgba(0,0,0,0.82);
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
    z-index: 100000; /* ensure above navbar */
    opacity: 0;
    pointer-events: none;
    transition: opacity .25s ease;
  }
  .auth-loading-overlay.active { opacity:1; pointer-events: auto; }
  .auth-loading-spinner {
    width: 58px;
    height: 58px;
    border: 5px solid rgba(255,255,255,0.18);
    border-top-color: #36b58b; /* login accent */
    border-radius: 50%;
    animation: spin 1s linear infinite;
    margin-bottom: 18px;
  }
  .auth-loading-text { color:#e9f9f3; font-size:14px; letter-spacing:.08em; font-weight:600; font-family:'Segoe UI', sans-serif; }
  @keyframes spin { to { transform: rotate(360deg); } }

  /* LocalStorage cached overrides are used to hydrate shading instantly on open */

  /* Notification: top-right corner above modal */
  .notification {
    position: fixed;
    top: 18px;
    right: 22px;
    left: auto;
    transform: none;
    z-index: 12000;
    max-width: 420px;
    width: auto;
  }

  /* Inline form error box (non-overlapping) */
  .form-error-box {
    display: none; /* shown via JS */
    grid-template-columns: auto 1fr auto;
    gap: 12px;
    align-items: flex-start;
    padding: 12px 14px;
    margin: 8px 0 14px 0;
    border: 1px solid #fca5a5; /* red-300 */
    background: #fee2e2;       /* red-100 */
    color: #7f1d1d;            /* red-900 */
    border-radius: 10px;
  }
  .form-error-box .feb-icon {
    width: 28px; height: 28px;
    border-radius: 50%;
    background: #ef4444; /* red-500 */
    color: #fff; display: grid; place-items: center;
    font-weight: 800;
    line-height: 1;
    margin-top: 2px;
  }
  .form-error-box .feb-title { font-weight: 700; margin-bottom: 6px; }
  .form-error-box ul { margin: 0; padding-left: 18px; }
  .form-error-box li { margin: 2px 0; }
  .form-error-box .feb-close { background: transparent; border: 0; color: #7f1d1d; font-size: 22px; line-height: 1; cursor: pointer; }

  /* Per-field red highlight without shifting layout */
  .field-error {
    outline: 2px solid #ef4444;
    outline-offset: 2px;
    background: rgba(254,226,226,.45);
    border-radius: 10px;
  }
  .input-error {
    outline: 2px solid #ef4444 !important;
    outline-offset: 2px;
    background: #fee2e2 !important;
    border-radius: 8px;
  }
  /* Slim calendar error: wrap only the grid, not the label/legend */
  .calendar-choices { display:block; width:100%; border-radius:12px; box-sizing:border-box; position: relative; }
  .calendar-choices.field-error { background: transparent !important; outline: none !important; outline-offset: 0 !important; }
  .calendar-choices.field-error::after{
    content:""; position:absolute; left:-12px; right:-12px; top:-3px; bottom:-3px;
    border:1.5px solid #ef4444; border-radius:16px; pointer-events:none; box-sizing:border-box;
  }
  /* Inner wrapper for mode radios so error box stays slim */
  .mode-choices { display: inline-flex; gap: 32px; align-items: center; padding: 8px 12px; border-radius: 12px; }
  /* Make mode error look like a slim rectangle, not a tall block */
  .mode-choices.field-error {
    outline: 2px solid #ef4444;
    outline-offset: 2px;
    background: rgba(254, 226, 226, .55);
    border-radius: 12px;
  }
  /* Keep the mode section from shifting position when error toggles (desktop only) */
  @media (min-width: 769px){
    .mode-selection { min-height: 146px; }
  }

  /* Minimal helper: dim label when disabled (class applied via JS); keep main CSS in public/css */
  .mode-selection label.disabled { opacity:.6; cursor:not-allowed; pointer-events:none; }
  /* Calendar error highlight */
  /* Removed red outline styling on calendar error; we now show toast only */

  /* Override badges and day tints (match dashboards/ITIS) */
  .ov-badge {
    position: absolute;
    left: 50%;
    bottom: 6px;
    font-size: 11px;
    line-height: 1;
    padding: 2px 6px;
    border-radius: 8px;
    color: #ffffff;
    pointer-events: none;
    white-space: nowrap;
    max-width: calc(100% - 12px);
    overflow: hidden;
    text-overflow: ellipsis;
    z-index: 3;
    transform: translateX(-50%);
    text-align: center;
  }
  .ov-holiday { background-color: #9B59B6; }
  .ov-blocked { background-color: #374151; }
  .ov-force   { background-color: #2563eb; }
  .ov-online  { background-color: #FF69B4; }
  .ov-endyear { background-color: #6366f1; } /* End of School Year → Indigo */
  .ov-leave   { background-color: #0ea5a4; } /* Professor Leave → Teal */
  .day-holiday { background: rgba(155, 89, 182, 0.55) !important; }
  .day-blocked { background: rgba(55, 65, 81, 0.75) !important; }
  .day-force   { background: rgba(37, 99, 235, 0.6) !important; }
  .day-online  { background: rgba(255, 105, 180, 0.45) !important; }
  .day-endyear { background: rgba(99, 102, 241, 0.6) !important; } /* End Year → Indigo */

  /* Persist tint even when disabled for holiday/forced; hard grey for suspended */
  .is-disabled .pika-button.day-holiday { background: rgba(155, 89, 182, 0.55) !important; color:#fff !important; border-color:transparent !important; }
  .is-disabled .pika-button.day-force   { background: rgba(37, 99, 235, 0.6) !important;  color:#fff !important; border-color:transparent !important; }
  .is-disabled .pika-button.day-online  { background: rgba(255, 105, 180, 0.45) !important; color:#fff !important; border-color:transparent !important; }
  .is-disabled .pika-button.day-endyear { background: rgba(99, 102, 241, 0.6) !important; color:#fff !important; border-color:transparent !important; }
  .is-disabled .pika-button.ov-hard-block { background:#5f6b77 !important; color:#fff !important; border:1px solid transparent !important; }

  /* Make override tints win over availability and hover states */
  /* Online Day (pink) */
  .pika-button.day-online,
  .slot-free .pika-button.day-online,
  .slot-low .pika-button.day-online,
  .slot-full .pika-button.day-online,
  .pika-button.day-online:hover,
  .slot-free .pika-button.day-online:hover,
  .slot-low .pika-button.day-online:hover,
  .slot-full .pika-button.day-online:hover {
    background: rgba(255, 105, 180, 0.55) !important;
    color: #ffffff !important;
    border-color: transparent !important;
  }
  /* Forced Online (blue) */
  .pika-button.day-force,
  .slot-free .pika-button.day-force,
  .slot-low .pika-button.day-force,
  .slot-full .pika-button.day-force,
  .pika-button.day-force:hover,
  .slot-free .pika-button.day-force:hover,
  .slot-low .pika-button.day-force:hover,
  .slot-full .pika-button.day-force:hover {
    background: rgba(37, 99, 235, 0.7) !important;
    color: #ffffff !important;
    border-color: transparent !important;
  }
  /* When selected, always show dark green */
  .is-selected .pika-button.day-online,
  .is-selected .pika-button.day-force { background:#12372a !important; color:#ffffff !important; border-color:transparent !important; }




  </style>
</head>
<body class="page-comsci">
  @include('components.navbar')

  <div class="main-content">
    <div class="header">
      <h1>Professors</h1>
    </div>

    <div class="search-container">
  <input type="text" id="searchInput" placeholder="Search..." 
    autocomplete="off" spellcheck="false" inputmode="text" 
    maxlength="100" aria-label="Search professors by name" 
    pattern="[A-Za-z0-9 .,@_-]{0,100}">
    </div>

    <div class="profile-cards-grid">
      @foreach($professors as $prof)
  <div class="profile-card"
       onclick="openModal(this)"
       data-name="{{ $prof->Name }}"
       data-img="{{ $prof->profile_photo_url }}"
       data-prof-id="{{ $prof->Prof_ID }}"
       data-schedule="{{ $prof->Schedule ?: 'No schedule set' }}">
          <img src="{{ $prof->profile_photo_url }}" alt="Profile Picture">
          <div class="profile-name">{{ $prof->Name }}</div>
        </div>
      @endforeach
    </div>
    <div id="noResults" style="display:none; margin-top:12px; color:#b00020; font-weight:600; font-style: italic;">
      NO PROFESSOR FOUND
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
      <div id="quickReplies" class="quick-replies" role="group" aria-label="Common questions">
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

      <form id="chatForm" novalidate>
        <input type="text" id="message" placeholder="Type your message" autocomplete="off" spellcheck="false" required>
        <button type="submit">Send</button>
      </form>
    </div>
  </div>


  <div id="consultationModal" class="modal-overlay" style="display:none;">
    <form id="bookingForm" action="{{ route('consultation-book') }}" method="POST" class="modal-content" novalidate>
      @csrf

      {{-- <input type="hidden" name="prof_id" value="{{ $professor->Prof_ID }}"> --}}
      <input type="hidden" name="prof_id" id="modalProfId" value="">


      <div class="modal-header">
        <div class="profile-section">
          <img id="modalProfilePic" class="profile-pic" src="" alt="Profile Picture">
          <div class="profile-info">
              <h2 id="modalProfileName">Professor Name</h2>
              <div id="modalSchedule" class="schedule-display">
                <!-- Schedule will be populated by JavaScript -->
              </div>
          </div>
        </div>

        <select name="subject_id" id="modalSubjectSelect">
          {{-- Options will be filled by JS --}}
        </select>
        <!-- Custom dropdown (mobile only) - keeps native select for form submit -->
        <div id="csSubjectDropdown" class="cs-dd" style="display:none;">
          <button type="button" class="cs-dd-trigger" id="csDdTrigger">Select a Subject</button>
          <ul class="cs-dd-list" id="csDdList"></ul>
        </div>
      </div>

      

      <div class="checkbox-section" id="consultTypeSection">
        @foreach($consultationTypes as $type)
          @if($type->Consult_Type === 'Others')
            <div class="others-checkbox-container">
              <label id="othersLabel">
                <input type="checkbox" name="types[]" value="{{ $type->Consult_type_ID }}" id="otherTypeCheckbox">
                {{ $type->Consult_Type }}
              </label>
              <input type="text" name="other_type_text" id="otherTypeText"
                placeholder="Please specify...">
            </div>
          @else
            <label>
              <input type="checkbox" name="types[]" value="{{ $type->Consult_type_ID }}">
              {{ $type->Consult_Type }}
            </label>
          @endif
        @endforeach
      </div>

  <div class="flex-layout">
        <div class="calendar-wrapper-container">
          <label for="calendar">Select Date:</label>
          <div class="availability-legend">
            <span><i class="legend-free"></i> Available</span>
            <span><i class="legend-low"></i> Almost Full</span>
            <span><i class="legend-full"></i> Full</span>
          </div>
          <div id="bookingWindowHint" style="font-size:12px;color:#2563eb;margin:4px 0 8px;">
            <!-- JS will fill hint about when next month opens -->
          </div>
          <div id="calendarContainer" class="calendar-choices"></div>
          <input id="calendar" type="text" placeholder="Select Date" name="booking_date" required>
        </div>

        <div class="message-mode-container">
          <div class="mode-selection">
            <div class="mode-choices">
              <label><input type="radio" name="mode" value="online"> Online</label>
              <label><input type="radio" name="mode" value="onsite"> Onsite</label>
            </div>
          </div>
          <div class="button-group">
        <button type="submit" class="submit-btn">Submit</button>
        <button type="button" class="cancel-btn" onclick="closeModal()">Cancel</button>
      </div>
        </div>
        
      </div>

    </form>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/pikaday/pikaday.js"></script>
  <script>
  // Mobile-only: add dark green background to navbar after scrolling
  (function mobileHeaderScroll(){
    const header = document.querySelector('.mobile-header');
    if(!header) return;
    function apply(){
      const onMobile = window.innerWidth <= 950;
      const scrolled = onMobile && window.scrollY > 4;
      header.classList.toggle('scrolled', scrolled);
      document.body.classList.toggle('navbar-offset', scrolled);
    }
    window.addEventListener('scroll', apply, { passive:true });
    window.addEventListener('resize', apply);
    apply();
  })();

  document.addEventListener("DOMContentLoaded", function() {
      // Booking window rule: allow only current month; open next month when today is in the last week (Mon-start) of the month
      function todayStart(){ const t=new Date(); return new Date(t.getFullYear(), t.getMonth(), t.getDate()); }
      function getLastWeekMondayOfMonth(base){
        const y=base.getFullYear(), m=base.getMonth();
        const last=new Date(y, m+1, 0);
        // Monday=1, JS getDay(): Sun=0..Sat=6
        const d=last.getDay();
        const diff = (d - 1 + 7) % 7; // days back to Monday
        const mon=new Date(last); mon.setDate(last.getDate()-diff);
        return new Date(mon.getFullYear(), mon.getMonth(), mon.getDate());
      }
      function computeAllowedMaxDate(){
        const t = todayStart();
        const lastWeekMon = getLastWeekMondayOfMonth(t);
        if(t.getTime() >= lastWeekMon.getTime()){
          // open entire next month
          const nextEnd = new Date(t.getFullYear(), t.getMonth()+2, 0);
          return new Date(nextEnd.getFullYear(), nextEnd.getMonth(), nextEnd.getDate());
        }
        // otherwise, only until end of current month
        const curEnd = new Date(t.getFullYear(), t.getMonth()+1, 0);
        return new Date(curEnd.getFullYear(), curEnd.getMonth(), curEnd.getDate());
      }
      const __BOOK_MIN_DATE = todayStart();
      const __BOOK_MAX_DATE = computeAllowedMaxDate();
  // Always allow navigating far into the future so students can SEE future months (but selection stays disabled outside window)
  const __NAV_MAX_DATE = new Date(2099, 11, 31);

      // Render hint for students
      (function renderBookingWindowHint(){
        try{
          const t = todayStart();
          const lastWeekMon = getLastWeekMondayOfMonth(t);
          const el = document.getElementById('bookingWindowHint');
          if(!el) return;
          const fmt = new Intl.DateTimeFormat('en-US', { weekday:'short', month:'short', day:'2-digit', year:'numeric'});
          if(t.getTime() >= lastWeekMon.getTime()){
            el.textContent = `Next month is now open for booking.`;
            el.style.color = '#047857'; // green when open
          } else {
            el.textContent = `Heads up: Next month opens on ${fmt.format(lastWeekMon)}.`;
            el.style.color = '#2563eb';
          }
        }catch(_){ }
      })();

      

      let allowedWeekdays = new Set(); // numeric 1-5 Mon-Fri allowed for selected professor

      // LocalStorage helpers for instant hydration of overrides
      function lsGetOv(key){
        try {
          const raw = localStorage.getItem(key);
          if (!raw) return null;
          const obj = JSON.parse(raw);
          if (!obj || !obj.exp || Date.now() > obj.exp) { localStorage.removeItem(key); return null; }
          return obj.data || null;
        } catch(_) { return null; }
      }
      function lsSetOv(key, data, ttlMs){
        try { localStorage.setItem(key, JSON.stringify({ exp: Date.now() + (ttlMs || 6*3600*1000), data })); } catch(_) {}
      }
      function buildLsKey(scope, cacheKey){ return `ov:${scope}:${cacheKey}`; }

      // Public overrides cache and helpers (ISO-keyed)
      window.__publicOverrides = window.__publicOverrides || {}; // { 'YYYY-MM-DD': [ ... ] }
      window.__blockedOverrideSet = window.__blockedOverrideSet || new Set();
      function isOverrideBlocked(date){
        try{ const iso = `${date.getFullYear()}-${String(date.getMonth()+1).padStart(2,'0')}-${String(date.getDate()).padStart(2,'0')}`; return window.__blockedOverrideSet.has(iso); }catch(_){ return false; }
      }
      function hasForceOrOnlineOverride(date){
        try{ const iso = `${date.getFullYear()}-${String(date.getMonth()+1).padStart(2,'0')}-${String(date.getDate()).padStart(2,'0')}`; const items=(window.__publicOverrides||{})[iso]||[]; return items.some(x=>x.effect==='force_mode'); }catch(_){ return false; }
      }

      function disableDayFn(date){
        // Enforce booking window
        if(date < __BOOK_MIN_DATE) return true;
        if(date > __BOOK_MAX_DATE) return true;
        const day = date.getDay(); // 0 Sun..6 Sat
        if(day===0 || day===6) return true; // weekends blocked
        // Block by overrides (Holiday or Suspended)
        if(isOverrideBlocked(date)) return true;
        // If no schedule, block ALL weekdays (even Online Day)
        if(allowedWeekdays.size === 0) return true;
        // Otherwise block days not in schedule
        if(!allowedWeekdays.has(day)) return true;
        return false;
      }

      function updateWeekdayHeaders(){
        const headers=document.querySelectorAll('.pika-table th');
        if(!headers.length) return;
        headers.forEach(th=>{
          th.classList.remove('allowed-day','disallowed-day','weekend-day','weekday-mon','weekday-sun');
          const ab=th.querySelector('abbr'); if(!ab) return;
          const title=ab.getAttribute('title');
          const map={'Sunday':0,'Monday':1,'Tuesday':2,'Wednesday':3,'Thursday':4,'Friday':5,'Saturday':6};
          const d=map[title];
          if(d===1) th.classList.add('weekday-mon');
          if(d===0) th.classList.add('weekday-sun');
          if(d===0 || d===6){ th.classList.add('weekend-day'); return; }
          if(allowedWeekdays.size===0){ th.classList.add('disallowed-day'); return; }
          if(allowedWeekdays.has(d)) th.classList.add('allowed-day'); else th.classList.add('disallowed-day');
        });
      }

      window.__updateAllowedWeekdays = function(scheduleText){
        allowedWeekdays.clear();
        if(!scheduleText){ picker.draw(); updateWeekdayHeaders(); return; }
        const lines = scheduleText.split(/\n|<br\s*\/>/i).map(l=>l.trim()).filter(Boolean);
        const nameToNum = { Monday:1, Tuesday:2, Wednesday:3, Thursday:4, Friday:5 };
        lines.forEach(line=>{
          const m=line.match(/^(Monday|Tuesday|Wednesday|Thursday|Friday)\b/i);
          if(m){
            const key=m[1].charAt(0).toUpperCase()+m[1].slice(1).toLowerCase();
            if(nameToNum[key]) allowedWeekdays.add(nameToNum[key]);
          }
        });
        picker.draw();
        updateWeekdayHeaders();
      };

      var picker = new Pikaday({
        field: document.getElementById('calendar'),
        container: document.getElementById('calendarContainer'),
        format: 'ddd, MMM DD YYYY',
        onSelect: function(){
          document.getElementById('calendar').value = this.toString('ddd, MMM DD YYYY');
          // Apply lock whenever a new date is selected
          try { if (typeof applyLockForSelectedDate === 'function') applyLockForSelectedDate(); } catch(_) {}
          // Clear calendar error once a valid date is chosen
          document.querySelector('.calendar-choices')?.classList.remove('field-error');
        },
        showDaysInNextAndPreviousMonths: true,
        firstDay: 1,
        bound: false,
        minDate: __BOOK_MIN_DATE,
        maxDate: __NAV_MAX_DATE,
        disableDayFn: disableDayFn
      });
      // Expose globally so async fetchers can trigger redraws reliably
      window.picker = picker;
  let __availabilityCache = {};
  window.__availabilityCache = __availabilityCache;
  // Shared overrides cache across pages (profId|range -> overrides)
  window.__ovCache = window.__ovCache || {};
  // Toggle for console debugging if needed
  window.__DEBUG_MODE_LOCK = window.__DEBUG_MODE_LOCK || false;
      let __dailyCapacity = 5;
      function setLabelDisabled(input, disabled){
        if(!input) return; const label = input.closest('label'); if(label){ label.classList.toggle('disabled', !!disabled); }
      }
      function setModeLockUI(mode){
        const online = document.querySelector('input[name="mode"][value="online"]');
        const onsite = document.querySelector('input[name="mode"][value="onsite"]');
        if(!online || !onsite) return;
        // Reset first
        online.disabled = false; onsite.disabled = false; setLabelDisabled(online,false); setLabelDisabled(onsite,false);
        if(!mode){
          // No forced mode: don't touch current selection; just ensure both options are enabled
          return;
        }
        if(mode === 'online'){
          online.checked = true; onsite.checked = false; onsite.disabled = true; setLabelDisabled(onsite,true);
          // fire change to ensure CSS/UA paints checked state consistently
          online.dispatchEvent(new Event('change', { bubbles: true }));
          online.focus({ preventScroll: true });
        }
        if(mode === 'onsite'){
          onsite.checked = true; online.checked = false; online.disabled = true; setLabelDisabled(online,true);
          onsite.dispatchEvent(new Event('change', { bubbles: true }));
          onsite.focus({ preventScroll: true });
        }
      }

      // Remember user-chosen mode so redraws don't inadvertently clear it
      (function rememberUserMode(){
        const radios = document.querySelectorAll('input[name="mode"]');
        radios.forEach(r=>{
          r.addEventListener('change', ()=>{
            if(r.checked){ window.__userSelectedMode = r.value; }
          });
        });
      })();

      function applyLockForSelectedDate(){  
        try{
          if(!window.picker) return;
          const d = window.picker.getDate(); if(!d){ setModeLockUI(null); return; }
          const key = d.toLocaleDateString('en-US', { weekday:'short', month:'short', day:'2-digit', year:'numeric'}).replace(/,/g,'');
          const rec = (window.__availabilityCache||{})[key];
          const mode = rec && rec.mode ? rec.mode : null;
          if(window.__DEBUG_MODE_LOCK) console.log('[mode-lock] applyLockForSelectedDate', { key, mode, rec });
          setModeLockUI(mode);
          // If no forced mode, restore user selection if we have one
          if(!mode && window.__userSelectedMode){
            const el = document.querySelector(`input[name="mode"][value="${window.__userSelectedMode}"]`);
            if(el && !el.disabled && !el.checked){ el.checked = true; }
          }
          // If mode not yet available (async fetch racing), retry once shortly
          if(!mode){ setTimeout(()=>{
            const r2 = (window.__availabilityCache||{})[key];
            const m2 = r2 && r2.mode ? r2.mode : null;
            if(window.__DEBUG_MODE_LOCK) console.log('[mode-lock] retry applyLockForSelectedDate', { key, m2 });
            setModeLockUI(m2);
            if(!m2 && window.__userSelectedMode){
              const el2 = document.querySelector(`input[name="mode"][value="${window.__userSelectedMode}"]`);
              if(el2 && !el2.disabled && !el2.checked){ el2.checked = true; }
            }
          }, 60); }
        }catch(_){}
      }
        try { applyPublicOverridesToCalendar(); } catch(_) {}

      window.__applyAvailability = function(map){
        const cells = document.querySelectorAll('.pika-table td');
        cells.forEach(td=>td.classList.remove('slot-free','slot-low','slot-full'));
        cells.forEach(td=>{
          const btn = td.querySelector('.pika-button');
          if(!btn) return;
          // Do not recolor days that Pikaday already marked disabled (by schedule/overrides)
          if (td.classList.contains('is-disabled') || btn.hasAttribute('disabled') || btn.getAttribute('aria-disabled') === 'true') return;
          const year = btn.getAttribute('data-pika-year');
          if(!year) return;
          const month = parseInt(btn.getAttribute('data-pika-month'),10);
          const day = parseInt(btn.getAttribute('data-pika-day'),10);
          const d = new Date(year, month, day);
          const key = d.toLocaleDateString('en-US', { weekday:'short', month:'short', day:'2-digit', year:'numeric'}).replace(/,/g,'');
          const info = map[key];
          let remaining, booked;
          if(info){ remaining = info.remaining; booked = info.booked; }
          else { remaining = __dailyCapacity; booked = 0; }
          btn.dataset.remaining = remaining;
          btn.dataset.booked = booked;
          btn.dataset.capacity = __dailyCapacity;
          btn.dataset.mode = (info && info.mode) ? info.mode : '';
          const modeTxt = info && info.mode ? ` • Mode: ${info.mode}` : '';
          btn.title = (remaining <= 0 ? `Fully booked (0/${__dailyCapacity})` : `${remaining} slot${remaining===1?'':'s'} left (${booked}/${__dailyCapacity} booked)`) + modeTxt;
          if(remaining <= 0){
            td.classList.add('slot-full');
            btn.setAttribute('disabled','disabled');
            btn.setAttribute('aria-disabled','true');
            btn.style.pointerEvents='none';
          }
          else if(remaining <= 2) td.classList.add('slot-low');
          else td.classList.add('slot-free');
        });
      };
      function refreshAvailabilityColors(){ window.__applyAvailability(__availabilityCache); }
  function enforceDisabledAttrs(){
    try { document.querySelectorAll('.pika-table td.is-disabled .pika-button').forEach(btn=>{ btn.setAttribute('disabled','disabled'); btn.setAttribute('aria-disabled','true'); btn.style.pointerEvents='none'; }); } catch(_) {}
  }
  const _origDraw = picker.draw.bind(picker);
  picker.draw = function(){
    _origDraw();
    updateWeekdayHeaders();
    refreshAvailabilityColors();
    try{ applyPublicOverridesToCalendar(); }catch(_){ }
    try{ if(window.applyLockForSelectedDate){ applyLockForSelectedDate(); } }catch(_){ }
    try{ attachSelectionObserver(); }catch(_){ }
    enforceDisabledAttrs();
  };
  // Ensure calendar is visible and headers state is applied immediately (mirrors ITIS)
  try { picker.show(); updateWeekdayHeaders(); } catch(_) {}
  // Note: We rely on our picker.draw override to repaint overrides consistently.
  // Avoid observing all table mutations as that can cause repaint loops and jank.
      function fetchAvailability(profId){
        if(!profId) return;
        const now = new Date();
        const start = now.toISOString().slice(0,10);
        const endDate = new Date(now.getFullYear(), now.getMonth()+2, 0);
        const end = endDate.toISOString().slice(0,10);
        fetch(`/api/professor/availability?prof_id=${profId}&start=${start}&end=${end}`)
      .then(r=>r.json())
    .then(data=>{ if(!data.success) return; if(typeof data.capacity==='number') __dailyCapacity=data.capacity; __availabilityCache={}; data.dates.forEach(rec=>{ __availabilityCache[rec.date]=rec; }); window.__availabilityCache = __availabilityCache; refreshAvailabilityColors(); applyLockForSelectedDate(); })
          .catch(()=>{});
      }
      window.__fetchAvailability = fetchAvailability;
      document.addEventListener('click', function(e){
        const btn = e.target.closest('.pika-button');
        if(!btn) return;
        // Block any interaction on disabled days (Leave/Suspension/Holiday)
        try{
          const tdCell = btn.closest('td');
          const disabled = (tdCell && tdCell.classList.contains('is-disabled')) || btn.hasAttribute('disabled') || btn.getAttribute('aria-disabled')==='true';
          if(disabled){
            e.preventDefault(); e.stopPropagation(); if(e.stopImmediatePropagation) e.stopImmediatePropagation();
            const sel = document.querySelector('.pika-table td.is-selected');
            if(sel && sel.classList.contains('is-disabled')) sel.classList.remove('is-selected');
            return false;
          }
        }catch(_){ }
        // Any valid click clears calendar error highlight
  document.querySelector('.calendar-choices')?.classList.remove('field-error');
        // Apply mode lock to radio inputs when selecting a date
        let mode = btn.dataset.mode || null;
        if(!mode){
          // Fallback: compute key and check availability cache
          try {
            const key = new Date(btn.getAttribute('data-pika-year'), parseInt(btn.getAttribute('data-pika-month'),10), parseInt(btn.getAttribute('data-pika-day'),10))
              .toLocaleDateString('en-US', { weekday:'short', month:'short', day:'2-digit', year:'numeric'}).replace(/,/g,'');
            const rec = (window.__availabilityCache||{})[key];
            if(rec && rec.mode) mode = rec.mode;
          } catch(_) {}
        }
        if(!mode){
          // Also check overrides for force_mode
          try{
            const y = parseInt(btn.getAttribute('data-pika-year'),10);
            const m = parseInt(btn.getAttribute('data-pika-month'),10);
            const d = parseInt(btn.getAttribute('data-pika-day'),10);
            const isoKey = `${y}-${String(m+1).padStart(2,'0')}-${String(d).padStart(2,'0')}`;
            const items = (window.__publicOverrides||{})[isoKey] || [];
            const fm = items.find(x=>x.effect==='force_mode');
            if(fm){ mode = fm.allowed_mode || (fm.reason_key==='online_day'?'online':null) || 'online'; }
          }catch(_){ }
        }
        if(window.__DEBUG_MODE_LOCK) console.log('[mode-lock] click day', { mode, btn });
        setModeLockUI(mode);
        // Also re-apply after the calendar finishes its own handlers
        setTimeout(()=>{ setModeLockUI(mode); }, 0);
        setTimeout(()=>{ try{ applyLockForSelectedDate(); }catch(_){} }, 0);
        if(btn.dataset && btn.dataset.remaining === '0'){
          e.preventDefault();
          e.stopPropagation();
          if(e.stopImmediatePropagation) e.stopImmediatePropagation();
          const sel = document.querySelector('.pika-table td.is-selected');
          if(sel && sel.classList.contains('slot-full')) sel.classList.remove('is-selected');
          return false;
        }
      }, true);
      picker.show();
      updateWeekdayHeaders();
      window.picker = picker;

      // Observe selection highlight changes to enforce mode lock reliably
      function attachSelectionObserver(){
        const table = document.querySelector('.pika-table');
        if(!table) return;
        if(table.__modeSelObserver){ return; }
        const obs = new MutationObserver(()=>{
          const td = table.querySelector('td.is-selected .pika-button');
          if(!td) return;
          // Ignore disabled selections (e.g., Leave/Suspension/holiday)
          try { const tdCell = td.closest('td'); if(tdCell && tdCell.classList.contains('is-disabled')) return; } catch(_) {}
          let mode = td.dataset.mode || null;
          if(!mode){
            try{
              const y = parseInt(td.getAttribute('data-pika-year'),10);
              const m = parseInt(td.getAttribute('data-pika-month'),10);
              const d = parseInt(td.getAttribute('data-pika-day'),10);
              const isoKey = `${y}-${String(m+1).padStart(2,'0')}-${String(d).padStart(2,'0')}`;
              const items = (window.__publicOverrides||{})[isoKey] || [];
              const fm = items.find(x=>x.effect==='force_mode');
              if(fm){ mode = fm.allowed_mode || (fm.reason_key==='online_day'?'online':null) || 'online'; }
            }catch(_){ }
          }
          if(!mode){
            try{
              const key = new Date(td.getAttribute('data-pika-year'), parseInt(td.getAttribute('data-pika-month'),10), parseInt(td.getAttribute('data-pika-day'),10))
                .toLocaleDateString('en-US', { weekday:'short', month:'short', day:'2-digit', year:'numeric'}).replace(/,/g,'');
              const rec = (window.__availabilityCache||{})[key];
              if(rec && rec.mode) mode = rec.mode;
            }catch(_){ }
          }
          if(window.__DEBUG_MODE_LOCK) console.log('[mode-lock][observer] apply', { mode });
          setModeLockUI(mode);
        });
        obs.observe(table, { attributes:true, subtree:true, attributeFilter:['class'] });
        table.__modeSelObserver = obs;
      }
      attachSelectionObserver();

      function recomputeBlockedSet(){
        const set = new Set();
        const map = window.__publicOverrides || {};
        Object.keys(map).forEach(k=>{
          const arr = map[k]||[];
          if(arr.some(x=>x.effect==='holiday' || x.effect==='block_all')) set.add(k);
        });
        window.__blockedOverrideSet = set;
      }

      function getVisibleMonthBaseDate(){
        try{
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
          if(labelEl){
            const text = (labelEl.textContent||'').trim();
            const parts = text.split(/\s+/);
            if(parts.length===2){
              const monthMap = { January:0, February:1, March:2, April:3, May:4, June:5, July:6, August:7, September:8, October:9, November:10, December:11, Jan:0, Feb:1, Mar:2, Apr:3, Jun:5, Jul:6, Aug:7, Sep:8, Oct:9, Nov:10, Dec:11 };
              const m = monthMap[parts[0]]; const y = parseInt(parts[1],10);
              if(!isNaN(m)&&!isNaN(y)) { const d=new Date(y,m,1); if(!isNaN(d.getTime())) return d; }
            }
          }
          const cur = document.querySelector('.pika-table .pika-button:not(.is-outside-current-month)');
          if(cur){ const y=parseInt(cur.getAttribute('data-pika-year'),10); const m=parseInt(cur.getAttribute('data-pika-month'),10); if(!isNaN(y)&&!isNaN(m)) return new Date(y,m,1); }
        }catch(_){}
        const t=new Date(); return new Date(t.getFullYear(), t.getMonth(), 1);
      }
      // Expose helpers to global scope for use outside this closure
      window.__comsciGetVisibleMonthBaseDate = getVisibleMonthBaseDate;

      // Paint whenever overrides exist (LS/cache/network)
      window.__ovInitialized = window.__ovInitialized || false;
      function applyPublicOverridesToCalendar(){
        const cells = document.querySelectorAll('.pika-table td');
        cells.forEach(td=>{
          const btn = td.querySelector('.pika-button'); if(!btn) return;
          const y = parseInt(btn.getAttribute('data-pika-year'),10);
          const m = parseInt(btn.getAttribute('data-pika-month'),10);
          const d = parseInt(btn.getAttribute('data-pika-day'),10);
          if(Number.isNaN(y)||Number.isNaN(m)||Number.isNaN(d)) return;
          const isoKey = `${y}-${String(m+1).padStart(2,'0')}-${String(d).padStart(2,'0')}`;
          const items = (window.__publicOverrides||{})[isoKey] || [];
          if(!items.length) {
            // No override for this date: clear only override visuals; DO NOT touch schedule-disabled state
            const old = btn.querySelector('.ov-badge'); if(old) old.remove();
            btn.classList.remove('day-holiday','day-blocked','day-force','day-online','day-endyear','ov-hard-block','day-leave');
            td.classList.remove('day-leave-td');
            return;
          }
          // We have items: clear previous and paint
          const old = btn.querySelector('.ov-badge'); if(old) old.remove();
          btn.classList.remove('day-holiday','day-blocked','day-force','day-online','day-endyear','ov-hard-block');
          let chosen = items.find(x=>x.effect==='holiday') || items.find(x=>x.effect==='block_all') || items[0];
          const badge = document.createElement('span');
          let chosenCls;
          if (chosen.effect === 'holiday') chosenCls = 'ov-holiday';
          else if (chosen.effect === 'block_all') {
            const isLeave = (chosen.reason_key === 'prof_leave' || /leave/i.test(chosen.label||''));
            const isEndYear = (!isLeave) && ((chosen.reason_key === 'end_year') || /end\s*year/i.test(chosen.label||'') || /end\s*year/i.test(chosen.reason_text||''));
            chosenCls = isLeave ? 'ov-leave' : (isEndYear ? 'ov-endyear' : 'ov-blocked');
          }
          else if (chosen.effect === 'force_mode') chosenCls = (chosen.reason_key === 'online_day') ? 'ov-online' : 'ov-force';
          else chosenCls = 'ov-force';
          badge.className = 'ov-badge ' + chosenCls;
          const forceLabel = (chosen.effect === 'force_mode' && (chosen.reason_key === 'online_day')) ? 'Online Day' : 'Forced Online';
          badge.title = chosen.label || chosen.reason_text || (chosen.effect === 'force_mode' ? forceLabel : chosen.effect);
          const isLeaveLbl = (chosen.effect === 'block_all') && (chosen.reason_key === 'prof_leave' || /leave/i.test(chosen.label||''));
          const isEndYearLbl = (chosen.effect === 'block_all') && (!isLeaveLbl) && ((chosen.reason_key === 'end_year') || /end\s*year/i.test(chosen.label||'') || /end\s*year/i.test(chosen.reason_text||''));
          badge.textContent = chosen.effect === 'holiday' ? (chosen.reason_text || 'Holiday') : (chosen.effect === 'block_all' ? (isLeaveLbl ? 'Leave' : (isEndYearLbl ? 'End Year' : 'Suspension')) : forceLabel);
          btn.style.position = 'relative';
          btn.appendChild(badge);
          if (chosen.effect === 'force_mode') {
            const dayCls = (chosen.reason_key === 'online_day') ? 'day-online' : 'day-force';
            btn.classList.add(dayCls);
          }
            if (chosen.effect === 'holiday' || chosen.effect === 'block_all'){
            td.classList.remove('slot-free','slot-low','slot-full');
            td.classList.add('is-disabled');
            btn.setAttribute('disabled','disabled');
            btn.setAttribute('aria-disabled','true');
            btn.style.pointerEvents='none';
            if (chosen.effect === 'block_all') {
              const isLeave = (chosen.reason_key === 'prof_leave' || /leave/i.test(chosen.label||''));
              const isEndYear = (!isLeave) && ((chosen.reason_key === 'end_year') || /end\s*year/i.test(chosen.label||'') || /end\s*year/i.test(chosen.reason_text||''));
                if (isLeave) { btn.classList.add('day-leave'); td.classList.add('day-leave-td'); btn.classList.remove('ov-hard-block'); }
              else if (isEndYear) { btn.classList.add('day-endyear'); btn.classList.remove('ov-hard-block'); }
              else { btn.classList.add('ov-hard-block'); }
            } else { btn.classList.add('day-holiday'); }
          } else {
            btn.classList.remove('ov-hard-block','day-holiday','day-leave','day-endyear');
            td.classList.remove('day-leave-td');
          }
          if (chosen.effect === 'force_mode'){
            let mode = chosen.allowed_mode || (chosen.reason_key==='online_day'?'online':null) || 'online';
            btn.dataset.mode = mode;
          }
        });
      }

      function fetchPublicOverridesForMonth(dateObj){
        try{
          if (!dateObj || !(dateObj instanceof Date) || isNaN(dateObj.getTime())) return;
          // Fetch previous, current, and next month to shade adjacent cells
          const start = new Date(dateObj.getFullYear(), dateObj.getMonth()-1, 1);
          const end = new Date(dateObj.getFullYear(), dateObj.getMonth()+2, 0);
          const toIso = d=>`${d.getFullYear()}-${String(d.getMonth()+1).padStart(2,'0')}-${String(d.getDate()).padStart(2,'0')}`;
          const bust = Date.now();
          const profIdInput = document.getElementById('modalProfId');
          const profId = profIdInput ? profIdInput.value : '';
          // Cache-first paint to remove initial delay
          if (profId) {
            const cacheKey = `${profId}|${toIso(start)}-${toIso(end)}`; // expanded range includes prev+next months
            // Hydrate instantly from LS if present
            try { const ls = lsGetOv(buildLsKey('public', cacheKey)); if(ls){ window.__publicOverrides = ls; recomputeBlockedSet(); if(window.picker){ window.picker.draw(); try{ applyPublicOverridesToCalendar(); }catch(_){ } } } } catch(_) {}
            if (window.__ovCache && window.__ovCache[cacheKey]) {
              const cached = window.__ovCache[cacheKey];
              const prev = window.__publicOverrides || {};
              const changed = JSON.stringify(cached) !== JSON.stringify(prev);
              if (!window.__ovInitialized || changed) {
                window.__publicOverrides = cached;
              }
              window.__ovInitialized = true;
              recomputeBlockedSet();
              if(window.picker) window.picker.draw();
              // Also apply directly to avoid relying solely on draw timing
              try { applyPublicOverridesToCalendar(); } catch(_) {}
            }
            // Fetch fresh professor overrides and apply immediately when ready
            try {
              const toIso = (d)=>`${d.getFullYear()}-${String(d.getMonth()+1).padStart(2,'0')}-${String(d.getDate()).padStart(2,'0')}`;
              const url = `/api/calendar/overrides/professor?prof_id=${encodeURIComponent(profId)}&start_date=${toIso(start)}&end_date=${toIso(end)}&_=${Date.now()}`;
              fetch(url, { headers:{ 'Accept':'application/json' } })
                .then(r=>r.json())
                .then(data=>{
                  if(data && data.success){
                    window.__publicOverrides = data.overrides || {};
                    recomputeBlockedSet();
                    if(window.picker) window.picker.draw();
                    try { applyPublicOverridesToCalendar(); } catch(_) {}
                    // update cache
                    window.__ovCache = window.__ovCache || {}; window.__ovCache[cacheKey] = window.__publicOverrides;
                    try{ lsSetOv(buildLsKey('public', cacheKey), window.__publicOverrides, 6*3600*1000); }catch(_){ }
                  }
                })
                .catch(()=>{});
            } catch(_){ }
          }
          if (window.__comsciOvLoading) return;
          window.__comsciOvLoading = true;
          const url = profId ? `/api/calendar/overrides/professor?prof_id=${encodeURIComponent(profId)}&start_date=${toIso(start)}&end_date=${toIso(end)}&_=${bust}`
                              : `/api/calendar/overrides?start_date=${toIso(start)}&end_date=${toIso(end)}&_=${bust}`;
          fetch(url, { headers: { 'Accept':'application/json' } })
            .then(r=>r.json())
            .then(data=>{
              if(data && data.success){
                const incoming = data.overrides || {};
                // Store to shared cache for next-open instant paint
                if (profId) {
                  const cacheKey = `${profId}|${toIso(start)}-${toIso(end)}`;
                  window.__ovCache = window.__ovCache || {};
                  window.__ovCache[cacheKey] = incoming;
                  try{ lsSetOv(buildLsKey('public', cacheKey), incoming, 6*3600*1000); }catch(_){ }
                }
                const prev = window.__publicOverrides || {};
                const changed = JSON.stringify(incoming) !== JSON.stringify(prev);
                window.__ovInitialized = true;
                if(changed){ window.__publicOverrides = incoming; }
                recomputeBlockedSet();
                if(window.picker) window.picker.draw();
                // Direct apply to prevent visual gap if draw is coalesced
                try { applyPublicOverridesToCalendar(); } catch(_) {}
              }
            })
            .catch(()=>{})
            .finally(()=>{ window.__comsciOvLoading = false; });
        }catch(_){}
      }
  // Expose fetcher globally so openModal can trigger immediately after prof selection
  window.__comsciFetchOverridesForMonth = fetchPublicOverridesForMonth;

      (function observeMonthNav(){
        const run = () => fetchPublicOverridesForMonth(getVisibleMonthBaseDate());
        setTimeout(run, 120);
        document.addEventListener('click', (e)=>{ const t=e.target; if(t.closest && (t.closest('.pika-prev') || t.closest('.pika-next'))) setTimeout(run, 160); });
        setInterval(run, 6000);
        window.addEventListener('focus', () => setTimeout(run, 250));
        document.addEventListener('visibilitychange', () => { if (!document.hidden) setTimeout(run, 250); });
      })();
      // Prefetch overrides on hover/focus of professor cards to warm cache before modal opens
      (function prefetchOnHover(){
        // Track in-flight override fetches so openModal can await briefly
        window.__ovPending = window.__ovPending || {}; // key -> Promise
        // Expand to prev + current + next month so adjacent grid cells paint instantly
        function monthRange(d){ const s=new Date(d.getFullYear(), d.getMonth()-1, 1); const e=new Date(d.getFullYear(), d.getMonth()+2, 0); return {s,e}; }
        function toIso(d){ return `${d.getFullYear()}-${String(d.getMonth()+1).padStart(2,'0')}-${String(d.getDate()).padStart(2,'0')}`; }
        async function prefetch(profId){
          try{
            if(!profId) return;
            const today = new Date();
            const {s,e} = monthRange(today);
            const cacheKey = `${profId}|${toIso(s)}-${toIso(e)}`;
            if(window.__ovCache && window.__ovCache[cacheKey]) return; // already cached
            if(window.__ovPending && window.__ovPending[cacheKey]) return window.__ovPending[cacheKey];
            const url = `/api/calendar/overrides/professor?prof_id=${encodeURIComponent(profId)}&start_date=${toIso(s)}&end_date=${toIso(e)}&_=${Date.now()}`;
            const p = fetch(url, { headers:{ 'Accept':'application/json' } })
              .then(r=>r.json())
              .then(data=>{ if(data && data.success){ window.__ovCache = window.__ovCache||{}; window.__ovCache[cacheKey] = data.overrides||{}; } return data; })
              .finally(()=>{ try{ delete window.__ovPending[cacheKey]; }catch(_){ } });
            window.__ovPending[cacheKey] = p;
            return p;
          }catch(_){ }
        }
        // Prefetch all visible professor cards to avoid delay on first open
        async function prefetchAllVisible(){
          try{
            const cards = Array.from(document.querySelectorAll('.profile-card'));
            const profIds = cards.map(c => c.getAttribute('data-prof-id')).filter(Boolean);
            const uniq = Array.from(new Set(profIds));
            const chunkSize = 6;
            for(let i=0;i<uniq.length;i+=chunkSize){
              const slice = uniq.slice(i, i+chunkSize);
              await Promise.all(slice.map(id => prefetch(id)));
            }
          }catch(_){ }
        }
        setTimeout(prefetchAllVisible, 150);
        setTimeout(prefetchAllVisible, 800);
        setTimeout(prefetchAllVisible, 1800);
        document.addEventListener('mouseover', (e)=>{
          const card = e.target.closest && e.target.closest('.profile-card');
          if(!card) return; const id = card.getAttribute('data-prof-id'); prefetch(id);
        });
        document.addEventListener('focusin', (e)=>{
          const card = e.target.closest && e.target.closest('.profile-card');
          if(!card) return; const id = card.getAttribute('data-prof-id'); prefetch(id);
        });
      })();
      // Pointerdown prefetch to eliminate initial delay on click
      (function pointerdownPrefetch(){
        document.addEventListener('pointerdown', (e)=>{
          const card = e.target && e.target.closest ? e.target.closest('.profile-card') : null;
          if(!card) return;
          const id = card.getAttribute('data-prof-id');
          if(!id) return;
          try {
            const today = new Date();
            // Prefetch prev + current + next month to cover adjacent grid days
            const s = new Date(today.getFullYear(), today.getMonth()-1, 1);
            const eN = new Date(today.getFullYear(), today.getMonth()+2, 0);
            const toIso = d=>`${d.getFullYear()}-${String(d.getMonth()+1).padStart(2,'0')}-${String(d.getDate()).padStart(2,'0')}`;
            const cacheKey = `${id}|${toIso(s)}-${toIso(eN)}`;
            if(window.__ovCache && window.__ovCache[cacheKey]) return;
            fetch(`/api/calendar/overrides/professor?prof_id=${encodeURIComponent(id)}&start_date=${toIso(s)}&end_date=${toIso(eN)}&_=${Date.now()}`, { headers:{ 'Accept':'application/json' } })
              .then(r=>r.json())
              .then(data=>{ if(data && data.success){ window.__ovCache = window.__ovCache||{}; window.__ovCache[cacheKey] = data.overrides||{}; } })
              .catch(()=>{});
          } catch(_) {}
        }, { passive:true });
      })();
  });

// Open modal and set professor info
async function openModal(card) {
    // Reset previous calendar selection so a date is required freshly each time
    (function resetCalendar(){
      const input = document.getElementById('calendar');
      if(input) input.value='';
  document.querySelector('.calendar-choices')?.classList.remove('has-error');
      try { if(window.picker){ window.picker.setDate(null); } } catch(e) {}
      document.querySelectorAll('.pika-table td.is-selected').forEach(td=>td.classList.remove('is-selected'));
    })();

    // Always reset the visible month to the current month when opening a professor modal
    (function resetVisibleMonth(){
      try{
        if(window.picker){
          const t=new Date();
          window.picker.gotoDate(new Date(t.getFullYear(), t.getMonth(), 1));
          window.picker.draw();
        }
      }catch(_){ }
    })();

    // Clear previously selected checkboxes, radios, and Others field
    (function resetFormSelections(){
      try{
        document.querySelectorAll('#bookingForm input[name="types[]"]').forEach(cb=>{ cb.checked = false; });
        const otherCb = document.getElementById('otherTypeCheckbox');
        const otherTxt = document.getElementById('otherTypeText');
        if(otherCb) otherCb.checked = false;
        if(otherTxt){ otherTxt.style.display='none'; otherTxt.removeAttribute('required'); otherTxt.value=''; }
        const radios = document.querySelectorAll('#bookingForm input[name="mode"]');
        radios.forEach(r=>{ r.checked=false; r.disabled=false; });
        const cont = document.querySelector('.mode-selection');
        cont && cont.querySelectorAll('label').forEach(l=>l.classList.remove('disabled'));
        // Clear any remembered user-selected mode to avoid carryover between professors
        try{ delete window.__userSelectedMode; }catch(_){ window.__userSelectedMode = undefined; }
      }catch(_){ }
    })();

    const name = card.getAttribute("data-name");
    const img = card.getAttribute("data-img");
    const profId = card.getAttribute("data-prof-id");
    const schedule = card.getAttribute("data-schedule");
    
    // Find professor in JS (pass professors data as JSON to the page)
    const prof = window.professors.find(p => p.Prof_ID == profId);
  const select = document.getElementById("modalSubjectSelect");
  select.innerHTML = "";
    
  if (prof && prof.subjects && prof.subjects.length > 0) {
    // Placeholder option (no default subject selected)
    const ph = document.createElement("option");
    ph.value = "";
  ph.textContent = "Select a Subject";
    ph.disabled = true;
    ph.selected = true;
    select.appendChild(ph);
        prof.subjects.forEach(subj => {
            const opt = document.createElement("option");
            opt.value = subj.Subject_ID;
            opt.textContent = subj.Subject_Name;
            select.appendChild(opt);
        });
    } else {
        // If professor has no subjects assigned, show a default message
        const opt = document.createElement("option");
    opt.value = "";
    opt.textContent = "No subjects assigned to this professor";
    opt.disabled = true;
    opt.selected = true;
        select.appendChild(opt);
    }

  initCustomSubjectDropdown();

  // Toggle consultation type section for General Consultation subject
  try{
    const nativeSel = document.getElementById('modalSubjectSelect');
    function isGeneralSelected(){
      const opt = nativeSel && nativeSel.options[nativeSel.selectedIndex];
      return opt && String(opt.textContent||'').trim().toLowerCase() === 'general consultation';
    }
    function toggleTypesForSubject(){
      const section = document.getElementById('consultTypeSection');
      const otherTxt = document.getElementById('otherTypeText');
      const otherCb = document.getElementById('otherTypeCheckbox');
      const typeInputs = Array.from(document.querySelectorAll('#bookingForm input[name="types[]"]'));
      const general = isGeneralSelected();
      if(general){
        typeInputs.forEach(cb=>{
          const isOthers = cb.id === 'otherTypeCheckbox';
          if(isOthers){
            cb.removeAttribute('disabled');
            cb.dataset.locked = '1';
            cb.checked = true;
            const lbl = cb.closest('label');
            if(lbl){ lbl.classList.remove('type-disabled'); }
          } else {
            cb.checked = false;
            cb.setAttribute('disabled','disabled');
            if(cb.dataset){ cb.dataset.locked = '0'; }
            const lbl = cb.closest('label');
            if(lbl){ lbl.classList.add('type-disabled'); }
          }
        });
        if(otherCb && otherTxt){
          otherCb.checked = true;
          otherTxt.style.display = 'inline-block';
          otherTxt.setAttribute('required','required');
        }
      } else {
        typeInputs.forEach(cb=>{
          cb.removeAttribute('disabled');
          if(cb.dataset){ cb.dataset.locked = '0'; }
          const lbl = cb.closest('label');
          if(lbl){ lbl.classList.remove('type-disabled'); }
        });
        if(otherCb){
          otherCb.dataset.locked = '0';
          otherCb.checked = false;
        }
        if(otherTxt){
          const show = !!(otherCb && otherCb.checked);
          otherTxt.style.display = show ? 'inline-block' : 'none';
          if(show){ otherTxt.setAttribute('required','required'); }
          else { otherTxt.removeAttribute('required'); otherTxt.value=''; }
        }
      }
    }
    nativeSel.addEventListener('change', toggleTypesForSubject);
    // Also hook custom dropdown
    document.addEventListener('click', (e)=>{
      const li = e.target && e.target.closest ? e.target.closest('#csDdList li') : null;
      if(li){ setTimeout(toggleTypesForSubject, 0); }
    });
    // Initial state
    setTimeout(toggleTypesForSubject, 0);
  }catch(_){ }

    document.getElementById("modalProfilePic").src = img;
    document.getElementById("modalProfileName").textContent = name;
    document.getElementById("modalProfId").value = profId;
    // Hydrate professor overrides BEFORE first draw so leave/suspension apply instantly like global
    try {
      // Clear any previous professor's overrides to prevent bleed-through
      window.__publicOverrides = {};
      if (typeof recomputeBlockedSet === 'function') recomputeBlockedSet();
      try { if(window.picker){ window.picker.draw(); } } catch(_) {}

      // Then assign this professor's preloaded overrides (can be empty)
      try {
        const pre = (window.__preloadedProfOverrides||{})[parseInt(profId,10)] || {};
        window.__publicOverrides = pre;
        if (typeof recomputeBlockedSet === 'function') recomputeBlockedSet();
        try { if(window.picker){ window.picker.draw(); } } catch(_) {}
      } catch(_) {}

      const base = (window.__comsciGetVisibleMonthBaseDate ? window.__comsciGetVisibleMonthBaseDate() : getVisibleMonthBaseDate());
      const toIso = d=>`${d.getFullYear()}-${String(d.getMonth()+1).padStart(2,'0')}-${String(d.getDate()).padStart(2,'0')}`;
      const start = new Date(base.getFullYear(), base.getMonth()-1, 1);
      const end = new Date(base.getFullYear(), base.getMonth()+2, 0);
      const cacheKey = `${profId}|${toIso(start)}-${toIso(end)}`;
      // Load from localStorage if present (no draw yet; we'll draw after schedule is applied)
      try { const ls = lsGetOv(buildLsKey('public', cacheKey)); if(ls){ window.__publicOverrides = ls; if(typeof recomputeBlockedSet==='function') recomputeBlockedSet(); } } catch(_) {}
      // Await any in-flight prefetch briefly; then hydrate from in-memory cache
      try { const p=(window.__ovPending||{})[cacheKey]; if(p){ await Promise.race([p, new Promise(r=>setTimeout(r,180))]); } } catch(_) {}
      if (window.__ovCache && window.__ovCache[cacheKey]){
        window.__publicOverrides = window.__ovCache[cacheKey];
        window.__ovInitialized = true;
        try { if (typeof recomputeBlockedSet === 'function') recomputeBlockedSet(); } catch(_) {}
      }
    } catch(_) {}

    // Apply schedule NOW so the first draw includes hydrated overrides
    if (window.__updateAllowedWeekdays) { try { window.__updateAllowedWeekdays(schedule); } catch(_) {} }
    // Then fetch availability (slight delay keeps UI responsive)
    if(window.__fetchAvailability){ setTimeout(()=>window.__fetchAvailability(profId),120); }
    // Fetch professor-specific overrides for the visible month (network refresh after initial cache-first)
    try { if (typeof window.__comsciFetchOverridesForMonth === 'function') window.__comsciFetchOverridesForMonth(window.__comsciGetVisibleMonthBaseDate ? window.__comsciGetVisibleMonthBaseDate() : getVisibleMonthBaseDate()); } catch(_) {}

    // Show the modal after first draw to avoid flicker
    document.getElementById("consultationModal").style.display = "flex";
    document.body.classList.add("modal-open");
    
    // Populate schedule
    const scheduleDiv = document.getElementById("modalSchedule");
  if (schedule && schedule !== 'No schedule set') {
    const scheduleLines = schedule.split('\n');
    scheduleDiv.innerHTML = scheduleLines.map(line => `<p>${line}</p>`).join('');
    if(window.__updateAllowedWeekdays){ window.__updateAllowedWeekdays(schedule); }
  } else {
    scheduleDiv.innerHTML = '<p style="color: #888;">No schedule available</p>';
    if(window.__updateAllowedWeekdays){ window.__updateAllowedWeekdays(''); }
  }

  // Disable submit if no schedule
  const submitBtn = document.querySelector('#bookingForm .submit-btn');
  if(submitBtn){
    const hasSchedule = schedule && schedule !== 'No schedule set';
    submitBtn.disabled = !hasSchedule;
    submitBtn.classList.toggle('no-schedule', !hasSchedule);
    submitBtn.title = !hasSchedule ? 'Cannot book: professor has no schedule set.' : '';
  }
  // Reset mode radios state on open (do not clear user selection if they re-open)
  const online = document.querySelector('input[name="mode"][value="online"]');
  const onsite = document.querySelector('input[name="mode"][value="onsite"]');
  if(online && onsite){
    online.disabled=false; onsite.disabled=false;
    const cont = document.querySelector('.mode-selection');
    cont && cont.querySelectorAll('label').forEach(l=>l.classList.remove('disabled'));
  }
}

// Custom dropdown (isolated)
function initCustomSubjectDropdown(){
  const wrap=document.getElementById('csSubjectDropdown');
  const trigger=document.getElementById('csDdTrigger');
  const list=document.getElementById('csDdList');
  const native=document.getElementById('modalSubjectSelect');
  if(!wrap||!trigger||!list||!native) return;
  list.innerHTML='';
  Array.from(native.options).forEach((o,i)=>{
    const li=document.createElement('li');
    li.textContent=o.text; if(i===native.selectedIndex) li.classList.add('active');
    li.addEventListener('click',()=>{ native.selectedIndex=i; updateCsTrigger(); wrap.classList.remove('open'); Array.from(list.children).forEach(c=>c.classList.remove('active')); li.classList.add('active'); });
    list.appendChild(li);
  });
  updateCsTrigger();
  trigger.onclick=()=>{ wrap.classList.toggle('open'); };
  document.addEventListener('click',e=>{ if(!wrap.contains(e.target)) wrap.classList.remove('open'); });
  function updateCsTrigger(){ 
    const sel=native.options[native.selectedIndex]; 
    trigger.textContent=(sel?sel.text:'Select a Subject'); 
  }
}

function resetBookingFormState(){
  try{
    const form = document.getElementById('bookingForm');
    if(form) form.reset();
    const input = document.getElementById('calendar');
    if(input) input.value='';
    try { if(window.picker){ window.picker.setDate(null); } } catch(_){ }
    document.querySelectorAll('.pika-table td.is-selected').forEach(td=>td.classList.remove('is-selected'));
    document.getElementById('modalSubjectSelect')?.classList.remove('input-error');
    document.getElementById('consultTypeSection')?.classList.remove('field-error');
    document.querySelector('.mode-choices')?.classList.remove('field-error');
    document.querySelector('.calendar-choices')?.classList.remove('field-error');
    document.getElementById('otherTypeText')?.classList.remove('input-error');
    const section = document.getElementById('consultTypeSection');
    document.querySelectorAll('#bookingForm input[name="types[]"]').forEach(cb=>{
      cb.removeAttribute('disabled');
      if(cb.dataset){ cb.dataset.locked = '0'; }
      const lbl = cb.closest('label');
      if(lbl){ lbl.classList.remove('type-disabled'); }
    });
    const otherTxt = document.getElementById('otherTypeText');
    if(otherTxt){ otherTxt.style.display='none'; otherTxt.removeAttribute('required'); otherTxt.value=''; }
    const radios = document.querySelectorAll('#bookingForm input[name="mode"]');
    radios.forEach(r=>{ r.checked=false; r.disabled=false; });
    const cont = document.querySelector('.mode-selection');
    cont && cont.querySelectorAll('label').forEach(l=>l.classList.remove('disabled'));
    try{ delete window.__userSelectedMode; }catch(_){ window.__userSelectedMode = undefined; }
  }catch(_){ }
}

function isBookingFormDirty(){
  try{
    const form = document.getElementById('bookingForm');
    if(!form) return false;
    const subject = form.querySelector('#modalSubjectSelect');
    if(subject && subject.value) return true;
    if(form.querySelector('input[name="types[]"]:checked')) return true;
    const otherTxt = document.getElementById('otherTypeText');
    if(otherTxt && otherTxt.value.trim()) return true;
    if(form.querySelector('input[name="mode"]:checked')) return true;
    const dateInput = document.getElementById('calendar');
    if(dateInput && dateInput.value.trim()) return true;
  }catch(_){ }
  return false;
}

async function closeModal(force){
  const modal = document.getElementById('consultationModal');
  if(!modal) return;
  const shouldForce = force === true;
  if(!shouldForce && isBookingFormDirty()){
    try{
      const confirmLeave = await studentConfirm(
        'Discard booking?',
        "You're about to leave this booking form. Any details you've entered will be cleared. Do you want to continue?"
      );
      if(!confirmLeave) return;
    }catch(_){ return; }
  }
  modal.style.display = 'none';
  document.body.classList.remove('modal-open');
  resetBookingFormState();
}

// Optional: Close modal when clicking outside modal-content
window.onclick = function(event) {
    const modal = document.getElementById("consultationModal");
    if (event.target === modal) {
        closeModal();
    }
}

document.addEventListener('DOMContentLoaded', function() {
    const otherCheckbox = document.getElementById('otherTypeCheckbox');
    const otherText = document.getElementById('otherTypeText');
    if (otherCheckbox && otherText) {
        otherCheckbox.addEventListener('change', function() {
            otherText.style.display = this.checked ? 'inline-block' : 'none';
            if (this.checked) {
              otherText.setAttribute('required','required');
            } else {
              otherText.removeAttribute('required');
              otherText.value = '';
            }
        });
    }

    // Ensure only one consultation type is selected at a time (with general consultation override)
    try{
      const typeInputs = Array.from(document.querySelectorAll('#bookingForm input[name="types[]"]'));
      function onTypeChange(e){
        const target = e.target;
        if(target.name !== 'types[]') return;
        if(target.dataset && target.dataset.locked === '1'){
          target.checked = true;
        }
        typeInputs.forEach(cb => {
          if(cb === target) return;
          if(cb.dataset && cb.dataset.locked === '1'){
            cb.checked = true;
            return;
          }
          cb.checked = false;
        });
        const otherCb = document.getElementById('otherTypeCheckbox');
        const otherTxt = document.getElementById('otherTypeText');
        const nativeSel = document.getElementById('modalSubjectSelect');
        const sel = nativeSel && nativeSel.options[nativeSel.selectedIndex];
        const general = !!(sel && String(sel.textContent||'').trim().toLowerCase() === 'general consultation');
        if(otherTxt){
          const show = general || (otherCb && otherCb.checked);
          otherTxt.style.display = show ? 'inline-block' : 'none';
          if(show){ otherTxt.setAttribute('required','required'); }
          else { otherTxt.removeAttribute('required'); otherTxt.value=''; }
        }
        if(general && otherCb){
          otherCb.checked = true;
        }
      }
      typeInputs.forEach(cb => cb.addEventListener('change', onTypeChange));
    }catch(_){ /* no-op */ }
});

// Client-side validation to keep modal open (prevent submit if invalid)
const bookingForm = document.getElementById('bookingForm');
if(bookingForm){
  function clearFieldErrors(){
    document.getElementById('modalSubjectSelect')?.classList.remove('input-error');
    document.getElementById('consultTypeSection')?.classList.remove('field-error');
  document.querySelector('.mode-choices')?.classList.remove('field-error');
  document.querySelector('.calendar-choices')?.classList.remove('field-error');
    document.getElementById('otherTypeText')?.classList.remove('input-error');
  }

  function validateAndMark(){
    const errs = [];
    const profId = document.getElementById('modalProfId').value.trim();
    if(!profId) errs.push('Professor not selected.');
    const subjectSel = document.getElementById('modalSubjectSelect');
    if(!subjectSel || !subjectSel.value){ errs.push('Please select a subject.'); subjectSel?.classList.add('input-error'); }
    const selText = subjectSel.options[subjectSel.selectedIndex]?.text?.trim().toLowerCase() || '';
    const isGeneral = selText === 'general consultation';
    const typesChecked = bookingForm.querySelectorAll('input[name="types[]"]:checked').length;
    if(!isGeneral && typesChecked === 0){ errs.push('Please select at least one consultation type.'); document.getElementById('consultTypeSection')?.classList.add('field-error'); }
  const modeInputs = bookingForm.querySelectorAll('input[name="mode"]');
  const selected = Array.from(modeInputs).find(i=>i.checked);
  if(!selected){ errs.push('Please select consultation mode (Online or Onsite).'); document.querySelector('.mode-choices')?.classList.add('field-error'); }
    const dateInput = document.getElementById('calendar');
  const hasSelectedCell = document.querySelector('.pika-table td.is-selected');
  if(!dateInput.value.trim() || !hasSelectedCell){ errs.push('Please select your desired consultation date.'); document.querySelector('.calendar-choices')?.classList.add('field-error'); }
    if(window.__availabilityCache){
      const key = dateInput.value.replace(/,/g,'');
      const rec = window.__availabilityCache[key];
      if(rec && rec.remaining <= 0) errs.push('Selected date is already fully booked.');
    }
  const otherCb = document.getElementById('otherTypeCheckbox');
  const otherTxt = document.getElementById('otherTypeText');
  if(isGeneral){
    if(!otherTxt || !otherTxt.value.trim()){ errs.push('Please describe your concern in the Others field.'); otherTxt?.classList.add('input-error'); }
  } else if(otherCb && otherCb.checked && !otherTxt.value.trim()){
    errs.push('Please specify the consultation type in the Others field.');
    otherTxt?.classList.add('input-error');
  }
    if(window.__availabilityCache){
      const key = dateInput.value.replace(/,/g,'');
      const rec = window.__availabilityCache[key];
      if(rec && rec.mode && selected && selected.value !== rec.mode){ errs.push(`This date is locked to ${rec.mode}.`); }
    }
    return errs;
  }

  // Clear highlight when user interacts
  bookingForm.addEventListener('change', (e)=>{
    const t=e.target;
    if(t.id==='modalSubjectSelect') t.classList.remove('input-error');
    if(t.name==='types[]') document.getElementById('consultTypeSection')?.classList.remove('field-error');
  if(t.name==='mode') document.querySelector('.mode-choices')?.classList.remove('field-error');
    if(t.id==='otherTypeText') t.classList.remove('input-error');
  }, true);

  bookingForm.addEventListener('submit', async function(e){
    e.preventDefault();
    clearFieldErrors();
    const errs = validateAndMark();
    if(errs.length){ showNotification(errs[0], true); return; }
    clearFieldErrors();
    // Confirm details before final submission
    const ok = await studentConfirm(
      'Confirm Booking',
      'Are you sure the details are correct? You have 1 hour to cancel; if the professor has already approved it, it cannot be cancelled.'
    );
    if(!ok) return;
    const submitBtn = bookingForm.querySelector('.submit-btn');
    if(submitBtn){ submitBtn.disabled = true; }
    const overlay = document.getElementById('submitOverlay');
    const MIN_LOADING_MS = 2000; // match login overlay feel
    const showStart = Date.now();
    if(overlay){ overlay.classList.add('active'); }
    try {
      const fd = new FormData(bookingForm);
      const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
      const res = await fetch(bookingForm.action, { method:'POST', headers:{ 'X-CSRF-TOKEN': token, 'Accept':'application/json' }, body: fd });
      const contentType = res.headers.get('content-type')||'';
      if(res.status === 422){
        let msg = 'Validation error.';
        if(contentType.includes('application/json')){
          const data = await res.json();
          if(data.errors){
            const first = Object.values(data.errors)[0];
            if(first && first[0]) msg = first[0];
          } else if(data.message){ msg = data.message; }
        }
        const normalizedMsg = String(msg || '').toLowerCase();
        if(submitBtn){ submitBtn.disabled = false; }
        if(overlay){ overlay.classList.remove('active'); }
        if(normalizedMsg.includes('already have a consultation booked')){
          await studentAlert('Booking Blocked', msg);
        } else {
          showNotification(msg, true);
        }
        return;
      }
      if(!res.ok){ showNotification('Server error. Please try again.', true); return; }
      if(contentType.includes('application/json')){
        const data = await res.json();
        if(data.success){
          showNotification(data.message || 'Consultation booked successfully.', false);
          await closeModal(true);
          bookingForm.reset();
        } else {
          showNotification(data.message || 'Unexpected response.', true);
        }
      } else {
        showNotification('Consultation booked successfully.', false);
        await closeModal(true);
        bookingForm.reset();
      }
    } catch(ex){
      showNotification('Network error. Please try again.', true);
    } finally {
      if(submitBtn){ submitBtn.disabled = false; }
      if(overlay){
        const elapsed = Date.now() - showStart;
        const delay = Math.max(0, MIN_LOADING_MS - elapsed);
        setTimeout(()=> overlay.classList.remove('active'), delay);
      }
    }
  });
}

window.professors = @json($professors);
// Preloaded professor leave dates for prev+current+next month window
window.__preloadedProfOverrides = @json($preloadedOverrides ?? []);

// === Secure client-side search (defensive) ===
// This search is purely client-side (DOM filtering). We still defensively
// sanitize user input to remove characters commonly used in injection payloads
// (quotes, semicolons, SQL comment markers, block comments, angle brackets).
(function secureSearch(){
  const input = document.getElementById('searchInput');
  if(!input) return;
  const MAX_LEN = 50;
  function sanitize(raw){
    if(!raw) return '';
    return raw
      .replace(/\/*.*?\*\//g,'')   // strip block comments
      .replace(/--+/g,' ')            // collapse SQL line comment openers
      .replace(/[;`'"<>]/g,' ')      // remove dangerous punctuation
      .slice(0,MAX_LEN);
  }
  function filter(){
    const safe = sanitize(input.value);
    const term = safe.toLowerCase();
    const norm = term.replace(/\s+/g,' ').trim(); // normalized for matching, but do not change UI
    const cards = document.querySelectorAll('.profile-card');
    let visible = 0;
    cards.forEach(c=>{
      const name = (c.dataset.name||c.textContent||'').toLowerCase();
      const nameNorm = name.replace(/\s+/g,' ').trim();
      const show = norm === '' || nameNorm.includes(norm);
      c.style.display = show ? '' : 'none';
      if(show) visible++;
    });
    const msg = document.getElementById('noResults');
    if(msg){ msg.style.display = (norm !== '' && visible === 0) ? 'block' : 'none'; }
  }
  input.addEventListener('input', filter);
})();

// === Chatbot (dashboard parity) ===
function toggleChat() {
    const overlay = document.getElementById('chatOverlay');
    overlay.classList.toggle('open');
    const isOpen = overlay.classList.contains('open');
    document.body.classList.toggle('chat-open', isOpen);
    const bell = document.getElementById('mobileNotificationBell');
    if (bell) {
      if (isOpen) { bell.style.zIndex='0'; bell.style.pointerEvents='none'; bell.style.opacity='0'; }
      else { bell.style.zIndex=''; bell.style.pointerEvents=''; bell.style.opacity=''; }
    }
}

const csrfToken = document
    .querySelector('meta[name="csrf-token"]')
    .getAttribute("content");
const chatForm = document.getElementById("chatForm");
const input = document.getElementById("message");
if(input){
  input.setAttribute('maxlength','250');
  input.setAttribute('autocomplete','off');
  input.setAttribute('spellcheck','false');
}
const chatBody = document.getElementById("chatBody");
const quickReplies = document.getElementById('quickReplies');
const quickRepliesToggle = document.getElementById('quickRepliesToggle');

function sendQuick(text){ if(!text) return; input.value = text; chatForm.dispatchEvent(new Event('submit')); }
quickReplies?.addEventListener('click', (e)=>{ const btn=e.target.closest('.quick-reply'); if(btn){ sendQuick(btn.dataset.message); } });
quickRepliesToggle?.addEventListener('click', ()=>{ if(quickReplies){ quickReplies.style.display='flex'; quickRepliesToggle.style.display='none'; } });

function sanitize(raw){
  if(!raw) return '';
  return raw
    .replace(/\/*.*?\*\//g,'')
    .replace(/--+/g,' ')
    .replace(/[;`'"<>]/g,' ')
    .replace(/\s+/g,' ')
    .trim()
    .slice(0,250);
}

chatForm.addEventListener("submit", async function (e) {
    e.preventDefault();
    const text = sanitize(input.value);
    if (!text) return;

    // hide quick replies on first interaction
    if (quickReplies && quickReplies.style.display !== 'none') {
      quickReplies.style.display = 'none';
      if (quickRepliesToggle) quickRepliesToggle.style.display = 'flex';
    }

    const um = document.createElement("div");
    um.classList.add("message", "user");
    um.innerText = text;
    chatBody.appendChild(um);
    chatBody.scrollTop = chatBody.scrollHeight;
    input.value = "";

    const res = await fetch("/chat", {
        method: "POST",
        credentials: "same-origin",
        headers: {
            Accept: "application/json",
            "Content-Type": "application/json",
            "X-CSRF-TOKEN": csrfToken,
        },
        body: JSON.stringify({ message: text }),
    });

    if (!res.ok) {
        const err = await res.json();
        const bm = document.createElement("div");
        bm.classList.add("message", "bot");
        bm.innerText = err.message || "Server error.";
        chatBody.appendChild(bm);
        return;
    }

    const { reply } = await res.json();
    const bm = document.createElement("div");
    bm.classList.add("message", "bot");
    bm.innerText = reply;
    chatBody.appendChild(bm);
    chatBody.scrollTop = chatBody.scrollHeight;
});
  </script>

  <!-- Notification Div -->
  <div id="notification" class="notification">
    <span id="notification-message"></span>
    <button onclick="hideNotification()" class="close-btn">&times;</button>
  </div>

  <script>
    function showNotification(message, isError = false) {
      const notif = document.getElementById('notification');
      notif.classList.toggle('error', isError);
      document.getElementById('notification-message').textContent = message;
      notif.style.display = 'flex';
      setTimeout(hideNotification, 4000);
    }
    
    function hideNotification() {
      document.getElementById('notification').style.display = 'none';
    }
  </script>

  <!-- Handle Laravel session messages -->
  @if (session('success'))
    <script>
      document.addEventListener('DOMContentLoaded', function() {
        showNotification(@json(session('success')), false);
      });
    </script>
  @endif

  @if (session('error'))
    <script>
      document.addEventListener('DOMContentLoaded', function() {
        showNotification(@json(session('error')), true);
      });
    </script>
  @endif

  @if ($errors->any())
    <script>
      document.addEventListener('DOMContentLoaded', function() {
        showNotification(@json($errors->first()), true);
      });
    </script>
  @endif
  <!-- Global submitting overlay covering entire page including navbar -->
  <div class="auth-loading-overlay" id="submitOverlay">
    <div class="auth-loading-spinner"></div>
    <div class="auth-loading-text">Submitting…</div>
  </div>
</body>
<script src="https://js.pusher.com/7.0/pusher.min.js"></script>
<script>
  // Student confirmation matching consultation log UI (confirm-modal.css)
  function studentConfirm(title, message){
    return new Promise(resolve=>{
      const overlay=document.createElement('div'); overlay.className='confirm-overlay'; overlay.id = 'studentConfirmOverlayInline';
      const dlg=document.createElement('div'); dlg.className='confirm-modal student-confirm'; dlg.setAttribute('role','dialog'); dlg.setAttribute('aria-modal','true'); dlg.setAttribute('aria-labelledby','studentConfirmTitleInline');
      dlg.innerHTML = `
        <div class="confirm-header">
          <i class='bx bx-help-circle'></i>
          <div id="studentConfirmTitleInline">${title || 'Please confirm'}</div>
        </div>
        <div class="confirm-body">${message}</div>
        <div class="confirm-actions">
          <button id="dlgOk" class="btn-confirm-green">OK</button>
          <button id="dlgCancel" class="btn-cancel-red">Cancel</button>
        </div>`;
      overlay.appendChild(dlg);
      document.body.appendChild(overlay);
      requestAnimationFrame(()=> overlay.classList.add('active'));
      const onKey=(e)=>{
        if(e.key==='Escape'){ e.preventDefault(); close(false); }
        if(e.key==='Tab'){
          const focusables=dlg.querySelectorAll('button,[href],input,select,textarea,[tabindex]:not([tabindex="-1"])');
          if(focusables.length){
            const first=focusables[0]; const last=focusables[focusables.length-1];
            if(e.shiftKey && document.activeElement===first){ e.preventDefault(); last.focus(); }
            else if(!e.shiftKey && document.activeElement===last){ e.preventDefault(); first.focus(); }
          } else { e.preventDefault(); }
        }
      };

      function studentAlert(title, message){
        return new Promise(resolve=>{
          const overlay=document.createElement('div'); overlay.className='confirm-overlay'; overlay.id='studentAlertOverlayInline';
          const dlg=document.createElement('div'); dlg.className='confirm-modal student-alert'; dlg.setAttribute('role','dialog'); dlg.setAttribute('aria-modal','true'); dlg.setAttribute('aria-labelledby','studentAlertTitleInline');
          dlg.innerHTML = `
            <div class="confirm-header">
              <i class='bx bx-error'></i>
              <div id="studentAlertTitleInline">${title || 'Notice'}</div>
            </div>
            <div class="confirm-body">${message}</div>
            <div class="confirm-actions">
              <button id="alertOk" class="btn-confirm-green">OK</button>
            </div>`;
          overlay.appendChild(dlg);
          document.body.appendChild(overlay);
          requestAnimationFrame(()=> overlay.classList.add('active'));
          function cleanup(){ document.removeEventListener('keydown', onKey); overlay.classList.remove('active'); setTimeout(()=> overlay.remove(), 150); }
          function close(){ cleanup(); resolve(); }
          function onKey(e){ if(e.key==='Escape' || e.key==='Enter'){ e.preventDefault(); close(); } }
          document.addEventListener('keydown', onKey);
          overlay.addEventListener('click', (e)=>{ if(!dlg.contains(e.target)) close(); });
          const okBtn = dlg.querySelector('#alertOk');
          okBtn.onclick = close;
          setTimeout(()=> okBtn.focus(), 0);
        });
      }
      const cleanup=()=>{ document.removeEventListener('keydown', onKey); overlay.classList.remove('active'); setTimeout(()=> overlay.remove(), 150); };
      const close=(v)=>{ cleanup(); resolve(v); };
      document.addEventListener('keydown', onKey);
      const okBtn = dlg.querySelector('#dlgOk');
      const cancelBtn = dlg.querySelector('#dlgCancel');
      okBtn.onclick   =()=> close(true);
      cancelBtn.onclick =()=> close(false);
      overlay.addEventListener('click', (e)=>{ const m = dlg; if(m && !m.contains(e.target)) close(false); });
      setTimeout(()=> okBtn.focus(), 0);
    });
  }

  (function(){
    const pusher = new Pusher('{{ config('broadcasting.connections.pusher.key') }}', {cluster: '{{ config('broadcasting.connections.pusher.options.cluster') }}'});
    const channel = pusher.subscribe('professors.dept.2'); // Dept_ID 2 for ComSci
    const fallbackAvatar = @json(asset('images/dprof.jpg'));

    function buildCard(data){
      const grid = document.querySelector('.profile-cards-grid');
      if(!grid) return;
      // Avoid duplicates
      if(grid.querySelector('[data-prof-id="'+data.Prof_ID+'"]')) return;
      const div = document.createElement('div');
      div.className='profile-card';
      div.setAttribute('onclick','openModal(this)');
      div.dataset.name = data.Name;
        const imgPath = data.profile_photo_url || (data.profile_picture ? `/storage/${data.profile_picture}` : fallbackAvatar);
      div.dataset.img = imgPath;
      div.dataset.profId = data.Prof_ID;
      div.dataset.profId = data.Prof_ID;
      div.setAttribute('data-prof-id', data.Prof_ID);
      div.dataset.schedule = data.Schedule || 'No schedule set';
      /* Width managed by responsive CSS grid */
      div.innerHTML = `<img src="${imgPath}" alt="Profile Picture"><div class="profile-name">${data.Name}</div>`;
      grid.prepend(div); // put newest first
    }

    channel.bind('ProfessorAdded', function(data){ buildCard(data); });
    channel.bind('ProfessorUpdated', function(data){
      const card = document.querySelector('[data-prof-id="'+data.Prof_ID+'"]');
      if(card){
        card.dataset.name = data.Name;
        card.dataset.schedule = data.Schedule || 'No schedule set';
      const imgPath = data.profile_photo_url || (data.profile_picture ? `/storage/${data.profile_picture}` : fallbackAvatar);
        card.dataset.img = imgPath;
        card.querySelector('.profile-name').textContent = data.Name;
        const imgEl = card.querySelector('img'); if(imgEl) imgEl.src = imgPath;
      } else { buildCard(data); }
    });
    channel.bind('ProfessorDeleted', function(data){
      const card = document.querySelector('[data-prof-id="'+data.Prof_ID+'"]');
      if(card) card.remove();
    });
  })();
</script>
</html>
