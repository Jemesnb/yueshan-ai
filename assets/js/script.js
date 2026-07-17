document.addEventListener('DOMContentLoaded', function() {
    const chatBox = document.getElementById("chatBox");
    const userInput = document.getElementById("userInput");
    const chatForm = document.getElementById("chatForm");

    // 页面加载后滚动到底部
    function scrollToBottom() {
        chatBox.scrollTop = chatBox.scrollHeight;
    }
    
    scrollToBottom();

    // 输入框高度自适应
    if (userInput) {
        userInput.addEventListener('input', function() {
            this.style.height = 'auto';
            this.style.height = (this.scrollHeight) + 'px';
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
    }
});
