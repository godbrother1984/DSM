<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Digital Signage Management System</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: Arial, sans-serif; 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); 
            min-height: 100vh; 
            display: flex; 
            align-items: center; 
            justify-content: center;
        }
        .container { 
            text-align: center; 
            background: white; 
            padding: 60px; 
            border-radius: 20px; 
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            max-width: 600px;
        }
        h1 { 
            font-size: 3rem; 
            margin-bottom: 20px; 
            background: linear-gradient(135deg, #667eea, #764ba2);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        p { 
            font-size: 1.2rem; 
            color: #666; 
            margin-bottom: 40px; 
        }
        .btn { 
            background: linear-gradient(135deg, #667eea, #764ba2); 
            color: white; 
            padding: 15px 30px; 
            border: none; 
            border-radius: 8px; 
            text-decoration: none; 
            display: inline-block; 
            font-size: 16px; 
            margin: 10px; 
            transition: transform 0.3s ease; 
        }
        .btn:hover { transform: translateY(-2px); }
        .system-info {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-top: 30px;
            text-align: left;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🎬 DSM</h1>
        <p>Digital Signage Management System</p>
        
        <div>
            <a href="admin/" class="btn">📋 Admin Dashboard</a>
            <a href="player/" class="btn" target="_blank">📺 Player Interface</a>
            <a href="api/" class="btn" target="_blank">🔌 API Documentation</a>
        </div>
        
        <div class="system-info">
            <h3>🎯 System Overview</h3>
            <ul style="text-align: left; margin-top: 15px; padding-left: 20px;">
                <li><strong>Admin Dashboard:</strong> Manage content and system settings</li>
                <li><strong>Player Interface:</strong> Full-screen content display</li>
                <li><strong>API System:</strong> RESTful API for integration</li>
                <li><strong>File Upload:</strong> Drag & drop content management</li>
            </ul>
        </div>
    </div>
</body>
</html>