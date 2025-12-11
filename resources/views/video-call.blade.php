<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <meta name="csrf-token" content="{{ csrf_token() }}" />
  @php
    $callTitle = 'Online Consultation with ' . (($counterpartName ?? null) !== null && trim($counterpartName) !== '' ? $counterpartName : 'your professor');
  @endphp
  <title>{{ $callTitle }}</title>
  <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
  <link rel="stylesheet" href="/css/video-call.css" />
  <script src="https://download.agora.io/sdk/release/AgoraRTC_N.js"></script>
  <script src="https://download.agora.io/sdk/release/AgoraRTM.min.js"></script>
</head>
<body>
  <div class="layout no-sidebar">
    <div class="topbar">
  <div class="title">{{ $callTitle }}</div>
      <div></div>
    </div>
    <div id="stage">
      <div id="local-player" class="video-player">
        <div id="local-status" class="status-icon hidden"><i class='bx bxs-microphone-off'></i> Mic Off</div>
      </div>
      <div id="remote-player" class="video-player">
        <div id="remote-placeholder" class="remote-placeholder">Waiting for others to join...</div>
        <div id="remote-grid" class="remote-grid hidden"></div>
      </div>
      <div id="controls-panel">
        <div></div>
        <div class="controls-center">
          <div class="ctrl">
            <button id="toggle-mic" class="ctrl-btn"><i class='bx bxs-microphone'></i><span>Audio</span></button>
            <button id="mic-caret" class="caret" title="More"><i class='bx bx-chevron-up'></i></button>
            <div id="mic-dropdown" class="dropdown">
              <label>Microphone</label>
              <select id="micQuickSelect"></select>
              <label style="margin-top:6px;">Speaker</label>
              <select id="spkQuickSelect"></select>
              <span class="link" id="openSettingsFromMic">Audio settings…</span>
            </div>
          </div>
          <div class="ctrl">
            <button id="toggle-cam" class="ctrl-btn"><i class='bx bxs-video'></i><span>Video</span></button>
            <button id="video-caret" class="caret" title="More"><i class='bx bx-chevron-up'></i></button>
            <div id="video-dropdown" class="dropdown">
              <label>Camera</label>
              <select id="camQuickSelect"></select>
              <label style="margin-top:6px;">Resolution</label>
              <select id="resQuickSelect">
                <option value="default">Default</option>
                <option value="hd">1280x720</option>
                <option value="fhd">1920x1080</option>
              </select>
              <span class="link" id="openSettingsFromCam">Video settings…</span>
            </div>
          </div>
          <button id="participantsBtn" class="icon-btn"><i class='bx bxs-user-detail'></i><span>Participants</span></button>
          <button id="chatBtn" class="icon-btn"><i class='bx bx-chat'></i><span>Chat</span></button>
          <button id="ctrl-share" class="icon-btn"><i class='bx bx-desktop'></i><span>Share</span></button>
        </div>
        <div class="controls-right"><button id="leave-btn"><i class='bx bx-phone-off'></i>End</button></div>
      </div>
    </div>
    <aside class="sidebar" id="sidebar">
      <div class="tabs">
        <div class="tab active" data-tab="chat">Chat</div>
        <div class="tab" data-tab="people">People</div>
        <button type="button" class="close-sidebar-btn" id="closeSidebar" aria-label="Close panel"><i class='bx bx-x'></i></button>
      </div>
      <div class="panel" id="panel-chat"><div class="messages" id="messages"></div></div>
      <div class="panel hidden" id="panel-people"><ul class="participants" id="participants"></ul></div>
      <div class="msg-input" id="chat-input">
        <input id="messageBox" type="text" placeholder="Type a message…" maxlength="5000" />
        <button id="sendBtn"><i class='bx bx-send'></i></button>
      </div>
    </aside>
  </div>

  <!-- Settings Modal -->
  <div class="modal" id="settingsModal" aria-hidden="true">
    <div class="card">
      <h3>Audio & Video Settings</h3>
      <div class="grid">
        <div>
          <label>Camera</label>
          <select id="cameraSelect"></select>
        </div>
        <div>
          <label>Microphone</label>
          <select id="micSelect"></select>
        </div>
        <div>
          <label>Speaker (output)</label>
          <select id="speakerSelect"></select>
        </div>
        <div>
          <label>Resolution</label>
          <select id="resolutionSelect">
            <option value="default">Default</option>
            <option value="hd">1280x720</option>
            <option value="fhd">1920x1080</option>
          </select>
        </div>
      </div>
      <div style="display:flex; gap:8px; margin-top:12px;">
        <button id="applySettings">Apply</button>
        <button id="closeSettings">Close</button>
      </div>
    </div>
  </div>

  <script>
  if (window.__videoCallScriptLoaded) {
    console.warn('Duplicate video call script execution skipped.');
  } else {
    window.__videoCallScriptLoaded = true;
  // Basic configuration now fetched dynamically (no hardcoded tokens)
  const APP_ID   = @json(config('app.agora_app_id'));
  const CHANNEL  = @json($channel ?? 'room');
  const IS_PROF  = @json(auth('professor')->check());
  const COUNTERPART_NAME = @json($counterpartName ?? null);
  const IS_DEBUG = @json(config('app.debug'));
  const STUD_ID  = @json($studId ?? null);
  const PROF_ID  = @json($profId ?? null);
  let TOKEN     = null;
  let RTM_TOKEN = null;
  const LEAVE_REDIRECT = "{{ auth('professor')->check() ? route('messages.professor') : route('messages') }}";

  let client, rtmClient, rtmChannel, localUid, rtcDataStream;
    let localAudioTrack, localVideoTrack, screenVideoTrack;
    let micMuted = false, camOff = false, isSharing = false;

  const localContainer   = document.getElementById('local-player');
  const remoteContainer  = document.getElementById('remote-player');
  const remoteGrid       = document.getElementById('remote-grid');
  const remotePlaceholder = document.getElementById('remote-placeholder');
  const micBtn           = document.getElementById('toggle-mic');
  const camBtn           = document.getElementById('toggle-cam');
  const leaveBtn         = document.getElementById('leave-btn');
  const shareBtn         = document.getElementById('ctrl-share') || document.getElementById('btn-share');
  const sidebarBtn       = document.getElementById('ctrl-panel') || document.getElementById('btn-toggle-sidebar');
  const settingsBtn      = document.getElementById('ctrl-settings') || document.getElementById('btn-settings');
  const localStatus      = document.getElementById('local-status');
  const urlParams        = new URLSearchParams(window.location.search);
  const MOCK_COUNT       = IS_DEBUG ? Number(urlParams.get('mock') || 0) : 0;
  const MOCK_NAMES_RAW   = IS_DEBUG ? (urlParams.get('mockNames') || '') : '';
  const MOCK_VIDEO_OFF_RAW = IS_DEBUG ? (urlParams.get('mockVideoOff') || '') : '';
  const MOCK_MIC_OFF_RAW   = IS_DEBUG ? (urlParams.get('mockMicOff') || '') : '';
  const MOCK_ACTIVE_RAW    = IS_DEBUG ? (urlParams.get('mockActive') || '') : '';
  const MOCK_SELF_CAM      = IS_DEBUG ? (urlParams.get('mockSelfCam') || 'on') : 'on';
  const MOCK_SELF_MIC      = IS_DEBUG ? (urlParams.get('mockSelfMic') || 'on') : 'on';
  const MOCK_SELF_NAME     = IS_DEBUG ? (urlParams.get('mockSelfName') || 'You') : 'You';
  const MOCK_MODE        = MOCK_COUNT > 0;
  const AVATAR_THEMES = [
    { gradient: 'linear-gradient(135deg, #FF8A65 0%, #D84315 100%)', border: 'rgba(255, 170, 140, 0.65)' },
    { gradient: 'linear-gradient(135deg, #9575CD 0%, #673AB7 100%)', border: 'rgba(206, 181, 255, 0.55)' },
    { gradient: 'linear-gradient(135deg, #4FC3F7 0%, #0288D1 100%)', border: 'rgba(132, 222, 255, 0.6)' },
    { gradient: 'linear-gradient(135deg, #81C784 0%, #388E3C 100%)', border: 'rgba(167, 231, 173, 0.58)' },
    { gradient: 'linear-gradient(135deg, #FFB74D 0%, #F57C00 100%)', border: 'rgba(255, 205, 140, 0.6)' },
    { gradient: 'linear-gradient(135deg, #64B5F6 0%, #1976D2 100%)', border: 'rgba(140, 200, 255, 0.6)' },
    { gradient: 'linear-gradient(135deg, #E57373 0%, #C62828 100%)', border: 'rgba(255, 182, 182, 0.52)' },
    { gradient: 'linear-gradient(135deg, #4DB6AC 0%, #00796B 100%)', border: 'rgba(119, 214, 203, 0.58)' },
    { gradient: 'linear-gradient(135deg, #BA68C8 0%, #8E24AA 100%)', border: 'rgba(218, 166, 236, 0.55)' },
    { gradient: 'linear-gradient(135deg, #FFD54F 0%, #FFA000 100%)', border: 'rgba(255, 223, 128, 0.62)' },
    { gradient: 'linear-gradient(135deg, #80CBC4 0%, #009688 100%)', border: 'rgba(176, 235, 226, 0.56)' },
    { gradient: 'linear-gradient(135deg, #7986CB 0%, #3F51B5 100%)', border: 'rgba(173, 189, 255, 0.58)' }
  ];
  let mockMediaStream = null;
  let mockStreamFailed = false;
  let mockStreamCleanupAttached = false;

  const sidebar          = document.getElementById('sidebar');
  const layoutEl         = document.querySelector('.layout');
    const tabEls           = document.querySelectorAll('.tab');
    const panelChat        = document.getElementById('panel-chat');
    const panelPeople      = document.getElementById('panel-people');
  const messagesEl       = document.getElementById('messages');
    const participantsEl   = document.getElementById('participants');
    const messageBox       = document.getElementById('messageBox');
    const sendBtn          = document.getElementById('sendBtn');
  const closeSidebarBtn  = document.getElementById('closeSidebar');

    const settingsModal    = document.getElementById('settingsModal');
    const cameraSelect     = document.getElementById('cameraSelect');
    const micSelect        = document.getElementById('micSelect');
    const speakerSelect    = document.getElementById('speakerSelect');
    const resolutionSelect = document.getElementById('resolutionSelect');
    const applySettingsBtn = document.getElementById('applySettings');
    const closeSettingsBtn = document.getElementById('closeSettings');
  // Bottom bar extras
  const participantsBtn  = document.getElementById('participantsBtn');
  const chatBtn          = document.getElementById('chatBtn');
  const micCaret         = document.getElementById('mic-caret');
  const videoCaret       = document.getElementById('video-caret');
  const micDropdown      = document.getElementById('mic-dropdown');
  const videoDropdown    = document.getElementById('video-dropdown');
  const micQuickSelect   = document.getElementById('micQuickSelect');
  const spkQuickSelect   = document.getElementById('spkQuickSelect');
  const camQuickSelect   = document.getElementById('camQuickSelect');
  const resQuickSelect   = document.getElementById('resQuickSelect');
  const openSettingsFromMic = document.getElementById('openSettingsFromMic');
  const openSettingsFromCam = document.getElementById('openSettingsFromCam');
  let autoplayOverlayEl = null;
  let autoplayResumeBtn = null;

  function ensureAutoplayOverlay(){
      if(autoplayOverlayEl){ return autoplayOverlayEl; }
      const overlay = document.createElement('div');
      overlay.className = 'autoplay-overlay';
      overlay.setAttribute('aria-hidden', 'true');
      overlay.innerHTML = `
        <div class="autoplay-card">
          <h3>Playback paused</h3>
          <p>Tap resume to enable audio and video for this consultation.</p>
          <button type="button" class="autoplay-resume-btn">Resume playback</button>
        </div>`;
      const btn = overlay.querySelector('.autoplay-resume-btn');
      if(btn){
        btn.addEventListener('click', async () => {
          btn.disabled = true;
          try {
            await attemptResumePlayback();
          } finally {
            btn.disabled = false;
            hideAutoplayOverlay();
          }
        });
      }
      document.body.appendChild(overlay);
      autoplayResumeBtn = btn;
      autoplayOverlayEl = overlay;
      return overlay;
  }

  function showAutoplayOverlay(){
      const overlay = ensureAutoplayOverlay();
      overlay.classList.add('visible');
      overlay.setAttribute('aria-hidden', 'false');
      if(autoplayResumeBtn){
        autoplayResumeBtn.disabled = false;
        try { autoplayResumeBtn.focus({ preventScroll: true }); } catch {}
      }
  }

  function hideAutoplayOverlay(){
      if(!autoplayOverlayEl){ return; }
      autoplayOverlayEl.classList.remove('visible');
      autoplayOverlayEl.setAttribute('aria-hidden', 'true');
  }

  async function attemptResumePlayback(){
      try {
        if(localVideoTrack){ localVideoTrack.play(localContainer); }
      } catch {}
      try {
        if(screenVideoTrack){ screenVideoTrack.play(localContainer); }
      } catch {}
      const remotes = Array.isArray(client?.remoteUsers) ? client.remoteUsers : [];
      remotes.forEach(user => {
        if(!user){ return; }
        const tile = remoteTiles.get(String(user.uid));
        if(user.videoTrack && tile){
          try { user.videoTrack.play(tile.videoHost); } catch {}
        }
        if(user.audioTrack){
          try { user.audioTrack.play(); } catch {}
        }
      });
  }

  function setupAutoplayGuard(){
      if(typeof AgoraRTC === 'undefined'){ return; }
      AgoraRTC.onAutoplayFailed = () => {
        showAutoplayOverlay();
      };
  }
  setupAutoplayGuard();
  function showRetryChat(){ /* intentionally hidden from UI to avoid noise */ }

  const remoteTiles = new Map();
  const remoteCustomNames = new Map();
  const participantProfiles = new Map();
  const participantProfileFetches = new Map();
  const avatarColorAssignments = new Map();
  const pendingLocalMessages = [];
  const confirmedLocalMessages = [];
  let sendCooldown = false;
    let layoutFrame = null;
    let lastLayoutSignature = '';

    function registerLocalEcho(rawText, element){
      if(!element) return;
      const normalized = (rawText ?? '').trim();
      const entry = { text: normalized, element };
      pendingLocalMessages.push(entry);
      if(pendingLocalMessages.length > 40){ pendingLocalMessages.shift(); }
      element.dataset.origin = element.dataset.origin || 'local';
    }

    function stashConfirmed(entry){
      if(!entry) return;
      confirmedLocalMessages.push(entry);
      if(confirmedLocalMessages.length > 60){ confirmedLocalMessages.shift(); }
    }

    function resolveLocalEcho(rawText, onResolve){
      const normalized = (rawText ?? '').trim();
      if(!normalized){ return false; }
      const idx = pendingLocalMessages.findIndex(entry => entry.text === normalized);
      if(idx !== -1){
        const entry = pendingLocalMessages.splice(idx, 1)[0];
        if(entry && entry.element){
          entry.element.dataset.origin = 'confirmed';
          if(typeof onResolve === 'function'){
            try{ onResolve(entry.element); }catch(_err){}
          }
        }
        stashConfirmed(entry);
        return true;
      }
      const confirmedIndex = confirmedLocalMessages.findIndex(entry => entry.text === normalized);
      if(confirmedIndex !== -1){
        const confirmed = confirmedLocalMessages.splice(confirmedIndex, 1)[0];
        if(confirmed && confirmed.element){
          if(typeof onResolve === 'function'){
            try{ onResolve(confirmed.element); }catch(_err){}
          }
        }
        return true;
      }
      return false;
    }

    function scheduleSendReset(delay = 800){
      setTimeout(()=>{
        sendCooldown = false;
        if(sendBtn){ sendBtn.disabled = false; }
      }, delay);
    }

    function parseMockList(raw){
      const spec = { indices: new Set(), names: new Set() };
      if(!raw){ return spec; }
      raw.split(/\||,/).map(part => part.trim()).filter(Boolean).forEach(entry => {
        const idx = Number(entry);
        if(Number.isFinite(idx) && idx > 0){ spec.indices.add(idx); }
        else { spec.names.add(entry.toLowerCase()); }
      });
      return spec;
    }

    function specHasEntries(spec){ return !!(spec && (spec.indices.size || spec.names.size)); }

    function mockMatches(spec, name, index){
      if(!spec) return false;
      if(spec.indices.has(index)) return true;
      if(name && spec.names.has(String(name).toLowerCase())) return true;
      return false;
    }

    function getInitial(name){
      const trimmed = String(name || '').trim();
      if(!trimmed){ return 'A'; }
      return trimmed.charAt(0).toUpperCase();
    }

    function computeDisplayName(key){
      const cacheKey = String(key ?? '');
      const profile = participantProfiles.get(cacheKey);
      if(profile && profile.name){ return profile.name; }
      const custom = remoteCustomNames.get(cacheKey);
      if(custom){ return custom; }
      return getRemoteLabel(cacheKey);
    }

    function hashString(str){
      let hash = 0;
      for(let i = 0; i < str.length; i++){
        hash = (hash * 33 + str.charCodeAt(i)) | 0;
      }
      return Math.abs(hash);
    }

    function getAvatarTheme(key){
      const normalized = String(key || '').trim();
      if(avatarColorAssignments.has(normalized)){
        return avatarColorAssignments.get(normalized);
      }
      const source = normalized || `anon-${Math.random().toString(36).slice(2)}`;
      const hash = hashString(source);
      const theme = AVATAR_THEMES[hash % AVATAR_THEMES.length];
      avatarColorAssignments.set(normalized, theme);
      return theme;
    }

    function buildVideoOffPlaceholder(){
      const placeholder = document.createElement('div');
      placeholder.className = 'video-off-placeholder';
      const avatar = document.createElement('div');
      avatar.className = 'avatar-circle';
      placeholder.appendChild(avatar);
      const nameEl = document.createElement('span');
      nameEl.className = 'placeholder-name';
      placeholder.appendChild(nameEl);
      return placeholder;
    }

    function updatePlaceholderVisual(placeholder, profile, name, key){
      if(!placeholder) return;
      const displayName = (name || '').trim() || 'Participant';
      const themeKey = String(key || profile?.uid || displayName);
      const theme = getAvatarTheme(themeKey);
      placeholder.style.setProperty('--avatar-gradient', theme?.gradient || '');
      placeholder.style.setProperty('--avatar-border', theme?.border || '');
      placeholder.dataset.uid = themeKey;
      let avatar = placeholder.querySelector('.avatar-circle');
      if(!avatar){
        avatar = document.createElement('div');
        avatar.className = 'avatar-circle';
        placeholder.insertBefore(avatar, placeholder.firstChild);
      }
      avatar.innerHTML = '';
      const hasPhoto = !!(profile && profile.photoUrl);
      if(hasPhoto){
        const img = document.createElement('img');
        img.src = profile.photoUrl;
        img.alt = displayName;
        avatar.appendChild(img);
        avatar.classList.add('has-photo');
      } else {
        const initial = (profile && profile.initial) || getInitial(displayName);
        const span = document.createElement('span');
        span.className = 'avatar-initial';
        span.textContent = initial;
        avatar.appendChild(span);
        avatar.classList.remove('has-photo');
      }
      placeholder.classList.toggle('has-photo', hasPhoto);
      let nameEl = placeholder.querySelector('.placeholder-name');
      if(!nameEl){
        nameEl = document.createElement('span');
        nameEl.className = 'placeholder-name';
        placeholder.appendChild(nameEl);
      }
      nameEl.textContent = displayName;
    }

    function ensureVideoOffPlaceholder(record){
      if(!record) return null;
      let placeholder = record.videoHost.querySelector('.video-off-placeholder');
      if(!placeholder){
        placeholder = buildVideoOffPlaceholder();
        record.videoHost.appendChild(placeholder);
      }
      return placeholder;
    }

    function syncTileProfile(uid){
      const key = String(uid ?? '');
      const record = remoteTiles.get(key);
      if(!record) return;
      const profile = participantProfiles.get(key);
      const name = computeDisplayName(key);
      if(name){ record.name.textContent = name; }
      const placeholder = record.videoHost.querySelector('.video-off-placeholder');
      if(placeholder){ updatePlaceholderVisual(placeholder, profile, name, key); }
    }

    async function loadParticipantProfile(uid){
      if(MOCK_MODE) return null;
      const key = String(uid ?? '');
      if(!key){ return null; }
      if(localUid !== undefined && localUid !== null && key === String(localUid)){
        return null;
      }
      if(participantProfiles.has(key)){
        return participantProfiles.get(key);
      }
      if(participantProfileFetches.has(key)){
        return participantProfileFetches.get(key);
      }
      const request = fetch(`/video-call/participants/${encodeURIComponent(key)}`, { credentials: 'include' })
        .then(async res => {
          if(!res.ok){
            if(res.status === 404){
              participantProfiles.set(key, null);
              return null;
            }
            throw new Error(`HTTP ${res.status}`);
          }
          const data = await res.json();
          participantProfiles.set(key, data);
          return data;
        })
        .catch(err => {
          console.warn('Participant profile fetch failed', key, err);
          participantProfiles.set(key, null);
          return null;
        })
        .finally(() => {
          participantProfileFetches.delete(key);
        });
      participantProfileFetches.set(key, request);
      return request;
    }

    const mockVideoOffSpec = MOCK_MODE ? parseMockList(MOCK_VIDEO_OFF_RAW) : parseMockList('');
    const mockMicOffSpec   = MOCK_MODE ? parseMockList(MOCK_MIC_OFF_RAW)   : parseMockList('');
    const mockActiveSpec   = MOCK_MODE ? parseMockList(MOCK_ACTIVE_RAW)    : parseMockList('');

    async function ensureMockStream(){
      if(mockMediaStream || mockStreamFailed){ return mockMediaStream; }
      if(!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia){
        mockStreamFailed = true;
        return null;
      }
      try{
        mockMediaStream = await navigator.mediaDevices.getUserMedia({ video: { width: 1280, height: 720 }, audio: false });
        if(!mockStreamCleanupAttached){
          mockStreamCleanupAttached = true;
          window.addEventListener('beforeunload', ()=>{
            try{
              if(mockMediaStream && mockMediaStream.getTracks){ mockMediaStream.getTracks().forEach(track=>track.stop()); }
            }catch{}
          }, { once: true });
        }
      }catch(err){
        mockStreamFailed = true;
        console.warn('Mock camera stream unavailable', err);
        return null;
      }
      return mockMediaStream;
    }

    function pxToNumber(value){
      const parsed = parseFloat(value);
      return Number.isFinite(parsed) ? parsed : 0;
    }

    function computeLayout(count){
      if(!remoteGrid || !count){
        return { cols: 1, rows: Math.max(1, count || 0) };
      }
      if(count === 3 || count === 4){
        return { cols: 2, rows: 2 };
      }
      // Force 3 columns x 2 rows for mid-sized calls (5–6 participants) so the grid stays 3x2.
      if(count >= 5 && count <= 6){
        return { cols: 3, rows: 2 };
      }
      const style = getComputedStyle(remoteGrid);
      const paddingX = pxToNumber(style.paddingLeft) + pxToNumber(style.paddingRight);
      const paddingY = pxToNumber(style.paddingTop) + pxToNumber(style.paddingBottom);
      const gapX = pxToNumber(style.columnGap || style.gap);
      const gapY = pxToNumber(style.rowGap || style.gap);
  const availableWidth = Math.max(0, remoteGrid.clientWidth - paddingX);
  const availableHeight = Math.max(0, remoteGrid.clientHeight - paddingY);
      const maxCols = Math.min(Math.max(1, count), 6);
      const minCols = count >= 5 ? 3 : 1;
      const goldenDefaultCols = count <= 3 ? count : Math.ceil(Math.sqrt(count));
      let defaultCols = Math.max(minCols, Math.min(goldenDefaultCols, maxCols));
      if(count === 2){
        defaultCols = 2;
      }
      let best = { score: -Infinity, cols: defaultCols, rows: Math.max(1, Math.ceil(count / Math.max(1, defaultCols))) };

      if(availableWidth <= 0 || availableHeight <= 0){
        return { cols: best.cols, rows: best.rows };
      }

      for(let cols = minCols; cols <= maxCols; cols++){
        if(count === 2 && cols === 1){ continue; }
        const rows = Math.ceil(count / cols);
        const totalGapX = gapX * (cols - 1);
        const totalGapY = gapY * (rows - 1);
        const cellWidth = (availableWidth - totalGapX) / cols;
        if(cellWidth <= 0) continue;
        const cellHeightByWidth = cellWidth * 9 / 16;
        const maxHeightPerTile = (availableHeight - totalGapY) / rows;
        if(maxHeightPerTile <= 0) continue;
        const tileHeight = Math.min(cellHeightByWidth, maxHeightPerTile);
        if(tileHeight <= 0) continue;
        const tileWidth = tileHeight * 16 / 9;
        if(tileWidth <= 0) continue;
        const fitsWidth = (tileWidth * cols) + totalGapX <= availableWidth + 0.1;
        const fitsHeight = (tileHeight * rows) + totalGapY <= availableHeight + 0.1;
        if(!fitsWidth || !fitsHeight) continue;
        const score = tileWidth * tileHeight;
        if(score > best.score){
          best = { score, cols, rows };
        }
      }

      return { cols: best.cols, rows: best.rows };
    }

    function createGridFiller(){
      const filler = document.createElement('div');
      filler.className = 'grid-filler';
      return filler;
    }

    function rebuildGrid(cols, rows){
      if(!remoteGrid) return;
      const tiles = Array.from(remoteTiles.values()).map(record => record.tile);
      if(!tiles.length){
        remoteGrid.replaceChildren();
        return;
      }
      const orderedNodes = [];
      let index = 0;
      for(let row = 0; row < rows; row++){
        const remaining = tiles.length - index;
        const slotCount = Math.min(cols, remaining);
        const emptySlots = Math.max(0, cols - slotCount);
        const leadingFillers = Math.floor(emptySlots / 2);
        const trailingFillers = emptySlots - leadingFillers;
        for(let i=0; i<leadingFillers; i++){ orderedNodes.push(createGridFiller()); }
        for(let i=0; i<slotCount; i++){ orderedNodes.push(tiles[index++]); }
        for(let i=0; i<trailingFillers; i++){ orderedNodes.push(createGridFiller()); }
      }
      remoteGrid.replaceChildren(...orderedNodes);
    }

    function renderMockVisual(record, options){
      if(!record || !record.tile) return;
      const name = options.name || '';
      const index = options.index || 0;
      const hasVideo = !!options.hasVideo;
      const isActive = !!options.active;
      const uid = options.uid ?? record.tile.dataset.uid;
      record.tile.dataset.mock = '1';
      record.tile.dataset.mockIndex = String(index);
      record.tile.dataset.mockName = name;
      record.tile.classList.toggle('active-speaker', isActive);
      record.tile.classList.toggle('cam-off', !hasVideo);
      record.videoHost.innerHTML = '';
      record.status.classList.toggle('hidden', hasVideo);
  record.name.classList.toggle('hidden', hasVideo && !!mockMediaStream);
      if(hasVideo && mockMediaStream){
        const wrapper = document.createElement('div');
        wrapper.className = 'mock-feed has-motion';
        const videoEl = document.createElement('video');
        videoEl.className = 'mock-video';
        videoEl.autoplay = true;
        videoEl.muted = true;
        videoEl.playsInline = true;
        videoEl.srcObject = mockMediaStream;
        if(index % 2 === 0){ videoEl.style.transform = 'scaleX(-1)'; }
        const hue = (index * 37) % 360;
        if(index > 1){ videoEl.style.filter = `hue-rotate(${hue}deg) saturate(1.12)`; }
        wrapper.appendChild(videoEl);
        const overlay = document.createElement('div');
        overlay.className = 'mock-overlay';
        if(name){
          const label = document.createElement('span');
          label.className = 'mock-name';
          label.textContent = name;
          overlay.appendChild(label);
        }
        wrapper.appendChild(overlay);
        record.videoHost.appendChild(wrapper);
      } else if(hasVideo){
        const wrapper = document.createElement('div');
        wrapper.className = 'mock-feed';
        const initial = document.createElement('span');
        initial.className = 'mock-initial';
        initial.textContent = getInitial(name);
        wrapper.appendChild(initial);
        if(name){
          const label = document.createElement('span');
          label.className = 'mock-name';
          label.textContent = name;
          wrapper.appendChild(label);
        }
        record.videoHost.appendChild(wrapper);
      } else {
        const wrapper = document.createElement('div');
        wrapper.className = 'mock-feed mock-camera-off';
        const icon = document.createElement('i');
        icon.className = 'bx bxs-video-off';
        wrapper.appendChild(icon);
        const label = document.createElement('span');
        label.textContent = 'Camera Off';
        wrapper.appendChild(label);
        record.videoHost.appendChild(wrapper);
      }
    }

      function renderLocalMockSelf(options){
        if(!localContainer) return;
        const name = options.name || 'You';
        const stream = options.stream || null;
        const cameraOff = !!options.camOff;
        const showVideo = !!stream && !cameraOff;
        localContainer.innerHTML = '';
        localContainer.classList.toggle('cam-off', !showVideo);
        if(showVideo){
          const wrapper = document.createElement('div');
          wrapper.className = 'mock-feed mock-self has-motion';
          const videoEl = document.createElement('video');
          videoEl.className = 'mock-video';
          videoEl.autoplay = true;
          videoEl.muted = true;
          videoEl.playsInline = true;
          videoEl.srcObject = stream;
          videoEl.style.transform = 'scaleX(-1)';
          wrapper.appendChild(videoEl);
          const overlay = document.createElement('div');
          overlay.className = 'mock-overlay';
          const label = document.createElement('span');
          label.className = 'mock-name';
          label.textContent = name;
          overlay.appendChild(label);
          wrapper.appendChild(overlay);
          localContainer.appendChild(wrapper);
        } else {
          const wrapper = document.createElement('div');
          wrapper.className = 'mock-feed mock-self' + (cameraOff ? ' cam-off' : '');
          if(cameraOff){
            const icon = document.createElement('i');
            icon.className = 'bx bxs-video-off';
            wrapper.appendChild(icon);
            const label = document.createElement('span');
            label.textContent = 'Camera Off';
            wrapper.appendChild(label);
          } else {
            const initial = document.createElement('span');
            initial.className = 'mock-initial';
            initial.textContent = getInitial(name);
            wrapper.appendChild(initial);
            const nameLbl = document.createElement('span');
            nameLbl.className = 'mock-name';
            nameLbl.textContent = name;
            wrapper.appendChild(nameLbl);
          }
          localContainer.appendChild(wrapper);
        }
      }

    function applyRemoteLayoutMetrics(){
      layoutFrame = null;
      if(!remoteGrid) return;
      const activeCount = remoteTiles.size;
      if(activeCount === 0){
        remoteGrid.style.setProperty('--remote-columns', '1');
        remoteGrid.classList.remove('single-row', 'double-row', 'multi-row', 'tight');
        remoteGrid.replaceChildren();
        lastLayoutSignature = '';
        return;
      }
      const layout = computeLayout(activeCount);
      const isMobile = window.matchMedia('(max-width: 768px)').matches;
      const shouldStackMobile = isMobile && activeCount <= 2;
      const effectiveCols = shouldStackMobile ? 1 : layout.cols;
      const effectiveRows = shouldStackMobile ? Math.max(1, activeCount) : layout.rows;
      remoteGrid.style.setProperty('--remote-columns', String(effectiveCols));
      remoteGrid.classList.toggle('mobile-stack', shouldStackMobile);
      const isSingleRow = shouldStackMobile || layout.rows <= 1;
      const isDoubleRow = !shouldStackMobile && layout.rows === 2;
      const isTripleWide = !shouldStackMobile && layout.cols === 3 && layout.rows === 2;
      const isMultiRow = !shouldStackMobile && layout.rows >= 3;
      const shouldTighten = !shouldStackMobile && !isTripleWide && (layout.cols >= 4 || activeCount >= 6);
      const isTwoUp = !shouldStackMobile && layout.cols === 2 && activeCount === 2;
      remoteGrid.classList.toggle('single-row', isSingleRow);
      remoteGrid.classList.toggle('double-row', isDoubleRow);
      remoteGrid.classList.toggle('triple-wide', isTripleWide);
      remoteGrid.classList.toggle('multi-row', isMultiRow);
      remoteGrid.classList.toggle('tight', shouldTighten);
      remoteGrid.classList.toggle('two-up', isTwoUp);
      const signature = `${effectiveCols}x${effectiveRows}-${activeCount}-${shouldStackMobile ? 'stack' : 'grid'}`;
      if(signature !== lastLayoutSignature){
        rebuildGrid(effectiveCols, effectiveRows);
        lastLayoutSignature = signature;
      }
    }

    function queueRemoteLayout(){
      if(layoutFrame !== null){ return; }
      layoutFrame = requestAnimationFrame(applyRemoteLayoutMetrics);
    }

    function updateRemoteLayoutState(){
      if(remoteGrid){ remoteGrid.classList.toggle('hidden', remoteTiles.size === 0); }
      if(remotePlaceholder){ remotePlaceholder.classList.toggle('hidden', remoteTiles.size > 0); }
      queueRemoteLayout();
    }

    function getRemoteLabel(uid){
      const key = String(uid ?? '');
      if(!key){ return IS_PROF ? 'Student' : 'Participant'; }
      const custom = remoteCustomNames.get(key);
      if(custom){ return custom; }
      const remoteCount = Array.isArray(client?.remoteUsers) ? client.remoteUsers.length : 0;
      if(!IS_PROF && COUNTERPART_NAME && remoteCount <= 1){
        return COUNTERPART_NAME;
      }
      const suffix = key.length > 4 ? key.slice(-4) : key.padStart(4, '0');
      const base = IS_PROF ? 'Student' : 'Participant';
      return `${base} ${suffix}`.trim();
    }

  function ensureRemoteTile(user){
      if(!remoteGrid || !user) return null;
      const key = String(user.uid);
      const displayName = user?.displayName || user?.mockName || user?.name || user?.fullName || null;
      if(displayName){ remoteCustomNames.set(key, displayName); }
      if(remoteTiles.has(key)){
        const existing = remoteTiles.get(key);
        const label = displayName || computeDisplayName(key);
        existing.name.textContent = label;
        syncTileProfile(key);
        return existing;
      }
      const tile = document.createElement('div');
      tile.className = 'remote-tile';
      tile.dataset.uid = key;

      const videoHost = document.createElement('div');
      videoHost.className = 'remote-video-slot';

      const status = document.createElement('div');
      status.className = 'status-icon';
      status.innerHTML = "<i class='bx bxs-video-off'></i> Cam Off";

      const name = document.createElement('div');
      name.className = 'remote-name';
      name.textContent = displayName || getRemoteLabel(user.uid);

      tile.appendChild(videoHost);
      tile.appendChild(status);
      tile.appendChild(name);
      remoteGrid.appendChild(tile);

      const record = { tile, videoHost, status, name };
      remoteTiles.set(key, record);
      status.classList.toggle('hidden', !!user.hasVideo);
      markTrackState(user.uid, !!user.hasVideo);
      updateRemoteLayoutState();
      if(!MOCK_MODE && !user?.isMock){
        loadParticipantProfile(user.uid).then(profile => {
          if(profile && profile.name){
            remoteCustomNames.set(key, profile.name);
          }
          syncTileProfile(key);
          refreshParticipants();
        }).catch(()=>{});
      }
      return record;
    }

    function updateRemoteLabels(){
      remoteTiles.forEach((record, key)=>{
        const label = computeDisplayName(key);
        record.name.textContent = label;
        const placeholder = record.videoHost.querySelector('.video-off-placeholder');
        if(placeholder){
          const profile = participantProfiles.get(String(key));
          updatePlaceholderVisual(placeholder, profile, label, key);
        }
      });
    }

    function removeRemoteTile(uid){
      const key = String(uid);
      const record = remoteTiles.get(key);
      if(!record) return;
      record.videoHost.innerHTML = '';
      record.tile.remove();
      remoteTiles.delete(key);
      remoteCustomNames.delete(key);
      participantProfiles.delete(key);
      participantProfileFetches.delete(key);
      avatarColorAssignments.delete(key);
      updateRemoteLayoutState();
    }

    function markTrackState(uid, hasVideo){
      const record = remoteTiles.get(String(uid));
      if(!record) return;
      const showVideo = !!hasVideo;
      const isMockTile = record.tile.dataset.mock === '1';
      record.status.classList.toggle('hidden', showVideo);
      record.tile.classList.toggle('cam-off', !showVideo);
      if(isMockTile){ return; }
      if(!showVideo){
        ensureVideoOffPlaceholder(record);
        syncTileProfile(uid);
      } else {
        const placeholder = record.videoHost.querySelector('.video-off-placeholder');
        if(placeholder){ placeholder.remove(); }
      }
    }

    function parseMockNames(count){
      const raw = MOCK_NAMES_RAW.split('|').map(s => s.trim()).filter(Boolean);
      const names = [];
      for(let i=0; i<count; i++){
        names.push(raw[i] || `Student ${i+1}`);
      }
      return names;
    }

    async function setupMockCall(){
      client = { remoteUsers: [] };
      remoteTiles.forEach(rec => rec.tile.remove());
      remoteTiles.clear();
      remoteCustomNames.clear();
  participantProfiles.clear();
  participantProfileFetches.clear();
  avatarColorAssignments.clear();
      lastLayoutSignature = '';
      remoteGrid?.replaceChildren();

      const names = parseMockNames(MOCK_COUNT);
      const hasExplicitActive = specHasEntries(mockActiveSpec);
      const selfName = MOCK_SELF_NAME || 'You';
      const selfCamOff = (MOCK_SELF_CAM || '').toLowerCase() === 'off';
      const selfMicOff = (MOCK_SELF_MIC || '').toLowerCase() === 'off';
      const stream = await ensureMockStream();

      renderLocalMockSelf({ name: selfName, stream, camOff: selfCamOff });

      micMuted = selfMicOff;
      camOff = selfCamOff;
      localStatus.classList.toggle('hidden', !micMuted);
      micBtn.innerHTML = micMuted ? "<i class='bx bxs-microphone-off'></i>Unmute" : "<i class='bx bxs-microphone'></i>Mute";
      camBtn.innerHTML = camOff ? "<i class='bx bxs-video-off'></i>Show" : "<i class='bx bxs-video'></i>Video";

      for(let i=0; i<MOCK_COUNT; i++){
        const idx = i + 1;
        const name = names[i];
        const hasVideo = !mockMatches(mockVideoOffSpec, name, idx);
        const hasAudio = !mockMatches(mockMicOffSpec, name, idx);
        const isActive = mockMatches(mockActiveSpec, name, idx) || (!hasExplicitActive && i === 0);
        const uid = `mock-${idx}`;
        const mockUser = { uid, hasVideo, hasAudio, displayName: name, isMock: true, mockIndex: idx };
        client.remoteUsers.push(mockUser);
        const record = ensureRemoteTile(mockUser);
        if(record){
          renderMockVisual(record, { name, index: idx, hasVideo, active: isActive, uid });
        }
      }
      updateRemoteLayoutState();
      refreshParticipants();
    }
    function logMsg(content, isSystem=false, isSelf=false){
      // Always suppress system/debug messages from the visible chat UI
      if(isSystem){ try{ console.debug(content); }catch{} return null; }
      const div = document.createElement('div');
      div.className = 'msg ' + (isSelf ? 'me' : 'other');
      div.textContent = content;
      messagesEl.appendChild(div);
      messagesEl.scrollTop = messagesEl.scrollHeight;
      return div;
    }

    function refreshParticipants(){
      participantsEl.innerHTML = '';
      const me = document.createElement('li');
      me.innerHTML = `<i class='bx bxs-user'></i> You`;
      participantsEl.appendChild(me);
      updateRemoteLabels();
      const remotes = Array.isArray(client?.remoteUsers) ? client.remoteUsers : [];
      remotes.forEach(u => {
        const li = document.createElement('li');
        const vid = u.hasVideo ? '' : "<span class='pill'>Cam off</span>";
        const mic = u.hasAudio ? '' : "<span class='pill'>Mic muted</span>";
        const tile = remoteTiles.get(String(u.uid));
        const name = tile?.name?.textContent || getRemoteLabel(u.uid);
        li.innerHTML = `<i class='bx bxs-user'></i> ${name} ${vid} ${mic}`;
        participantsEl.appendChild(li);
      });
    }

    // --- Simple HTTP-polling chat fallback (works without RTM) ---
    const CSRF = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    const CHAT_CHANNEL = CHANNEL;
    const CHAT_HISTORY_URL = '/video-call/chat/history';
    const CHAT_SEND_URL = '/video-call/chat';
    const SELF_ROLE = IS_PROF ? 'professor' : 'student';
    const SELF_UID = SELF_ROLE === 'professor'
      ? (PROF_ID ? String(PROF_ID) : null)
      : (STUD_ID ? String(STUD_ID) : null);
    let pollTimer = null;
    let lastChatMessageId = 0;
    const seenMessageIds = new Set();
    const pendingChatSyncQueue = [];
    let pendingChatSyncTimer = null;

    function markPendingEcho(entry){
      if(!entry) return;
      entry.dataset.pending = '1';
      entry.dataset.failed = '0';
      entry.classList.add('chat-pending');
      entry.classList.remove('chat-failed');
      entry.dataset.origin = entry.dataset.origin || 'local';
    }

    function markEchoFailed(entry){
      if(!entry) return;
      entry.dataset.pending = '0';
      entry.dataset.failed = '1';
      entry.classList.remove('chat-pending');
      entry.classList.add('chat-failed');
    }

    function finalizeLocalChatEcho(rawText, payload, fallbackEntry){
      const apply = (element)=>{
        if(!element) return;
        element.dataset.pending = '0';
        element.dataset.failed = '0';
        element.classList.remove('chat-pending');
        element.classList.remove('chat-failed');
        element.dataset.origin = 'confirmed';
        if(payload && payload.id){ element.dataset.msgId = payload.id; }
      };
      if(!resolveLocalEcho(rawText, apply) && fallbackEntry){
        apply(fallbackEntry);
      }
      if(payload && payload.id){
        const numericId = Number(payload.id);
        if(Number.isFinite(numericId) && numericId > 0){
          seenMessageIds.add(numericId);
          if(numericId > lastChatMessageId){ lastChatMessageId = numericId; }
        }
      }
      try{ setTimeout(()=>{ try{ pollOnce(); }catch(_err){}; }, 200); }catch(_err){}
    }

    function queueChatSyncRetry(rawText, entry, attempt = 0){
      if(!rawText) return;
      pendingChatSyncQueue.push({ text: rawText, entry, attempt });
      if(!pendingChatSyncTimer){ pendingChatSyncTimer = setTimeout(flushChatSyncQueue, 1600); }
    }

    async function flushChatSyncQueue(){
      if(pendingChatSyncTimer){
        clearTimeout(pendingChatSyncTimer);
        pendingChatSyncTimer = null;
      }
      if(!pendingChatSyncQueue.length) return;
      const batch = pendingChatSyncQueue.splice(0, pendingChatSyncQueue.length);
      for(const item of batch){
        let payload = null;
        try{ payload = await httpSendChat(item.text); }catch(_err){ payload = null; }
        if(payload){
          finalizeLocalChatEcho(item.text, payload, item.entry);
          continue;
        }
        const nextAttempt = (item.attempt ?? 0) + 1;
        if(nextAttempt < 5){
          pendingChatSyncQueue.push({ text: item.text, entry: item.entry, attempt: nextAttempt });
        }else{
          markEchoFailed(item.entry);
          logMsg('Unable to sync chat message. Please retry.', true);
        }
      }
      if(pendingChatSyncQueue.length){ pendingChatSyncTimer = setTimeout(flushChatSyncQueue, 4000); }
    }
    function renderFetched(list){
      for (const msg of list){
        if(!msg) continue;
        const msgId = Number(msg.id ?? 0);
        if(msgId > 0){
          if(seenMessageIds.has(msgId)) { continue; }
          seenMessageIds.add(msgId);
          if(msgId > lastChatMessageId){ lastChatMessageId = msgId; }
        }
        const normalized = (msg.message ?? '').trim();
        if(normalized === '') { continue; }
        const senderRole = (msg.sender_role || '').toLowerCase();
        const senderUid = msg.sender_uid !== undefined && msg.sender_uid !== null ? String(msg.sender_uid) : null;
        const isSelf = senderUid && SELF_UID && senderUid === SELF_UID && senderRole === SELF_ROLE;
        const who = isSelf
          ? 'You'
          : (msg.sender_name && msg.sender_name.trim() !== ''
              ? msg.sender_name
              : (senderRole === 'professor'
                  ? (COUNTERPART_NAME || 'Professor')
                  : 'Student'));
        if(isSelf && resolveLocalEcho(normalized, element => {
          if(msgId > 0){ element.dataset.msgId = msgId; }
          element.dataset.origin = element.dataset.origin || 'confirmed';
        })){
          continue;
        }
        const entry = logMsg(`${who}: ${normalized}`, false, isSelf);
        if(entry){
          if(msgId > 0){ entry.dataset.msgId = msgId; }
          if(isSelf && entry.dataset.origin !== 'local'){ entry.dataset.origin = entry.dataset.origin || 'history'; }
        }
      }
    }
    async function pollOnce(){
      if(!CHAT_CHANNEL) return;
      try{
        const url = `${CHAT_HISTORY_URL}?channel=${encodeURIComponent(CHAT_CHANNEL)}&after_id=${lastChatMessageId}`;
        const res = await fetch(url, { credentials: 'include' });
        if(!res.ok){ return; }
        const payload = await res.json();
        const list = Array.isArray(payload.messages) ? payload.messages : [];
        renderFetched(list);
      }catch{}
    }
    function startPolling(){ if(pollTimer) return; pollTimer = setInterval(pollOnce, 2000); pollOnce(); }
    async function httpSendChat(text){
      if(!CHAT_CHANNEL) return null;
      try{
        const res = await fetch(CHAT_SEND_URL, {
          method: 'POST', credentials: 'include',
          headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
          body: JSON.stringify({ channel: CHAT_CHANNEL, message: text })
        });
        if(!res.ok){ return null; }
        const payload = await res.json();
        if(payload && payload.id){
          seenMessageIds.add(Number(payload.id));
          if(Number(payload.id) > lastChatMessageId){ lastChatMessageId = Number(payload.id); }
        }
        setTimeout(pollOnce, 200);
        return payload;
      }catch{}
      return null;
    }

    async function enumerateDevices(){
      const devices = await navigator.mediaDevices.enumerateDevices();
      const cams = devices.filter(d=>d.kind==='videoinput');
      const mics = devices.filter(d=>d.kind==='audioinput');
      const outs = devices.filter(d=>d.kind==='audiooutput');
      cameraSelect.innerHTML = cams.map(d=>`<option value="${d.deviceId}">${d.label||'Camera'}</option>`).join('');
      micSelect.innerHTML = mics.map(d=>`<option value="${d.deviceId}">${d.label||'Microphone'}</option>`).join('');
      speakerSelect.innerHTML = outs.map(d=>`<option value="${d.deviceId}">${d.label||'Speaker'}</option>`).join('');
    }

    async function switchCamera(deviceId){
      const profile = resolutionSelect.value;
      const encoderConfig = profile==='hd' ? '720p' : profile==='fhd' ? '1080p' : undefined;
      const newVideo = await AgoraRTC.createCameraVideoTrack({ cameraId: deviceId, encoderConfig });
      await client.unpublish(localVideoTrack);
      localVideoTrack.stop(); localVideoTrack.close();
      localVideoTrack = newVideo;
      await client.publish(localVideoTrack);
      localVideoTrack.play(localContainer);
      camOff = false; camBtn.innerHTML = "<i class='bx bxs-video'></i>Video";
    }

    async function switchMic(deviceId){
      const newAudio = await AgoraRTC.createMicrophoneAudioTrack({ microphoneId: deviceId });
      await client.unpublish(localAudioTrack);
      localAudioTrack.stop(); localAudioTrack.close();
      localAudioTrack = newAudio;
      await client.publish(localAudioTrack);
      if(micMuted){ localAudioTrack.setEnabled(false); }
    }

    function openSettings(){ settingsModal.classList.add('open'); }
    function closeSettings(){ settingsModal.classList.remove('open'); }

    // Sidebar tabs
    tabEls.forEach(t=>t.addEventListener('click', ()=>{
      tabEls.forEach(el=>el.classList.remove('active'));
      t.classList.add('active');
      const tab = t.getAttribute('data-tab');
      panelChat.classList.toggle('hidden', tab!=='chat');
      panelPeople.classList.toggle('hidden', tab!=='people');
      document.getElementById('chat-input').classList.toggle('hidden', tab!=='chat');
    }));

  if (sidebarBtn) sidebarBtn.addEventListener('click', ()=>{
      const hidden = layoutEl.classList.toggle('no-sidebar');
      if(hidden){
        // Ensure chat tab is visible when reopened later
        tabEls.forEach(el=>el.classList.remove('active'));
        document.querySelector('.tab[data-tab="chat"]').classList.add('active');
        panelChat.classList.remove('hidden');
        panelPeople.classList.add('hidden');
      }
      queueRemoteLayout();
  });
    function toggleSidebarFor(tab){
      const isMobile = window.matchMedia('(max-width: 768px)').matches;
      if(isMobile){
        const open = layoutEl.classList.contains('show-sidebar');
        const current = document.querySelector('.tab.active')?.getAttribute('data-tab');
        if(open && current === tab){ layoutEl.classList.remove('show-sidebar'); }
        else { layoutEl.classList.add('show-sidebar'); } 
      } else {
        const isHidden = layoutEl.classList.contains('no-sidebar');
        if(isHidden){ layoutEl.classList.remove('no-sidebar'); }
        else {
          const current = document.querySelector('.tab.active')?.getAttribute('data-tab');
          if(current === tab){ layoutEl.classList.add('no-sidebar'); return; }
        }
      }
      tabEls.forEach(el=>el.classList.remove('active'));
      document.querySelector(`.tab[data-tab="${tab}"]`).classList.add('active');
      panelChat.classList.toggle('hidden', tab!=='chat');
      panelPeople.classList.toggle('hidden', tab!=='people');
      queueRemoteLayout();
    }
    participantsBtn.addEventListener('click', ()=> toggleSidebarFor('people'));
    chatBtn.addEventListener('click', ()=> toggleSidebarFor('chat'));
    closeSidebarBtn?.addEventListener('click', ()=>{
      layoutEl.classList.remove('show-sidebar');
    });
    // Mic/Video dropdowns
    function closeDrops(){ micDropdown.classList.remove('open'); videoDropdown.classList.remove('open'); }
    document.addEventListener('click', (e)=>{
      if(!e.target.closest('.ctrl')) closeDrops();
    });
    micCaret.addEventListener('click', async (e)=>{ e.stopPropagation(); await enumerateDevices();
      micQuickSelect.innerHTML = micSelect.innerHTML; spkQuickSelect.innerHTML = speakerSelect.innerHTML; micDropdown.classList.toggle('open'); videoDropdown.classList.remove('open');
    });
    videoCaret.addEventListener('click', async (e)=>{ e.stopPropagation(); await enumerateDevices();
      camQuickSelect.innerHTML = cameraSelect.innerHTML; resQuickSelect.value = resolutionSelect.value; videoDropdown.classList.toggle('open'); micDropdown.classList.remove('open');
    });
    micQuickSelect.addEventListener('change', async ()=>{ if(micQuickSelect.value) await switchMic(micQuickSelect.value); });
    spkQuickSelect?.addEventListener('change', async ()=>{
      // Try to set sinkId for all media elements
      try{
        const vids = document.querySelectorAll('video, audio');
        for(const v of vids){ if(v.setSinkId) await v.setSinkId(spkQuickSelect.value); }
      }catch{}
    });
    camQuickSelect.addEventListener('change', async ()=>{ if(camQuickSelect.value){ resolutionSelect.value = resQuickSelect.value; await switchCamera(camQuickSelect.value); }});
    resQuickSelect.addEventListener('change', async ()=>{ if(camQuickSelect.value){ resolutionSelect.value = resQuickSelect.value; await switchCamera(camQuickSelect.value); }});
    openSettingsFromMic.addEventListener('click', ()=>{ enumerateDevices(); openSettings(); closeDrops(); });
    openSettingsFromCam.addEventListener('click', ()=>{ enumerateDevices(); openSettings(); closeDrops(); });
  if (settingsBtn) settingsBtn.addEventListener('click', ()=>{ enumerateDevices(); openSettings(); });
    closeSettingsBtn.addEventListener('click', closeSettings);
    applySettingsBtn.addEventListener('click', async ()=>{
      if(cameraSelect.value) await switchCamera(cameraSelect.value);
      if(micSelect.value) await switchMic(micSelect.value);
      // Attempt set sinkId if supported
      try{
        const videos = remoteContainer.querySelectorAll('video');
        videos.forEach(v=>v.setSinkId && v.setSinkId(speakerSelect.value));
      }catch{}
      closeSettings();
    });

    async function fetchRtcToken(channel){
      const url = `{{ route('agora.token.rtc') }}?channel=${encodeURIComponent(channel)}`;
      const res = await fetch(url, { credentials: 'include' });
      if(!res.ok){ let body=''; try{ body=await res.text(); }catch{} console.error('RTC token fetch failed', {status: res.status, url, body}); throw new Error(`RTC token endpoint returned ${res.status}`); }
      const data = await res.json();
      if(!data.token || !data.appId){ console.error('Invalid RTC token response payload', data); throw new Error('Invalid RTC token response'); }
      return data;
    }

    async function fetchRtmToken(){
      try{
        const url = `{{ route('agora.token.rtm') }}`;
        const res = await fetch(url, { credentials: 'include' });
        if(!res.ok){ let body=''; try{ body=await res.text(); }catch{} console.warn('RTM token endpoint status', {status: res.status, url, body}); return null; }
        const data = await res.json();
        if(!data || !data.token) return null;
        return data;
      }catch(err){ console.warn('RTM token fetch error', err); return null; }
    }

    // Device fallback like professor page
    async function createLocalTracksWithFallback(){
      try{ const pair = await AgoraRTC.createMicrophoneAndCameraTracks(); return { audio: pair[0], video: pair[1], mode: 'mic+cam' }; }
      catch(err){
        console.warn('createMicrophoneAndCameraTracks failed, falling back…', err);
        let audio=null, video=null, mode='none';
        try{ video = await AgoraRTC.createCameraVideoTrack(); mode = video ? 'cam' : mode; }catch(e){ console.warn('camera track failed', e); }
        try{ audio = await AgoraRTC.createMicrophoneAudioTrack(); mode = audio && mode==='cam' ? 'mic+cam' : (audio ? 'mic' : mode); }catch(e){ console.warn('microphone track failed', e); }
        return { audio, video, mode, error: err };
      }
    }

    async function joinCall(){
  const rtc = await fetchRtcToken(CHANNEL);
  TOKEN = rtc.token;
  const appIdFromServer = rtc.appId || APP_ID;
  const uidFromServer = rtc.uid !== undefined && rtc.uid !== null ? Number(rtc.uid) : null;
      client = AgoraRTC.createClient({ mode: 'rtc', codec: 'vp8' });
      client.on('user-published', handleUserPublished);
      client.on('user-unpublished', handleUserUnpublished);
      client.on('user-joined', user => {
        ensureRemoteTile(user);
        refreshParticipants();
      });
      client.on('user-left', user => {
        removeRemoteTile(user.uid);
        refreshParticipants();
      });
      client.on('token-privilege-will-expire', async ()=>{
        try{
          const fresh = await fetchRtcToken(CHANNEL);
          TOKEN = fresh.token;
          await client.renewToken(TOKEN);
          logMsg('RTC token renewed', true);
        }catch{ logMsg('RTC token renewal failed', true); }
      });
      client.on('token-privilege-did-expire', async ()=>{
        try{
          const fresh = await fetchRtcToken(CHANNEL);
          TOKEN = fresh.token;
          await client.renewToken(TOKEN);
          logMsg('RTC token reloaded after expiry', true);
        }catch{ logMsg('RTC token reload failed', true); }
      });

  // Join first; don't fail whole call if mic/cam creation fails
  localUid = await client.join(appIdFromServer, CHANNEL, TOKEN, uidFromServer);
    const created = await createLocalTracksWithFallback();
    localAudioTrack = created.audio || null;
    localVideoTrack = created.video || null;
    const toPublish = [];
    if(localAudioTrack) toPublish.push(localAudioTrack);
    if(localVideoTrack){ toPublish.push(localVideoTrack); try{ localVideoTrack.play(localContainer); }catch{} }
    if(toPublish.length){ try{ await client.publish(toPublish); }catch(pubErr){ console.error('Failed to publish local tracks', pubErr);} }
    else { logMsg('Joined without mic/camera (device blocked or not available).', true); }
      // Create RTC data stream fallback for chat
      try {
        rtcDataStream = await client.createDataStream();
        logMsg('Chat ready (RTC data stream).', true);
      } catch (e) {
        // ignore if not supported
      }
      refreshParticipants();

      // Optional RTM (chat) with diagnostics and retry
      async function connectRTM(){
        if (typeof AgoraRTM === 'undefined') {
          logMsg('Chat SDK (AgoraRTM) not loaded; skipping RTM.', true);
          startPolling();
          return;
        }
        try{
          const rtm = await fetchRtmToken();
          if(rtm && rtm.token){
            RTM_TOKEN = rtm.token;
            const rtmUid = String(rtm.uid ?? localUid);
            logMsg(`RTM: attempting login as ${rtmUid}`, true);
            logMsg(`RTM: appId ${appIdFromServer}, token length ${RTM_TOKEN?.length||0}`, true);
            rtmClient = AgoraRTM.createInstance(appIdFromServer);
            rtmClient.on('ConnectionStateChanged', (state, reason)=>{ logMsg(`RTM state: ${state} (${reason})`, true); });
            await rtmClient.login({ uid: rtmUid, token: RTM_TOKEN });
            rtmClient.on('TokenPrivilegeWillExpire', async ()=>{
              try{ const fresh = await fetchRtmToken(); if(fresh){ await rtmClient.renewToken(fresh.token); logMsg('RTM token renewed', true);} }catch{}
            });
            rtmClient.on('TokenPrivilegeDidExpire', async ()=>{
              try{ const fresh = await fetchRtmToken(); if(fresh){ await rtmClient.renewToken(fresh.token); logMsg('RTM token reloaded after expiry', true);} }catch{}
            });
            rtmChannel = await rtmClient.createChannel(CHANNEL);
            await rtmChannel.join();
            const otherRole = SELF_ROLE === 'professor' ? 'Student' : (COUNTERPART_NAME || 'Professor');
            rtmChannel.on('ChannelMessage', ({text}, senderId)=>{ logMsg(`${otherRole}: ${text}`, false, false); });
            logMsg('Chat connected', true);
          } else {
            logMsg('Chat not available (no RTM token).', true);
          }
        }catch(err){
          console.error('RTM login error', err);
          const code = err?.code ?? err?.message ?? 'unknown';
          logMsg(`Chat not available (RTM login failed: ${code}).`, true);
          showRetryChat();
        }
      }
  await connectRTM();
  // Always engage fallback polling as a safety net
  startPolling();
      queueRemoteLayout();
    }

    window.addEventListener('DOMContentLoaded', async () => {
      if(MOCK_MODE){
        try{ await setupMockCall(); }
        catch(err){ console.error('Mock setup failed', err); }
        return;
      }
      try { await joinCall(); } catch (err) { alert('Connection failed. See logs or token endpoint.'); }
    });

    window.addEventListener('resize', ()=> queueRemoteLayout());

    micBtn.addEventListener('click', () => {
      if (MOCK_MODE){
        micMuted = !micMuted;
        micBtn.innerHTML = micMuted ? "<i class='bx bxs-microphone-off'></i>Unmute" : "<i class='bx bxs-microphone'></i>Mute";
        localStatus.classList.toggle('hidden', !micMuted);
        return;
      }
      if (!localAudioTrack) return;
      micMuted = !micMuted;
      localAudioTrack.setEnabled(!micMuted);
      micBtn.innerHTML = micMuted ? "<i class='bx bxs-microphone-off'></i>Unmute" : "<i class='bx bxs-microphone'></i>Mute";
      localStatus.classList.toggle('hidden', !micMuted);
    });

    camBtn.addEventListener('click', async () => {
      if (MOCK_MODE){
        camOff = !camOff;
        camBtn.innerHTML = camOff ? "<i class='bx bxs-video-off'></i>Show" : "<i class='bx bxs-video'></i>Video";
        if(!camOff && !mockMediaStream){
          try{ await ensureMockStream(); }
          catch(err){ console.warn('Mock camera enable failed', err); }
        }
        renderLocalMockSelf({ name: MOCK_SELF_NAME || 'You', stream: camOff ? null : mockMediaStream, camOff });
        return;
      }
      if (!localVideoTrack) return;
      camOff = !camOff;
      localVideoTrack.setEnabled(!camOff);
      camBtn.innerHTML = camOff ? "<i class='bx bxs-video-off'></i>Show" : "<i class='bx bxs-video'></i>Video";
    });

    // Screen share
    shareBtn.addEventListener('click', async ()=>{
      if(MOCK_MODE){
        console.info('Screen sharing is disabled in mock mode.');
        return;
      }
      if(!isSharing){
        try{
          screenVideoTrack = await AgoraRTC.createScreenVideoTrack({ withAudio: 'auto' });
          await client.unpublish(localVideoTrack);
          localVideoTrack.stop();
          await client.publish(screenVideoTrack);
          screenVideoTrack.play(localContainer);
          isSharing = true; shareBtn.innerHTML = "<i class='bx bx-desktop'></i><span>Stop</span>";
          // When user stops via browser UI
          screenVideoTrack.on('track-ended', async ()=>{
            if(isSharing) await stopShare();
          });
        }catch(e){ logMsg('Share screen failed.', true); }
      }else{
        await stopShare();
      }
    });

    async function stopShare(){
      if(!isSharing) return;
      await client.unpublish(screenVideoTrack);
      screenVideoTrack.stop(); screenVideoTrack.close();
      await client.publish(localVideoTrack);
      localVideoTrack.play(localContainer);
      isSharing = false; shareBtn.innerHTML = "<i class='bx bx-desktop'></i><span>Share</span>";
    }

    // Chat send
    sendBtn.addEventListener('click', sendChat);
    messageBox.addEventListener('keydown', (e)=>{ if(e.key==='Enter'){ e.preventDefault(); sendChat(); } });
    async function sendChat(){
      const rawValue = messageBox.value;
      const text = rawValue ? rawValue.trim() : '';
      if(!text || sendCooldown) return;

      sendCooldown = true;
      sendBtn.disabled = true;
      messageBox.value = '';
      messageBox.focus();

      const localEcho = logMsg(`You: ${text}`, false, true);
      if(localEcho){
        registerLocalEcho(text, localEcho);
        markPendingEcho(localEcho);
      }

      let persisted = null;
      try{
        persisted = await httpSendChat(text);
      }catch(_err){ persisted = null; }

      if(persisted){
        finalizeLocalChatEcho(text, persisted, localEcho);
        scheduleSendReset();
        return;
      }

      let deliveredRealtime = false;
      if(rtmChannel){
        try{
          await rtmChannel.sendMessage({ text });
          deliveredRealtime = true;
        }catch(_err){ deliveredRealtime = false; }
      }

      if(!deliveredRealtime && rtcDataStream){
        try{
          await rtcDataStream.send(text);
          deliveredRealtime = true;
        }catch(_err){ deliveredRealtime = false; }
      }

      queueChatSyncRetry(text, localEcho);
      if(!deliveredRealtime){
        logMsg('Message will send once connection stabilizes.', true);
      }

      scheduleSendReset();
    }

    leaveBtn.addEventListener('click', async () => {
      try{
        if (localAudioTrack) { localAudioTrack.close(); }
        if (localVideoTrack) { localVideoTrack.close(); }
        if (screenVideoTrack) { screenVideoTrack.close(); }
        if (rtmChannel) { await rtmChannel.leave(); }
        if (rtmClient) { await rtmClient.logout(); }
        await client.leave();
      } finally {
        window.location.href = LEAVE_REDIRECT;
      }
    });

    async function handleUserPublished(user, mediaType) {
      const tile = ensureRemoteTile(user);
      await client.subscribe(user, mediaType);
      if (mediaType === 'video' && tile) {
        tile.videoHost.innerHTML = '';
        try { user.videoTrack.play(tile.videoHost); } catch {}
        markTrackState(user.uid, true);
      }
      if (mediaType === 'audio') {
        user.audioTrack.play();
      }
      refreshParticipants();
      queueRemoteLayout();
    }

    function handleUserUnpublished(user, mediaType) {
      if ((mediaType === 'video' || mediaType === undefined) && remoteTiles.has(String(user.uid))) {
        const tile = remoteTiles.get(String(user.uid));
        if (tile) { tile.videoHost.innerHTML = ''; }
        markTrackState(user.uid, false);
      }
      if (mediaType === 'audio' && user.audioTrack) {
        try { user.audioTrack.stop(); } catch {}
      }
      refreshParticipants();
    }

    // Receive RTC data stream messages (real-time fallback)
    if (client && client.on) {
      client.on('stream-message', ({uid, streamId, data}) => {
        try {
          const raw = typeof data === 'string' ? data : (new TextDecoder()).decode(data);
          const normalized = (raw ?? '').trim();
          if(!normalized) return;
          const isSelf = String(uid) === String(localUid);
          if(isSelf && resolveLocalEcho(normalized)){
            return;
          }
          const tile = remoteTiles.get(String(uid));
          const who = isSelf ? 'You' : (tile?.name?.textContent || getRemoteLabel(uid));
          const entry = logMsg(`${who}: ${normalized}`, false, isSelf);
          if(entry && isSelf && entry.dataset.origin !== 'local'){ entry.dataset.origin = entry.dataset.origin || 'history'; }
        } catch {}
      });
    }
  }
  </script>

</body>
</html>
