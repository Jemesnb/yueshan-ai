<?php
/**
 * 越山对话ai - 智能对话系统 (由mountain开发)
 */
$models_file = 'models.json';
$history = [];
$error_msg = "";

// 加载模型配置
if (!file_exists($models_file)) {
    die("错误：找不到 models.json 配置文件。");
}
$models = json_decode(file_get_contents($models_file), true);

// 处理表单提交
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_input = trim($_POST['user_input'] ?? '');
    $history_json = $_POST['history_json'] ?? '[]';
    $history = json_decode($history_json, true) ?: [];
    $model_index = intval($_POST['model_index'] ?? 0);
    $selected_model = $models[$model_index] ?? null;

    if ($user_input !== '' && $selected_model) {
        $history[] = ['role' => 'user', 'content' => $user_input];
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $selected_model['api_url']);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        
        $messages = array_merge([['role' => 'system', 'content' => 'You are a helpful assistant.']], $history);
        
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
            'model' => $selected_model['model_id'],
            'messages' => $messages,
            'stream' => false
        ]));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . trim($selected_model['api_key'])
        ]);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);
        $response = curl_exec($ch);
        $err = curl_error($ch);
        
        if ($err) {
            $error_msg = "网络连接失败: " . $err;
        } else {
            $res_data = json_decode($response, true);
            if (isset($res_data['choices'][0]['message']['content'])) {
                $ai_content = $res_data['choices'][0]['message']['content'];
                $history[] = ['role' => 'assistant', 'content' => $ai_content];
            } else {
                $error_msg = "接口错误: " . ($res_data['error']['message'] ?? "未知错误");
            }
        }
    }
}

