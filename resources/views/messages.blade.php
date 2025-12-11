<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Messages</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
  <link rel="stylesheet" href="{{ asset('css/messages.css') }}">
  <link rel="stylesheet" href="{{ asset('css/chat-shared.css') }}">
</head>
<body class="messages-page">
    @include('components.navbar')
    <div class="main-content">
        <div class="messages-wrapper">
            <div class="inbox">
                <div class="inbox-header-line">
                  <h2>Professors</h2>
                </div>
                <div class="search-wrapper">
                  <input type="text" id="prof-search" placeholder="Search professor..." oninput="filterProfessors()" />
                </div>
                <div id="inboxNoResults" style="display:none; margin:10px 0 6px; color:#b00020; font-weight:600; font-style:italic;">
                  NO PROFESSOR FOUND
                </div>
        @php
          $deptLabels = [2=>'Computer Science'];
        @endphp
        @foreach($professors as $professor)
          @php
            $picUrl = $professor->profile_photo_url ?? asset('images/dprof.jpg');
            $lastMessage = $professor->last_message ?? 'No messages yet';
            $youPrefix = isset($professor->last_sender) && $professor->last_sender === 'student' ? 'You: ' : '';
            $displayMessage = $youPrefix . $lastMessage;
            $shortMsg = \Illuminate\Support\Str::limit($displayMessage, 40); // truncate with ellipsis
            $relTime = $professor->last_message_time
              ? \Carbon\Carbon::parse($professor->last_message_time)->timezone('Asia/Manila')->diffForHumans(['short'=>true])
              : '';
            $deptLabel = $deptLabels[$professor->dept_id] ?? 'Computer Science';
          @endphp
          <div class="inbox-item" data-name="{{ strtolower($professor->name) }}" data-dept="{{ strtolower($deptLabel) }}" data-prof-id="{{ $professor->prof_id }}" data-can-video="{{ isset($professor->can_video_call) && $professor->can_video_call ? '1':'0' }}" data-channel="{{ $professor->meeting_link ?? '' }}" onclick="loadChat('{{ $professor->name }}', {{ $professor->prof_id }})">
              <img class="inbox-avatar" src="{{ $picUrl }}" alt="{{ $professor->name }}">
              <div class="inbox-meta">
                  <div class="name"><span class="presence-dot" data-presence="prof-{{ $professor->prof_id }}"></span>{{ $professor->name }} <span class="unread-badge hidden" data-unread="prof-{{ $professor->prof_id }}"></span></div>
                  <div class="snippet-line">
                        @php
                          $snippetBase = $lastMessage === '' && $professor->last_message_time ? '[File]' : $lastMessage;
                        @endphp
                        @if($professor->last_message_time)
                          <span class="snippet" title="{{ $displayMessage }}">{!! isset($professor->last_sender) && $professor->last_sender==='student' ? '<strong>You:</strong> ' : '' !!}{{ \Illuminate\Support\Str::limit($snippetBase, 36) }}</span>
                        @else
                          <span class="snippet" title="No conversation yet">No conversation yet</span>
                        @endif
                      @if($relTime)
                        <span class="rel-time">{{ $relTime }}</span>
                      @endif
                  </div>
              </div>
          </div>
        @endforeach
            </div>
            <div class="chat-panel" id="chat-panel">
                <div class="chat-header">
                    <button class="back-btn" id="back-btn" style="display:none;"><i class='bx bx-arrow-back'></i></button>
                    <span id="chat-person">Select a Professor</span>
                    <span id="typing-indicator" class="typing-indicator" style="display:none;">Typing...</span>
                    <button class="video-btn" id="launch-call" onclick="startVideoCall()" title="Video call is only available during your consultation schedule">Video Call</button>
          
                </div>
                <div class="chat-body" id="chat-body">
                    @if(count($professors) === 0)
                        <div class="message">No professors found.</div>
                    @endif
                </div>
                <div class="chat-input" id="chat-input">
                  <div id="file-preview-container" class="file-preview-container"></div>
                  <div class="chat-input-main">
                    <label for="file-input" class="attach-btn" title="Upload file">
                      <i class='bx bx-paperclip'></i>
                    </label>
                    <textarea id="message-input" placeholder="Type a message..." rows="1" maxlength="5000"></textarea>
                    <button id="send-btn" onclick="sendMessage()">Send</button>
                  </div>
                  <input type="file" id="file-input" multiple style="display:none;" accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx" />
                  <input type="hidden" id="last-send-ts" value="0" />
                </div>
            </div>
        </div>
    </div>
    <script>window.csrfToken='{{ csrf_token() }}';</script>
    <script src="{{ asset('js/chat-common.js') }}"></script>
    <script src="{{ asset('js/messages.js') }}"></script>
    <script src="https://js.pusher.com/7.0/pusher.min.js"></script>
    <script>
  // Themed toast helper for consistent UI feedback
  function showToast(message, variant = 'error', timeout = 2800){
    let root = document.getElementById('toast-root');
    if(!root){
      root = document.createElement('div');
      root.id = 'toast-root';
      root.className = 'toast-root';
      document.body.appendChild(root);
    }
    // Coalesce: if a toast with same message exists, reset its timer instead of stacking
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
  let currentProfId = null; // direct messaging target
  const currentStudentId = {{ auth()->user()->Stud_ID ?? 0 }};

  // Init realtime using shared module
  ChatCommon.initPusher('00e7e382ce019a1fa987','ap1', currentStudentId, null);
  // Real-time presence updates
  const presenceLast = {}; // key => timestamp ms
  ChatCommon.onPresence(function(data){
    const key = (data.role === 'student' ? 'stud-' : 'prof-') + data.id;
    presenceLast[key] = Date.now();
    const dot = document.querySelector(`[data-presence="${key}"]`);
    if(dot){ dot.classList.add('online'); }
  });
  ChatCommon.onMessage(function(data){
    // any incoming message implies the conversation has content; clear placeholder
    const emptyEl = document.querySelector('#chat-body .no-conversation');
    if(emptyEl) emptyEl.remove();
    const openPair = currentProfId && parseInt(data.prof_id) === parseInt(currentProfId) && parseInt(data.stud_id) === parseInt(currentStudentId);
    if(openPair){
      // If this is our own optimistic message, reconcile pending bubble
      if(data.sender === 'student' && parseInt(data.stud_id) === parseInt(currentStudentId)){
        if(data.client_uuid){
          const pendingEl = document.querySelector(`.message.sent.pending[data-client-uuid="${data.client_uuid}"]`);
          if(pendingEl){
            pendingEl.classList.remove('pending');
            pendingEl.style.opacity='1';
            pendingEl.dataset.clientUuidResolved='1';
            pendingEl.title = new Date(data.created_at_iso).toLocaleTimeString('en-PH',{hour:'2-digit',minute:'2-digit'});
            // delivered check removed
            if(window.CHAT_LATENCY_LOG && pendingMap[data.client_uuid]){ console.log('[ChatLatency ms]', Date.now()-pendingMap[data.client_uuid].t); }
            return; // don't duplicate
          }
        }
        // Fallback (no uuid match) skip because probably already rendered
        return;
      }
  appendMessageToChat(data.sender === 'student' ? 'sent':'received', data.message, data.file, data.file_type, data.original_name, data.created_at_iso);
  if(data.sender==='professor'){ // keep delivered avatar; only remove typing bubble
    removeTypingBubbleStudent();
    // (Do NOT remove status; avatar persists until student sends next message)
  }
      // If incoming from professor while thread open, immediately mark read & clear badge
      if(data.sender === 'professor'){
        markCurrentPairRead();
      }
    } else if(data.sender === 'professor' && parseInt(data.stud_id) === parseInt(currentStudentId)) {
      const badge = document.querySelector(`[data-unread="prof-${data.prof_id}"]`);
      if(badge){ let val = parseInt(badge.textContent||'0')+1; badge.textContent=val; badge.classList.remove('hidden'); }
    }
  });
  ChatCommon.onTyping(function(data){
    if(!currentProfId) return;
    const samePair = parseInt(data.prof_id)===parseInt(currentProfId) && parseInt(data.stud_id)===parseInt(currentStudentId);
    if(!samePair) return;
    if(data.sender === 'professor'){
      handleIncomingTypingStudent(data.is_typing);
    }
  });

  // In-chat typing bubble (receiver side) - persistent until explicit stop or message arrival
  let typingBubbleElStudent=null;
  function ensureTypingBubbleStudent(){
    if(!typingBubbleElStudent){
      typingBubbleElStudent=document.createElement('div');
      typingBubbleElStudent.className='typing-bubble';
      typingBubbleElStudent.innerHTML='<div class="dots"><span></span><span></span><span></span></div>';
    }
    return typingBubbleElStudent;
  }
  function handleIncomingTypingStudent(isTyping){
    const chatBody = document.getElementById('chat-body');
    if(isTyping){
      const bub = ensureTypingBubbleStudent();
      const last = chatBody.lastElementChild;
      if(!last || last !== bub){
        chatBody.appendChild(bub);
        chatBody.scrollTop = chatBody.scrollHeight;
      }
    } else {
      removeTypingBubbleStudent();
    }
  }
  function removeTypingBubbleStudent(){ if(typingBubbleElStudent && typingBubbleElStudent.parentNode){ typingBubbleElStudent.parentNode.removeChild(typingBubbleElStudent); } }

    function ensureDateLabelForAppend(createdAtIso){
      const chatBody = document.getElementById('chat-body');
      const ts = createdAtIso ? new Date(createdAtIso) : new Date();
      if(isNaN(ts.getTime())) return;
      // Find last message with a timestamp
      const msgs = Array.from(chatBody.querySelectorAll('.message'));
      let lastTime = null;
      for(let i = msgs.length-1; i >= 0; i--){
        const d = msgs[i].dataset && msgs[i].dataset.created ? new Date(msgs[i].dataset.created) : null;
        if(d && !isNaN(d.getTime())){ lastTime = d; break; }
      }
      const needLabel = !lastTime || ((ts - lastTime)/60000 >= 30);
      if(needLabel){
        const dateDiv = document.createElement('div');
        dateDiv.className = 'chat-date-label';
        // Match same formatting used in renderMessages
        const today = new Date();
        const oneWeekAgo = new Date(today.getTime()-7*24*60*60*1000);
        let label='';
        if(ts.toDateString()===today.toDateString()){
          label = ts.toLocaleTimeString([], {hour:'2-digit',minute:'2-digit'});
        } else if (ts > oneWeekAgo){
          label = ts.toLocaleDateString([], {weekday:'short'})+' '+ts.toLocaleTimeString([], {hour:'2-digit',minute:'2-digit'});
        } else {
          label = ts.toLocaleDateString('en-US',{month:'numeric',day:'numeric',year:'2-digit'})+', '+ts.toLocaleTimeString([], {hour:'2-digit',minute:'2-digit'});
        }
        dateDiv.textContent = label;
        chatBody.appendChild(dateDiv);
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
          msgDiv.innerHTML = `<div class="chat-img-wrapper"><img src="${fileUrl}" alt="${originalName||'image'}" class="chat-image"/></div>`;
        } else {
          msgDiv.innerHTML = `<a href="${fileUrl}" target="_blank">${originalName||'Download file'}</a>`;
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
      chatBody.innerHTML = '';
      if(!messages.length){
        chatBody.innerHTML = '<div class="message no-conversation">No conversation yet. You can start the conversation anytime.</div>';
        return;
      }
      let lastMsgTime = null;
      const chatImages = [];
      messages.forEach(msg=>{
        const msgDate = new Date(msg.created_at_iso || msg.Created_At);
        if(isNaN(msgDate.getTime())) return;
        let showDate = false; let dateLabel='';
        if(!lastMsgTime || (msgDate - lastMsgTime)/60000 >= 30){
          showDate = true;
          const today = new Date();
          const oneWeekAgo = new Date(today.getTime()-7*24*60*60*1000);
          if(msgDate.toDateString() === today.toDateString()){
            dateLabel = msgDate.toLocaleTimeString([], {hour:'2-digit',minute:'2-digit'});
          } else if (msgDate > oneWeekAgo){
            dateLabel = msgDate.toLocaleDateString([], {weekday:'short'})+' '+msgDate.toLocaleTimeString([], {hour:'2-digit',minute:'2-digit'});
          } else {
            dateLabel = msgDate.toLocaleDateString('en-US',{month:'numeric',day:'numeric',year:'2-digit'})+', '+msgDate.toLocaleTimeString([], {hour:'2-digit',minute:'2-digit'});
          }
        }
        lastMsgTime = msgDate;
        if(showDate){
          const dateDiv = document.createElement('div');
          dateDiv.className='chat-date-label';
          dateDiv.textContent = dateLabel;
          chatBody.appendChild(dateDiv);
        }
        // Build message bubble
        const direction = msg.Sender === 'student' ? 'sent':'received';
        const msgDiv = document.createElement('div');
        msgDiv.className = `message ${direction}`;
  const isoVal = msg.created_at_iso || msg.Created_At || null; if(isoVal){ msgDiv.dataset.created = isoVal; }
        if (msg.file_path) {
          const fileUrl = `/storage/${msg.file_path}`;
          if (msg.file_type && msg.file_type.startsWith('image/')) {
            const imgIndex = chatImages.length;
            chatImages.push({ url: fileUrl, name: msg.original_name || 'image', createdAt: msgDate.toISOString() });
            msgDiv.innerHTML = `<div class="chat-img-wrapper" data-index="${imgIndex}"><img src="${fileUrl}" alt="${msg.original_name||'image'}" class="chat-image"/></div>`;
          } else {
            msgDiv.innerHTML = `<a href="${fileUrl}" target="_blank">${msg.original_name || 'Download file'}</a>`;
          }
        } else {
          msgDiv.textContent = msg.Message;
        }
        msgDiv.title = msgDate.toLocaleTimeString('en-PH',{hour:'2-digit',minute:'2-digit'});
        chatBody.appendChild(msgDiv);
      });
      // Image click bindings
      document.querySelectorAll('.chat-img-wrapper').forEach(el=>{
        el.addEventListener('click', ()=>{
          const idx = parseInt(el.getAttribute('data-index'));
          openImageOverlay(idx);
        });
      });
      window.currentChatImages = chatImages;
      setTimeout(()=>{ chatBody.scrollTop = chatBody.scrollHeight; },0);
    }

  function loadChat(person, profId) {
    currentChatPerson = person;
    currentProfId = profId;
          try { localStorage.setItem('chat_last_prof_id', String(profId)); } catch(e){}
          document.getElementById('chat-person').textContent = person;

          // Highlight the selected inbox item
          document.querySelectorAll('.inbox-item').forEach(item => item.classList.remove('active'));
          // Find the clicked inbox item and add 'active'
          const inboxItems = document.querySelectorAll('.inbox-item');
          inboxItems.forEach(item => {
            if (item.textContent.includes(person)) {
              item.classList.add('active');
            }
          });

          // Set video call button state based on inbox item attribute
          const selected = Array.from(document.querySelectorAll('.inbox-item')).find(i=>i.classList.contains('active'));
          const canVideo = selected && selected.getAttribute('data-can-video') === '1';
          const btn = document.getElementById('launch-call');
          if(canVideo){
            btn.classList.remove('is-blocked');
            btn.title = 'Start video call';
          } else {
            btn.classList.add('is-blocked');
            btn.title = 'Video call is only available during your consultation schedule';
          }

          // Fetch messages for the selected chat
          fetch(`/load-direct-messages/${currentStudentId}/${profId}`)
            .then(r=>{ if(!r.ok) throw new Error('HTTP '+r.status); return r.json(); })
            .then(messages => { console.log('Loaded messages', messages); renderMessages(messages); refreshUnread(); markCurrentPairRead(); attemptRestoreStudentAvatar(); })
            .catch(err=>{ console.error('Load direct messages failed', err); document.getElementById('chat-body').innerHTML = '<div class="message">Failed to load messages.</div>'; });
        }

        function startVideoCall() {
          if (!currentChatPerson) {
            showToast('Please select a professor to start a video call.', 'error');
            return;
          }
          const active = document.querySelector('.inbox-item.active');
          if(!active || active.getAttribute('data-can-video') !== '1'){
            showToast('Video call is only available during your consultation schedule.', 'error');
            return;
          }
          const studId = Number(currentStudentId);
          const profId = Number(currentProfId);
          if(!studId || !profId){ showToast('Missing IDs for call.', 'error'); return; }
          const override = active?.getAttribute('data-channel') || '';
          const trimmed = override.trim();
          const channel = trimmed.length ? trimmed : `stud-${studId}-prof-${profId}`;
          window.location.href = `/video-call/${encodeURIComponent(channel)}`;
        }

        let selectedFiles = [];

        document.getElementById("file-input").addEventListener("change", function (e) {
            const files = Array.from(e.target.files);
            const ALLOWED_EXT = ['pdf','doc','docx','xls','xlsx','ppt','pptx'];
            const MAX_MB = 25;
            const MAX_BYTES = MAX_MB * 1024 * 1024;
            const MAX_FILES = 10;
            const rejected = [];
            const accepted = [];
            if(selectedFiles.length + files.length > MAX_FILES){
              showToast(`You can attach up to ${MAX_FILES} files at once.`, 'error');
              e.target.value='';
              return;
            }
            files.forEach(f=>{
              const name = (f.name||'').toLowerCase();
              const ext = name.split('.').pop();
              const isAllowed = ALLOWED_EXT.includes(ext);
              const within = f.size <= MAX_BYTES;
              if(isAllowed && within){ accepted.push(f); }
              else { rejected.push({name: f.name, reason: !isAllowed ? 'type' : 'size'}); }
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
    // Enter to send (Shift+Enter = newline)
    textarea.addEventListener('keydown', function(e){
      if(e.key === 'Enter' && !e.shiftKey){
        e.preventDefault();
        sendMessage(); // no artificial delay
      }
    });

  // Send message with files
  window.CHAT_LATENCY_LOG = true; // toggle to false to silence latency logs
  let sending = false;
  const SEND_COOLDOWN_MS = 1200; // anti-spam minimum interval per send
  let sendCooldownUntil = 0;
  const pendingMap = {}; // client_uuid -> {el,t}
    function genUuid(){ return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g,c=>{const r=Math.random()*16|0,v=c==='x'?r:(r&0x3|0x8);return v.toString(16);}); }
    function sendMessage() {
      // Anti-spam: block if within cooldown window or currently uploading
      const now = Date.now();
      if (now < sendCooldownUntil || sending) { return; }
      const message = textarea.value.trim();
      if(!message && selectedFiles.length===0) return;
      // Start cooldown immediately (even for plain text) and disable button
      sendCooldownUntil = now + SEND_COOLDOWN_MS;
      const sendBtn = document.getElementById('send-btn');
      if (sendBtn) sendBtn.disabled = true;
      const clientUuid = genUuid();
      // Optimistic append immediately
      if(message){
        // Remove empty-state placeholder if present
        const prevEmpty = document.querySelector('#chat-body .no-conversation');
        if(prevEmpty) prevEmpty.remove();
        const chatBody = document.getElementById('chat-body');
        const msgDiv = document.createElement('div');
        msgDiv.className='message sent pending';
        msgDiv.dataset.clientUuid = clientUuid;
        msgDiv.textContent = message;
        msgDiv.style.opacity='0.7';
        msgDiv.dataset.created = new Date().toISOString();
        // Ensure a centered time label appears for the first/next message
        if(typeof ensureDateLabelForAppend === 'function'){
          ensureDateLabelForAppend(msgDiv.dataset.created);
        }
        chatBody.appendChild(msgDiv); chatBody.scrollTop = chatBody.scrollHeight;
        pendingMap[clientUuid]={el:msgDiv,t:Date.now()};
        placeSentStatus(msgDiv);
      }
  const hasFiles = selectedFiles.length>0;
  sending = hasFiles; // if uploading files, keep network lock too

            const formData = new FormData();
            formData.append('message', message);
            formData.append('recipient', currentChatPerson);
            formData.append('stud_id', {{ auth()->user()->Stud_ID ?? 0 }});
            formData.append('prof_id', currentProfId);
            formData.append('sender', 'student');
            formData.append('_token', '{{ csrf_token() }}');
            formData.append('client_uuid', clientUuid);
            selectedFiles.forEach((file, i) => {
                formData.append('files[]', file);
            });

            fetch('/send-message', { method: 'POST', body: formData })
            .then(async response => {
              let data=null; let raw='';
              try { raw = await response.text(); data = JSON.parse(raw); }
              catch(parseErr){
                const hint = response.status === 413 ? 'Attachments too large.' : 'Server returned an unexpected response.';
                throw { status: 'Error', error: hint + ` (HTTP ${response.status})` };
              }
              if(!response.ok){ throw data; }
              return data;
            })
      .then(data => {
                if (data.status === 'Message sent!') {
                    textarea.value=''; textarea.style.height='auto';
          if(hasFiles){ loadChat(currentChatPerson, currentProfId); }
                    selectedFiles=[]; renderFilePreviews();
                    // Stop typing indicator
                    ChatCommon.sendTyping(currentStudentId, currentProfId, 'student', false);
                } else {
                    showToast('Invalid attachment. Only PDF, Word, Excel, or PowerPoint up to 25 MB each.', 'error');
                    // On error, mark pending bubble failed
                    if(pendingMap[clientUuid]){ pendingMap[clientUuid].classList.add('failed'); pendingMap[clientUuid].style.opacity='1'; }
                }
        sending = false;
        // Release send button after remaining cooldown (or immediately if elapsed)
        const left = Math.max(0, sendCooldownUntil - Date.now());
        setTimeout(()=>{ const b=document.getElementById('send-btn'); if(b) b.disabled=false; }, left);
            })
      .catch(error => {
        let msg = 'Failed to send.';
        if(error){
          if(error.details && Array.isArray(error.details) && error.details.length){ msg = error.details[0]; }
          else if(error.error){ msg = error.error; }
          else if(error.status){ msg = error.status; }
        }
        if(/25 MB/i.test(msg)){ msg = 'Each file must be 25 MB or smaller.'; }
        showToast(msg, 'error');
        sending=false;
        const left = Math.max(0, sendCooldownUntil - Date.now());
        setTimeout(()=>{ const b=document.getElementById('send-btn'); if(b) b.disabled=false; }, left);
      });
        }

        document.getElementById("attach-btn")?.addEventListener("click", function () {
            document.getElementById("file-input").click();
        });

        // Responsive logic (mobile layout under or equal 700px)
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

        // On load, show chat panel on desktop, hide on mobile
        document.addEventListener('DOMContentLoaded', function() {
          if (!isMobile()) {
            document.getElementById('chat-panel').classList.add('active');
            document.getElementById('back-btn').style.display = 'none';
            // Try to restore last opened professor conversation
            let restored = false;
            try {
              const lastId = localStorage.getItem('chat_last_prof_id');
              if(lastId){
                const target = document.querySelector(`.inbox-item[data-prof-id="${lastId}"]`);
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
          // (removed sticky & dynamic scrollbar class logic)
          // Initial fetches
          refreshUnread();
          refreshPresence();
          heartbeat();
          setInterval(refreshUnread, 15000);
          setInterval(refreshPresence, 30000);
          setInterval(heartbeat, 25000);
          // Sweep offline (no ping in >180s) every 60s
          setInterval(()=>{
            const now = Date.now();
            const OFFLINE_MS = 80000; // ~80s quicker UI drop
            document.querySelectorAll('[data-presence]').forEach(dot=>{
              const key = dot.getAttribute('data-presence');
              if(presenceLast[key] && (now - presenceLast[key]) > OFFLINE_MS){
                dot.classList.remove('online');
              }
            });
          },60000); // still sweep every 60s; server hard cutoff still 3m
        });

        // IMAGE OVERLAY LIGHTBOX (student side only)
        function openImageOverlay(index) {
          const images = window.currentChatImages || [];
          if (!images.length || index < 0 || index >= images.length) return;
          window.currentImageIndex = index;
          const overlay = document.getElementById('image-overlay');
          const mainImg = document.getElementById('overlay-main');
          const dl = document.getElementById('overlay-download');
          const data = images[index];
          mainImg.src = data.url;
          mainImg.alt = data.name;
            // Force download filename
          dl.href = data.url;
          dl.setAttribute('download', data.name.replace(/[^a-zA-Z0-9._-]/g,'_'));
          buildThumbs();
          overlay.classList.remove('hidden');
          document.body.style.overflow = 'hidden';
        }

        function closeImageOverlay() {
          const overlay = document.getElementById('image-overlay');
          overlay.classList.add('hidden');
          document.body.style.overflow = '';
        }

        function navImage(delta) {
          const images = window.currentChatImages || [];
          if (!images.length) return;
          let idx = (window.currentImageIndex || 0) + delta;
          if (idx < 0) idx = images.length - 1;
          if (idx >= images.length) idx = 0;
          openImageOverlay(idx);
        }

        function buildThumbs() {
          const images = window.currentChatImages || [];
          const thumbs = document.getElementById('overlay-thumbs');
          if (!thumbs) return;
          thumbs.innerHTML = '';
          images.forEach((img, i) => {
            const t = document.createElement('img');
            t.src = img.url;
            t.alt = img.name;
            t.className = 'overlay-thumb' + (i === window.currentImageIndex ? ' active' : '');
            t.addEventListener('click', () => openImageOverlay(i));
            thumbs.appendChild(t);
          });
        }

        // Keyboard navigation
        document.addEventListener('keydown', (e) => {
          const overlay = document.getElementById('image-overlay');
          if (overlay.classList.contains('hidden')) return;
          if (e.key === 'Escape') closeImageOverlay();
          else if (e.key === 'ArrowRight') navImage(1);
          else if (e.key === 'ArrowLeft') navImage(-1);
        });

        // Click outside main image closes
        document.addEventListener('click', (e) => {
          const overlay = document.getElementById('image-overlay');
          if (!overlay || overlay.classList.contains('hidden')) return;
          if (e.target === overlay) closeImageOverlay();
        });

        // Search filtering
        function filterProfessors(){
          const term = document.getElementById('prof-search').value.trim().toLowerCase();
          const items = document.querySelectorAll('.inbox-item');
          let visibleCount = 0;
          items.forEach(it=>{
            const name = it.getAttribute('data-name');
            if(!term || name.includes(term)){
              it.style.display='flex';
              visibleCount++;
            } else {
              it.style.display='none';
            }
          });
          const emptyEl = document.getElementById('inboxNoResults');
          if(emptyEl){ emptyEl.style.display = (visibleCount === 0) ? 'block' : 'none'; }
        }

        // Manual realtime test trigger
        // removed testBroadcast helper

        // Unread + Presence helpers
        function refreshUnread(){
          ChatCommon.fetchUnread(false).then(map=>{
            if(!map) return;
            document.querySelectorAll('[data-unread]').forEach(el=>{
              const key = el.getAttribute('data-unread');
              const val = map[key] || 0;
              if(val>0){ el.textContent = val; el.classList.remove('hidden'); }
              else { el.textContent=''; el.classList.add('hidden'); }
            });
          });
        }
        function refreshPresence(){
          ChatCommon.fetchPresence().then(data=>{
            if(!data) return;
            document.querySelectorAll('[data-presence]').forEach(dot=>{
              const key = dot.getAttribute('data-presence');
              // key format prof-<id>
              const id = key.startsWith('prof-') ? key.split('-')[1] : null;
              if(id && data.professors && data.professors.includes(parseInt(id))){ dot.classList.add('online'); }
              else { dot.classList.remove('online'); }
            });
          });
        }
        function heartbeat(){ ChatCommon.pingPresence(); }

        function markCurrentPairRead(){
          if(!currentProfId) return;
          fetch('/chat/read-pair', {method:'POST', headers:{'X-CSRF-TOKEN':window.csrfToken,'Accept':'application/json','Content-Type':'application/x-www-form-urlencoded'}, body:`stud_id=${encodeURIComponent(currentStudentId)}&prof_id=${encodeURIComponent(currentProfId)}`})
            .then(()=>{ const b=document.querySelector(`[data-unread="prof-${currentProfId}"]`); if(b){ b.textContent=''; b.classList.add('hidden'); } })
            .catch(()=>{});
        }

        // Typing indicator emission (persistent while input not empty)
        let typingActive=false; // whether we've sent a TRUE state
        textarea.addEventListener('input', function(){
          if(!currentProfId) return;
            const hasText = this.value.trim().length>0;
            if(hasText && !typingActive){
              ChatCommon.sendTyping(currentStudentId, currentProfId, 'student', true); typingActive=true;
            } else if(!hasText && typingActive){
              ChatCommon.sendTyping(currentStudentId, currentProfId, 'student', false); typingActive=false;
            }
        });
        // On blur: only stop typing if input emptied (keep if user still composing)
        textarea.addEventListener('blur', function(){
          if(!currentProfId) return;
          if(this.value.trim()==='' && typingActive){ ChatCommon.sendTyping(currentStudentId, currentProfId, 'student', false); typingActive=false; }
        });
    // ===== Sent / Delivered status helpers (student) =====
    function clearExistingStatus(){
      const existing = document.querySelector('.msg-status-wrapper');
      if(existing && existing.parentNode){ existing.parentNode.removeChild(existing); }
      const prev = document.querySelector('.message.sent.has-status');
      if(prev){ prev.classList.remove('has-status'); }
    }
    function placeSentStatus(messageEl){
      if(!messageEl) return;
      clearExistingStatus();
      messageEl.classList.add('has-status');
      if(currentProfId){
        try {
          localStorage.removeItem('chat_read_prof_'+currentProfId);
          localStorage.removeItem('chat_read_prof_avatar_'+currentProfId);
        } catch(e){}
      }
      const wrap=document.createElement('div');
      wrap.className='msg-status-wrapper';
      const span=document.createElement('span'); span.className='msg-status-text'; span.textContent='Sent';
      wrap.appendChild(span);
      if(messageEl.nextSibling){ messageEl.parentNode.insertBefore(wrap, messageEl.nextSibling); } else { messageEl.parentNode.appendChild(wrap); }
    }
    function upgradeStatusToAvatar(){
      let wrap=document.querySelector('.msg-status-wrapper');
      if(!wrap){
        const lastOutgoing = Array.from(document.querySelectorAll('.message.sent')).pop();
        if(!lastOutgoing) return;
        lastOutgoing.classList.add('has-status');
        wrap=document.createElement('div'); wrap.className='msg-status-wrapper';
        if(lastOutgoing.nextSibling){ lastOutgoing.parentNode.insertBefore(wrap,lastOutgoing.nextSibling); } else { lastOutgoing.parentNode.appendChild(wrap); }
      }
      wrap.innerHTML='';
      const img=document.createElement('img'); img.className='msg-status-avatar';
      let storedAvatar=null; if(currentProfId){ try { storedAvatar=localStorage.getItem('chat_read_prof_avatar_'+currentProfId); } catch(e){} }
      if(!storedAvatar){
        const active=document.querySelector('.inbox-item.active img.inbox-avatar');
        storedAvatar = active ? active.getAttribute('src') : '{{ asset('images/dprof.jpg') }}';
      }
      img.src=storedAvatar; img.alt='Delivered'; wrap.appendChild(img);
      if(currentProfId){
        try {
          localStorage.setItem('chat_read_prof_'+currentProfId,'1');
          const active=document.querySelector('.inbox-item.active img.inbox-avatar');
          if(active){ localStorage.setItem('chat_read_prof_avatar_'+currentProfId, active.getAttribute('src')); }
        } catch(e){}
      }
      if(window.CHAT_DEBUG_READ_STUD) console.log('[STUD][upgrade] avatar shown src=', img.src);
    }
    function removeStatus(){ clearExistingStatus(); }
    // Subscribe to read receipts
    ChatCommon.onPairRead(function(data){
      if(!currentProfId) return;
      if(parseInt(data.prof_id)!==parseInt(currentProfId) || parseInt(data.stud_id)!==parseInt(currentStudentId)) return;
      if(data.reader_role==='professor'){ upgradeStatusToAvatar(); }
    });
    function restoreReadStatus(){
      if(!currentProfId) return false;
      try {
        const flag = localStorage.getItem('chat_read_prof_'+currentProfId);
        if(!flag){ if(window.CHAT_DEBUG_READ_STUD) console.log('[STUD][restore] no flag'); return false; }
        const hasSent = document.querySelectorAll('.message.sent').length>0;
        if(!hasSent){ if(window.CHAT_DEBUG_READ_STUD) console.log('[STUD][restore] no sent yet'); return false; }
        upgradeStatusToAvatar();
        return !!document.querySelector('.msg-status-wrapper img.msg-status-avatar');
      } catch(e){ if(window.CHAT_DEBUG_READ_STUD) console.log('[STUD][restore] error', e); return false; }
    }
    function attemptRestoreStudentAvatar(){
      if(window.CHAT_DEBUG_READ_STUD) console.log('[STUD] attemptRestoreStudentAvatar start');
      let t=0, max=10; const iv=120; const timer=setInterval(()=>{
        const ok=restoreReadStatus();
        if(ok){ if(window.CHAT_DEBUG_READ_STUD) console.log('[STUD] restored on try', t); clearInterval(timer); ob && ob.disconnect(); return; }
        if(++t>=max){ if(window.CHAT_DEBUG_READ_STUD) console.log('[STUD] max tries reached forcing'); const f=localStorage.getItem('chat_read_prof_'+currentProfId); if(f) upgradeStatusToAvatar(); clearInterval(timer); ob && ob.disconnect(); }
      },iv);
      const chatBody=document.getElementById('chat-body');
      let ob=null;
      if(window.MutationObserver && chatBody){
        ob=new MutationObserver(()=>{ if(window.CHAT_DEBUG_READ_STUD) console.log('[STUD][observer] mutation'); restoreReadStatus(); });
        ob.observe(chatBody,{childList:true,subtree:false});
      }
    }
    window.CHAT_DEBUG_READ_STUD = true;
    window.forceStudentAvatarRestore = () => { console.log('[STUD] manual force'); upgradeStatusToAvatar(); };
    </script>
    <!-- Image Overlay (Student only) -->
    <div id="image-overlay" class="image-overlay hidden">
      <button class="overlay-btn close" onclick="closeImageOverlay()" aria-label="Close image">&times;</button>
      <a id="overlay-download" class="overlay-btn download" aria-label="Download image"><i class='bx bx-download'></i></a>
      <button class="overlay-nav prev" onclick="navImage(-1)" aria-label="Previous image">&#10094;</button>
      <button class="overlay-nav next" onclick="navImage(1)" aria-label="Next image">&#10095;</button>
      <img id="overlay-main" class="overlay-main" alt="Preview" />
      <div id="overlay-thumbs" class="overlay-thumbs"></div>
    </div>
    <!-- Toast root (bottom center) -->
    <div id="toast-root" class="toast-root" aria-live="polite" aria-atomic="true"></div>
</body>
</html>

