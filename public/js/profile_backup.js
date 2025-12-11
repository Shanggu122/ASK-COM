function toggleChat() {
    const chat = document.getElementById("chatOverlay");
    chat.classList.toggle("open");
}

function sendMessage() {
    const input = document.getElementById("userInput");
    const message = input.value.trim();
    if (message === "") return;

    const chatBody = document.getElementById("chatBody");

    // Add user's message
    const userMsg = document.createElement("div");
    userMsg.className = "message user";
    userMsg.textContent = message;
    chatBody.appendChild(userMsg);

    // Fake bot reply
    const botMsg = document.createElement("div");
    botMsg.className = "message bot";
    botMsg.textContent =
        "Thank you for your message. I’ll get back to you soon!";
    setTimeout(() => {
        chatBody.appendChild(botMsg);
        chatBody.scrollTop = chatBody.scrollHeight;
    }, 600);

    input.value = "";
    chatBody.scrollTop = chatBody.scrollHeight;
}

function handleKey(e) {
    if (e.key === "Enter") {
        sendMessage();
    }
}

// Update existing click handlers to trigger overlays
document.querySelectorAll('a[href="#"]').forEach((anchor) => {
    anchor.addEventListener("click", function (e) {
        e.preventDefault();
        if (this.textContent.includes("Change Password")) {
            toggleOverlay("passwordOverlay");
        } else if (this.textContent.includes("Email Notification")) {
            toggleOverlay("notificationOverlay");
        }
    });
});
function togglePanel(panelId) {
    const panel = document.getElementById(panelId);
    panel.classList.toggle("open");
}

function togglePasswordVisibility(inputId, icon) {
    const input = document.getElementById(inputId);
    if (input.type === "password") {
        input.type = "text";
        icon.classList.remove("bx-show");
        icon.classList.add("bx-hide");
    } else {
        input.type = "password";
        icon.classList.remove("bx-hide");
        icon.classList.add("bx-show");
    }
}
function loadChat(name) {
    document.getElementById("chat-person").innerText = name;
    document.getElementById("chat-body").innerHTML = `
      <div class="message received">Hi ${name}</div>
      <div class="message sent">Good Morning!</div>
    `;
}

function startVideoCall() {
    alert("Video call started!");
}

function sendMessage() {
    const input = document.getElementById("message-input");
    const text = input.value.trim();
    if (text !== "") {
        const chatBody = document.getElementById("chat-body");
        const message = document.createElement("div");
        message.className = "message sent";
        message.textContent = text;
        chatBody.appendChild(message);
        input.value = "";
        chatBody.scrollTop = chatBody.scrollHeight;
    }
}
// Add event listener to handle "Enter" key for sending messages
document.getElementById("message").addEventListener("keydown", function (e) {
    if (e.key === "Enter" && !e.shiftKey) {
        // Detect Enter key (without Shift key for multi-line messages)
        e.preventDefault(); // Prevent newline
        sendMessage(); // Call send message function
    }
});

// Function to send message
function sendMessage() {
    const input = document.getElementById("message");
    const text = input.value.trim();
    if (text !== "") {
        const chatBody = document.getElementById("chat-body");
        const message = document.createElement("div");
        message.className = "message sent";
        message.textContent = text;
        chatBody.appendChild(message);
        input.value = ""; // Clear input field after sending message
        chatBody.scrollTop = chatBody.scrollHeight; // Scroll to bottom of chat body
    }
}

// Add this function to ensure you can enter backspace and edit messages
function handleKey(e) {
    if (e.key === "Enter" && !e.shiftKey) {
        // Only send message on Enter (not Shift+Enter)
        sendMessage();
    }
}
