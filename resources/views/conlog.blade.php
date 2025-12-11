<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>Consultation Log</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
  <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
  <link rel="stylesheet" href="{{ asset('css/conlog.css') }}">
  <style>
    .completion-review-overlay {
      position: fixed;
      inset: 0;
      background: rgba(0, 0, 0, 0.6);
      display: none;
      align-items: center;
      justify-content: center;
      z-index: 1200;
      padding: 20px;
    }

    .completion-review-modal {
      background: #ffffff;
      border-radius: 20px;
      width: 100%;
      max-width: 420px;
      box-shadow: 0 24px 60px rgba(12, 34, 26, 0.28);
      overflow: hidden;
      display: flex;
      flex-direction: column;
    }

    .completion-review-modal .modal-header {
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 20px 24px 16px;
      background: #0e2f27;
      color: #f6fffb;
    }

    .completion-review-modal .modal-header .title {
      display: flex;
      align-items: center;
      gap: 10px;
      font-weight: 600;
      font-size: 16px;
      letter-spacing: 0.01em;
    }

    .completion-review-close {
      background: none;
      border: none;
      color: inherit;
      font-size: 20px;
      cursor: pointer;
      opacity: 0.75;
      transition: opacity 0.2s ease;
    }

    .completion-review-close:hover {
      opacity: 1;
    }

    .completion-review-modal .modal-body {
      padding: 22px 24px 0;
      color: #1f2a37;
      font-size: 14px;
      line-height: 1.6;
    }

    .completion-review-remarks {
      background: #ffffff;
      border: 1px solid #e2e8f0;
      border-radius: 12px;
      padding: 14px 16px;
      margin-bottom: 18px;
      color: #12372a;
    }

    .completion-review-remarks strong {
      display: block;
      font-size: 13px;
      letter-spacing: 0.04em;
      text-transform: uppercase;
      color: #0d3b2e;
      margin-bottom: 6px;
    }

    .completion-review-remarks p {
      margin: 0;
      white-space: pre-wrap;
    }

    .completion-review-meta {
      font-size: 12px;
      color: #64748b;
      margin-bottom: 18px;
    }

    .completion-review-error {
      display: none;
      margin: 0 0 22px;
      background: #fef2f2;
      color: #b91c1c;
      border: 1px solid #fecaca;
      border-radius: 10px;
      padding: 10px 12px;
      font-size: 13px;
    }

    .completion-review-actions {
      display: flex;
      gap: 12px;
      padding: 18px 24px 24px;
      background: #f8fafc;
    }

    .completion-review-actions button {
      flex: 1;
      border-radius: 999px;
      border: 2px solid transparent;
      padding: 12px 0;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.2s ease;
    }

    .completion-review-actions .btn-outline {
      background: #ffffff;
      color: #b91c1c;
      border-color: #f8d0d0;
    }

    .completion-review-actions .btn-outline:hover {
      border-color: #b91c1c;
      background: #fff5f5;
    }

    .completion-review-actions .btn-solid {
      background: #1f7a67;
      color: #ffffff;
    }

    .completion-review-actions .btn-solid:hover {
      background: #155d4c;
      transform: translateY(-1px);
    }

    @media (max-width: 480px) {
      .completion-review-modal {
        border-radius: 16px;
      }

      .completion-review-actions {
        flex-direction: column;
      }
    }
  </style>
