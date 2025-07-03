<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Digital Signage Player</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #000;
            overflow: hidden;
            cursor: none;
        }

        .player-container {
            position: relative;
            width: 100vw;
            height: 100vh;
            background: #000;
        }

        .content-display {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .content-item {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
        }

        .content-item.image {
            width: auto;
            height: auto;
            max-width: 100vw;
            max-height: 100vh;
        }

        .content-item.video {
            width: 100vw;
            height: 100vh;
            object-fit: cover;
        }

        .content-item.html {
            width: 100vw;
            height: 100vh;
            border: none;
            background: white;
        }

        .loading-screen {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            color: white;
            z-index: 1000;
        }

        .loading-spinner {
            width: 60px;
            height: 60px;
            border: 4px solid rgba(255,255,255,0.3);
            border-left: 4px solid white;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin-bottom: 20px;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .loading-text {
            font-size: 24px;
            font-weight: 300;
            margin-bottom: 10px;
        }

        .loading-detail {
            font-size: 16px;
            opacity: 0.8;
        }

        .device-info {
            position: fixed;
            top: 20px;
            right: 20px;
            background: rgba(0,0,0,0.8);
            color: white;
            padding: 15px 20px;
            border-radius: 8px;
            font-size: 14px;
            opacity: 0;
            transition: opacity 0.3s ease;
            z-index: 100;
        }

        .device-info.show {
            opacity: 1;
        }

        .device-info h4 {
            margin-bottom: 8px;
            color: #3498db;
        }

        .device-info div {
            margin-bottom: 4px;
        }

        .progress-bar {
            position: fixed;
            bottom: 0;
            left: 0;
            height: 4px;
            background: #3498db;
            transition: width 0.1s linear;
            z-index: 100;
        }

        .playlist-info {
            position: fixed;
            bottom: 20px;
            left: 20px;
            background: rgba(0,0,0,0.8);
            color: white;
            padding: 10px 15px;
            border-radius: 6px;
            font-size: 12px;
            opacity: 0;
            transition: opacity 0.3s ease;
            z-index: 100;
        }

        .playlist-info.show {
            opacity: 1;
        }

        .error-screen {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%);
            display: none;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            color: white;
            z-index: 999;
        }

        .error-icon {
            font-size: 80px;
            margin-bottom: 20px;
        }

        .error-title {
            font-size: 32px;
            font-weight: 300;
            margin-bottom: 10px;
        }

        .error-message {
            font-size: 18px;
            opacity: 0.9;
            text-align: center;
            max-width: 600px;
            margin-bottom: 30px;
        }

        .retry-button {
            padding: 12px 30px;
            background: rgba(255,255,255,0.2);
            border: 2px solid white;
            color: white;
            border-radius: 6px;
            font-size: 16px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .retry-button:hover {
            background: rgba(255,255,255,0.3);
        }

        .status-indicator {
            position: fixed;
            top: 20px;
            left: 20px;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: #27ae60;
            z-index: 100;
            animation: pulse 2s infinite;
        }

        .status-indicator.offline {
            background: #e74c3c;
        }

        .status-indicator.error {
            background: #f39c12;
        }

        @keyframes pulse {
            0% { opacity: 1; }
            50% { opacity: 0.5; }
            100% { opacity: 1; }
        }

        .transition-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: black;
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.5s ease;
            z-index: 50;
        }

        .transition-overlay.active {
            opacity: 1;
        }

        /* Layout Templates */
        .layout-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            width: 100vw;
            height: 100vh;
        }

        .layout-grid .main-content {
            grid-column: 1;
            position: relative;
        }

        .layout-grid .sidebar {
            grid-column: 2;
            background: #f8f9fa;
            padding: 20px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .layout-corporate {
            display: grid;
            grid-template-rows: 100px 1fr 80px;
            width: 100vw;
            height: 100vh;
        }

        .layout-corporate .header {
            background: #2c3e50;
            color: white;
            display: flex;
            align-items: center;
            padding: 0 30px;
            font-size: 24px;
            font-weight: 300;
        }

        .layout-corporate .main-content {
            position: relative;
        }

        .layout-corporate .footer {
            background: #34495e;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
        }

        /* Widget Styles */
        .widget {
            background: white;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 15px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }

        .widget h3 {
            margin-bottom: 10px;
            color: #2c3e50;
            font-size: 16px;
        }

        .clock-widget {
            text-align: center;
        }

        .clock-time {
            font-size: 28px;
            font-weight: 300;
            color: #3498db;
            margin-bottom: 5px;
        }

        .clock-date {
            font-size: 14px;
            color: #7f8c8d;
        }

        .weather-widget {
            text-align: center;
        }

        .weather-temp {
            font-size: 24px;
            font-weight: 300;
            color: #e67e22;
            margin-bottom: 5px;
        }

        .weather-desc {
            font-size: 12px;
            color: #7f8c8d;
        }

        /* Hidden class for smooth transitions */
        .hidden {
            opacity: 0;
            visibility: hidden;
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .layout-grid {
                grid-template-columns: 1fr;
                grid-template-rows: 1fr auto;
            }
            
            .layout-grid .sidebar {
                grid-row: 2;
                height: 200px;
                padding: 15px;
            }
        }
    </style>
</head>
<body>
    <div class="player-container">
        <!-- Loading Screen -->
        <div class="loading-screen" id="loadingScreen">
            <div class="loading-spinner"></div>
            <div class="loading-text">Digital Signage Player</div>
            <div class="loading-detail">Initializing...</div>
        </div>

        <!-- Error Screen -->
        <div class="error-screen" id="errorScreen">
            <div class="error-icon">⚠️</div>
            <div class="error-title">Connection Error</div>
            <div class="error-message" id="errorMessage">
                Unable to connect to the signage server. Please check your network connection.
            </div>
            <button class="retry-button" onclick="window.location.reload()">
                🔄 Retry Connection
            </button>
        </div>

        <!-- Status Indicator -->
        <div class="status-indicator" id="statusIndicator"></div>

        <!-- Device Info Panel -->
        <div class="device-info" id="deviceInfo">
            <h4>Device Information</h4>
            <div>ID: <span id="deviceId">Loading...</span></div>
            <div>Status: <span id="deviceStatus">Connecting...</span></div>
            <div>Playlist: <span id="currentPlaylist">None</span></div>
            <div>Last Update: <span id="lastUpdate">Never</span></div>
        </div>

        <!-- Playlist Info -->
        <div class="playlist-info" id="playlistInfo">
            <div>Item <span id="currentItem">0</span> of <span id="totalItems">0</span></div>
            <div>Next: <span id="nextItem">None</span></div>
        </div>

        <!-- Progress Bar -->
        <div class="progress-bar" id="progressBar" style="width: 0%"></div>

        <!-- Transition Overlay -->
        <div class="transition-overlay" id="transitionOverlay"></div>

        <!-- Content Display Area -->
        <div class="content-display" id="contentDisplay">
            <!-- Dynamic content will be loaded here -->
        </div>
    </div>

    <script>
        class DigitalSignagePlayer {
            constructor() {
                this.deviceId = this.generateDeviceId();
                this.playlist = null;
                this.currentItemIndex = 0;
                this.currentItem = null;
                this.isPlaying = false;
                this.isPaused = false;
                this.progressTimer = null;
                this.updateTimer = null;
                this.currentProgress = 0;
                this.layout = 'fullscreen';
                
                this.init();
            }

            generateDeviceId() {
                let deviceId = localStorage.getItem('deviceId');
                if (!deviceId) {
                    deviceId = 'device_' + Math.random().toString(36).substr(2, 9);
                    localStorage.setItem('deviceId', deviceId);
                }
                return deviceId;
            }

            async init() {
                console.log('Initializing Digital Signage Player...');
                
                this.updateLoadingText('Registering device...');
                await this.registerDevice();
                
                this.updateLoadingText('Loading playlist...');
                await this.loadPlaylist();
                
                this.updateLoadingText('Setting up player...');
                this.setupEventListeners();
                this.updateDeviceInfo();
                
                // Check for preview mode
                const urlParams = new URLSearchParams(window.location.search);
                if (urlParams.get('preview') === 'true') {
                    this.loadPreviewPlaylist();
                } else {
                    this.startPlayback();
                }
                
                this.hideLoading();
                this.startHeartbeat();
                
                console.log('Player initialized successfully');
            }

            updateLoadingText(text) {
                const loadingDetail = document.querySelector('.loading-detail');
                if (loadingDetail) {
                    loadingDetail.textContent = text;
                }
            }

            async registerDevice() {
                try {
                    const deviceInfo = {
                        device_id: this.deviceId,
                        name: `Display ${this.deviceId.split('_')[1]}`,
                        location: 'Auto-detected',
                        screen_width: window.screen.width,
                        screen_height: window.screen.height,
                        user_agent: navigator.userAgent,
                        ip_address: await this.getClientIP()
                    };

                    const response = await fetch('/api/devices/register', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-Device-ID': this.deviceId
                        },
                        body: JSON.stringify(deviceInfo)
                    });

                    const result = await response.json();
                    if (result.success) {
                        console.log('Device registered successfully');
                        this.updateStatus('online');
                    } else {
                        console.warn('Device registration failed:', result.message);
                    }
                } catch (error) {
                    console.error('Registration error:', error);
                    this.updateStatus('error');
                }
            }

            async getClientIP() {
                try {
                    const response = await fetch('https://api.ipify.org?format=json');
                    const data = await response.json();
                    return data.ip;
                } catch {
                    return 'Unknown';
                }
            }

            async loadPlaylist() {
                try {
                    const response = await fetch(`/api/player/playlist?device_id=${this.deviceId}`, {
                        headers: {
                            'X-Device-ID': this.deviceId
                        }
                    });

                    const result = await response.json();
                    if (result.success && result.data.playlist) {
                        this.playlist = result.data.playlist;
                        this.layout = result.data.playlist.layout?.template || 'fullscreen';
                        console.log('Playlist loaded:', this.playlist.name);
                        this.updatePlaylistInfo();
                    } else {
                        console.log('No playlist assigned');
                        this.showDefaultContent();
                    }
                } catch (error) {
                    console.error('Failed to load playlist:', error);
                    this.showError('Failed to load playlist content');
                }
            }

            loadPreviewPlaylist() {
                const previewData = localStorage.getItem('previewPlaylist');
                if (previewData) {
                    try {
                        const data = JSON.parse(previewData);
                        this.playlist = {
                            name: data.name,
                            items: data.items,
                            layout: { template: data.layout || 'fullscreen' },
                            shuffle: data.shuffle
                        };
                        this.layout = this.playlist.layout.template;
                        console.log('Preview playlist loaded:', this.playlist.name);
                        
                        // Show preview indicator
                        this.showPreviewMode();
                        this.updatePlaylistInfo();
                        this.startPlayback();
                    } catch (error) {
                        console.error('Failed to load preview playlist:', error);
                        this.showError('Invalid preview data');
                    }
                } else {
                    this.showError('No preview data found');
                }
            }

            showPreviewMode() {
                const deviceInfo = document.getElementById('deviceInfo');
                deviceInfo.style.background = 'rgba(230, 126, 34, 0.9)';
                deviceInfo.innerHTML = `
                    <h4>🎭 PREVIEW MODE</h4>
                    <div>Playlist: ${this.playlist.name}</div>
                    <div>Items: ${this.playlist.items.length}</div>
                    <div>Layout: ${this.layout}</div>
                    <div><small>Press F5 to exit preview</small></div>
                `;
                deviceInfo.classList.add('show');
            }

            startPlayback() {
                if (!this.playlist || !this.playlist.items || this.playlist.items.length === 0) {
                    this.showDefaultContent();
                    return;
                }

                this.applyLayout();
                
                if (this.playlist.shuffle) {
                    this.shufflePlaylist();
                }

                this.currentItemIndex = 0;
                this.playCurrentItem();
                this.isPlaying = true;
            }

            applyLayout() {
                const contentDisplay = document.getElementById('contentDisplay');
                
                // Remove existing layout classes
                contentDisplay.className = 'content-display';
                
                switch (this.layout) {
                    case 'grid':
                        contentDisplay.className += ' layout-grid';
                        this.setupGridLayout();
                        break;
                    case 'corporate':
                        contentDisplay.className += ' layout-corporate';
                        this.setupCorporateLayout();
                        break;
                    default:
                        // Fullscreen layout (default)
                        break;
                }
            }

            setupGridLayout() {
                const contentDisplay = document.getElementById('contentDisplay');
                contentDisplay.innerHTML = `
                    <div class="main-content" id="mainContent"></div>
                    <div class="sidebar">
                        <div class="widget clock-widget">
                            <h3>🕐 Current Time</h3>
                            <div class="clock-time" id="clockTime"></div>
                            <div class="clock-date" id="clockDate"></div>
                        </div>
                        <div class="widget weather-widget">
                            <h3>🌤️ Weather</h3>
                            <div class="weather-temp">22°C</div>
                            <div class="weather-desc">Partly Cloudy</div>
                        </div>
                        <div class="widget">
                            <h3>📊 Quick Stats</h3>
                            <div style="font-size: 12px; color: #7f8c8d;">
                                <div>Uptime: <span id="uptime">0h 0m</span></div>
                                <div>Items Played: <span id="itemsPlayed">0</span></div>
                            </div>
                        </div>
                    </div>
                `;
                
                this.startClock();
                this.startUptimeCounter();
            }

            setupCorporateLayout() {
                const contentDisplay = document.getElementById('contentDisplay');
                contentDisplay.innerHTML = `
                    <div class="header">
                        <div>Digital Signage System</div>
                        <div style="margin-left: auto; font-size: 16px;" id="headerClock"></div>
                    </div>
                    <div class="main-content" id="mainContent"></div>
                    <div class="footer">
                        <div>Powered by Digital Signage System | Last Updated: <span id="footerTime"></span></div>
                    </div>
                `;
                
                this.startHeaderClock();
            }

            startClock() {
                const updateClock = () => {
                    const now = new Date();
                    const timeElement = document.getElementById('clockTime');
                    const dateElement = document.getElementById('clockDate');
                    
                    if (timeElement) {
                        timeElement.textContent = now.toLocaleTimeString();
                    }
                    if (dateElement) {
                        dateElement.textContent = now.toLocaleDateString();
                    }
                };
                
                updateClock();
                setInterval(updateClock, 1000);
            }

            startHeaderClock() {
                const updateHeaderClock = () => {
                    const now = new Date();
                    const headerClock = document.getElementById('headerClock');
                    const footerTime = document.getElementById('footerTime');
                    
                    if (headerClock) {
                        headerClock.textContent = now.toLocaleTimeString();
                    }
                    if (footerTime) {
                        footerTime.textContent = now.toLocaleString();
                    }
                };
                
                updateHeaderClock();
                setInterval(updateHeaderClock, 1000);
            }

            startUptimeCounter() {
                const startTime = Date.now();
                
                const updateUptime = () => {
                    const uptimeElement = document.getElementById('uptime');
                    if (uptimeElement) {
                        const diff = Date.now() - startTime;
                        const hours = Math.floor(diff / (1000 * 60 * 60));
                        const minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
                        uptimeElement.textContent = `${hours}h ${minutes}m`;
                    }
                };
                
                setInterval(updateUptime, 60000);
            }

            async playCurrentItem() {
                if (!this.playlist || !this.playlist.items || this.playlist.items.length === 0) {
                    return;
                }

                const item = this.playlist.items[this.currentItemIndex];
                if (!item) {
                    this.nextItem();
                    return;
                }

                this.currentItem = item;
                console.log('Playing item:', item.title);

                // Show transition
                this.showTransition();

                // Wait for transition
                setTimeout(() => {
                    this.displayContent(item);
                    this.hideTransition();
                    this.startProgress(item.effective_duration || item.duration || 10);
                    this.updatePlaylistInfo();
                    this.trackPlayback(item);
                }, 500);
            }

            displayContent(item) {
                const contentContainer = this.getContentContainer();
                
                // Clear previous content
                contentContainer.innerHTML = '';

                switch (item.type) {
                    case 'image':
                        this.displayImage(item, contentContainer);
                        break;
                    case 'video':
                        this.displayVideo(item, contentContainer);
                        break;
                    case 'html':
                        this.displayHTML(item, contentContainer);
                        break;
                    default:
                        this.displayDefault(item, contentContainer);
                }
            }

            getContentContainer() {
                // Return appropriate container based on layout
                const mainContent = document.getElementById('mainContent');
                return mainContent || document.getElementById('contentDisplay');
            }

            displayImage(item, container) {
                const img = document.createElement('img');
                img.className = 'content-item image';
                img.src = item.file_url || `/uploads/content/${item.id}`;
                img.alt = item.title;
                
                img.onload = () => {
                    console.log('Image loaded successfully');
                };
                
                img.onerror = () => {
                    console.error('Failed to load image:', item.title);
                    this.displayError('Failed to load image');
                };
                
                container.appendChild(img);
            }

            displayVideo(item, container) {
                const video = document.createElement('video');
                video.className = 'content-item video';
                video.src = item.file_url || `/uploads/content/${item.id}`;
                video.autoplay = true;
                video.muted = true;
                video.loop = false;
                
                video.onloadeddata = () => {
                    console.log('Video loaded successfully');
                };
                
                video.onended = () => {
                    console.log('Video ended');
                    this.nextItem();
                };
                
                video.onerror = () => {
                    console.error('Failed to load video:', item.title);
                    this.displayError('Failed to load video');
                };
                
                container.appendChild(video);
            }

            displayHTML(item, container) {
                const iframe = document.createElement('iframe');
                iframe.className = 'content-item html';
                iframe.src = item.file_url || `/uploads/content/${item.id}`;
                iframe.frameBorder = '0';
                iframe.allowFullscreen = true;
                
                iframe.onload = () => {
                    console.log('HTML content loaded successfully');
                };
                
                iframe.onerror = () => {
                    console.error('Failed to load HTML content:', item.title);
                    this.displayError('Failed to load HTML content');
                };
                
                container.appendChild(iframe);
            }

            displayDefault(item, container) {
                const defaultDiv = document.createElement('div');
                defaultDiv.style.cssText = `
                    display: flex;
                    flex-direction: column;
                    align-items: center;
                    justify-content: center;
                    height: 100%;
                    color: white;
                    text-align: center;
                    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                `;
                
                defaultDiv.innerHTML = `
                    <div style="font-size: 80px; margin-bottom: 20px;">📄</div>
                    <h2 style="font-size: 32px; margin-bottom: 10px;">${item.title}</h2>
                    <p style="font-size: 18px; opacity: 0.8;">${item.type.toUpperCase()} Content</p>
                `;
                
                container.appendChild(defaultDiv);
            }

            startProgress(duration) {
                this.currentProgress = 0;
                const progressBar = document.getElementById('progressBar');
                
                if (this.progressTimer) {
                    clearInterval(this.progressTimer);
                }
                
                const interval = 100; // Update every 100ms
                const increment = (100 / duration) / (1000 / interval);
                
                this.progressTimer = setInterval(() => {
                    this.currentProgress += increment;
                    
                    if (progressBar) {
                        progressBar.style.width = Math.min(this.currentProgress, 100) + '%';
                    }
                    
                    if (this.currentProgress >= 100) {
                        clearInterval(this.progressTimer);
                        this.nextItem();
                    }
                }, interval);
            }

            nextItem() {
                if (!this.playlist || !this.playlist.items || this.playlist.items.length === 0) {
                    return;
                }

                this.currentItemIndex++;
                
                if (this.currentItemIndex >= this.playlist.items.length) {
                    // End of playlist
                    if (this.playlist.loop_count === 0 || this.playlist.loop_count > 1) {
                        // Loop playlist
                        this.currentItemIndex = 0;
                        console.log('Looping playlist');
                    } else {
                        // Stop playback
                        this.isPlaying = false;
                        this.showDefaultContent();
                        return;
                    }
                }

                // Update items played counter
                const itemsPlayedElement = document.getElementById('itemsPlayed');
                if (itemsPlayedElement) {
                    const current = parseInt(itemsPlayedElement.textContent) || 0;
                    itemsPlayedElement.textContent = current + 1;
                }

                this.playCurrentItem();
            }

            previousItem() {
                if (!this.playlist || !this.playlist.items || this.playlist.items.length === 0) {
                    return;
                }

                this.currentItemIndex--;
                
                if (this.currentItemIndex < 0) {
                    this.currentItemIndex = this.playlist.items.length - 1;
                }

                this.playCurrentItem();
            }

            shufflePlaylist() {
                if (!this.playlist || !this.playlist.items) return;
                
                const items = [...this.playlist.items];
                for (let i = items.length - 1; i > 0; i--) {
                    const j = Math.floor(Math.random() * (i + 1));
                    [items[i], items[j]] = [items[j], items[i]];
                }
                this.playlist.items = items;
                console.log('Playlist shuffled');
            }

            showDefaultContent() {
                const contentContainer = this.getContentContainer();
                contentContainer.innerHTML = `
                    <div style="
                        display: flex;
                        flex-direction: column;
                        align-items: center;
                        justify-content: center;
                        height: 100%;
                        color: white;
                        text-align: center;
                        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                    ">
                        <div style="font-size: 120px; margin-bottom: 30px;">🎬</div>
                        <h1 style="font-size: 48px; font-weight: 300; margin-bottom: 20px;">Digital Signage</h1>
                        <p style="font-size: 24px; opacity: 0.8; margin-bottom: 40px;">Ready to display content</p>
                        <div style="font-size: 16px; opacity: 0.6;">
                            <div>Device: ${this.deviceId}</div>
                            <div>Status: Waiting for playlist assignment</div>
                        </div>
                    </div>
                `;
            }

            showTransition() {
                const overlay = document.getElementById('transitionOverlay');
                if (overlay) {
                    overlay.classList.add('active');
                }
            }

            hideTransition() {
                const overlay = document.getElementById('transitionOverlay');
                if (overlay) {
                    overlay.classList.remove('active');
                }
            }

            updatePlaylistInfo() {
                const playlistInfo = document.getElementById('playlistInfo');
                const currentItemSpan = document.getElementById('currentItem');
                const totalItemsSpan = document.getElementById('totalItems');
                const nextItemSpan = document.getElementById('nextItem');

                if (this.playlist && this.playlist.items) {
                    currentItemSpan.textContent = this.currentItemIndex + 1;
                    totalItemsSpan.textContent = this.playlist.items.length;
                    
                    const nextIndex = (this.currentItemIndex + 1) % this.playlist.items.length;
                    const nextItem = this.playlist.items[nextIndex];
                    nextItemSpan.textContent = nextItem ? nextItem.title : 'None';
                    
                    playlistInfo.classList.add('show');
                } else {
                    playlistInfo.classList.remove('show');
                }
            }

            updateDeviceInfo() {
                document.getElementById('deviceId').textContent = this.deviceId;
                document.getElementById('deviceStatus').textContent = 'Connected';
                document.getElementById('currentPlaylist').textContent = 
                    this.playlist ? this.playlist.name : 'None';
                document.getElementById('lastUpdate').textContent = new Date().toLocaleTimeString();
            }

            updateStatus(status) {
                const indicator = document.getElementById('statusIndicator');
                indicator.className = `status-indicator ${status}`;
                
                const statusText = document.getElementById('deviceStatus');
                if (statusText) {
                    statusText.textContent = status.charAt(0).toUpperCase() + status.slice(1);
                }
            }

            async trackPlayback(item) {
                try {
                    await fetch('/api/analytics/track', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-Device-ID': this.deviceId
                        },
                        body: JSON.stringify({
                            device_id: this.deviceId,
                            content_id: item.id,
                            playlist_id: this.playlist?.id,
                            event_type: 'start',
                            timestamp: new Date().toISOString()
                        })
                    });
                } catch (error) {
                    console.error('Failed to track playback:', error);
                }
            }

            startHeartbeat() {
                setInterval(async () => {
                    try {
                        await fetch('/api/devices/heartbeat', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-Device-ID': this.deviceId
                            },
                            body: JSON.stringify({
                                device_id: this.deviceId,
                                status: this.isPlaying ? 'playing' : 'idle',
                                current_content: this.currentItem?.id || null,
                                timestamp: new Date().toISOString()
                            })
                        });
                    } catch (error) {
                        console.error('Heartbeat failed:', error);
                        this.updateStatus('error');
                    }
                }, 30000); // Every 30 seconds
            }

            setupEventListeners() {
                // Keyboard controls
                document.addEventListener('keydown', (e) => {
                    switch (e.code) {
                        case 'Space':
                            e.preventDefault();
                            this.togglePlayPause();
                            break;
                        case 'ArrowRight':
                            e.preventDefault();
                            this.nextItem();
                            break;
                        case 'ArrowLeft':
                            e.preventDefault();
                            this.previousItem();
                            break;
                        case 'KeyR':
                            if (e.ctrlKey) {
                                e.preventDefault();
                                this.restart();
                            }
                            break;
                        case 'KeyI':
                            e.preventDefault();
                            this.toggleDeviceInfo();
                            break;
                        case 'KeyP':
                            e.preventDefault();
                            this.togglePlaylistInfo();
                            break;
                        case 'F5':
                            // Allow F5 to refresh normally
                            break;
                        default:
                            e.preventDefault();
                    }
                });

                // Touch controls for mobile devices
                let touchStartX = 0;
                let touchStartY = 0;

                document.addEventListener('touchstart', (e) => {
                    touchStartX = e.touches[0].clientX;
                    touchStartY = e.touches[0].clientY;
                });

                document.addEventListener('touchend', (e) => {
                    const touchEndX = e.changedTouches[0].clientX;
                    const touchEndY = e.changedTouches[0].clientY;
                    
                    const deltaX = touchEndX - touchStartX;
                    const deltaY = touchEndY - touchStartY;
                    
                    const minSwipeDistance = 50;
                    
                    if (Math.abs(deltaX) > Math.abs(deltaY) && Math.abs(deltaX) > minSwipeDistance) {
                        if (deltaX > 0) {
                            this.previousItem(); // Swipe right
                        } else {
                            this.nextItem(); // Swipe left
                        }
                    }
                });

                // Auto-reload playlist periodically
                setInterval(async () => {
                    const urlParams = new URLSearchParams(window.location.search);
                    if (urlParams.get('preview') !== 'true') {
                        await this.checkForUpdates();
                    }
                }, 60000); // Every minute

                // Visibility change handling
                document.addEventListener('visibilitychange', () => {
                    if (document.visibilityState === 'visible') {
                        console.log('Page became visible, checking for updates...');
                        this.checkForUpdates();
                    }
                });
            }

            async checkForUpdates() {
                try {
                    const response = await fetch(`/api/player/playlist?device_id=${this.deviceId}`, {
                        headers: {
                            'X-Device-ID': this.deviceId
                        }
                    });

                    const result = await response.json();
                    if (result.success && result.data.playlist) {
                        const newPlaylist = result.data.playlist;
                        
                        // Check if playlist has been updated
                        if (!this.playlist || this.playlist.id !== newPlaylist.id || 
                            this.playlist.updated_at !== newPlaylist.updated_at) {
                            
                            console.log('Playlist updated, reloading...');
                            this.playlist = newPlaylist;
                            this.layout = newPlaylist.layout?.template || 'fullscreen';
                            this.currentItemIndex = 0;
                            this.restart();
                        }
                    }
                } catch (error) {
                    console.error('Failed to check for updates:', error);
                }
            }

            togglePlayPause() {
                if (this.isPaused) {
                    this.resume();
                } else {
                    this.pause();
                }
            }

            pause() {
                this.isPaused = true;
                if (this.progressTimer) {
                    clearInterval(this.progressTimer);
                }
                console.log('Playback paused');
            }

            resume() {
                this.isPaused = false;
                if (this.currentItem) {
                    const remainingTime = ((100 - this.currentProgress) / 100) * 
                        (this.currentItem.effective_duration || this.currentItem.duration || 10);
                    this.startProgress(remainingTime);
                }
                console.log('Playback resumed');
            }

            restart() {
                if (this.progressTimer) {
                    clearInterval(this.progressTimer);
                }
                this.currentItemIndex = 0;
                this.startPlayback();
                console.log('Player restarted');
            }

            toggleDeviceInfo() {
                const deviceInfo = document.getElementById('deviceInfo');
                deviceInfo.classList.toggle('show');
            }

            togglePlaylistInfo() {
                const playlistInfo = document.getElementById('playlistInfo');
                playlistInfo.classList.toggle('show');
            }

            showError(message) {
                const errorScreen = document.getElementById('errorScreen');
                const errorMessage = document.getElementById('errorMessage');
                
                errorMessage.textContent = message;
                errorScreen.style.display = 'flex';
                
                this.updateStatus('error');
                console.error('Player error:', message);
            }

            hideError() {
                const errorScreen = document.getElementById('errorScreen');
                errorScreen.style.display = 'none';
                this.updateStatus('online');
            }

            hideLoading() {
                const loadingScreen = document.getElementById('loadingScreen');
                loadingScreen.style.display = 'none';
            }

            displayError(message) {
                const contentContainer = this.getContentContainer();
                contentContainer.innerHTML = `
                    <div style="
                        display: flex;
                        flex-direction: column;
                        align-items: center;
                        justify-content: center;
                        height: 100%;
                        color: white;
                        text-align: center;
                        background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%);
                    ">
                        <div style="font-size: 80px; margin-bottom: 20px;">⚠️</div>
                        <h2 style="font-size: 32px; margin-bottom: 10px;">Content Error</h2>
                        <p style="font-size: 18px; opacity: 0.8;">${message}</p>
                        <p style="font-size: 14px; opacity: 0.6; margin-top: 20px;">Skipping to next item in 3 seconds...</p>
                    </div>
                `;
                
                // Auto-skip to next item after error
                setTimeout(() => {
                    this.nextItem();
                }, 3000);
            }
        }

        // Initialize player when page loads
        document.addEventListener('DOMContentLoaded', () => {
            window.player = new DigitalSignagePlayer();
        });

        // Global error handling
        window.addEventListener('error', (e) => {
            console.error('Global error:', e.error);
            if (window.player) {
                window.player.showError('An unexpected error occurred');
            }
        });

        // Handle unhandled promise rejections
        window.addEventListener('unhandledrejection', (e) => {
            console.error('Unhandled promise rejection:', e.reason);
            if (window.player) {
                window.player.showError('A network error occurred');
            }
        });
    </script>
</body>
</html>