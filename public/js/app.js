/**
 * CommonsEventUploader - Standalone JavaScript
 * No Node.js or build process required
 */

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
            <button onclick="login()" class="btn btn-primary">
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
                <button onclick="logout()" class="btn btn-red">
                    Logout
                </button>
            </div>
        `;
    }
    const eventsSection = document.getElementById('events-section');
    const dashboardSection = document.getElementById('dashboard-section');
    if (eventsSection) eventsSection.classList.remove('hidden');
    if (dashboardSection) dashboardSection.classList.remove('hidden');
    
    loadEvents();
    loadStats();
}

function login() {
    window.location.href = '/api/auth/login';
}

async function logout() {
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
        <div class="event-item">
            <h3>${escapeHtml(event.title)}</h3>
            <p>${escapeHtml(event.description || 'No description')}</p>
            <div class="event-date">
                <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                </svg>
                ${escapeHtml(event.start_date)} to ${escapeHtml(event.end_date)}
            </div>
        </div>
    `).join('');
}

async function loadStats() {
    try {
        const response = await fetch('/api/stats/user');
        if (response.ok) {
            const stats = await response.json();
            const myUploadsCount = document.getElementById('my-uploads-count');
            const successfulUploadsCount = document.getElementById('successful-uploads-count');
            const eventsParticipatedCount = document.getElementById('events-participated-count');
            
            if (myUploadsCount) myUploadsCount.textContent = stats.total_uploads || 0;
            if (successfulUploadsCount) successfulUploadsCount.textContent = stats.successful_uploads || 0;
            if (eventsParticipatedCount) eventsParticipatedCount.textContent = stats.events_participated || 0;
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
