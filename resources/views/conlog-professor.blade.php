<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>Consultation Log</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
  <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
  <link rel="stylesheet" href="{{ asset('css/conlog-professor.css') }}">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/pikaday/css/pikaday.css">
  <link rel="stylesheet" href="{{ asset('css/logout-confirm.css') }}">
  <script src="{{ asset('js/logout-confirm.js') }}" defer></script>
  
  
  <style>
    /* Reschedule Modal Styles */
    .reschedule-overlay {
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: rgba(0, 0, 0, 0.7);
      display: none;
      justify-content: center;
      align-items: center;
      z-index: 1000;
    }

    .reschedule-modal {
      background: white;
      border-radius: 12px;
      padding: 0;
      width: 90%;
      max-width: 450px;
      box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
      animation: modalSlideIn 0.3s ease-out;
    }

    @keyframes modalSlideIn {
      from {
        opacity: 0;
        transform: translateY(-50px) scale(0.9);
      }
      to {
        opacity: 1;
        transform: translateY(0) scale(1);
      }
    }

    .reschedule-header {
      background: #2c5f4f;
      color: white;
      padding: 20px;
      border-radius: 12px 12px 0 0;
      display: flex;
      justify-content: space-between;
      align-items: center;
    }

    .reschedule-header h3 {
      margin: 0;
      font-size: 18px;
      font-weight: 600;
    }

    .reschedule-header .close-btn {
      background: none;
      border: none;
      color: white;
      font-size: 24px;
      cursor: pointer;
      padding: 0;
      width: 30px;
      height: 30px;
      display: flex;
      align-items: center;
      justify-content: center;
      border-radius: 50%;
      transition: background-color 0.3s;
    }

    .reschedule-header .close-btn:hover {
      background: rgba(255, 255, 255, 0.2);
    }

    .reschedule-body {
      padding: 25px;
    }

    .reschedule-body p {
      margin: 0 0 20px 0;
      color: #555;
      font-size: 14px;
    }

    .date-input-group {
      margin-bottom: 25px;
    }

    .date-input-group label {
      display: block;
      margin-bottom: 8px;
      font-weight: 600;
      color: #333;
      font-size: 14px;
    }

    .date-input {
      width: 100%;
      padding: 12px;
      border: 2px solid #e1e5e9;
      border-radius: 8px;
      font-size: 14px;
      transition: border-color 0.3s;
      font-family: 'Poppins', sans-serif;
      resize: vertical;
    }

    .date-input:focus {
      outline: none;
      border-color: #2c5f4f;
      box-shadow: 0 0 0 3px rgba(44, 95, 79, 0.1);
    }

    .date-input[type="date"] {
      resize: none;
    }

    .reschedule-buttons {
      display: flex;
      gap: 12px;
      justify-content: flex-end;
    }

    .btn-cancel,
    .btn-confirm {
      padding: 10px 20px;
      border: none;
      border-radius: 6px;
      cursor: pointer;
      font-size: 14px;
      font-weight: 600;
      transition: all 0.3s;
      font-family: 'Poppins', sans-serif;
    }

    .btn-cancel {
      background: #f8f9fa;
      color: #6c757d;
      border: 1px solid #dee2e6;
    }

    .btn-cancel:hover {
      background: #e9ecef;
      color: #495057;
    }

    .btn-confirm {
      background: #2c5f4f;
      color: white;
    }

    .btn-confirm:hover {
      background: #1e4235;
      transform: translateY(-1px);
    }

    .btn-confirm:disabled {
      background: #ccc;
      cursor: not-allowed;
      transform: none;
    }

    .action-btn.btn-muted {
      background: #3a5247;
      cursor: default;
      opacity: 0.65;
    }

    .action-btn.btn-muted:disabled {
      opacity: 0.65;
    }

    .completion-remarks-modal textarea {
      min-height: 110px;
    }

    .completion-feedback-box {
      background: #eef5f1;
      border-left: 4px solid #2c5f4f;
      padding: 12px;
      border-radius: 10px;
      margin-bottom: 12px;
      color: #1f3931;
      font-size: 13px;
      line-height: 1.45;
    }

    .completion-feedback-box strong {
      display: block;
      margin-bottom: 6px;
    }

    .completion-feedback-box p {
      margin: 0;
      white-space: pre-wrap;
    }
  </style>
