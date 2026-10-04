<?php
// Create directories
if (!file_exists('assets')) mkdir('assets', 0777, true);
if (!file_exists('assets/css')) mkdir('assets/css', 0777, true);
if (!file_exists('assets/js')) mkdir('assets/js', 0777, true);

// Create chatbot.css
$cssContent = '/* Chatbot Styles - Urban Wheels */
.chat-button {
    position: fixed;
    bottom: 30px;
    right: 30px;
    width: 60px;
    height: 60px;
    background: linear-gradient(135deg, #667eea, #764ba2);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    z-index: 1000;
    box-shadow: 0 5px 20px rgba(0,0,0,0.2);
    transition: all 0.3s ease;
    border: none;
}
.chat-button:hover { transform: scale(1.1); }
.chat-button i { font-size: 28px; color: white; }
.chat-window {
    position: fixed;
    bottom: 100px;
    right: 30px;
    width: 380px;
    height: 500px;
    background: white;
    border-radius: 15px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.2);
    display: none;
    flex-direction: column;
    z-index: 1001;
    overflow: hidden;
}
.chat-header {
    background: linear-gradient(135deg, #667eea, #764ba2);
    color: white;
    padding: 15px 20px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}
.chat-messages { flex: 1; overflow-y: auto; padding: 15px; background: #f5f5f5; }
.message-content { display: flex; align-items: flex-start; gap: 10px; }
.user-message .message-content { flex-direction: row-reverse; }
.user-message .message-content p { background: #667eea; color: white; border-radius: 18px 18px 4px 18px; }
.bot-message .message-content p { background: white; color: #333; border-radius: 18px 18px 18px 4px; }
.message-content i {
    width: 32px; height: 32px;
    background: #667eea;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
}
.message-content p { max-width: 75%; padding: 10px 15px; margin: 0; font-size: 13px; }
.chat-input-area { padding: 15px; background: white; border-top: 1px solid #eee; display: flex; gap: 10px; }
.chat-input-area input { flex: 1; padding: 10px 15px; border: 1px solid #ddd; border-radius: 25px; }
.chat-input-area button {
    width: 40px; height: 40px;
    background: linear-gradient(135deg, #667eea, #764ba2);
    border: none; border-radius: 50%;
    color: white; cursor: pointer;
}
.suggested-questions { padding: 10px 15px; background: #fafafa; border-top: 1px solid #eee; display: flex; flex-wrap: wrap; gap: 8px; }
.suggested-q { background: #f0f0f0; padding: 5px 12px; border-radius: 20px; font-size: 11px; cursor: pointer; color: #666; }
.suggested-q:hover { background: #667eea; color: white; }
@media (max-width: 480px) {
    .chat-window { width: calc(100vw - 40px); right: 20px; height: 450px; }
    .chat-button { bottom: 20px; right: 20px; width: 50px; height: 50px; }
}
';
file_put_contents('assets/css/chatbot.css', $cssContent);

// Create chatbot.js
$jsContent = '// FAQ Chatbot - Urban Wheels
const faqResponses = {
    "how to book|booking process|how do i book": "To book a vehicle: 1) Login to your account, 2) Browse vehicles, 3) Select dates, 4) Complete booking form, 5) Make payment to confirm.",
    "payment methods|how to pay|payment|mpesa": "We accept M-Pesa, Credit/Debit Cards, Bank Transfer, and Cash on Pickup.",
    "cancellation policy|cancel booking|how to cancel": "Free cancellation up to 7 days before pickup. 25% fee for 3-6 days. 50% fee for 1-2 days. No refund for last-minute.",
    "requirements|documents needed|driver license|age requirement": "Valid driver\'s license, National ID/Passport, Minimum age 23 years.",
    "working hours|opening hours|support": "Mon-Fri: 8am-8pm, Sat: 9am-6pm, Sun: 10am-4pm. 24/7 emergency support.",
    "vehicles available|what vehicles|fleet": "We offer BMW, Mercedes, Toyota, Honda, and more. Check our Vehicles page!",
    "delivery|pickup location|airport pickup": "Pickup locations: Nairobi CBD, JKIA Airport, Westlands, Karen. Delivery available for a fee.",
    "insurance|coverage|excess": "All rentals include basic insurance. Full insurance available as add-on for extra protection.",
    "how to register|forgot password|login issues": "Click Sign Up/Login on homepage. Forgot password? Use the reset link on login page.",
    "default": "I can help with bookings, payments, cancellations, requirements, and more. What would you like to know?"
};

function getBotResponse(message) {
    const msg = message.toLowerCase().trim();
    if(msg.match(/^(hi|hello|hey)$/i)) return "Hello! Welcome to Urban Wheels! How can I help you today?";
    if(msg.match(/^(thanks|thank you)$/i)) return "You\'re welcome! Anything else I can help with?";
    if(msg.match(/^(bye|goodbye)$/i)) return "Thank you for choosing Urban Wheels! Have a great day! 👋";
    for(const [key, response] of Object.entries(faqResponses)) {
        const keywords = key.split("|");
        for(const kw of keywords) if(msg.includes(kw)) return response;
    }
    return faqResponses.default;
}

let isChatOpen = false;
function toggleChat() {
    isChatOpen = !isChatOpen;
    const chatWindow = document.getElementById("chatWindow");
    chatWindow.style.display = isChatOpen ? "flex" : "none";
}
function sendMessage() {
    const input = document.getElementById("chatInput");
    const msg = input.value.trim();
    if(!msg) return;
    addUserMessage(msg);
    setTimeout(() => addBotMessage(getBotResponse(msg)), 500);
    input.value = "";
}
function addUserMessage(msg) {
    const container = document.getElementById("chatMessages");
    const div = document.createElement("div");
    div.className = "chat-message user-message";
    div.innerHTML = `<div class="message-content"><i class="fas fa-user"></i><p>${escapeHtml(msg)}</p></div>`;
    container.appendChild(div);
    scrollToBottom();
}
function addBotMessage(msg) {
    const container = document.getElementById("chatMessages");
    const div = document.createElement("div");
    div.className = "chat-message bot-message";
    div.innerHTML = `<div class="message-content"><i class="fas fa-robot"></i><p>${escapeHtml(msg)}</p></div>`;
    container.appendChild(div);
    scrollToBottom();
}
function scrollToBottom() {
    const container = document.getElementById("chatMessages");
    container.scrollTop = container.scrollHeight;
}
function escapeHtml(text) {
    const div = document.createElement("div");
    div.textContent = text;
    return div.innerHTML;
}
document.addEventListener("DOMContentLoaded", function() {
    const input = document.getElementById("chatInput");
    if(input) input.addEventListener("keypress", function(e) { if(e.key === "Enter") sendMessage(); });
});
';
file_put_contents('assets/js/chatbot.js', $jsContent);

echo "<h1 style='color:green'>✓ Chatbot files created successfully!</h1>";
echo "<p>Files created:</p><ul>";
echo "<li>assets/css/chatbot.css</li>";
echo "<li>assets/js/chatbot.js</li>";
echo "</ul>";
echo "<p>Now add the chatbot code to your pages (see above instructions).</p>";
echo "<p><a href='index.php'>Go to Homepage</a> to see the chatbot!</p>";
?>