if (isset($_GET['clear'])) {
    $history = [];
    header("Location: index.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>越山对话ai</title>
    <link rel="icon" href="https://mountainai.nekoweb.org/IMG_1282.jpeg" type="image/jpeg">
    <link href="https://fonts.googleapis.com/css2?family=Google+Sans:wght@400;500&family=Roboto:wght@400;500&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg-color: #ffffff;
            --sidebar-color: #f0f4f9;
            --text-main: #1f1f1f;
            --text-sub: #444746;
            --accent-blue: #1a73e8;
            --ai-bubble-bg: transparent;
            --user-bubble-bg: #f0f4f9;
            --input-bg: #f0f4f9;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: 'Google Sans', 'Roboto', Arial, sans-serif; 
            background: var(--bg-color); 
            color: var(--text-main);
            height: 100dvh;                /* 使用动态视口高度，兼容移动端 */
            display: flex;
            flex-direction: column;
            overflow: hidden;
            max-width: 100vw;
        }

        /* 顶部导航 - 固定在顶部 */
        header {
            padding: 12px 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: var(--bg-color);
            flex-shrink: 0;                /* 不收缩 */
            flex-wrap: wrap;
            gap: 8px;
            z-index: 10;
            border-bottom: 1px solid #f0f0f0; /* 轻微分隔 */
        }
        .brand {
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 20px;
            font-weight: 500;
            color: var(--text-main);
            flex-shrink: 0;
        }
        .brand img {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            object-fit: cover;
        }
        .header-actions {
            display: flex;
            gap: 15px;
            align-items: center;
            flex-shrink: 0;
        }
        .btn-clear {
            padding: 8px 16px;
            border-radius: 20px;
            border: 1px solid #dadce0;
            background: transparent;
            color: var(--text-sub);
            text-decoration: none;
            font-size: 14px;
            transition: background 0.2s;
            white-space: nowrap;
        }
        .btn-clear:hover { background: #f1f3f4; }

        /* 聊天区域 - 可滚动，填充剩余空间 */
        main {
            flex: 1 1 auto;
            overflow-y: auto;
            padding: 20px 0;
            scroll-behavior: smooth;
            width: 100%;
        }
        .chat-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 0 20px;
            display: flex;
            flex-direction: column;
            gap: 32px;
            width: 100%;
        }

        /* 消息样式 */
        .message {
            display: flex;
            gap: 16px;
            max-width: 100%;
        }
        .message.user {
            flex-direction: row-reverse;
        }
        .avatar {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            flex-shrink: 0;
            background: #e8eaed;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }
        .avatar img { width: 100%; height: 100%; object-fit: cover; }
        .content {
            max-width: calc(100% - 52px);
            font-size: 16px;
            line-height: 1.6;
            word-wrap: break-word;
            padding: 12px 16px;
            border-radius: 18px;
        }
        .ai .content {
            background: var(--ai-bubble-bg);
            color: var(--text-main);
        }
        .user .content {
            background: var(--user-bubble-bg);
            color: var(--text-main);
            border-bottom-right-radius: 4px;
        }

        /* 错误信息 */
        .error-msg {
            text-align: center;
            color: #d93025;
            font-size: 14px;
            margin-top: 10px;
        }

        /* 欢迎界面 */
        .welcome {
            text-align: left;
            margin-top: 10vh;
        }
        .welcome h1 {
            font-size: 44px;
            background: linear-gradient(74deg, #4285f4 0, #9b72cb 9%, #d96570 20%, #d96570 24%, #9b72cb 35%, #4285f4 44%, #9b72cb 50%, #d96570 56%, #131314 75%, #131314 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 8px;
            font-weight: 500;
        }
        .welcome p {
            font-size: 44px;
            color: #c4c7c5;
            font-weight: 500;
        }

        /* ===== 输入区域 - 固定在底部 ===== */
        footer {
            padding: 12px 20px;            /* 稍微减少内边距 */
            background: var(--bg-color);
            flex-shrink: 0;                /* 不收缩 */
            position: sticky;              /* 关键：粘性定位 */
            bottom: 0;                     /* 贴底 */
            z-index: 10;
            border-top: 1px solid #f0f0f0; /* 视觉分隔 */
            width: 100%;
            /* 增加一点安全距离，避免被系统手势遮挡（可选） */
            padding-bottom: env(safe-area-inset-bottom, 12px);
        }
        .input-wrapper {
            max-width: 800px;
            margin: 0 auto;
            position: relative;
            width: 100%;
        }
        .model-selector {
            margin-bottom: 8px;             /* 缩小间距 */
            display: flex;
            justify-content: center;
        }
        select {
            background: var(--input-bg);
            border: none;
            padding: 6px 12px;
            border-radius: 12px;
            font-size: 13px;
            color: var(--text-sub);
            outline: none;
            cursor: pointer;
            max-width: 100%;
        }
        .input-box {
            background: var(--input-bg);
            border-radius: 28px;
            padding: 6px 16px;             /* 减少内边距 */
            display: flex;
            align-items: center;
            gap: 8px;                      /* 减小间距 */
            transition: box-shadow 0.2s;
            overflow: hidden;
            width: 100%;
        }
        .input-box:focus-within {
            box-shadow: 0 1px 6px rgba(32,33,36,.28);
            background: #fff;
            border: 1px solid transparent;
        }
        textarea {
            flex: 1 1 0;                   /* 允许收缩到最小 */
            border: none;
            background: transparent;
            resize: none;
            padding: 10px 0;               /* 调整内边距 */
            font-size: 16px;
            line-height: 1.5;
            outline: none;
            max-height: 150px;             /* 限制最大高度 */
            font-family: inherit;
            min-width: 0;
            width: 100%;
            max-width: 100%;
        }
        .send-btn {
            background: transparent;
            border: none;
            color: var(--accent-blue);
            cursor: pointer;
            padding: 6px;                  /* 缩小按钮内边距 */
            display: flex;
            align-items: center;
            justify-content: center;
            transition: transform 0.1s;
            flex-shrink: 0;
        }
        .send-btn:active { transform: scale(0.9); }
        .send-btn svg {
            width: 24px;
            height: 24px;
        }

        @media (max-width: 600px) {
            .welcome h1, .welcome p { font-size: 32px; }
            .chat-container { padding: 0 15px; }
            footer { padding: 10px 15px; }
            .input-box { padding: 4px 12px; gap: 6px; }
            .send-btn svg { width: 22px; height: 22px; }
            select { font-size: 12px; padding: 4px 10px; }
        }
        @media (max-width: 400px) {
            .input-box { padding: 4px 8px; gap: 4px; }
            textarea { font-size: 15px; padding: 8px 0; }
            .send-btn svg { width: 20px; height: 20px; }
        }
    </style>
</head>
<body>
    <header>
        <div class="brand">
            <img src="https://mountainai.nekoweb.org/IMG_1282.jpeg" alt="Logo">
            <span>越山对话ai</span>
        </div>
        <div class="header-actions">
            <a href="?clear=1" class="btn-clear">新对话</a>
        </div>
    </header>

    <main id="chatBox">
        <div class="chat-container">
            <?php if (empty($history)): ?>
                <div class="welcome">
                    <h1>您好，</h1>
                    <p>今天我能帮您做些什么？</p>
                </div>
            <?php else: ?>
                <?php foreach ($history as $msg): ?>
                    <div class="message <?php echo $msg['role'] === 'user' ? 'user' : 'ai'; ?>">
                        <div class="avatar">
                            <?php if ($msg['role'] === 'user'): ?>
                                <img src="https://ui-avatars.com/api/?name=User&background=f0f4f9&color=1a73e8" alt="U">
                            <?php else: ?>
                                <img src="https://mountainai.nekoweb.org/IMG_1282.jpeg" alt="AI">
                            <?php endif; ?>
                        </div>
                        <div class="content"><?php echo nl2br(htmlspecialchars($msg['content'])); ?></div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
            
            <?php if ($error_msg): ?>
                <div class="error-msg"><?php echo htmlspecialchars($error_msg); ?></div>
            <?php endif; ?>
        </div>
    </main>

    <footer>
        <div class="input-wrapper">
            <form method="POST" action="index.php" id="chatForm">
                <div class="model-selector">
                    <select name="model_index">
                        <?php foreach ($models as $index => $m): ?>
                            <option value="<?php echo $index; ?>" <?php echo (isset($_POST['model_index']) && $_POST['model_index'] == $index) ? 'selected' : ''; ?>>
                                ✨ <?php echo htmlspecialchars($m['display_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="input-box">
                    <textarea name="user_input" id="userInput" placeholder="在这里输入内容..." required rows="1"></textarea>
                    <input type="hidden" name="history_json" value='<?php echo json_encode($history, JSON_HEX_APOS | JSON_HEX_QUOT); ?>'>
                    <button type="submit" class="send-btn" id="sendBtn">
                        <svg viewBox="0 0 24 24" fill="currentColor"><path d="M2.01 21L23 12 2.01 3 2 10l15 2-15 2z"></path></svg>
                    </button>
                </div>
            </form>
        </div>
    </footer>

    <script>
        const chatBox = document.getElementById("chatBox");
        const userInput = document.getElementById("userInput");
        const chatForm = document.getElementById("chatForm");

        // 页面加载后滚动到底部，确保输入框可见
        function scrollToBottom() {
            chatBox.scrollTop = chatBox.scrollHeight;
        }
        // 初始滚动
        scrollToBottom();

        // 输入框高度自适应
        userInput.addEventListener('input', function() {
            this.style.height = 'auto';
            this.style.height = (this.scrollHeight) + 'px';
            // 输入时也滚动到底部，防止输入框被遮挡
            scrollToBottom();
        });

        // 回车发送
        userInput.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                if (this.value.trim() !== '') {
                    chatForm.submit();
                }
            }
        });

        // 表单提交后（页面刷新后）仍然会触发初始滚动
        // 另外，监听窗口大小变化（如键盘弹出）重新滚动
        window.addEventListener('resize', scrollToBottom);
        // 对于移动端，在焦点进入输入框时滚动到底部
        userInput.addEventListener('focus', scrollToBottom);
    </script>
</body>
</html>