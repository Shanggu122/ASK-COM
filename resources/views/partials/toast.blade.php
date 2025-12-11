<div id="toast-container" aria-live="polite" aria-atomic="true" class="toast-wrapper">
  <!-- Toasts will be injected dynamically -->
</div>

<script>
  window.ASCCToast = (function(){
    const containerId = 'toast-container';
    function ensureContainer(){
      let el = document.getElementById(containerId);
      if(!el){
        el = document.createElement('div');
        el.id = containerId;
        el.className = 'toast-wrapper';
        document.body.appendChild(el);
      }
      return el;
    }
    function show(message, type='success', timeout=3500){
      const container = ensureContainer();
      const toast = document.createElement('div');
      toast.className = 'ascc-toast ascc-toast-' + type;
      toast.innerHTML = `<span class="ascc-toast-msg">${message}</span><button class="ascc-toast-close" aria-label="Close">&times;</button>`;
      container.appendChild(toast);
      const close = ()=>{ toast.classList.add('hide'); setTimeout(()=> toast.remove(), 250); };
      toast.querySelector('.ascc-toast-close').addEventListener('click', close);
      setTimeout(close, timeout);
    }
    return { show };
  })();
  @if(session('status'))
    document.addEventListener('DOMContentLoaded', function(){
      ASCCToast.show(@json(session('status')), 'success');
    });
  @endif
</script>