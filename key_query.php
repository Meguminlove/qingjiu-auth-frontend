<?php
// key_query.php (全新安全流程)
// 版权所有：小奏 (https://blog.mofuc.cn/)
// 本软件是小奏独立开发的开源项目，二次开发请务必保留原作者的版权信息。
// 博客: https://blog.mofuc.cn/
// B站: https://space.bilibili.com/63216596
// GitHub: https://github.com/Meguminlove/qingjiu-auth-frontend

require_once 'bootstrap.php';
global $settings;

$page_title = '授权密钥找回';
$current_page = 'key_query.php';
require_once 'header.php';
?>
<div class="bg-white rounded-lg shadow-md p-6 sm:p-8">
    <div class="text-center mb-6">
        <h2 id="page-title" class="text-2xl font-bold text-gray-800">密钥找回 (步骤 1/3)</h2>
        <p id="page-description" class="text-gray-600 mt-2">通过您授权时使用的域名和邮箱，找回您的授权密钥。</p>
    </div>

    <!-- 统一的消息提示区域 -->
    <div id="message-container" class="max-w-md mx-auto mb-4"></div>

    <!-- 步骤 1: 输入域名和邮箱 -->
    <form id="step1-form" class="space-y-4 max-w-md mx-auto">
        <div>
            <label for="auth-domain" class="block text-sm font-medium text-gray-700">授权域名</label>
            <input type="text" name="auth_domain" id="auth-domain" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500" placeholder="例如：example.com" required>
        </div>
        <div>
            <label for="auth-email" class="block text-sm font-medium text-gray-700">授权邮箱</label>
            <input type="email" name="auth_email" id="auth-email" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500" placeholder="请输入您的授权邮箱" required>
        </div>
        <button type="submit" class="w-full flex justify-center py-2.5 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 disabled:bg-gray-400">
            <span class="btn-text">发送验证码</span>
            <i data-lucide="loader-2" class="w-5 h-5 animate-spin hidden"></i>
        </button>
    </form>
    
    <!-- 步骤 2: 输入验证码 -->
    <form id="step2-form" class="space-y-4 max-w-md mx-auto hidden">
        <div>
            <label for="verification-code" class="block text-sm font-medium text-gray-700">邮箱验证码</label>
            <input type="text" name="verification_code" id="verification-code" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500" placeholder="请输入6位数字验证码" required>
            <p id="countdown-timer" class="mt-2 text-xs text-gray-500"></p>
        </div>
        <button type="submit" class="w-full flex justify-center py-2.5 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 disabled:bg-gray-400">
             <span class="btn-text">验证并找回密钥</span>
             <i data-lucide="loader-2" class="w-5 h-5 animate-spin hidden"></i>
        </button>
    </form>

    <!-- 步骤 3: 显示结果 -->
    <div id="step3-result" class="max-w-md mx-auto hidden">
         <div class="space-y-3 text-sm border p-4 rounded-md bg-gray-50">
            <p><strong>授权域名:</strong> <span id="result-domain"></span></p>
            <p><strong>授权邮箱:</strong> <span id="result-email"></span></p>
            <p class="font-mono break-all"><strong>授权密钥:</strong> <span id="result-key"></span></p>
        </div>
        <button id="send-copy-btn" class="mt-4 w-full flex justify-center py-2.5 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-green-600 hover:bg-green-700 disabled:bg-gray-400">
            <span class="btn-text">发送一份副本到我的邮箱</span>
            <i data-lucide="loader-2" class="w-5 h-5 animate-spin hidden"></i>
        </button>
    </div>
</div>