</head>
<body>
  @include('components.navbar')

  @php
    $fixedTypes = [
      'Tutoring',
      'Grade Consultation',
      'Missed Activities',
      'Special Quiz or Exam',
      'Capstone Consultation'
    ];
    // Filter out cancelled bookings for initial render + subject list
    $bookingsFiltered = collect($bookings ?? [])->filter(function($b){
      return strtolower($b->Status ?? '') !== 'cancelled';
    })->values();
  @endphp

  <div class="main-content">
    <div class="header">
      <h1>Consultation Log</h1>
    </div>
    @php
  // Build unique "Subject" filter values based on the activity type column so the dropdown mirrors the table
  $subjects = collect($bookingsFiltered ?? [])->pluck('type')->filter(fn($s)=>filled($s))
       ->map(fn($s)=>trim($s))->unique()->sort()->values();
      // Build unique types list for type filter based on actual records (cancelled excluded already)
      $types = collect($bookingsFiltered ?? [])->pluck('type')->filter(fn($t)=>filled($t))
                 ->map(fn($t)=>trim($t))->unique()->sort()->values();
    @endphp

    <div class="search-container">
      <input type="text" id="searchInput" placeholder="Search..." style="flex:1;"
        autocomplete="off" spellcheck="false" maxlength="100"
        pattern="[A-Za-z0-9 .,@_-]{0,100}" aria-label="Search consultations">
      <!-- Mobile-only: Filters button on the right side of the search bar -->
      <button type="button" class="filters-btn" id="openFiltersBtn" aria-label="Open filters" title="Filters">
        <i class='bx bx-slider-alt'></i>
      </button>
      <div class="filter-group-horizontal">
        <select id="subjectFilter" class="filter-select" aria-label="Subject filter">
          <option value="">All Subjects</option>
          @foreach($subjects as $s)
            <option value="{{ $s }}">{{ $s }}</option>
          @endforeach
        </select>
      </div>
      <div class="filter-group-horizontal">
        <select id="typeFilter" class="filter-select" aria-label="Type filter">
          <option value="">All Types</option>
          @foreach($types as $t)
            <option value="{{ $t }}">{{ $t }}</option>
          @endforeach
          @if(!in_array('Others', $types->toArray()))
            <option value="Others">Others</option>
          @endif
        </select>
        <!-- Custom filter dropdown (mobile) -->
        <div id="typeFilterDropdown" class="cs-dd" style="display:none;">
          <button type="button" class="cs-dd-trigger" id="typeFilterTrigger">All Types</button>
          <ul class="cs-dd-list" id="typeFilterList"></ul>
        </div>
      </div>
      <div class="filter-group-horizontal page-size-group" style="margin-left:auto">
        <select id="pageSize" class="filter-select" aria-label="Items per page" style="width:92px">
          <option value="5">5</option>
          <option value="10" selected>10</option>
          <option value="25">25</option>
          <option value="50">50</option>
          <option value="100">100</option>
        </select>
        <span class="filter-label-inline items-per-page-label">items per page</span>
      </div>
    </div>

    <div class="table-container">
      <div class="table">
        <!-- Header Row -->
        <div class="table-row table-header" id="conlogHeader">
          <div class="table-cell">No.</div>
          <div class="table-cell sort-header" data-sort="instructor" role="button" tabindex="0" aria-label="Sort by instructor">Instructor <span class="sort-icon"></span></div>
          <div class="table-cell sort-header" data-sort="subject" role="button" tabindex="0" aria-label="Sort by subject">Subject <span class="sort-icon"></span></div>
          <div class="table-cell sort-header" data-sort="date" role="button" tabindex="0" aria-label="Sort by date">Date <span class="sort-icon"></span></div>
          <div class="table-cell sort-header" data-sort="type" role="button" tabindex="0" aria-label="Sort by type">Type <span class="sort-icon"></span></div>
          <div class="table-cell sort-header" data-sort="mode" role="button" tabindex="0" aria-label="Sort by mode">Mode <span class="sort-icon"></span></div>
          <div class="table-cell sort-header" data-sort="booked" role="button" tabindex="0" aria-label="Sort by booked at">Booked At <span class="sort-icon"></span></div>
          <div class="table-cell sort-header" data-sort="status" role="button" tabindex="0" aria-label="Sort by status">Status <span class="sort-icon"></span></div>
          <div class="table-cell" aria-hidden="true" style="width:100px">Action</div>
        </div>
    
        <!-- Dynamic Data Rows -->
  @forelse($bookingsFiltered as $b)
  @php
    $statusLower = strtolower($b->Status ?? '');
    $statusLabels = [
      'completion_pending' => 'Completion Pending',
      'completion_declined' => 'Completion Declined',
    ];
    $statusLabel = $statusLabels[$statusLower] ?? ucfirst($b->Status);
    $completionReason = trim((string) ($b->completion_reason ?? ''));
    try {
      $completionRequestedAt = $b->completion_requested_at
        ? \Carbon\Carbon::parse($b->completion_requested_at, 'Asia/Manila')->format('Y-m-d H:i:s')
        : '';
    } catch (\Throwable $_) {
      $completionRequestedAt = '';
    }
    try {
      $completionReviewedAt = $b->completion_reviewed_at
        ? \Carbon\Carbon::parse($b->completion_reviewed_at, 'Asia/Manila')->format('Y-m-d H:i:s')
        : '';
    } catch (\Throwable $_) {
      $completionReviewedAt = '';
    }
  @endphp
        <div class="table-row"
             data-instructor="{{ strtolower($b->Professor) }}"
             data-subject="{{ strtolower($b->subject) }}"
             data-date="{{ \Carbon\Carbon::parse($b->Booking_Date)->format('Y-m-d') }}"
             data-date-ts="{{ \Carbon\Carbon::parse($b->Booking_Date)->timestamp }}"
             data-type="{{ strtolower($b->type) }}"
             data-mode="{{ strtolower($b->Mode) }}"
             data-booked="{{ \Carbon\Carbon::parse($b->Created_At)->timezone('Asia/Manila')->format('Y-m-d H:i:s') }}"
             data-booked-ts="{{ \Carbon\Carbon::parse($b->Created_At)->timezone('Asia/Manila')->timestamp }}"
             data-status="{{ $statusLower }}"
             data-completion-reason="{{ e($completionReason) }}"
             data-completion-requested="{{ $completionRequestedAt }}"
             data-completion-reviewed="{{ $completionReviewedAt }}"
             data-completion-response="{{ e($b->completion_student_response ?? '') }}"
             data-completion-comment="{{ e($b->completion_student_comment ?? '') }}"
             data-matched="1"
        >
          <div class="table-cell" data-label="No." data-booking-id="{{ $b->Booking_ID ?? '' }}">{{ $loop->iteration }}</div>
          <div class="table-cell instructor-cell" data-label="Instructor">{{ $b->Professor }}</div>
          <div class="table-cell" data-label="Subject">{{ $b->subject }}</div>
          <div class="table-cell" data-label="Date">{{ \Carbon\Carbon::parse($b->Booking_Date)->format('D, M d Y') }}</div>
          <div class="table-cell" data-label="Type">{{ $b->type }}</div>
          <div class="table-cell" data-label="Mode">{{ ucfirst($b->Mode) }}</div>
          <div class="table-cell" data-label="Booked At">{{ \Carbon\Carbon::parse($b->Created_At)->timezone('Asia/Manila')->format('M d Y h:i A') }}</div>
          <div class="table-cell" data-label="Status" @if($completionReason) title="{{ 'Remarks: '.$completionReason }}" @endif>{{ $statusLabel }}</div>
          <div class="table-cell" data-label="Action" style="width: 100px;">
            <div class="action-btn-group" style="display:flex;gap:8px;"><!-- buttons inserted by JS --></div>
          </div>
        </div>

      @empty
        <div class="table-row no-results-row">
          <div class="table-cell" style="text-align:center;color:#666;font-style:italic;">No Consultations Found.</div>
        </div>
      @endforelse
  <!-- Spacer removed: use CSS margins for layout spacing -->
    
      </div>
    </div>

    <!-- Pagination controls -->
    <div class="pagination-bar">
      <div class="pagination-right">
        <div id="paginationControls" class="pagination"></div>
      </div>
    </div>

    <!-- Bottom spacer to ensure pagination isn't overlapped by fixed chat button (mobile) -->
    <div class="bottom-safe-space" aria-hidden="true"></div>

    <!-- ITIS-style notification (injected via JS functions below) -->
    <div id="notification" class="notification" style="display:none;">
      <span id="notification-message"></span>
      <button onclick="hideNotification()" class="close-btn" aria-label="Close">&times;</button>
    </div>

    <!-- Mobile Filters Overlay -->
    <div class="filters-overlay" id="filtersOverlay" aria-hidden="true">
      <div class="filters-drawer" role="dialog" aria-modal="true" aria-labelledby="filtersTitle">
        <div class="filters-drawer-header">
          <h2 id="filtersTitle">Filters</h2>
          <button type="button" class="filters-close" id="closeFiltersBtn" aria-label="Close">×</button>
        </div>
        <div class="filters-drawer-body">
          <div class="filter-group">
            <label class="filter-label" for="subjectFilterMobile">Subject</label>
            <select id="subjectFilterMobile" class="filter-select" aria-label="Subject (mobile)">
              <option value="">All Subjects</option>
              @foreach($subjects as $s)
                <option value="{{ $s }}">{{ $s }}</option>
              @endforeach
            </select>
          </div>
          <div class="filter-group">
            <label class="filter-label" for="typeFilterMobile">Type</label>
            <select id="typeFilterMobile" class="filter-select" aria-label="Type (mobile)">
              <option value="">All Types</option>
              @foreach($types as $t)
                <option value="{{ $t }}">{{ $t }}</option>
              @endforeach
              @if(!in_array('Others', $types->toArray()))
                <option value="Others">Others</option>
              @endif
            </select>
          </div>
          
        </div>
        <div class="filters-drawer-footer">
          <button type="button" class="btn-reset" id="resetFiltersBtn">Reset</button>
          <button type="button" class="btn-apply" id="applyFiltersBtn">Apply</button>
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
  </div>

  <script src="{{ asset('js/ccit.js') }}"></script>
  <script src="https://js.pusher.com/7.0/pusher.min.js"></script>
  
  <!-- Student Themed Confirm Modal -->
  <div class="confirm-overlay" id="studentConfirmOverlay" aria-hidden="true" style="display:none;">
    <div class="confirm-modal student-confirm" role="dialog" aria-modal="true" aria-labelledby="studentConfirmTitle">
      <div class="confirm-header">
        <i class='bx bx-help-circle'></i>
        <div id="studentConfirmTitle">Please confirm</div>
      </div>
      <div class="confirm-body">
        <div id="studentConfirmMessage">Are you sure?</div>
      </div>
      <div class="confirm-actions">
        <button type="button" class="btn-confirm-green" id="studentConfirmOk">OK</button>
        <button type="button" class="btn-cancel-red" id="studentConfirmCancel">Cancel</button>
      </div>
    </div>
  </div>

  <!-- Completion Review Modal -->
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
  <script>
