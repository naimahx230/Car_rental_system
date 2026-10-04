// FAQ Chatbot - Urban Wheels Car Rental
// Comprehensive FAQ responses for customer support

const faqResponses = {
    // Booking Related FAQs
    "how to book|booking|book a car": "To book a vehicle: 1️⃣ Login to your account, 2️⃣ Browse our available vehicles, 3️⃣ Select your desired vehicle, 4️⃣ Choose pickup and return dates, 5️⃣ Complete the booking form, 6️⃣ Make payment to confirm your booking. Would you like me to guide you through any specific step?",
    
    "booking process|how do i book|make a booking": "The booking process is simple: 📝 Login → 🚗 Choose Vehicle → 📅 Select Dates → 📋 Fill Details → 💳 Make Payment → ✅ Receive Confirmation. You can start booking right now by visiting our Vehicles page!",
    
    // Payment Related FAQs
    "payment methods|how to pay|pay": "We accept multiple payment methods: 💳 Credit/Debit Cards (Visa, Mastercard), 📱 M-Pesa, 🏦 Bank Transfer, and 💵 Cash on Pickup. All payments are secure and encrypted.",
    
    "mpesa|lipa na mpesa": "To pay via M-Pesa: 1️⃣ Go to M-Pesa on your phone, 2️⃣ Select 'Lipa na M-Pesa', 3️⃣ Enter Business Number, 4️⃣ Enter your booking number as account number, 5️⃣ Enter amount, 6️⃣ Enter your PIN. You'll receive confirmation immediately!",
    
    "deposit|security deposit": "A refundable security deposit may be required depending on the vehicle. The deposit amount ranges from KES 5,000 to KES 20,000 and is refunded within 7 days after vehicle return (minus any damages).",
    
    // Cancellation Related FAQs
    "cancellation policy|cancel|refund": "📋 Free cancellation up to 7 days before pickup. ⚠️ 25% fee for 3-6 days notice. ⚠️ 50% fee for 1-2 days notice. ❌ No refund for last-minute cancellations. To cancel, go to 'My Bookings' and click 'Cancel Booking'.",
    
    "how to cancel|cancel booking": "To cancel a booking: 1️⃣ Login to your account, 2️⃣ Go to 'My Bookings', 3️⃣ Find your booking, 4️⃣ Click 'Cancel Booking', 5️⃣ Confirm cancellation. Cancellation fees apply based on notice period.",
    
    // Vehicle Related FAQs
    "vehicles available|what vehicles|car types": "We offer a wide range of vehicles including: 🚙 SUVs (Toyota RAV4, Fortuner), 🚗 Sedans (Honda Civic, Mercedes C-Class), 🏎️ Luxury cars (BMW X5), and 🚘 Economy vehicles. Check our Vehicles page for current availability and pricing!",
    
    "vehicle condition|maintenance": "All our vehicles are 🛠️ regularly serviced, ✅ well-maintained, 🧼 thoroughly cleaned, and 🔧 inspected before each rental. Your safety is our priority!",
    
    "fuel policy|fuel": "⛽ Vehicles are provided with a FULL tank of fuel. Please return with a FULL tank to avoid refueling charges. We recommend refueling at our partner stations for the best rates.",
    
    "mileage|unlimited mileage": "Most rentals include 🆓 UNLIMITED MILEAGE! Some economy vehicles may have daily mileage limits - check your booking details for specific terms.",
    
    // Requirements FAQs
    "requirements|documents needed": "To rent a vehicle you need: 1️⃣ Valid driver's license (minimum 2 years old), 2️⃣ National ID or Passport, 3️⃣ Minimum age of 23 years, 4️⃣ Valid payment method for deposit. International visitors need an International Driving Permit (IDP).",
    
    "age requirement|minimum age": "🚫 Minimum age to rent is 23 years. 👨‍🦱 Drivers under 25 may incur a young driver surcharge of KES 1,000/day. Maximum age limit is 70 years.",
    
    "driver license|license": "✅ A valid driver's license is required. International customers need an International Driving Permit (IDP) along with their home country license. Digital copies are accepted for online bookings.",
    
    // Hours & Contact FAQs
    "working hours|opening hours": "🕐 Our operating hours: Monday-Friday: 8:00 AM - 8:00 PM, Saturday: 9:00 AM - 6:00 PM, Sunday: 10:00 AM - 4:00 PM. 📞 24/7 emergency support available for roadside assistance!",
    
    "contact|support|help": "📞 Phone: +254 700 000 000\n✉️ Email: info@urbanwheels.com\n💬 Live Chat: Available during business hours\n📍 Visit us: Nairobi CBD, 2nd Floor, Urban Towers\n📱 WhatsApp: +254 700 000 001",
    
    // Delivery & Pickup FAQs
    "delivery|home delivery": "🚚 Yes! We offer vehicle delivery to your location for KES 1,500-3,500 depending on distance. The vehicle will be delivered within 2 hours of booking confirmation.",
    
    "pickup location|where to pick up": "📍 Pickup locations include:\n• Nairobi CBD (Main Office)\n• JKIA Airport (Terminal 1 & 2)\n• Westlands\n• Karen\n• Mombasa Road\nYou can select your preferred location during booking!",
    
    "airport pickup|airport": "✈️ Yes! We offer pickup and drop-off at Jomo Kenyatta International Airport (JKIA). Select 'Airport' as your pickup location when booking. Our representative will meet you at the arrivals terminal.",
    
    // Insurance FAQs
    "insurance|coverage": "🛡️ All rentals include basic insurance coverage (Third Party Liability). Comprehensive insurance is available as an add-on service (KES 1,000-2,500/day) covering collision, theft, and damage.",
    
    "excess|deductible": "💰 Standard excess (deductible) is KES 30,000. You can reduce this to KES 10,000 by purchasing our 'Premium Protection' package for an additional KES 1,000/day.",
    
    // Account FAQs
    "how to register|create account|sign up": "📝 To create an account: 1️⃣ Click 'Sign Up' on the homepage, 2️⃣ Fill in your details (name, email, phone), 3️⃣ Create a password, 4️⃣ Verify your email, 5️⃣ Complete your profile. It's free and takes only 2 minutes!",
    
    "forgot password|reset password": "🔑 Forgot your password? Click 'Forgot password?' on the login page, enter your email, and we'll send you a reset link. Check your spam folder if you don't see it within 5 minutes.",
    
    "login issues|can't login": "Having trouble logging in? Try these steps: 1️⃣ Reset your password, 2️⃣ Clear browser cache, 3️⃣ Try a different browser, 4️⃣ Contact support if issues persist. We're here to help!",
    
    // Rental Period FAQs
    "rental period|minimum rental": "📅 Minimum rental period is 1 day (24 hours). Long-term rentals (7+ days) get 10% discount, 14+ days get 15% discount, and 30+ days get 20% discount!",
    
    "late return|extend rental": "⏰ Late returns incur charges of KES 500 per hour. Need to extend? Contact us BEFORE your return time to avoid late fees. Extensions are subject to vehicle availability.",
    
    // Damage & Accidents
    "damage policy|accident": "⚠️ In case of accident: 1️⃣ Ensure everyone is safe, 2️⃣ Contact police, 3️⃣ Take photos of damage, 4️⃣ Call our 24/7 emergency line, 5️⃣ Don't attempt repairs without authorization.",
    
    "toll charges|toll roads": "🛣️ Toll charges are the customer's responsibility. We recommend carrying KES 500-1,000 cash for toll roads. Our vehicles are equipped with electronic toll tags (optional - KES 200/day).",
    
    "cross border|international": "🌍 Cross-border travel may be restricted. Kenya only (no international travel). Special permission required for travel to Tanzania/Uganda - contact us at least 7 days in advance.",
    
    // Default & General
    "default": "🤖 I'm your Urban Wheels assistant! I can help you with:\n\n📌 Booking Process\n💳 Payment Methods\n❌ Cancellation Policy\n🚗 Available Vehicles\n📋 Requirements\n📞 Contact Info\n🕒 Working Hours\n✈️ Airport Pickup\n🛡️ Insurance\n💰 Pricing & Discounts\n\nWhat would you like to know?",
    
    "greeting": "👋 Hello! Welcome to Urban Wheels Car Rental! I'm your virtual assistant. How can I help you today? You can ask me about bookings, payments, vehicles, or anything else!",
    
    "thanks": "😊 You're very welcome! Is there anything else I can help you with? If not, have a great day and drive safe! 🚗✨",
    
    "bye": "👋 Thank you for chatting with Urban Wheels! Have a wonderful day! Don't hesitate to come back if you have more questions. 🚙💨",
    
    "price|pricing|rates": "💰 Our daily rates start from:\n• Economy: KES 2,500/day\n• Sedan: KES 4,000/day\n• SUV: KES 6,000/day\n• Luxury: KES 10,000/day\n• Weekly & Monthly discounts available!\n\nCheck our Vehicles page for specific model pricing.",
    
    "discount|promotion": "🎉 Current promotions:\n• 10% off weekly rentals\n• 15% off monthly rentals\n• Student discount: 5% with valid ID\n• Corporate rates available\n• Referral program: Get KES 1,000 credit!",
    
    "location|where are you": "📍 Our main office is located at:\nUrban Wheels HQ, 2nd Floor\nUrban Towers, Kenyatta Avenue\nNairobi, Kenya\n\nWe also have pickup points at JKIA Airport, Westlands, and Mombasa Road."
};

