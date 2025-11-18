import './bootstrap';

let currentUser = null;

// Escape HTML to prevent XSS
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Check if user is logged in
async function checkAuth() {
    try {
        const response = await fetch('/api/auth/me');
        if (response.ok) {
            currentUser = await response.json();
            showDashboard();
        } else {
            showLogin();
        }
    } catch (error) {
        showLogin();
    }
}

function showLogin() {
    const authSection = document.getElementById('auth-section');
    if (authSection) {
        authSection.innerHTML = `
            <button onclick="login()" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-lg transition duration-150">
                Login
            </button>
        `;
    }
}

function showDashboard() {
    const authSection = document.getElementById('auth-section');
    if (authSection) {
        authSection.innerHTML = `
            <div class="flex items-center space-x-4">
                <span class="text-gray-700">Welcome, ${escapeHtml(currentUser.username)}!</span>
                <span class="text-sm text-gray-500 bg-gray-200 px-2 py-1 rounded">${escapeHtml(currentUser.role)}</span>
                <button onclick="logout()" class="bg-red-600 hover:bg-red-700 text-white font-bold py-2 px-4 rounded-lg transition duration-150">
                    Logout
                </button>
            </div>
        `;
    }
    document.getElementById('events-section')?.classList.remove('hidden');
    document.getElementById('dashboard-section')?.classList.remove('hidden');
    loadEvents();
    loadStats();
}

window.login = function() {
    window.location.href = '/api/auth/login';
}

window.logout = async function() {
    await fetch('/api/auth/logout', { method: 'POST' });
    window.location.reload();
}

async function loadEvents() {
    try {
        const response = await fetch('/api/events');
        if (response.ok) {
            const events = await response.json();
            displayEvents(events);
        }
    } catch (error) {
        console.error('Failed to load events:', error);
    }
}

function displayEvents(events) {
    const container = document.getElementById('events-list');
    if (!container) return;
    
    if (events.length === 0) {
        container.innerHTML = '<p class="text-gray-500">No events available.</p>';
        return;
    }

    container.innerHTML = events.map(event => `
        <div class="border-b border-gray-200 py-4 hover:bg-gray-50 px-4 rounded transition duration-150">
            <h3 class="text-lg font-semibold text-gray-900">${escapeHtml(event.title)}</h3>
            <p class="text-gray-600 mt-1">${escapeHtml(event.description || 'No description')}</p>
            <p class="text-sm text-gray-500 mt-2 flex items-center">
                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                </svg>
                ${escapeHtml(event.start_date)} to ${escapeHtml(event.end_date)}
            </p>
        </div>
    `).join('');
}

async function loadStats() {
    try {
        const response = await fetch('/api/stats/user');
        if (response.ok) {
            const stats = await response.json();
            document.getElementById('my-uploads-count').textContent = stats.total_uploads || 0;
            document.getElementById('successful-uploads-count').textContent = stats.successful_uploads || 0;
            document.getElementById('events-participated-count').textContent = stats.events_participated || 0;
        }
    } catch (error) {
        console.error('Failed to load stats:', error);
    }
}

// Initialize on page load
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', checkAuth);
} else {
    checkAuth();
}