const fixedTypes = [
  'tutoring',
  'grade consultation',
  'missed activities',
  'special quiz or exam',
  'capstone consultation'
];

// Basic sanitizer shared by search & chat below
function sanitize(raw){
  if(!raw) return '';
  return raw
    .replace(/\/*.*?\*\//g,'') // remove block comments
    .replace(/--+/g,' ')          // remove repeated dashes (SQL comment openers)
    .replace(/[;`'"<>]/g,' ')    // strip risky punctuation
    .replace(/\s+/g,' ')         // collapse whitespace
    .trim()
    .slice(0,50);
}

function decodeHtmlEntities(value){
  if(!value) return '';
  const textarea = document.createElement('textarea');
  textarea.innerHTML = value;
  return textarea.value;
}

function escapeForAttr(value){
  return String(value ?? '')
    .replace(/&/g,'&amp;')
    .replace(/"/g,'&quot;')
    .replace(/</g,'&lt;')
    .replace(/>/g,'&gt;');
}

  // ===== Sorting + Pagination State =====
  let sortKey = 'date'; // default sort
  let sortDir = 'desc';
  let currentPage = 1;
  let pageSize = parseInt(localStorage.getItem('conlog.pageSize')||'10',10);
  if(![5,10,25,50,100].includes(pageSize)) pageSize = 10;
  document.addEventListener('DOMContentLoaded',()=>{
    const ps = document.getElementById('pageSize'); if(ps) ps.value = String(pageSize);
  });

  function getDataRows(){
    return Array.from(document.querySelectorAll('.table .table-row'))
      .filter(r=>!r.classList.contains('table-header') && !r.classList.contains('no-results-row'));
  }

  function setSortIndicators(){
    const headers = document.querySelectorAll('#conlogHeader .sort-header');
    headers.forEach(h=>{
      const icon = h.querySelector('.sort-icon');
      if(!icon) return;
      const key = h.getAttribute('data-sort');
      if(key===sortKey){ icon.textContent = sortDir==='asc' ? ' ▲' : ' ▼'; h.classList.add('active-sort'); }
      else { icon.textContent=''; h.classList.remove('active-sort'); }
    });
  }

  function compareRows(a,b){
    const get = (row,key)=>{
      if(key==='date') return Number(row.dataset.dateTs||0);
      if(key==='booked') return Number(row.dataset.bookedTs||0);
      return (row.dataset[key]||'').toString();
    };
    const va = get(a,sortKey); const vb = get(b,sortKey);
    let cmp = 0;
    if(typeof va==='number' && typeof vb==='number') cmp = va - vb;
    else cmp = va.localeCompare(vb);
    return sortDir==='asc' ? cmp : -cmp;
  }

  // Subject dropdown mirrors the "Type of Activity" values present in the log
  function rebuildSubjectOptions(){
    const sel = document.getElementById('subjectFilter'); if(!sel) return;
    const map = new Map();
    getDataRows().forEach(row=>{
      const cell = row.querySelector('.table-cell[data-label="Type"]');
      const label = (cell ? cell.textContent : (row.dataset.type||'')).trim();
      if(!label) return; const key = label.toLowerCase();
      if(!map.has(key)) map.set(key, label);
    });
    const previous = String(sel.value||'');
    const arr = Array.from(map.values()).sort((a,b)=>a.localeCompare(b));
    sel.innerHTML = '<option value="">All Subjects</option>' + arr.map(v=>`<option value="${v}">${v}</option>`).join('');
    const match = previous ? arr.find(v=>v.toLowerCase() === previous.toLowerCase()) : '';
    const resolved = match || '';
    sel.value = resolved;
    const norm = (v)=>String(v||'').toLowerCase();
    if(norm(previous) !== norm(resolved)){
      if(!rebuildSubjectOptions._pending){
        rebuildSubjectOptions._pending = true;
        setTimeout(()=>{
          rebuildSubjectOptions._pending = false;
          if(typeof filterRows === 'function') filterRows();
        }, 0);
      }
    }
  }

  // Build the Type dropdown from the table's "Type" values (no duplicates)
  function rebuildTypeOptions(){
    const sel = document.getElementById('typeFilter'); if(!sel) return;
    const map = new Map(); // lower -> first-seen case label
    getDataRows().forEach(r=>{
      const cell = r.querySelector('.table-cell[data-label="Type"]');
      const label = (cell ? cell.textContent : (r.dataset.type||'')).trim();
      if(!label) return; const lower = label.toLowerCase(); if(!map.has(lower)) map.set(lower, label);
    });
    const cur = sel.value;
    const arr = Array.from(map.values()).sort((a,b)=>a.localeCompare(b));
    const hasOthers = map.has('others');
    sel.innerHTML = '<option value="">All Types</option>' +
      arr.map(v=>`<option value="${v}">${v}</option>`).join('') +
      (!hasOthers ? '<option value="Others">Others</option>' : '');
    if(arr.includes(cur) || cur==='') sel.value = cur; else sel.value = '';
    if(typeof buildTypeFilterDropdown==='function') buildTypeFilterDropdown();
  }

  function applySortAndPaginate(){
    const table = document.querySelector('.table'); if(!table) return;
    const header = document.querySelector('.table-header');
    const rows = getDataRows();
    const matched = rows.filter(r=>r.dataset.matched==='1');

    const existingNo = document.querySelector('.no-results-row'); if(existingNo) existingNo.remove();
    if(matched.length===0){
      rows.forEach(r=>r.style.display='none');
      const noRow = document.createElement('div');
      noRow.className='table-row no-results-row';
      noRow.innerHTML = `<div class="table-cell" style="text-align:center;padding:20px;color:#666;font-style:italic;grid-column:1 / -1;">No Consultations Found.</div>`;
      header.insertAdjacentElement('afterend', noRow);
      const info = document.getElementById('pageInfo'); if(info) info.textContent='';
      const pag = document.getElementById('paginationControls'); if(pag) pag.innerHTML='';
      setSortIndicators();
      return;
    }

    matched.sort(compareRows);
    const frag = document.createDocumentFragment();
    matched.forEach(r=>frag.appendChild(r));
    table.appendChild(frag);

    const total = matched.length; const totalPages = Math.max(1, Math.ceil(total/pageSize));
    if(currentPage>totalPages) currentPage = totalPages;
    const start = (currentPage-1)*pageSize; const end = Math.min(total, start+pageSize)-1;

    const matchedSet = new Set(matched);
    rows.forEach(r=>{
      if(!matchedSet.has(r)) { r.style.display='none'; return; }
      const idx = matched.indexOf(r);
      const isVisible = (idx>=start && idx<=end);
      r.style.display = isVisible ? '' : 'none';
    });

    // Renumber the visible rows so the "No." column always shows 1..N for the current view
    let displayCounter = 1;
    for(let i=start; i<=end; i++){
      const row = matched[i];
      if(!row) continue;
      const noCell = row.querySelector('.table-cell[data-label="No."]');
      if(noCell){ noCell.textContent = String(displayCounter++); }
    }

  /* pageInfo removed from UI */
    const pag = document.getElementById('paginationControls');
    if(pag){
      // Build compact pagination: ‹ Page [select] of N ›
      const totalPagesCalc = Math.max(1, Math.ceil(total/pageSize));
      const makeBtn = (label, target, disabled=false)=>{ const b=document.createElement('button'); b.className='page-btn'; b.textContent=label; b.disabled=disabled; b.addEventListener('click',()=>{ currentPage = target; applySortAndPaginate(); }); return b; };
      pag.innerHTML='';
  // Prev chevron
  const prevBtn = makeBtn('‹', Math.max(1, currentPage-1), currentPage===1); prevBtn.classList.add('chev','prev'); pag.appendChild(prevBtn);
      // "Page" label
      const lbl = document.createElement('span'); lbl.className='page-label'; lbl.textContent='Page'; pag.appendChild(lbl);
      // Page select
      const sel = document.createElement('select'); sel.className='page-select'; sel.setAttribute('aria-label','Current page');
      for(let p=1;p<=totalPagesCalc;p++){ const o=document.createElement('option'); o.value=String(p); o.textContent=String(p); if(p===currentPage) o.selected=true; sel.appendChild(o); }
      sel.addEventListener('change', (e)=>{ const v=parseInt(e.target.value,10)||1; currentPage = Math.min(Math.max(1,v), totalPagesCalc); applySortAndPaginate(); });
      pag.appendChild(sel);
      // "of N"
      const of = document.createElement('span'); of.className='page-of'; of.textContent=`of ${totalPagesCalc}`; pag.appendChild(of);
  // Next chevron
  const nextBtn = makeBtn('›', Math.min(totalPagesCalc, currentPage+1), currentPage===totalPagesCalc); nextBtn.classList.add('chev','next'); pag.appendChild(nextBtn);
    }
    setSortIndicators();
  }

function filterRows() {
  const inputEl = document.getElementById('searchInput');
  let search = sanitize(inputEl.value).toLowerCase();
  let type = document.getElementById('typeFilter').value.toLowerCase();
  let subject = (document.getElementById('subjectFilter')?.value||'').toLowerCase();
  let rows = document.querySelectorAll('.table-row:not(.table-header)');

  rows.forEach(row => {
    if (row.classList.contains('no-results-row')) return;
    // Get key dataset values for filters
    let rowType = (row.dataset.type||'').toLowerCase();
  let rowSubject = (row.dataset.type||'').toLowerCase();

    // Build a case-insensitive haystack from visible cell texts across ALL columns
    // Exclude only the numbering and action columns
    const hay = Array.from(row.querySelectorAll('.table-cell'))
      .filter(c => {
        const lbl = c.getAttribute('data-label')||'';
        return lbl !== 'No.' && lbl !== 'Action';
      })
      .map(c => (c.textContent||'').toLowerCase().trim())
      .join(' ');

    let isOthers = fixedTypes.indexOf(rowType) === -1 && rowType !== '';

    let matchesType = !type || (type !== 'others' && rowType === type) || (type === 'others' && isOthers);
    let matchesSubject = !subject || rowSubject === subject;
    let matchesSearch = !search || hay.includes(search);

    row.dataset.matched = (matchesSearch && matchesType && matchesSubject) ? '1' : '0';
  });
  currentPage = 1;
  applySortAndPaginate();
}

  // Custom dropdown for type filter (mobile)
  function buildTypeFilterDropdown(){
    const wrap=document.getElementById('typeFilterDropdown');
    const trigger=document.getElementById('typeFilterTrigger');
    const list=document.getElementById('typeFilterList');
    const native=document.getElementById('typeFilter');
    if(!wrap||!trigger||!list||!native) return;
    list.innerHTML='';
    Array.from(native.options).forEach((o,i)=>{
      const li=document.createElement('li');
      li.textContent=o.textContent; if(i===native.selectedIndex) li.classList.add('active');
      li.addEventListener('click',()=>{ native.selectedIndex=i; updateTrigger(); wrap.classList.remove('open'); Array.from(list.children).forEach(c=>c.classList.remove('active')); li.classList.add('active'); native.dispatchEvent(new Event('change')); });
      list.appendChild(li);
    });
    updateTrigger();
    trigger.onclick=()=>{ wrap.classList.toggle('open'); };
    document.addEventListener('click',e=>{ if(!wrap.contains(e.target)) wrap.classList.remove('open'); });
    function updateTrigger(){ const sel=native.options[native.selectedIndex]; trigger.textContent= sel? sel.textContent : 'All Types'; }
  }

  document.addEventListener('DOMContentLoaded',function(){ buildTypeFilterDropdown(); rebuildTypeOptions(); });

document.getElementById('searchInput').addEventListener('input', filterRows);
document.getElementById('typeFilter').addEventListener('change', filterRows);
document.getElementById('subjectFilter').addEventListener('change', filterRows);
document.getElementById('pageSize').addEventListener('change', (e)=>{
  pageSize = parseInt(e.target.value,10) || 10;
  localStorage.setItem('conlog.pageSize', String(pageSize));
  currentPage = 1;
  applySortAndPaginate();
});

// Header sorting handlers
document.querySelectorAll('#conlogHeader .sort-header').forEach(h=>{
  const set = ()=>{
    const key = h.getAttribute('data-sort');
    if(sortKey===key){ sortDir = (sortDir==='asc' ? 'desc' : 'asc'); }
    else { sortKey = key; sortDir = (key==='date' || key==='booked') ? 'desc' : 'asc'; }
    applySortAndPaginate();
  };
  h.addEventListener('click', set);
  h.addEventListener('keypress', (e)=>{ if(e.key==='Enter' || e.key===' ') { e.preventDefault(); set(); } });
});

document.addEventListener('DOMContentLoaded',()=>{ filterRows(); });

  // Insert Cancel buttons for eligible rows (pending and within 1 hour), else no action.
  function refreshRowActions(row){
    if(!row || row.classList.contains('table-header')) return;
    const nowTs = Date.now() / 1000;
    const status = (row.dataset.status||'').toLowerCase();
    const createdTs = Number(row.dataset.bookedTs||'0');
    const actionGroup = row.querySelector('.action-btn-group');
    if(!actionGroup) return;
    actionGroup.innerHTML = '';
    const withinHour = (nowTs - createdTs) <= 3600;
    const isPending = status === 'pending';
    if(status === 'completion_pending'){
      const btn = document.createElement('button');
      btn.className = 'action-btn btn-review';
      btn.type = 'button';
      btn.title = 'Review completion request';
      btn.innerHTML = "<i class='bx bx-task'></i>";
      btn.addEventListener('click', function(){
        const bookingId = row.querySelector('.table-cell[data-label="No."]')?.getAttribute('data-booking-id');
        if(!bookingId) return;
        openCompletionReviewFromRow(row, bookingId);
      });
      actionGroup.appendChild(btn);
    } else if(isPending && withinHour){
      const btn = document.createElement('button');
      btn.className = 'action-btn btn-cancel';
      btn.type = 'button';
      btn.title = 'Cancel';
      btn.innerHTML = "<i class='bx bx-x-circle'></i>";
      btn.addEventListener('click', function(){
        const idCell = row.querySelector('.table-cell[data-label="No."]');
        const bookingId = idCell ? idCell.getAttribute('data-booking-id') : null;
        if(!bookingId){ return; }
        showStudentConfirm('Cancel this consultation request?', function(ok){
          if(!ok) return;
          cancelStudentBooking(bookingId, row, btn);
        });
      });
      actionGroup.appendChild(btn);
    }
  }

  document.addEventListener('DOMContentLoaded', ()=>{
    document.querySelectorAll('.table .table-row').forEach(refreshRowActions);
  });

  const completionReviewState = {
    bookingId: null,
    row: null,
    notificationId: null,
    pending: false,
  };

  function formatDateLabel(iso){
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
    if(!overlay || !remarksEl || !infoEl || !errorEl){
      return;
    }
    const reason = details.reason ? details.reason.trim() : '';
    if(reason){
      remarksEl.style.display = 'block';
      if(remarksTextEl){ remarksTextEl.textContent = reason; }
    }else{
      remarksEl.style.display = 'none';
      if(remarksTextEl){ remarksTextEl.textContent = ''; }
    }
    const requestedLabel = formatDateLabel(details.requestedAt);
    infoEl.textContent = requestedLabel ? `Requested on ${requestedLabel}${details.professor ? ` by ${details.professor}` : ''}.` : (details.professor ? `Professor ${details.professor} sent this request.` : '');
    errorEl.style.display = 'none';
    errorEl.textContent = '';
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
    if(overlay){ overlay.style.display = 'none'; overlay.setAttribute('aria-hidden','true'); }
    if(errorEl){ errorEl.textContent=''; errorEl.style.display='none'; }
    approveBtn && (approveBtn.disabled = false);
    declineBtn && (declineBtn.disabled = false);
    closeBtn && (closeBtn.disabled = false);
    completionReviewState.bookingId = null;
    completionReviewState.row = null;
    completionReviewState.notificationId = null;
    completionReviewState.pending = false;
  }

  function openCompletionReviewFromRow(row, bookingId){
    const professor = row?.querySelector('.instructor-cell')?.textContent?.trim() || '';
    const reason = decodeHtmlEntities(row?.dataset?.completionReason || '');
    const requested = row?.dataset?.completionRequested || '';
    completionReviewState.bookingId = bookingId;
    completionReviewState.row = row;
    completionReviewState.notificationId = null;
    populateCompletionModal({ professor, reason, requestedAt: requested });
  }

  function openCompletionReviewWithFetch(bookingId, notificationId){
    completionReviewState.bookingId = bookingId;
    completionReviewState.notificationId = notificationId || null;
    completionReviewState.row = null;
    const overlay = document.getElementById('completionReviewOverlay');
    const remarksEl = document.getElementById('completionReviewRemarks');
    const remarksTextEl = document.getElementById('completionReviewRemarksText');
    const infoEl = document.getElementById('completionReviewInfo');
    const errorEl = document.getElementById('completionReviewError');
    if(overlay){
      overlay.style.display = 'flex';
      overlay.setAttribute('aria-hidden','false');
    }
    if(remarksEl){ remarksEl.style.display='none'; }
    if(remarksTextEl){ remarksTextEl.textContent=''; }
    if(infoEl){ infoEl.textContent = 'Loading details…'; }
    if(errorEl){ errorEl.style.display='none'; errorEl.textContent=''; }
    fetch(`/api/student/consultation-details/${bookingId}`)
      .then(r=>r.json())
      .then(data=>{
        if(!data || !data.success){ throw new Error(data?.message || 'Unable to load details'); }
        const consult = data.consultation || {};
        populateCompletionModal({
          professor: consult.professor_name || '',
          reason: consult.completion_reason || consult.reschedule_reason || '',
          requestedAt: consult.completion_requested_at || '',
        });
      })
      .catch(err=>{
        if(errorEl){ errorEl.textContent = err?.message || 'Unable to load completion details.'; errorEl.style.display='block'; }
      });
  }

  function applyDecisionToRow(row, decision){
    if(!row) return;
    const statusCell = row.querySelector('.table-cell[data-label="Status"]');
    if(statusCell){
      if(decision === 'completed'){ statusCell.textContent = 'Completed'; }
      else if(decision === 'completion_declined'){ statusCell.textContent = 'Completion Declined'; }
      const baseRemark = decodeHtmlEntities(row.dataset.completionReason || '');
      statusCell.title = baseRemark ? `Remarks: ${baseRemark}` : '';
    }
    row.dataset.status = decision;
    row.dataset.completionResponse = decision === 'completed' ? 'agreed' : 'declined';
    row.dataset.completionComment = '';
    row.dataset.completionReviewed = new Date().toISOString();
    row.dataset.matched = row.dataset.matched || '1';
    const actionGroup = row.querySelector('.action-btn-group');
    if(actionGroup){ actionGroup.innerHTML=''; }
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
      const note = decision === 'completed' ? 'Marked as completed. Thank you!' : 'We let your professor know you still need help.';
      if(typeof showNotification === 'function'){ showNotification(note, false); }
      if(completionReviewState.row){ applyDecisionToRow(completionReviewState.row, decision); }
      if(typeof filterRows === 'function'){ filterRows(); }
      closeCompletionReview();
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

  const completionOverlayEl = document.getElementById('completionReviewOverlay');
  completionOverlayEl?.addEventListener('click', (e)=>{
    if(e.target === completionOverlayEl && !completionReviewState.pending){ closeCompletionReview(); }
  });

  window.openCompletionReviewWithFetch = openCompletionReviewWithFetch;


  function cancelStudentBooking(bookingId, row, btn){
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    fetch('/api/student/consultations/cancel',{
      method:'POST',
      headers:{ 'Content-Type':'application/json','X-CSRF-TOKEN': csrfToken },
      body: JSON.stringify({ id: Number(bookingId) })
    }).then(r=>r.json()).then(data=>{
      if(data && data.success){
        // Remove the row entirely from the log and re-apply filters/pagination
        try { row && row.remove && row.remove(); } catch(e) {}
  if(typeof filterRows==='function') filterRows();
  if(typeof rebuildSubjectOptions==='function') rebuildSubjectOptions();
  if(typeof rebuildTypeOptions==='function') rebuildTypeOptions();
        showNotification('Your consultation has been successfully cancelled.', false);
      } else {
        showNotification((data && data.message) ? data.message : 'Failed to cancel.', true);
      }
    }).catch(()=>{
      showNotification('Network error while cancelling.', true);
    });
  }

  // Themed confirm modal for student conlog
  function showStudentConfirm(message, onConfirm){
    const overlay = document.getElementById('studentConfirmOverlay');
    const msg = document.getElementById('studentConfirmMessage');
    const okBtn = document.getElementById('studentConfirmOk');
    const cancelBtn = document.getElementById('studentConfirmCancel');
    if(!overlay || !msg || !okBtn || !cancelBtn){
      // Fallback if modal is not present
      const ok = window.confirm(message||'Are you sure?');
      if(typeof onConfirm==='function') onConfirm(ok);
      return;
    }
    msg.textContent = message || 'Are you sure?';
    function cleanup(){
      overlay.style.display='none';
      document.removeEventListener('keydown', escHandler);
      overlay.removeEventListener('click', outsideHandler);
      okBtn.removeEventListener('click', okHandler);
      cancelBtn.removeEventListener('click', cancelHandler);
    }
    function okHandler(){ cleanup(); onConfirm && onConfirm(true); }
    function cancelHandler(){ cleanup(); onConfirm && onConfirm(false); }
    function escHandler(e){ if(e.key==='Escape'){ cancelHandler(); } }
    function outsideHandler(e){ const modal = document.querySelector('#studentConfirmOverlay .confirm-modal'); if(modal && !modal.contains(e.target)) cancelHandler(); }
    okBtn.addEventListener('click', okHandler);
    cancelBtn.addEventListener('click', cancelHandler);
    document.addEventListener('keydown', escHandler);
    overlay.addEventListener('click', outsideHandler);
    overlay.style.display='flex';
  }

// ===== Mobile Filters Overlay =====
function syncOverlayFromMain(){
  const tMain = document.getElementById('typeFilter');
  const sMain = document.getElementById('subjectFilter');
  const pMain = document.getElementById('pageSize');
  const tMob = document.getElementById('typeFilterMobile');
  const sMob = document.getElementById('subjectFilterMobile');
  const pMob = null;
  if(tMain && tMob){
    // Mirror dynamic type options
    const map = new Map(); const opts = ['<option value="">All Types</option>'];
    getDataRows().forEach(r=>{ const c=r.querySelector('.table-cell[data-label="Type"]'); const lbl=(c?c.textContent:(r.dataset.type||'')).trim(); if(!lbl) return; const k=lbl.toLowerCase(); if(!map.has(k)) map.set(k,lbl); });
    const arr=Array.from(map.values()).sort((a,b)=>a.localeCompare(b));
    const hasOthers = map.has('others');
    tMob.innerHTML = opts.concat(arr.map(v=>`<option value="${v}">${v}</option>`)).join('') + (hasOthers? '' : '<option value="Others">Others</option>');
    tMob.value = tMain.value;
  }
  if(sMain && sMob) {
    // Keep subject filter aligned with consultation type labels
    const map = new Map();
    getDataRows().forEach(row=>{
      const cell = row.querySelector('.table-cell[data-label="Type"]');
      const label = (cell ? cell.textContent : (row.dataset.type||''))?.trim();
      if(!label) return; const key = label.toLowerCase();
      if(!map.has(key)) map.set(key, label);
    });
    const arr = Array.from(map.values()).sort((a,b)=>a.localeCompare(b));
    sMob.innerHTML = '<option value="">All Subjects</option>' + arr.map(v=>`<option value="${v}">${v}</option>`).join('');
    const current = sMain.value;
    if(current){
      const match = arr.find(v=>v.toLowerCase() === current.toLowerCase());
      sMob.value = match || '';
      if(match && match !== current){ sMain.value = match; }
    } else {
      sMob.value = '';
    }
  }
  // page size is not in overlay anymore
}

function openFilters(){ const ov = document.getElementById('filtersOverlay'); if(!ov) return; syncOverlayFromMain(); ov.classList.add('open'); ov.setAttribute('aria-hidden','false'); document.body.style.overflow='hidden'; }
function closeFilters(){ const ov = document.getElementById('filtersOverlay'); if(!ov) return; ov.classList.remove('open'); ov.setAttribute('aria-hidden','true'); document.body.style.overflow=''; }

function applyFiltersFromOverlay(){
  const tMain = document.getElementById('typeFilter');
  const sMain = document.getElementById('subjectFilter');
  const pMain = document.getElementById('pageSize');
  const tMob = document.getElementById('typeFilterMobile');
  const sMob = document.getElementById('subjectFilterMobile');
  const pMob = null;
  if(tMain && tMob){ tMain.value = tMob.value; tMain.dispatchEvent(new Event('change')); }
  if(sMain && sMob){ sMain.value = sMob.value; sMain.dispatchEvent(new Event('change')); }
  // no page size in overlay to apply
  closeFilters();
}

function resetFiltersOverlay(){
  const tMob = document.getElementById('typeFilterMobile');
  const sMob = document.getElementById('subjectFilterMobile');
  const pMob = null;
  if(tMob) tMob.value = '';
  if(sMob) sMob.value = '';
  // no page size to reset in overlay
}

document.getElementById('openFiltersBtn')?.addEventListener('click', openFilters);
document.getElementById('closeFiltersBtn')?.addEventListener('click', closeFilters);
document.getElementById('applyFiltersBtn')?.addEventListener('click', applyFiltersFromOverlay);
document.getElementById('resetFiltersBtn')?.addEventListener('click', resetFiltersOverlay);
document.getElementById('filtersOverlay')?.addEventListener('click', (e)=>{
  const drawer = document.querySelector('.filters-drawer');
  if(drawer && !drawer.contains(e.target)) closeFilters();
});

// Real-time updates for consultation log - DISABLED TO PREVENT DUPLICATE ROWS
/*
function loadConsultationLogs() {
  fetch('/api/student/consultation-logs')
    .then(response => response.json())
    .then(data => {
      updateConsultationTable(data);
    })
    .catch(error => {
      console.error('Error loading consultation logs:', error);
    });
}

function updateConsultationTable(bookings) {
  const table = document.querySelector('.table');
  const header = document.querySelector('.table-header');
  
  // Clear existing rows except header
  const existingRows = table.querySelectorAll('.table-row:not(.table-header)');
  existingRows.forEach(row => row.remove());
  
  if (bookings.length === 0) {
    const emptyRow = document.createElement('div');
    emptyRow.className = 'table-row';
    emptyRow.innerHTML = `
      <div class="table-cell" colspan="8">No consultations found.</div>
    `;
    table.appendChild(emptyRow);
  } else {
    bookings.forEach((booking, index) => {
      const row = document.createElement('div');
      row.className = 'table-row';
      
      const bookingDate = new Date(booking.Booking_Date);
      const createdAt = new Date(booking.Created_At);
      
      row.innerHTML = `
        <div class="table-cell" data-label="No.">${index + 1}</div>
        <div class="table-cell instructor-cell" data-label="Instructor">${booking.Professor}</div>
        <div class="table-cell" data-label="Subject">${booking.subject}</div>
        <div class="table-cell" data-label="Date">${bookingDate.toLocaleDateString('en-US', { weekday: 'short', month: 'short', day: 'numeric', year: 'numeric' })}</div>
        <div class="table-cell" data-label="Type">${booking.type}</div>
        <div class="table-cell" data-label="Mode">${booking.Mode.charAt(0).toUpperCase() + booking.Mode.slice(1)}</div>
        <div class="table-cell" data-label="Booked At">${createdAt.toLocaleDateString('en-US', { month: 'numeric', day: 'numeric', year: 'numeric' })} ${createdAt.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit', hour12: true })}</div>
        <div class="table-cell" data-label="Status">${booking.Status.charAt(0).toUpperCase() + booking.Status.slice(1)}</div>
      `;
      
      table.appendChild(row);
    });
  }
  
  // Add spacer
  const spacer = document.createElement('div');
  spacer.style.height = '80px';
  table.appendChild(spacer);
  
  // Re-apply filters after updating
  filterRows();
}

// Initial load and real-time updates every 5 seconds - DISABLED
loadConsultationLogs();
setInterval(loadConsultationLogs, 5000);
*/

// Live updates via Pusher for the current student
(function(){
  try {
    // Try to determine current student ID from navbar or hidden context
    const metaUser = document.querySelector('meta[name="csrf-token"]'); // placeholder anchor; no student id here
    // Prefer server-side injection through navbar; fallback to fetch on first update if needed
    const studIdEl = document.getElementById('navbar') || document.body; // just to keep DOM lookup cheap
  // Prefer student guard if available; fall back to web user with Stud_ID
  const studId = {{ optional(auth()->user())->Stud_ID ?? optional(auth()->guard('web')->user())->Stud_ID ?? 'null' }};
    if(!studId) return;

  const pusher = new Pusher('{{ config('broadcasting.connections.pusher.key') }}', {cluster: '{{ config('broadcasting.connections.pusher.options.cluster') }}'});
  Pusher.logToConsole = false;
    const channel = pusher.subscribe('bookings.stud.'+studId);

    function normalizeDate(str){ try{ return new Date(str).toLocaleDateString('en-US',{weekday:'short', month:'short', day:'numeric', year:'numeric'}); }catch(e){ return str; } }

    function renderRow(data){
      const table = document.querySelector('.table'); if(!table) return;
      const rows = Array.from(table.querySelectorAll('.table-row'))
        .filter(r=>!r.classList.contains('table-header') && !r.classList.contains('no-results-row'));
      let existing = null;
      const targetId = (data.Booking_ID!==undefined && data.Booking_ID!==null) ? String(data.Booking_ID).trim() : '';
      rows.forEach(r=>{ const idCell=r.querySelector('[data-booking-id]'); const rowId = idCell? String(idCell.getAttribute('data-booking-id')||'').trim():''; if(rowId && targetId && rowId===targetId){ existing=r; } });

      // If this booking is now cancelled, remove its row (if any) and stop.
      if(String((data.Status||'')).toLowerCase()==='cancelled'){
        if(existing){ try{ existing.remove(); }catch(e){} }
        if(typeof filterRows==='function') filterRows();
        if(typeof rebuildSubjectOptions==='function') rebuildSubjectOptions();
        return;
      }

      // Helper to merge missing fields from a given row's current cells
      function mergeFromRow(row) {
        if(!row) return;
        const cells = row.querySelectorAll('.table-cell');
        data.Professor = data.Professor ?? (cells[1]?.textContent.trim()||'');
        data.subject = data.subject ?? (cells[2]?.textContent.trim()||'');
        data.Booking_Date = data.Booking_Date ?? (cells[3]?.textContent.trim()||'');
        data.type = data.type ?? (cells[4]?.textContent.trim()||'');
        data.Mode = data.Mode ?? (cells[5]?.textContent.trim().toLowerCase()||'');
        data.Created_At = data.Created_At ?? (cells[6]?.textContent.trim()||'');
        data.Status = data.Status ?? (cells[7]?.textContent.trim().toLowerCase()||'');
      }
      if(existing){ mergeFromRow(existing); }

      const date = normalizeDate(data.Booking_Date||'');
      const mode = (data.Mode||'').charAt(0).toUpperCase() + (data.Mode||'').slice(1);
      const bookedAt = data.Created_At ? new Date(data.Created_At).toLocaleString('en-US', { month:'short', day:'2-digit', year:'numeric', hour:'numeric', minute:'2-digit'}) : (existing? (existing.querySelectorAll('.table-cell')[6]?.textContent||'') : '');
      const rawStatus = (data.Status||'').toString().toLowerCase();
      const statusLabelMap = {
        completion_pending: 'Completion Pending',
        completion_declined: 'Completion Declined',
      };
      const status = statusLabelMap[rawStatus] || (rawStatus ? rawStatus.charAt(0).toUpperCase() + rawStatus.slice(1) : '');
  const completionReason = data.completion_reason || '';
  const statusTitle = completionReason ? ` title="${escapeForAttr('Remarks: ' + completionReason)}"` : '';
      const iter = existing ? (existing.querySelector('.table-cell')?.textContent||'') : (rows.length+1);

      const html = `
        <div class="table-cell" data-label="No." data-booking-id="${data.Booking_ID}">${iter}</div>
        <div class="table-cell instructor-cell" data-label="Instructor">${data.Professor||''}</div>
        <div class="table-cell" data-label="Subject">${data.subject||''}</div>
        <div class="table-cell" data-label="Date">${date}</div>
        <div class="table-cell" data-label="Type">${data.type||''}</div>
        <div class="table-cell" data-label="Mode">${mode}</div>
        <div class="table-cell" data-label="Booked At">${bookedAt}</div>
          <div class="table-cell" data-label="Status"${statusTitle}>${status}</div>
          <div class="table-cell" data-label="Action" style="width: 100px;">
            <div class="action-btn-group" style="display:flex;gap:8px;"></div>
          </div>`;

      function setDataAttrs(row){
        const d = new Date(data.Booking_Date||'');
        const created = data.Created_At ? new Date(data.Created_At) : null;
        row.dataset.instructor = (data.Professor||'').toString().toLowerCase();
        row.dataset.subject = (data.subject||'').toString().toLowerCase();
        if(!isNaN(d.getTime())){
          row.dataset.date = `${d.getFullYear()}-${String(d.getMonth()+1).padStart(2,'0')}-${String(d.getDate()).padStart(2,'0')}`;
          row.dataset.dateTs = String(Math.floor(d.getTime()/1000));
        }
        row.dataset.type = (data.type||'').toString().toLowerCase();
        row.dataset.mode = (data.Mode||'').toString().toLowerCase();
        if(created && !isNaN(created.getTime())){
          row.dataset.booked = `${created.getFullYear()}-${String(created.getMonth()+1).padStart(2,'0')}-${String(created.getDate()).padStart(2,'0')} ${String(created.getHours()).padStart(2,'0')}:${String(created.getMinutes()).padStart(2,'0')}:00`;
          row.dataset.bookedTs = String(Math.floor(created.getTime()/1000));
        }
  row.dataset.status = rawStatus;
        row.dataset.matched = '1';
  row.dataset.completionReason = data.completion_reason || '';
  row.dataset.completionRequested = data.completion_requested_at || '';
  row.dataset.completionReviewed = data.completion_reviewed_at || '';
  row.dataset.completionResponse = data.completion_student_response || '';
  row.dataset.completionComment = '';
      }

      if(existing){
        existing.innerHTML = html;
        // guarantee the data-booking-id attribute remains for subsequent updates
        const first = existing.querySelector('.table-cell'); if(first){ first.setAttribute('data-booking-id', String(data.Booking_ID)); }
        setDataAttrs(existing);
        refreshRowActions(existing);
      }
      else {
        // Try to reuse any orphan row (missing data-booking-id) to avoid duplicates from earlier sessions
        const orphan = rows.find(r => !r.querySelector('[data-booking-id]'));
        if(orphan){
          // Merge current orphan cell values for fields not present in payload
          mergeFromRow(orphan);
          orphan.innerHTML = html;
          const first = orphan.querySelector('.table-cell'); if(first){ first.setAttribute('data-booking-id', String(data.Booking_ID)); }
          setDataAttrs(orphan);
          refreshRowActions(orphan);
        } else {
        const row = document.createElement('div');
        row.className = 'table-row';
        row.innerHTML = html;
        const first = row.querySelector('.table-cell'); if(first){ first.setAttribute('data-booking-id', String(data.Booking_ID)); }
        setDataAttrs(row);
        refreshRowActions(row);
        table.appendChild(row);
        }
      }

  if(typeof filterRows==='function') filterRows();
  rebuildSubjectOptions();
  if(typeof rebuildTypeOptions==='function') rebuildTypeOptions();
    }

    // Bind to the explicit alias and FQCN fallback to be safe across drivers
    channel.bind('BookingUpdated', renderRow);
    channel.bind('App\\Events\\BookingUpdatedStudent', renderRow);
  } catch(e){ console.warn('Realtime (student) init failed', e); }
})();

// ITIS-style notification helpers (top-right banner)
function showNotification(message, isError = false) {
  const notif = document.getElementById('notification');
  const msgEl = document.getElementById('notification-message');
  if(!notif || !msgEl){
    // Fallback
    return alert(String(message||''));
  }
  notif.classList.toggle('error', !!isError);
  msgEl.textContent = String(message||'');
  notif.style.display = 'flex';
  clearTimeout(showNotification._t);
  showNotification._t = setTimeout(hideNotification, 4000);
}
function hideNotification(){
  const notif = document.getElementById('notification');
  if(notif) notif.style.display = 'none';
}

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

function sendQuick(text){ if(!text) return; input.value = text; if(typeof chatForm.requestSubmit === 'function') chatForm.requestSubmit(); else chatForm.dispatchEvent(new Event('submit', {cancelable:true})); }
quickReplies?.addEventListener('click', (e)=>{ const btn=e.target.closest('.quick-reply'); if(btn){ sendQuick(btn.dataset.message); } });
quickRepliesToggle?.addEventListener('click', ()=>{ if(quickReplies){ quickReplies.style.display='flex'; quickRepliesToggle.style.display='none'; } });

// Send on Enter (like ITIS/COMSCI). Prevent accidental double submits.
input.addEventListener('keydown', function(e){
  if(e.key === 'Enter'){ 
    e.preventDefault();
  if(!sanitize(input.value)) return;
    // Use requestSubmit if supported
    if(typeof chatForm.requestSubmit === 'function') chatForm.requestSubmit();
    else chatForm.dispatchEvent(new Event('submit', {cancelable:true}));
  }
});

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
</body>
</html>
