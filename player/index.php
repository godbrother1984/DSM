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

        body, html {
            height: 100%;
            overflow: hidden;
            background: #000;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }

        .player-container {
            position: relative;
            width: 100vw;
            height: 100vh;
            background: #000;
        }

        /* Loading Screen */
        .loading-screen {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            z-index: 1000;
            transition: opacity 0.5s ease;
        }

        .loading-spinner {
            width: 60px;
            height: 60px;
            border: 4px solid rgba(255, 255, 255, 0.3);
            border-top: 4px solid white;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin-bottom: 20px;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .loading-text {
            color: white;
            font-size: 24px;
            font-weight: 300;
            margin-bottom: 10px;
        }

        .loading-detail {
            color: rgba(255, 255, 255, 0.8);
            font-size: 14px;
        }

        /* Error Screen */
        .error-screen {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%);
            display: none;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            z-index: 999;
            color: white;
            text-align: center;
            padding: 40px;
        }

        .error-icon {
            font-size: 80px;
            margin-bottom: 20px;
            animation: pulse 2s infinite;
        }

        .error-title {
            font-size: 32px;
            font-weight: 300;
            margin-bottom: 15px;
        }

        .error-message {
            font-size: 16px;
            line-height: 1.6;
            max-width: 600px;
            margin-bottom: 30px;
            opacity: 0.9;
        }

        .retry-button {
            background: rgba(255, 255, 255, 0.2);
            border: 2px solid rgba(255, 255, 255, 0.3);
            color: white;
            padding: 15px 30px;
            border-radius: 25px;
            cursor: pointer;
            font-size: 16px;
            transition: all 0.3s ease;
        }

        .retry-button:hover {
            background: rgba(255, 255, 255, 0.3);
            border-color: rgba(255, 255, 255, 0.5);
        }

        /* Device Info Panel */
        .device-info {
            position: fixed;
            top: 20px;
            left: 20px;
            background: rgba(0, 0, 0, 0.8);
            color: white;
            padding: 15px 20px;
            border-radius: 12px;
            font-size: 14px;
            z-index: 500;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            min-width: 280px;
        }

        .device-info h4 {
            margin-bottom: 10px;
            color: #667eea;
            font-size: 16px;
        }

        .device-info-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
        }

        .device-info-label {
            color: rgba(255, 255, 255, 0.7);
        }

        .device-info-value {
            color: white;
            font-weight: 500;
        }

        .device-id {
            color: #4CAF50 !important;
            font-family: monospace;
            font-size: 15px;
            font-weight: bold;
        }

        /* Status Indicator */
        .status-indicator {
            position: fixed;
            top: 20px;
            right: 20px;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: #27ae60;
            z-index: 500;
            animation: pulse 2s infinite;
        }

        .status-indicator.offline {
            background: #e74c3c;
        }

        /* Content Display */
        .content-display {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: #000;
            display: flex;
            justify-content: center;
            align-items: center;
            overflow: hidden;
        }

        .content-item {
            width: 100%;
            height: 100%;
            display: flex;
            justify-content: center;
            align-items: center;
            background: #000;
        }

        .content-item img {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
        }

        .content-item video {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .content-item iframe {
            width: 100%;
            height: 100%;
            border: none;
            background: white;
        }

        .content-item .text-content {
            width: 100%;
            height: 100%;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 40px;
            color: white;
            text-align: center;
        }

        /* Progress Bar */
        .progress-bar {
            position: fixed;
            bottom: 0;
            left: 0;
            height: 4px;
            background: #3498db;
            transition: width 0.5s ease;
            z-index: 500;
        }

        /* Playlist Info */
        .playlist-info {
            position: fixed;
            bottom: 20px;
            right: 20px;
            background: rgba(0, 0, 0, 0.8);
            color: white;
            padding: 10px 15px;
            border-radius: 8px;
            font-size: 12px;
            display: none;
            z-index: 500;
            backdrop-filter: blur(10px);
        }

        .playlist-info.show {
            display: block;
        }

        /* Default Content */
        .default-content {
            width: 100%;
            height: 100%;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            text-align: center;
            padding: 40px;
        }

        .default-content h1 {
            font-size: 48px;
            font-weight: 300;
            margin-bottom: 20px;
        }

        .default-content p {
            font-size: 18px;
            opacity: 0.8;
            margin-bottom: 30px;
        }

        .default-content .device-display {
            background: rgba(255, 255, 255, 0.1);
            padding: 20px;
            border-radius: 12px;
            margin-top: 20px;
            backdrop-filter: blur(10px);
        }

        .default-content .device-display h3 {
            margin-bottom: 10px;
            color: #4CAF50;
        }

        .default-content .device-id-large {
            font-family: monospace;
            font-size: 24px;
            font-weight: bold;
            color: #4CAF50;
            margin-bottom: 10px;
        }

        @keyframes pulse {
            0% { opacity: 1; }
            50% { opacity: 0.5; }
            100% { opacity: 1; }
        }

        /* Responsive */
        @media (max-width: 768px) {
            .default-content h1 {
                font-size: 32px;
            }
            
            .default-content p {
                font-size: 16px;
            }
            
            .device-info {
                top: 10px;
                left: 10px;
                font-size: 12px;
                padding: 10px 15px;
                min-width: 250px;
            }
            
            .default-content .device-id-large {
                font-size: 18px;
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
            <div class="loading-detail" id="loadingDetail">Initializing...</div>
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
            <h4>📱 Device Information</h4>
            <div class="device-info-row">
                <span class="device-info-label">Device ID:</span>
                <span class="device-info-value device-id" id="deviceId">Loading...</span>
            </div>
            <div class="device-info-row">
                <span class="device-info-label">Status:</span>
                <span class="device-info-value" id="deviceStatus">Connecting...</span>
            </div>
            <div class="device-info-row">
                <span class="device-info-label">Playlist:</span>
                <span class="device-info-value" id="currentPlaylist">None</span>
            </div>
            <div class="device-info-row">
                <span class="device-info-label">Resolution:</span>
                <span class="device-info-value" id="screenResolution">Loading...</span>
            </div>
            <div class="device-info-row">
                <span class="device-info-label">Last Update:</span>
                <span class="device-info-value" id="lastUpdate">Never</span>
            </div>
        </div>

        <!-- Playlist Info -->
        <div class="playlist-info" id="playlistInfo">
            <div>Item <span id="currentItem">0</span> of <span id="totalItems">0</span></div>
            <div>Next: <span id="nextItem">None</span></div>
        </div>

        <!-- Progress Bar -->
        <div class="progress-bar" id="progressBar" style="width: 0%"></div>

        <!-- Content Display Area -->
        <div class="content-display" id="contentDisplay">
            <div class="default-content">
                <h1>🎬 Digital Signage</h1>
                <p>Ready to display your content</p>
                <div class="device-display">
                    <h3>Device Information</h3>
                    <div class="device-id-large" id="deviceIdDisplay">Loading...</div>
                    <div id="defaultStatus">Connecting to server...</div>
                </div>
            </div>
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
                this.apiBase = this.detectApiPath();
                this.connectionRetries = 0;
                this.maxRetries = 3;
                
                console.log('🎬 Digital Signage Player initializing...');
                console.log('🔧 API Base:', this.apiBase);
                console.log('📱 Device ID:', this.deviceId);
                
                // Display device ID immediately
                this.updateDeviceIdDisplay();
                
                this.init();
            }

            detectApiPath() {
                const currentUrl = window.location.href;
                const currentPath = window.location.pathname;
                
                console.log('🔍 Current URL:', currentUrl);
                console.log('🔍 Current Path:', currentPath);
                
                // Check for /dsm/ in URL
                if (currentUrl.includes('/dsm/')) {
                    const baseUrl = currentUrl.split('/dsm/')[0] + '/dsm/api/';
                    console.log('✅ DSM path detected:', baseUrl);
                    return baseUrl;
                }
                
                // Extract from current path
                const pathParts = currentPath.split('/').filter(segment => segment);
                
                // Look for player folder and build path
                const playerIndex = pathParts.indexOf('player');
                if (playerIndex > 0) {
                    const baseParts = pathParts.slice(0, playerIndex);
                    const basePath = '/' + baseParts.join('/') + '/api/';
                    console.log('✅ Player-based path:', basePath);
                    return basePath;
                }
                
                // Check for common project folders
                const projectFolders = ['dsm', 'digital-signage', 'signage'];
                for (const folder of projectFolders) {
                    if (pathParts.includes(folder)) {
                        const folderIndex = pathParts.indexOf(folder);
                        const baseParts = pathParts.slice(0, folderIndex + 1);
                        const basePath = '/' + baseParts.join('/') + '/api/';
                        console.log(`✅ Project folder '${folder}' detected:`, basePath);
                        return basePath;
                    }
                }
                
                // Fallback
                console.log('⚠️ Using relative path fallback');
                return '../api/';
            }

            generateDeviceId() {
                let deviceId = localStorage.getItem('deviceId');
                if (!deviceId) {
                    deviceId = 'DSM-' + Math.random().toString(36).substr(2, 8).toUpperCase();
                    localStorage.setItem('deviceId', deviceId);
                }
                return deviceId;
            }

            updateDeviceIdDisplay() {
                // Update device ID in multiple places
                const deviceIdElement = document.getElementById('deviceId');
                const deviceIdDisplayElement = document.getElementById('deviceIdDisplay');
                
                if (deviceIdElement) {
                    deviceIdElement.textContent = this.deviceId;
                }
                
                if (deviceIdDisplayElement) {
                    deviceIdDisplayElement.textContent = this.deviceId;
                }
                
                // Update screen resolution
                const screenResElement = document.getElementById('screenResolution');
                if (screenResElement) {
                    screenResElement.textContent = `${window.screen.width}x${window.screen.height}`;
                }
            }

            async init() {
                this.updateLoadingText('Registering device...');
                
                try {
                    await this.registerDevice();
                    await this.loadPlaylist();
                    this.hideLoading();
                    this.startPlayback();
                    this.startUpdateLoop();
                    this.setupEventListeners();
                    
                } catch (error) {
                    console.error('❌ Initialization failed:', error);
                    this.showError('Failed to initialize player: ' + error.message);
                }
            }

            updateLoadingText(text) {
                const loadingDetail = document.getElementById('loadingDetail');
                if (loadingDetail) {
                    loadingDetail.textContent = text;
                }
                console.log('📝', text);
            }

            async registerDevice() {
                try {
                    const deviceInfo = {
                        device_id: this.deviceId,
                        name: 'Digital Player ' + this.deviceId.substr(-4),
                        location: 'Unknown',
                        device_type: 'player',
                        screen_width: window.screen.width,
                        screen_height: window.screen.height,
                        user_agent: navigator.userAgent,
                        ip_address: 'Auto-detected'
                    };

                    const response = await fetch(this.apiBase + 'devices', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify(deviceInfo)
                    });

                    const result = await response.json();
                    
                    if (result.success) {
                        console.log('✅ Device registered successfully');
                        this.updateDeviceInfo(result.data.device || result.data);
                        this.connectionRetries = 0;
                    } else {
                        throw new Error('Registration failed: ' + result.message);
                    }
                    
                } catch (error) {
                    console.error('❌ Device registration failed:', error);
                    
                    if (this.connectionRetries < this.maxRetries) {
                        this.connectionRetries++;
                        this.updateLoadingText(`Registration failed, retrying... (${this.connectionRetries}/${this.maxRetries})`);
                        await new Promise(resolve => setTimeout(resolve, 2000));
                        return this.registerDevice();
                    } else {
                        // Continue without registration
                        console.log('⚠️ Continuing without server registration');
                        this.updateDeviceInfo({
                            device_id: this.deviceId,
                            status: 'offline',
                            name: 'Digital Player ' + this.deviceId.substr(-4)
                        });
                    }
                }
            }

            async loadPlaylist() {
                try {
                    this.updateLoadingText('Loading playlist...');
                    
                    const response = await fetch(this.apiBase + 'player/playlist?device_id=' + this.deviceId, {
                        headers: {
                            'X-Device-ID': this.deviceId
                        }
                    });

                    const result = await response.json();
                    
                    if (result.success && result.data && result.data.playlist) {
                        this.playlist = result.data.playlist;
                        console.log('✅ Playlist loaded:', this.playlist.name);
                        this.updatePlaylistInfo();
                    } else {
                        console.log('⚠️ No playlist assigned, using default content');
                        this.playlist = this.getDefaultPlaylist();
                    }
                    
                } catch (error) {
                    console.error('❌ Failed to load playlist:', error);
                    this.playlist = this.getDefaultPlaylist();
                }
            }

            getDefaultPlaylist() {
                return {
                    id: 'default',
                    name: 'Default Content',
                    items: [
                        {
                            id: 'welcome',
                            title: 'Welcome Message',
                            type: 'text',
                            content: `
                                <div style="display: flex; flex-direction: column; justify-content: center; align-items: center; height: 100vh; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; text-align: center; padding: 40px;">
                                    <h1 style="font-size: 48px; font-weight: 300; margin-bottom: 20px;">🎬 Digital Signage</h1>
                                    <p style="font-size: 18px; opacity: 0.8; margin-bottom: 30px;">System is online and ready</p>
                                    <div style="background: rgba(255, 255, 255, 0.1); padding: 20px; border-radius: 12px; backdrop-filter: blur(10px);">
                                        <h3 style="margin-bottom: 10px; color: #4CAF50;">Device ID</h3>
                                        <div style="font-family: monospace; font-size: 24px; font-weight: bold; color: #4CAF50; margin-bottom: 10px;">${this.deviceId}</div>
                                        <div>Waiting for playlist assignment...</div>
                                    </div>
                                </div>
                            `,
                            duration: 10
                        },
                        {
                            id: 'system-info',
                            title: 'System Information',
                            type: 'text',
                            content: `
                                <div style="display: flex; flex-direction: column; justify-content: center; align-items: center; height: 100vh; background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%); color: white; text-align: center; padding: 40px;">
                                    <h1 style="font-size: 36px; margin-bottom: 30px;">📱 System Status</h1>
                                    <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px; max-width: 600px;">
                                        <div style="background: rgba(255, 255, 255, 0.1); padding: 20px; border-radius: 12px;">
                                            <h3 style="color: #3498db; margin-bottom: 10px;">Device ID</h3>
                                            <div style="font-family: monospace; font-weight: bold;">${this.deviceId}</div>
                                        </div>
                                        <div style="background: rgba(255, 255, 255, 0.1); padding: 20px; border-radius: 12px;">
                                            <h3 style="color: #e74c3c; margin-bottom: 10px;">Resolution</h3>
                                            <div>${window.screen.width}x${window.screen.height}</div>
                                        </div>
                                        <div style="background: rgba(255, 255, 255, 0.1); padding: 20px; border-radius: 12px;">
                                            <h3 style="color: #f39c12; margin-bottom: 10px;">Status</h3>
                                            <div>Online & Ready</div>
                                        </div>
                                        <div style="background: rgba(255, 255, 255, 0.1); padding: 20px; border-radius: 12px;">
                                            <h3 style="color: #27ae60; margin-bottom: 10px;">Time</h3>
                                            <div>${new Date().toLocaleTimeString()}</div>
                                        </div>
                                    </div>
                                </div>
                            `,
                            duration: 8
                        }
                    ]
                };
            }

            hideLoading() {
                const loadingScreen = document.getElementById('loadingScreen');
                if (loadingScreen) {
                    loadingScreen.style.opacity = '0';
                    setTimeout(() => {
                        loadingScreen.style.display = 'none';
                    }, 500);
                }
            }

            showError(message) {
                const errorScreen = document.getElementById('errorScreen');
                const errorMessage = document.getElementById('errorMessage');
                const loadingScreen = document.getElementById('loadingScreen');
                
                if (loadingScreen) {
                    loadingScreen.style.display = 'none';
                }
                
                if (errorMessage) {
                    errorMessage.textContent = message;
                }
                
                if (errorScreen) {
                    errorScreen.style.display = 'flex';
                }
                
                this.updateStatusIndicator(false);
            }

            startPlayback() {
                if (!this.playlist || !this.playlist.items || this.playlist.items.length === 0) {
                    console.log('⚠️ No content to play');
                    this.showDefaultContent();
                    return;
                }

                this.currentItemIndex = 0;
                this.isPlaying = true;
                this.playCurrentItem();
                this.updateStatusIndicator(true);
                
                console.log('▶️ Playback started');
            }

            playCurrentItem() {
                if (!this.playlist || !this.playlist.items) return;

                const item = this.playlist.items[this.currentItemIndex];
                if (!item) return;

                this.currentItem = item;
                this.displayContent(item);
                this.startProgress(item.duration || 10);
                this.updatePlaylistInfo();

                console.log(`▶️ Playing: ${item.title} (${item.duration}s)`);
            }

            displayContent(item) {
                const contentDisplay = document.getElementById('contentDisplay');
                if (!contentDisplay) return;

                let html = '';

                switch (item.type) {
                    case 'image':
                        html = `<div class="content-item"><img src="${item.file_url || item.content}" alt="${item.title}" loading="lazy"></div>`;
                        break;
                        
                    case 'video':
                        html = `<div class="content-item"><video src="${item.file_url || item.content}" autoplay muted loop></video></div>`;
                        break;
                        
                    case 'html':
                        html = `<div class="content-item"><iframe src="${item.file_url || item.content}" sandbox="allow-scripts allow-same-origin"></iframe></div>`;
                        break;
                        
                    case 'iframe':
                        html = `<div class="content-item"><iframe src="${item.file_url || item.content}" sandbox="allow-scripts allow-same-origin allow-forms"></iframe></div>`;
                        break;
                        
                    case 'text':
                    default:
                        html = `<div class="content-item"><div class="text-content">${item.content || item.title}</div></div>`;
                        break;
                }

                contentDisplay.innerHTML = html;
            }

            showDefaultContent() {
                const contentDisplay = document.getElementById('contentDisplay');
                if (contentDisplay) {
                    contentDisplay.innerHTML = `
                        <div class="default-content">
                            <h1>🎬 Digital Signage</h1>
                            <p>Ready to display your content</p>
                            <div class="device-display">
                                <h3>Device Information</h3>
                                <div class="device-id-large">${this.deviceId}</div>
                                <div>No playlist assigned</div>
                            </div>
                        </div>
                    `;
                }
            }

            startProgress(duration) {
                this.currentProgress = 0;
                const progressBar = document.getElementById('progressBar');
                
                if (this.progressTimer) {
                    clearInterval(this.progressTimer);
                }

                const interval = 100; // Update every 100ms
                const increment = (interval / 1000) / duration * 100;

                this.progressTimer = setInterval(() => {
                    if (this.isPaused) return;
                    
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
                if (!this.playlist || !this.playlist.items) return;

                this.currentItemIndex++;
                
                if (this.currentItemIndex >= this.playlist.items.length) {
                    this.currentItemIndex = 0; // Loop back to start
                    console.log('🔄 Playlist completed, looping...');
                }

                this.playCurrentItem();
            }

            updatePlaylistInfo() {
                const playlistInfo = document.getElementById('playlistInfo');
                const currentItemSpan = document.getElementById('currentItem');
                const totalItemsSpan = document.getElementById('totalItems');
                const nextItemSpan = document.getElementById('nextItem');
                const currentPlaylistSpan = document.getElementById('currentPlaylist');

                if (this.playlist && this.playlist.items) {
                    const total = this.playlist.items.length;
                    const current = this.currentItemIndex + 1;
                    const nextIndex = (this.currentItemIndex + 1) % total;
                    const nextItem = this.playlist.items[nextIndex];

                    if (currentItemSpan) currentItemSpan.textContent = current;
                    if (totalItemsSpan) totalItemsSpan.textContent = total;
                    if (nextItemSpan) nextItemSpan.textContent = nextItem ? nextItem.title : 'None';
                    if (currentPlaylistSpan) currentPlaylistSpan.textContent = this.playlist.name || 'Default';
                    
                    if (playlistInfo && total > 1) {
                        playlistInfo.classList.add('show');
                    }
                }
            }

            updateDeviceInfo(device) {
                const deviceIdElement = document.getElementById('deviceId');
                const deviceStatusElement = document.getElementById('deviceStatus');
                const lastUpdateElement = document.getElementById('lastUpdate');
                
                if (deviceIdElement) deviceIdElement.textContent = device.device_id || this.deviceId;
                if (deviceStatusElement) deviceStatusElement.textContent = device.status || 'Online';
                if (lastUpdateElement) lastUpdateElement.textContent = new Date().toLocaleTimeString();
                
                // Update device ID display
                this.updateDeviceIdDisplay();
            }

            updateStatusIndicator(isOnline) {
                const indicator = document.getElementById('statusIndicator');
                if (indicator) {
                    indicator.className = isOnline ? 'status-indicator' : 'status-indicator offline';
                }
            }

            startUpdateLoop() {
                // Check for playlist updates every 30 seconds
                this.updateTimer = setInterval(async () => {
                    try {
                        await this.checkForUpdates();
                        await this.sendHeartbeat();
                    } catch (error) {
                        console.error('❌ Update loop error:', error);
                    }
                }, 30000);
            }

            async checkForUpdates() {
                try {
                    const response = await fetch(this.apiBase + 'player/playlist?device_id=' + this.deviceId);
                    const result = await response.json();
                    
                    if (result.success && result.data && result.data.playlist) {
                        const newPlaylist = result.data.playlist;
                        
                        if (!this.playlist || this.playlist.id !== newPlaylist.id) {
                            console.log('🔄 Playlist updated, reloading...');
                            this.playlist = newPlaylist;
                            this.currentItemIndex = 0;
                            this.playCurrentItem();
                            this.updatePlaylistInfo();
                        }
                    }
                } catch (error) {
                    console.error('❌ Failed to check for updates:', error);
                }
            }

            async sendHeartbeat() {
                try {
                    await fetch(this.apiBase + 'player/heartbeat', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            device_id: this.deviceId,
                            status: 'online',
                            current_item: this.currentItem ? this.currentItem.id : null,
                            playlist_id: this.playlist ? this.playlist.id : null
                        })
                    });
                } catch (error) {
                    console.error('❌ Heartbeat failed:', error);
                }
            }

            setupEventListeners() {
                // Keyboard controls
                document.addEventListener('keydown', (e) => {
                    switch (e.key) {
                        case 'ArrowRight':
                        case 'n':
                            this.nextItem();
                            break;
                        case ' ':
                            e.preventDefault();
                            this.togglePlayPause();
                            break;
                        case 'r':
                            window.location.reload();
                            break;
                        case 'f':
                            this.toggleFullscreen();
                            break;
                        case 'i':
                            this.toggleDeviceInfo();
                            break;
                    }
                });

                // Auto-enter fullscreen
                setTimeout(() => {
                    if (!document.fullscreenElement) {
                        document.documentElement.requestFullscreen().catch(err => {
                            console.log('Could not enter fullscreen:', err);
                        });
                    }
                }, 3000);
            }

            togglePlayPause() {
                this.isPaused = !this.isPaused;
                console.log(this.isPaused ? '⏸️ Paused' : '▶️ Resumed');
            }

            toggleFullscreen() {
                if (document.fullscreenElement) {
                    document.exitFullscreen();
                } else {
                    document.documentElement.requestFullscreen();
                }
            }

            toggleDeviceInfo() {
                const deviceInfo = document.getElementById('deviceInfo');
                if (deviceInfo) {
                    deviceInfo.style.display = deviceInfo.style.display === 'none' ? 'block' : 'none';
                }
            }
        }

        // Initialize player when page loads
        document.addEventListener('DOMContentLoaded', () => {
            window.player = new DigitalSignagePlayer();
        });

        // Handle page visibility changes
        document.addEventListener('visibilitychange', () => {
            if (window.player) {
                if (document.hidden) {
                    window.player.isPaused = true;
                } else {
                    window.player.isPaused = false;
                }
            }
        });
    </script>
</body>
</html>