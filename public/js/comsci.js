// === Modal Open/Close ===
function openModal() {
    document.getElementById('consultationModal').style.display = 'flex';
}

function closeModal() {
    document.getElementById('consultationModal').style.display = 'none';
}

function handleKey(e) {
    if (e.key === 'Enter') {
        sendMessage();
    }
}

// Update existing click handlers to trigger overlays
document.querySelectorAll('a[href="#"]').forEach((anchor) => {
    anchor.addEventListener('click', function (e) {
        e.preventDefault();
        if (this.textContent.includes('Change Password')) {
            toggleOverlay('passwordOverlay');
        } else if (this.textContent.includes('Email Notification')) {
            toggleOverlay('notificationOverlay');
        }
    });
});
function togglePanel(panelId) {
    const panel = document.getElementById(panelId);
    panel.classList.toggle('open');
}

function togglePasswordVisibility(inputId, icon) {
    const input = document.getElementById(inputId);
    if (input.type === 'password') {
        input.type = 'text';
        icon.classList.remove('bx-hide');
        icon.classList.add('bx-show');
    } else {
        input.type = 'password';
        icon.classList.remove('bx-show');
        icon.classList.add('bx-hide');
    }
}
function loadChat(name) {
    document.getElementById('chat-person').innerText = name;
    document.getElementById('chat-body').innerHTML = `
      <div class="message received">Hi ${name}</div>
      <div class="message sent">Good Morning!</div>
    `;
}

function startVideoCall() {
    alert('Video call started!');
}

function sendMessage() {
    const input = document.getElementById('message-input');
    const text = input.value.trim();
    if (text !== '') {
        const chatBody = document.getElementById('chat-body');
        const message = document.createElement('div');
        message.className = 'message sent';
        message.textContent = text;
        chatBody.appendChild(message);
        input.value = '';
        chatBody.scrollTop = chatBody.scrollHeight;
    }
}
// Add event listener to handle "Enter" key for sending messages
// Corrected
document.getElementById('message').addEventListener('keydown', function (e) {
    if (e.key === 'Enter' && !e.shiftKey) {
        e.preventDefault();
        sendMessage();
    }
});

// Function to send message
function sendMessage() {
    const input = document.getElementById('message');
    const text = input.value.trim();
    if (text !== '') {
        const chatBody = document.getElementById('chat-body');
        const message = document.createElement('div');
        message.className = 'message sent';
        message.textContent = text;
        chatBody.appendChild(message);
        input.value = ''; // Clear input field after sending message
        chatBody.scrollTop = chatBody.scrollHeight; // Scroll to bottom of chat body
    }
}

// Add this function to ensure you can enter backspace and edit messages
function handleKey(e) {
    if (e.key === 'Enter' && !e.shiftKey) {
        // Only send message on Enter (not Shift+Enter)
        sendMessage();
    }
}

function toggleChat() {
    document.getElementById('chatOverlay').classList.toggle('open');
}

const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
const chatForm = document.getElementById('chatForm');
const input = document.getElementById('message');
const chatBody = document.getElementById('chatBody');

chatForm.addEventListener('submit', async function (e) {
    e.preventDefault();
    const text = input.value.trim();
    if (!text) return;

    // show user message…
    const um = document.createElement('div');
    um.classList.add('message', 'user');
    um.innerText = text;
    chatBody.appendChild(um);

    chatBody.scrollTop = chatBody.scrollHeight;
    input.value = '';

    // send to Laravel
    const res = await fetch('/chat', {
        method: 'POST',
        credentials: 'same-origin',
        headers: {
            Accept: 'application/json',
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken,
        },
        body: JSON.stringify({ message: text }),
    });

    if (!res.ok) {
        const err = await res.json();
        const bm = document.createElement('div');
        bm.classList.add('message', 'bot');
        bm.innerText = err.message || 'Server error.';
        chatBody.appendChild(bm);
        return;
    }

    // render bot reply…
    const { reply } = await res.json();
    const bm = document.createElement('div');
    bm.classList.add('message', 'bot');
    bm.innerText = reply;
    chatBody.appendChild(bm);
    chatBody.scrollTop = chatBody.scrollHeight;
});

// Set min date on a #calendar input if present
window.addEventListener('load', function () {
    const cal = document.getElementById('calendar');
    if (cal) {
        const today = new Date().toISOString().split('T')[0];
        cal.setAttribute('min', today);
    }
});

//Sidebar nav active
// const active = document.getElementsByClassName("nav-active");

// console.log(active);

document.addEventListener('DOMContentLoaded', function () {
    // ...existing code...

    // Search functionality
    const searchInput = document.getElementById('searchInput');
    if (searchInput) {
        searchInput.addEventListener('input', function () {
            const filter = searchInput.value.toLowerCase();
            const cards = document.querySelectorAll('.profile-card');
            cards.forEach((card) => {
                const name = (card.getAttribute('data-name') || '').toLowerCase();
                card.style.display = name.includes(filter) ? '' : 'none';
            });
        });
    }
});

function openModal() {
    document.getElementById('consultationModal').style.display = 'flex';
    document.body.classList.add('modal-open');
}
function closeModal() {
    document.getElementById('consultationModal').style.display = 'none';
    document.body.classList.remove('modal-open');
}