// Smart response matching function
function getBotResponse(message) {
    const userMessage = message.toLowerCase().trim();
    
    // Greeting patterns
    if (userMessage.match(/^(hi|hello|hey|good morning|good afternoon|good evening|hola|sup)$/i)) {
        return faqResponses.greeting;
    }
    
    // Thanks patterns
    if (userMessage.match(/^(thanks|thank you|thx|appreciate|thank|ty)$/i)) {
        return faqResponses.thanks;
    }
    
    // Bye patterns
    if (userMessage.match(/^(bye|goodbye|see you|exit|quit|cya|ttyl)$/i)) {
        return faqResponses.bye;
    }
    
    // Search for matching keywords in faqResponses
    for (const [key, response] of Object.entries(faqResponses)) {
        const keywords = key.split('|');
        for (const keyword of keywords) {
            if (userMessage.includes(keyword)) {
                return response;
            }
        }
    }
    
    // Check for price-related queries
    if (userMessage.match(/price|cost|how much|rate|rental fee|charges/i)) {
        return faqResponses.price;
    }
    
    // Check for discount-related queries
    if (userMessage.match(/discount|offer|deal|promotion|save|cheaper/i)) {
        return faqResponses.discount;
    }
    
    // Return default response if no match found
    return faqResponses.default;
}

