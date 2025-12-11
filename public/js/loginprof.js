document
    .getElementById("toggle-password")
    .addEventListener("click", function () {
        const passwordInput = document.getElementById("password");
        const icon = this;
        if (passwordInput.type === "password") {
            passwordInput.type = "text";
            icon.classList.replace("bx-hide", "bx-show");
        } else {
            passwordInput.type = "password";
            icon.classList.replace("bx-show", "bx-hide");
        }
    });

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