</head>
<body>
  @include('components.navbarprof')
  @php
  $termOptions = $termOptions ?? collect();
  $activeTermId = optional($activeTerm)->id;
  $mapTermMeta = function ($term) {
    $year = $term->academicYear;
    $yearLabel = $year->label ?? null;
    $syStart = null;
    $syEnd = null;
    if (is_string($yearLabel) && preg_match('/(\d{4})\s*-\s*(\d{4})/', $yearLabel, $matches)) {
      $syStart = $matches[1];
      $syEnd = $matches[2];
    } else {
      $startAt = $term->academicYear?->start_at;
      $endAt = $term->academicYear?->end_at;
      if ($startAt instanceof \Carbon\CarbonInterface) {
        $syStart = $startAt->format('Y');
      }
      if ($endAt instanceof \Carbon\CarbonInterface) {
        $syEnd = $endAt->format('Y');
      }
    }
    $sequence = (int) ($term->sequence ?? 0);

    $shortLabel = match ($sequence) {
      1 => '1st Sem',
      2 => '2nd Sem',
      3 => 'Midyear Term',
      default => $term->name ?? 'Term',
    };

    $semesterLabel = match ($sequence) {
      1 => 'First Semester',
      2 => 'Second Semester',
      3 => 'Midyear Term',
      default => $term->name ?? 'Term',
    };

    $label = trim(($shortLabel ?: 'Term') . ($yearLabel ? ' ' . $yearLabel : ''));

    return [
      'id' => $term->id,
      'label' => $label,
      'status' => $term->status,
      'name' => $term->name,
      'sequence' => $sequence,
      'semester_label' => $semesterLabel,
      'year_label' => $yearLabel,
      'sy_start' => $syStart,
      'sy_end' => $syEnd,
    ];
  };
  $termList = $termOptions->map($mapTermMeta)->values();
  $activeTermMeta = $activeTerm ? $mapTermMeta($activeTerm) : null;
  @endphp
  <!-- Custom Modal HTML for Professor Message Handling -->
  <div class="custom-modal" id="professorModal">
    <div class="custom-modal-content">
      <span id="professorModalMessage"></span>
      <button class="custom-modal-btn" onclick="closeProfessorModal()">OK</button>
    </div>
  </div>

  <div class="confirm-overlay" id="completionRemarksOverlay" aria-hidden="true" style="display:none;">
    <div class="confirm-modal completion-remarks-modal" role="dialog" aria-modal="true" aria-labelledby="completionRemarksTitle">
      <div class="confirm-header">
        <i class='bx bx-comment-detail'></i>
        <div id="completionRemarksTitle">Add completion remarks</div>
      </div>
      <div class="confirm-body">
        <p style="margin-bottom:12px;">Share a short summary of what was accomplished. Students will review this before finalizing.</p>
        <div id="completionStudentFeedback" class="completion-feedback-box" style="display:none;">
          <strong>Student feedback</strong>
          <p id="completionStudentFeedbackText" style="margin:0;"></p>
        </div>
        <textarea id="completionRemarksInput" class="date-input" rows="4" maxlength="500" placeholder="Example: Discussed project outline and agreed on next milestones."></textarea>
        <div id="completionRemarksError" style="display:none;color:#c0392b;font-size:13px;margin-top:6px;">Please enter at least 5 characters.</div>
      </div>
      <div class="confirm-actions">
        <button type="button" class="btn-cancel" id="completionRemarksCancel">Cancel</button>
        <button type="button" class="btn-confirm" id="completionRemarksSave">Send to student</button>
      </div>
    </div>
  </div>

  <div class="main-content">
    <div class="header">
      <h1 style="display:flex;align-items:center;gap:14px;">Consultation Log
        <button id="print-logs-btn" type="button" class="print-logs-btn" title="Print Consultation Log">
          <i class='bx bx-printer'></i><span class="print-label">Print</span>
        </button>
      </h1>
    </div>
    <div class="search-container">
      <input type="text" id="searchInput" placeholder="Search..." style="flex:1;"
        autocomplete="off" spellcheck="false" maxlength="100"
        pattern="[A-Za-z0-9 .,@_-]{0,100}" aria-label="Search consultation">
      <button type="button" class="filters-btn" id="openFiltersBtn" aria-label="Open filters" title="Filters">
        <i class='bx bx-slider-alt'></i>
      </button>
      <div class="filter-group-horizontal filter-fixed">
        @php
          $bookingsFiltered = collect($bookings ?? [])->values();
          // Align subject filter with the consultation type labels shown in the table
          $subjects = collect($bookingsFiltered ?? [])->pluck('type')->filter(fn($s)=>filled($s))
                       ->map(fn($s)=>trim($s))->unique()->sort()->values();
        @endphp
        <select id="subjectFilter" class="filter-select" aria-label="Subject filter">
          <option value="">All Subjects</option>
          @foreach($subjects as $s)
            <option value="{{ $s }}">{{ $s }}</option>
          @endforeach
        </select>
      </div>
      <div class="filter-group-horizontal filter-fixed">
        <select id="typeFilter" class="filter-select" aria-label="Type filter">
          <option value="">All Types</option>
          @php
            $fixedTypes = [
              'Tutoring',
              'Grade Consultation',
              'Missed Activities',
              'Special Quiz or Exam',
              'Capstone Consultation'
            ];
          @endphp
          @foreach($fixedTypes as $type)
            <option value="{{ $type }}">{{ $type }}</option>
          @endforeach
          <option value="Others">Others</option>
        </select>
      </div>
      <div class="filter-group-horizontal filter-fixed">
        <select id="termFilter" class="filter-select" aria-label="Term filter">
          <option value="all">All Terms</option>
          @foreach($termList as $term)
            <option value="{{ $term['id'] }}" @if($activeTermId === $term['id']) selected @endif>{{ $term['label'] }}</option>
          @endforeach
        </select>
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
        <div class="table-row table-header" id="profConlogHeader">
          <div class="table-cell">No.</div>
          <div class="table-cell sort-header" data-sort="student" role="button" tabindex="0">Student <span class="sort-icon"></span></div>
          <div class="table-cell sort-header" data-sort="subject" role="button" tabindex="0">Subject <span class="sort-icon"></span></div>
          <div class="table-cell sort-header" data-sort="date" role="button" tabindex="0">Date <span class="sort-icon"></span></div>
          <div class="table-cell sort-header" data-sort="type" role="button" tabindex="0">Type <span class="sort-icon"></span></div>
          <div class="table-cell sort-header" data-sort="mode" role="button" tabindex="0">Mode <span class="sort-icon"></span></div>
          <div class="table-cell sort-header" data-sort="status" role="button" tabindex="0">Status <span class="sort-icon"></span></div>
          <div class="table-cell" style="width: 180px">Action</div>
        </div>
    
        <!-- Dynamic Data Rows -->
  @forelse($bookingsFiltered as $b)
  @php
    $statusLower = strtolower($b->Status ?? '');
    $statusLabels = [
      'completion_pending' => 'Awaiting Student Review',
      'completion_declined' => 'Student Declined Completion',
    ];
    $statusLabel = $statusLabels[$statusLower] ?? ($statusLower === 'cancelled' ? 'Cancelled' : ucfirst($b->Status));
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
  @php
    $studentIdRaw = trim((string) ($b->student_id ?? ''));
    $studentDisplayId = $studentIdRaw !== '' ? $studentIdRaw : 'N/A';
  @endphp
  <div class="table-row {{ $statusLower === 'cancelled' ? 'cancelled-booking' : '' }}"
    data-student="{{ strtolower($b->student) }}"
    data-student-id="{{ $studentIdRaw }}"
    data-subject="{{ strtolower($b->subject) }}"
    data-date="{{ \Carbon\Carbon::parse($b->Booking_Date)->format('Y-m-d') }}"
    data-date-ts="{{ \Carbon\Carbon::parse($b->Booking_Date)->timestamp }}"
    data-booked="{{ \Carbon\Carbon::parse($b->Created_At)->timezone('Asia/Manila')->format('Y-m-d H:i:s') }}"
    data-booked-ts="{{ \Carbon\Carbon::parse($b->Created_At)->timezone('Asia/Manila')->timestamp }}"
    data-type="{{ strtolower($b->type) }}"
    data-mode="{{ strtolower($b->Mode) }}"
    data-status="{{ $statusLower }}"
    data-term-id="{{ $b->term_id ?? '' }}"
    data-completion-reason="{{ e($completionReason) }}"
    data-completion-requested="{{ $completionRequestedAt }}"
    data-completion-reviewed="{{ $completionReviewedAt }}"
    data-completion-response="{{ e($b->completion_student_response ?? '') }}"
  data-completion-comment="{{ e($b->completion_student_comment ?? '') }}"
  data-is-completion-pending="{{ $statusLower === 'completion_pending' ? '1' : '0' }}"
    data-matched="1"
  >
    <div class="table-cell" data-label="No." data-booking-id="{{ $b->Booking_ID }}">{{ $loop->iteration }}</div>
          <div class="table-cell" data-label="Student">
            <div class="student-cell">
              <span class="student-id" data-field="student-id">{{ $studentDisplayId }}</span>
              <span class="student-name-line">
                <span class="student-name" data-field="student-name">{{ $b->student }}</span>
              </span>
            </div>
          </div>
          <div class="table-cell" data-label="Subject">{{ $b->subject }}</div>
          <div class="table-cell" data-label="Date">{{ \Carbon\Carbon::parse($b->Booking_Date)->format('D, M d Y') }}</div>
          <div class="table-cell" data-label="Type">{{ $b->type }}</div>
          <div class="table-cell" data-label="Mode">{{ ucfirst($b->Mode) }}</div>
          <div class="table-cell" data-label="Status" @if($completionReason) title="{{ 'Remarks: '.$completionReason }}" @endif>{{ $statusLabel }}</div>
          <div class="table-cell" data-label="Action" style="width: 180px;">
            <div class="action-btn-group" style="display: flex; gap: 8px;">
              @php
                $canRequestCompletion = in_array($statusLower, ['approved', 'completion_declined']);
              @endphp
              @if(!in_array($statusLower, ['completed', 'cancelled', 'completion_pending']))
                @if($statusLower !== 'rescheduled')
                <button 
                  onclick="showRescheduleModal({{ $b->Booking_ID }}, '{{ $b->Booking_Date }}', '{{ $b->Mode }}')" 
                  class="action-btn btn-reschedule"
                  title="Reschedule"
                >
                  <i class='bx bx-calendar-x'></i>
                </button>
                @endif

                @if(!in_array($statusLower, ['approved', 'completion_declined']))
                <button 
                  onclick="approveWithWarning(this, {{ $b->Booking_ID }}, '{{ $b->Booking_Date }}')" 
                  class="action-btn btn-approve"
                  title="Approve"
                >
                  <i class='bx bx-check-circle'></i>
                </button>
                @endif
                @if($canRequestCompletion)
                <button 
                  onclick="openCompletionRemarks(this, {{ $b->Booking_ID }})" 
                  class="action-btn btn-completed"
                  title="Request completion confirmation"
                >
                  <i class='bx bx-task'></i>
                </button>
                @else
                <button 
                  type="button"
                  class="action-btn btn-muted"
                  title="Approve this consultation before requesting completion confirmation"
                  data-need-approval="1"
                >
                  <i class='bx bx-task'></i>
                </button>
                @endif
              @endif
              @if($statusLower === 'completion_pending')
                <button type="button" class="action-btn btn-muted" title="Awaiting student confirmation" disabled>
                  <i class='bx bx-time'></i>
                </button>
              @endif
            </div>
          </div>
        </div>
        @empty
          <div class="table-row no-results-row">
            <div class="table-cell" style="text-align:center;color:#666;font-style:italic;">No Consultations Found.</div>
          </div>
        @endforelse
      <!-- Spacer removed: layout handled by CSS margins -->
      </div>
    </div>

    <!-- Pagination controls (no left info) -->
    <div class="pagination-bar">
      <div class="pagination-right">
        <div id="paginationControls" class="pagination"></div>
      </div>
    </div>

    <!-- Bottom scroll spacer for mobile chat overlay clearance -->
    <div class="bottom-safe-space" aria-hidden="true"></div>

    <!-- Top-right notification banner -->
    <div id="notification" class="notification" style="display:none;">
      <span id="notification-message"></span>
      <button type="button" class="close-btn" onclick="hideNotification()" aria-label="Close">&times;</button>
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
            <label class="filter-label" for="termFilterMobile">Term</label>
            <select id="termFilterMobile" class="filter-select" aria-label="Term (mobile)">
              <option value="all">All Terms</option>
              @foreach($termList as $term)
                <option value="{{ $term['id'] }}">{{ $term['label'] }}</option>
              @endforeach
            </select>
          </div>
          <div class="filter-group">
            <label class="filter-label" for="typeFilterMobile">Type</label>
            <select id="typeFilterMobile" class="filter-select" aria-label="Type (mobile)">
              <option value="">All Types</option>
              @foreach($fixedTypes as $type)
                <option value="{{ $type }}">{{ $type }}</option>
              @endforeach
              <option value="Others">Others</option>
            </select>
          </div>
          <div class="filter-group">
            <label class="filter-label" for="subjectFilterMobile">Subject</label>
            <select id="subjectFilterMobile" class="filter-select" aria-label="Subject (mobile)">
              <option value="">All Subjects</option>
              @foreach($subjects as $s)
                <option value="{{ $s }}">{{ $s }}</option>
              @endforeach
            </select>
          </div>
        </div>
        <div class="filters-drawer-footer">
          <button type="button" class="btn-reset" id="resetFiltersBtn">Reset</button>
          <button type="button" class="btn-apply" id="applyFiltersBtn">Apply</button>
        </div>
      </div>
    </div>

    <!-- Print Preview Overlay -->
    <div class="preview-overlay" id="pdfPreviewOverlay" aria-hidden="true">
      <div class="preview-modal" role="dialog" aria-modal="true" aria-labelledby="pdfPreviewTitle">
        <div class="preview-header">
          <h2 id="pdfPreviewTitle">Print Preview</h2>
          <button type="button" class="preview-close" id="closePreviewBtn" aria-label="Close preview">×</button>
        </div>
        <div class="preview-body">
          <iframe id="pdfPreviewFrame" title="Consultation log preview" loading="lazy"></iframe>
        </div>
        <div class="preview-footer">
          <label for="pdfFileName" class="preview-label">File name</label>
          <div class="preview-actions">
            <input type="text" id="pdfFileName" class="preview-input" value="" autocomplete="off" spellcheck="false" aria-label="PDF file name">
            <button type="button" id="downloadPdfBtn" class="preview-download">Download</button>
          </div>
        </div>
      </div>
    </div>

    <button class="chat-button" onclick="toggleChat()">
      <i class='bx bxs-message-rounded-dots'></i>
      Click to chat with me!
    </button>

    <!-- Chat Overlay Panel -->
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
        <button type="button" class="quick-reply" data-message="What are my consultations for today?">Today's consultations</button>
        <button type="button" class="quick-reply" data-message="Who are the students scheduled for consultation today?">Students today</button>
        <button type="button" class="quick-reply" data-message="What are my consultations for this week?">This week</button>
        <button type="button" class="quick-reply" data-message="How many consultation slots are still available today?">Slots today</button>
        <button type="button" class="quick-reply" data-message="What is my schedule?">My schedule</button>
      </div>
      <button type="button" id="quickRepliesToggle" class="quick-replies-toggle" style="display:none" title="Show FAQs">
        <i class='bx bx-help-circle'></i>
      </button>
      <form id="chatForm">
        <input type="text" id="message" placeholder="Type your message" required
               autocomplete="off" spellcheck="false" maxlength="250"
               pattern="[A-Za-z0-9 .,@_!?-]{1,250}" aria-label="Chat message">
        <button type="submit">Send</button>
      </form>
    </div>

    <!-- Reschedule Modal -->
    <div class="reschedule-overlay" id="rescheduleOverlay">
      <div class="reschedule-modal">
        <div class="reschedule-header">
          <h3>Reschedule Consultation</h3>
          <button class="close-btn" onclick="closeRescheduleModal()">×</button>
        </div>
        <div class="reschedule-body">
          <p><strong>Current Date:</strong> <span id="currentDate"></span></p>
          <div class="date-input-group">
            <label for="newDate">Select New Date:</label>
            <input type="text" id="newDate" class="date-input" placeholder="YYYY-MM-DD" required>
          </div>
          <div class="date-input-group">
            <label for="rescheduleReason">Reason for Rescheduling:</label>
            <textarea id="rescheduleReason" class="date-input" rows="3" placeholder="Please provide a reason for rescheduling this consultation..." required></textarea>
          </div>
          <div class="reschedule-buttons">
            <button type="button" class="btn-cancel" onclick="closeRescheduleModal()">Cancel</button>
            <button type="button" class="btn-confirm" onclick="confirmReschedule()">Reschedule</button>
          </div>
        </div>
      </div>
    </div>

    <!-- Generic Confirmation Modal -->
    <div class="confirm-overlay" id="confirmOverlay" aria-hidden="true">
      <div class="confirm-modal" role="dialog" aria-modal="true" aria-labelledby="confirmTitle">
        <div class="confirm-header">
          <i class='bx bx-help-circle'></i>
          <div id="confirmTitle">Please confirm your action</div>
        </div>
        <div class="confirm-body">
          <div id="confirmMessage">Are you sure you want to continue?</div>
        </div>
        <div class="confirm-actions">
          <button type="button" class="btn-cancel-red" id="confirmCancelBtn">Cancel</button>
          <button type="button" class="btn-confirm-green" id="confirmOkBtn">Yes, proceed</button>
        </div>
      </div>
    </div>

    <!-- Approval Warning Modal -->
    <div class="reschedule-overlay approval-warning-modal" id="approvalWarningOverlay">
      <div class="reschedule-modal approval-warning-content">
        <div class="reschedule-header">
          <h3>⚠️ High Volume Warning</h3>
          <button class="close-btn" onclick="closeApprovalWarningModal()">×</button>
        </div>
        <div class="reschedule-body approval-warning-body">
          <div class="warning-info">
            <p>
              <i class='bx bx-info-circle'></i>
              This date already has <span id="existingConsultationsCount">5</span> approved consultations
            </p>
            <p>
              <strong>Date:</strong> <span id="warningDate"></span>
            </p>
          </div>
          <p class="warning-text">
            Are you sure you want to approve another consultation for this date? This will bring your total to <span id="totalAfterApproval">6</span> consultations.
          </p>
          <div class="reschedule-buttons">
            <button type="button" class="btn-cancel" onclick="showRescheduleFromWarning()">Reschedule Instead</button>
            <button type="button" class="btn-confirm" onclick="confirmApproval()">
              Yes, Approve Anyway
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Hidden printable container -->
    <div id="printLogsContainer" style="display:none;">
      <div class="print-header">
        <h2>Professor Consultation Log</h2>
  <div id="printProfessor" class="print-professor" 
      data-prof-name="{{ optional(auth()->guard('professor')->user())->Name ?? (auth()->user()->Name ?? auth()->user()->name ?? '') }}"
      data-prof-id="{{ optional(auth()->guard('professor')->user())->Prof_ID ?? (auth()->user()->Prof_ID ?? auth()->user()->id ?? '') }}"
      data-prof-schedule="{{ optional(auth()->guard('professor')->user())->Schedule ?? '' }}">
  </div>
  <div id="printMeta" class="print-meta"></div>
      </div>
      <table class="print-table" id="printLogsTable">
        <thead>
          <tr>
            <th>No.</th>
            <th>Student</th>
            <th>Subject</th>
            <th>Date</th>
            <th>Type</th>
            <th>Mode</th>
            <th>Status</th>
            <th>Remarks</th>
          </tr>
        </thead>
        <tbody></tbody>
      </table>
      <div id="printFooter" class="print-footer-note"></div>
    </div>
  </div>
  <script src="https://cdn.jsdelivr.net/npm/pikaday/pikaday.js"></script>
  <script>
    window.profTermContext = {
      activeTermId: @json($activeTermId),
      activeTerm: @json($activeTermMeta),
      terms: @json($termList),
    };
  </script>
  <script>
    let currentBookingId = null;
    let currentRescheduleButton = null;
    let reschedulePicker = null;
    // Sets/maps used by disableDayFn (populated on modal open)
    let __resAllowedWeekdays = new Set(); // 1-5 = Mon-Fri
    let __resBlockedIso = new Set(); // 'YYYY-MM-DD' dates blocked by overrides
    let __resForcedByIso = new Map(); // iso -> forced_mode string ('online'|'onsite')
    let __resOriginalIso = null; // original booking date ISO (disabled)
  let __resFullIso = new Set(); // fully-booked dates (disabled)

    function parseAllowedWeekdaysFromSchedule(scheduleText){
      const set = new Set();
      if(!scheduleText) return set;
      try{
        const lines = String(scheduleText).split(/\n|<br\s*\/>/i).map(s=>s.trim()).filter(Boolean);
        const nameToNum = { Monday:1, Tuesday:2, Wednesday:3, Thursday:4, Friday:5 };
        lines.forEach(line=>{
          const m = line.match(/^(Monday|Tuesday|Wednesday|Thursday|Friday)\b/i);
          if(m){
            const key = m[1].charAt(0).toUpperCase()+m[1].slice(1).toLowerCase();
            const n = nameToNum[key]; if(n) set.add(n);
          }
        });
      }catch(_){ }
      return set;
    }

    function isoFromDateObj(d){
      try { return `${d.getFullYear()}-${String(d.getMonth()+1).padStart(2,'0')}-${String(d.getDate()).padStart(2,'0')}`; } catch(_){ return ''; }
    }

    function makeRescheduleDisableDayFn(){
      return function(date){
        const day = date.getDay(); // 0 Sun..6 Sat
        // Always block weekends
        if(day===0 || day===6) return true;
        // Block the original date itself
        try{ const iso = isoFromDateObj(date); if (__resOriginalIso && iso === __resOriginalIso) return true; }catch(_){ }
        // Overrides: if blocked and not specifically force-open by mode, disable
        const iso = isoFromDateObj(date);
        // Fully booked dates disabled
        if(__resFullIso.has(iso)) return true;
        if(__resBlockedIso.has(iso)) return true;
        // If no schedule at all, block weekdays unless there's a forced mode override
        if(__resAllowedWeekdays.size===0){
          return __resForcedByIso.has(iso) ? false : true;
        }
        // If weekday not in allowed schedule, allow only if forced mode override exists
        if(!__resAllowedWeekdays.has(day)){
          return __resForcedByIso.has(iso) ? false : true;
        }
        return false; // allowed
      };
    }

  function showRescheduleModal(bookingId, currentDate, bookingMode) {
      // Guard: block actions if not allowed per first-book rule
      try{ if(isActionBlockedFor(bookingId)){ showProfessorModal('Please take action on the first student who booked for this date before acting on others.'); return; } }catch(_){ }
      currentBookingId = bookingId;
      // Resolve button context only if invoked via a click event
      try{
        const ev = (typeof event !== 'undefined') ? event : null;
        currentRescheduleButton = ev && ev.target && ev.target.closest ? ev.target.closest('button') : currentRescheduleButton;
      }catch(_){ /* ignore */ }
      
  // Set current date in the modal
  document.getElementById('currentDate').textContent = currentDate;
  // Capture original ISO for disallowing same-day pick
  try { const d = new Date(currentDate); __resOriginalIso = isoFromDateObj(d); } catch(_) { __resOriginalIso = null; }
      
      // Prepare schedule context for disableDayFn
      const profIdEl = document.getElementById('printProfessor');
      const profSchedule = profIdEl ? (profIdEl.getAttribute('data-prof-schedule')||'') : '';
      __resAllowedWeekdays = parseAllowedWeekdaysFromSchedule(profSchedule);
      __resBlockedIso = new Set();
      __resForcedByIso = new Map();

      // Initialize/Reset Pikaday on the input
      const dateInput = document.getElementById('newDate');
      if(reschedulePicker && typeof reschedulePicker.destroy==='function'){
        reschedulePicker.destroy(); reschedulePicker = null;
      }
      dateInput.value = '';
      reschedulePicker = new Pikaday({
        field: dateInput,
        format: 'YYYY-MM-DD',
        firstDay: 1,
        minDate: new Date(),
        disableDayFn: makeRescheduleDisableDayFn(),
        onSelect: function(){
          // Keep ISO format for downstream validators
          dateInput.value = this.getMoment ? this.getMoment().format('YYYY-MM-DD') : this.toString();
          dateInput.dispatchEvent(new Event('input', { bubbles: true }));
        }
      });

      // Detect original mode from parameter or table cell fallback
      let originalMode = (bookingMode||'').toLowerCase();
      if(!originalMode){
        // Prefer resolving via the row with matching booking id
        let row = null;
        try{
          const cell = document.querySelector(`.table .table-cell[data-booking-id="${bookingId}"]`);
          row = cell ? cell.closest('.table-row') : null;
        }catch(_){ }
        if(!row && currentRescheduleButton) row = currentRescheduleButton.closest('.table-row');
        const modeCell = row ? row.querySelector('.table-cell[data-label="Mode"]') : null;
        originalMode = (modeCell ? (modeCell.textContent||'').trim().toLowerCase() : '').replace(/[^a-z]/g,'');
      }

      // Fetch fully booked dates and availability (with per-day mode) to enforce client-side rule
      Promise.all([
        fetch('/api/professor/fully-booked-dates').then(r=>r.json()).catch(()=>null),
        (function(){
          // Build a short range availability request for next 60 days
          try{
            const profId = profIdEl ? profIdEl.getAttribute('data-prof-id') : null;
            if(!profId) return Promise.resolve(null);
            const now = new Date();
            const start = now.toISOString().slice(0,10);
            const endDate = new Date(now.getFullYear(), now.getMonth()+2, 0);
            const end = endDate.toISOString().slice(0,10);
            return fetch(`/api/professor/availability?prof_id=${profId}&start=${start}&end=${end}`).then(r=>r.json()).catch(()=>null);
          }catch(e){ return Promise.resolve(null); }
        })()
      ]).then(([fullResp, availResp])=>{
        // Store fully-booked list for capacity validation
        dateInput.removeAttribute('data-full');
        __resFullIso.clear();
        if(fullResp && fullResp.success){
          dateInput.setAttribute('data-full', JSON.stringify(fullResp.dates));
          try{
            (fullResp.dates||[]).forEach(str=>{
              const d = new Date(str);
              if(!isNaN(d.getTime())){
                const iso = `${d.getFullYear()}-${String(d.getMonth()+1).padStart(2,'0')}-${String(d.getDate()).padStart(2,'0')}`;
                __resFullIso.add(iso);
              }
            });
          }catch(_){ }
        }

        // Store availability map to check per-day mode lock
        if(availResp && availResp.success){
          const map = {};
          (availResp.dates||[]).forEach(rec=>{ map[rec.date]=rec; });
          dateInput.setAttribute('data-avail', JSON.stringify(map));
          // Build overrides sets for disableDayFn (blocked/forced_mode)
          __resBlockedIso.clear(); __resForcedByIso.clear();
          (availResp.dates||[]).forEach(rec=>{
            // rec.date is like 'Mon Jan 01 2025' — convert to ISO
            try{
              const d = new Date(rec.date);
              const iso = `${d.getFullYear()}-${String(d.getMonth()+1).padStart(2,'0')}-${String(d.getDate()).padStart(2,'0')}`;
              if(rec.blocked) __resBlockedIso.add(iso);
              if(rec.forced_mode) __resForcedByIso.set(iso, rec.forced_mode);
            }catch(_){ }
          });
          // Redraw picker to apply new disable rules
          try{ reschedulePicker && reschedulePicker.draw && reschedulePicker.draw(); }catch(_){ }
        } else {
          dateInput.removeAttribute('data-avail');
        }

        // Attach one-time input validator combining capacity + mode rule
        dateInput.addEventListener('input', function(){
          try{
            if(!this.value){ this.setCustomValidity(''); return; }
            const d = new Date(this.value);
            const mapDays = ['Sun','Mon','Tue','Wed','Thu','Fri','Sat'];
            const mons = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
            const fmt = `${mapDays[d.getUTCDay()]} ${mons[d.getUTCMonth()]} ${('0'+d.getUTCDate()).slice(-2)} ${d.getUTCFullYear()}`;

            // Capacity check
            const full = JSON.parse(this.getAttribute('data-full')||'[]');
            if(full.includes(fmt)){
              this.setCustomValidity('This date is already fully booked (5 consultations). Choose another.');
              return;
            }

            // Mode rule: if the target date already has a lock, it must match original booking mode
            const avail = JSON.parse(this.getAttribute('data-avail')||'{}');
            const rec = avail[fmt];
            if(rec && rec.mode){
              if(originalMode && rec.mode !== originalMode){
                this.setCustomValidity(`This date is locked to ${rec.mode}. You can only reschedule this ${originalMode} booking to a ${originalMode} date.`);
                return;
              }
            }
            // If no lock on that day: allowed client-side; backend will enforce final rule
            this.setCustomValidity('');
          }catch(e){ this.setCustomValidity(''); }
        }, { once: true });

        document.getElementById('rescheduleOverlay').style.display = 'flex';
      }).catch(()=>{
        document.getElementById('rescheduleOverlay').style.display = 'flex';
      });
    }

    function closeRescheduleModal() {
      document.getElementById('rescheduleOverlay').style.display = 'none';
      currentBookingId = null;
      currentRescheduleButton = null;
      if(reschedulePicker && typeof reschedulePicker.destroy==='function'){
        try{ reschedulePicker.destroy(); }catch(_){ }
        reschedulePicker = null;
      }
      
      // Clear form fields
      document.getElementById('newDate').value = '';
      document.getElementById('rescheduleReason').value = '';
    }

    // Function to show reschedule modal from the approval warning
    function showRescheduleFromWarning() {
      if (!pendingApprovalBookingId) {
        showProfessorModal('Error: No booking selected for rescheduling.');
        return;
      }

      // Get the booking date from the warning modal
      const warningDateElement = document.getElementById('warningDate');
      const currentDate = warningDateElement ? warningDateElement.textContent : '';

      // Close the approval warning modal
      closeApprovalWarningModal();

      // Set up the reschedule modal with the pending booking data
      currentBookingId = pendingApprovalBookingId;
      currentRescheduleButton = pendingApprovalButton;

      // Set current date in the modal
      document.getElementById('currentDate').textContent = currentDate;
      // Defer to main initializer to setup picker, availability, and open modal
      showRescheduleModal(currentBookingId, currentDate, '');

      // Clear the pending approval variables since we're now rescheduling
      pendingApprovalButton = null;
      pendingApprovalBookingId = null;
      return;
    }

    function confirmReschedule() {
      const newDate = document.getElementById('newDate').value;
      const reason = document.getElementById('rescheduleReason').value.trim();

          // Client-side capacity check using cached fully booked list
          const input = document.getElementById('newDate');
          try {
            if (newDate && input.getAttribute('data-full')) {
              const full = JSON.parse(input.getAttribute('data-full'));
              const d = new Date(newDate);
              const map = ['Sun','Mon','Tue','Wed','Thu','Fri','Sat'];
              const mons = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
              const fmt = `${map[d.getUTCDay()]} ${mons[d.getUTCMonth()]} ${('0'+d.getUTCDate()).slice(-2)} ${d.getUTCFullYear()}`;
              if (full.includes(fmt)) {
                showProfessorModal('That date is already fully booked (5 consultations). Please pick another date.');
                return;
              }
            }
          } catch(e) {}
      
      if (!newDate) {
        showProfessorModal('Please select a new date.');
        return;
      }
      
      if (!reason) {
        showProfessorModal('Please provide a reason for rescheduling.');
        return;
      }
      // Prevent choosing the same date as the original booking
      try {
        if (__resOriginalIso && newDate) {
          const iso = newDate;
          if (iso === __resOriginalIso) {
            showProfessorModal('Please choose a different date from the original booking.');
            return;
          }
        }
      } catch(_) {}
      
      if (!currentBookingId) {
        showProfessorModal('Error: Booking ID is missing. Please try again.');
        return;
      }
      
      // Store the values before closing modal (which sets them to null)
      const bookingId = currentBookingId;
      const rescheduleButton = currentRescheduleButton;
      
      // Convert date to a more readable format
      const dateObj = new Date(newDate);
      const options = { weekday: 'short', year: 'numeric', month: 'short', day: 'numeric' };
      const formattedDate = dateObj.toLocaleDateString('en-US', options);
      
      // Close modal (this sets currentBookingId and currentRescheduleButton to null)
      closeRescheduleModal();
      
      // Remove the button immediately for better UX
      if (rescheduleButton) {
        rescheduleButton.remove();
      }
      
      // Call the update function with the stored booking ID, date, and reason
      updateStatusWithDate(bookingId, 'rescheduled', formattedDate, reason);
    }

    function updateStatusWithDate(bookingId, status, newDate = null, reason = null) {
      
      const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
      
      if (!csrfToken) {
        showProfessorModal('Error: CSRF token not found. Please refresh the page and try again.');
        location.reload();
        return;
      }
      
      const explicitStatus = (status && typeof status === 'object' && typeof status.status === 'string') ? status.status : status;
      const requestBody = {
        id: bookingId,
        status: typeof explicitStatus === 'string' ? explicitStatus.toLowerCase() : String(explicitStatus || '').toLowerCase()
      };
      
      if (newDate) {
        requestBody.new_date = newDate;
      }
      
      if (reason) {
        requestBody.reschedule_reason = reason;
      }

      if (typeof status === 'object' && status !== null) {
        Object.assign(requestBody, status);
        if (typeof requestBody.status === 'string') {
          requestBody.status = requestBody.status.toLowerCase();
        }
      }
      
      fetch('/api/consultations/update-status', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': csrfToken
        },
        body: JSON.stringify(requestBody)
      })
      .then(response => {
        if (!response.ok) {
          throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.json();
      })
      .then(data => {
        if (data.success) {
          showProfessorModal('Success: ' + data.message);
          setTimeout(() => location.reload(), 3500); // Reload the page to reflect changes
        } else {
          showProfessorModal('Failed to update status: ' + data.message);
          setTimeout(() => location.reload(), 3500); // Reload to restore the original state
        }
      })
      .catch(error => {
        console.error('Fetch error:', error);
        showProfessorModal('Network error occurred while updating status. Please check your connection and try again.\n\nError: ' + error.message);
  setTimeout(() => location.reload(), 3500);
      });
    }

    function updateStatus(bookingId, status) {
      if(status && typeof status === 'object' && !Array.isArray(status)){
        const payload = Object.assign({ id: bookingId }, status);
      updateStatusWithDate(bookingId, payload);
        return;
      }
      updateStatusWithDate(bookingId, status);
    }

    function removeThisButton(btn, bookingId, status, completionReason = null) {
      const normalizedStatus = String(status||'').toLowerCase();
      const row = btn && btn.closest ? btn.closest('.table-row') : null;
      const shouldHandle = ['completed','cancelled','completionpending','completion_pending'].includes(normalizedStatus);

      if(shouldHandle && row){
        setRowStatusUI(bookingId, normalizedStatus, {
          row,
          completionReason,
          updateActions: true
        });
      } else if(btn && btn.remove){
        btn.remove();
      }

      try{
        const targetRow = row || findRowByBookingId(bookingId);
        if(targetRow){
          setRowStatusUI(bookingId, normalizedStatus, {
            row: targetRow,
            completionReason,
            updateActions: true
          });
        }
      }catch(_){ }

      const payload = { status };
      if(completionReason && (normalizedStatus === 'completionpending' || normalizedStatus === 'completion_pending')){
        payload.completion_reason = completionReason;
      }
      updateStatus(bookingId, payload);
    }

    // Confirmation modal logic
    let __confirmPending = null;
    function showConfirm(message, onConfirm){
      const overlay = document.getElementById('confirmOverlay');
      const msg = document.getElementById('confirmMessage');
      const ok = document.getElementById('confirmOkBtn');
      const cancel = document.getElementById('confirmCancelBtn');
      if(!overlay||!msg||!ok||!cancel){ if(typeof onConfirm==='function') onConfirm(false); return; }
      msg.textContent = message || 'Are you sure you want to continue?';
      // clear prior
      if(__confirmPending){ ok.removeEventListener('click', __confirmPending); }
      const handler = ()=>{ overlay.style.display='none'; document.removeEventListener('keydown', escHandler); onConfirm && onConfirm(true); };
      const cancelHandler = ()=>{ overlay.style.display='none'; document.removeEventListener('keydown', escHandler); onConfirm && onConfirm(false); };
      function escHandler(e){ if(e.key==='Escape'){ cancelHandler(); } }
      __confirmPending = handler;
      ok.addEventListener('click', handler, { once: true });
      cancel.onclick = cancelHandler;
      overlay.style.display='flex';
      setTimeout(()=>document.addEventListener('keydown', escHandler), 0);
    }

    // Completed flow: ask confirmation first
    let completionModalContext = { button: null, bookingId: null };

  function openCompletionRemarks(btn, bookingId){
    try{ if(isActionBlockedFor(bookingId)){ showProfessorModal('Please take action on the first student who booked for this date before acting on others.'); return; } }catch(_){ }

        const row = findRowByBookingId(bookingId);
        const statusNow = row ? String(row.dataset.status||'').toLowerCase() : '';
        const canRequest = ['approved','completion_declined'];
        if(statusNow && !canRequest.includes(statusNow)){
          showProfessorModal('Please approve this consultation before requesting completion confirmation.');
          return;
        }
        if(row && String(row.dataset.isCompletionPending||'') === '1'){
          showProfessorModal('This consultation is already awaiting student confirmation.');
          return;
        }

        completionModalContext = { button: btn, bookingId };
        const overlay = document.getElementById('completionRemarksOverlay');
        const textarea = document.getElementById('completionRemarksInput');
        const error = document.getElementById('completionRemarksError');
        const feedbackBox = document.getElementById('completionStudentFeedback');
        const feedbackText = document.getElementById('completionStudentFeedbackText');
        if(!overlay || !textarea || !error){
          showProfessorModal('Unable to open remarks modal. Please reload and try again.');
          return;
        }
        const priorReason = row ? decodeHtml(row.dataset.completionReason||'') : '';
        textarea.value = priorReason;
        error.style.display = 'none';
        if(feedbackBox && feedbackText){
          feedbackBox.style.display = 'none';
          feedbackText.textContent = '';
          if(row){
            const response = String(row.dataset.completionResponse||'').toLowerCase();
            const comment = decodeHtml(row.dataset.completionComment||'');
            const lines = [];
            if(response === 'agreed'){ lines.push('Student approved this request.'); }
            else if(response === 'declined'){ lines.push('Student declined this request.'); }
            if(comment){ lines.push(`Comment: ${comment}`); }
            if(lines.length){
              feedbackText.textContent = lines.join('\n');
              feedbackBox.style.display = 'block';
            }
          }
        }
        overlay.style.display = 'flex';
        setTimeout(()=>{ try{ textarea.focus(); }catch(_){ } }, 0);
    }

    function closeCompletionRemarks(){
      const overlay = document.getElementById('completionRemarksOverlay');
      if(overlay) overlay.style.display = 'none';
      const textarea = document.getElementById('completionRemarksInput');
      if(textarea) textarea.value = '';
      const error = document.getElementById('completionRemarksError');
      if(error) error.style.display = 'none';
      const feedbackBox = document.getElementById('completionStudentFeedback');
      if(feedbackBox) feedbackBox.style.display = 'none';
      const feedbackText = document.getElementById('completionStudentFeedbackText');
      if(feedbackText) feedbackText.textContent = '';
    }

    function submitCompletionRequest(){
      if(submitCompletionRequest.pending){ return; }
      const textarea = document.getElementById('completionRemarksInput');
      const error = document.getElementById('completionRemarksError');
      const overlay = document.getElementById('completionRemarksOverlay');
      if(!textarea || !error){ return; }
      const raw = sanitize(textarea.value||'');
      if(raw.length < 5){
        error.style.display = 'block';
        return;
      }
      error.style.display = 'none';
      closeCompletionRemarks();
      closeCompletionRemarks();
      submitCompletionRequest.pending = true;
      removeThisButton(completionModalContext.button, completionModalContext.bookingId, 'completion_pending', raw);
      setTimeout(()=>{ submitCompletionRequest.pending = false; }, 400);
    }

    // Variables to store approval context
    let pendingApprovalButton = null;
    let pendingApprovalBookingId = null;

    // New function to handle approval with warning
  function approveWithWarning(btn, bookingId, bookingDate) {
      console.log('approveWithWarning called:', { bookingId, bookingDate });
    // Guard: block actions if not allowed per first-book rule
    try{ if(isActionBlockedFor(bookingId)){ showProfessorModal('Please take action on the first student who booked for this date before acting on others.'); return; } }catch(_){}
      
      // Store the context for later use
      pendingApprovalButton = btn;
      pendingApprovalBookingId = bookingId;

      // Count existing approved consultations for this date
      fetch('/api/consultations')
        .then(response => response.json())
        .then(data => {
          // Filter consultations for the specific date with approved status
          const consultationsOnDate = data.filter(consultation => {
            const consultationDate = new Date(consultation.Booking_Date).toDateString();
            const targetDate = new Date(bookingDate).toDateString();
            return consultationDate === targetDate && consultation.Status.toLowerCase() === 'approved';
          });

          const approvedCount = consultationsOnDate.length;
          console.log('Approved consultations on', bookingDate, ':', approvedCount);

          // Show warning if already 5 or more approved consultations
          if (approvedCount >= 5) {
            showApprovalWarningModal(bookingDate, approvedCount);
          } else {
            // Ask confirmation then approve if less than 5
            showConfirm('Approve this consultation? A notification will be sent to the student.', (ok)=>{
              if(!ok) return; removeThisButton(btn, bookingId, 'Approved');
            });
          }
        })
        .catch(error => {
          console.error('Error fetching consultation data:', error);
          // If we can't fetch data, show a generic warning
          showProfessorModal('Unable to verify consultation count. Please try again.');
        });
// Custom Modal JS (moved to global scope)
function showProfessorModal(message) {
  document.getElementById('professorModalMessage').textContent = message;
  document.getElementById('professorModal').style.display = 'flex';
}
function closeProfessorModal() {
  document.getElementById('professorModal').style.display = 'none';
}
    }

  function showApprovalWarningModal(bookingDate, currentCount) {
      const modal = document.getElementById('approvalWarningOverlay');
      const dateElement = document.getElementById('warningDate');
      const countElement = document.getElementById('existingConsultationsCount');
      const totalElement = document.getElementById('totalAfterApproval');

      // Format the date nicely
      const formattedDate = new Date(bookingDate).toLocaleDateString('en-US', {
        weekday: 'long',
        year: 'numeric',
        month: 'long',
        day: 'numeric'
      });

      dateElement.textContent = formattedDate;
      countElement.textContent = currentCount;
      totalElement.textContent = currentCount + 1;

      modal.style.display = 'flex';
    }

  function closeApprovalWarningModal() {
      const modal = document.getElementById('approvalWarningOverlay');
      modal.style.display = 'none';
      
      // Clear the context
      pendingApprovalButton = null;
      pendingApprovalBookingId = null;
    }

    function confirmApproval() {
      if (pendingApprovalButton && pendingApprovalBookingId) {
        // Proceed with the approval after second confirmation
        closeApprovalWarningModal();
        showConfirm('You are approving despite high volume on this date. Continue?', (ok)=>{
          if(!ok) return; removeThisButton(pendingApprovalButton, pendingApprovalBookingId, 'Approved');
        });
      }
    }

    // Close modal when clicking outside of it
    document.addEventListener('click', function(event) {
      const rescheduleModal = document.getElementById('rescheduleOverlay');
      const approvalWarningModal = document.getElementById('approvalWarningOverlay');
      
      if (event.target === rescheduleModal) {
        closeRescheduleModal();
      }
      
      if (event.target === approvalWarningModal) {
        closeApprovalWarningModal();
      }
    });

    // Close modal with Escape key
    document.addEventListener('keydown', function(event) {
      if (event.key === 'Escape') {
        closeRescheduleModal();
        closeApprovalWarningModal();
      }
    });

    const fixedTypes = [
      'tutoring',
      'grade consultation',
      'missed activities',
      'special quiz or exam',
      'capstone consultation'
    ];

    // Basic front-end sanitizer to reduce junk / obvious attempt strings
    function sanitize(input, options){
      if(!input) return '';
      const opts = options || {};
      let cleaned = input.replace(/\/\*.*?\*\//g,''); // remove /* */ comments
      cleaned = cleaned.replace(/--/g,' '); // collapse double dashes
      cleaned = cleaned.replace(/[;`'"<>]/g,' '); // strip risky punctuation
      if(opts.preserveSpacing){
        cleaned = cleaned.replace(/[\r\n\t\f\v]+/g,' '); // flatten control whitespace
        cleaned = cleaned.replace(/\u00A0/g,' '); // normalize nbsp
      }else{
        cleaned = cleaned.replace(/\s+/g,' ').trim(); // normalize whitespace
      }
      return cleaned.slice(0,250); // enforce hard limit
    }

    function decodeHtml(value){
      if(!value) return '';
      const textarea = document.createElement('textarea');
      textarea.innerHTML = value;
      return textarea.value;
    }

    const STATUS_LABEL_MAP = {
      pending: 'Pending',
      approved: 'Approved',
      completed: 'Completed',
      cancelled: 'Cancelled',
      rescheduled: 'Rescheduled',
      completion_pending: 'Awaiting Student Review',
      completion_declined: 'Student Declined Completion'
    };

    function normalizeStatusKey(status){
      const key = String(status || '').toLowerCase().replace(/-/g,'_');
      if(key === 'completionpending') return 'completion_pending';
      if(key === 'completiondeclined') return 'completion_declined';
      return key;
    }

    function formatStatusLabel(status){
      const key = normalizeStatusKey(status);
      if(!key) return '';
      if(Object.prototype.hasOwnProperty.call(STATUS_LABEL_MAP, key)){
        return STATUS_LABEL_MAP[key];
      }
      return key.replace(/_/g,' ').replace(/\b\w/g, c => c.toUpperCase());
    }

    function setRowStatusUI(bookingId, status, opts = {}){
      const normalized = normalizeStatusKey(status);
      const row = opts.row || findRowByBookingId(bookingId);
      if(!row) return;
      row.dataset.status = normalized;
      if(normalized === 'completion_pending'){ row.dataset.isCompletionPending = '1'; }
      else { row.removeAttribute('data-is-completion-pending'); }
  if(normalized === 'completed'){ row.setAttribute('data-completed-lock','1'); }
  else { row.removeAttribute('data-completed-lock'); }
      if(opts.completionReason !== undefined){ row.dataset.completionReason = opts.completionReason || ''; }
      if(opts.completionRequested !== undefined){ row.dataset.completionRequested = opts.completionRequested || ''; }
      if(opts.completionReviewed !== undefined){ row.dataset.completionReviewed = opts.completionReviewed || ''; }
      if(opts.completionResponse !== undefined){ row.dataset.completionResponse = opts.completionResponse || ''; }
      if(opts.completionComment !== undefined){ row.dataset.completionComment = opts.completionComment || ''; }

      const statusCell = row.querySelector('.table-cell[data-label="Status"]');
      if(statusCell){
        statusCell.textContent = formatStatusLabel(normalized);
        statusCell.title = opts.completionReason ? `Remarks: ${opts.completionReason}` : '';
      }

      row.classList.toggle('cancelled-booking', normalized === 'cancelled');

      if(opts.updateActions !== false){
        const actionCell = row.querySelector('.table-cell[data-label="Action"]');
        if(actionCell){
          if(normalized === 'completion_pending'){
            actionCell.innerHTML = `<div class="action-btn-group" style="display:flex;gap:8px;"><button type="button" class="action-btn btn-muted" title="Awaiting student confirmation" disabled><i class='bx bx-time'></i></button></div>`;
          } else if(normalized === 'cancelled' || normalized === 'completed'){
            actionCell.innerHTML = `<div class="action-btn-group" style="display:flex;gap:8px;"></div>`;
          } else {
            const escapeInlineArg = (value)=>String(value||'')
              .replace(/\\/g,'\\\\')
              .replace(/'/g, "\\'")
              .replace(/\r?\n/g,' ')
              .trim();
            const dateLabel = row.querySelector('.table-cell[data-label="Date"]')?.textContent?.trim() || row.dataset.date || '';
            const modeLabel = row.querySelector('.table-cell[data-label="Mode"]')?.textContent?.trim() || '';
            const isoDate = row.dataset.date || '';
            const rescheduleParamDate = escapeInlineArg(dateLabel || isoDate);
            const approveParamDate = escapeInlineArg(isoDate || dateLabel);
            const rescheduleParamMode = escapeInlineArg(modeLabel);
            const buttons = [];
            const isApprovedLike = normalized === 'approved' || normalized === 'completion_declined';
            if(normalized !== 'rescheduled'){
              buttons.push(`<button onclick="showRescheduleModal(${bookingId}, '${rescheduleParamDate}', '${rescheduleParamMode}')" class="action-btn btn-reschedule" title="Reschedule"><i class='bx bx-calendar-x'></i></button>`);
            }
            if(!isApprovedLike){
              buttons.push(`<button onclick="approveWithWarning(this, ${bookingId}, '${approveParamDate}')" class="action-btn btn-approve" title="Approve"><i class='bx bx-check-circle'></i></button>`);
            }
            if(isApprovedLike){
              buttons.push(`<button onclick="openCompletionRemarks(this, ${bookingId})" class="action-btn btn-completed" title="Request completion confirmation"><i class='bx bx-task'></i></button>`);
            } else {
              buttons.push(`<button type="button" class="action-btn btn-muted" title="Approve this consultation before requesting completion confirmation" data-need-approval="1"><i class='bx bx-task'></i></button>`);
            }
            actionCell.innerHTML = `<div class="action-btn-group" style="display:flex;gap:8px;">${buttons.join('')}</div>`;
          }
        }
      }

      if(typeof markFirstBookingsProf === 'function'){ markFirstBookingsProf(); }
      if(typeof enforceFirstBookRule === 'function'){ enforceFirstBookRule(); }
    }

  function filterRows() {
    const si = document.getElementById('searchInput');
  const raw = si.value;
  const cleaned = sanitize(raw).slice(0,50); // search shorter cap
  const search = cleaned.toLowerCase();
    const type = (document.getElementById('typeFilter')?.value||'').toLowerCase();
    const subject = (document.getElementById('subjectFilter')?.value||'').toLowerCase();
    const rows = document.querySelectorAll('.table-row:not(.table-header)');
    rows.forEach(row => {
      if (row.classList.contains('no-results-row')) return;

      const rowType = (row.dataset.type||'').toLowerCase();
  const rowSubject = (row.dataset.type||'').toLowerCase();

      // Build searchable haystack from visible cells (all columns except No./Action)
      const hay = Array.from(row.querySelectorAll('.table-cell'))
        .filter(c => { const lbl=c.getAttribute('data-label')||''; return lbl !== 'No.' && lbl !== 'Action'; })
        .map(c => (c.textContent||'').toLowerCase().trim())
        .join(' ');

      const isOthers = fixedTypes.indexOf(rowType) === -1 && rowType !== '';
      const matchesType = !type || (type !== 'others' && rowType === type) || (type === 'others' && isOthers);
      const matchesSubject = !subject || rowSubject === subject;
      const matchesSearch = !search || hay.includes(search);

      row.dataset.matched = (matchesType && matchesSubject && matchesSearch) ? '1' : '0';
    });
    // Reset to first page and re-apply sorting/pagination + renumber
    currentPage = 1;
    profApply();
  }

  document.getElementById('searchInput').addEventListener('input', filterRows);
  document.getElementById('typeFilter').addEventListener('change', filterRows);
  document.getElementById('subjectFilter')?.addEventListener('change', filterRows);

    // Chat form hardening (local only – actual server still validates)
    (function(){
      const chatForm = document.getElementById('chatForm');
      const chatInput = document.getElementById('message');
      const chatBody = document.getElementById('chatBody');
      const quickReplies = document.getElementById('quickReplies');
      const quickRepliesToggle = document.getElementById('quickRepliesToggle');
      const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
      
      // toggleChat parity (also dim mobile bell if present)
      window.toggleChat = function(){
        const overlay = document.getElementById('chatOverlay');
        if(!overlay) return;
        overlay.classList.toggle('open');
        const isOpen = overlay.classList.contains('open');
        document.body.classList.toggle('chat-open', isOpen);
        const bell = document.getElementById('mobileNotificationBell');
        if(bell){ bell.style.zIndex = isOpen ? '0' : ''; bell.style.pointerEvents = isOpen ? 'none' : ''; bell.style.opacity = isOpen ? '0' : ''; }
      }

      if(!chatForm || !chatInput) return;
      chatInput.addEventListener('input', () => {
        const raw = chatInput.value;
        const hadTrailingSpace = /\s$/.test(raw);
        const cleaned = sanitize(raw, { preserveSpacing: true });
        const lostTrailing = hadTrailingSpace && !cleaned.endsWith(' ');
        const normalized = lostTrailing ? `${cleaned} ` : cleaned;
        if(normalized !== raw){
          const cursor = normalized.length;
          chatInput.value = normalized;
          try{ chatInput.setSelectionRange(cursor, cursor); }catch(_){ /* ignore unsupported */ }
        }
      });
      function sendQuick(text){ if(!text) return; chatInput.value = text; chatForm.dispatchEvent(new Event('submit')); }
      quickReplies?.addEventListener('click',(e)=>{ const btn=e.target.closest('.quick-reply'); if(btn){ sendQuick(btn.dataset.message); } });
      quickRepliesToggle?.addEventListener('click',()=>{ if(quickReplies){ quickReplies.style.display='flex'; quickRepliesToggle.style.display='none'; } });
      chatForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        const raw = chatInput.value;
  const cleaned = sanitize(raw, { preserveSpacing: true });
  if(!cleaned.trim()){ chatInput.value=''; chatInput.focus(); return; }
  chatInput.value = cleaned;

        if(quickReplies && quickReplies.style.display !== 'none'){
          quickReplies.style.display = 'none';
          if(quickRepliesToggle) quickRepliesToggle.style.display = 'flex';
        }

        // Show user bubble
  const displayMessage = cleaned.trimEnd();
  if(chatBody){ const um = document.createElement('div'); um.classList.add('message','user'); um.innerText = displayMessage; chatBody.appendChild(um); chatBody.scrollTop = chatBody.scrollHeight; }
        chatInput.value = '';

        try{
          const res = await fetch('/chat', { method:'POST', credentials:'same-origin', headers:{ 'Accept':'application/json','Content-Type':'application/json','X-CSRF-TOKEN':csrfToken }, body: JSON.stringify({ message: displayMessage }) });
          let reply = 'Server error.';
          if(res.ok){ const data = await res.json(); reply = data.reply || reply; } else { try{ const err = await res.json(); reply = err.message || reply; }catch(_){} }
          if(chatBody){ const bm = document.createElement('div'); bm.classList.add('message','bot'); bm.innerText = reply; chatBody.appendChild(bm); chatBody.scrollTop = chatBody.scrollHeight; }
        }catch(_){ if(chatBody){ const bm = document.createElement('div'); bm.classList.add('message','bot'); bm.innerText = 'Network error.'; chatBody.appendChild(bm); } }
      });
      chatInput.addEventListener('keydown', (e)=>{
        if(e.key==='Enter' && !e.shiftKey){
          e.preventDefault();
          chatForm.requestSubmit();
        }
      });
    })();

// ===== Sorting + Pagination (mirrors student log) =====
let sortKey = 'date';
let sortDir = 'desc';
let currentPage = 1;
let pageSize = parseInt(localStorage.getItem('proflog.pageSize')||'10',10);
if(![5,10,25,50,100].includes(pageSize)) pageSize = 10;
document.addEventListener('DOMContentLoaded',()=>{
  const ps=document.getElementById('pageSize');
  if(ps) ps.value=String(pageSize);
  const completionSave=document.getElementById('completionRemarksSave');
  const completionCancel=document.getElementById('completionRemarksCancel');
  completionSave?.addEventListener('click', submitCompletionRequest);
  completionCancel?.addEventListener('click', closeCompletionRemarks);
  initTermFilter();
});

function initTermFilter(){
  const ctx = window.profTermContext || {};
  const select = document.getElementById('termFilter');
  if(!select){ return; }

  const rows = Array.from(document.querySelectorAll('.table-row[data-term-id]')); // exclude header via attribute absence
  const actionButtons = Array.from(document.querySelectorAll('.action-btn'));
  actionButtons.forEach(btn => {
    if(!btn.dataset.originalDisabled){ btn.dataset.originalDisabled = btn.disabled ? '1' : '0'; }
    if(!btn.dataset.originalMuted){ btn.dataset.originalMuted = btn.classList.contains('btn-muted') ? '1' : '0'; }
  });

  const setReadOnly = (readOnly) => {
    actionButtons.forEach(btn => {
      const wasDisabled = btn.dataset.originalDisabled === '1';
      const wasMuted = btn.dataset.originalMuted === '1';
      if(readOnly){
        btn.setAttribute('disabled', 'disabled');
        btn.classList.add('btn-muted');
      }else{
        if(wasDisabled){
          btn.setAttribute('disabled', 'disabled');
        }else{
          btn.removeAttribute('disabled');
        }
        if(!wasMuted){ btn.classList.remove('btn-muted'); }
      }
    });
  };

  const applyFilter = (termId) => {
    const activeId = ctx.activeTermId ? String(ctx.activeTermId) : null;
    const normalized = termId ? String(termId) : (activeId || 'all');

    rows.forEach(row => {
      const rowTerm = row.dataset.termId ? String(row.dataset.termId) : '';
      let visible = false;
      if(normalized === 'all'){
        visible = true;
      }else if(normalized === 'unassigned'){
        visible = !rowTerm;
      }else{
        visible = rowTerm === normalized;
      }
      row.style.display = visible ? '' : 'none';
    });

    const readOnly = normalized === 'all' || (activeId && normalized !== activeId);
    setReadOnly(readOnly);
  };

  select.addEventListener('change', () => {
    const value = select.value || (ctx.activeTermId ? String(ctx.activeTermId) : 'all');
    applyFilter(value);
  });

  const initialValue = select.value || (ctx.activeTermId ? String(ctx.activeTermId) : 'all');
  applyFilter(initialValue);
}

function profGetRows(){
  return Array.from(document.querySelectorAll('.table .table-row'))
    .filter(r=>!r.classList.contains('table-header') && !r.classList.contains('no-results-row'));
}

function profCollectActivityTypes(rows){
  const uniques = new Map();
  (rows || profGetRows()).forEach(row=>{
    const cell = row.querySelector('.table-cell[data-label="Type"]');
    const raw = cell ? cell.textContent : (row.dataset.type || '');
    const label = String(raw || '').trim();
    if(!label) return;
    const key = label.toLowerCase();
    if(!uniques.has(key)) uniques.set(key, label);
  });
  return Array.from(uniques.values()).sort((a,b)=>a.localeCompare(b));
}

function profRebuildSubjectOptions(rows){
  const main = document.getElementById('subjectFilter');
  const mobile = document.getElementById('subjectFilterMobile');
  if(!main && !mobile) return;
  const labels = profCollectActivityTypes(rows);

  const norm = (value)=>String(value||'').toLowerCase();
  const originalMain = main ? String(main.value||'') : '';
  const originalMobile = mobile ? String(mobile.value||'') : '';

  const applyOptions = (select, previous, fallback)=>{
    if(!select) return '';
    const prev = typeof previous === 'string' ? previous : '';
    const fallbackValue = typeof fallback === 'string' ? fallback : '';
    select.innerHTML = '';
    const defaultOpt = document.createElement('option');
    defaultOpt.value = '';
    defaultOpt.textContent = 'All Subjects';
    select.appendChild(defaultOpt);
    labels.forEach(label=>{
      const opt = document.createElement('option');
      opt.value = label;
      opt.textContent = label;
      select.appendChild(opt);
    });
    const findMatch = (value)=>{
      if(!value) return '';
      const match = labels.find(item=>item.toLowerCase() === value.toLowerCase());
      return match || '';
    };
    const target = findMatch(prev) || findMatch(fallbackValue) || '';
    select.value = target;
    return target;
  };

  const resolvedMain = applyOptions(main, originalMain, '');
  applyOptions(mobile, originalMobile, resolvedMain);

  if(main && norm(originalMain) !== norm(resolvedMain)){
    if(!profRebuildSubjectOptions._pending){
      profRebuildSubjectOptions._pending = true;
      setTimeout(()=>{
        profRebuildSubjectOptions._pending = false;
        if(typeof filterRows === 'function') filterRows();
      }, 0);
    }
  }
}

function profSetSortIndicators(){
  const headers = document.querySelectorAll('#profConlogHeader .sort-header');
  headers.forEach(h=>{
    const icon = h.querySelector('.sort-icon');
    const key = h.getAttribute('data-sort');
    if(key===sortKey){ icon.textContent = sortDir==='asc' ? ' ▲' : ' ▼'; h.classList.add('active-sort'); }
    else { icon.textContent=''; h.classList.remove('active-sort'); }
  });
}

function profCompare(a,b){
  const get=(row,key)=>{
    if(key==='date') return Number(row.dataset.dateTs||0);
    return (row.dataset[key]||'')+'';
  };
  const va=get(a,sortKey), vb=get(b,sortKey);
  let cmp = (typeof va==='number' && typeof vb==='number')? (va-vb) : (va.localeCompare(vb));
  return sortDir==='asc'? cmp : -cmp;
}

function profApply(){
  const table=document.querySelector('.table'); if(!table) return;
  const header=document.getElementById('profConlogHeader');
  const rows=profGetRows();
  profRebuildSubjectOptions(rows);
  const matched=rows.filter(r=>r.dataset.matched==='1');
  const existingNo = document.querySelector('.no-results-row'); if(existingNo) existingNo.remove();
  if(matched.length===0){
    rows.forEach(r=>r.style.display='none');
    const noRow=document.createElement('div'); noRow.className='table-row no-results-row';
    noRow.innerHTML = `<div class="table-cell" style="text-align:center;padding:20px;color:#666;font-style:italic;grid-column:1 / -1;">No Consultations Found.</div>`;
    header.insertAdjacentElement('afterend', noRow);
    const pag=document.getElementById('paginationControls'); if(pag) pag.innerHTML='';
    profSetSortIndicators(); return;
  }
  matched.sort(profCompare);
  const frag=document.createDocumentFragment(); matched.forEach(r=>frag.appendChild(r)); table.appendChild(frag);
  const total=matched.length; const totalPages=Math.max(1, Math.ceil(total/pageSize));
  if(currentPage>totalPages) currentPage=totalPages;
  const start=(currentPage-1)*pageSize; const end=Math.min(total, start+pageSize)-1;
  const set=new Set(matched);
  rows.forEach(r=>{
    if(!set.has(r)) { r.style.display='none'; return; }
    const idx=matched.indexOf(r);
    const isVisible = (idx>=start && idx<=end);
    r.style.display = isVisible ? '' : 'none';
  });

  // Renumber the visible rows so the "No." column remains 1..N for the current page
  let displayCounter = 1;
  for(let i=start; i<=end; i++){
    const row = matched[i];
    if(!row) continue;
    const noCell = row.querySelector('.table-cell[data-label="No."]');
    if(noCell){ noCell.textContent = String(displayCounter++); }
  }
  const pag=document.getElementById('paginationControls');
  if(pag){
    const makeBtn=(label,target,disabled=false)=>{ const b=document.createElement('button'); b.className='page-btn'; b.textContent=label; b.disabled=disabled; b.addEventListener('click',()=>{ currentPage=target; profApply();}); return b; };
    pag.innerHTML='';
    const totalPagesCalc = Math.max(1, Math.ceil(total/pageSize));
    const prev = makeBtn('‹', Math.max(1,currentPage-1), currentPage===1); prev.classList.add('chev','prev'); pag.appendChild(prev);
    const lbl=document.createElement('span'); lbl.className='page-label'; lbl.textContent='Page'; pag.appendChild(lbl);
    const sel=document.createElement('select'); sel.className='page-select'; for(let p=1;p<=totalPagesCalc;p++){ const o=document.createElement('option'); o.value=String(p); o.textContent=String(p); if(p===currentPage) o.selected=true; sel.appendChild(o);} sel.addEventListener('change',(e)=>{ const v=parseInt(e.target.value,10)||1; currentPage=Math.min(Math.max(1,v), totalPagesCalc); profApply();}); pag.appendChild(sel);
    const of=document.createElement('span'); of.className='page-of'; of.textContent=`of ${totalPagesCalc}`; pag.appendChild(of);
    const next = makeBtn('›', Math.min(totalPagesCalc,currentPage+1), currentPage===totalPagesCalc); next.classList.add('chev','next'); pag.appendChild(next);
  }
  // Mark first-book-of-day after pagination/sort
  markFirstBookingsProf();
  // Enforce first-book-first-serve rule on action buttons
  enforceFirstBookRule();
  profSetSortIndicators();
}

// Compute per-date earliest booking and badge the student cell
// Sticky first-badge map: date => {id, ts}, persisted per professor in localStorage
window.__firstByDate = window.__firstByDate || new Map();

function __firstLsKey(){
  try{
    const profEl = document.getElementById('printProfessor');
    const pid = profEl ? (profEl.getAttribute('data-prof-id')||'') : '';
    return `firstByDate:prof:${pid||'unknown'}`;
  }catch(_){ return 'firstByDate:prof:unknown'; }
}

function __saveFirstMapToLs(){
  try{
    const obj = {};
    window.__firstByDate.forEach((v,k)=>{ obj[k] = { id: v.id, ts: v.ts }; });
    localStorage.setItem(__firstLsKey(), JSON.stringify(obj));
  }catch(_){ }
}

function __loadFirstMapFromLs(){
  try{
    const raw = localStorage.getItem(__firstLsKey());
    if(!raw) return false;
    const obj = JSON.parse(raw);
    if(!obj || typeof obj !== 'object') return false;
    window.__firstByDate = new Map(Object.entries(obj).map(([k,v])=>[k,{ id:Number(v.id), ts:Number(v.ts) }]));
    return true;
  }catch(_){ return false; }
}

function __updateFirstMapForRow(row){
  try{
    const status = (row.dataset.status||'').toLowerCase();
    if(status === 'cancelled') return;
    const date = row.dataset.date||'';
    const ts = Number(row.dataset.bookedTs);
    const idCell = row.querySelector('.table-cell[data-booking-id]');
    const id = idCell ? Number(idCell.getAttribute('data-booking-id')) : NaN;
    if(!date || !Number.isFinite(ts) || !Number.isFinite(id)) return;
    const cur = window.__firstByDate.get(date);
    if(!cur || ts < cur.ts){ window.__firstByDate.set(date, { id, ts }); __saveFirstMapToLs(); }
  }catch(_){ }
}

function __ensureFirstMapInitialized(){
  // Try loading a persisted map first
  if(window.__firstByDate && window.__firstByDate.size) return;
  if(__loadFirstMapFromLs() && window.__firstByDate.size) return;
  const rows = profGetRows();
  rows.forEach(r=>{ if(!r.classList.contains('no-results-row')) __updateFirstMapForRow(r); });
}

function markFirstBookingsProf(){
  __ensureFirstMapInitialized();
  const rows = profGetRows();
  const activeRows = rows.filter(r => (r.dataset.status||'').toLowerCase() !== 'cancelled');
  // If a new row arrives for a date we haven't seen, or with earlier ts, update map
  activeRows.forEach(r=>__updateFirstMapForRow(r));

  // Reconcile persisted map against DOM rows to avoid stale/mismatched entries
  try{
    const byDate = new Map();
    activeRows.forEach(r=>{ const d=r.dataset.date||''; if(!d) return; if(!byDate.has(d)) byDate.set(d, []); byDate.get(d).push(r); });
    byDate.forEach((list, date)=>{
      // compute earliest actually present in DOM
      let best = null; // {id, ts}
      list.forEach(r=>{
        const ts = Number(r.dataset.bookedTs);
        const idCell = r.querySelector('.table-cell[data-booking-id]');
        const id = idCell ? Number(idCell.getAttribute('data-booking-id')) : NaN;
        if(!Number.isFinite(ts) || !Number.isFinite(id)) return;
        if(!best || ts < best.ts) best = { id, ts };
      });
      if(!best){
        window.__firstByDate.delete(date);
        return;
      }
      const cur = window.__firstByDate.get(date);
      const ids = new Set(list.map(r=>{ const c=r.querySelector('.table-cell[data-booking-id]'); return c? Number(c.getAttribute('data-booking-id')):NaN; }));
      const curIsMissing = !cur || !ids.has(cur.id);
      if(best && (curIsMissing || cur.ts > best.ts)){
        window.__firstByDate.set(date, best);
      }
    });
    // Remove dates that no longer exist or have no active rows
    if(window.__firstByDate instanceof Map){
      const activeDates = new Set(activeRows.map(r=>r.dataset.date||'').filter(Boolean));
      Array.from(window.__firstByDate.keys()).forEach(dateKey=>{
        if(!activeDates.has(dateKey)) window.__firstByDate.delete(dateKey);
      });
    }
    __saveFirstMapToLs();
  }catch(_){ }
  rows.forEach(r=>{
    const studentCell = r.querySelector('.table-cell[data-label="Student"]');
    if(!studentCell) return;
    const existing = studentCell.querySelector('.first-book-badge');
    const status = (r.dataset.status||'').toLowerCase();
    if(status === 'cancelled'){
      if(existing) existing.remove();
      return;
    }
    const date = r.dataset.date||'';
    const idCell = r.querySelector('.table-cell[data-booking-id]');
    const myId = idCell ? Number(idCell.getAttribute('data-booking-id')) : NaN;
    const rec = date ? window.__firstByDate.get(date) : null;
    const isFirst = !!(rec && Number.isFinite(myId) && rec.id === myId);
    if(isFirst){
      if(!existing){
        const badge = document.createElement('span');
        badge.className = 'first-book-badge';
        badge.title = 'First to book for this date';
        badge.textContent = 'First';
        const nameLine = studentCell.querySelector('.student-name-line');
        if(nameLine){
          nameLine.appendChild(badge);
        } else {
          studentCell.appendChild(badge);
        }
      }
    } else if(existing){
      existing.remove();
    }
  });
}

// Disable or grey out actions for non-first bookings of the same date
function enforceFirstBookRule(){
  __ensureFirstMapInitialized();
  const rows = profGetRows().filter(r=>!r.classList.contains('no-results-row'));
  const activeRows = rows.filter(r => (r.dataset.status||'').toLowerCase() !== 'cancelled');
  const byDate = new Map();
  activeRows.forEach(r=>{ const d=r.dataset.date||''; if(!d) return; if(!byDate.has(d)) byDate.set(d, []); byDate.get(d).push(r); });

  byDate.forEach(list => {
    // Use sticky first map to identify the earliest booker
    const date = list[0]?.dataset.date || '';
    const rec = date ? window.__firstByDate.get(date) : null;
    if(!rec) return;
    // earliest rows: match booking id
    const earliest = list.filter(r=>{
      const idCell = r.querySelector('.table-cell[data-booking-id]');
      const id = idCell ? Number(idCell.getAttribute('data-booking-id')) : NaN;
      return Number.isFinite(id) && id === rec.id;
    });
    const earliestPending = earliest.some(r=> (r.dataset.status||'').toLowerCase() === 'pending');
    list.forEach(r=>{
      const isEarliest = earliest.includes(r);
      const lock = (!isEarliest && earliestPending);
      // mark dataset for guards
      if(lock){ r.dataset.actionsLocked = '1'; }
      else { delete r.dataset.actionsLocked; }
      // visually indicate lock by greying buttons (but keep clickable for error modal)
      const btns = r.querySelectorAll('.action-btn-group .action-btn');
      btns.forEach(b=>{
        if(lock){
          b.style.opacity = '0.45';
          b.style.cursor = 'not-allowed';
          const baseTitle = b.getAttribute('title') || '';
          b.setAttribute('data-base-title', baseTitle);
          b.title = 'Locked: act on the first booker for this date';
          b.setAttribute('aria-disabled','true');
        } else {
          b.style.opacity = '';
          b.style.cursor = '';
          if(b.hasAttribute('data-base-title')){ b.title = b.getAttribute('data-base-title'); b.removeAttribute('data-base-title'); }
          b.removeAttribute('aria-disabled');
        }
      });
    });
  });

  // Ensure cancelled rows never show lock affordances
  rows.forEach(r=>{
    if((r.dataset.status||'').toLowerCase() === 'cancelled'){
      delete r.dataset.actionsLocked;
      const btns = r.querySelectorAll('.action-btn-group .action-btn');
      btns.forEach(b=>{
        b.style.opacity = '';
        b.style.cursor = '';
        if(b.hasAttribute('data-base-title')){
          b.title = b.getAttribute('data-base-title');
          b.removeAttribute('data-base-title');
        }
        b.removeAttribute('aria-disabled');
      });
    }
  });
}

// Helper: find row by booking ID
function findRowByBookingId(bookingId){
  try{
    const cell = document.querySelector(`.table-row .table-cell[data-booking-id="${bookingId}"]`);
    return cell ? cell.closest('.table-row') : null;
  }catch(_){ return null; }
}

// Helper: check if actions are blocked for this booking (non-first while first is pending)
function isActionBlockedFor(bookingId){
  const row = findRowByBookingId(bookingId);
  return !!(row && row.dataset && row.dataset.actionsLocked === '1');
}

// Override filterRows to cooperate with pagination
function filterRows(){
  const si=document.getElementById('searchInput');
  const cleaned = sanitize(si.value||'').slice(0,50);
  const search=cleaned.toLowerCase();
  const type=(document.getElementById('typeFilter')?.value||'').toLowerCase();
  const subject=(document.getElementById('subjectFilter')?.value||'').toLowerCase();
  document.querySelectorAll('.table-row:not(.table-header)').forEach(row=>{
    if(row.classList.contains('no-results-row')) return;
    const rowType=(row.dataset.type||'').toLowerCase();
  const rowSubject=(row.dataset.type||'').toLowerCase();
    const hay = Array.from(row.querySelectorAll('.table-cell'))
      .filter(c => { const lbl=c.getAttribute('data-label')||''; return lbl!=='No.' && lbl!=='Action'; })
      .map(c => (c.textContent||'').toLowerCase().trim())
      .join(' ');
    const isOthers = !['tutoring','grade consultation','missed activities','special quiz or exam','capstone consultation'].includes(rowType) && rowType!=='';
    const matchesType = !type || (type!=='others' && rowType===type) || (type==='others' && isOthers);
    const matchesSubject = !subject || rowSubject===subject;
    const matchesSearch = !search || hay.includes(search);
    row.dataset.matched = (matchesType && matchesSubject && matchesSearch) ? '1' : '0';
  });
  currentPage = 1;
  profApply();
}

// listeners
document.getElementById('pageSize')?.addEventListener('change',(e)=>{
  pageSize = parseInt(e.target.value,10) || 10;
  localStorage.setItem('proflog.pageSize', String(pageSize));
  currentPage = 1;
  profApply();
});
document.querySelectorAll('#profConlogHeader .sort-header').forEach(h=>{
  const set=()=>{ const key=h.getAttribute('data-sort'); if(sortKey===key){ sortDir=(sortDir==='asc'?'desc':'asc'); } else { sortKey=key; sortDir=(key==='date'?'desc':'asc'); } profApply(); };
  h.addEventListener('click', set);
  h.addEventListener('keypress', (e)=>{ if(e.key==='Enter'||e.key===' '){ e.preventDefault(); set(); }});
});
document.addEventListener('DOMContentLoaded', ()=>{ filterRows(); });
// Guarantee first-badge is visible immediately on first paint (before any live updates)
document.addEventListener('DOMContentLoaded', ()=>{
  try {
    // Build first-map from current DOM and apply badges/locks instantly
    markFirstBookingsProf();
    enforceFirstBookRule();
  } catch(_) {}
});

// Inform professors why completion button is locked until approval
document.addEventListener('click', (event)=>{
  const blockedBtn = event.target && event.target.closest ? event.target.closest('button[data-need-approval]') : null;
  if(blockedBtn){
    event.preventDefault();
    event.stopPropagation();
    showNotification('Approve this consultation before requesting completion confirmation.', true);
  }
});

// Mobile filters overlay
function profSyncOverlay(){
  profRebuildSubjectOptions();
  const tMain=document.getElementById('typeFilter');
  const sMain=document.getElementById('subjectFilter');
  const termMain=document.getElementById('termFilter');
  const tMob=document.getElementById('typeFilterMobile');
  const sMob=document.getElementById('subjectFilterMobile');
  const termMob=document.getElementById('termFilterMobile');
  if(tMain && tMob) tMob.value=tMain.value;
  if(sMain && sMob) sMob.value=sMain.value;
  if(termMain && termMob) termMob.value=termMain.value;
}
function openFilters(){ const ov=document.getElementById('filtersOverlay'); if(!ov) return; profSyncOverlay(); ov.classList.add('open'); ov.setAttribute('aria-hidden','false'); document.body.style.overflow='hidden'; }
function closeFilters(){ const ov=document.getElementById('filtersOverlay'); if(!ov) return; ov.classList.remove('open'); ov.setAttribute('aria-hidden','true'); document.body.style.overflow=''; }
function applyFiltersFromOverlay(){ const tMain=document.getElementById('typeFilter'); const sMain=document.getElementById('subjectFilter'); const termMain=document.getElementById('termFilter'); const tMob=document.getElementById('typeFilterMobile'); const sMob=document.getElementById('subjectFilterMobile'); const termMob=document.getElementById('termFilterMobile'); if(tMain&&tMob){ tMain.value=tMob.value; tMain.dispatchEvent(new Event('change')); } if(sMain&&sMob){ sMain.value=sMob.value; sMain.dispatchEvent(new Event('change')); } if(termMain&&termMob){ termMain.value=termMob.value; termMain.dispatchEvent(new Event('change')); } closeFilters(); }
function resetFiltersOverlay(){ const tMob=document.getElementById('typeFilterMobile'); const sMob=document.getElementById('subjectFilterMobile'); const termMob=document.getElementById('termFilterMobile'); if(tMob) tMob.value=''; if(sMob) sMob.value=''; if(termMob) termMob.value='all'; }
document.getElementById('openFiltersBtn')?.addEventListener('click', openFilters);
document.getElementById('closeFiltersBtn')?.addEventListener('click', closeFilters);
document.getElementById('applyFiltersBtn')?.addEventListener('click', applyFiltersFromOverlay);
document.getElementById('resetFiltersBtn')?.addEventListener('click', resetFiltersOverlay);

    // Real-time updates for professor consultation log - DISABLED TO PREVENT DUPLICATE ROWS
    /*
    function loadProfessorConsultationLogs() {
      fetch('/api/professor/consultation-logs')
        .then(response => response.json())
        .then(data => {
          updateProfessorConsultationTable(data);
        })
        .catch(error => {
          console.error('Error loading professor consultation logs:', error);
        });
    }

    function updateProfessorConsultationTable(bookings) {
      const table = document.querySelector('.table');
      const header = document.querySelector('.table-header');
      
      // Clear existing rows except header
      const existingRows = table.querySelectorAll('.table-row:not(.table-header)');
      existingRows.forEach(row => row.remove());
      
      if (bookings.length === 0) {
        const emptyRow = document.createElement('div');
        emptyRow.className = 'table-row';
        emptyRow.innerHTML = `
          <div class="table-cell" colspan="9">No consultations found.</div>
        `;
        table.appendChild(emptyRow);
      } else {
        bookings.forEach((booking, index) => {
          const row = document.createElement('div');
          row.className = 'table-row';
          
          const bookingDate = new Date(booking.Booking_Date);
          const createdAt = new Date(booking.Created_At);
          
          let statusActions = '';
          if (booking.Status.toLowerCase() === 'pending') {
            statusActions = `
              <button class="action-btn approve-btn" onclick="updateStatus(${booking.Booking_ID}, 'approved')">Approve</button>
              <button class="action-btn reschedule-btn" onclick="showRescheduleModal(${booking.Booking_ID})">Reschedule</button>
            `;
          } else if (booking.Status.toLowerCase() === 'approved') {
            statusActions = `
              <button class="action-btn complete-btn" onclick="updateStatus(${booking.Booking_ID}, 'completed')">Complete</button>
              <button class="action-btn reschedule-btn" onclick="showRescheduleModal(${booking.Booking_ID})">Reschedule</button>
            `;
          } else {
            statusActions = `<span class="status-final">${booking.Status.charAt(0).toUpperCase() + booking.Status.slice(1)}</span>`;
          }
          
          row.innerHTML = `
            <div class="table-cell" data-label="No.">${index + 1}</div>
            <div class="table-cell" data-label="Student">
              <div class="student-cell">
                <span class="student-id" data-field="student-id">${booking.student_id || 'N/A'}</span>
                <span class="student-name-line">
                  <span class="student-name" data-field="student-name">${booking.student || 'N/A'}</span>
                </span>
              </div>
            </div>
            <div class="table-cell" data-label="Subject">${booking.subject}</div>
            <div class="table-cell" data-label="Date">${bookingDate.toLocaleDateString('en-US', { weekday: 'short', month: 'short', day: 'numeric', year: 'numeric' })}</div>
            <div class="table-cell" data-label="Type">${booking.type}</div>
            <div class="table-cell" data-label="Mode">${booking.Mode.charAt(0).toUpperCase() + booking.Mode.slice(1)}</div>
            <div class="table-cell" data-label="Status">${booking.Status.charAt(0).toUpperCase() + booking.Status.slice(1)}</div>
            <div class="table-cell action-cell" data-label="Action">${statusActions}</div>
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
    loadProfessorConsultationLogs();
    setInterval(loadProfessorConsultationLogs, 5000);
    */

    // Mobile notification functions for navbar
    function toggleMobileNotifications() {
      const dropdown = document.getElementById('mobileNotificationsDropdown');
      if (dropdown) {
        dropdown.classList.toggle('active');
        
        // Close sidebar if open
        const sidebar = document.getElementById('sidebar');
        if (sidebar && sidebar.classList.contains('active')) {
          sidebar.classList.remove('active');
        }
        
        // Load notifications if opening dropdown
        if (dropdown.classList.contains('active')) {
          loadMobileNotifications();
        }
      }
    }

    function loadMobileNotifications() {
      fetch('/api/professor/notifications')
        .then(response => response.json())
        .then(data => {
          displayMobileNotifications(data.notifications);
          updateMobileNotificationBadge();
        })
        .catch(error => {
          console.error('Error loading mobile notifications:', error);
        });
    }

    function displayMobileNotifications(notifications) {
      const mobileContainer = document.getElementById('mobileNotificationsContainer');
      if (!mobileContainer) return;
      
      if (notifications.length === 0) {
        mobileContainer.innerHTML = `
          <div class="no-notifications">
            <i class='bx bx-bell-off'></i>
            <p>No notifications yet</p>
          </div>
        `;
        return;
      }
      
      const notificationsHtml = notifications.map(notification => {
        const unreadClass = notification.is_read ? '' : 'unread';
        
        return `
          <div class="notification-item ${unreadClass}" onclick="markMobileNotificationAsRead(${notification.id})">
            <div class="notification-type ${notification.type}">${notification.type.replace('_', ' ')}</div>
            <div class="notification-title">${notification.title}</div>
            <div class="notification-message">${notification.message}</div>
            <div class="notification-time" data-timeago data-ts="${notification.created_at}"></div>
          </div>
        `;
      }).join('');
      
      mobileContainer.innerHTML = notificationsHtml;
    }

    function updateMobileNotificationBadge() {
      fetch('/api/professor/notifications/unread-count')
        .then(response => response.json())
        .then(data => {
          const mobileCountElement = document.getElementById('mobileNotificationBadge');
          if (mobileCountElement) {
            if (data.unread_count > 0) {
              mobileCountElement.textContent = data.unread_count;
              mobileCountElement.style.display = 'flex';
            } else {
              mobileCountElement.style.display = 'none';
            }
          }
        })
        .catch(error => {
          console.error('Error updating mobile notification badge:', error);
        });
    }

    function markMobileNotificationAsRead(notificationId) {
      fetch('/api/professor/notifications/mark-read', {
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
          loadMobileNotifications(); // Reload to update read status
        }
      })
      .catch(error => {
        console.error('Error marking mobile notification as read:', error);
      });
    }

    function markAllProfessorNotificationsAsRead() {
      fetch('/api/professor/notifications/mark-all-read', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        }
      })
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          loadMobileNotifications(); // Reload to update read status
        }
      })
      .catch(error => {
        console.error('Error marking all professor notifications as read:', error);
      });
    }

    // Live timeago handled by public/js/timeago.js

    // Initialize mobile notifications on page load
    document.addEventListener('DOMContentLoaded', function() {
      updateMobileNotificationBadge();
      // Update badge every 30 seconds
      setInterval(updateMobileNotificationBadge, 30000);
  const printBtn = document.getElementById('print-logs-btn');
   if (printBtn) printBtn.addEventListener('click', generateAndDownloadPdf);
    });
  </script>
  <script src="https://js.pusher.com/7.0/pusher.min.js"></script>
  <script>
    // Live updates: subscribe to the professor's booking channel and patch rows in-place
    (function(){
      try {
        const profEl = document.getElementById('printProfessor');
        const profId = profEl ? profEl.getAttribute('data-prof-id') : null;
        if(!profId) return;
        const pusher = new Pusher('{{ config('broadcasting.connections.pusher.key') }}', {cluster: '{{ config('broadcasting.connections.pusher.options.cluster') }}'});
  const channel = pusher.subscribe('bookings.prof.'+profId);

        const STATUS_LABELS = {
          'completion_pending': 'Awaiting Student Review',
          'completion_declined': 'Student Declined Completion',
          'cancelled': 'Cancelled'
        };

        function escapeAttr(value){
          return String(value ?? '')
            .replace(/&/g,'&amp;')
            .replace(/"/g,'&quot;')
            .replace(/</g,'&lt;')
            .replace(/>/g,'&gt;');
        }

        function escapeHtml(value){
          return String(value ?? '')
            .replace(/&/g,'&amp;')
            .replace(/</g,'&lt;')
            .replace(/>/g,'&gt;')
            .replace(/"/g,'&quot;')
            .replace(/'/g,'&#39;');
        }

        function buildStudentCellHtml(idValue, nameValue){
          const normalizedId = String(idValue ?? '').trim();
          const normalizedName = String(nameValue ?? '').trim();
          const safeId = escapeHtml(normalizedId !== '' ? normalizedId : 'N/A');
          const safeName = escapeHtml(normalizedName !== '' ? normalizedName : 'N/A');
          return (
            '<div class="student-cell">'
              + '<span class="student-id" data-field="student-id">' + safeId + '</span>'
              + '<span class="student-name-line">'
                  + '<span class="student-name" data-field="student-name">' + safeName + '</span>'
                + '</span>'
            + '</div>'
          );
        }

        function normalizeDate(str){ try{ return new Date(str).toLocaleDateString('en-US',{weekday:'short', month:'short', day:'numeric', year:'numeric'}); }catch(e){ return str; } }
        function formatStatusLabel(raw){
          const normalized = String(raw||'').toLowerCase();
          if(!normalized) return '';
          if(STATUS_LABELS[normalized]) return STATUS_LABELS[normalized];
          return normalized.replace(/_/g,' ').replace(/\b\w/g, letter => letter.toUpperCase());
        }
        function renderRow(data){
          const table = document.querySelector('.table');
          if(!table) return;
          // find existing row by Booking_ID
          const rows = Array.from(table.querySelectorAll('.table-row')).filter(r=>!r.classList.contains('table-header'));
          let existing = null; let index = 0;
          rows.forEach((r,i)=>{ const idCell = r.querySelector('[data-booking-id]'); if(idCell && parseInt(idCell.getAttribute('data-booking-id'))===parseInt(data.Booking_ID)){ existing = r; index=i; } });

          // If updating and some fields are missing, read them from the existing row
          if(existing){
            const cells = existing.querySelectorAll('.table-cell');
            if(data.student === undefined || data.student === null || String(data.student).trim() === ''){
              const nameSpan = existing.querySelector('.student-name[data-field="student-name"]');
              if(nameSpan && nameSpan.textContent.trim() !== ''){
                data.student = nameSpan.textContent.trim();
              } else if(cells[1]){
                const cellText = cells[1].textContent ? cells[1].textContent.trim() : '';
                if(cellText) data.student = cellText;
              }
              if(data.student === undefined || data.student === null || String(data.student).trim() === ''){
                const attrStudent = existing.getAttribute('data-student');
                if(attrStudent && attrStudent.trim() !== ''){
                  data.student = attrStudent;
                }
              }
            }
            if(data.student_id === undefined || data.student_id === null || String(data.student_id).trim() === ''){
              const attrId = existing.getAttribute('data-student-id');
              if(attrId && attrId.trim() !== ''){
                data.student_id = attrId;
              } else {
                const idSpan = existing.querySelector('.student-id[data-field="student-id"]');
                data.student_id = idSpan ? idSpan.textContent.trim() : '';
              }
            }
            data.subject = data.subject ?? (cells[2]?.textContent.trim()||'');
            data.Booking_Date = data.Booking_Date ?? (cells[3]?.textContent.trim()||'');
            data.type = data.type ?? (cells[4]?.textContent.trim()||'');
            data.Mode = data.Mode ?? (cells[5]?.textContent.trim().toLowerCase()||'');
            data.Status = data.Status ?? (cells[6]?.textContent.trim().toLowerCase()||'');
          }

          if(!existing){
            const hasEssentialFields = Boolean(data.student || data.subject || data.Booking_Date || data.type || data.Mode);
            if(!hasEssentialFields){ return; }
          }

          const mode = (data.Mode||'').charAt(0).toUpperCase() + (data.Mode||'').slice(1);
          // If the row was locally locked as completed, force completed state to avoid re-adding buttons
          const lockedCompleted = existing && existing.getAttribute('data-completed-lock') === '1';
          if (lockedCompleted) { data.Status = 'completed'; }
          const statusRaw = data.Status ?? '';
          const normalizedStatus = String(statusRaw).toLowerCase();
          const status = formatStatusLabel(normalizedStatus);
          const date = normalizeDate(data.Booking_Date||'');
          const iter = existing ? (existing.querySelector('.table-cell')?.textContent||'') : (rows.length+1);
          const studentIdValue = String(data.student_id ?? '').trim();
          const studentCellHtml = buildStudentCellHtml(studentIdValue, data.student||'');

          const escapeInlineArg = (value)=>String(value||'')
            .replace(/\\/g,'\\\\')
            .replace(/'/g, "\\'")
            .replace(/\r?\n/g,' ')
            .trim();
          const rescheduleDisplayArg = escapeInlineArg(date || data.Booking_Date || '');
          const approveDateRaw = (()=>{
            const parsed = new Date(data.Booking_Date);
            if(!isNaN(parsed.getTime())){
              return `${parsed.getFullYear()}-${String(parsed.getMonth()+1).padStart(2,'0')}-${String(parsed.getDate()).padStart(2,'0')}`;
            }
            if(existing){
              const prev = existing.getAttribute('data-date');
              if(prev) return prev;
            }
            return data.Booking_Date || '';
          })();
          const approveDateArg = escapeInlineArg(approveDateRaw);
          const modeArg = escapeInlineArg(mode);

          let actionsHtml = '';
          if (normalizedStatus === 'completed' || normalizedStatus === 'cancelled') {
            actionsHtml = `<div class="action-btn-group" style="display:flex;gap:8px;"></div>`;
          } else if (normalizedStatus === 'completion_pending') {
            actionsHtml = `<div class="action-btn-group" style="display:flex;gap:8px;"><button type="button" class="action-btn btn-muted" title="Awaiting student confirmation" disabled><i class='bx bx-time'></i></button></div>`;
          } else {
            const approvedLike = normalizedStatus === 'approved' || normalizedStatus === 'completion_declined';
            actionsHtml = `
            <div class="action-btn-group" style="display:flex;gap:8px;">
              ${normalizedStatus!=='rescheduled' ? `<button onclick="showRescheduleModal(${data.Booking_ID}, '${rescheduleDisplayArg}', '${modeArg}')" class="action-btn btn-reschedule" title="Reschedule"><i class='bx bx-calendar-x'></i></button>`:''}
              ${!approvedLike ? `<button onclick="approveWithWarning(this, ${data.Booking_ID}, '${approveDateArg}')" class="action-btn btn-approve" title="Approve"><i class='bx bx-check-circle'></i></button>`:''}
              ${approvedLike
                ? `<button onclick="openCompletionRemarks(this, ${data.Booking_ID})" class="action-btn btn-completed" title="Request completion confirmation"><i class='bx bx-task'></i></button>`
                : `<button type="button" class="action-btn btn-muted" title="Approve this consultation before requesting completion confirmation" data-need-approval="1"><i class='bx bx-task'></i></button>`}
            </div>`;
          }


          const statusTitle = data.completion_reason ? ` title="${escapeAttr('Remarks: '+data.completion_reason)}"` : '';
          const html = `
            <div class="table-cell" data-label="No.">${iter}</div>
            <div class="table-cell" data-label="Student">${studentCellHtml}</div>
            <div class="table-cell" data-label="Subject">${data.subject||''}</div>
            <div class="table-cell" data-label="Date">${date}</div>
            <div class="table-cell" data-label="Type">${data.type||''}</div>
            <div class="table-cell" data-label="Mode">${mode}</div>
            <div class="table-cell" data-label="Status"${statusTitle}>${status}</div>
            <div class="table-cell" data-label="Action" style="width:180px;">${actionsHtml}</div>`;

          if(existing){
            existing.innerHTML = html;
            existing.querySelector('.table-cell').setAttribute('data-booking-id', data.Booking_ID);
            // refresh dataset attributes for filtering/sorting/first badge
            try{
              const prevDateIso = existing.getAttribute('data-date');
              const prevDateTs = existing.getAttribute('data-date-ts');
              const dateObj = new Date(data.Booking_Date);
              // Created_At is sometimes omitted in updates; preserve previous booked-ts if missing
              let createdTs = null;
              if (data.Created_At) {
                const createdObj = new Date(data.Created_At);
                if (!isNaN(createdObj.getTime())) createdTs = Math.floor(createdObj.getTime()/1000);
              }
              if (createdTs === null) {
                const prevTs = Number(existing.getAttribute('data-booked-ts'));
                if (Number.isFinite(prevTs)) createdTs = prevTs;
              }
              existing.setAttribute('data-student', String(data.student||'').toLowerCase());
              existing.setAttribute('data-student-id', studentIdValue);
              existing.setAttribute('data-subject', String(data.subject||'').toLowerCase());
              if (!isNaN(dateObj.getTime())) {
                existing.setAttribute('data-date', `${dateObj.getFullYear()}-${String(dateObj.getMonth()+1).padStart(2,'0')}-${String(dateObj.getDate()).padStart(2,'0')}`);
                existing.setAttribute('data-date-ts', String(Math.floor(dateObj.getTime()/1000)));
              } else {
                if(prevDateIso !== null) existing.setAttribute('data-date', prevDateIso);
                if(prevDateTs !== null) existing.setAttribute('data-date-ts', prevDateTs);
              }
              if (createdTs !== null) {
                const d = new Date(createdTs*1000);
                const fmt = `${d.getFullYear()}-${String(d.getMonth()+1).padStart(2,'0')}-${String(d.getDate()).padStart(2,'0')} ${String(d.getHours()).padStart(2,'0')}:${String(d.getMinutes()).padStart(2,'0')}:${String(d.getSeconds()).padStart(2,'0')}`;
                existing.setAttribute('data-booked', fmt);
                existing.setAttribute('data-booked-ts', String(createdTs));
              }
              existing.setAttribute('data-type', String(data.type||'').toLowerCase());
              existing.setAttribute('data-mode', String(data.Mode||'').toLowerCase());
              existing.setAttribute('data-status', normalizedStatus);
              existing.setAttribute('data-matched','1');
              if(normalizedStatus === 'completion_pending'){
                existing.setAttribute('data-is-completion-pending','1');
              } else {
                existing.removeAttribute('data-is-completion-pending');
              }
              if(data.hasOwnProperty('completion_reason')){
                if(data.completion_reason){ existing.setAttribute('data-completion-reason', data.completion_reason); }
                else { existing.removeAttribute('data-completion-reason'); }
              }
              if(data.hasOwnProperty('completion_requested_at')){
                if(data.completion_requested_at){ existing.setAttribute('data-completion-requested', data.completion_requested_at); }
                else { existing.removeAttribute('data-completion-requested'); }
              }
              if(data.hasOwnProperty('completion_reviewed_at')){
                if(data.completion_reviewed_at){ existing.setAttribute('data-completion-reviewed', data.completion_reviewed_at); }
                else { existing.removeAttribute('data-completion-reviewed'); }
              }
              if(data.hasOwnProperty('completion_student_response')){
                if(data.completion_student_response){ existing.setAttribute('data-completion-response', data.completion_student_response); }
                else { existing.removeAttribute('data-completion-response'); }
              }
              if(data.hasOwnProperty('completion_student_comment')){
                if(data.completion_student_comment){ existing.setAttribute('data-completion-comment', data.completion_student_comment); }
                else { existing.removeAttribute('data-completion-comment'); }
              }
              // Update sticky first map
              __updateFirstMapForRow(existing);
            }catch(_){ }
            existing.classList.toggle('cancelled-booking', normalizedStatus === 'cancelled');
            if(normalizedStatus !== 'completed'){
              existing.removeAttribute('data-completed-lock');
            }
          } else {
            const row = document.createElement('div');
            row.className = 'table-row' + (normalizedStatus === 'cancelled' ? ' cancelled-booking' : '');
            row.innerHTML = html;
            // attach booking id to first cell for lookup next time
            const first = row.querySelector('.table-cell'); if(first){ first.setAttribute('data-booking-id', data.Booking_ID); }
            // initialize dataset attributes for filtering/sorting/first badge
            try{
              const dateObj = new Date(data.Booking_Date);
              const createdObj = new Date(data.Created_At);
              row.setAttribute('data-student', String(data.student||'').toLowerCase());
              row.setAttribute('data-student-id', studentIdValue);
              row.setAttribute('data-subject', String(data.subject||'').toLowerCase());
              row.setAttribute('data-date', isNaN(dateObj.getTime())?'':`${dateObj.getFullYear()}-${String(dateObj.getMonth()+1).padStart(2,'0')}-${String(dateObj.getDate()).padStart(2,'0')}`);
              row.setAttribute('data-date-ts', isNaN(dateObj.getTime())?'': String(Math.floor(dateObj.getTime()/1000)));
              if (!isNaN(createdObj.getTime())){
                row.setAttribute('data-booked', `${createdObj.getFullYear()}-${String(createdObj.getMonth()+1).padStart(2,'0')}-${String(createdObj.getDate()).padStart(2,'0')} ${String(createdObj.getHours()).padStart(2,'0')}:${String(createdObj.getMinutes()).padStart(2,'0')}:${String(createdObj.getSeconds()).padStart(2,'0')}`);
                row.setAttribute('data-booked-ts', String(Math.floor(createdObj.getTime()/1000)));
              }
              row.setAttribute('data-type', String(data.type||'').toLowerCase());
              row.setAttribute('data-mode', String(data.Mode||'').toLowerCase());
              row.setAttribute('data-status', normalizedStatus);
              row.setAttribute('data-matched','1');
              if(normalizedStatus === 'completion_pending'){
                row.setAttribute('data-is-completion-pending','1');
              }
              if(data.completion_reason){ row.setAttribute('data-completion-reason', data.completion_reason); }
              if(data.completion_requested_at){ row.setAttribute('data-completion-requested', data.completion_requested_at); }
              if(data.completion_reviewed_at){ row.setAttribute('data-completion-reviewed', data.completion_reviewed_at); }
              if(data.completion_student_response){ row.setAttribute('data-completion-response', data.completion_student_response); }
              if(data.completion_student_comment){ row.setAttribute('data-completion-comment', data.completion_student_comment); }
              // Update sticky first map
              __updateFirstMapForRow(row);
            }catch(_){ }
            table.appendChild(row);
          }

          // Re-apply filters to respect current UI state
          if(typeof filterRows==='function') filterRows();
        }

        channel.bind('BookingUpdated', function(data){
          // data.event may be 'BookingCreated' or 'BookingUpdated'
          renderRow(data);
        });
        // Fallback for environments where event name is the FQCN
        channel.bind('App\\Events\\BookingUpdated', function(data){
          renderRow(data);
        });
      } catch(e) { console.warn('Realtime init failed', e); }
    })();
  </script>
  <script src="{{ asset('js/ccit.js') }}"></script>
  <script>
    // Custom Modal JS (guaranteed global)
    function showProfessorModal(message) {
      document.getElementById('professorModalMessage').textContent = message;
      document.getElementById('professorModal').style.display = 'flex';
    }
    function closeProfessorModal() {
      document.getElementById('professorModal').style.display = 'none';
    }

    function showNotification(message, isError = false) {
      const banner = document.getElementById('notification');
      const label = document.getElementById('notification-message');
      if (!banner || !label) {
        showProfessorModal(String(message || ''));
        return;
      }
      banner.classList.toggle('error', !!isError);
      label.textContent = String(message || '');
      banner.style.display = 'flex';
      clearTimeout(showNotification._timer);
      showNotification._timer = setTimeout(hideNotification, 4000);
    }

    function hideNotification() {
      const banner = document.getElementById('notification');
      if (banner) {
        banner.style.display = 'none';
      }
    }

    function resolveTermMetaForPrint() {
      const ctx = window.profTermContext || {};
      const select = document.getElementById('termFilter');
      const rawSelection = select ? String(select.value || '') : '';
      const explicitUnassigned = rawSelection === 'unassigned';
      let normalized = rawSelection;

      if (!normalized || normalized === 'all') {
        normalized = ctx.activeTermId ? String(ctx.activeTermId) : '';
      } else if (explicitUnassigned) {
        normalized = '';
      }

      if (explicitUnassigned) {
        return null;
      }

      const terms = Array.isArray(ctx.terms) ? ctx.terms : [];
      const findById = (id) => terms.find((term) => String(term.id) === String(id));

      if (normalized) {
        const match = findById(normalized);
        if (match) {
          return match;
        }
      }

      if (ctx.activeTerm && ctx.activeTerm.id) {
        const activeMatch = findById(ctx.activeTerm.id);
        return activeMatch || ctx.activeTerm;
      }

      return null;
    }

    // PDF DOWNLOAD FEATURE
    let __pdfPreviewState = { blob: null, url: null, defaultName: '' };

    async function generateAndDownloadPdf(){
      try {
        const rows = Array.from(document.querySelectorAll('.table-row')).filter(r => !r.classList.contains('table-header'));
        const data = [];
        rows.forEach(r => {
          if (r.style.display === 'none') return; // respect active filters
          const cells = r.querySelectorAll('.table-cell');
          if(cells.length < 7) return;
          const studentCell = r.querySelector('.table-cell[data-label="Student"]');
          let studentName = '';
          let studentId = '';
          if(studentCell){
            const nameEl = studentCell.querySelector('.student-name[data-field="student-name"]');
            const idEl = studentCell.querySelector('.student-id[data-field="student-id"]');
            studentName = nameEl ? nameEl.textContent.trim() : '';
            studentId = idEl ? idEl.textContent.trim() : '';
          }
          let studentText = '';
          if(studentName || studentId){
            studentText = [studentName, studentId].filter(Boolean).join('\n');
          } else if(studentCell){
            const clone = studentCell.cloneNode(true);
            const badge = clone.querySelectorAll('.first-book-badge');
            badge.forEach(b=>b.remove());
            studentText = clone.textContent.trim();
          }
          if(!studentText){
            studentText = cells[1]?.innerText.trim() || '';
          }

          const remarksAttr = r.dataset ? (r.dataset.completionReason || '') : '';
          let remarks = remarksAttr ? decodeHtml(remarksAttr).trim() : '';
          if (!remarks) {
            const statusCell = cells[6];
            const title = statusCell ? statusCell.getAttribute('title') || '' : '';
            if (title && title.toLowerCase().startsWith('remarks:')) {
              remarks = title.slice(8).trim();
            }
          }
          data.push({
            no: cells[0]?.innerText.trim() || '',
            student: studentText,
            subject: cells[2]?.innerText.trim() || '',
            date: cells[3]?.innerText.trim() || '',
            type: cells[4]?.innerText.trim() || '',
            mode: cells[5]?.innerText.trim() || '',
            status: cells[6]?.innerText.trim() || '',
            remarks
          });
        });
    if (data.length === 0){ showProfessorModal('No consultation to print.'); return; }
        // sort by date then student
        data.sort((a,b)=> parseDate(a.date) - parseDate(b.date) || a.student.localeCompare(b.student));
        // Prepare payload for server
        const payload = data.map(d => ({
          student: d.student,
          subject: d.subject,
          date: d.date,
          type: d.type,
          mode: d.mode,
          status: d.status,
          remarks: d.remarks
        }));
        
        const termMeta = resolveTermMetaForPrint();
        const metaPayload = {
          semester: termMeta?.semester_label || termMeta?.name || termMeta?.label || '',
          sy_start: termMeta?.sy_start || termMeta?.syStart || '',
          sy_end: termMeta?.sy_end || termMeta?.syEnd || '',
          term: termMeta?.term_stage || termMeta?.termStage || '',
        };

        // Generate the PDF on the server
    const res = await fetch("{{ route('conlog-professor.pdf') }}", {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
          },
          body: JSON.stringify(Object.assign({ logs: payload }, metaPayload))
        });
        if(!res.ok) throw new Error('Failed to generate PDF');
        const blob = await res.blob();
        const defaultName = `consultation_logs_${new Date().toISOString().slice(0,10)}.pdf`;
        if (openPdfPreview(blob, defaultName)) {
          return;
        }
        await savePdfBlob(blob, defaultName, true);
      } catch(err){
        console.error('Export error', err); showProfessorModal('Failed to prepare data.');
      }
    }
    function parseDate(str){ const d = new Date(str); return isNaN(d)? Infinity : d; }
    function extractCreatedAt(){ return ''; }
    function escapeHtml(s){ return String(s).replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;','\'':'&#39;'}[c])); }
    function getPrintStyles(){ return `body{font-family:Poppins,Arial,sans-serif;margin:24px;}h2{margin:0 0 4px;color:#12372a;font-size:26px;} .print-professor{font-size:12px;color:#234b3b;margin-bottom:2px;font-weight:500;} .print-meta{font-size:12px;color:#555;margin-bottom:12px;}table{width:100%;border-collapse:collapse;font-size:12px;}th,td{border:1px solid #222;padding:6px 8px;text-align:left;}th{background:#12372a;color:#fff;font-weight:600;} .status-badge{padding:2px 6px;border-radius:4px;font-weight:600;font-size:11px;color:#fff;display:inline-block;} .status-badge.status-pending{background:#ffa600;} .status-badge.status-approved{background:#27ae60;} .status-badge.status-completed{background:#093b2f;} .status-badge.status-rescheduled{background:#c50000;} .print-footer-note{margin-top:22px;font-size:11px;color:#444;text-align:right;}@media print{body{margin:0;padding:0;} }`; }

    function openPdfPreview(blob, defaultName){
      const overlay = document.getElementById('pdfPreviewOverlay');
      const frame = document.getElementById('pdfPreviewFrame');
      const nameInput = document.getElementById('pdfFileName');
      if(!overlay || !frame || !nameInput){
        return false;
      }
      closePdfPreview();
      if(__pdfPreviewState.url){
        try{ window.URL.revokeObjectURL(__pdfPreviewState.url); }catch(_){ }
      }
    __pdfPreviewState.blob = blob;
    __pdfPreviewState.defaultName = defaultName;
  __pdfPreviewState.url = window.URL.createObjectURL(blob);
  const viewerParams = window.matchMedia('(max-width: 768px)').matches ? 'view=FitH' : 'zoom=page-fit';
  frame.src = `${__pdfPreviewState.url}#toolbar=0&navpanes=0&${viewerParams}`;
      const baseName = (defaultName || '').replace(/\.pdf$/i, '');
      nameInput.value = baseName;
      overlay.classList.add('open');
      overlay.setAttribute('aria-hidden', 'false');
      document.body.style.overflow = 'hidden';
      document.body.classList.add('preview-open');
      return true;
    }

    function closePdfPreview(){
      const overlay = document.getElementById('pdfPreviewOverlay');
      if(!overlay) return;
      if(!overlay.classList.contains('open')) return;
      overlay.classList.remove('open');
      overlay.setAttribute('aria-hidden', 'true');
      document.body.style.overflow = '';
  document.body.classList.remove('preview-open');
      const frame = document.getElementById('pdfPreviewFrame');
      if(frame) frame.src = 'about:blank';
      if(__pdfPreviewState.url){
        try{ window.URL.revokeObjectURL(__pdfPreviewState.url); }catch(_){ }
      }
      __pdfPreviewState = { blob: null, url: null, defaultName: '' };
    }

    function buildPdfFileName(raw, fallback){
      const fallbackBase = (fallback || '').replace(/\.pdf$/i, '') || 'consultation_logs';
      const cleaned = String(raw || '')
        .replace(/[\\/:*?"<>|]/g, ' ')
        .replace(/\s+/g, ' ')
        .trim();
      const base = cleaned || fallbackBase;
      if(!base) return '';
      return /\.pdf$/i.test(base) ? base : `${base}.pdf`;
    }

    async function savePdfBlob(blob, filename, allowFallback){
      const safeName = buildPdfFileName(filename, 'consultation_logs');
      if(!safeName){
        return false;
      }
      if(!allowFallback){
        return false;
      }
      const url = window.URL.createObjectURL(blob);
      const a = document.createElement('a');
      a.href = url;
      a.download = safeName;
      document.body.appendChild(a);
      a.click();
      a.remove();
      setTimeout(()=>window.URL.revokeObjectURL(url), 1500);
      return true;
    }

    async function downloadPdfFromPreview(){
      const overlay = document.getElementById('pdfPreviewOverlay');
      if(!overlay || !overlay.classList.contains('open')) return;
      if(!__pdfPreviewState.blob){
        showProfessorModal('Preview not ready yet.');
        return;
      }
      const input = document.getElementById('pdfFileName');
      const desired = input ? input.value : '';
      const filename = buildPdfFileName(desired, __pdfPreviewState.defaultName);
      if(!filename){
        showProfessorModal('Please enter a file name.');
        input && input.focus();
        return;
      }
      try{
        const saved = await savePdfBlob(__pdfPreviewState.blob, filename, true);
        if(saved){
          closePdfPreview();
        }
      }catch(err){
        console.error('Download error', err);
        showProfessorModal('Unable to download the file.');
      }
    }

    (function(){
      const closeBtn = document.getElementById('closePreviewBtn');
      const downloadBtn = document.getElementById('downloadPdfBtn');
      const overlay = document.getElementById('pdfPreviewOverlay');
      closeBtn?.addEventListener('click', closePdfPreview);
      downloadBtn?.addEventListener('click', downloadPdfFromPreview);
      overlay?.addEventListener('click', (e)=>{
        if(e.target === overlay){
          closePdfPreview();
        }
      });
      document.addEventListener('keydown', (e)=>{
        if(e.key === 'Escape'){
          closePdfPreview();
        }
      });
    })();
  </script>
  <script src="{{ asset('js/timeago.js') }}"></script>
</body>
</html>