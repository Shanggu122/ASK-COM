// Common chat utilities (student & professor)
window.ChatCommon = (function(){
  const state = {
    unreadMap: {}, // key: otherId (prof or stud)
    onlineStudents: new Set(),
    onlineProfessors: new Set(),
    typingPairs: new Map(), // key: studId-profId -> { sender,is_typing, ts }
    studentId: null,
    professorId: null,
    pusher: null,
    channel: null,
  };

  function initPusher(appKey, cluster, studentId, professorId, debug=false){
    state.studentId = studentId; state.professorId = professorId;
    if(debug){ Pusher.logToConsole = true; }
    state.pusher = new Pusher(appKey, { cluster });
    state.channel = state.pusher.subscribe('chat');
    if(debug){
      state.channel.bind_global((event, data)=>{
        console.log('[ChatCommon] Event:', event, data);
        // Fallback: if event name is fully-qualified class we still try to route
        if(event.endsWith('MessageSent') && !data._handled){
          if(state._onMessage){ state._onMessage(data); data._handled=true; }
        }
        if(event.endsWith('TypingIndicator') && !data._handled){
          if(state._onTyping){ state._onTyping(data); data._handled=true; }
        }
      });
      state.pusher.connection.bind('state_change', states=>console.log('[ChatCommon] Connection state', states));
      state.pusher.connection.bind('error', err=>console.warn('[ChatCommon] Pusher error', err));
    }
  }

  function onMessage(callback){
    if(!state.channel) return;
    state._onMessage = callback;
    state.channel.bind('MessageSent', data => callback(data));
  }

  function onTyping(callback){
    if(!state.channel) return;
    state._onTyping = callback;
    state.channel.bind('TypingIndicator', data => callback(data));
  }

  function onPresence(callback){
    if(!state.channel) return;
    state._onPresence = callback;
    state.channel.bind('PresencePing', data => callback(data));
  }

  function onPairRead(callback){
    if(!state.channel) return;
    state._onPairRead = callback;
    state.channel.bind('PairRead', data => callback(data));
  }

  function fetchUnread(isProfessor){
    const url = isProfessor ? '/chat/unread/professor' : '/chat/unread/student';
    return fetch(url).then(r=>r.json()).then(rows => {
      state.unreadMap = {};
      rows.forEach(r => {
        if(r.Prof_ID){ state.unreadMap['prof-'+r.Prof_ID] = r.unread; }
        if(r.Stud_ID){ state.unreadMap['stud-'+r.Stud_ID] = r.unread; }
      });
      return state.unreadMap;
    }).catch(()=>{});
  }

  function fetchPresence(){
    return fetch('/chat/presence/online')
      .then(r=>r.json())
      .then(data => {
        state.onlineStudents = new Set(data.students || []);
        state.onlineProfessors = new Set(data.professors || []);
        return data;
      }).catch(()=>{});
  }

  function pingPresence(){
    return fetch('/chat/presence/ping', {method:'POST', headers:{'X-CSRF-TOKEN':window.csrfToken}});
  }

  function sendTyping(studId, profId, sender, isTyping){
    const fd = new FormData();
    fd.append('stud_id', studId); fd.append('prof_id', profId); fd.append('sender', sender); fd.append('is_typing', isTyping ? '1':'0');
    fd.append('_token', window.csrfToken);
    return fetch('/chat/typing',{method:'POST', body:fd});
  }

  function formatRelative(ts){
    const d = new Date(ts); if(isNaN(d)) return '';
    return d.toLocaleTimeString([], {hour:'2-digit',minute:'2-digit'});
  }

  return { state, initPusher, onMessage, onTyping, onPresence, onPairRead, fetchUnread, fetchPresence, pingPresence, sendTyping, formatRelative };
})();
