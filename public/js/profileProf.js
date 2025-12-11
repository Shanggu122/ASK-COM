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
        icon.classList.remove("bx-hide");
        icon.classList.add("bx-show");
    } else {
        input.type = "password";
        icon.classList.remove("bx-show");
        icon.classList.add("bx-hide");
    }
}

// Enhanced password validation function with prioritized checking
function validatePasswordForm(form) {
    const oldPassword = form.querySelector("#oldPassword").value;
    const newPassword = form.querySelector("#newPassword").value;
    const confirmPassword = form.querySelector(
        "#newPassword_confirmation"
    ).value;

    // Check if all fields are filled
    if (!oldPassword) {
        showNotification("Please enter your current password.", true);
        return false;
    }

    if (!newPassword) {
        showNotification("Please enter your new password.", true);
        return false;
    }

    if (!confirmPassword) {
        showNotification("Please confirm your new password.", true);
        return false;
    }

    // NOTE: We cannot validate current password on client-side since we don't have access to it
    // The server will handle current password validation as the highest priority

    // Check minimum length for new password
    if (newPassword.length < 8) {
        showNotification(
            "Your new password is too short. It must be at least 8 characters long.",
            true
        );
        return false;
    }

    // Check if passwords match
    if (newPassword !== confirmPassword) {
        showNotification(
            "Your new password and confirmation password do not match. Please re-enter them correctly.",
            true
        );
        return false;
    }

    // Check if new password is different from old password
    if (newPassword === oldPassword) {
        showNotification(
            "Your new password must be different from your current password.",
            true
        );
        return false;
    }

    return true;
}

// Add form submission validation
document.addEventListener("DOMContentLoaded", function () {
    const passwordForm = document.querySelector(
        'form[action*="changePassword"]'
    );
    if (passwordForm) {
        passwordForm.addEventListener("submit", function (e) {
            if (!validatePasswordForm(this)) {
                e.preventDefault();
                return false;
            }
        });

        // Real-time validation for password confirmation
        const confirmPasswordInput = document.getElementById(
            "newPassword_confirmation"
        );
        const newPasswordInput = document.getElementById("newPassword");

        if (confirmPasswordInput && newPasswordInput) {
            confirmPasswordInput.addEventListener("input", function () {
                const newPassword = newPasswordInput.value;
                const confirmPassword = this.value;

                if (confirmPassword && newPassword !== confirmPassword) {
                    this.style.borderColor = "#ff6b6b";
                } else {
                    this.style.borderColor = "";
                }
            });

            newPasswordInput.addEventListener("input", function () {
                if (this.value.length > 0 && this.value.length < 8) {
                    this.style.borderColor = "#ff6b6b";
                } else {
                    this.style.borderColor = "";
                }
            });
        }
    }
});
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
const messageInputElement = document.getElementById("message-input");
if (messageInputElement) {
    messageInputElement.addEventListener("keydown", function (e) {
        if (e.key === "Enter" && !e.shiftKey) {
            // Detect Enter key (without Shift key for multi-line messages)
            e.preventDefault(); // Prevent newline
            sendMessage(); // Call send message function
        }
    });
}

// Function to send message
function sendMessage() {
    const input = document.getElementById("message-input");
    if (!input) {
        // If message-input doesn't exist, try userInput (profile page)
        const userInput = document.getElementById("userInput");
        if (!userInput) return;

        const text = userInput.value.trim();
        if (text !== "") {
            const chatBody = document.getElementById("chatBody");
            if (!chatBody) return;

            const message = document.createElement("div");
            message.className = "message user";
            message.textContent = text;
            chatBody.appendChild(message);
            userInput.value = "";
            chatBody.scrollTop = chatBody.scrollHeight;
        }
        return;
    }

    const text = input.value.trim();
    if (text !== "") {
        const chatBody = document.getElementById("chat-body");
        if (!chatBody) return;

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
