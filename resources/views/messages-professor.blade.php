<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Messages - Professor</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
  <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
  <link rel="stylesheet" href="{{ asset('css/messages-professor.css') }}">
  <link rel="stylesheet" href="{{ asset('css/chat-shared.css') }}">
  <link rel="stylesheet" href="{{ asset('css/logout-confirm.css') }}">
  <script src="{{ asset('js/logout-confirm.js') }}" defer></script>
</head>
<body class="messages-page">
  @include('components.navbarprof')
  @php $todaySchedules = $todaySchedules ?? []; @endphp

  <div class="main-content">
  <!-- Messaging Area -->
  <div class="messages-wrapper">
    <!-- Inbox -->
    <div class="inbox">
  <div class="inbox-header-line">
    <h2>Students</h2>
    <div class="class-call-control">
      <button type="button" class="class-call-btn is-disabled" id="start-class-call" disabled aria-expanded="false">Start class call</button>
      <div class="class-call-menu" id="class-call-menu" role="menu"></div>
    </div>
  </div>
      @foreach($students as $student)
        @php
          $picUrl = $student->profile_photo_url ?? asset('images/dprof.jpg');
          $rawLast = $student->last_message ?? '';
          $isFileOnly = $rawLast === '' && $student->last_message_time;
            $lastMessage = $isFileOnly ? '[File]' : ($rawLast ?: 'No messages yet');
          $youPrefix = isset($student->last_sender) && $student->last_sender === 'professor' ? 'You: ' : '';
          $displayMessage = $youPrefix . $lastMessage;
          $relTime = $student->last_message_time ? \Carbon\Carbon::parse($student->last_message_time)->timezone('Asia/Manila')->diffForHumans(['short'=>true]) : '';
        @endphp
  <div class="inbox-item" data-stud-id="{{ $student->stud_id }}" data-can-video="{{ isset($student->can_video_call) && $student->can_video_call ? '1':'0' }}" data-channel="{{ e($student->meeting_link ?? '') }}" data-schedule-channel="{{ e($student->schedule_channel ?? '') }}" onclick="loadChat('{{ $student->name }}', {{ $student->stud_id }})">
          <img class="inbox-avatar" src="{{ $picUrl }}" alt="{{ $student->name }}">
          <div class="inbox-meta">
            <div class="name"><span class="presence-dot" data-presence="stud-{{ $student->stud_id }}"></span>{{ $student->name }} <span class="unread-badge hidden" data-unread="stud-{{ $student->stud_id }}"></span></div>
            <div class="snippet-line">
              @if($student->last_message_time)
                <span class="snippet" title="{{ $displayMessage }}">{!! isset($student->last_sender) && $student->last_sender==='professor' ? '<strong>You:</strong> ' : '' !!}{{ \Illuminate\Support\Str::limit($lastMessage, 36) }}</span>
              @else
                <span class="snippet" title="No conversation yet">No conversation yet</span>
              @endif
              @if($relTime)<span class="rel-time">{{ $relTime }}</span>@endif
            </div>
          </div>
        </div>
      @endforeach
    </div>

    <!-- Chat Panel -->
    <div class="chat-panel" id="chat-panel">
      <div class="chat-header">
        <button class="back-btn" id="back-btn" style="display:none;"><i class='bx bx-arrow-back'></i></button>
        <span id="chat-person">Select a student</span>
        <span id="typing-indicator" class="typing-indicator" style="display:none;">Typing...</span>
  <button class="video-btn is-blocked" id="video-call-btn" onclick="startVideoCall()" disabled>Video Call</button>
        
      </div>
      <div class="chat-body" id="chat-body">
        @if(count($students) === 0)
          <div class="message">No students found.</div>
        @endif
        <!-- Messages will be dynamically loaded here -->
      </div>
      <div class="chat-input" id="chat-input">
        <div id="file-preview-container" class="file-preview-container"></div>
        <div class="chat-input-main">
          <label for="file-input" id="attach-btn" class="attach-btn" title="Upload file">
              <i class='bx bx-paperclip'></i>
          </label>
          <textarea id="message-input" placeholder="Type a message..." rows="1" maxlength="5000" disabled></textarea>
          <button id="send-btn" onclick="sendMessage()" disabled>Send</button>
        </div>
        <input type="file" id="file-input" multiple style="display:none;" accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx" disabled />
        <input type="hidden" id="last-send-ts" value="0" />
      </div>
    </div>
  </div>
