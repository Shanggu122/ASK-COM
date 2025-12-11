function toggleChat() {
    const chat = document.getElementById("chatOverlay");
    chat.classList.toggle("open");
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

// Note: sendMessage and handleKey functions are now handled by the chatbot implementation in the HTML file
// The chat functionality is now integrated with the AI chatbot API
