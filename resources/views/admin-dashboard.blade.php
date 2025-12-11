<!-- filepath: c:\Users\Admin\ASCC-ITv1-studentV1\ASCC-ITv1-student\resources\views\dashboard-admin.blade.php -->
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>Admin Dashboard</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
  <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
  <link rel="stylesheet" href="{{ asset('css/admin-navbar.css') }}">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/pikaday/css/pikaday.css">
  <link rel="stylesheet" href="{{ asset('css/dashboard-admin.css') }}">
  <link rel="stylesheet" href="{{ asset('css/notifications.css') }}">
  <link rel="stylesheet" href="{{ asset('css/legend.css') }}">
  <link rel="stylesheet" href="{{ asset('css/logout-confirm.css') }}">
  <script src="{{ asset('js/logout-confirm.js') }}" defer></script>
</head>
<body>
  @include('components.navbar-admin')

  <div class="main-content">
    @php
        $termCollection = ($termOptions ?? collect())->sortByDesc(function ($term) {
            return optional($term->start_at)->format('Y-m-d');
        });
        $activeTermId = optional($activeTerm)->id;
        $termDropdown = $termCollection->map(function ($term) {
            $yearLabel = optional($term->academicYear)->label;
            $label = trim(($term->name ?? 'Term') . ($yearLabel ? ' ' . $yearLabel : ''));

            return [
                'id' => $term->id,
                'label' => $label,
                'status' => $term->status,
                'start_at' => optional($term->start_at)->format('Y-m-d'),
                'end_at' => optional($term->end_at)->format('Y-m-d'),
                'name' => $term->name,
                'year_label' => $yearLabel,
                'enrollment_deadline' => optional($term->enrollment_deadline)->format('Y-m-d'),
                'grade_submission_deadline' => optional($term->grade_submission_deadline)->format('Y-m-d'),
            ];
        });
    @endphp
    <div class="header">
      <div class="header-bar">
        <h1>Consultation Activity</h1>
        <div class="term-controls">
          <select id="adminTermDropdown" class="term-select" aria-label="Switch academic term">
            <option value="" data-status="all">All Terms</option>
            @foreach($termDropdown as $option)
              <option value="{{ $option['id'] }}" data-status="{{ $option['status'] }}" @if($activeTermId === $option['id']) selected @endif>
                {{ $option['label'] }}
              </option>
            @endforeach
          </select>
          <button type="button" id="manageAcademicYearBtn" class="manage-term-btn">
            <i class='bx bx-cog'></i>
            Manage Academic Year
          </button>
        </div>
      </div>
    </div>
    <div id="termManageModal" class="term-modal-backdrop" aria-hidden="true">
      <div class="term-modal" role="dialog" aria-modal="true" aria-labelledby="termModalTitle">
        <header>
          <h2 id="termModalTitle">Create Academic Term</h2>
          <div class="term-modal-actions">
            <button type="button" id="termModalEditTrigger" class="term-edit-trigger">Edit existing</button>
            <button type="button" id="termModalClose" class="term-close-btn" aria-label="Close">×</button>
          </div>
        </header>
        <div class="term-modal-body">
          <div class="term-progress">
            <span data-step="1" class="active"></span>
            <span data-step="2"></span>
          </div>
          <div class="term-step active" data-step="1">
            <p>Select the semester you want to configure.</p>
            <div class="term-choice-grid" role="listbox" aria-label="Semester choices">
              <div class="term-choice" role="option" tabindex="0" data-value="1st Semester">
                <h4>First Semester</h4>
                <span>SY XXXX-XXXX</span>
              </div>
              <div class="term-choice" role="option" tabindex="0" data-value="2nd Semester">
                <h4>Second Semester</h4>
                <span>SY XXXX-XXXX</span>
              </div>
              <div class="term-choice" role="option" tabindex="0" data-value="Midyear Term">
                <h4>Midyear Term</h4>
                <span>Optional short term</span>
              </div>
            </div>
          </div>
          <div class="term-step" data-step="2">
            <p>Set the academic year label and key dates.</p>
            <div class="term-form-field">
              <label for="termYearInput">Academic Year Label</label>
              <input type="text" id="termYearInput" placeholder="SY 2025-2026" maxlength="50">
            </div>
            <div class="term-form-field">
              <label for="termStartInput">Term starts</label>
              <input type="date" id="termStartInput">
            </div>
            <div class="term-form-field">
              <label for="termEndInput">Term ends</label>
              <input type="date" id="termEndInput">
            </div>
            <div class="term-error" id="termModalError" role="alert"></div>
          </div>
        </div>
        <footer>
          <button type="button" class="btn-secondary" id="termModalBack" disabled>Back</button>
          <button type="button" class="btn-primary" id="termModalNext">Next</button>
        </footer>
      </div>
    </div>

    <div id="termEditModal" class="term-modal-backdrop" aria-hidden="true">
      <div class="term-modal" role="dialog" aria-modal="true" aria-labelledby="termEditTitle">
        <header>
          <h2 id="termEditTitle">Edit Academic Term</h2>
          <button type="button" id="termEditClose" class="term-close-btn" aria-label="Close">×</button>
        </header>
        <div class="term-modal-body">
          <div class="term-form-field">
            <label for="termEditSelect">Select term to update</label>
            <select id="termEditSelect"></select>
          </div>
          <div class="term-form-field">
            <label for="termEditYear">Academic Year Label</label>
            <input type="text" id="termEditYear" maxlength="50" placeholder="SY 2025-2026">
          </div>
          <div class="term-form-field">
            <label for="termEditStart">Term starts</label>
            <input type="date" id="termEditStart">
          </div>
          <div class="term-form-field">
            <label for="termEditEnd">Term ends</label>
            <input type="date" id="termEditEnd">
          </div>
          <div class="term-error" id="termEditError" role="alert"></div>
        </div>
        <footer>
          <button type="button" class="btn-secondary" id="termEditCancel">Cancel</button>
          <button type="button" class="btn-primary" id="termEditSave">Save Changes</button>
        </footer>
      </div>
    </div>

    <div id="termSwitchConfirm" class="term-modal-backdrop" aria-hidden="true">
      <div class="term-modal" role="dialog" aria-modal="true" aria-labelledby="termConfirmTitle">
        <header>
          <h2 id="termConfirmTitle">Activate Semester</h2>
          <button type="button" id="termConfirmClose" class="term-close-btn" aria-label="Close">×</button>
        </header>
        <div class="term-modal-body">
          <p id="termConfirmMessage">Are you sure you want to activate this term?</p>
          <p class="term-confirm-note">Activating a term will close the current one, archive consultation records, and reset schedules for the new semester.</p>
        </div>
        <footer>
          <button type="button" class="btn-secondary" id="termConfirmCancel">Cancel</button>
          <button type="button" class="btn-primary" id="termConfirmProceed">Activate Term</button>
        </footer>
      </div>
    </div>

    <div class="flex-layout">
      <div class="calendar-box">
        <div class="calendar-wrapper-container">
          <input id="calendar" type="text" placeholder="Select Date" name="booking_date" required>
        </div>
        <!-- Collapsible legend: toggle button + panel -->
        <button id="legendToggle" class="legend-toggle" aria-haspopup="dialog" aria-controls="legendBackdrop" aria-label="View Legend" title="View Legend">
          <svg width="22" height="22" viewBox="0 0 24 24" aria-hidden="true" focusable="false">
            <path fill="currentColor" d="M12 2a10 10 0 1 0 0 20a10 10 0 0 0 0-20zm0 7a1.25 1.25 0 1 1 0-2.5a1.25 1.25 0 0 1 0 2.5zM11 11h2v6h-2z"/>
          </svg>
        </button>
        <div id="legendBackdrop" class="legend-backdrop" aria-hidden="true">
          <div class="legend-panel" role="dialog" aria-modal="true" aria-labelledby="legendTitle">
            <div class="legend-header">
              <h3 id="legendTitle">Legend</h3>
              <button id="legendClose" class="legend-close" aria-label="Close">×</button>
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
                  <div class="legend-item"><span class="legend-swatch swatch-endyear"></span>End of School Year <i class='bx bx-calendar-x legend-icon' aria-hidden="true"></i></div>
                  <div class="legend-item"><span class="legend-swatch swatch-multiple"></span>Multiple Bookings <i class='bx bx-group legend-icon' aria-hidden="true"></i></div>
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
                ✓
              </button>
              <span id="unread-count" class="unread-count">0</span>
            </div>
          </div>
          <div class="inbox-content" id="notifications-container">
            <div class="loading-notifications">
              <i class='bx bx-loader-alt bx-spin'></i>
              <p>Loading notifications...</p>
            </div>
          </div>
        </div>
      </div>
    </div>

    {{-- <button class="chat-button" onclick="toggleChat()">
      <i class='bx bxs-message-rounded-dots'></i>
      Click to chat with me!
    </button>

    <div class="chat-overlay" id="chatOverlay">
      <div class="chat-header">
        <span>AI Chat Assistant</span>
        <button class="close-btn" onclick="toggleChat()">×</button>
      </div>
      <div class="chat-body" id="chatBody">
        <div class="message bot">Hi! How can I help you today?</div>
        <div id="chatBox"></div>
      </div>

      <form id="chatForm">
        <input type="text" id="message" placeholder="Type your message" required>
        <button type="submit">Send</button>
      </form>
    </div>
  </div> --}}

  <!-- Consultation Tooltip (Admin version shows ALL consultations) -->
  <div id="consultationTooltip"></div>

  <!-- Consultation Details Modal -->
  <div id="consultationModal" class="modal hidden">
    <div class="modal-content">
      <div class="modal-header">
        <h3>Consultation Details</h3>
        <span class="modal-close" onclick="closeConsultationModal()">&times;</span>
      </div>
      <div class="modal-body" id="modalConsultationDetails">
        <div class="loading">Loading consultation details...</div>
      </div>
    </div>
  </div>

  <script src="{{ asset('js/dashboard.js') }}"></script>
  <script src="https://cdn.jsdelivr.net/npm/pikaday/pikaday.js"></script>
  <link rel="stylesheet" href="{{ asset('css/toast.css') }}">
  <link rel="stylesheet" href="{{ asset('css/confirm.css') }}">
  <script>
    window.__termContext = {
      activeTermId: @json($activeTermId),
      terms: @json($termDropdown),
    };
  </script>
  <script>
    // Wait for DOM to be fully loaded
    document.addEventListener('DOMContentLoaded', function() {
      // Load initial mobile notifications
      loadMobileNotifications();
      initTermManagement();
    });

    function initTermManagement() {
      const ctx = window.__termContext || {};
      const normalizeTerm = (term) => ({
        id: Number(term.id),
        label: term.label,
        status: term.status,
        start_at: term.start_at,
        end_at: term.end_at,
        year_label: term.year_label || null,
        name: term.name || null,
        enrollment_deadline: term.enrollment_deadline || null,
        grade_submission_deadline: term.grade_submission_deadline || null,
      });

      const state = {
        step: 1,
        selectedTermName: null,
        terms: Array.isArray(ctx.terms) ? ctx.terms.map(normalizeTerm) : [],
        activeTermId: ctx.activeTermId ? Number(ctx.activeTermId) : null,
        pendingTermId: null,
        previousDropdownValue: ctx.activeTermId ? String(ctx.activeTermId) : '',
        editingTermId: null,
      };

      const dropdown = document.getElementById('adminTermDropdown');
      const manageBtn = document.getElementById('manageAcademicYearBtn');
      const modal = document.getElementById('termManageModal');
      const confirmModal = document.getElementById('termSwitchConfirm');
      const editModal = document.getElementById('termEditModal');
      if (!dropdown || !manageBtn || !modal || !confirmModal) {
        return;
      }

      const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
      const showToast = window.showToast
        ? window.showToast
        : (message, type = 'info') => {
            if (type === 'error') {
              console.error(message);
            } else {
              console.log(message);
            }
          };

      const modalSteps = Array.from(modal.querySelectorAll('.term-step'));
      const modalProgress = Array.from(modal.querySelectorAll('.term-progress span'));
      const modalChoices = Array.from(modal.querySelectorAll('.term-choice'));
      const modalNext = document.getElementById('termModalNext');
      const modalBack = document.getElementById('termModalBack');
      const modalClose = document.getElementById('termModalClose');
      const modalEditTrigger = document.getElementById('termModalEditTrigger');
      const yearInput = document.getElementById('termYearInput');
      const startInput = document.getElementById('termStartInput');
      const endInput = document.getElementById('termEndInput');
      const modalError = document.getElementById('termModalError');

      const confirmMessage = document.getElementById('termConfirmMessage');
      const confirmProceed = document.getElementById('termConfirmProceed');
      const confirmCancel = document.getElementById('termConfirmCancel');
      const confirmClose = document.getElementById('termConfirmClose');

      const editSelect = document.getElementById('termEditSelect');
      const editYearInput = document.getElementById('termEditYear');
      const editStartInput = document.getElementById('termEditStart');
      const editEndInput = document.getElementById('termEditEnd');
      const editError = document.getElementById('termEditError');
      const editSave = document.getElementById('termEditSave');
      const editCancel = document.getElementById('termEditCancel');
      const editClose = document.getElementById('termEditClose');

      const closeModal = (focusTrigger = true) => {
        modal.classList.remove('open');
        modal.setAttribute('aria-hidden', 'true');
        if (focusTrigger) {
          manageBtn.focus({ preventScroll: true });
        }
      };

      const openModal = () => {
        state.step = 1;
        state.selectedTermName = null;
        modal.classList.add('open');
        modal.setAttribute('aria-hidden', 'false');
        if (modalError) {
          modalError.classList.remove('is-visible');
          modalError.textContent = '';
        }
        [yearInput, startInput, endInput].forEach((input) => {
          input.value = '';
        });
        modalChoices.forEach((choice) => choice.classList.remove('active'));
        modalNext.textContent = 'Next';
        modalBack.disabled = true;
        updateStepDisplay();
      };

      const clearEditForm = () => {
        [editYearInput, editStartInput, editEndInput].forEach((input) => {
          if (input) {
            input.value = '';
          }
        });
        if (editError) {
          editError.classList.remove('is-visible');
          editError.textContent = '';
        }
      };

      const closeEditModal = (focusManage = true) => {
        if (!editModal) {
          return;
        }
        editModal.classList.remove('open');
        editModal.setAttribute('aria-hidden', 'true');
        state.editingTermId = null;
        clearEditForm();
        if (focusManage) {
          manageBtn.focus({ preventScroll: true });
        }
      };

      const applyEditSelection = (termId) => {
        if (!editSelect) {
          return;
        }
        const id = Number(termId);
        if (!Number.isFinite(id)) {
          state.editingTermId = null;
          clearEditForm();
          return;
        }
        const term = state.terms.find((item) => item.id === id);
        if (!term) {
          state.editingTermId = null;
          clearEditForm();
          return;
        }
        state.editingTermId = id;
        if (editYearInput) editYearInput.value = term.year_label || '';
        if (editStartInput) editStartInput.value = term.start_at || '';
        if (editEndInput) editEndInput.value = term.end_at || '';
        if (editError) {
          editError.classList.remove('is-visible');
          editError.textContent = '';
        }
      };

      const populateEditOptions = () => {
        if (!editSelect) {
          return;
        }
        editSelect.innerHTML = '';
        const placeholder = document.createElement('option');
        placeholder.value = '';
        placeholder.textContent = state.terms.length ? 'Select a term' : 'No terms available yet';
        editSelect.appendChild(placeholder);

        if (!state.terms.length) {
          clearEditForm();
          if (editSave) editSave.disabled = true;
          if (editError) {
            editError.textContent = 'No existing terms to edit.';
            editError.classList.add('is-visible');
          }
          return;
        }

        const sorted = [...state.terms].sort((a, b) => {
          const left = a.start_at || '';
          const right = b.start_at || '';
          return right.localeCompare(left);
        });

        sorted.forEach((term) => {
          const opt = document.createElement('option');
          opt.value = String(term.id);
          opt.textContent = term.label;
          editSelect.appendChild(opt);
        });

        const fallbackId = sorted[0]?.id;
        const chosenId = state.editingTermId || state.activeTermId || fallbackId;
        if (chosenId) {
          editSelect.value = String(chosenId);
          applyEditSelection(chosenId);
        } else {
          clearEditForm();
        }
        if (editSave) editSave.disabled = false;
      };

      const openEditModal = async () => {
        if (!editModal) {
          return;
        }
        editModal.classList.add('open');
        editModal.setAttribute('aria-hidden', 'false');
        if (editSave) editSave.disabled = false;
        if (editError) {
          editError.classList.remove('is-visible');
          editError.textContent = '';
        }
        // Ensure we have the latest term list before populating options
        try {
          await refreshTerms();
        } catch (_) {
          // refreshTerms already reports errors via toast; continue with existing state
        }
        populateEditOptions();
        if (editSelect) {
          editSelect.focus({ preventScroll: true });
        }
      };

      const submitTermEdit = async () => {
        if (!state.editingTermId) {
          if (editError) {
            editError.textContent = 'Select a term before saving changes.';
            editError.classList.add('is-visible');
          }
          return;
        }

        const start = editStartInput?.value || '';
        const end = editEndInput?.value || '';
        if (!start || !end) {
          if (editError) {
            editError.textContent = 'Start and end dates are required.';
            editError.classList.add('is-visible');
          }
          return;
        }
        if (end < start) {
          if (editError) {
            editError.textContent = 'End date must be after the start date.';
            editError.classList.add('is-visible');
          }
          return;
        }

        if (editError) {
          editError.classList.remove('is-visible');
          editError.textContent = '';
        }

        if (editSave) editSave.disabled = true;
        try {
          const payload = {
            start_at: start,
            end_at: end,
          };
          const label = editYearInput?.value ? editYearInput.value.trim() : '';
          if (label) {
            payload.year_label = label;
          }

          const response = await fetch(`/admin/terms/${state.editingTermId}`, {
            method: 'PUT',
            headers: {
              'Content-Type': 'application/json',
              Accept: 'application/json',
              'X-CSRF-TOKEN': csrfToken,
            },
            credentials: 'same-origin',
            body: JSON.stringify(payload),
          });

          if (!response.ok) {
            const errorData = await response.json().catch(() => ({}));
            const extracted = errorData?.errors
              ? Object.values(errorData.errors)[0]?.[0]
              : null;
            const message = errorData?.message || extracted || 'Unable to update term.';
            throw new Error(message);
          }

          const updated = await response.json();
          state.editingTermId = updated.id ? Number(updated.id) : state.editingTermId;
          closeEditModal();
          showToast('Term updated successfully.', 'success');
          await refreshTerms();
        } catch (error) {
          if (editError) {
            editError.textContent = error.message;
            editError.classList.add('is-visible');
          }
        } finally {
          if (editSave) editSave.disabled = false;
        }
      };

      const updateStepDisplay = () => {
        modalSteps.forEach((step) => {
          step.classList.toggle('active', Number(step.dataset.step) === state.step);
        });
        modalProgress.forEach((bar) => {
          bar.classList.toggle('active', Number(bar.dataset.step) <= state.step);
        });
      };

      const closeConfirm = (persistSelection = false) => {
        confirmModal.classList.remove('open');
        confirmModal.setAttribute('aria-hidden', 'true');
        if (!persistSelection) {
          dropdown.value = state.activeTermId ? String(state.activeTermId) : '';
        }
      };

      const openConfirm = (term) => {
        confirmModal.classList.add('open');
        confirmModal.setAttribute('aria-hidden', 'false');
        confirmMessage.textContent = `Activate ${term.label}?`;
        confirmProceed.disabled = false;
      };

      const updateDropdown = () => {
        dropdown.innerHTML = '';
        const allOption = document.createElement('option');
        allOption.value = '';
        allOption.dataset.status = 'all';
        allOption.textContent = 'All Terms';
        dropdown.appendChild(allOption);

        state.terms.forEach((term) => {
          const opt = document.createElement('option');
          opt.value = String(term.id);
          opt.dataset.status = term.status;
          opt.textContent = term.label;
          if (state.activeTermId && term.id === state.activeTermId) {
            opt.selected = true;
          }
          dropdown.appendChild(opt);
        });

        if (!state.activeTermId) {
          dropdown.value = '';
        }
      };

      const refreshTerms = async () => {
        try {
          const response = await fetch('/admin/academic-terms', {
            headers: { Accept: 'application/json' },
            credentials: 'same-origin',
          });
          if (!response.ok) {
            throw new Error('Failed to refresh terms.');
          }
          const data = await response.json();
          const flattened = [];
          if (Array.isArray(data.years)) {
            data.years.forEach((year) => {
              (year.terms || []).forEach((term) => {
                flattened.push({
                  id: Number(term.id),
                  label: `${term.name ?? ''} ${year.label || ''}`.trim() || term.name,
                  status: term.status,
                  start_at: term.start_at,
                  end_at: term.end_at,
                  name: term.name,
                  year_label: year.label || null,
                  enrollment_deadline: term.enrollment_deadline,
                  grade_submission_deadline: term.grade_submission_deadline,
                });
              });
            });
          }
          state.terms = flattened.map(normalizeTerm);
          if (data.active_term?.id) {
            state.activeTermId = Number(data.active_term.id);
          }
          updateDropdown();
          if (editModal && editModal.classList.contains('open')) {
            populateEditOptions();
          }
        } catch (error) {
          showToast(error.message, 'error');
        }
      };

      const sequenceForName = (name) => {
        if (!name) return null;
        const lower = name.toLowerCase();
        if (lower.includes('1st')) return 1;
        if (lower.includes('first')) return 1;
        if (lower.includes('2nd')) return 2;
        if (lower.includes('second')) return 2;
        if (lower.includes('mid')) return 3;
        return null;
      };

      const submitTerm = async () => {
        if (modalError) {
          modalError.classList.remove('is-visible');
          modalError.textContent = '';
        }
        const label = yearInput.value.trim();
        const start = startInput.value;
        const end = endInput.value;

        if (!state.selectedTermName) {
          if (modalError) {
            modalError.textContent = 'Choose which semester to configure.';
            modalError.classList.add('is-visible');
          }
          state.step = 1;
          updateStepDisplay();
          return;
        }
        if (!label || !start || !end) {
          if (modalError) {
            modalError.textContent = 'Academic year, start date, and end date are required.';
            modalError.classList.add('is-visible');
          }
          return;
        }
        if (end < start) {
          if (modalError) {
            modalError.textContent = 'End date must be after start date.';
            modalError.classList.add('is-visible');
          }
          return;
        }

        modalNext.disabled = true;
        try {
          const payload = {
            year: {
              label,
              start_at: start,
              end_at: end,
            },
            term: {
              name: state.selectedTermName,
              sequence: sequenceForName(state.selectedTermName),
              start_at: start,
              end_at: end,
            },
          };

          const response = await fetch('/admin/academic-years', {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json',
              Accept: 'application/json',
              'X-CSRF-TOKEN': csrfToken,
            },
            credentials: 'same-origin',
            body: JSON.stringify(payload),
          });

          if (!response.ok) {
            const errorData = await response.json().catch(() => ({}));
            const message = errorData?.message || 'Unable to create term.';
            throw new Error(message);
          }

          showToast('New term saved. Activate it whenever you are ready.', 'success');
          closeModal();
          await refreshTerms();
        } catch (error) {
          if (modalError) {
            modalError.textContent = error.message;
            modalError.classList.add('is-visible');
          }
        } finally {
          modalNext.disabled = false;
          modalNext.textContent = 'Save Term';
          modalBack.disabled = false;
        }
      };

      manageBtn.addEventListener('click', openModal);
      modalClose.addEventListener('click', closeModal);
      modalEditTrigger?.addEventListener('click', () => {
        closeModal(false);
        openEditModal();
      });
      modalBack.addEventListener('click', () => {
        if (state.step === 1) {
          closeModal();
        } else {
          state.step = 1;
          modalBack.disabled = true;
          modalNext.textContent = 'Next';
          updateStepDisplay();
        }
      });

      modalNext.addEventListener('click', () => {
        if (state.step === 1) {
          if (!state.selectedTermName) {
            if (modalError) {
              modalError.textContent = 'Select a semester first.';
              modalError.classList.add('is-visible');
            }
            return;
          }
          if (modalError) {
            modalError.classList.remove('is-visible');
            modalError.textContent = '';
          }
          state.step = 2;
          modalBack.disabled = false;
          modalNext.textContent = 'Save Term';
          updateStepDisplay();
          return;
        }

        submitTerm();
      });

      modalChoices.forEach((choice) => {
        choice.addEventListener('click', () => {
          modalChoices.forEach((item) => item.classList.remove('active'));
          choice.classList.add('active');
          state.selectedTermName = choice.dataset.value;
          if (modalError) {
            modalError.classList.remove('is-visible');
            modalError.textContent = '';
          }
        });
        choice.addEventListener('keydown', (event) => {
          if (event.key === 'Enter' || event.key === ' ') {
            event.preventDefault();
            choice.click();
          }
        });
      });

      editClose?.addEventListener('click', () => closeEditModal());
      editCancel?.addEventListener('click', () => closeEditModal());
      editSelect?.addEventListener('change', (event) => {
        applyEditSelection(event.target.value);
      });
      editSave?.addEventListener('click', submitTermEdit);

      confirmCancel.addEventListener('click', () => closeConfirm(false));
      confirmClose.addEventListener('click', () => closeConfirm(false));

      confirmProceed.addEventListener('click', async () => {
        if (!state.pendingTermId) {
          closeConfirm(false);
          return;
        }
        confirmProceed.disabled = true;
        try {
          const response = await fetch(`/admin/terms/${state.pendingTermId}/activate`, {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json',
              Accept: 'application/json',
              'X-CSRF-TOKEN': csrfToken,
            },
            credentials: 'same-origin',
            body: JSON.stringify({ force: false }),
          });

          if (!response.ok) {
            const errorData = await response.json().catch(() => ({}));
            const message = errorData?.message || 'Activation failed.';
            throw new Error(message);
          }

          showToast('Term activated successfully.', 'success');
          state.activeTermId = state.pendingTermId;
          closeConfirm(true);
          await refreshTerms();
        } catch (error) {
          showToast(error.message, 'error');
          closeConfirm(false);
        } finally {
          confirmProceed.disabled = false;
          state.pendingTermId = null;
        }
      });

      dropdown.addEventListener('change', (event) => {
        const selectedValue = event.target.value;
        if (!selectedValue) {
          event.target.value = state.activeTermId ? String(state.activeTermId) : '';
          return;
        }

        const nextId = Number(selectedValue);
        if (Number.isNaN(nextId)) {
          event.target.value = state.activeTermId ? String(state.activeTermId) : '';
          return;
        }

        if (state.activeTermId && nextId === state.activeTermId) {
          return;
        }

        const targetTerm = state.terms.find((term) => term.id === nextId);
        if (!targetTerm) {
          event.target.value = state.activeTermId ? String(state.activeTermId) : '';
          return;
        }

        state.pendingTermId = nextId;
        state.previousDropdownValue = state.activeTermId ? String(state.activeTermId) : '';
        openConfirm(targetTerm);
      });

      updateDropdown();
    }

    // Mobile Notification Functions  
    function toggleMobileNotifications() {
      const dropdown = document.getElementById('mobileNotificationDropdown');
      if (dropdown && dropdown.classList) {
        dropdown.classList.toggle('active');
        
        if (dropdown.classList.contains('active')) {
          loadMobileNotifications();
        }
      } else {
        console.log('Mobile notification dropdown not found or classList not available');
      }
    }

    function loadMobileNotifications() {
      fetch('/api/professor/notifications')
        .then(response => response.json())
        .then(data => {
          displayMobileNotifications(data.notifications);
          updateMobileNotificationBadge(data.unread_count);
        })
        .catch(error => {
          console.error('Error loading mobile notifications:', error);
        });
    }

    function displayMobileNotifications(notifications) {
      const container = document.getElementById('mobileNotificationsContainer');
      if (!container) return;

      if (notifications.length === 0) {
        container.innerHTML = '<div class="mobile-notification-item">No notifications</div>';
        return;
      }

      container.innerHTML = notifications.map(notification => `
        <div class="mobile-notification-item ${notification.is_read ? 'read' : 'unread'}" 
             onclick="markMobileNotificationAsRead(${notification.id})">
          <div class="mobile-notification-content">
            <div class="mobile-notification-title">${notification.title}</div>
            <div class="mobile-notification-message">${notification.message}</div>
            <div class="mobile-notification-time">${formatMobileTime(notification.created_at)}</div>
          </div>
          ${!notification.is_read ? '<div class="mobile-notification-dot"></div>' : ''}
        </div>
      `).join('');
    }

    function updateMobileNotificationBadge(count) {
      const badge = document.getElementById('mobileNotificationBadge');
      if (badge) {
        badge.textContent = count;
        badge.style.display = count > 0 ? 'flex' : 'none';
      }
    }

    function markMobileNotificationAsRead(notificationId) {
      fetch('/api/admin/notifications/mark-read', {
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
          loadMobileNotifications();
        }
      })
      .catch(error => {
        console.error('Error marking mobile notification as read:', error);
      });
    }

    function markAllNotificationsAsRead() {
      fetch('/api/admin/notifications/mark-all-read', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        }
      })
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          loadMobileNotifications();
          updateMobileNotificationBadge(0);
        }
      })
      .catch(error => {
        console.error('Error marking all notifications as read:', error);
      });
    }

    function formatMobileTime(dateString) {
      const date = new Date(dateString);
      const now = new Date();
      const diffInSeconds = Math.floor((now - date) / 1000);
      if (diffInSeconds < 60) return 'Just now';
      if (diffInSeconds < 3600) {
        const m = Math.floor(diffInSeconds / 60);
        return `${m} ${m === 1 ? 'min' : 'mins'} ago`;
      }
      if (diffInSeconds < 86400) {
        const h = Math.floor(diffInSeconds / 3600);
        return `${h === 1 ? '1 hr' : h + ' hrs'} ago`;
      }
      const d = Math.floor(diffInSeconds / 86400);
      return `${d} ${d === 1 ? 'day' : 'days'} ago`;
    }

    // Close mobile notifications when clicking outside
    document.addEventListener('click', function(event) {
      const dropdown = document.getElementById('mobileNotificationDropdown');
      const bell = document.querySelector('.mobile-notification-bell');
      
      if (dropdown && dropdown.classList && bell && !dropdown.contains(event.target) && !bell.contains(event.target)) {
        dropdown.classList.remove('active');
      }
    });
    
    const bookingMap = new Map();
    const detailsMap = new Map();
   
    // Initialize Pikaday immediately so the calendar renders without waiting for network calls
    (function initAdminCalendar() {
      const picker = new Pikaday({
        field: document.getElementById('calendar'),
        format: 'ddd, MMM DD YYYY',
        showDaysInNextAndPreviousMonths: true,
        firstDay: 1,
        bound: false,
        onDraw: function() {
          // Determine visible month key once per draw
          const baseForDraw = (function(){
            try { return getVisibleMonthBaseDate(); } catch(_) { const t=new Date(); return new Date(t.getFullYear(), t.getMonth(), 1); }
          })();
          const monthKeyForDraw = `${baseForDraw.getFullYear()}-${String(baseForDraw.getMonth()+1).padStart(2,'0')}`;
          const cells = document.querySelectorAll('.pika-button');
          cells.forEach(cell => {
            const day = cell.getAttribute('data-pika-day');
            const month = cell.getAttribute('data-pika-month');
            const year = cell.getAttribute('data-pika-year');
            if (day && month && year) {
              const cellDate = new Date(year, month, day);
              const key = cellDate.toDateString();
              const isoKey = `${cellDate.getFullYear()}-${String(cellDate.getMonth()+1).padStart(2,'0')}-${String(cellDate.getDate()).padStart(2,'0')}`;

              // Use overrides source with per-month sticky fallback so badges don't flicker
              const ovSource = (function(){
                if (window.adminOverrides && typeof window.adminOverrides === 'object') return window.adminOverrides;
                if (window.__adminOvCacheByMonth && window.__adminOvCacheByMonth[monthKeyForDraw]) return window.__adminOvCacheByMonth[monthKeyForDraw];
                // legacy single cache fallback
                if (window.__adminOvCache && typeof window.__adminOvCache === 'object') return window.__adminOvCache;
                // global cache fallback for instant paint across months
                if (window.__adminOvGlobalCache && typeof window.__adminOvGlobalCache === 'object') return window.__adminOvGlobalCache;
                return null;
              })();
              const haveOv = !!ovSource;

              // Only clear previous override visuals if we have some source to repaint from
              if (haveOv) {
                const oldBadge = cell.querySelector('.ov-badge');
                if (oldBadge) oldBadge.remove();
                cell.classList.remove('day-holiday','day-blocked','day-force','day-online','day-endyear');
              }

              // Render overrides badge if present (pulling from chosen source)
              if (haveOv && ovSource[isoKey] && ovSource[isoKey].length > 0) {
                const items = ovSource[isoKey];
                let chosen = null;
                for (const ov of items) { if (ov.effect === 'holiday') { chosen = ov; break; } }
                if (!chosen) { for (const ov of items) { if (ov.effect === 'block_all') { chosen = ov; break; } } }
                if (!chosen) { chosen = items[0]; }
                const badge = document.createElement('span');
                const isOnlineDay = (chosen.effect === 'force_mode' && (chosen.reason_key === 'online_day'));
                const isEndYear = (chosen.effect === 'block_all' && (chosen.reason_key === 'end_year'));
                const chosenCls = (chosen.effect === 'holiday'
                  ? 'ov-holiday'
                  : (chosen.effect === 'block_all'
                    ? (isEndYear ? 'ov-endyear' : 'ov-blocked')
                    : (isOnlineDay ? 'ov-online' : 'ov-force')));
                badge.className = 'ov-badge ' + chosenCls;
                const forceLabel = isOnlineDay ? 'Online Day' : 'Forced Online';
                const labelTxt = (chosen.effect === 'holiday')
                  ? (chosen.reason_text || 'Holiday')
                  : (chosen.effect === 'block_all'
                    ? (isEndYear ? 'End Year' : 'Suspension')
                    : forceLabel);
                badge.title = chosen.label || chosen.reason_text || labelTxt;
                badge.textContent = labelTxt;
                cell.style.position = 'relative';
                cell.appendChild(badge);
                const dayCls = (chosen.effect === 'holiday'
                  ? 'day-holiday'
                  : (chosen.effect === 'block_all'
                    ? (isEndYear ? 'day-endyear' : 'day-blocked')
                    : (isOnlineDay ? 'day-online' : 'day-force')));
                cell.classList.add(dayCls);
              }

              // Render booking status when data is available (bookingMap may initially be empty)
              if (bookingMap.has(key)) {
                const booking = bookingMap.get(key);
                const status = booking.status;
                const consultationsForDay = detailsMap.get(key) || [];
                const consultationCount = consultationsForDay.length;
                const classMap = {
                  pending: 'status-pending',
                  approved: 'status-approved',
                  completed: 'status-completed',
                  rescheduled: 'status-rescheduled'
                };
                cell.classList.add('has-booking');
                cell.classList.add(classMap[status]);
                cell.setAttribute('data-status', status);
                if (consultationCount >= 2) cell.classList.add('has-multiple-bookings');
                cell.setAttribute('data-consultation-count', consultationCount);
                // Prepare hover data
                cell.setAttribute('data-consultation-key', key);
                cell.setAttribute('data-has-consultations', 'true');
                cell.style.cursor = 'default';
              }
            }
          });
          // Debug: show how many override days are available for the visible month
          try {
            const monthKey = `${baseForDraw.getFullYear()}-${String(baseForDraw.getMonth()+1).padStart(2,'0')}`;
            const src = (function(){
              if (window.adminOverrides && typeof window.adminOverrides === 'object') return window.adminOverrides;
              if (window.__adminOvCacheByMonth && window.__adminOvCacheByMonth[monthKey]) return window.__adminOvCacheByMonth[monthKey];
              if (window.__adminOvCache && typeof window.__adminOvCache === 'object') return window.__adminOvCache;
              if (window.__adminOvGlobalCache && typeof window.__adminOvGlobalCache === 'object') return window.__adminOvGlobalCache;
              return null;
            })();
            if (src) {
              const cnt = Object.keys(src).filter(k=>Array.isArray(src[k]) && src[k].length>0).length;
              console.debug('[OV] Draw visible month', monthKey, 'days:', cnt);
            } else {
              console.debug('[OV] Draw visible month', monthKey, 'no source yet');
            }
          } catch(_) {}
        }
      });
      // Prepare overrides BEFORE first draw to avoid visible delay
      window.adminPicker = picker;
      try {
        hydrateCachesFromStorage();
        const basePre = getVisibleMonthBaseDate();
        const monthKeyPre = `${basePre.getFullYear()}-${String(basePre.getMonth()+1).padStart(2,'0')}`;
        // Prefer stored month cache
        const storedMonth = (typeof loadMonthFromStorage === 'function') ? loadMonthFromStorage(monthKeyPre) : null;
        if (storedMonth && Object.keys(storedMonth).length > 0) {
          if (!window.__adminOvCacheByMonth) window.__adminOvCacheByMonth = {};
          window.__adminOvCacheByMonth[monthKeyPre] = storedMonth;
          window.adminOverrides = storedMonth;
          window.__adminOvCache = storedMonth;
        } else {
          // Compose from global persistent cache
          const composed = (typeof composeMonthMapFromGlobal === 'function') ? composeMonthMapFromGlobal(basePre) : null;
          if (composed) {
            if (!window.__adminOvCacheByMonth) window.__adminOvCacheByMonth = {};
            window.__adminOvCacheByMonth[monthKeyPre] = composed;
            window.adminOverrides = composed;
            window.__adminOvCache = composed;
          }
        }
      } catch(_) {}
      picker.show();
      picker.draw();
      // Instant paint from persistent cache and prefetch neighbors on first init
      try {
        const baseNow = getVisibleMonthBaseDate();
        hydrateCachesFromStorage();
        if (!applyCachedOverridesForMonth(baseNow)) {
          // Try composing month map from global cache stored in localStorage
          const map = composeMonthMapFromGlobal(baseNow);
          if (map) {
            window.adminOverrides = map;
            window.__adminOvCache = map;
            if (!window.__adminOvCacheByMonth) window.__adminOvCacheByMonth = {};
            const keyNow = `${baseNow.getFullYear()}-${String(baseNow.getMonth()+1).padStart(2,'0')}`;
            window.__adminOvCacheByMonth[keyNow] = map;
            picker.draw();
          }
        }
        prefetchAdjacentMonths(baseNow);
      } catch(_) {}
    })();

    // Kick off initial background fetches without blocking initial render
    (function initialFetches() {
      // Fetch overrides for visible month right away; fetchAdminOverridesForMonth will redraw on success
      try {
        const base = (function(){
          const t = new Date();
          return new Date(t.getFullYear(), t.getMonth(), 1);
        })();
        fetchAdminOverridesForMonth(base);
      } catch (e) { console.warn('Initial overrides fetch error:', e); }

      // Load consultations data; when it completes, loadAdminCalendarData will also refresh overrides as needed
      if (typeof loadAdminCalendarData === 'function') {
        loadAdminCalendarData();
      } else {
        // Fallback simple populate if function not yet defined (should be defined later)
        fetch('/api/admin/all-consultations')
          .then(r => r.json())
          .then(data => {
            bookingMap.clear();
            detailsMap.clear();
            data.forEach(entry => {
              const date = new Date(entry.Booking_Date);
              const key = date.toDateString();
              bookingMap.set(key, { status: entry.Status.toLowerCase(), id: entry.Booking_ID });
              if (!detailsMap.has(key)) detailsMap.set(key, []);
              detailsMap.get(key).push(entry);
            });
            if (window.adminPicker) window.adminPicker.draw();
          })
          .catch(err => console.warn('Initial consultations fetch failed:', err));
      }
    })();

    // Fetch overrides for current visible month and cache on window
  function fetchAdminOverridesForMonth(dateObj) {
      try {
        if (!dateObj || !(dateObj instanceof Date) || isNaN(dateObj.getTime())) {
          console.warn('Overrides fetch skipped: invalid base date', dateObj);
          return;
        }
        const start = new Date(dateObj.getFullYear(), dateObj.getMonth(), 1);
        const end = new Date(dateObj.getFullYear(), dateObj.getMonth() + 1, 0);
        const monthKey = `${start.getFullYear()}-${String(start.getMonth()+1).padStart(2,'0')}`;
        const toIso = (d) => {
          if (!d || !(d instanceof Date) || isNaN(d.getTime())) return null;
          const y = d.getFullYear();
          const m = String(d.getMonth() + 1).padStart(2, '0');
          const day = String(d.getDate()).padStart(2, '0');
          return `${y}-${m}-${day}`;
        };
        const startStr = toIso(start);
        const endStr = toIso(end);
        if (!startStr || !endStr) {
          console.warn('Overrides fetch skipped: start/end invalid', { start, end });
          return;
        }
        const adminUrl = `/api/admin/calendar/overrides?start_date=${startStr}&end_date=${endStr}`;
        const publicUrl = `/api/calendar/overrides?start_date=${startStr}&end_date=${endStr}`;
        console.debug('[OV] Fetching admin overrides for', monthKey, adminUrl);
        fetch(adminUrl, {
        method: 'GET',
        headers: { 'Accept':'application/json' },
        credentials: 'same-origin'
      }).then(async r=>{
        if (!r.ok) {
          console.warn('[OV] Admin overrides HTTP status', r.status);
          throw new Error('http_' + r.status);
        }
        let data;
        try {
          data = await r.json();
        } catch(jsonErr) {
          console.warn('[OV] Admin overrides non-JSON response, keeping cache', jsonErr);
          throw new Error('non_json');
        }
        if (data && data.success) {
          const incoming = data.overrides || {};
          const keys = Object.keys(incoming).filter(k=>Array.isArray(incoming[k]) && incoming[k].length>0);
          console.debug('[OV] Admin overrides loaded', { monthKey, days: keys.length, sample: keys.slice(0,5) });
          // Init per-month cache
          if (!window.__adminOvCacheByMonth) window.__adminOvCacheByMonth = {};
          window.__adminOvCacheByMonth[monthKey] = incoming;
          // Merge into global cache for instant cross-month paint
          if (!window.__adminOvGlobalCache) window.__adminOvGlobalCache = {};
          keys.forEach(k=>{ window.__adminOvGlobalCache[k] = incoming[k]; });
          // Persist to localStorage
          persistMonthToStorage(monthKey, incoming);
          persistGlobalToStorage(window.__adminOvGlobalCache);
          // Only update live overrides if the fetched month matches the currently visible month
          const visibleBase = (function(){
            try { return getVisibleMonthBaseDate(); } catch(_) { const t=new Date(); return new Date(t.getFullYear(), t.getMonth(), 1); }
          })();
          const visibleKey = `${visibleBase.getFullYear()}-${String(visibleBase.getMonth()+1).padStart(2,'0')}`;
          if (visibleKey === monthKey) {
            window.adminOverrides = incoming;
            // Legacy single cache for older paths
            window.__adminOvCache = incoming;
            // Re-draw if picker exists to paint badges
            if (window.adminPicker) window.adminPicker.draw();
          }
        } else {
          console.warn('[OV] Admin overrides payload not successful', data);
          throw new Error('payload_unsuccessful');
        }
      }).catch((e) => {
        console.warn('[OV] Admin overrides fetch failed, will try public fallback', e && e.message);
        // Public fallback: global overrides only (fine for Suspended/Holiday/Online Day global cases)
        fetch(publicUrl, { method:'GET', headers:{'Accept':'application/json'} })
          .then(r=>r.ok ? r.json() : Promise.reject(new Error('public_http_'+r.status)))
          .then(data=>{
            if (data && data.success) {
              const incoming = data.overrides || {};
              const keys = Object.keys(incoming).filter(k=>Array.isArray(incoming[k]) && incoming[k].length>0);
              console.debug('[OV/FALLBACK] Public overrides loaded', { monthKey, days: keys.length, sample: keys.slice(0,5) });
              if (!window.__adminOvCacheByMonth) window.__adminOvCacheByMonth = {};
              window.__adminOvCacheByMonth[monthKey] = incoming;
              if (!window.__adminOvGlobalCache) window.__adminOvGlobalCache = {};
              Object.keys(incoming).forEach(k=>{
                if (Array.isArray(incoming[k]) && incoming[k].length>0) window.__adminOvGlobalCache[k] = incoming[k];
              });
              persistMonthToStorage(monthKey, incoming);
              persistGlobalToStorage(window.__adminOvGlobalCache);
              const visibleBase = (function(){
                try { return getVisibleMonthBaseDate(); } catch(_) { const t=new Date(); return new Date(t.getFullYear(), t.getMonth(), 1); }
              })();
              const visibleKey = `${visibleBase.getFullYear()}-${String(visibleBase.getMonth()+1).padStart(2,'0')}`;
              if (visibleKey === monthKey) {
                // Note: mark as fallback source
                window.adminOverrides = incoming;
                window.__adminOvCache = incoming;
                if (window.adminPicker) window.adminPicker.draw();
              }
            } else {
              console.warn('[OV/FALLBACK] Public overrides payload not successful', data);
            }
          })
          .catch(err=>{
            console.warn('[OV/FALLBACK] Public overrides failed', err && err.message);
          });
      });
      } catch (err) {
        console.error('Admin Error loading calendar data:', err);
      }
    }

    // Fast path: immediately apply cached overrides for a given month (no network)
    function applyCachedOverridesForMonth(dateObj) {
      try {
        if (!dateObj || !(dateObj instanceof Date) || isNaN(dateObj.getTime())) return;
        const monthKey = `${dateObj.getFullYear()}-${String(dateObj.getMonth()+1).padStart(2,'0')}`;
        if (window.__adminOvCacheByMonth && window.__adminOvCacheByMonth[monthKey]) {
          window.adminOverrides = window.__adminOvCacheByMonth[monthKey];
          window.__adminOvCache = window.adminOverrides; // legacy single cache
          if (window.adminPicker) window.adminPicker.draw();
          console.debug('[OV] Applied cached overrides for', monthKey);
          return true;
        }
        // Try from persistent storage
        hydrateCachesFromStorage();
        const map = composeMonthMapFromGlobal(dateObj);
        if (map) {
          if (!window.__adminOvCacheByMonth) window.__adminOvCacheByMonth = {};
          window.__adminOvCacheByMonth[monthKey] = map;
          window.adminOverrides = map;
          window.__adminOvCache = map;
          if (window.adminPicker) window.adminPicker.draw();
          console.debug('[OV] Applied composed overrides from stored global for', monthKey);
          return true;
        }
      } catch(_) {}
      return false;
    }

    // Prefetch adjacent months (previous/next) if not cached yet
    function prefetchAdjacentMonths(baseDate) {
      try {
        if (!baseDate || isNaN(baseDate.getTime())) return;
        const prev = new Date(baseDate.getFullYear(), baseDate.getMonth() - 1, 1);
        const next = new Date(baseDate.getFullYear(), baseDate.getMonth() + 1, 1);
        const needPrev = !(window.__adminOvCacheByMonth && window.__adminOvCacheByMonth[`${prev.getFullYear()}-${String(prev.getMonth()+1).padStart(2,'0')}`]);
        const needNext = !(window.__adminOvCacheByMonth && window.__adminOvCacheByMonth[`${next.getFullYear()}-${String(next.getMonth()+1).padStart(2,'0')}`]);
        if (needPrev) fetchAdminOverridesForMonth(prev);
        if (needNext) fetchAdminOverridesForMonth(next);
      } catch(_) {}
    }

    // Helper: find the currently visible calendar month as a safe Date (YYYY,MM,1)
    function getVisibleMonthBaseDate() {
      try {
        // 1) Prefer Pikaday select elements (most reliable)
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
        // 2) Parse label; support full and short month names
        const labelEl = document.querySelector('.pika-label');
        if (labelEl) {
          const text = (labelEl.textContent || '').trim();
          const parts = text.split(/\s+/);
          if (parts.length === 2) {
            const monthMap = {
              January:0, February:1, March:2, April:3, May:4, June:5, July:6, August:7, September:8, October:9, November:10, December:11,
              Jan:0, Feb:1, Mar:2, Apr:3, Jun:5, Jul:6, Aug:7, Sep:8, Oct:9, Nov:10, Dec:11
            };
            const m = monthMap[parts[0]];
            const y = parseInt(parts[1], 10);
            if (!isNaN(m) && !isNaN(y)) {
              const d = new Date(y, m, 1);
              if (!isNaN(d.getTime())) return d;
            }
          }
        }
        // 3) Fallback: use any current-month day cell if available
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

    // Hook into month navigation to refresh overrides
    (function observeMonthNavigation(){
      const calendarEl = document.getElementById('calendar');
      if (!calendarEl) return;
      const run = () => {
        const base = getVisibleMonthBaseDate();
        // Paint instantly from cache if available, then update via network
        applyCachedOverridesForMonth(base);
        fetchAdminOverridesForMonth(base);
        // Proactively fetch neighbors
        prefetchAdjacentMonths(base);
      };
      // Initial load
      setTimeout(run, 100);
      // Re-run on next/prev clicks
      document.addEventListener('click', (e)=>{
        const t = e.target;
        if (t.closest && (t.closest('.pika-prev') || t.closest('.pika-next'))) {
          setTimeout(run, 150);
        }
      });
      // Also handle month/year dropdown changes
      document.addEventListener('change', (e)=>{
        const el = e.target;
        if (!el) return;
        if (el.classList && (el.classList.contains('pika-select-month') || el.classList.contains('pika-select-year'))) {
          setTimeout(run, 80);
        }
      });
    })();

    // -------- Persistent storage helpers --------
    const LS_KEY_GLOBAL = 'ascc_admin_ov_global_v1';
    const LS_KEY_MONTH_PREFIX = 'ascc_admin_ov_month_v1:';
    function persistGlobalToStorage(map) {
      try { localStorage.setItem(LS_KEY_GLOBAL, JSON.stringify(map||{})); } catch(_) {}
    }
    function loadGlobalFromStorage() {
      try { const s = localStorage.getItem(LS_KEY_GLOBAL); return s ? JSON.parse(s) : {}; } catch(_) { return {}; }
    }
    function persistMonthToStorage(monthKey, map) {
      try { localStorage.setItem(LS_KEY_MONTH_PREFIX+monthKey, JSON.stringify(map||{})); } catch(_) {}
    }
    function loadMonthFromStorage(monthKey) {
      try { const s = localStorage.getItem(LS_KEY_MONTH_PREFIX+monthKey); return s ? JSON.parse(s) : null; } catch(_) { return null; }
    }
    function hydrateCachesFromStorage() {
      try {
        if (!window.__adminOvGlobalCache) window.__adminOvGlobalCache = loadGlobalFromStorage();
      } catch(_) {}
    }
    function composeMonthMapFromGlobal(dateObj) {
      try {
        if (!dateObj || isNaN(dateObj.getTime())) return null;
        hydrateCachesFromStorage();
        const g = window.__adminOvGlobalCache || {};
        const y = dateObj.getFullYear();
        const m = dateObj.getMonth();
        const first = new Date(y, m, 1);
        const last = new Date(y, m+1, 0);
        let any = false;
        const map = {};
        for (let d = new Date(first); d <= last; d.setDate(d.getDate()+1)) {
          const iso = `${d.getFullYear()}-${String(d.getMonth()+1).padStart(2,'0')}-${String(d.getDate()).padStart(2,'0')}`;
          if (Array.isArray(g[iso]) && g[iso].length>0) { map[iso] = g[iso]; any = true; }
        }
        return any ? map : null;
      } catch(_) { return null; }
    }

    // ADMIN TOOLTIP HOVER FUNCTIONALITY
    console.log('Setting up ADMIN global hover event delegation...');

    let tooltipTimeout;
    let currentHoveredCell = null;

    document.addEventListener('mouseover', function(e) {
      const target = e.target;
      
      // Clear any pending hide timeout
      if (tooltipTimeout) {
        clearTimeout(tooltipTimeout);
        tooltipTimeout = null;
      }
      
      // Check if the target is a Pikaday button with consultation data
      if (target && target.classList && target.classList.contains('pika-button') && target.hasAttribute('data-consultation-key')) {
        const key = target.getAttribute('data-consultation-key');
        
        // Only update tooltip if it's a different cell or tooltip is not visible
        const tooltip = document.getElementById('consultationTooltip');
        const isTooltipVisible = tooltip && tooltip.style.display === 'block';
        const isDifferentCell = currentHoveredCell !== target;
        
        if (!isTooltipVisible || isDifferentCell) {
          currentHoveredCell = target;
          
          console.log('Admin Hovering over cell with key:', key);
          const consultations = detailsMap.get(key) || [];
          console.log('Admin Consultations found:', consultations);
          
          if (consultations.length === 0) {
            return;
          }
          
          let html = '';

          // Add header with consultation count
          const countText = consultations.length === 1 ? '1 Consultation' : `${consultations.length} Consultations`;
          html += `<div class="tooltip-header">${countText}</div>`;

          // Helper to convert 'YYYY-MM-DD HH:MM:SS' to 'YYYY-MM-DD hh:MM:SS AM/PM'
          function formatTo12Hour(ts) {
            if (!ts) return '';
            const parts = ts.split(' ');
            if (parts.length < 2) return ts;
            const datePart = parts[0];
            const timePart = parts[1];
            const tPieces = timePart.split(':');
            if (tPieces.length < 2) return ts;
            let hour = parseInt(tPieces[0], 10);
            const minute = tPieces[1];
            const second = tPieces[2] || '00';
            if (isNaN(hour)) return ts;
            const suffix = hour >= 12 ? 'PM' : 'AM';
            const hour12 = ((hour + 11) % 12) + 1; // 0 -> 12
            const hourStr = hour12.toString().padStart(2, '0');
            return `${datePart} ${hourStr}:${minute}:${second} ${suffix}`;
          }

          // Determine earliest booking by Created_At for this day
          let firstIdx = -1;
          let minTs = Number.POSITIVE_INFINITY;
          try {
            consultations.forEach((entry, idx) => {
              const ts = new Date(entry.Created_At).getTime();
              if (!isNaN(ts) && ts < minTs) {
                minTs = ts;
                firstIdx = idx;
              }
            });
          } catch (_) {
            firstIdx = -1;
          }

          // Build each consultation entry (no actions)
          consultations.forEach((entry, index) => {
            const isFirst = index === firstIdx;
            const statusClass = getStatusClassName(entry.Status);
            html += `
              <div class="consultation-entry${isFirst ? ' is-first-booking' : ''}">
                <div class="student-name">${entry.student} have consultation with ${entry.professor}${isFirst ? '<span class="first-book-badge" title="First to book for this date">First</span>' : ''}</div>
                <div class="detail-row">Subject: ${entry.subject}</div>
                <div class="detail-row">Type: ${entry.type}</div>
                <div class="detail-row">Mode: ${entry.Mode}</div>
                <div class="status-row ${statusClass}">Status: ${entry.Status}</div>
                <div class="booking-time">Booked: ${formatTo12Hour(entry.Created_At)}</div>
              </div>
            `;
          });
          
          if (!tooltip) {
            console.error('Admin Tooltip element not found!');
            return;
          }
          
          tooltip.innerHTML = html;
          tooltip.style.display = 'block';

          // Anchor tooltip to the RIGHT of the hovered cell (consistent placement)
          const cellRect = target.getBoundingClientRect();
          const tooltipRect = tooltip.getBoundingClientRect();
          const viewportHeight = window.innerHeight;
          const scrollY = window.scrollY || document.documentElement.scrollTop;
          const scrollX = window.scrollX || document.documentElement.scrollLeft;
          const GAP = 12; // space between day cell and tooltip

          // Base positions
          let left = cellRect.right + GAP + scrollX;
          let top = cellRect.top + scrollY; // align tops by default

          // Vertical adjustments to keep fully in view
          if (top + tooltipRect.height > scrollY + viewportHeight - 10) {
            top = scrollY + viewportHeight - tooltipRect.height - 10;
          }
          if (top < scrollY + 10) {
            top = scrollY + 10;
          }

          // Optional: if extremely close to right edge and would overflow, gently shift left but keep to the right side
          const maxRight = scrollX + window.innerWidth - 10;
            if (left + tooltipRect.width > maxRight) {
              left = Math.min(left, maxRight - tooltipRect.width);
            }

          tooltip.style.left = left + 'px';
          tooltip.style.top = top + 'px';
        }
      } else {
        // Mouse is not over a consultation cell, check if it's over the tooltip
        if (currentHoveredCell && !target.closest('#consultationTooltip')) {
          tooltipTimeout = setTimeout(function() {
            const tooltip = document.getElementById('consultationTooltip');
            if (tooltip) {
              tooltip.style.display = 'none';
            }
            currentHoveredCell = null;
          }, 300); // Increased delay to allow moving to tooltip
        }
      }
    });

    document.addEventListener('mouseout', function(e) {
      const target = e.target;
      const relatedTarget = e.relatedTarget;
      
      // Check if we're leaving a consultation cell
      if (target && target.classList && target.classList.contains('pika-button') && target.hasAttribute('data-consultation-key')) {
        // Make sure we're not moving to the tooltip itself
        if (!relatedTarget || !relatedTarget.closest('#consultationTooltip')) {
          const tooltip = document.getElementById('consultationTooltip');
          if (tooltip) {
            tooltip.style.display = 'none';
          }
        }
      }
    });

    // Additional safety: Hide tooltip when mouse leaves the calendar area entirely
    document.addEventListener('mouseleave', function(e) {
      const target = e.target;
      if (target && target.classList && (target.classList.contains('pika-table') || target.closest('.pika-single'))) {
        const tooltip = document.getElementById('consultationTooltip');
        if (tooltip) {
          tooltip.style.display = 'none';
        }
      }
    });

    // Hide tooltip when clicking anywhere outside calendar cells
    document.addEventListener('click', function(e) {
      const target = e.target;
      if (!target || !target.classList || !target.classList.contains('pika-button')) {
        const tooltip = document.getElementById('consultationTooltip');
        if (tooltip) {
          tooltip.style.display = 'none';
        }
      }
    });

    console.log('Admin Global hover delegation system initialized');

    // Add hover events to tooltip to keep it stable for scrolling
    document.addEventListener('DOMContentLoaded', function() {
      const tooltip = document.getElementById('consultationTooltip');
      if (tooltip) {
        // Keep tooltip visible when hovering over it
        tooltip.addEventListener('mouseenter', function() {
          if (tooltipTimeout) {
            clearTimeout(tooltipTimeout);
            tooltipTimeout = null;
          }
        });
        
        // Hide tooltip when leaving it (with delay)
        tooltip.addEventListener('mouseleave', function() {
          tooltipTimeout = setTimeout(function() {
            tooltip.style.display = 'none';
            currentHoveredCell = null;
          }, 200);
        });
      }
    });

    // PREVENT ONLY CLICK AND TOUCH EVENTS ON CALENDAR DATE CELLS, ALLOW HOVER
    // Allow clicks when admin date edit modal is enabled
    window.ADMIN_DATE_EDIT_ENABLED = true;
    function preventCalendarClicks(e) {
      const target = e.target;
      // Only prevent clicks/touches on date buttons inside the table, not navigation buttons
      // Allow mouseover/mouseout for tooltips
      if (window.ADMIN_DATE_EDIT_ENABLED) {
        return; // allow click through for admin edit
      }
      if (target && target.classList && target.classList.contains('pika-button') && target.closest('.pika-table')) {
        if (e.type === 'click' || e.type === 'mousedown' || e.type === 'touchstart' || e.type === 'touchend') {
          e.preventDefault();
          e.stopPropagation();
          e.stopImmediatePropagation();
          console.log('Admin Calendar date interaction prevented:', e.type);
          return false;
        }
      }
    }

    // Prevent only specific events that cause date selection
    ['click', 'mousedown', 'touchstart', 'touchend'].forEach(eventType => {
      document.addEventListener(eventType, preventCalendarClicks, true); // Capture phase
      document.addEventListener(eventType, preventCalendarClicks, false); // Bubble phase
    });

    // --- Admin Date Edit Modal ---
    // Modal template
    const modalHtml = `
      <div id="adminOverrideBackdrop" class="admin-override-backdrop hidden"></div>
      <div id="adminOverrideModal" class="admin-override-modal hidden" role="dialog" aria-modal="true" aria-labelledby="adminOverrideTitle">
        <div class="admin-override-header">
          <div id="adminOverrideTitle" class="admin-override-title">Edit Day</div>
          <button id="adminOverrideClose" type="button" class="admin-override-close" aria-label="Close">×</button>
        </div>
        <div class="admin-override-body">
          <div class="admin-override-date">Date: <span id="adminOverrideDate" class="admin-override-date-value"></span></div>
          <div class="admin-override-options">
            <label class="admin-override-option"><input type="radio" name="ov_effect" value="online_day"> Online Day</label>
            <label class="admin-override-option"><input type="radio" name="ov_effect" value="force_online"> Forced Online</label>
            <label class="admin-override-option"><input type="radio" name="ov_effect" value="block_all"> Suspension</label>
            <label class="admin-override-option"><input type="radio" name="ov_effect" value="holiday"> Holiday</label>
            <label class="admin-override-option"><input type="radio" name="ov_effect" value="end_year"> End of School Year</label>
          </div>
          <div id="forceModeRow" class="admin-override-row admin-override-row--flex hidden">
            <label class="admin-override-label">Allowed Mode:
              <select id="ov_allowed_mode" class="admin-override-select">
                <option value="online">Online</option>
                <option value="onsite">Onsite</option>
              </select>
            </label>
          </div>
          <div id="reasonRow" class="admin-override-row admin-override-row--flex hidden">
            <label class="admin-override-label">Reason
              <select id="ov_reason_key" class="admin-override-select">
                <option value="">—</option>
                <option value="weather">Weather</option>
                <option value="power_outage">Power outage</option>
                <option value="health_advisory">Health advisory</option>
                <option value="holiday_shift">Holiday shift</option>
                <option value="facility">Facility issue</option>
                <option value="others">Others</option>
              </select>
            </label>
            <input id="ov_reason_text" class="admin-override-input admin-override-input--wide hidden" placeholder="Enter reason">
          </div>
          <div id="holidayRow" class="admin-override-row hidden">
            <label class="admin-override-label">Holiday Name
              <input id="ov_holiday_name" class="admin-override-input admin-override-input--wide" placeholder="e.g., Christmas Day">
            </label>
          </div>
          <div id="endYearRow" class="admin-override-row admin-override-row--end hidden">
            <div><strong>Start day:</strong> <span id="ov_start_label">—</span></div>
            <label class="admin-override-label"><strong>End day:</strong>
              <input id="ov_end_date" type="date" class="admin-override-input">
            </label>
            <div class="admin-override-helper">All days from Start to End will be disabled (no classes).</div>
          </div>
          <div id="autoReschedRow" class="admin-override-row admin-override-row--auto hidden">
            <label class="admin-override-option"><input type="checkbox" id="ov_auto_reschedule"> Auto‑reschedule affected bookings</label>
            <div class="admin-override-helper">All affected bookings will be rescheduled. Exam/Quiz bookings will be placed first into onsite slots. Others will follow mode rules.</div>
          </div>
          <div id="ov_preview" class="admin-override-preview hidden"></div>
        </div>
        <div class="admin-override-footer">
          <button id="ovRemoveBtn" type="button" class="admin-override-btn admin-override-btn--danger">Remove</button>
          <div class="admin-override-footer-actions">
            <button id="ovPreviewBtn" type="button" class="admin-override-btn admin-override-btn--muted">Preview</button>
            <button id="ovApplyBtn" type="button" class="admin-override-btn admin-override-btn--primary">Apply</button>
          </div>
        </div>
      </div>`;

    // Inject modal once
    if (!document.getElementById('adminOverrideModal')) {
      const wrap = document.createElement('div');
      wrap.innerHTML = modalHtml;
      document.body.appendChild(wrap);
    }

    // Helper: determine if a given date has any override applied (by data map or DOM fallback)
    function hasOverrideForDate(dateStr) {
      try {
        const dt = new Date(dateStr);
        const iso = isNaN(dt.getTime()) ? null : `${dt.getFullYear()}-${String(dt.getMonth()+1).padStart(2,'0')}-${String(dt.getDate()).padStart(2,'0')}`;
        if (iso && window.adminOverrides && window.adminOverrides[iso] && window.adminOverrides[iso].length > 0) {
          return true;
        }
        // Fallback: scan DOM for a badge or override day class on the specific cell
        const cells = document.querySelectorAll('.pika-button');
        for (const cell of cells) {
          const d = new Date(
            cell.getAttribute('data-pika-year'),
            cell.getAttribute('data-pika-month'),
            cell.getAttribute('data-pika-day')
          );
          if (d.toDateString() === dateStr) {
            if (cell.querySelector('.ov-badge')) return true;
            if (
              cell.classList.contains('day-holiday') ||
              cell.classList.contains('day-blocked') ||
              cell.classList.contains('day-force') ||
              cell.classList.contains('day-endyear')
            ) return true;
            break;
          }
        }
      } catch (e) { /* noop */ }
      return false;
    }

    function openOverrideModal(dateStr) {
      const modal = document.getElementById('adminOverrideModal');
      const backdrop = document.getElementById('adminOverrideBackdrop');
      const dateLabel = document.getElementById('adminOverrideDate');
      if (dateLabel) {
        dateLabel.textContent = dateStr;
      }
      if (modal) {
        modal.classList.remove('hidden');
      }
      if (backdrop) {
        backdrop.classList.remove('hidden');
      }
      // Clear previous selection/state each time the modal opens
      try {
        document.querySelectorAll('input[name="ov_effect"]').forEach(r => r.checked = false);
        const rk = document.getElementById('ov_reason_key');
        const rt = document.getElementById('ov_reason_text');
        const hn = document.getElementById('ov_holiday_name');
        const ed = document.getElementById('ov_end_date');
        const sl = document.getElementById('ov_start_label');
  if (rk) rk.value = '';
  if (rt) { rt.value = ''; rt.disabled = true; rt.classList.add('hidden'); rt.placeholder = 'Enter reason'; }
        if (hn) hn.value = '';
        if (ed) {
          ed.value = '';
          // Ensure min is updated each time the modal opens
          try {
            const today = new Date();
            const start = new Date(dateStr);
            const base = (isNaN(start) ? today : (start > today ? start : today));
            const iso = `${base.getFullYear()}-${String(base.getMonth()+1).padStart(2,'0')}-${String(base.getDate()).padStart(2,'0')}`;
            ed.min = iso;
          } catch(_) {}
        }
        if (sl) sl.textContent = dateStr || '—';
        const ar = document.getElementById('ov_auto_reschedule');
        if (ar) ar.checked = false;
        const previewBox = document.getElementById('ov_preview');
        if (previewBox) {
          previewBox.classList.add('hidden');
          previewBox.innerHTML = '';
        }
      } catch(_) {}
      // Toggle Remove button availability based on whether an override exists on that date
      const removeBtn = document.getElementById('ovRemoveBtn');
      if (removeBtn) {
        const exists = hasOverrideForDate(dateStr);
        removeBtn.disabled = !exists;
        removeBtn.setAttribute('aria-disabled', String(!exists));
        removeBtn.title = exists ? 'Remove existing override' : 'No override on this date';
      }
      // Ensure rows reflect the currently selected option (none by default)
      if (typeof updateOverrideRows === 'function') updateOverrideRows();
    }
    function closeOverrideModal() {
      const modal = document.getElementById('adminOverrideModal');
      const backdrop = document.getElementById('adminOverrideBackdrop');
      if (modal) {
        modal.classList.add('hidden');
      }
      if (backdrop) {
        backdrop.classList.add('hidden');
      }
      const preview = document.getElementById('ov_preview');
      if (preview) { preview.classList.add('hidden'); preview.innerHTML=''; }
    }
    (function(){
      const closeBtn = document.getElementById('adminOverrideClose');
      if (closeBtn) closeBtn.addEventListener('click', closeOverrideModal);
      const backdrop = document.getElementById('adminOverrideBackdrop');
      if (backdrop) backdrop.addEventListener('click', closeOverrideModal);
    })();

    // Centralized UI toggle for modal rows
    function updateOverrideRows() {
      const effect = document.querySelector('input[name="ov_effect"]:checked')?.value;
      const forceRow = document.getElementById('forceModeRow');
      const autoRow = document.getElementById('autoReschedRow');
      const reasonRow = document.getElementById('reasonRow');
      const holidayRow = document.getElementById('holidayRow');
      const endYearRow = document.getElementById('endYearRow');
      const reasonKeyEl = document.getElementById('ov_reason_key');
      const reasonTextEl = document.getElementById('ov_reason_text');

      if (!effect) {
        if (forceRow) forceRow.classList.add('hidden');
        if (autoRow) autoRow.classList.add('hidden');
        if (reasonRow) reasonRow.classList.add('hidden');
        if (holidayRow) holidayRow.classList.add('hidden');
        if (endYearRow) endYearRow.classList.add('hidden');
        if (reasonKeyEl) {
          reasonKeyEl.disabled = true;
          reasonKeyEl.value = '';
        }
        if (reasonTextEl) {
          reasonTextEl.disabled = true;
          reasonTextEl.value = '';
          reasonTextEl.placeholder = 'Notes (optional)';
          reasonTextEl.classList.add('hidden');
        }
        return;
      }

      if (forceRow) {
        const showForce = effect === 'force_online';
        forceRow.classList.toggle('hidden', !showForce);
      }

      if (autoRow) {
        autoRow.classList.toggle('hidden', !(effect === 'block_all' || effect === 'force_online'));
      }

      if (endYearRow) {
        endYearRow.classList.toggle('hidden', effect !== 'end_year');
        try {
          const ed = document.getElementById('ov_end_date');
          const startLabel = document.getElementById('ov_start_label')?.textContent || '';
          const start = new Date(startLabel);
          const today = new Date();
          const base = (!isNaN(start) && start > today) ? start : today;
          const iso = `${base.getFullYear()}-${String(base.getMonth()+1).padStart(2,'0')}-${String(base.getDate()).padStart(2,'0')}`;
          if (ed) {
            ed.min = iso;
            if (ed.value && ed.value < iso) ed.value = '';
          }
        } catch (_) {}
      }

      const hideReasons = effect === 'online_day' || effect === 'holiday' || effect === 'end_year';
      if (reasonRow) {
        reasonRow.classList.toggle('hidden', hideReasons);
      }
      if (holidayRow) {
        holidayRow.classList.toggle('hidden', effect !== 'holiday');
      }

      if (reasonKeyEl) {
        reasonKeyEl.disabled = hideReasons;
        if (hideReasons) {
          reasonKeyEl.value = '';
        }
      }
      if (reasonTextEl) {
        reasonTextEl.disabled = hideReasons;
        if (hideReasons) {
          reasonTextEl.value = '';
          reasonTextEl.classList.add('hidden');
        } else {
          const rkVal = reasonKeyEl ? reasonKeyEl.value : '';
          const showText = rkVal === 'others';
          reasonTextEl.classList.toggle('hidden', !showText);
          if (!showText) {
            reasonTextEl.value = '';
          }
          reasonTextEl.placeholder = 'Enter reason';
        }
      }
    }
    // Change handler for reason key to toggle placeholder and ensure input is enabled when Others
    document.addEventListener('change', function(e){
      if (e.target && e.target.id === 'ov_reason_key') {
        const reasonTextEl = document.getElementById('ov_reason_text');
        const val = e.target.value;
        if (reasonTextEl) {
          const showText = (val === 'others');
          reasonTextEl.classList.toggle('hidden', !showText);
          reasonTextEl.disabled = !showText;
          if (!showText) reasonTextEl.value = '';
          reasonTextEl.placeholder = 'Enter reason';
        }
      }
    });

    // Wire change handler for radios
    document.addEventListener('change', function(e){
      if (e.target && e.target.name === 'ov_effect') {
        updateOverrideRows();
      }
    });

    // Helper to (re-)compute and set min for end date field
    function setEndDateMin() {
      try {
        const ed = document.getElementById('ov_end_date');
        if (!ed) return;
        const startLabel = document.getElementById('ov_start_label')?.textContent || '';
        const start = new Date(startLabel);
        const today = new Date();
        const base = (!isNaN(start) && start > today) ? start : today;
        const iso = `${base.getFullYear()}-${String(base.getMonth()+1).padStart(2,'0')}-${String(base.getDate()).padStart(2,'0')}`;
        ed.min = iso;
      } catch(_) {}
    }

    // Enforce min on value change
    function enforceEndDateMin() {
      const ed = document.getElementById('ov_end_date');
      if (!ed) return;
      if (ed.min && ed.value && ed.value < ed.min) {
        ed.value = ed.min;
        try { if (typeof showToast === 'function') showToast('End day cannot be in the past.', 'error'); } catch(_) {}
      }
    }

    // Apply min when the input is focused or clicked, and verify on change
    document.addEventListener('focusin', function(e){ if (e.target && e.target.id === 'ov_end_date') setEndDateMin(); });
    document.addEventListener('click', function(e){ if (e.target && e.target.id === 'ov_end_date') setEndDateMin(); });
    document.addEventListener('change', function(e){ if (e.target && e.target.id === 'ov_end_date') enforceEndDateMin(); });

    // Helper: is a JS Date before today (local)?
    function isPastDay(d) {
      if (!(d instanceof Date) || isNaN(d.getTime())) return false;
      const today = new Date();
      today.setHours(0,0,0,0);
      const cmp = new Date(d.getFullYear(), d.getMonth(), d.getDate());
      return cmp < today;
    }

    // Click handler on date cells to open modal (robust delegation)
    document.addEventListener('click', function(e){
      if (!window.ADMIN_DATE_EDIT_ENABLED) return;
      const btn = e.target && e.target.closest ? e.target.closest('.pika-button') : null;
      if (!btn) return;
      // Ensure it's inside the calendar table, not prev/next buttons
      if (!btn.closest('.pika-table')) return;
      const year = btn.getAttribute('data-pika-year');
      const month = btn.getAttribute('data-pika-month');
      const day = btn.getAttribute('data-pika-day');
      if (year && month && day) {
        const d = new Date(year, month, day);
        // Do not allow editing past days
        if (isPastDay(d)) { try { if (typeof showToast === 'function') showToast('Cannot edit a past day.', 'error'); } catch(_) {} return; }
        const dateStr = d.toDateString();
        openOverrideModal(dateStr);
      }
    });

    // Also handle mousedown early in capture phase to beat any other handlers
    document.addEventListener('mousedown', function(e){
      if (!window.ADMIN_DATE_EDIT_ENABLED) return;
      const btn = e.target && e.target.closest ? e.target.closest('.pika-button') : null;
      if (!btn || !btn.closest('.pika-table')) return;
      const year = btn.getAttribute('data-pika-year');
      const month = btn.getAttribute('data-pika-month');
      const day = btn.getAttribute('data-pika-day');
      if (year && month && day) {
        // Prevent Pikaday from consuming the click if we're editing
        e.preventDefault();
        e.stopPropagation();
        const d = new Date(year, month, day);
        if (isPastDay(d)) { return; }
        const dateStr = d.toDateString();
        openOverrideModal(dateStr);
      }
    }, true);

    // Preview and Apply actions
    function postJson(url, payload){
      return fetch(url, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify(payload)
      }).then(r => r.json());
    }

  const ovPreviewBtn = document.getElementById('ovPreviewBtn');
  if (ovPreviewBtn) ovPreviewBtn.addEventListener('click', function(){
    const checkedRadio = document.querySelector('input[name="ov_effect"]:checked');
    if (!checkedRadio) { showToast('Please select a day type', 'error'); return; }
    const sel = checkedRadio.value;
      // Map UI selection into API effect/allowed_mode
      let effect = sel;
      let allowed = null;
      if (sel === 'force_online' || sel === 'online_day') { effect = 'force_mode'; allowed = 'online'; }
      let reason_key, reason_text;
      if (effect === 'holiday') {
        reason_key = 'holiday';
        reason_text = (document.getElementById('ov_holiday_name').value || '').trim();
      } else if (sel === 'online_day') {
        // Online Day: no reason/notes
        reason_key = 'online_day';
        reason_text = '';
      } else if (sel === 'end_year') {
        // End of School Year → block all days in range
        reason_key = 'end_year';
        reason_text = '';
      } else {
        // Forced Online / Suspended: allow reasons
        reason_key = document.getElementById('ov_reason_key').value;
        const rtVal = (document.getElementById('ov_reason_text').value || '').trim();
        reason_text = (reason_key === 'others') ? rtVal : '';
        // If Others is selected, require a typed reason
        if ((sel === 'block_all' || sel === 'force_online') && reason_key === 'others') {
          if (!reason_text) {
            showToast('Please enter a reason for Others', 'error');
            return;
          }
        }
      }
      const dateLabel = document.getElementById('adminOverrideDate').textContent;
      const start = new Date(dateLabel);
      if (!(start instanceof Date) || isNaN(start.getTime())) {
        alert('Selected date is invalid. Please pick another day.');
        return;
      }
      const startIso = `${start.getFullYear()}-${String(start.getMonth()+1).padStart(2,'0')}-${String(start.getDate()).padStart(2,'0')}`;
      // Validate end date when end_year
      let endIso = null;
      if (sel === 'end_year') {
        const endVal = document.getElementById('ov_end_date')?.value;
        if (!endVal) { showToast('Please pick an end day', 'error'); return; }
        const end = new Date(endVal + 'T00:00:00');
        if (!(end instanceof Date) || isNaN(end.getTime()) || end < start) {
          showToast('End day must be the same or after start day', 'error'); return;
        }
        endIso = `${end.getFullYear()}-${String(end.getMonth()+1).padStart(2,'0')}-${String(end.getDate()).padStart(2,'0')}`;
        effect = 'block_all'; // backend: disable days for the range
      }
      const payload = {
        start_date: startIso,
        end_date: endIso,
        effect: effect,
        allowed_mode: effect === 'force_mode' ? allowed : null,
        reason_key, reason_text,
        auto_reschedule: document.getElementById('ov_auto_reschedule').checked
      };
      postJson('/api/admin/calendar/overrides/preview', payload).then(data => {
        const box = document.getElementById('ov_preview');
        if (!box) return;
        box.classList.remove('hidden');
        if (data && data.success) {
          let html = `<div><strong>Preview</strong></div>`;
          if (payload.end_date) {
            html += `<div>Range: ${dateLabel} → ${document.getElementById('ov_end_date')?.value || ''}</div>`;
          }
          html += `<div>Affected bookings: ${data.affected_count}</div>`;
          if (typeof data.reschedule_candidate_count !== 'undefined') {
            html += `<div>Rescheduling candidates (exam/quiz): ${data.reschedule_candidate_count}</div>`;
          }
          box.innerHTML = html;
        } else {
          box.innerHTML = `<div class="text-error">Failed to preview.</div>`;
        }
      }).catch(()=>{
        const box = document.getElementById('ov_preview');
        if (!box) return;
        box.classList.remove('hidden');
        box.innerHTML = `<div class="text-error">Failed to preview.</div>`;
      });
    });

  const ovApplyBtn = document.getElementById('ovApplyBtn');
  if (ovApplyBtn) ovApplyBtn.addEventListener('click', async function(){
    const checkedRadio = document.querySelector('input[name="ov_effect"]:checked');
    if (!checkedRadio) { showToast('Please select a day type', 'error'); return; }
    const sel = checkedRadio.value;
      let effect = sel;
      let allowed = null;
      if (sel === 'force_online' || sel === 'online_day') { effect = 'force_mode'; allowed = 'online'; }
      let reason_key, reason_text;
      if (effect === 'holiday') {
        reason_key = 'holiday';
        reason_text = (document.getElementById('ov_holiday_name').value || '').trim();
      } else if (sel === 'online_day') {
        reason_key = 'online_day';
        reason_text = '';
      } else if (sel === 'end_year') {
        reason_key = 'end_year';
        reason_text = '';
      } else {
        reason_key = document.getElementById('ov_reason_key').value;
        const rtVal2 = (document.getElementById('ov_reason_text').value || '').trim();
        reason_text = (reason_key === 'others') ? rtVal2 : '';
        if ((sel === 'block_all' || sel === 'force_online') && reason_key === 'others') {
          if (!reason_text) {
            showToast('Please enter a reason for Others', 'error');
            return;
          }
        }
      }
      const auto_reschedule = document.getElementById('ov_auto_reschedule').checked;
      const dateLabel = document.getElementById('adminOverrideDate').textContent;
      const start = new Date(dateLabel);
      if (!(start instanceof Date) || isNaN(start.getTime())) {
        alert('Selected date is invalid. Please pick another day.');
        return;
      }

      // Validate end date (end_year)
      let endIso = null; let endLabel = '';
      if (sel === 'end_year') {
        const endVal = document.getElementById('ov_end_date')?.value;
        if (!endVal) { showToast('Please pick an end day', 'error'); return; }
        const end = new Date(endVal + 'T00:00:00');
        if (!(end instanceof Date) || isNaN(end.getTime()) || end < start) {
          showToast('End day must be the same or after start day', 'error'); return;
        }
        endIso = `${end.getFullYear()}-${String(end.getMonth()+1).padStart(2,'0')}-${String(end.getDate()).padStart(2,'0')}`;
        endLabel = end.toDateString();
        effect = 'block_all';
      }

      // Themed confirmation before applying
  const labelMap = { online_day: 'Online Day', force_online: 'Forced Online', block_all: 'Suspension', holiday: 'Holiday', end_year: 'End of School Year' };
      const humanLabel = labelMap[sel] || 'Change';
      const proceed = await themedConfirm(`Apply ${humanLabel}`, sel === 'end_year' ?
        `Disable all classes from <strong>${dateLabel}</strong> to <strong>${endLabel}</strong>?` :
        `Are you sure you want to apply "${humanLabel}" for <strong>${dateLabel}</strong>?`);
      if (!proceed) return;

      const startIso = `${start.getFullYear()}-${String(start.getMonth()+1).padStart(2,'0')}-${String(start.getDate()).padStart(2,'0')}`;
      const payload = {
        start_date: startIso,
        end_date: endIso,
        effect: effect,
        allowed_mode: effect === 'force_mode' ? allowed : null,
        reason_key, reason_text,
        auto_reschedule
      };
      postJson('/api/admin/calendar/overrides/apply', payload).then(data => {
        if (data && data.success) {
          closeOverrideModal();
          // refresh admin calendar data
          if (typeof loadAdminCalendarData === 'function') {
            loadAdminCalendarData();
          }
          // refresh overrides for current month to paint badges immediately
          try {
            const base = getVisibleMonthBaseDate();
            fetchAdminOverridesForMonth(base);
            // Also stamp the just-applied date immediately as a badge (fallback)
            const immediateDateStr = dateLabel; // e.g., Sun Dec 25 2025
            const ovItem = { effect, reason_text: reason_text, reason_key, allowed_mode: allowed };
            addBadgeForDate(immediateDateStr, ovItem);
            // If a range (End Year), seed global cache for all days to avoid delays across months
            if (payload.end_date && reason_key === 'end_year') {
              if (!window.__adminOvGlobalCache) window.__adminOvGlobalCache = {};
              const s = new Date(startIso);
              const e = new Date(payload.end_date);
              for (let d = new Date(s); d <= e; d.setDate(d.getDate()+1)) {
                const iso = `${d.getFullYear()}-${String(d.getMonth()+1).padStart(2,'0')}-${String(d.getDate()).padStart(2,'0')}`;
                window.__adminOvGlobalCache[iso] = [ { effect: 'block_all', reason_key: 'end_year' } ];
              }
              // Persist for instant paint after page reload
              try { localStorage.setItem('ascc_admin_ov_global_v1', JSON.stringify(window.__adminOvGlobalCache)); } catch(_) {}
              // Draw immediately with global seed
              if (window.adminPicker) window.adminPicker.draw();
            }
          } catch(e) {}
          showToast('Changes applied', 'success');
        } else {
          alert('Failed to apply changes');
        }
      }).catch(()=> alert('Failed to apply changes'));

    // Helper: add badge and day-level background directly to a specific date cell (immediate feedback)
    function addBadgeForDate(dateStr, item) {
      const cells = document.querySelectorAll('.pika-button');
      for (const cell of cells) {
        const d = new Date(
          cell.getAttribute('data-pika-year'),
          cell.getAttribute('data-pika-month'),
          cell.getAttribute('data-pika-day')
        );
        if (d.toDateString() === dateStr) {
          // Remove any existing badge and override day classes
          const old = cell.querySelector('.ov-badge');
          if (old) old.remove();
          cell.classList.remove('day-holiday','day-blocked','day-force','day-online','day-endyear');

          // Create new badge
          const badge = document.createElement('span');
          const isOnline = (item.effect !== 'holiday' && item.effect !== 'block_all' && item.reason_key === 'online_day');
          const isEndYear = (item.effect === 'block_all' && item.reason_key === 'end_year');
          const cls = item.effect === 'holiday' ? 'ov-holiday' : (item.effect === 'block_all' ? (isEndYear ? 'ov-endyear' : 'ov-blocked') : (isOnline ? 'ov-online' : 'ov-force'));
          badge.className = 'ov-badge ' + cls;
            const text = item.effect === 'holiday'
            ? (item.reason_text || 'Holiday')
            : (item.effect === 'block_all'
              ? (item.reason_key === 'end_year' ? 'End Year' : 'Suspension')
              : (item.reason_key === 'online_day' ? 'Online Day' : 'Forced Online'));
          badge.textContent = text;
          badge.title = text;

          // Apply cell-level background to make it visually obvious immediately
          const dayCls = item.effect === 'holiday' ? 'day-holiday' : (item.effect === 'block_all' ? (isEndYear ? 'day-endyear' : 'day-blocked') : (isOnline ? 'day-online' : 'day-force'));
          cell.classList.add(dayCls);

          cell.style.position = 'relative';
          cell.appendChild(badge);
          break;
        }
      }
    }

    // Helper: clear all temporary override badges and background classes on the visible calendar
    function resetCalendarHighlights() {
      try {
        const cells = document.querySelectorAll('.pika-table .pika-button');
        cells.forEach(cell => {
          // Remove any override badge
          const b = cell.querySelector('.ov-badge');
          if (b) b.remove();
          // Remove background classes
          cell.classList.remove('day-holiday','day-blocked','day-force','day-online','day-endyear');
        });
        // Clear any selected cells
        document.querySelectorAll('.pika-table td.is-selected').forEach(td => td.classList.remove('is-selected'));
        // Hide tooltip if visible
        const tooltip = document.getElementById('consultationTooltip');
        if (tooltip) tooltip.style.display = 'none';
        // Soft reset flag: do not change window.adminOverrides so persisted server overrides will return on next redraw
        console.log('Admin calendar highlights reset');
      } catch (e) {
        console.warn('Reset calendar highlights encountered an issue:', e);
      }
    }
    });

    // ADMIN NOTIFICATION FUNCTIONS
    // Mark all as read functionality
    (function(){
      const markAllBtn = document.getElementById('mark-all-read');
      if (markAllBtn) {
        markAllBtn.addEventListener('click', function() {
          markAllAdminNotificationsAsRead();
        });
      }
    })();

    // Remove overrides for selected date
    (function(){
      const btn = document.getElementById('ovRemoveBtn');
      if (!btn) return;
      btn.addEventListener('click', function(){
        const dateLabel = document.getElementById('adminOverrideDate').textContent;
        const start = new Date(dateLabel);
        if (!(start instanceof Date) || isNaN(start.getTime())) {
          alert('Selected date is invalid.');
          return;
        }

        // If there is no override for this date, do not proceed
        try {
          const exists = (function(){
            const iso = `${start.getFullYear()}-${String(start.getMonth()+1).padStart(2,'0')}-${String(start.getDate()).padStart(2,'0')}`;
            if (window.adminOverrides && window.adminOverrides[iso] && window.adminOverrides[iso].length > 0) return true;
            const cells = document.querySelectorAll('.pika-button');
            for (const cell of cells) {
              const d = new Date(
                cell.getAttribute('data-pika-year'),
                cell.getAttribute('data-pika-month'),
                cell.getAttribute('data-pika-day')
              );
              if (d.toDateString() === dateLabel) {
                if (cell.querySelector('.ov-badge')) return true;
                if (
                  cell.classList.contains('day-holiday') ||
                  cell.classList.contains('day-blocked') ||
                  cell.classList.contains('day-force') ||
                  cell.classList.contains('day-endyear')
                ) return true;
                break;
              }
            }
            return false;
          })();
          if (!exists) {
            showToast('No override on this date to remove', 'info');
            // keep button disabled to reflect state
            btn.disabled = true; btn.setAttribute('aria-disabled','true'); btn.style.opacity = '0.5'; btn.style.cursor = 'not-allowed';
            return;
          }
        } catch (e) { /* ignore and continue */ }

        // Themed confirmation before removing
        themedConfirm('Remove Override', `Are you sure you want to remove overrides for <strong>${dateLabel}</strong>?`).then(ok => {
          if (!ok) return;

        const startIso = `${start.getFullYear()}-${String(start.getMonth()+1).padStart(2,'0')}-${String(start.getDate()).padStart(2,'0')}`;
        postJson('/api/admin/calendar/overrides/remove', { start_date: startIso })
          .then(data => {
            if (data && data.success) {
              // Clear badge/background for that date immediately
              const cells = document.querySelectorAll('.pika-button');
              for (const cell of cells) {
                const d = new Date(
                  cell.getAttribute('data-pika-year'),
                  cell.getAttribute('data-pika-month'),
                  cell.getAttribute('data-pika-day')
                );
                if (d.toDateString() === dateLabel) {
                  const old = cell.querySelector('.ov-badge');
                  if (old) old.remove();
                  cell.classList.remove('day-holiday','day-blocked','day-force','day-online','day-endyear');
                  break;
                }
              }
              // Refresh month overrides
              const base = getVisibleMonthBaseDate();
              fetchAdminOverridesForMonth(base);
              // Close modal
              closeOverrideModal();
              showToast(data.deleted > 0 ? 'Override removed' : 'No override found', data.deleted > 0 ? 'success' : 'info');
              // After removal, ensure Remove stays disabled for this date
              try {
                const removeBtn = document.getElementById('ovRemoveBtn');
                if (removeBtn) { removeBtn.disabled = true; removeBtn.setAttribute('aria-disabled','true'); removeBtn.style.opacity = '0.5'; removeBtn.style.cursor = 'not-allowed'; }
              } catch(e) {}
            } else {
              alert('Failed to remove override');
            }
          })
          .catch(()=> alert('Failed to remove override'));
        });
      });
    })();
    // Themed toast + confirm helpers (aligned with site theme)
    function ensureToastWrapper() {
      let wrap = document.querySelector('.toast-wrapper');
      if (!wrap) {
        wrap = document.createElement('div');
        wrap.className = 'toast-wrapper';
        document.body.appendChild(wrap);
      }
      return wrap;
    }

    // Always spawn a new toast; no dedupe or stacking limits
    function showToast(message, type='info', timeout=2200) {
      const wrap = ensureToastWrapper();
      const toast = document.createElement('div');
      toast.className = `ascc-toast ${type==='success'?'ascc-toast-success': type==='error'?'ascc-toast-error':'ascc-toast-info'}`;
      toast.setAttribute('role', 'status');
      toast.setAttribute('aria-live', 'polite');
      toast.innerHTML = `<div>${message}</div><button class=\"ascc-toast-close\" aria-label=\"Close\">×</button>`;
      wrap.appendChild(toast);
      const closer = toast.querySelector('.ascc-toast-close');
      const onClose = () => safeHideToast(toast);
      closer.addEventListener('click', onClose);
      setTimeout(onClose, timeout);
    }

    function safeHideToast(el) {
      if (!el) return;
      el.classList.add('hide');
      setTimeout(() => { if (el && el.parentNode) el.parentNode.removeChild(el); }, 250);
    }

    function themedConfirm(title, htmlMessage) {
      return new Promise(resolve => {
        const overlay = document.createElement('div');
        overlay.className = 'ascc-confirm-overlay';
        const dlg = document.createElement('div');
        dlg.className = 'ascc-confirm';
        dlg.setAttribute('role', 'dialog');
        dlg.setAttribute('aria-modal', 'true');
        dlg.innerHTML = `
          <div class="ascc-confirm-header">
            <div class="ascc-confirm-title">${title}</div>
            <button class="ascc-confirm-close" aria-label="Close">×</button>
          </div>
          <div class="ascc-confirm-body">${htmlMessage}</div>
          <div class="ascc-confirm-actions">
            <button id="dlgCancel" class="ascc-btn ascc-btn-secondary">Cancel</button>
            <button id="dlgOk" class="ascc-btn ascc-btn-primary">Confirm</button>
          </div>
        `;
        overlay.appendChild(dlg);
        document.body.appendChild(overlay);

        const okBtn = dlg.querySelector('#dlgOk');
        const cancelBtn = dlg.querySelector('#dlgCancel');
        const closeBtn = dlg.querySelector('.ascc-confirm-close');

        const cleanup = () => {
          document.removeEventListener('keydown', onKey);
          overlay.remove();
        };
        const close = (val) => { cleanup(); resolve(val); };

        const onKey = (e) => {
          if (e.key === 'Escape') { e.preventDefault(); close(false); }
          if (e.key === 'Tab') {
            // basic focus trap
            const focusables = dlg.querySelectorAll('button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])');
            if (focusables.length) {
              const first = focusables[0];
              const last = focusables[focusables.length - 1];
              if (e.shiftKey && document.activeElement === first) { last.focus(); e.preventDefault(); }
              else if (!e.shiftKey && document.activeElement === last) { first.focus(); e.preventDefault(); }
            }
          }
        };

        closeBtn.addEventListener('click', () => close(false));
        cancelBtn.addEventListener('click', () => close(false));
        okBtn.addEventListener('click', () => close(true));
        document.addEventListener('keydown', onKey);
        // Initial focus
        okBtn.focus();
      });
    }

    // (Reset button removed as requested)

  function loadAdminCalendarData() {
      fetch('/api/admin/all-consultations', {
        method: 'GET',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
          'Accept': 'application/json'
        }
      })
        .then(response => {
          console.log('Admin Real-time API Response status:', response.status);
          if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
          }
          return response.json();
        })
        .then(data => {
          console.log('Admin Real-time update - fetched data:', data.length, 'entries');
          // Ensure overrides for this month are loaded too
          const base = getVisibleMonthBaseDate();
          fetchAdminOverridesForMonth(base);
          
          // Store previous booking map for comparison
          const previousBookings = new Map();
          bookingMap.forEach((value, key) => {
            previousBookings.set(key, value);
          });
          
          bookingMap.clear(); // Clear existing data
          detailsMap.clear(); // Clear details data
          
          data.forEach(entry => {
            // Exclude cancelled from admin tooltip/booking maps
            if ((entry.Status || '').toLowerCase() === 'cancelled') return;
            const date = new Date(entry.Booking_Date);
            const key = date.toDateString();
            // For status coloring and modal
            bookingMap.set(key, { status: entry.Status.toLowerCase(), id: entry.Booking_ID });
            // For hover tooltip details
            if (!detailsMap.has(key)) detailsMap.set(key, []);
            detailsMap.get(key).push(entry);
          });

          // Only update calendar if there are actual changes
          let hasChanges = false;
          
          // Check for new or changed bookings
          for (const [dateStr, booking] of bookingMap) {
            const previousBooking = previousBookings.get(dateStr);
            if (!previousBooking || previousBooking.status !== booking.status || previousBooking.id !== booking.id) {
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
          if (hasChanges && window.adminPicker) {
            const cells = document.querySelectorAll('.pika-button');
            cells.forEach(cell => {
              const cellDate = new Date(cell.getAttribute('data-pika-year'), cell.getAttribute('data-pika-month'), cell.getAttribute('data-pika-day'));
              const dateStr = cellDate.toDateString();
              const isoKey = `${cellDate.getFullYear()}-${String(cellDate.getMonth()+1).padStart(2,'0')}-${String(cellDate.getDate()).padStart(2,'0')}`;
              // Refresh override badges/classes on update, using per-month sticky cache to avoid flicker
              const visibleBaseNow = (function(){
                try { return getVisibleMonthBaseDate(); } catch(_) { const t=new Date(); return new Date(t.getFullYear(), t.getMonth(), 1); }
              })();
              const visKeyNow = `${visibleBaseNow.getFullYear()}-${String(visibleBaseNow.getMonth()+1).padStart(2,'0')}`;
              const ovSource = (function(){
                if (window.adminOverrides && typeof window.adminOverrides === 'object') return window.adminOverrides;
                if (window.__adminOvCacheByMonth && window.__adminOvCacheByMonth[visKeyNow]) return window.__adminOvCacheByMonth[visKeyNow];
                if (window.__adminOvCache && typeof window.__adminOvCache === 'object') return window.__adminOvCache;
                if (window.__adminOvGlobalCache && typeof window.__adminOvGlobalCache === 'object') return window.__adminOvGlobalCache;
                return null;
              })();
              if (ovSource) {
                const oldBadge = cell.querySelector('.ov-badge');
                if (oldBadge) oldBadge.remove();
                cell.classList.remove('day-holiday','day-blocked','day-force','day-online','day-endyear');
              }
              if (ovSource && ovSource[isoKey] && ovSource[isoKey].length > 0) {
                const items = ovSource[isoKey];
                let chosen = null;
                for (const ov of items) { if (ov.effect === 'holiday') { chosen = ov; break; } }
                if (!chosen) { for (const ov of items) { if (ov.effect === 'block_all') { chosen = ov; break; } } }
                if (!chosen) { chosen = items[0]; }
                const badge = document.createElement('span');
                // Distinguish Online Day vs Forced Online for clarity
                const isOnlineDay = (chosen.effect === 'force_mode' && (chosen.reason_key === 'online_day'));
                const isEndYear = (chosen.effect === 'block_all' && (chosen.reason_key === 'end_year'));
                const chosenCls = (chosen.effect === 'holiday'
                  ? 'ov-holiday'
                  : (chosen.effect === 'block_all'
                    ? (isEndYear ? 'ov-endyear' : 'ov-blocked')
                    : (isOnlineDay ? 'ov-online' : 'ov-force')));
                badge.className = 'ov-badge ' + chosenCls;
                const forceLabel2 = isOnlineDay ? 'Online Day' : 'Forced Online';
                const labelTxt2 = (chosen.effect === 'holiday')
                  ? (chosen.reason_text || 'Holiday')
                  : (chosen.effect === 'block_all' ? (isEndYear ? 'End Year' : 'Suspension') : forceLabel2);
                badge.title = chosen.label || chosen.reason_text || labelTxt2;
                badge.textContent = labelTxt2;
                cell.style.position = 'relative';
                cell.appendChild(badge);
                const dayCls = (chosen.effect === 'holiday'
                  ? 'day-holiday'
                  : (chosen.effect === 'block_all'
                    ? (isEndYear ? 'day-endyear' : 'day-blocked')
                    : (isOnlineDay ? 'day-online' : 'day-force')));
                cell.classList.add(dayCls);
              }
              const booking = bookingMap.get(dateStr);
              const previousBooking = previousBookings.get(dateStr);
              
              // Only update if status changed for this specific date
              if (!previousBooking && booking || 
                  previousBooking && !booking ||
                  (previousBooking && booking && previousBooking.status !== booking.status)) {
                
                // Remove existing status classes and multiple booking classes
                cell.classList.remove('status-pending', 'status-approved', 'status-completed', 'status-rescheduled');
                cell.classList.remove('has-multiple-bookings');
                
                // Clear any existing event listeners by cloning the element
                const newCell = cell.cloneNode(true);
                cell.parentNode.replaceChild(newCell, cell);
                
                if (booking) {
                  newCell.classList.add(`status-${booking.status}`);
                  
                  // Get the number of consultations for this date and add appropriate classes
                  const consultationsForDay = detailsMap.get(dateStr) || [];
                  const consultationCount = consultationsForDay.length;
                  
                  if (consultationCount >= 2) {
                    newCell.classList.add('has-multiple-bookings');
                  }
                  
                  // Store consultation count for tooltip or other uses
                  newCell.setAttribute('data-consultation-count', consultationCount);
                  
                  // Use data attributes for global event delegation (Pikaday-compatible)
                  const key = dateStr;
                  newCell.setAttribute('data-consultation-key', key);
                  newCell.setAttribute('data-has-consultations', 'true');
                  
                  console.log('Admin Updated cell with global hover data:', key, 'Consultations:', consultationCount);
                }
              }
            });
            // If current hovered cell now has no consultations, hide tooltip
            try {
              const tooltip = document.getElementById('consultationTooltip');
              if (tooltip && currentHoveredCell) {
                const c = currentHoveredCell;
                const d = new Date(c.getAttribute('data-pika-year'), c.getAttribute('data-pika-month'), c.getAttribute('data-pika-day'));
                const key = d.toDateString();
                const list = detailsMap.get(key) || [];
                if (list.length === 0) {
                  tooltip.style.display = 'none';
                  currentHoveredCell = null;
                }
              }
            } catch(_) {}
          }
        })
        .catch(error => {
          console.error('Admin Error loading calendar data:', error);
        });
    }

    let adminNotificationsHash = '';

    function loadAdminNotifications() {
      fetch('/api/admin/notifications')
        .then(response => response.json())
        .then(data => {
          // Create a hash of the notifications to detect changes
          const notificationsString = JSON.stringify(data.notifications);
          const currentHash = btoa(notificationsString);
          
          // Only update if notifications have changed
          if (currentHash !== adminNotificationsHash) {
            adminNotificationsHash = currentHash;
            displayAdminNotifications(data.notifications);
            updateAdminUnreadCount();
          }
        })
        .catch(error => {
          console.error('Error loading admin notifications:', error);
        });
    }

    function displayAdminNotifications(notifications) {
      const container = document.getElementById('notifications-container');
      const mobileContainer = document.getElementById('mobileNotificationsContainer');
      
      if (notifications.length === 0) {
        const noNotificationsHtml = `
          <div class="no-notifications">
            🔔
            <p>No notifications yet</p>
          </div>
        `;
        container.innerHTML = noNotificationsHtml;
        if (mobileContainer) {
          mobileContainer.innerHTML = noNotificationsHtml;
        }
        return;
      }
      
      const notificationsHtml = notifications.map(notification => {
  const timeTs = notification.created_at;
        const unreadClass = notification.is_read ? '' : 'unread';
        
        return `
          <div class="notification-item ${unreadClass}" onclick="showConsultationDetails(${notification.id}, ${notification.booking_id})">
            <div class="notification-type ${notification.type}">${notification.type.replace('_', ' ')}</div>
            <div class="notification-title">${notification.title}</div>
            <div class="notification-message">${notification.message}</div>
            <div class="notification-time" data-timeago data-ts="${timeTs}"></div>
          </div>
        `;
      }).join('');
      
      container.innerHTML = notificationsHtml;
      if (mobileContainer) {
        mobileContainer.innerHTML = notificationsHtml;
      }
    }

    function markAdminNotificationAsRead(notificationId) {
      fetch('/api/admin/notifications/mark-read', {
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
          adminNotificationsHash = '';
          loadAdminNotifications(); // Reload to update read status
        }
      })
      .catch(error => {
        console.error('Error marking admin notification as read:', error);
      });
    }

    function markAllAdminNotificationsAsRead() {
      fetch('/api/admin/notifications/mark-all-read', {
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
          adminNotificationsHash = '';
          loadAdminNotifications(); // Reload to update read status
          updateAdminUnreadCount();
        }
      })
      .catch(error => {
        console.error('Error marking all admin notifications as read:', error);
      });
    }

    function updateAdminUnreadCount() {
      const unreadCount = document.querySelectorAll('#notifications-container .notification-item.unread').length;
      const badge = document.getElementById('unread-count');
      if (badge) {
        badge.textContent = unreadCount;
        badge.style.display = unreadCount > 0 ? 'inline' : 'none';
      }
    }

    function getStatusClassName(status) {
      const classes = {
        pending: 'tooltip-status--pending',
        approved: 'tooltip-status--approved',
        completed: 'tooltip-status--completed',
        rescheduled: 'tooltip-status--rescheduled',
      };
      const key = (status || '').toString().toLowerCase();
      return classes[key] || 'tooltip-status--default';
    }

    // Live timeago handled by public/js/timeago.js

    // Modal functions for consultation details
    function showConsultationDetails(notificationId, bookingId) {
      // Mark notification as read
      markAdminNotificationAsRead(notificationId);
      
      // Show modal
      const modal = document.getElementById('consultationModal');
      const modalBody = document.getElementById('modalConsultationDetails');

      if (modal) {
        modal.classList.remove('hidden');
      }
      modalBody.innerHTML = '<div class="loading">Loading consultation details...</div>';
      
      // Fetch consultation details
      if (bookingId) {
        fetchConsultationDetails(bookingId);
      } else {
        modalBody.innerHTML = '<div class="error">No booking information available for this notification.</div>';
      }
    }

    function closeConsultationModal() {
      const modal = document.getElementById('consultationModal');
      if (modal) {
        modal.classList.add('hidden');
      }
    }

    function fetchConsultationDetails(bookingId) {
      fetch(`/api/admin/consultation-details/${bookingId}`, {
        method: 'GET',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
          'Accept': 'application/json'
        }
      })
      .then(response => {
        if (!response.ok) {
          throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.json();
      })
      .then(consultation => {
        displayConsultationDetails(consultation);
      })
      .catch(error => {
        console.error('Error fetching consultation details:', error);
        document.getElementById('modalConsultationDetails').innerHTML = 
          '<div class="error">Failed to load consultation details. Please try again.</div>';
      });
    }

    function displayConsultationDetails(consultation) {
      const modalBody = document.getElementById('modalConsultationDetails');
      
      const html = `
        <div class="consultation-detail-card">
          <h4>Consultation Information</h4>
          
          <div class="detail-row">
            <span class="detail-label">Student:</span>
            <span class="detail-value"><strong>${consultation.student_name || 'N/A'}</strong></span>
          </div>
          
          <div class="detail-row">
            <span class="detail-label">Professor:</span>
            <span class="detail-value"><strong>${consultation.professor_name || 'N/A'}</strong></span>
          </div>
          
          <div class="detail-row">
            <span class="detail-label">Subject:</span>
            <span class="detail-value">${consultation.subject || 'N/A'}</span>
          </div>
          
          <div class="detail-row">
            <span class="detail-label">Consultation Type:</span>
            <span class="detail-value">${consultation.type || 'N/A'}</span>
          </div>
          
          <div class="detail-row">
            <span class="detail-label">Date:</span>
            <span class="detail-value">${consultation.booking_date || 'N/A'}</span>
          </div>
          
          <div class="detail-row">
            <span class="detail-label">Mode:</span>
            <span class="detail-value">${consultation.mode || 'N/A'}</span>
          </div>
          
          <div class="detail-row">
            <span class="detail-label">Status:</span>
            <span class="detail-value">
              <span class="status-badge status-${consultation.status ? consultation.status.toLowerCase() : 'unknown'}">
                ${consultation.status || 'Unknown'}
              </span>
            </span>
          </div>
          
          <div class="detail-row">
            <span class="detail-label">Booking ID:</span>
            <span class="detail-value">#${consultation.booking_id || 'N/A'}</span>
          </div>
          
          <div class="detail-row">
            <span class="detail-label">Created:</span>
            <span class="detail-value">${consultation.created_at ? new Date(consultation.created_at).toLocaleString() : 'N/A'}</span>
          </div>
        </div>
      `;
      
      modalBody.innerHTML = html;
    }

    // Close modal when clicking outside
    window.onclick = function(event) {
      const modal = document.getElementById('consultationModal');
      if (event.target === modal) {
        closeConsultationModal();
      }
    }

    // Initialize admin notifications
    loadAdminNotifications();

    // Initialize calendar data refresh
    loadAdminCalendarData();

    // Legend panel interactions
    (function legendPanelInit(){
      const btn = document.getElementById('legendToggle');
      const backdrop = document.getElementById('legendBackdrop');
      const closeBtn = document.getElementById('legendClose');
      if(!btn || !backdrop) return;
      const open = () => { backdrop.classList.add('open'); backdrop.setAttribute('aria-hidden','false'); };
      const close = () => { backdrop.classList.remove('open'); backdrop.setAttribute('aria-hidden','true'); };
      btn.addEventListener('click', open);
      closeBtn && closeBtn.addEventListener('click', close);
      backdrop.addEventListener('click', (e)=>{ if(e.target === backdrop) close(); });
      document.addEventListener('keydown', (e)=>{ if(e.key === 'Escape') close(); });
    })();

    // Real-time load notifications every 3 seconds (reduced for smoother updates)
    setInterval(loadAdminNotifications, 3000);

    // Real-time refresh calendar data every 3 seconds (reduced for smoother updates)
    setInterval(loadAdminCalendarData, 3000);
  </script>
  <script src="{{ asset('js/timeago.js') }}"></script>
</body>
</html>