</div>
  <script>window.csrfToken='{{ csrf_token() }}'; window.PROF_SCHEDULES = @json($todaySchedules);</script>
  <script src="{{ asset('js/chat-common.js') }}"></script>
  <script src="https://js.pusher.com/7.0/pusher.min.js"></script>
  <script>
  // Themed toast helper (professor side) for consistent UI feedback
  function showToast(message, variant = 'error', timeout = 2800){
    let root = document.getElementById('toast-root');
    if(!root){
      root = document.createElement('div');
      root.id = 'toast-root';
      root.className = 'toast-root';
      document.body.appendChild(root);
    }
    const existing = Array.from(root.querySelectorAll('.toast')).find(el => el.dataset.msg === message);
    if(existing){
      clearTimeout(existing._hideTimer);
      existing.style.animation = 'toast-in 180ms ease-out forwards';
      existing._hideTimer = setTimeout(()=>{
        existing.style.animation = 'toast-out 160ms ease-in forwards';
        setTimeout(()=>{ existing.remove(); }, 190);
      }, timeout);
      return;
    }
    const t = document.createElement('div');
    t.className = 'toast ' + (variant === 'error' ? 'toast--error' : 'toast--ok');
    t.setAttribute('data-msg', message);
    t.innerHTML = "<i class='bx bxs-info-circle toast__icon'></i><div class='toast__text'></div>";
    t.querySelector('.toast__text').textContent = message;
    root.appendChild(t);
    t._hideTimer = setTimeout(()=>{
      t.style.animation = 'toast-out 160ms ease-in forwards';
      setTimeout(()=>{ t.remove(); }, 190);
    }, timeout);
  }
  let currentChatPerson = '';
  let currentStudentId = null; // direct messaging target
  const currentProfId = {{ auth()->guard('professor')->user()->Prof_ID ?? 0 }};
  const todaySchedules = Array.isArray(window.PROF_SCHEDULES) ? window.PROF_SCHEDULES : [];
  let classCallMenuOpen = false;

  function disableChatInputsUntilSelection(){
    const msgInput = document.getElementById('message-input');
    const sendBtn = document.getElementById('send-btn');
    if(msgInput){ msgInput.disabled = true; }
    if(sendBtn){ sendBtn.disabled = true; }
    setAttachmentEnabled(false);
  }

  function initClassCallControl(){
    const btn = document.getElementById('start-class-call');
    const menu = document.getElementById('class-call-menu');
    if(!btn || !menu) return;
    if(!todaySchedules.length){
      btn.disabled = true;
      btn.classList.add('is-disabled');
      btn.title = 'No approved schedules for today.';
      menu.innerHTML = '<div class="class-call-menu-empty">No schedules for today</div>';
      return;
    }
    btn.disabled = false;
    btn.classList.remove('is-disabled');
    btn.removeAttribute('title');
    btn.setAttribute('aria-expanded', 'false');
    menu.innerHTML = '';
    todaySchedules.forEach(function(schedule){
      const item = document.createElement('button');
      item.type = 'button';
      item.className = 'class-call-menu-item';
      item.setAttribute('role','menuitem');
      item.dataset.channel = schedule.channel;
      item.textContent = schedule.label;
      item.addEventListener('click', function(ev){
        ev.stopPropagation();
        closeClassCallMenu();
        launchScheduleCall(schedule.channel);
      });
      menu.appendChild(item);
    });
    if(todaySchedules.length === 1){
      btn.addEventListener('click', function(){
        launchScheduleCall(todaySchedules[0].channel);
      });
      classCallMenuOpen = false;
      menu.classList.remove('is-open');
      menu.style.display = 'none';
    } else {
      btn.addEventListener('click', function(ev){
        ev.stopPropagation();
        toggleClassCallMenu();
      });
      document.addEventListener('click', function(){
        if(classCallMenuOpen){ closeClassCallMenu(); }
      });
    }
  }

  function toggleClassCallMenu(){
    const btn = document.getElementById('start-class-call');
    const menu = document.getElementById('class-call-menu');
    if(!btn || !menu) return;
    classCallMenuOpen = !classCallMenuOpen;
    menu.classList.toggle('is-open', classCallMenuOpen);
    btn.setAttribute('aria-expanded', classCallMenuOpen ? 'true' : 'false');
  }

  function closeClassCallMenu(){
    const btn = document.getElementById('start-class-call');
    const menu = document.getElementById('class-call-menu');
    if(!btn || !menu) return;
    classCallMenuOpen = false;
    menu.classList.remove('is-open');
    btn.setAttribute('aria-expanded', 'false');
  }

  function launchScheduleCall(channel){
    closeClassCallMenu();
    if(!channel){
      showToast('Missing schedule channel for class call.', 'error');
      return;
    }
    window.location.href = `/prof-call/${encodeURIComponent(channel)}`;
  }

  // Helper: enable/disable attachment control based on chat selection
  function setAttachmentEnabled(enabled){
    const label = document.getElementById('attach-btn');
    const input = document.getElementById('file-input');
    if(!label || !input) return;
    if(enabled){
      label.style.pointerEvents='';
      label.style.opacity='';
      input.disabled = false;
    } else {
      label.style.pointerEvents='none';
      label.style.opacity='0.45';
      input.disabled = true;
      // clear any selected (optimistic) files if user navigated away
      if(window.selectedFiles && window.selectedFiles.length){
        window.selectedFiles = []; renderFilePreviews && renderFilePreviews();
      }
    }
  }

  ChatCommon.initPusher('00e7e382ce019a1fa987','ap1', null, currentProfId);
  // Real-time presence updates
  const presenceLast = {}; // key => timestamp ms
  ChatCommon.onPresence(function(data){
    const key = (data.role === 'student' ? 'stud-' : 'prof-') + data.id;
    presenceLast[key] = Date.now();
    const dot = document.querySelector(`[data-presence="${key}"]`);
    if(dot){ dot.classList.add('online'); }
  });
  window.CHAT_LATENCY_LOG = true;
  ChatCommon.onMessage(function(data){
    const openPair = currentStudentId && parseInt(data.prof_id)===parseInt(currentProfId) && parseInt(data.stud_id)===parseInt(currentStudentId);
    if(openPair){
      if(data.sender === 'professor' && parseInt(data.prof_id)===parseInt(currentProfId)){
        // reconcile
        if(data.client_uuid){
          const pendingEl = document.querySelector(`.message.sent.pending[data-client-uuid="${data.client_uuid}"]`);
          if(pendingEl){
            pendingEl.classList.remove('pending'); pendingEl.style.opacity='1';
            pendingEl.title = new Date(data.created_at_iso).toLocaleTimeString('en-PH',{hour:'2-digit',minute:'2-digit'});
            // delivered check removed
            if(window.CHAT_LATENCY_LOG && pendingMap[data.client_uuid]){ console.log('[ChatLatency ms]', Date.now()-pendingMap[data.client_uuid].t); }
            // keep status under newest bubble
            return;
          }
        }
        return; // already appended without uuid match
      }
  appendMessageToChat(data.sender === 'professor' ? 'sent':'received', data.message, data.file, data.file_type, data.original_name, data.created_at_iso);
  if(data.sender==='student'){
    // Keep delivered avatar; only remove typing bubble when student sends a message
    removeTypingBubbleProf();
    // (Do NOT remove status; avatar persists until professor sends next message)
  }
      if(data.sender === 'student'){
        markCurrentPairReadProf();
      }
    } else if(data.sender === 'student' && parseInt(data.prof_id)===parseInt(currentProfId)) {
      // Ensure inbox item exists for this student; if not, fetch summary and insert
      let inboxItem = document.querySelector(`.inbox-item[data-stud-id="${data.stud_id}"]`);
      if(!inboxItem){
        fetch(`/chat/student-summary/${data.stud_id}`)
          .then(r=>r.ok?r.json():null)
          .then(info=>{
            if(!info) return;
            const avatar = info.profile_photo_url || `{{ asset('images/dprof.jpg') }}`;
            const wrapper = document.querySelector('.inbox');
            const el = document.createElement('div');
            el.className='inbox-item';
            el.setAttribute('data-stud-id', String(info.stud_id));
            el.onclick = function(){ loadChat(info.name, info.stud_id); };
            el.innerHTML = `
              <img class="inbox-avatar" src="${avatar}" alt="${info.name}">
              <div class="inbox-meta">
                <div class="name"><span class="presence-dot" data-presence="stud-${info.stud_id}"></span>${info.name} <span class="unread-badge hidden" data-unread="stud-${info.stud_id}"></span></div>
                <div class="snippet-line">
                  <span class="snippet" title="New message">New message</span>
                  <span class="rel-time">now</span>
                </div>
              </div>`;
            el.setAttribute('data-can-video','0');
            el.setAttribute('data-channel', info.meeting_link || '');
            el.setAttribute('data-schedule-channel', info.schedule_channel || '');
            // Insert at top after the header (h2)
            const afterHeader = wrapper.querySelector('h2')?.nextElementSibling;
            if(afterHeader){ wrapper.insertBefore(el, afterHeader); } else { wrapper.appendChild(el); }
            // Bind mobile show panel on click
            el.addEventListener('click', showChatPanel);
            // Presence reflect immediately if available
            const dot = el.querySelector('[data-presence]');
            if(dot){
              const dataPresence = dot.getAttribute('data-presence');
              const id = dataPresence.split('-')[1];
              if(ChatCommon.state.onlineStudents.has(parseInt(id))){ dot.classList.add('online'); }
            }
            // Set badge after creating element
            const b = el.querySelector(`[data-unread="stud-${info.stud_id}"]`);
            if(b){ b.textContent='1'; b.classList.remove('hidden'); }
          })
          .catch(()=>{});
      } else {
        // Update unread badge
        const badge = document.querySelector(`[data-unread=\"stud-${data.stud_id}\"]`);
        if(badge){ let v=parseInt(badge.textContent||'0')+1; badge.textContent=v; badge.classList.remove('hidden'); }
        // Update snippet and time for the existing inbox item
        const meta = inboxItem.querySelector('.snippet-line .snippet');
        const rel = inboxItem.querySelector('.snippet-line .rel-time');
        const isFile = data.file && (!data.message || data.message === '');
        const lastText = isFile ? '[File]' : (data.message || 'New message');
        if(meta){ meta.textContent = lastText.length > 36 ? lastText.slice(0,33)+'...' : lastText; meta.setAttribute('title', lastText); }
        if(rel){ rel.textContent = ChatCommon.formatRelative(data.created_at_iso || new Date().toISOString()); }
        // Move the item to top (right under header) since it has the newest activity
        const wrapper = document.querySelector('.inbox');
        const afterHeader = wrapper.querySelector('h2')?.nextElementSibling;
        if(afterHeader && inboxItem !== afterHeader){ wrapper.insertBefore(inboxItem, afterHeader); }
      }
    }
  });
  ChatCommon.onTyping(function(data){
    if(!currentStudentId) return;
    const samePair = parseInt(data.prof_id)===parseInt(currentProfId) && parseInt(data.stud_id)===parseInt(currentStudentId);
    if(!samePair) return;
    if(data.sender === 'student'){
      handleIncomingTypingProfessor(data.is_typing);
    }
  });

  // In-chat typing bubble (receiver side - professor) persistent until stop / message
  let typingBubbleElProf=null;
  function ensureTypingBubbleProf(){
    if(!typingBubbleElProf){
      typingBubbleElProf=document.createElement('div');
      typingBubbleElProf.className='typing-bubble';
      typingBubbleElProf.innerHTML='<div class="dots"><span></span><span></span><span></span></div>';
    }
    return typingBubbleElProf;
  }
  function handleIncomingTypingProfessor(isTyping){
    const chatBody=document.getElementById('chat-body');
    if(isTyping){
      const bub=ensureTypingBubbleProf();
      const last=chatBody.lastElementChild;
      if(!last || last!==bub){ chatBody.appendChild(bub); chatBody.scrollTop=chatBody.scrollHeight; }
    } else { removeTypingBubbleProf(); }
  }
  function removeTypingBubbleProf(){ if(typingBubbleElProf && typingBubbleElProf.parentNode){ typingBubbleElProf.parentNode.removeChild(typingBubbleElProf); } }

    function ensureDateLabelForAppend(createdAtIso){
      const chatBody = document.getElementById('chat-body');
      const ts = createdAtIso ? new Date(createdAtIso) : new Date();
      if(isNaN(ts.getTime())) return;
      const msgs = Array.from(chatBody.querySelectorAll('.message'));
      let lastTime = null;
      for(let i=msgs.length-1;i>=0;i--){
        const d = msgs[i].dataset && msgs[i].dataset.created ? new Date(msgs[i].dataset.created) : null;
        if(d && !isNaN(d.getTime())){ lastTime=d; break; }
      }
      const needLabel = !lastTime || ((ts - lastTime)/60000 >= 30);
      if(needLabel){
        const dateDiv=document.createElement('div');
        dateDiv.className='chat-date-label';
        const today=new Date();
        const oneWeekAgo=new Date(today.getTime()-7*24*60*60*1000);
        let label='';
        if(ts.toDateString()===today.toDateString()){
          label = ts.toLocaleTimeString([], {hour:'2-digit',minute:'2-digit'});
        } else if (ts > oneWeekAgo){
          label = ts.toLocaleDateString([], {weekday:'short'})+' '+ts.toLocaleTimeString([], {hour:'2-digit',minute:'2-digit'});
        } else {
          label = ts.toLocaleDateString('en-US',{month:'numeric',day:'numeric',year:'2-digit'})+', '+ts.toLocaleTimeString([], {hour:'2-digit',minute:'2-digit'});
        }
        dateDiv.textContent=label; chatBody.appendChild(dateDiv);
      }
    }
    function appendMessageToChat(direction, text, filePath=null, fileType=null, originalName=null, createdAtIso=null){
      const chatBody = document.getElementById('chat-body');
      ensureDateLabelForAppend(createdAtIso);
      const msgDiv = document.createElement('div');
      msgDiv.className = `message ${direction}`;
      if(createdAtIso){ msgDiv.dataset.created = createdAtIso; }
      if(filePath){
        const fileUrl = `/storage/${filePath}`;
        if(fileType && fileType.startsWith('image/')){
          msgDiv.innerHTML = `<div class=\"chat-img-wrapper\"><img src=\"${fileUrl}\" alt=\"${originalName||'image'}\" class=\"chat-image\"/></div>`;
        } else {
          msgDiv.innerHTML = `<a href=\"${fileUrl}\" target=\"_blank\">${originalName||'Download file'}</a>`;
        }
      } else {
        msgDiv.textContent = text;
      }
      if(createdAtIso){
        msgDiv.title = new Date(createdAtIso).toLocaleTimeString('en-PH',{hour:'2-digit',minute:'2-digit'});
      }
      chatBody.appendChild(msgDiv);
      chatBody.scrollTop = chatBody.scrollHeight;
    }

    function renderMessages(messages){
      const chatBody = document.getElementById('chat-body');
      // Clear previous content and ensure placeholder is removed
      chatBody.innerHTML = '';
      const stale = document.querySelector('#chat-body .no-conversation'); if(stale) stale.remove();
      if(!messages.length){
        chatBody.innerHTML = '<div class="message no-conversation">No conversation yet. You can start the conversation anytime.</div>';
        return;
      }
      let lastMsgTime = null; const chatImages=[];
      messages.forEach(msg=>{
        const msgDate = new Date(msg.created_at_iso || msg.Created_At);
        if(isNaN(msgDate.getTime())) return;
        let showDate=false, dateLabel='';
        if(!lastMsgTime || (msgDate-lastMsgTime)/60000 >= 30){
          showDate=true; const today=new Date(); const oneWeekAgo=new Date(today.getTime()-7*24*60*60*1000);
          if(msgDate.toDateString()===today.toDateString()){
            dateLabel = msgDate.toLocaleTimeString([], {hour:'2-digit',minute:'2-digit'});
          } else if (msgDate > oneWeekAgo){
            dateLabel = msgDate.toLocaleDateString([], {weekday:'short'})+' '+msgDate.toLocaleTimeString([], {hour:'2-digit',minute:'2-digit'});
          } else {
            dateLabel = msgDate.toLocaleDateString('en-US',{month:'numeric',day:'numeric',year:'2-digit'})+', '+msgDate.toLocaleTimeString([], {hour:'2-digit',minute:'2-digit'});
          }
        }
        lastMsgTime = msgDate;
        if(showDate){
          const dateDiv=document.createElement('div');
          dateDiv.className='chat-date-label';
          dateDiv.textContent=dateLabel;
          chatBody.appendChild(dateDiv);
        }
        const direction = msg.Sender === 'professor' ? 'sent':'received';
        const msgDiv=document.createElement('div');
        msgDiv.className=`message ${direction}`;
        const isoVal = msg.created_at_iso || msg.Created_At || null; if(isoVal){ msgDiv.dataset.created = isoVal; }
        if(msg.file_path){
          const fileUrl=`/storage/${msg.file_path}`;
            if(msg.file_type && msg.file_type.startsWith('image/')){
              const imgIndex=chatImages.length; chatImages.push({url:fileUrl,name:msg.original_name||'image',createdAt:msgDate.toISOString()});
              msgDiv.innerHTML = `<div class="chat-img-wrapper" data-index="${imgIndex}"><img src="${fileUrl}" alt="${msg.original_name||'image'}" class="chat-image"/></div>`;
            } else {
              msgDiv.innerHTML = `<a href="${fileUrl}" target="_blank">${msg.original_name||'Download file'}</a>`;
            }
        } else { msgDiv.textContent = msg.Message; }
        msgDiv.title = msgDate.toLocaleTimeString('en-PH',{hour:'2-digit',minute:'2-digit'});
        chatBody.appendChild(msgDiv);
      });
      document.querySelectorAll('.chat-img-wrapper').forEach(el=>{
        el.addEventListener('click',()=>{ const idx=parseInt(el.getAttribute('data-index')); openImageOverlayProf(idx); });
      });
      window.currentProfChatImages = chatImages; setTimeout(()=>{ chatBody.scrollTop = chatBody.scrollHeight; },0);
    }

    function loadChat(person, studId){
      currentChatPerson = person; currentStudentId = studId; document.getElementById('chat-person').textContent = person;
      // Enable chat input when a student is selected
      document.getElementById('message-input').disabled = false;
      document.getElementById('send-btn').disabled = false;
      setAttachmentEnabled(true);
      const vbtn=document.getElementById('video-call-btn');
      if(vbtn){
        const activeItem = document.querySelector(`.inbox-item[data-stud-id="${studId}"]`);
        const canVideo = activeItem && activeItem.getAttribute('data-can-video')==='1';
        if(canVideo){ vbtn.disabled=false; vbtn.classList.remove('is-blocked'); vbtn.title='Start video call'; }
        else { vbtn.disabled=true; vbtn.classList.add('is-blocked'); vbtn.title='Video call available only on scheduled consultation day'; }
      }
      try { localStorage.setItem('chat_last_student_id', String(studId)); } catch(e){}
      // Highlight active inbox item (needed so avatar restore can find image)
      document.querySelectorAll('.inbox-item').forEach(it=>it.classList.remove('active'));
      const activeItem = document.querySelector(`.inbox-item[data-stud-id="${studId}"]`);
      if(activeItem){ activeItem.classList.add('active'); }
      fetch(`/load-direct-messages/${studId}/${currentProfId}`)
        .then(r=>{ if(!r.ok) throw new Error('HTTP '+r.status); return r.json(); })
  .then(messages => { console.log('Loaded messages (prof)', messages); renderMessages(messages); attemptRestoreProfAvatar(); refreshUnread(); })
        .catch(err=>{ console.error('Load direct messages failed', err); document.getElementById('chat-body').innerHTML='<div class="message">Failed to load messages.</div>'; });
    }

    function startVideoCall() {
      if (!currentChatPerson || !currentStudentId) { showToast('Select a student first to start a video call.','error'); return; }
      const active = document.querySelector(`.inbox-item.active`);
      if(!active || active.getAttribute('data-can-video')!=='1'){
        showToast('Video call is only allowed on your scheduled consultation day.','error');
        return;
      }
      const studId = Number(currentStudentId);
      const profId = Number(currentProfId);
      if(!studId || !profId){ showToast('Missing IDs for call.', 'error'); return; }
      let channel = '';
      if(active){
        channel = (active.getAttribute('data-channel') || '').trim();
        if(!channel){ channel = (active.getAttribute('data-schedule-channel') || '').trim(); }
      }
      if(!channel){ channel = `stud-${studId}-prof-${profId}`; }
      launchScheduleCall(channel);
    }

    let selectedFiles = [];

    document.getElementById("file-input").addEventListener("change", function (e) {
        if(!currentStudentId){
          // Guard: should not allow selecting files when no student chosen
          showToast('Select a student first before attaching a file.', 'error');
          e.target.value='';
          return;
        }
        const files = Array.from(e.target.files);
        const ALLOWED_EXT = ['pdf','doc','docx','xls','xlsx','ppt','pptx'];
        const MAX_MB = 25;
        const MAX_BYTES = MAX_MB * 1024 * 1024;
        const MAX_FILES = 10; // arbitrary sensible limit to prevent overload
        const rejected = [];
        const accepted = [];
        if(selectedFiles.length + files.length > MAX_FILES){
          showToast(`You can attach up to ${MAX_FILES} files at once.`, 'error');
          e.target.value='';
          return;
        }
        files.forEach(f=>{
          const name=(f.name||'').toLowerCase();
          const ext=name.split('.').pop();
          const isAllowed = ALLOWED_EXT.includes(ext);
          const within = f.size <= MAX_BYTES;
          if(isAllowed && within){ accepted.push(f); }
          else { rejected.push({name:f.name, reason: !isAllowed ? 'type' : 'size'}); }
        });
        if(rejected.length){
          const hasType = rejected.some(r=>r.reason==='type');
          const hasSize = rejected.some(r=>r.reason==='size');
          let msg = 'Invalid attachment. ';
          if(hasType){ msg += 'Only PDF, Word, Excel, or PowerPoint files are allowed. '; }
          if(hasSize){ msg += `Each file must be 25 MB or smaller.`; }
          showToast(msg.trim(), 'error');
        }
        if(accepted.length){ selectedFiles = selectedFiles.concat(accepted); }
        renderFilePreviews();
        e.target.value = ''; // Reset file input for next selection
    });

    function renderFilePreviews() {
        const container = document.getElementById('file-preview-container');
        container.innerHTML = '';
        selectedFiles.forEach((file, idx) => {
            const preview = document.createElement('div');
            preview.className = 'file-preview';
            if (file.type.startsWith('image/')) {
                const img = document.createElement('img');
                img.src = URL.createObjectURL(file);
                preview.appendChild(img);
                // Do NOT append file name for images
            } else {
                const icon = document.createElement('span');
                icon.innerHTML = "<i class='bx bx-file'></i>";
                preview.appendChild(icon);
                const name = document.createElement('span');
                name.textContent = file.name.length > 20 ? file.name.slice(0, 17) + '...' : file.name;
                preview.appendChild(name);
            }
            const removeBtn = document.createElement('button');
            removeBtn.className = 'remove-file';
            removeBtn.innerHTML = '&times;';
            removeBtn.onclick = () => {
                selectedFiles.splice(idx, 1);
                renderFilePreviews();
            };
            preview.appendChild(removeBtn);

            container.appendChild(preview);
        });
    }

    // Stretch textarea like Messenger
    const textarea = document.getElementById('message-input');
    textarea.addEventListener('input', function () {
        this.style.height = 'auto';
        this.style.height = (this.scrollHeight) + 'px';
    });

    // Send message with files (optimistic)
  let sending=false; const pendingMap={}; // client_uuid -> {el,t}
  const SEND_COOLDOWN_MS = 1200; // anti-spam throttle
  let sendCooldownUntil = 0;
  function genUuid(){ return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g,c=>{const r=Math.random()*16|0,v=c==='x'?r:(r&0x3|0x8);return v.toString(16);}); }
  function sendMessage() {
    const now = Date.now();
    if (now < sendCooldownUntil || sending) return; // block spam or active upload
    const message = textarea.value.trim();
    if (!message && selectedFiles.length === 0) return;
    // Start cooldown immediately; disable button
    sendCooldownUntil = now + SEND_COOLDOWN_MS;
    const sendBtn = document.getElementById('send-btn'); if(sendBtn) sendBtn.disabled = true;
    const clientUuid = genUuid();
    if(message){
      const prevEmpty = document.querySelector('#chat-body .no-conversation');
      if(prevEmpty) prevEmpty.remove();
      const chatBody=document.getElementById('chat-body');
      const msgDiv=document.createElement('div');
      msgDiv.className='message sent pending';
      msgDiv.dataset.clientUuid=clientUuid;
      msgDiv.textContent=message; msgDiv.style.opacity='0.7';
        msgDiv.dataset.created = new Date().toISOString();
      if(typeof ensureDateLabelForAppend === 'function'){
        ensureDateLabelForAppend(msgDiv.dataset.created);
      }
      chatBody.appendChild(msgDiv); chatBody.scrollTop=chatBody.scrollHeight; pendingMap[clientUuid]={el:msgDiv,t:Date.now()};
      placeSentStatusProf(msgDiv);
    }
  const hasFiles = selectedFiles.length>0; sending = hasFiles; if(hasFiles){ const b=document.getElementById('send-btn'); if(b) b.disabled=true; }

        const formData = new FormData();
        formData.append('message', message);
        formData.append('recipient', currentChatPerson);
  formData.append('stud_id', currentStudentId);
  formData.append('prof_id', currentProfId);
        formData.append('sender', 'professor'); // or 'student' for student side
  formData.append('_token', '{{ csrf_token() }}');
  formData.append('client_uuid', clientUuid);
        selectedFiles.forEach((file, i) => {
            formData.append('files[]', file);
        });

  fetch('/send-message', { method: 'POST', body: formData })
    .then(async response => {
      let data=null; let text='';
      try { text = await response.text(); data = JSON.parse(text); }
      catch(parseErr){
        // Non-JSON (likely HTML error page / 500). Provide friendly message.
        const hint = response.status === 413 ? 'Attachments too large.' : 'Server returned an unexpected response.';
        throw { status: 'Error', error: hint + ` (HTTP ${response.status})` };
      }
      if(!response.ok){ throw data; }
      return data;
    })
    .then(data => {
      if (data.status === 'Message sent!') {
        textarea.value=''; textarea.style.height='auto';
        if(hasFiles){ loadChat(currentChatPerson, currentStudentId); }
        selectedFiles=[]; renderFilePreviews();
        ChatCommon.sendTyping(currentStudentId, currentProfId, 'professor', false);
      } else {
        showToast('Invalid attachment. Only PDF, Word, Excel, or PowerPoint up to 25 MB each.', 'error');
  if(pendingMap[clientUuid]){ pendingMap[clientUuid].el.classList.add('failed'); pendingMap[clientUuid].el.style.opacity='1'; }
      }
      sending=false; const left=Math.max(0, sendCooldownUntil-Date.now()); setTimeout(()=>{ const b=document.getElementById('send-btn'); if(b) b.disabled=false; }, left);
    })
    .catch(error => {
      let msg = 'Failed to send.';
      if(error){
        if(error.details && Array.isArray(error.details) && error.details.length){ msg = error.details[0]; }
        else if(error.error){ msg = error.error; }
        else if(error.status){ msg = error.status; }
      }
      // Provide additional hints for size / validation
      if(/25 MB/i.test(msg)){ msg = 'Each file must be 25 MB or smaller.'; }
      showToast(msg,'error');
      if(pendingMap[clientUuid]){ pendingMap[clientUuid].el.classList.add('failed'); pendingMap[clientUuid].el.style.opacity='1'; }
      sending=false; const left=Math.max(0, sendCooldownUntil-Date.now()); setTimeout(()=>{ const b=document.getElementById('send-btn'); if(b) b.disabled=false; }, left);
    });
    }

    document.getElementById("attach-btn")?.addEventListener("click", function () {
        document.getElementById("file-input").click();
    });

    // ENTER to send (Shift+Enter = newline) for professor textarea (no debounce)
    const profMsgInput = document.getElementById('message-input');
    if (profMsgInput) {
      profMsgInput.addEventListener('keydown', function(e){
        if(e.key === 'Enter' && !e.shiftKey){ e.preventDefault(); sendMessage(); }
      });
      // Auto-resize like student side
      profMsgInput.addEventListener('input', function(){
        this.style.height='auto';
        this.style.height=this.scrollHeight+'px';
      });
    }

    // document.getElementById("file-input").addEventListener("change", function (e) {
    //     const files = Array.from(e.target.files);
    //     const filePreviewContainer = document.getElementById("file-preview-container");
    //     filePreviewContainer.innerHTML = ""; // Clear previous previews

    //     files.forEach((file) => {
    //         const fileDiv = document.createElement("div");
    //         fileDiv.className = "file-preview";

    //         const fileName = document.createElement("span");
    //         fileName.className = "file-name";
    //         fileName.textContent = file.name;

    //         const removeBtn = document.createElement("button");
    //         removeBtn.className = "remove-file";
    //         removeBtn.textContent = "Remove";
    //         removeBtn.onclick = function () {
    //             fileDiv.remove();
    //             const index = files.indexOf(file);
    //             if (index > -1) {
    //                 files.splice(index, 1);
    //             }
    //         };

    //         fileDiv.appendChild(fileName);
    //         fileDiv.appendChild(removeBtn);
    //         filePreviewContainer.appendChild(fileDiv);
    //     });

    //     e.target.value = ""; // Reset file input
    // });
  </script>
  <script>
    document.addEventListener('DOMContentLoaded', function() {
      disableChatInputsUntilSelection();
      initClassCallControl();
      const vbtn=document.getElementById('video-call-btn'); if(vbtn){ vbtn.disabled=true; vbtn.classList.add('is-blocked'); }
      // Desktop: show chat panel and try to restore last opened student; Mobile: don't auto-load any chat
      if (!isMobile()) {
        document.getElementById('chat-panel').classList.add('active');
        document.getElementById('back-btn').style.display = 'none';
        let restored = false;
        try {
          const lastStud = localStorage.getItem('chat_last_student_id');
          if(lastStud){
            const target = document.querySelector(`.inbox-item[data-stud-id="${lastStud}"]`);
            if(target){ target.click(); restored = true; }
          }
        } catch(e){}
        if(!restored){
          const firstInboxItem = document.querySelector('.inbox-item');
          if (firstInboxItem) { firstInboxItem.click(); }
        }
      } else {
        document.getElementById('chat-panel').classList.remove('active');
        document.getElementById('back-btn').style.display = 'none';
        // Do NOT auto-load any chat on mobile
      }
      // Initial fetches and timers
      refreshUnread(); refreshPresence(); heartbeat();
      setInterval(refreshUnread, 15000);
      setInterval(refreshPresence, 30000);
      setInterval(heartbeat, 25000);
      setInterval(()=>{
        const now = Date.now();
        const OFFLINE_MS = 80000; // ~80s threshold for UI offline
        document.querySelectorAll('[data-presence]').forEach(dot=>{
          const key = dot.getAttribute('data-presence');
          if(presenceLast[key] && (now - presenceLast[key]) > OFFLINE_MS){
            dot.classList.remove('online');
          }
        });
      },60000);
    });


    function isMobile() {
      return window.innerWidth <= 700;
    }

    function showChatPanel() {
      if (isMobile()) {
        document.getElementById('chat-panel').classList.add('active');
        document.getElementById('back-btn').style.display = 'block';
        document.body.style.overflow = 'hidden';
      }
    }

    function hideChatPanel() {
      if (isMobile()) {
        document.getElementById('chat-panel').classList.remove('active');
        document.getElementById('back-btn').style.display = 'none';
        document.body.style.overflow = '';
      }
    }

    // Show chat panel on inbox item click (mobile)
    document.querySelectorAll('.inbox-item').forEach(item => {
      item.addEventListener('click', showChatPanel);
    });

    // Back button to return to inbox (mobile)
    document.getElementById('back-btn').addEventListener('click', hideChatPanel);

    // On resize, hide chat panel if switching to desktop
    window.addEventListener('resize', function() {
      if (!isMobile()) {
        document.getElementById('chat-panel').classList.add('active');
        document.getElementById('back-btn').style.display = 'none';
        document.body.style.overflow = '';
      } else {
        document.getElementById('chat-panel').classList.remove('active');
        document.getElementById('back-btn').style.display = 'none';
        document.body.style.overflow = '';
      }
    });

    // On load handled above: desktop restores last chat or first; mobile does not auto-load any chat

    // IMAGE OVERLAY (professor side)
    function openImageOverlayProf(index) {
      const images = window.currentProfChatImages || [];
      if (!images.length || index < 0 || index >= images.length) return;
      window.currentProfImageIndex = index;
      const overlay = document.getElementById('prof-image-overlay');
      const mainImg = document.getElementById('prof-overlay-main');
      const dl = document.getElementById('prof-overlay-download');
      const data = images[index];
      mainImg.src = data.url;
      mainImg.alt = data.name;
      dl.href = data.url;
      dl.setAttribute('download', data.name.replace(/[^a-zA-Z0-9._-]/g,'_'));
      buildProfThumbs();
      overlay.classList.remove('hidden');
      document.body.style.overflow = 'hidden';
    }
    function closeProfImageOverlay(){
      const overlay = document.getElementById('prof-image-overlay');
      overlay.classList.add('hidden');
      document.body.style.overflow='';
    }
    function navProfImage(delta){
      const images = window.currentProfChatImages || [];
      if(!images.length) return;
      let idx = (window.currentProfImageIndex||0)+delta;
      if(idx<0) idx = images.length-1;
      if(idx>=images.length) idx=0;
      openImageOverlayProf(idx);
    }
    function buildProfThumbs(){
      const images = window.currentProfChatImages || [];
      const thumbs = document.getElementById('prof-overlay-thumbs');
      if(!thumbs) return;
      thumbs.innerHTML='';
      images.forEach((img,i)=>{
        const t=document.createElement('img');
        t.src=img.url; t.alt=img.name; t.className='overlay-thumb'+(i===window.currentProfImageIndex?' active':'');
        t.addEventListener('click',()=>openImageOverlayProf(i));
        thumbs.appendChild(t);
      });
    }
    document.addEventListener('keydown',(e)=>{
      const overlay=document.getElementById('prof-image-overlay');
      if(!overlay || overlay.classList.contains('hidden')) return;
      if(e.key==='Escape') closeProfImageOverlay();
      else if(e.key==='ArrowRight') navProfImage(1);
      else if(e.key==='ArrowLeft') navProfImage(-1);
    });
    document.addEventListener('click',(e)=>{
      const overlay=document.getElementById('prof-image-overlay');
      if(!overlay || overlay.classList.contains('hidden')) return;
      if(e.target===overlay) closeProfImageOverlay();
    });

    function refreshUnread(){
      ChatCommon.fetchUnread(true).then(map=>{
        if(!map) return;
        document.querySelectorAll('[data-unread]').forEach(el=>{
          const key = el.getAttribute('data-unread');
          const val = map[key] || 0;
          if(val>0){ el.textContent=val; el.classList.remove('hidden'); }
          else { el.textContent=''; el.classList.add('hidden'); }
        });
      });
    }
    function refreshPresence(){
      ChatCommon.fetchPresence().then(data=>{
        if(!data) return;
        document.querySelectorAll('[data-presence]').forEach(dot=>{
          const key = dot.getAttribute('data-presence');
          const id = key.startsWith('stud-') ? key.split('-')[1] : null;
          if(id && data.students && data.students.includes(parseInt(id))){ dot.classList.add('online'); }
          else { dot.classList.remove('online'); }
        });
      });
    }
    function heartbeat(){ ChatCommon.pingPresence(); }

    function markCurrentPairReadProf(){
      if(!currentStudentId) return;
      fetch('/chat/read-pair',{method:'POST', headers:{'X-CSRF-TOKEN':window.csrfToken,'Accept':'application/json','Content-Type':'application/x-www-form-urlencoded'}, body:`stud_id=${encodeURIComponent(currentStudentId)}&prof_id=${encodeURIComponent(currentProfId)}`})
        .then(()=>{ const b=document.querySelector(`[data-unread="stud-${currentStudentId}"]`); if(b){ b.textContent=''; b.classList.add('hidden'); } })
        .catch(()=>{});
    }

    // Typing emission (professor) persistent while textarea not empty
    let typingActive=false;
    textarea.addEventListener('input', function(){
      if(!currentStudentId) return;
      const hasText=this.value.trim().length>0;
      if(hasText && !typingActive){ ChatCommon.sendTyping(currentStudentId, currentProfId, 'professor', true); typingActive=true; }
      else if(!hasText && typingActive){ ChatCommon.sendTyping(currentStudentId, currentProfId, 'professor', false); typingActive=false; }
    });
    textarea.addEventListener('blur', function(){ if(!currentStudentId) return; if(this.value.trim()==='' && typingActive){ ChatCommon.sendTyping(currentStudentId, currentProfId, 'professor', false); typingActive=false; } });

    // removed testBroadcast helper
    // ===== Sent / Delivered status helpers (professor) =====
    function clearExistingStatusProf(){
      const ex=document.querySelector('.msg-status-wrapper'); if(ex && ex.parentNode){ ex.parentNode.removeChild(ex); }
      const prev=document.querySelector('.message.sent.has-status'); if(prev){ prev.classList.remove('has-status'); }
    }
    function placeSentStatusProf(messageEl){
      if(!messageEl) return; clearExistingStatusProf(); messageEl.classList.add('has-status');
      if(currentStudentId){ try { localStorage.removeItem('chat_read_stud_'+currentStudentId); } catch(e){} }
      const wrap=document.createElement('div'); wrap.className='msg-status-wrapper';
      const span=document.createElement('span'); span.className='msg-status-text'; span.textContent='Sent'; wrap.appendChild(span);
      if(messageEl.nextSibling){ messageEl.parentNode.insertBefore(wrap, messageEl.nextSibling); } else { messageEl.parentNode.appendChild(wrap); }
    }
    function showDeliveredAvatarProf(){
      const lastOutgoing = Array.from(document.querySelectorAll('.message.sent')).pop();
      if(!lastOutgoing) return;
      // Ensure wrapper exists
      let wrap = document.querySelector('.msg-status-wrapper');
      if(!wrap || !lastOutgoing.classList.contains('has-status')){
        // Create wrapper manually WITHOUT clearing localStorage flag
        if(wrap && wrap.parentNode){ wrap.parentNode.removeChild(wrap); }
        lastOutgoing.classList.add('has-status');
        wrap=document.createElement('div');
        wrap.className='msg-status-wrapper';
        // (Skip intermediate 'Sent' text to go straight to avatar on restore)
        if(lastOutgoing.nextSibling){ lastOutgoing.parentNode.insertBefore(wrap, lastOutgoing.nextSibling); }
        else { lastOutgoing.parentNode.appendChild(wrap); }
      }
      if(!wrap) return;
      wrap.innerHTML='';
      const img=document.createElement('img'); img.className='msg-status-avatar';
      // Prefer stored avatar URL
      let storedAvatar = null;
      if(currentStudentId){ try { storedAvatar = localStorage.getItem('chat_read_stud_avatar_'+currentStudentId); } catch(e){} }
      if(!storedAvatar){
        const active=document.querySelector('.inbox-item.active img.inbox-avatar');
        storedAvatar = active ? active.getAttribute('src') : '{{ asset('images/dprof.jpg') }}';
      }
      img.src = storedAvatar;
      img.alt='Delivered';
      wrap.appendChild(img);
      if(window.CHAT_DEBUG_READ) console.log('[READ][showDeliveredAvatarProf] ensured avatar src=', storedAvatar);
    }
    function upgradeStatusToAvatarProf(){
      showDeliveredAvatarProf();
      if(currentStudentId){
        try {
          localStorage.setItem('chat_read_stud_'+currentStudentId, '1');
          const active=document.querySelector('.inbox-item.active img.inbox-avatar');
          if(active){ localStorage.setItem('chat_read_stud_avatar_'+currentStudentId, active.getAttribute('src')); }
        } catch(e){}
      }
    }
    function removeStatusProf(){ clearExistingStatusProf(); }
    // Upgrade when student reads
    ChatCommon.onPairRead(function(data){
      if(!currentStudentId) return;
      if(parseInt(data.prof_id)!==parseInt(currentProfId) || parseInt(data.stud_id)!==parseInt(currentStudentId)) return;
      if(data.reader_role==='student'){ upgradeStatusToAvatarProf(); }
    });
    function restoreProfReadStatus(){
      if(!currentStudentId) return false;
      try {
        const stored = localStorage.getItem('chat_read_stud_'+currentStudentId);
        if(!stored){ if(window.CHAT_DEBUG_READ) console.log('[READ][restore] no flag'); return false; }
        const hasSent = document.querySelectorAll('.message.sent').length>0;
        if(!hasSent){ if(window.CHAT_DEBUG_READ) console.log('[READ][restore] no sent yet'); return false; }
        showDeliveredAvatarProf();
        try { localStorage.setItem('chat_read_stud_'+currentStudentId,'1'); } catch(e){}
        const ok = !!document.querySelector('.msg-status-wrapper img.msg-status-avatar');
        if(window.CHAT_DEBUG_READ) console.log('[READ][restore] success=', ok);
        return ok;
      } catch(e){ if(window.CHAT_DEBUG_READ) console.log('[READ][restore] error', e); return false; }
    }
    // Retry logic & observer fallback to ensure avatar restoration despite timing
    function attemptRestoreProfAvatar(){
      if(window.CHAT_DEBUG_READ) console.log('[READ] attemptRestoreProfAvatar start');
      let tries=0; const maxTries=10; const interval=120; // up to ~1.2s
      const timer = setInterval(()=>{
        const done = restoreProfReadStatus();
        if(done){ if(window.CHAT_DEBUG_READ) console.log('[READ] restored on try', tries); clearInterval(timer); observer && observer.disconnect(); return; }
        if(++tries>=maxTries){
          if(window.CHAT_DEBUG_READ) console.log('[READ] maxTries reached, forcing final show');
          const stored = currentStudentId && localStorage.getItem('chat_read_stud_'+currentStudentId);
          if(stored) showDeliveredAvatarProf();
          clearInterval(timer); observer && observer.disconnect();
        }
      }, interval);
      // MutationObserver in case messages render async after network
      const chatBody=document.getElementById('chat-body');
      if(window.MutationObserver && chatBody){
        var observer=new MutationObserver(()=>{ if(window.CHAT_DEBUG_READ) console.log('[READ][observer] mutation'); restoreProfReadStatus(); });
        observer.observe(chatBody,{childList:true,subtree:false});
      }
    }
    window.CHAT_DEBUG_READ = true;
    window.forceProfAvatarRestore = () => { console.log('[READ] manual force'); showDeliveredAvatarProf(); };
    // (status removal on reply handled inside primary onMessage above)
  </script>
  <!-- Professor Image Overlay -->
  <div id="prof-image-overlay" class="image-overlay hidden">
    <button class="overlay-btn close" onclick="closeProfImageOverlay()" aria-label="Close image">&times;</button>
    <a id="prof-overlay-download" class="overlay-btn download" aria-label="Download image"><i class='bx bx-download'></i></a>
    <button class="overlay-nav prev" onclick="navProfImage(-1)" aria-label="Previous image">&#10094;</button>
    <button class="overlay-nav next" onclick="navProfImage(1)" aria-label="Next image">&#10095;</button>
    <img id="prof-overlay-main" class="overlay-main" alt="Preview" />
    <div id="prof-overlay-thumbs" class="overlay-thumbs"></div>
  </div>
</body>
</html>