<?php

require_once '/app/vendor/autoload.php';

// Your publisher JWT (use the one you already have or generate properly)
define('PUBLISHER_JWT', 'eyJhbGciOiJIUzI1NiJ9.eyJtZXJjdXJlIjp7InB1Ymxpc2giOlsiKiJdLCJzdWJzY3JpYmUiOlsiaHR0cHM6Ly9leGFtcGxlLmNvbS9teS1wcml2YXRlLXRvcGljIiwie3NjaGVtZX06Ly97K2hvc3R9L2RlbW8vYm9va3Mve2lkfS5qc29ubGQiLCIvLndlbGwta25vd24vbWVyY3VyZS9zdWJzY3JpcHRpb25zey90b3BpY317L3N1YnNjcmliZXJ9Il0sInBheWxvYWQiOnsidXNlciI6Imh0dHBzOi8vZXhhbXBsZS5jb20vdXNlcnMvZHVuZ2xhcyIsInJlbW90ZUFkZHIiOiIxMjcuMC4wLjEifX19.KKPIikwUzRuB3DTpVw6ajzwSChwFw5omBMmMcWKiDcM');

while (frankenphp_handle_request(function () {
    // Parse URL path without query string
    $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

    switch ($path) {
        case '/':
            echo '<!DOCTYPE html>
<html>
<head>
    <title>FrankenPHP App</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen">
    <div class="container mx-auto px-4 py-8">
        <h1 class="text-4xl font-bold text-blue-600 mb-8">FrankenPHP Worker App</h1>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <a href="/api/users" class="bg-white p-6 rounded-lg shadow hover:shadow-lg transition">
                <h2 class="text-xl font-semibold mb-2">API Users</h2>
                <p class="text-gray-600">View JSON user data</p>
            </a>
            <a href="/subscribe" class="bg-white p-6 rounded-lg shadow hover:shadow-lg transition">
                <h2 class="text-xl font-semibold mb-2">Mercure Subscribe</h2>
                <p class="text-gray-600">Real-time messaging</p>
            </a>
            <a href="/send" class="bg-white p-6 rounded-lg shadow hover:shadow-lg transition">
                <h2 class="text-xl font-semibold mb-2">Send Message</h2>
                <p class="text-gray-600">Form to send messages</p>
            </a>
            <a href="/editor" class="bg-white p-6 rounded-lg shadow hover:shadow-lg transition">
                <h2 class="text-xl font-semibold mb-2">Collaborative Editor</h2>
                <p class="text-gray-600">Real-time text editing</p>
            </a>
            <a href="/publish" class="bg-white p-6 rounded-lg shadow hover:shadow-lg transition">
                <h2 class="text-xl font-semibold mb-2">Publish Message</h2>
                <p class="text-gray-600">Send Mercure message</p>
            </a>
            <a href="/about" class="bg-white p-6 rounded-lg shadow hover:shadow-lg transition">
                <h2 class="text-xl font-semibold mb-2">About</h2>
                <p class="text-gray-600">Learn more</p>
            </a>
        </div>
    </div>
</body>
</html>';
            break;
            
        case '/api/users':
            header('Content-Type: application/json');
            echo json_encode(['users' => ['john', 'jane']]);
            break;
            
        case '/api/collaborate':
            header('Content-Type: application/json');
            
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $input = json_decode(file_get_contents('php://input'), true);
                $action = $input['action'] ?? '';
                
                if ($action === 'publish') {
                    try {
                        $documentId = $input['document_id'] ?? 'default';
                        $content = $input['content'] ?? '';
                        $userId = $input['user_id'] ?? 0;
                        $userName = $input['user_name'] ?? 'Anonymous';
                        $cursorPosition = $input['cursor_position'] ?? null;
                        
                        $topic = "documents/{$documentId}";
                        
                        $data = json_encode([
                            'content' => $content,
                            'cursor_position' => $cursorPosition,
                            'user_id' => $userId,
                            'user_name' => $userName,
                            'timestamp' => date('c'),
                        ]);
                        
                        $updateID = file_get_contents('http://localhost/.well-known/mercure', 
                            context: stream_context_create(['http' => [
                                'method'  => 'POST',
                                'header'  => "Content-type: application/x-www-form-urlencoded\r\nAuthorization: Bearer " . PUBLISHER_JWT,
                                'content' => http_build_query([
                                    'topic' => $topic,
                                    'data' => $data,
                                ]),
                            ]])
                        );
                        
                        echo json_encode(['success' => true, 'update_id' => $updateID]);
                    } catch (Exception $e) {
                        http_response_code(500);
                        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
                    }
                } elseif ($action === 'auth') {
                    $documentId = $input['document_id'] ?? 'default';
                    $topic = "documents/{$documentId}";
                    
                    echo json_encode([
                        'token' => PUBLISHER_JWT,
                        'hub_url' => '/.well-known/mercure'
                    ]);
                } else {
                    http_response_code(400);
                    echo json_encode(['error' => 'Invalid action']);
                }
            } else {
                http_response_code(405);
                echo json_encode(['error' => 'Method not allowed']);
            }
            break;
            
        case '/editor':
            $documentId = $_GET['doc'] ?? 'default';
            $userId = $_GET['user'] ?? rand(1000, 9999);
            $userName = $_GET['name'] ?? 'User' . $userId;
            
            echo '<!DOCTYPE html>
<html>
<head>
    <title>Collaborative Editor</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen">
    <div class="container mx-auto px-4 py-8">
        <div class="mb-6">
            <h1 class="text-3xl font-bold text-blue-600">Collaborative Editor</h1>
            <p class="text-gray-600 mt-2">Document: <span class="font-mono">' . htmlspecialchars($documentId) . '</span></p>
            <p class="text-gray-600">You are: <span class="font-semibold">' . htmlspecialchars($userName) . '</span></p>
        </div>
        
        <div class="bg-white rounded-lg shadow p-6 mb-4">
            <div class="mb-4">
                <h3 class="text-sm font-semibold text-gray-700 mb-2">Active Users:</h3>
                <div id="active-users" class="flex gap-2 flex-wrap"></div>
            </div>
            
            <div id="connection-status" class="mb-4 p-2 rounded text-sm text-center">
                Connecting...
            </div>
            
            <textarea 
                id="editor" 
                class="w-full h-96 px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 font-mono text-sm"
                placeholder="Start typing... Your changes will be synchronized in real-time."
            ></textarea>
        </div>
        
        <a href="/" class="text-blue-600 hover:underline">← Back to Home</a>
    </div>

    <script>
    const DOCUMENT_ID = "' . $documentId . '";
    const USER_ID = ' . $userId . ';
    const USER_NAME = "' . htmlspecialchars($userName) . '";
    
    let eventSource = null;
    let isUpdating = false;
    let debounceTimer = null;
    const activeUsers = new Map();
    
    const editor = document.getElementById("editor");
    const statusDiv = document.getElementById("connection-status");
    const activeUsersDiv = document.getElementById("active-users");
    
    // Initialize Mercure connection
    async function initMercure() {
        try {
            const url = new URL("/.well-known/mercure", window.location);
            url.searchParams.append("topic", `documents/${DOCUMENT_ID}`);
            
            eventSource = new EventSource(url);
            
            eventSource.onopen = () => {
                statusDiv.textContent = "Connected ✓";
                statusDiv.className = "mb-4 p-2 rounded text-sm text-center bg-green-50 border border-green-200 text-green-700";
            };
            
            eventSource.onmessage = (event) => {
                const data = JSON.parse(event.data);
                
                // Only update if from different user
                if (data.user_id !== USER_ID) {
                    isUpdating = true;
                    const cursorPos = editor.selectionStart;
                    editor.value = data.content;
                    editor.setSelectionRange(cursorPos, cursorPos);
                    isUpdating = false;
                }
                
                // Update active users
                activeUsers.set(data.user_id, {
                    name: data.user_name,
                    timestamp: Date.now()
                });
                updateActiveUsers();
            };
            
            eventSource.onerror = (error) => {
                statusDiv.textContent = "Connection error - Reconnecting...";
                statusDiv.className = "mb-4 p-2 rounded text-sm text-center bg-red-50 border border-red-200 text-red-700";
                
                // Reconnect after 3 seconds
                setTimeout(() => {
                    if (eventSource) eventSource.close();
                    initMercure();
                }, 3000);
            };
            
        } catch (error) {
            console.error("Failed to initialize Mercure:", error);
        }
    }
    
    // Publish updates
    async function publishUpdate(content, cursorPosition) {
        try {
            await fetch("/api/collaborate", {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify({
                    action: "publish",
                    document_id: DOCUMENT_ID,
                    content: content,
                    cursor_position: cursorPosition,
                    user_id: USER_ID,
                    user_name: USER_NAME
                })
            });
        } catch (error) {
            console.error("Failed to publish:", error);
        }
    }
    
    // Update active users display
    function updateActiveUsers() {
        const now = Date.now();
        // Remove stale users (inactive for 10 seconds)
        for (const [id, user] of activeUsers.entries()) {
            if (now - user.timestamp > 10000) {
                activeUsers.delete(id);
            }
        }
        
        activeUsersDiv.innerHTML = "";
        for (const [id, user] of activeUsers.entries()) {
            const badge = document.createElement("span");
            badge.className = "px-3 py-1 bg-blue-100 text-blue-700 rounded-full text-sm";
            badge.textContent = user.name;
            activeUsersDiv.appendChild(badge);
        }
        
        if (activeUsers.size === 0) {
            activeUsersDiv.innerHTML = "<span class=\"text-gray-400 text-sm\">No other users</span>";
        }
    }
    
    // Handle editor input with debouncing
    editor.addEventListener("input", (e) => {
        if (isUpdating) return;
        
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(() => {
            publishUpdate(editor.value, editor.selectionStart);
        }, 300);
    });
    
    // Track cursor position
    editor.addEventListener("click", () => {
        if (!isUpdating) {
            publishUpdate(editor.value, editor.selectionStart);
        }
    });
    
    // Initialize
    initMercure();
    
    // Cleanup on page unload
    window.addEventListener("beforeunload", () => {
        if (eventSource) eventSource.close();
    });
    </script>
</body>
</html>';
            break;
            
        case '/send':
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $message = $_POST['message'] ?? '';
                $topic = $_POST['topic'] ?? 'chat';
                
                try {
                    $updateID = file_get_contents('http://localhost/.well-known/mercure', context: stream_context_create(['http' => [
                        'method'  => 'POST',
                        'header'  => "Content-type: application/x-www-form-urlencoded\r\nAuthorization: Bearer " . PUBLISHER_JWT,
                        'content' => http_build_query([
                            'topic' => $topic,
                            'data' => json_encode(['message' => $message, 'time' => date('H:i:s')]),
                        ]),
                    ]]));
                    
                    error_log("update $updateID published", 4);
                    $status = 'Message sent successfully!';
                    $statusClass = 'bg-green-50 border-green-200 text-green-800';
                } catch (Exception $e) {
                    $status = "Publish failed: " . $e->getMessage();
                    $statusClass = 'bg-red-50 border-red-200 text-red-800';
                }
            }
            
            echo '<!DOCTYPE html>
<html>
<head>
    <title>Send Message</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen">
    <div class="container mx-auto px-4 py-8">
        <h1 class="text-3xl font-bold text-blue-600 mb-6">Send Mercure Message</h1>
        ' . (isset($status) ? '<div class="mb-4 p-3 border rounded ' . $statusClass . '">' . $status . '</div>' : '') . '
        <div class="bg-white rounded-lg shadow p-6">
            <form method="POST" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Topic</label>
                    <input type="text" name="topic" value="chat" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Message</label>
                    <textarea name="message" rows="4" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Enter your message..."></textarea>
                </div>
                <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded-md hover:bg-blue-700 transition">Send Message</button>
            </form>
        </div>
        <a href="/" class="inline-block mt-4 text-blue-600 hover:underline">← Back to Home</a>
    </div>
</body>
</html>';
            break;
            
        case '/publish':
            try {
                $updateID = file_get_contents('http://localhost/.well-known/mercure', context: stream_context_create(['http' => [
                    'method'  => 'POST',
                    'header'  => "Content-type: application/x-www-form-urlencoded\r\nAuthorization: Bearer " . PUBLISHER_JWT,
                    'content' => http_build_query([
                        'topic' => 'chat',
                        'data' => json_encode(['test' => 'yohooo']),
                    ]),
                ]]));

                error_log("update $updateID published", 4);
                echo "Message published successfully!";
            } catch (Exception $e) {
                echo "Publish failed: " . $e->getMessage();
            }
            break;
            
        case '/subscribe':
            echo '<!DOCTYPE html>
<html>
<head>
    <title>Mercure Subscribe</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen">
    <div class="container mx-auto px-4 py-8">
        <h1 class="text-3xl font-bold text-blue-600 mb-6">Real-time Messages</h1>
        <div class="bg-white rounded-lg shadow p-6">
            <div id="messages" class="space-y-2 max-h-96 overflow-y-auto"></div>
        </div>
        <a href="/" class="inline-block mt-4 text-blue-600 hover:underline">← Back to Home</a>
    </div>
    <script>
    const url = new URL("/.well-known/mercure", window.location);
    url.searchParams.append("topic", "chat");
    const eventSource = new EventSource(url);
    eventSource.onmessage = function(event) {
        const div = document.createElement("div");
        div.className = "p-3 bg-green-50 border border-green-200 rounded";
        div.textContent = "Message: " + event.data;
        document.getElementById("messages").appendChild(div);
    };
    eventSource.onerror = function(event) {
        const div = document.createElement("div");
        div.className = "p-3 bg-red-50 border border-red-200 rounded";
        div.textContent = "Error: " + JSON.stringify(event);
        document.getElementById("messages").appendChild(div);
    };
    eventSource.onopen = function(event) {
        const div = document.createElement("div");
        div.className = "p-3 bg-blue-50 border border-blue-200 rounded";
        div.textContent = "Connected to Mercure";
        document.getElementById("messages").appendChild(div);
    };
    </script>
</body>
</html>';
            break;
            
        case '/about':
            echo '<!DOCTYPE html>
<html>
<head>
    <title>About - FrankenPHP App</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen">
    <div class="container mx-auto px-4 py-8">
        <h1 class="text-3xl font-bold text-blue-600 mb-6">About This App</h1>
        <div class="bg-white rounded-lg shadow p-6">
            <p class="text-gray-700 mb-4">This is a minimal FrankenPHP application featuring:</p>
            <ul class="list-disc list-inside space-y-2 text-gray-700">
                <li>Worker mode for high performance</li>
                <li>Multiple endpoints with routing</li>
                <li>Mercure real-time messaging</li>
                <li>Collaborative text editing</li>
                <li>TLS support</li>
                <li>Tailwind CSS styling</li>
            </ul>
        </div>
        <a href="/" class="inline-block mt-4 text-blue-600 hover:underline">← Back to Home</a>
    </div>
</body>
</html>';
            break;
            
        default:
            http_response_code(404);
            echo "Not Found";
    }
})) {
    // Continue handling requests
}