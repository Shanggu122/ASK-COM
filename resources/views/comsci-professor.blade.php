<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>Computer Science Department</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
  <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
  <link rel="stylesheet" href="{{ asset('css/comsci.css') }}">
  <link rel="stylesheet" href="{{ asset('css/logout-confirm.css') }}">
  <script src="{{ asset('js/logout-confirm.js') }}" defer></script>
</head>
<body>
  @include('components.navbarprof')
  
  <div class="main-content view-only">
    <div class="header">
      <div>
        <h1>Computer Science Department</h1>
        <div class="subtitle-view-only"><em>Faculty directory (view only)</em></div>
      </div>
    </div>
    
    <div class="search-container">
      <input type="text" id="searchInput" placeholder="Search..." onkeyup="filterColleagues()"
        autocomplete="off" spellcheck="false" maxlength="100"
        pattern="[A-Za-z0-9 .,@_-]{0,100}" aria-label="Search colleagues">
    </div>
    
    <div class="profile-cards-grid">
      @if($colleagues->count() > 0)
        @foreach($colleagues as $colleague)
          <div class="profile-card" data-name="{{ $colleague->Name }}">
            <img src="{{ $colleague->profile_photo_url ?? asset('images/dprof.jpg') }}" alt="Profile Picture">
            <div class="profile-name">{{ $colleague->Name }}</div>
          </div>
        @endforeach
      @else
        <div class="no-colleagues">
          <p>No other colleagues in this department.</p>
        </div>
      @endif
    </div>
  <div id="noResults" class="no-results-message">NO PROFESSOR FOUND</div>
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
      <input type="text" id="message" placeholder="Type your message" required>
      <button type="submit">Send</button>
    </form>
  </div>
  
  <script src="{{ asset('js/comsci.js') }}"></script>
  <script>
    function toggleChat(){
      const overlay = document.getElementById('chatOverlay');
      if(!overlay) return;
      overlay.classList.toggle('open');
      const isOpen = overlay.classList.contains('open');
      document.body.classList.toggle('chat-open', isOpen);
      const bell = document.getElementById('mobileNotificationBell');
      if(bell){ bell.style.zIndex = isOpen ? '0' : ''; bell.style.pointerEvents = isOpen ? 'none' : ''; bell.style.opacity = isOpen ? '0' : ''; }
    }
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

    function filterColleagues() {
      const searchInput = document.getElementById('searchInput');
      const cleaned = sanitize(searchInput.value);
      if (searchInput.value !== cleaned) searchInput.value = cleaned;
      const filter = cleaned.toLowerCase();
      const cards = document.querySelectorAll('.profile-card');
      let visible = 0;

      cards.forEach(card => {
        const name = (card.getAttribute('data-name') || '').toLowerCase();
        const show = !filter || name.includes(filter);
        if (show) {
          card.style.removeProperty('display'); // keep CSS layout (flex in grid)
          visible++;
        } else {
          card.style.display = 'none';
        }
      });

      const msg = document.getElementById('noResults');
      if (msg) {
        if (cleaned && visible === 0) {
          msg.style.display = 'block';
        } else {
          msg.style.display = 'none';
        }
      }
    }

    // Add Enter key functionality for chat form
    document.addEventListener('DOMContentLoaded', function() {
  const messageInput = document.getElementById('message');
      if (messageInput) {
        // Remove any existing event listeners first
        messageInput.removeEventListener('keydown', handleEnterKey);
        
        // Add our Enter key handler
        messageInput.addEventListener('keydown', handleEnterKey);
      }
      
      // Add Enter key functionality for search input as well
      const searchInput = document.getElementById('searchInput');
      if (searchInput) {
        searchInput.addEventListener('keydown', function(event) {
          if (event.key === 'Enter') {
            event.preventDefault();
            filterColleagues();
          }
        });
        searchInput.addEventListener('input', filterColleagues);
      }
    });
    
    // Define the Enter key handler function
    function handleEnterKey(event) {
      if (event.key === 'Enter') {
        event.preventDefault();
        const chatForm = document.getElementById('chatForm');
        if (chatForm) {
          const msg = document.getElementById('message');
          if(msg){
            const cleaned = sanitize(msg.value);
            if(cleaned) { msg.value = cleaned; chatForm.requestSubmit(); }
          }
        }
      }
    }

    // Chatbot (dashboard parity): quick replies + backend call
    document.addEventListener('DOMContentLoaded', function(){
      const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
      const form = document.getElementById('chatForm');
      const msg = document.getElementById('message');
      const chatBody = document.getElementById('chatBody');
      const quickReplies = document.getElementById('quickReplies');
      const quickRepliesToggle = document.getElementById('quickRepliesToggle');

      if(msg){ msg.setAttribute('maxlength','250'); msg.setAttribute('autocomplete','off'); msg.setAttribute('spellcheck','false'); }
      function sendQuick(text){ if(!text||!msg||!form) return; msg.value=text; form.dispatchEvent(new Event('submit')); }
      quickReplies?.addEventListener('click',(e)=>{ const btn=e.target.closest('.quick-reply'); if(btn){ sendQuick(btn.dataset.message); } });
      quickRepliesToggle?.addEventListener('click',()=>{ if(quickReplies){ quickReplies.style.display='flex'; quickRepliesToggle.style.display='none'; } });

      if(form && msg && chatBody){
        form.addEventListener('submit', async function(e){
          e.preventDefault();
          const text = sanitize(msg.value);
          if(!text) return;

          if(quickReplies && quickReplies.style.display !== 'none'){
            quickReplies.style.display='none';
            if(quickRepliesToggle) quickRepliesToggle.style.display='flex';
          }

          const um = document.createElement('div'); um.classList.add('message','user'); um.innerText=text; chatBody.appendChild(um); chatBody.scrollTop = chatBody.scrollHeight; msg.value='';

          try{
            const res = await fetch('/chat', { method:'POST', credentials:'same-origin', headers:{ 'Accept':'application/json','Content-Type':'application/json','X-CSRF-TOKEN':csrfToken }, body: JSON.stringify({ message: text }) });
            let reply='Server error.';
            if(res.ok){ const data=await res.json(); reply=data.reply||reply; } else { try{ const err=await res.json(); reply=err.message||reply; }catch(_){} }
            const bm=document.createElement('div'); bm.classList.add('message','bot'); bm.innerText=reply; chatBody.appendChild(bm); chatBody.scrollTop = chatBody.scrollHeight;
          }catch(_){ const bm=document.createElement('div'); bm.classList.add('message','bot'); bm.innerText='Network error.'; chatBody.appendChild(bm); }
        });
      }
    });
  </script>
</body>
</html> 