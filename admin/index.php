<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Digital Signage System - Admin Dashboard</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            color: #333;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        .header {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 30px;
            text-align: center;
            margin-bottom: 30px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
        }

        .header h1 {
            font-size: 2.5em;
            margin-bottom: 10px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .system-status {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            margin-top: 15px;
            font-weight: 500;
        }

        .status-dot {
            width: 12px;
            height: 12px;
            background: #27ae60;
            border-radius: 50%;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0% { box-shadow: 0 0 0 0 rgba(39, 174, 96, 0.7); }
            70% { box-shadow: 0 0 0 10px rgba(39, 174, 96, 0); }
            100% { box-shadow: 0 0 0 0 rgba(39, 174, 96, 0); }
        }

        .alert {
            padding: 15px 20px;
            border-radius: 12px;
            margin-bottom: 20px;
            border-left: 4px solid;
            backdrop-filter: blur(10px);
        }

        .alert-warning {
            background: rgba(255, 193, 7, 0.15);
            color: #856404;
            border-left-color: #ffc107;
        }

        .alert-success {
            background: rgba(40, 167, 69, 0.15);
            color: #155724;
            border-left-color: #28a745;
        }

        .alert-error {
            background: rgba(220, 53, 69, 0.15);
            color: #721c24;
            border-left-color: #dc3545;
        }

        .quick-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 16px;
            padding: 25px;
            text-align: center;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-5px);
        }

        .stat-icon {
            font-size: 2.5em;
            margin-bottom: 10px;
        }

        .stat-number {
            font-size: 2em;
            font-weight: bold;
            color: #667eea;
            margin-bottom: 5px;
        }

        .stat-label {
            color: #666;
            font-size: 0.9em;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 25px;
        }

        .card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(135deg, #667eea, #764ba2);
        }

        .card:hover {
            transform: translateY(-5px);
        }

        .card h3 {
            font-size: 1.3em;
            margin-bottom: 15px;
            color: #2c3e50;
        }

        .card p {
            color: #666;
            line-height: 1.6;
            margin-bottom: 20px;
        }

        .status-badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.75em;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 15px;
        }

        .status-online {
            background: #d4edda;
            color: #27ae60;
        }

        .status-working {
            background: #ffeaa7;
            color: #e17055;
        }

        .action-buttons {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 20px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            text-decoration: none;
            border-radius: 10px;
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
            background: linear-gradient(135deg, #95a5a6, #7f8c8d);
        }

        .api-status {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 10px 15px;
            border-radius: 25px;
            font-size: 0.8em;
            font-weight: 600;
            z-index: 1000;
        }

        .api-status.connected {
            background: #d4edda;
            color: #27ae60;
        }

        .api-status.error {
            background: #f8d7da;
            color: #721c24;
        }

        @media (max-width: 768px) {
            .header h1 {
                font-size: 2em;
            }
            
            .dashboard-grid {
                grid-template-columns: 1fr;
            }
            
            .quick-stats {
                grid-template-columns: repeat(2, 1fr);
            }
        }
    </style>
</head>
<body>
    <div class="api-status" id="apiStatus">
        <span id="apiStatusText">Connecting...</span>
    </div>

    <div class="container">
        <div class="header">
            <h1>🎬 Digital Signage System</h1>
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
                <p>RESTful API for content management, device control, and system integration. Complete documentation and testing tools available.</p>
                <div class="action-buttons">
                    <a href="../api/" class="btn" target="_blank">
                        <span>🔧</span> Test API
                    </a>
                    <a href="../test-api.html" class="btn btn-secondary" target="_blank">
                        <span>📋</span> API Docs
                    </a>
                </div>
            </div>

            <!-- Analytics (Phase 2) -->
            <div class="card">
                <h3>📊 Analytics & Reporting</h3>
                <div class="status-badge status-working">🔨 Phase 2</div>
                <p>Comprehensive analytics dashboard with content performance metrics, device statistics, and detailed reporting capabilities.</p>
                <div class="action-buttons">
                    <button class="btn" onclick="alert('📊 Analytics feature coming in Phase 2!')">
                        <span>📊</span> View Analytics
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        // API Configuration - Auto-detect correct path
        function detectApiPath() {
            const currentPath = window.location.pathname;
            const pathSegments = currentPath.split('/').filter(segment => segment);
            
            // Find project root by looking for 'admin' folder
            let basePath = '/';
            const adminIndex = pathSegments.indexOf('admin');
            
            if (adminIndex > 0) {
                basePath = '/' + pathSegments.slice(0, adminIndex).join('/') + '/';
            }
            
            return basePath + 'api/';
        }

        const API_BASE = detectApiPath();
        console.log('🔧 API Base Path:', API_BASE);

        // Global state
        let connectionStatus = 'connecting';
        let apiData = {};

        // Initialize dashboard
        document.addEventListener('DOMContentLoaded', function() {
            console.log('🚀 Initializing Admin Dashboard...');
            showAlert('warning', '🔄 Connecting...', 'Testing API connection...');
            updateApiStatus('error', 'Connecting...');
            
            // Start connection sequence
            setTimeout(testApiConnection, 500);
            setTimeout(loadDashboardStats, 1000);
            setTimeout(startHealthCheck, 2000);
        });

        // Test API Connection
        async function testApiConnection() {
            try {
                console.log('🔍 Testing API at:', API_BASE);
                
                const response = await fetch(API_BASE + 'testApiConnection', {
                    method: 'GET',
                    headers: {
                        'Content-Type': 'application/json'
                    }
                });

                const data = await response.json();
                
                if (response.ok && data.success) {
                    console.log('✅ API Connection: SUCCESS');
                    connectionStatus = 'connected';
                    updateApiStatus('connected', 'API Connected');
                    showAlert('success', '✅ Connected!', 'All API endpoints are working properly.');
                    apiData.connection = data.data;
                } else {
                    throw new Error('API returned error');
                }
                
            } catch (error) {
                console.error('❌ API Connection Failed:', error);
                connectionStatus = 'error';
                updateApiStatus('error', 'API Error');
                showAlert('error', '❌ Connection Failed', 'API not responding. Using demo data.');
            }
        }

        // Load Dashboard Statistics
        async function loadDashboardStats() {
            try {
                const response = await fetch(API_BASE + 'dashboard');
                const data = await response.json();
                
                if (data.success) {
                    updateStats(data.data);
                    console.log('📊 Dashboard stats loaded');
                } else {
                    throw new Error('Dashboard API error');
                }
                
            } catch (error) {
                console.log('📊 Using demo stats');
                // Use demo data
                updateStats({
                    total_content: 8,
                    total_playlists: 3,
                    total_devices: 5,
                    online_devices: 4,
                    total_views: 1247
                });
            }
        }

        // Update Statistics Display
        function updateStats(stats) {
            document.getElementById('totalContent').textContent = stats.total_content || 0;
            document.getElementById('totalPlaylists').textContent = stats.total_playlists || 0;
            document.getElementById('totalDevices').textContent = stats.total_devices || 0;
            document.getElementById('onlineDevices').textContent = stats.online_devices || 0;
            document.getElementById('totalViews').textContent = stats.total_views || 0;
        }

        // Update API Status Indicator
        function updateApiStatus(status, text) {
            const statusElement = document.getElementById('apiStatus');
            const textElement = document.getElementById('apiStatusText');
            
            statusElement.className = `api-status ${status}`;
            textElement.textContent = text;
        }

        // Show Alert Messages
        function showAlert(type, title, message) {
            const alertDiv = document.getElementById('statusAlert');
            const alertText = document.getElementById('alertText');
            
            alertDiv.className = `alert alert-${type}`;
            alertDiv.innerHTML = `<strong>${title}</strong> <span id="alertText">${message}</span>`;
        }

        // Start Health Check (every 30 seconds)
        function startHealthCheck() {
            setInterval(async () => {
                if (connectionStatus === 'connected') {
                    try {
                        const response = await fetch(API_BASE);
                        if (!response.ok) {
                            throw new Error('Health check failed');
                        }
                    } catch (error) {
                        console.log('⚠️ Connection lost, switching to offline mode');
                        connectionStatus = 'error';
                        updateApiStatus('error', 'Connection Lost');
                        showAlert('error', '⚠️ Connection Lost', 'API is no longer responding. Some features may be limited.');
                    }
                }
            }, 30000);
        }

        // Auto-hide success alerts after 5 seconds
        setTimeout(() => {
            const alert = document.getElementById('statusAlert');
            if (alert.classList.contains('alert-success')) {
                alert.style.transition = 'opacity 0.5s ease';
                alert.style.opacity = '0';
                setTimeout(() => {
                    alert.style.display = 'none';
                }, 500);
            }
        }, 5000);
    </script>
</body>
</html>