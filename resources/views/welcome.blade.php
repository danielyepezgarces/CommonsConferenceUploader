<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CommonsEventUploader</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body>
    <div class="min-h-screen">
        <!-- Navigation -->
        <nav>
            <div class="nav-content">
                <div class="nav-brand">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                    </svg>
                    <h1>CommonsEventUploader</h1>
                </div>
                <div id="auth-section"></div>
            </div>
        </nav>

        <!-- Main Content -->
        <main>
            <div id="app">
                <!-- Welcome Section -->
                <div class="card card-border">
                    <div style="display: flex; align-items: flex-start; gap: 1rem;">
                        <svg style="width: 3rem; height: 3rem; color: #2563eb; flex-shrink: 0;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                        </svg>
                        <div style="flex: 1;">
                            <h2>Welcome to CommonsEventUploader</h2>
                            <p>A web application for uploading conference-related images to Wikimedia Commons.</p>
                            <div class="mt-6">
                                <button onclick="login()" class="btn btn-primary">
                                    <svg style="width: 1.25rem; height: 1.25rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"></path>
                                    </svg>
                                    Login with Wikimedia
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Events Section (shown after login) -->
                <div id="events-section" class="hidden">
                    <div class="card">
                        <h2 style="font-size: 1.5rem; display: flex; align-items: center; gap: 0.5rem;">
                            <svg style="width: 1.5rem; height: 1.5rem; color: #2563eb;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                            </svg>
                            Events
                        </h2>
                        <div id="events-list"></div>
                    </div>
                </div>

                <!-- Dashboard Section (shown after login) -->
                <div id="dashboard-section" class="hidden">
                    <h2 style="font-size: 1.5rem; display: flex; align-items: center; gap: 0.5rem; margin-bottom: 1rem;">
                        <svg style="width: 1.5rem; height: 1.5rem; color: #2563eb;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                        </svg>
                        My Statistics
                    </h2>
                    <div class="stats-grid">
                        <div class="stat-card blue">
                            <div>
                                <h3>My Uploads</h3>
                                <div class="stat-number" id="my-uploads-count">0</div>
                            </div>
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                            </svg>
                        </div>
                        <div class="stat-card green">
                            <div>
                                <h3>Successful Uploads</h3>
                                <div class="stat-number" id="successful-uploads-count">0</div>
                            </div>
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                        <div class="stat-card purple">
                            <div>
                                <h3>Events Participated</h3>
                                <div class="stat-number" id="events-participated-count">0</div>
                            </div>
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                            </svg>
                        </div>
                    </div>
                </div>
            </div>
        </main>

        <!-- Footer -->
        <footer>
            <p>Powered by Laravel 12.x • Composer Only (No Node.js)</p>
        </footer>
    </div>
    <script src="{{ asset('js/app.js') }}"></script>
</body>
</html>