// Chatbot UI Manager
class ChatbotManager {
    constructor() {
        this.isOpen = false;
        this.messageCount = 0;
        this.typingTimeout = null;
        this.init();
    }
    
    init() {
        // Create chatbot elements if not exist
        if (!document.getElementById('chatbotContainer')) {
            this.createChatbotElements();
        }
        
        this.bindEvents();
    }
    
    createChatbotElements() {
        const chatbotHTML = `
            <div id="chatbotContainer" class="chatbot-container">
                <button id="chatButton" class="chat-button">
                    <i class="fas fa-comment"></i>
                    <span id="notificationBadge" class="notification-badge" style="display: none;">1</span>
                </button>
                
                <div id="chatWindow" class="chat-window">
                    <div class="chat-header">
                        <div class="chat-header-info">
                            <h3><i class="fas fa-robot"></i> Urban Wheels Assistant</h3>
                            <div class="chat-status">
                                <span class="status-dot"></span>
                                <span>Online • Typically replies instantly</span>
                            </div>
                        </div>
                        <div class="chat-controls">
                            <button id="minimizeChat" class="minimize-btn"><i class="fas fa-minus"></i></button>
                            <button id="closeChat" class="close-btn"><i class="fas fa-times"></i></button>
                        </div>
                    </div>
                    
                    <div id="chatMessages" class="chat-messages">
                        <!-- Messages will appear here -->
                    </div>
                    
                    <div class="quick-actions">
                        <button class="quick-action" data-question="book a car">🚗 Book a Car</button>
                        <button class="quick-action" data-question="payment methods">💳 Payment</button>
                        <button class="quick-action" data-question="cancellation policy">❌ Cancel</button>
                        <button class="quick-action" data-question="contact">📞 Contact</button>
                    </div>
                    
                    <div class="chat-input-area">
                        <input type="text" id="chatInput" class="chat-input" placeholder="Ask me anything...">
                        <button id="sendButton" class="send-button">
                            <i class="fas fa-paper-plane"></i>
                        </button>
                    </div>
                    
                    <div class="suggested-questions" id="suggestedQuestions">
                        <span class="suggested-question" data-question="How to book a vehicle?">📋 How to book?</span>
                        <span class="suggested-question" data-question="What payment methods do you accept?">💳 Payment methods</span>
                        <span class="suggested-question" data-question="What is your cancellation policy?">❌ Cancellation</span>
                        <span class="suggested-question" data-question="What documents do I need?">📄 Requirements</span>
                    </div>
                </div>
            </div>
        `;
        
        document.body.insertAdjacentHTML('beforeend', chatbotHTML);
    }
    