<?php require_once 'footer.php'; ?>
</main>
</div>
<script>
    lucide.createIcons();

    const step1Form = document.getElementById('step1-form');
    const step2Form = document.getElementById('step2-form');
    const step3Result = document.getElementById('step3-result');
    const messageContainer = document.getElementById('message-container');
    const pageTitle = document.getElementById('page-title');
    const pageDescription = document.getElementById('page-description');
    const countdownTimerEl = document.getElementById('countdown-timer');

    let countdownInterval;

    const showMessage = (text, type = 'error') => {
        const color = type === 'success' ? 'green' : 'red';
        messageContainer.innerHTML = `<div class="bg-${color}-100 border-l-4 border-${color}-500 text-${color}-700 p-4 rounded-md" role="alert"><p>${text}</p></div>`;
    };

    const clearMessage = () => {
        messageContainer.innerHTML = '';
    };

    const toggleButtonLoading = (button, isLoading) => {
        const btnText = button.querySelector('.btn-text');
        const loader = button.querySelector('.animate-spin');
        
        if (isLoading) {
            button.disabled = true;
            btnText.classList.add('hidden');
            loader.classList.remove('hidden');
        } else {
            button.disabled = false;
            btnText.classList.remove('hidden');
            loader.classList.add('hidden');
        }
    };

    const startCountdown = (duration) => {
        let timer = duration;
        countdownTimerEl.textContent = `验证码已发送，请在 ${Math.floor(timer / 60)}:${(timer % 60).toString().padStart(2, '0')} 内输入。`;
        
        clearInterval(countdownInterval);
        countdownInterval = setInterval(() => {
            timer--;
            if (timer >= 0) {
                countdownTimerEl.textContent = `验证码已发送，请在 ${Math.floor(timer / 60)}:${(timer % 60).toString().padStart(2, '0')} 内输入。`;
            } else {
                clearInterval(countdownInterval);
                countdownTimerEl.textContent = '验证码已过期，请刷新页面重试。';
            }
        }, 1000);
    };

    // Step 1: Send verification code
    step1Form.addEventListener('submit', async (e) => {
        e.preventDefault();
        const button = e.currentTarget.querySelector('button[type="submit"]');
        clearMessage();
        toggleButtonLoading(button, true);

        const formData = new FormData(step1Form);
        formData.append('action', 'send_code');

        try {
            const response = await fetch('api/key_recovery_api.php', { method: 'POST', body: formData });
            const data = await response.json();

            if (data.success) {
                showMessage('验证码已成功发送至您的邮箱，请注意查收。', 'success');
                step1Form.classList.add('hidden');
                step2Form.classList.remove('hidden');
                pageTitle.textContent = '密钥找回 (步骤 2/3)';
                pageDescription.textContent = '请输入您邮箱中收到的6位数字验证码。';
                startCountdown(300); // 5 minutes
            } else {
                showMessage(data.message || '操作失败，请重试。');
            }
        } catch (error) {
            showMessage('请求失败，请检查网络连接或服务器日志。');
        } finally {
            toggleButtonLoading(button, false);
        }
    });

    // Step 2: Verify code
    step2Form.addEventListener('submit', async (e) => {
        e.preventDefault();
        const button = e.currentTarget.querySelector('button[type="submit"]');
        clearMessage();
        toggleButtonLoading(button, true);

        const formData = new FormData(step2Form);
        formData.append('action', 'verify_code');

        try {
            const response = await fetch('api/key_recovery_api.php', { method: 'POST', body: formData });
            const data = await response.json();

            if (data.success) {
                clearInterval(countdownInterval);
                showMessage('验证成功！您的授权信息如下。', 'success');
                step2Form.classList.add('hidden');
                step3Result.classList.remove('hidden');
                pageTitle.textContent = '密钥找回 (步骤 3/3)';
                pageDescription.textContent = '请妥善保管您的授权密钥，建议发送一份副本到邮箱。';
                
                document.getElementById('result-domain').textContent = data.data.auth_domain;
                document.getElementById('result-email').textContent = data.data.auth_email;
                document.getElementById('result-key').textContent = data.data.license_key;
            } else {
                showMessage(data.message || '验证码错误或已过期。');
            }
        } catch (error) {
            showMessage('请求失败，请检查网络连接或服务器日志。');
        } finally {
            toggleButtonLoading(button, false);
        }
    });

    // Step 3: Send copy to email
    document.getElementById('send-copy-btn').addEventListener('click', async (e) => {
        const button = e.currentTarget;
        clearMessage();
        toggleButtonLoading(button, true);

        const formData = new FormData();
        formData.append('action', 'send_copy');

        try {
            const response = await fetch('api/key_recovery_api.php', { method: 'POST', body: formData });
            const data = await response.json();

            if (data.success) {
                showMessage('邮件副本已成功发送！', 'success');
            } else {
                showMessage(data.message || '邮件发送失败，请稍后重试。');
            }
        } catch (error) {
            showMessage('请求失败，请检查网络连接或服务器日志。');
        } finally {
            toggleButtonLoading(button, false);
        }
    });
</script>
</body>
</html>

