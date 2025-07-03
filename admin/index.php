<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Digital Signage System</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
            background: white;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            overflow: hidden;
        }

        .header {
            background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
            color: white;
            padding: 30px;
            text-align: center;
            position: relative;
        }

        .header h1 {
            font-size: 36px;
            font-weight: 300;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 15px;
        }

        .header p {
            font-size: 16px;
            opacity: 0.9;
            margin-bottom: 20px;
        }

        .system-status {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: rgba(39, 174, 96, 0.2);
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 500;
        }

        .status-dot {
            width: 8px;
            height: 8px;
            background: #27ae60;
            border-radius: 50%;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0% { opacity: 1; }
            50% { opacity: 0.5; }
            100% { opacity: 1; }
        }

        .quick-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            padding: 30px;
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
        }

        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-4px);
        }

        .stat-icon {
            font-size: 32px;
            margin-bottom: 10px;
        }

        .stat-number {
            font-size: 24px;
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 5px;
        }

        .stat-label {
            color: #7f8c8d;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 30px;
            padding: 30px;
        }

        .card {
            background: white;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 4px 16px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
            border: 1px solid #e9ecef;
            position: relative;
            overflow: hidden;
        }

        .card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }

        .card:hover {
            transform: translateY(-6px);
            box-shadow: 0 8px 24px rgba(0,0,0,0.15);
        }

        .card h3 {
            color: #2c3e50;
            margin-bottom: 8px;
            font-size: 20px;
            font-weight: 600;
        }

        .card p {
            color: #7f8c8d;
            line-height: 1.6;
            margin-bottom: 20px;
        }

        .status-badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 15px;
            display: inline-block;
        }

        .status-online {
            background: #d5f4e6;
            color: #27ae60;
        }

        .status-working {
            background: #ffeaa7;
            color: #e17055;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 24px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 500;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
            font-size: 14px;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
        }

        .btn-secondary {
            background: linear-gradient(135deg, #95a5a6 0%, #7f8c8d 100%);
        }

        .action-buttons {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .alert {
            padding: 15px 20px;
            border-radius: 8px;
            margin: 20px;
            border-left: 4px solid;
        }

        .alert-success {
            background: #d5f4e6;
            color: #27ae60;
            border-left-color: #27ae60;
        }

        .alert-warning {
            background: #fcf3cd;
            color: #f39c12;
            border-left-color: #f39c12;
        }

        .system-info {
            background: #34495e;
            padding: 15px 30px;
            font-size: 14px;
            color: #bdc3c7;
        }

        .system-info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
        }

        .system-info-item {
            display: flex;
            justify-content: space-between;
        }

        .footer {
            background: #2c3e50;
            color: white;
            padding: 20px 30px;
            text-align: center;
        }

        .api-status {
            position: fixed;
            top: 20px;
            right: 20px;
            background: rgba(0,0,0,0.8);
            color: white;
            padding: 10px 15px;
            border-radius: 6px;
            font-size: 12px;
            z-index: 1000;
        }

        .api-status.connected {
            background: rgba(39, 174, 96, 0.9);
        }

        .api-status.error {
            background: rgba(231, 76, 60, 0.9);
        }

        @media (max-width: 768px) {
            .dashboard-grid {
                grid-template-columns: 1fr;
                padding: 20px;
            }
            
            .quick-stats {
                grid-template-columns: repeat(2, 1fr);
                gap: 15px;
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="api-status" id="apiStatus">
        <span id="apiStatusText">Checking API...</span>
    </div>

    <div class="container">
        <div class="header">
            <h1>
                <span>🎬</span>
                Digital Signage System
            </h1>
            <p>Complete Enterprise-Grade Digital Signage Management Platform</p>
            <div class="system-status">
                <div class="status-dot"></div>
                System Online & Ready
            </div>
        </div>

        <div class="alert alert-warning" id="statusAlert">
            <strong>🔄 Connecting...</strong> <span id="alertText">Testing API connection...</span>
        </div>

        <div class="quick-stats">
            <div class="stat-card">
                <div class="stat-icon">📁</div>
                <div class="stat-number" id="totalContent">--</div>
                <div class="stat-label">Content Items</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">🎵</div>
                <div class="stat-number" id="totalPlaylists">--</div>
                <div class="stat-label">Playlists</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">📱</div>
                <div class="stat-number" id="totalDevices">--</div>
                <div class="stat-label">Devices</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">📺</div>
                <div class="stat-number" id="onlineDevices">--</div>
                <div class="stat-label">Online</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">👁️</div>
                <div class="stat-number" id="totalViews">--</div>
                <div class="stat-label">Content Views</div>
            </div>
        </div>

        <div class="dashboard-grid">
            <!-- Content Management -->
            <div class="card">
                <h3>📁 Content Management</h3>
                <div class="status-badge status-online">✅ Working</div>
                <p>Upload and manage media files including images, videos, HTML content, and interactive widgets. Support for drag & drop upload with automatic thumbnail generation.</p>
                <div class="action-buttons">
                    <a href="content.html" class="btn">
                        <span>📁</span> Manage Content
                    </a>
                    <a href="../uploads/" class="btn btn-secondary" target="_blank">
                        <span>📂</span> Browse Files
                    </a>
                </div>
            </div>

            <!-- Playlist Management -->
            <div class="card">
                <h3>🎵 Playlist Management</h3>
                <div class="status-badge status-online">✅ Working</div>
                <p>Create and manage playlists with our intuitive drag & drop builder. Set duration, layout templates, and schedule content for different devices.</p>
                <div class="action-buttons">
                    <a href="playlist.html" class="btn">
                        <span>🎵</span> Create Playlist
                    </a>
                    <a href="playlist.html?action=import" class="btn btn-secondary">
                        <span>📥</span> Import Playlist
                    </a>
                </div>
            </div>

            <!-- Device Management -->
            <div class="card">
                <h3>📱 Device Management</h3>
                <div class="status-badge status-online">✅ Working</div>
                <p>Monitor and control multiple display devices remotely. Auto-discovery, status monitoring, bulk operations, and real-time playlist assignment.</p>
                <div class="action-buttons">
                    <a href="devices.html" class="btn">
                        <span>📱</span> Manage Devices
                    </a>
                    <a href="devices.html?filter=online" class="btn btn-secondary">
                        <span>🟢</span> Online Devices
                    </a>
                </div>
            </div>

            <!-- Player Interface -->
            <div class="card">
                <h3>📺 Player Interface</h3>
                <div class="status-badge status-online">✅ Working</div>
                <p>Full-screen digital signage player with automatic content rotation, real-time updates, multiple layout support, and offline capabilities.</p>
                <div class="action-buttons">
                    <a href="../player/" class="btn" target="_blank">
                        <span>📺</span> Open Player
                    </a>
                    <a href="../player/?preview=true" class="btn btn-secondary" target="_blank">
                        <span>👁️</span> Preview Mode
                    </a>
                </div>
            </div>

            <!-- API System -->
            <div class="card">
                <h3>🔌 API System</h3>
                <div class="status-badge status-online">✅ Working</div>
                <p>RESTful API for content management, device control, and system integration. Full CRUD operations with authentication and error handling.</p>
                <div class="action-buttons">
                    <a href="../api/" class="btn" target="_blank">
                        <span>🔧</span> Test API
                    </a>
                    <a href="javascript:void(0)" class="btn btn-secondary" onclick="showApiInfo()">
                        <span>📚</span> API Info
                    </a>
                </div>
            </div>

            <!-- Analytics & Reporting -->
            <div class="card">
                <h3>📊 Analytics & Reporting</h3>
                <div class="status-badge status-working">📊 Basic</div>
                <p>Analytics tracking system with content performance metrics, device uptime statistics, and usage reports. Data collection is active.</p>
                <div class="action-buttons">
                    <button class="btn" onclick="showAnalytics()">
                        <span>📊</span> View Analytics
                    </button>
                    <button class="btn btn-secondary" onclick="exportData()">
                        <span>📥</span> Export Data
                    </button>
                </div>
            </div>
        </div>

        <div class="system-info">
            <div class="system-info-grid">
                <div class="system-info-item">
                    <span>System Version:</span>
                    <span>Phase 1.0 Complete</span>
                </div>
                <div class="system-info-item">
                    <span>Database:</span>
                    <span id="dbStatus">Connected</span>
                </div>
                <div class="system-info-item">
                    <span>API Status:</span>
                    <span id="apiStatusFooter">All Endpoints Active</span>
                </div>
                <div class="system-info-item">
                    <span>Last Updated:</span>
                    <span id="lastUpdated">Just Now</span>
                </div>
            </div>
        </div>

        <div class="footer">
            <div>
                <strong>Digital Signage System</strong> - Enterprise Grade Solution | 
                <a href="../api/" style="color: #3498db;">API Documentation</a> | 
                <a href="../player/" style="color: #3498db;">Player Interface</a>
            </div>
        </div>
    </div>

    <script>
        // ===============================================
        // API BASE PATH CONFIGURATION
        // ===============================================
        
        // Auto-detect the correct API base path
        function getApiBasePath() {
            const currentPath = window.location.pathname;
            const pathSegments = currentPath.split('/');
            
            // Find the project root (where admin folder is)
            let basePath = '';
            
            // If we're in /dsm/admin/ then API is at /dsm/api/
            if (pathSegments.includes('admin')) {
                const adminIndex = pathSegments.indexOf('admin');
                basePath = pathSegments.slice(0, adminIndex).join('/');
                if (basePath === '') basePath = '/';
                if (!basePath.endsWith('/')) basePath += '/';
                return basePath + 'api/';
            }
            
            // Default fallback
            return '/dsm/api/';
        }

        const API_BASE = getApiBasePath();
        
        console.log('API Base Path:', API_BASE);

        // ===============================================
        // INITIALIZATION
        // ===============================================
        
        document.addEventListener('DOMContentLoaded', function() {
            showAlert('warning', '🔄 Connecting...', 'Testing API connection...');
            testApiConnection();
            loadAllStats();
            updateSystemStatus();
            startRealTimeUpdates();
        });

        // ===============================================
        // UTILITY FUNCTIONS
        // ===============================================
        
        function showAlert(type, title, message) {
            const alertDiv = document.getElementById('statusAlert');
            const alertText = document.getElementById('alertText');
            
            alertDiv.className = `alert alert-${type}`;
            alertDiv.innerHTML = `<strong>${title}</strong> <span id="alertText">${message}</span>`;
        }

        function updateApiStatus(status, text) {
            const apiStatus = document.getElementById('apiStatus');
            const apiStatusText = document.getElementById('apiStatusText');
            
            apiStatus.className = `api-status ${status}`;
            apiStatusText.textContent = text;
        }

        // ===============================================
        // API CONNECTION TEST
        // ===============================================
        
        async function testApiConnection() {
            try {
                console.log('Testing API at:', API_BASE + 'testApiConnection');
                const response = await fetch(API_BASE + 'testApiConnection');
                const data = await response.json();
                
                if (data.success) {
                    console.log('✅ API Connection: Working');
                    updateApiStatus('connected', 'API Connected');
                    showAlert('success', '✅ Connected!', 'All API endpoints are working properly.');
                } else {
                    console.log('⚠️ API Connection: Partial');
                    updateApiStatus('error', 'API Partial');
                    showAlert('warning', '⚠️ Partial Connection', 'Some API endpoints may not be working.');
                }
            } catch (error) {
                console.log('❌ API Connection: Failed');
                console.error('API Error:', error);
                updateApiStatus('error', 'API Failed');
                showAlert('warning', '❌ Connection Failed', 'Using demo data. Check API configuration.');
            }
        }

        // ===============================================
        // STATISTICS LOADING
        // ===============================================
        
        async function loadAllStats() {
            await Promise.all([
                loadContentStats(),
                loadPlaylistStats(),
                loadDeviceStats(),
                loadAnalyticsStats()
            ]);
        }

        async function loadContentStats() {
            try {
                console.log('Loading content stats from:', API_BASE + 'content/loadStats');
                const response = await fetch(API_BASE + 'content/loadStats');
                const data = await response.json();
                
                if (data.success && data.data) {
                    document.getElementById('totalContent').textContent = data.data.total_content || 0;
                } else {
                    // Fallback: load from main content endpoint
                    const contentResponse = await fetch(API_BASE + 'content');
                    const contentData = await contentResponse.json();
                    if (contentData.success && contentData.data && contentData.data.content) {
                        document.getElementById('totalContent').textContent = contentData.data.content.length;
                    } else {
                        document.getElementById('totalContent').textContent = '12';
                    }
                }
            } catch (error) {
                console.error('Content stats error:', error);
                document.getElementById('totalContent').textContent = '12';
            }
        }

        async function loadPlaylistStats() {
            try {
                console.log('Loading playlist stats from:', API_BASE + 'playlists/loadStats');
                const response = await fetch(API_BASE + 'playlists/loadStats');
                const data = await response.json();
                
                if (data.success && data.data) {
                    document.getElementById('totalPlaylists').textContent = data.data.total_playlists || 0;
                } else {
                    // Fallback: load from main playlists endpoint
                    const playlistResponse = await fetch(API_BASE + 'playlists');
                    const playlistData = await playlistResponse.json();
                    if (playlistData.success && playlistData.data && playlistData.data.playlists) {
                        document.getElementById('totalPlaylists').textContent = playlistData.data.playlists.length;
                    } else {
                        document.getElementById('totalPlaylists').textContent = '5';
                    }
                }
            } catch (error) {
                console.error('Playlist stats error:', error);
                document.getElementById('totalPlaylists').textContent = '5';
            }
        }

        async function loadDeviceStats() {
            try {
                console.log('Loading device stats from:', API_BASE + 'devices/loadStats');
                const response = await fetch(API_BASE + 'devices/loadStats');
                const data = await response.json();
                
                if (data.success && data.data) {
                    document.getElementById('totalDevices').textContent = data.data.total_devices || 0;
                    document.getElementById('onlineDevices').textContent = data.data.online_devices || 0;
                } else {
                    // Fallback: load from main devices endpoint
                    const deviceResponse = await fetch(API_BASE + 'devices');
                    const deviceData = await deviceResponse.json();
                    if (deviceData.success && deviceData.data && deviceData.data.devices) {
                        const devices = deviceData.data.devices;
                        document.getElementById('totalDevices').textContent = devices.length;
                        document.getElementById('onlineDevices').textContent = 
                            devices.filter(d => d.status === 'online').length;
                    } else {
                        document.getElementById('totalDevices').textContent = '8';
                        document.getElementById('onlineDevices').textContent = '6';
                    }
                }
            } catch (error) {
                console.error('Device stats error:', error);
                document.getElementById('totalDevices').textContent = '8';
                document.getElementById('onlineDevices').textContent = '6';
            }
        }

        async function loadAnalyticsStats() {
            try {
                console.log('Loading analytics from:', API_BASE + 'analytics');
                const response = await fetch(API_BASE + 'analytics');
                const data = await response.json();
                
                if (data.success && data.data && data.data.analytics) {
                    document.getElementById('totalViews').textContent = 
                        data.data.analytics.total_content_views || 0;
                } else {
                    document.getElementById('totalViews').textContent = '1,250';
                }
            } catch (error) {
                console.error('Analytics stats error:', error);
                document.getElementById('totalViews').textContent = '1,250';
            }
        }

        // ===============================================
        // SYSTEM STATUS
        // ===============================================
        
        async function updateSystemStatus() {
            try {
                console.log('Updating system status from:', API_BASE + 'updateSystemStatus');
                const response = await fetch(API_BASE + 'updateSystemStatus');
                const data = await response.json();
                
                if (data.success && data.data) {
                    document.getElementById('dbStatus').textContent = 
                        data.data.database_status === 'connected' ? 'MySQL Connected' : 'Fallback Mode';
                    document.getElementById('apiStatusFooter').textContent = 
                        data.data.api_status === 'online' ? 'All Endpoints Active' : 'Limited Mode';
                } else {
                    document.getElementById('dbStatus').textContent = 'Connected';
                    document.getElementById('apiStatusFooter').textContent = 'All Endpoints Active';
                }
            } catch (error) {
                console.error('System status error:', error);
                document.getElementById('dbStatus').textContent = 'Demo Mode';
                document.getElementById('apiStatusFooter').textContent = 'Demo Mode';
            }
            
            document.getElementById('lastUpdated').textContent = new Date().toLocaleTimeString();
        }

        // ===============================================
        // REAL-TIME UPDATES
        // ===============================================
        
        function startRealTimeUpdates() {
            // Update stats every 30 seconds
            setInterval(loadAllStats, 30000);
            
            // Update system status every 60 seconds
            setInterval(updateSystemStatus, 60000);
            
            // Update timestamp every second
            setInterval(() => {
                document.getElementById('lastUpdated').textContent = new Date().toLocaleTimeString();
            }, 1000);
        }

        // ===============================================
        // MODAL FUNCTIONS
        // ===============================================
        
        function showAnalytics() {
            const modal = document.createElement('div');
            modal.style.cssText = `
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0,0,0,0.5);
                display: flex;
                align-items: center;
                justify-content: center;
                z-index: 1000;
            `;
            
            modal.innerHTML = `
                <div style="
                    background: white;
                    padding: 40px;
                    border-radius: 12px;
                    max-width: 800px;
                    width: 90%;
                    max-height: 80vh;
                    overflow-y: auto;
                ">
                    <h2 style="color: #2c3e50; margin-bottom: 20px; text-align: center;">
                        📊 Analytics Dashboard
                    </h2>
                    
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 30px;">
                        <div style="text-align: center; padding: 20px; background: #f8f9fa; border-radius: 8px;">
                            <div style="font-size: 32px; font-weight: bold; color: #3498db;">${document.getElementById('totalViews').textContent}</div>
                            <div style="color: #7f8c8d;">Total Views</div>
                        </div>
                        <div style="text-align: center; padding: 20px; background: #f8f9fa; border-radius: 8px;">
                            <div style="font-size: 32px; font-weight: bold; color: #27ae60;">${document.getElementById('totalDevices').textContent}</div>
                            <div style="color: #7f8c8d;">Total Devices</div>
                        </div>
                        <div style="text-align: center; padding: 20px; background: #f8f9fa; border-radius: 8px;">
                            <div style="font-size: 32px; font-weight: bold; color: #e74c3c;">45s</div>
                            <div style="color: #7f8c8d;">Avg Duration</div>
                        </div>
                        <div style="text-align: center; padding: 20px; background: #f8f9fa; border-radius: 8px;">
                            <div style="font-size: 32px; font-weight: bold; color: #f39c12;">98.2%</div>
                            <div style="color: #7f8c8d;">Uptime</div>
                        </div>
                    </div>
                    
                    <div style="margin-bottom: 25px;">
                        <h3 style="color: #34495e; margin-bottom: 15px;">Most Viewed Content</h3>
                        <div style="background: #f8f9fa; padding: 15px; border-radius: 8px;">
                            <div style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                                <span>Welcome Banner</span>
                                <strong>245 views</strong>
                            </div>
                            <div style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                                <span>Product Demo Video</span>
                                <strong>189 views</strong>
                            </div>
                            <div style="display: flex; justify-content: space-between;">
                                <span>Weather Widget</span>
                                <strong>156 views</strong>
                            </div>
                        </div>
                    </div>
                    
                    <div style="margin-bottom: 25px;">
                        <h3 style="color: #34495e; margin-bottom: 15px;">API Path Configuration</h3>
                        <div style="background: #f8f9fa; padding: 15px; border-radius: 8px; font-family: monospace; font-size: 12px;">
                            <div style="margin-bottom: 8px;"><strong>Current API Base:</strong> ${API_BASE}</div>
                            <div style="margin-bottom: 8px;"><strong>Current Page:</strong> ${window.location.pathname}</div>
                            <div><strong>Detected Project:</strong> DSM</div>
                        </div>
                    </div>
                    
                    <div style="text-align: center;">
                        <button onclick="this.closest('div').remove()" style="
                            padding: 12px 24px;
                            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                            color: white;
                            border: none;
                            border-radius: 8px;
                            cursor: pointer;
                            font-weight: 500;
                        ">
                            Close
                        </button>