    bindEvents() {
        const chatButton = document.getElementById('chatButton');
        const chatWindow = document.getElementById('chatWindow');
        const minimizeBtn = document.getElementById('minimizeChat');
        const closeBtn = document.getElementById('closeChat');
        const sendButton = document.getElementById('sendButton');
        const chatInput = document.getElementById('chatInput');
        
        if (chatButton) {
            chatButton.addEventListener('click', () => this.toggleChat());
        }
        
        if (minimizeBtn) {
            minimizeBtn.addEventListener('click', () => this.minimizeChat());
        }
        
        if (closeBtn) {
            closeBtn.addEventListener('click', () => this.closeChat());
        }
        
        if (sendButton) {
            sendButton.addEventListener('click', () => this.sendMessage());
        }
        
        if (chatInput) {
            chatInput.addEventListener('keypress', (e) => {
                if (e.key === 'Enter') this.sendMessage();
            });
        }
        
        // Suggested questions
        document.querySelectorAll('.suggested-question, .quick-action').forEach(el => {
            el.addEventListener('click', () => {
                const question = el.getAttribute('data-question');
                if (question) {
                    document.getElementById('chatInput').value = question;
                    this.sendMessage();
                }
            });
        });
        
        // Remove notification badge after first open
        const notificationBadge = document.getElementById('notificationBadge');
        if (notificationBadge) {
            setTimeout(() => {
                notificationBadge.style.display = 'none';
            }, 5000);
        }
    }
    
    toggleChat() {
        const chatWindow = document.getElementById('chatWindow');
        this.isOpen = !this.isOpen;
        
        if (this.isOpen) {
            chatWindow.classList.add('open');
            if (this.messageCount === 0) {
                this.addBotMessage(faqResponses.greeting);
                this.messageCount++;
            }
        } else {
            chatWindow.classList.remove('open');
        }
    }
    
    minimizeChat() {
        const chatWindow = document.getElementById('chatWindow');
        this.isOpen = false;
        chatWindow.classList.remove('open');
    }
    
    closeChat() {
        const chatWindow = document.getElementById('chatWindow');
        this.isOpen = false;
        chatWindow.classList.remove('open');
    }
    
    sendMessage() {
        const inputField = document.getElementById('chatInput');
        const message = inputField.value.trim();
        
        if (message === '') return;
        
        // Add user message
        this.addUserMessage(message);
        inputField.value = '';
        
        // Show typing indicator
        this.showTypingIndicator();
        
        // Get and add bot response
        setTimeout(() => {
            this.hideTypingIndicator();
            const response = getBotResponse(message);
            this.addBotMessage(response);
        }, 800);
    }
    
    addUserMessage(message) {
        const messagesContainer = document.getElementById('chatMessages');
        const messageDiv = document.createElement('div');
        messageDiv.className = 'message user';
        messageDiv.innerHTML = `
            <div class="message-content">
                <div class="message-avatar">
                    <i class="fas fa-user"></i>
                </div>
                <div class="message-bubble">${this.escapeHtml(message)}</div>
            </div>
        `;
        messagesContainer.appendChild(messageDiv);
        this.scrollToBottom();
    }
    
    addBotMessage(message) {
        const messagesContainer = document.getElementById('chatMessages');
        const messageDiv = document.createElement('div');
        messageDiv.className = 'message bot';
        messageDiv.innerHTML = `
            <div class="message-content">
                <div class="message-avatar">
                    <i class="fas fa-robot"></i>
                </div>
                <div class="message-bubble">${this.formatMessage(this.escapeHtml(message))}</div>
            </div>
        `;
        messagesContainer.appendChild(messageDiv);
        this.scrollToBottom();
    }
    
    showTypingIndicator() {
        const messagesContainer = document.getElementById('chatMessages');
        const typingDiv = document.createElement('div');
        typingDiv.className = 'message bot';
        typingDiv.id = 'typingIndicator';
        typingDiv.innerHTML = `
            <div class="message-content">
                <div class="message-avatar">
                    <i class="fas fa-robot"></i>
                </div>
                <div class="typing-indicator">
                    <span></span>
                    <span></span>
                    <span></span>
                </div>
            </div>
        `;
        messagesContainer.appendChild(typingDiv);
        this.scrollToBottom();
    }
    
    hideTypingIndicator() {
        const typingIndicator = document.getElementById('typingIndicator');
        if (typingIndicator) {
            typingIndicator.remove();
        }
    }
    
    formatMessage(message) {
        // Convert line breaks to <br>
        return message.replace(/\n/g, '<br>');
    }
    
    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
    
    scrollToBottom() {
        const messagesContainer = document.getElementById('chatMessages');
        if (messagesContainer) {
            messagesContainer.scrollTop = messagesContainer.scrollHeight;
        }
    }
}

// Initialize chatbot when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    // Check if Font Awesome is loaded
    if (typeof FontAwesome === 'undefined' && !document.querySelector('link[href*="font-awesome"]')) {
        // Load Font Awesome if not present
        const fontAwesome = document.createElement('link');
        fontAwesome.rel = 'stylesheet';
        fontAwesome.href = 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css';
        document.head.appendChild(fontAwesome);
        
        setTimeout(() => {
            window.chatbot = new ChatbotManager();
        }, 500);
    } else {
        window.chatbot = new ChatbotManager();
    }
});