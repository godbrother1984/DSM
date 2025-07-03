<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Digital Signage Player - Working</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            background: #000; 
            color: #fff; 
            font-family: Arial, sans-serif; 
            overflow: hidden; 
            cursor: none; 
        }
        .player-container { 
            position: relative; 
            width: 100vw; 
            height: 100vh; 
            display: flex; 
            align-items: center; 
            justify-content: center; 
        }
        .content-display { 
            width: 100%; 
            height: 100%; 
            position: relative; 
        }
        .content-item { 
            position: absolute; 
            top: 0; 
            left: 0; 
            width: 100%; 
            height: 100%; 
            display: flex; 
            align-items: center; 
            justify-content: center; 
            opacity: 0; 
            transition: opacity 1s ease-in-out; 
        }
        .content-item.active { opacity: 1; }
        .content-item img, .content-item video { 
            max-width: 100%; 
            max-height: 100%; 
            object-fit: contain; 
        }
        .text-content { 
            text-align: center; 
            padding: 2rem; 
            font-size: 3rem; 
        }
        .status-indicator { 
            position: fixed; 
            top: 20px; 
            right: 20px; 
            background: rgba(0,0,0,0.7); 
            padding: 10px 20px; 
            border-radius: 20px; 
            font-size: 14px; 
            z-index: 1000; 
        }
        .progress-bar { 
            position: fixed; 
            bottom: 0; 
            left: 0; 
            width: 100%; 
            height: 4px; 
            background: rgba(255,255,255,0.2); 
            z-index: 1000; 
        }
        .progress-fill { 
            height: 100%; 
            background: linear-gradient(90deg, #007bff, #0056b3); 
            width: 0%; 
            transition: width 0.1s linear; 
        }
        .loading-screen { 
            position: fixed; 
            top: 0; 
            left: 0; 
            width: 100%; 
            height: 100%; 
            background: linear-gradient(135deg, #007bff 0%, #0056b3 100%); 
            display: flex; 
            flex-direction: column; 
            align-items: center; 
            justify-content: center; 
            z-index: 2000; 
        }
        .loading-logo { font-size: 4rem; margin-bottom: 2rem; }
        .loading-text { font-size: 2rem; margin-bottom: 2rem; }
        .loading-spinner { 
            width: 60px; 
            height: 60px; 
            border: 4px solid rgba(255,255,255,0.3); 
            border-top: 4px solid #fff; 
            border-radius: 50%; 
            animation: spin 1s linear infinite; 
        }
        @keyframes spin { 
            0% { transform: rotate(0deg); } 
            100% { transform: rotate(360deg); } 
        }
    </style>
</head>
<body>
    <!-- Loading Screen -->
    <div id="loading-screen" class="loading-screen">
        <div class="loading-logo">🎬</div>
        <div class="loading-text">Digital Signage Player</div>
        <div class="loading-spinner"></div>
        <div style="margin-top: 2rem; text-align: center;">
            <div id="loading-message">Initializing player...</div>
            <div id="device-info" style="margin-top: 1rem; font-size: 1rem;"></div>
        </div>
    </div>

    <!-- Player Container -->
    <div id="player-container" class="player-container" style="display: none;">
        <div id="content-display" class="content-display">
            <!-- Content will be rendered here -->
        </div>

        <!-- Progress Bar -->
        <div class="progress-bar">
            <div id="progress-fill" class="progress-fill"></div>
        </div>

        <!-- Status Indicator -->
        <div id="status-indicator" class="status-indicator">
            🟢 Playing
        </div>
    </div>

    <script>
        let deviceId = null;
        let currentPlaylist = null;
        let currentContentIndex = 0;
        let contentTimer = null;
        let apiBase = "/api";

        // Initialize player
        async function initializePlayer() {
            try {
                updateLoadingMessage("Generating device ID...");
                deviceId = generateDeviceId();
                
                updateLoadingMessage("Registering device...");
                await registerDevice();
                
                updateLoadingMessage("Loading playlist...");
                await loadPlaylist();
                
                updateLoadingMessage("Starting playback...");
                startPlayback();
                
                hideLoadingScreen();
                
            } catch (error) {
                console.error("Initialization failed:", error);
                updateLoadingMessage("Error: " + error.message);
            }
        }

        function generateDeviceId() {
            let id = localStorage.getItem("signage_device_id");
            if (!id) {
                id = "device-" + Date.now() + "-" + Math.random().toString(36).substr(2, 9);
                localStorage.setItem("signage_device_id", id);
            }
            return id;
        }

        async function registerDevice() {
            try {
                const deviceData = {
                    device_id: deviceId,
                    name: `Display ${deviceId.substr(-8)}`,
                    screen_width: screen.width,
                    screen_height: screen.height,
                    device_type: "display"
                };

                const response = await fetch(apiBase + "/player/register", {
                    method: "POST",
                    headers: { "Content-Type": "application/json" },
                    body: JSON.stringify(deviceData)
                });

                const data = await response.json();
                
                if (data.success) {
                    updateDeviceInfo(data.data.device);
                } else {
                    throw new Error(data.message);
                }
            } catch (error) {
                console.warn("Device registration failed, continuing with demo mode:", error);
                updateDeviceInfo({ id: deviceId, name: "Demo Device" });
            }
        }

        async function loadPlaylist() {
            try {
                const response = await fetch(apiBase + `/player/playlist?device_id=${deviceId}`);
                const data = await response.json();

                if (data.success && data.data.playlist) {
                    currentPlaylist = data.data.playlist;
                } else {
                    // Use fallback playlist
                    currentPlaylist = {
                        id: 1,
                        name: "Demo Playlist",
                        items: [
                            {
                                content_id: 1,
                                title: "Welcome Message",
                                type: "image",
                                file_url: "https://picsum.photos/1920/1080?text=Welcome+to+Digital+Signage",
                                duration: 10
                            },
                            {
                                content_id: 2,
                                title: "Sample Video",
                                type: "video",
                                file_url: "https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/BigBuckBunny.mp4",
                                duration: 15
                            },
                            {
                                content_id: 3,
                                title: "Information",
                                type: "text",
                                file_url: "Your digital signage content will appear here",
                                duration: 8
                            }
                        ]
                    };
                }
            } catch (error) {
                console.warn("Failed to load playlist, using demo content:", error);
                // Use minimal fallback
                currentPlaylist = {
                    id: 1,
                    name: "Fallback Playlist",
                    items: [
                        {
                            content_id: 1,
                            title: "System Ready",
                            type: "text", 
                            file_url: "Digital Signage System is Ready",
                            duration: 5
                        }
                    ]
                };
            }
        }

        function startPlayback() {
            if (!currentPlaylist || !currentPlaylist.items || currentPlaylist.items.length === 0) {
                showErrorMessage("No content available");
                return;
            }

            currentContentIndex = 0;
            playCurrentContent();
        }

        function playCurrentContent() {
            const content = currentPlaylist.items[currentContentIndex];
            const duration = (content.duration || 10) * 1000;

            renderContent(content);
            startProgressBar(duration);

            if (contentTimer) {
                clearTimeout(contentTimer);
            }

            contentTimer = setTimeout(() => {
                nextContent();
            }, duration);
        }

        function renderContent(content) {
            const container = document.getElementById("content-display");
            
            // Clear previous content
            container.innerHTML = "";

            const contentDiv = document.createElement("div");
            contentDiv.className = "content-item active";

            switch (content.type) {
                case "image":
                    contentDiv.innerHTML = `<img src="${content.file_url}" alt="${content.title}">`;
                    break;

                case "video":
                    contentDiv.innerHTML = `
                        <video autoplay muted onended="nextContent()">
                            <source src="${content.file_url}" type="video/mp4">
                            Your browser does not support video playback.
                        </video>
                    `;
                    break;

                case "text":
                default:
                    contentDiv.innerHTML = `
                        <div class="text-content">
                            <h1>${content.title}</h1>
                            <p>${content.file_url}</p>
                        </div>
                    `;
                    break;
            }

            container.appendChild(contentDiv);
        }

        function nextContent() {
            currentContentIndex = (currentContentIndex + 1) % currentPlaylist.items.length;
            playCurrentContent();
        }

        function startProgressBar(duration) {
            const progressFill = document.getElementById("progress-fill");
            let startTime = Date.now();

            function updateProgress() {
                const elapsed = Date.now() - startTime;
                const progress = Math.min((elapsed / duration) * 100, 100);
                progressFill.style.width = progress + "%";

                if (progress < 100) {
                    requestAnimationFrame(updateProgress);
                }
            }

            progressFill.style.width = "0%";
            requestAnimationFrame(updateProgress);
        }

        function updateLoadingMessage(message) {
            document.getElementById("loading-message").textContent = message;
        }

        function updateDeviceInfo(device) {
            document.getElementById("device-info").textContent = `Device: ${device.name} | ID: ${device.id}`;
        }

        function hideLoadingScreen() {
            document.getElementById("loading-screen").style.display = "none";
            document.getElementById("player-container").style.display = "flex";
        }

        function showErrorMessage(message) {
            const container = document.getElementById("content-display");
            container.innerHTML = `
                <div class="content-item active">
                    <div class="text-content">
                        <h1>⚠️ Error</h1>
                        <p>${message}</p>
                    </div>
                </div>
            `;
        }

        // Keyboard controls
        document.addEventListener("keydown", function(event) {
            switch(event.key) {
                case "ArrowRight":
                case " ":
                    nextContent();
                    break;
                case "r":
                    location.reload();
                    break;
                case "f":
                    if (document.fullscreenElement) {
                        document.exitFullscreen();
                    } else {
                        document.documentElement.requestFullscreen();
                    }
                    break;
            }
        });

        // Initialize when page loads
        document.addEventListener("DOMContentLoaded", initializePlayer);
    </script>
</body>
</html>