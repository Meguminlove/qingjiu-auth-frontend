<?php
// user/register.php (全新带验证码流程)
// 版权所有：小奏 (https://blog.mofuc.cn/)
require_once __DIR__ . '/../bootstrap.php';
require_once 'functions.php';
global $settings;

if (is_user_logged_in()) {
    header('Location: index.php');
    exit;
}
$page_title = '用户注册';
require_once __DIR__ .'/../header.php';
?>
<div class="bg-white rounded-lg shadow-md p-6 sm:p-8 max-w-md mx-auto">
    <div class="text-center mb-6">
        <h2 id="page-title" class="text-2xl font-bold text-gray-800">创建账户 (步骤 1/3)</h2>
        <p id="page-description" class="text-gray-600 mt-2">请输入您的邮箱以开始注册流程。</p>
    </div>

    <div id="message-container" class="mb-4"></div>

    <!-- Step 1: 输入邮箱 -->
    <form id="step1-form" class="space-y-4">
        <div>
            <label for="email" class="block text-sm font-medium text-gray-700">邮箱地址</label>
            <input type="email" name="email" id="email" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md" required>
        </div>
        <button type="submit" class="w-full flex justify-center py-2.5 px-4 text-white bg-blue-600 hover:bg-blue-700 disabled:bg-gray-400">
            <span class="btn-text">发送验证码</span>
            <i data-lucide="loader-2" class="w-5 h-5 animate-spin hidden" style="display: none;"></i>
        </button>
    </form>
    
    <!-- Step 2: 输入验证码 -->
    <form id="step2-form" class="space-y-4 hidden">
        <div>
            <label for="verification-code" class="block text-sm font-medium text-gray-700">邮箱验证码</label>
            <input type="text" name="verification_code" id="verification-code" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md" required>
        </div>
        <button type="submit" class="w-full flex justify-center py-2.5 px-4 text-white bg-blue-600 hover:bg-blue-700 disabled:bg-gray-400">
             <span class="btn-text">下一步</span>
             <i data-lucide="loader-2" class="w-5 h-5 animate-spin hidden" style="display: none;"></i>
        </button>
    </form>

    <!-- Step 3: 创建账号 -->
    <form id="step3-form" class="space-y-4 hidden">
        <div>
            <label for="nickname" class="block text-sm font-medium text-gray-700">昵称</label>
            <input type="text" name="nickname" id="nickname" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md" required>
        </div>
        <div>
            <label for="password" class="block text-sm font-medium text-gray-700">设置密码</label>
            <input type="password" name="password" id="password" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md" required>
        </div>
        <button type="submit" class="w-full flex justify-center py-2.5 px-4 text-white bg-green-600 hover:bg-green-700 disabled:bg-gray-400">
             <span class="btn-text">完成注册</span>
             <i data-lucide="loader-2" class="w-5 h-5 animate-spin hidden" style="display: none;"></i>
        </button>
    </form>

    <div class="mt-6 text-center text-sm">
        <p class="text-gray-600">
            已经有账户？
            <a href="login.php" class="font-medium text-blue-600 hover:text-blue-500">前往登录</a>
        </p>
    </div>
</div>
<?php require_once __DIR__ .'/../footer.php'; ?>
<script>
    lucide.createIcons();
    const step1Form = document.getElementById('step1-form');
    const step2Form = document.getElementById('step2-form');
    const step3Form = document.getElementById('step3-form');
    const messageContainer = document.getElementById('message-container');
    const pageTitle = document.getElementById('page-title');
    const pageDescription = document.getElementById('page-description');

    const showMessage = (text, type = 'error') => {
        const color = type === 'success' ? 'green' : 'red';
        messageContainer.innerHTML = `<div class="bg-${color}-100 border-l-4 border-${color}-500 text-${color}-700 p-4 rounded-md" role="alert"><p>${text}</p></div>`;
    };
    const clearMessage = () => { messageContainer.innerHTML = ''; };
    const toggleButtonLoading = (button, isLoading) => {
        const btnText = button.querySelector('.btn-text');
        const loader = button.querySelector('.animate-spin');
        button.disabled = isLoading;
        btnText.style.display = isLoading ? 'none' : '';
        loader.style.display = isLoading ? 'inline-block' : 'none';
    };

    step1Form.addEventListener('submit', async (e) => {
        e.preventDefault();
        const button = e.currentTarget.querySelector('button');
        clearMessage();
        toggleButtonLoading(button, true);
        const formData = new FormData(step1Form);
        formData.append('action', 'send_code');
        try {
            const response = await fetch('../api/register_api.php', { method: 'POST', body: formData });
            const data = await response.json();
            if (data.success) {
                showMessage('验证码已发送，请查收您的邮箱。', 'success');
                step1Form.style.display = 'none';
                step2Form.style.display = 'block';
                pageTitle.textContent = '创建账户 (步骤 2/3)';
                pageDescription.textContent = '请输入您收到的6位验证码。';
            } else {
                showMessage(data.message || '操作失败。');
            }
        } catch (error) {
            showMessage('请求失败，请检查网络连接。');
        } finally {
            toggleButtonLoading(button, false);
        }
    });

    step2Form.addEventListener('submit', async (e) => {
        e.preventDefault();
        const button = e.currentTarget.querySelector('button');
        clearMessage();
        toggleButtonLoading(button, true);
        const formData = new FormData(step2Form);
        formData.append('action', 'verify_code');
        try {
            const response = await fetch('../api/register_api.php', { method: 'POST', body: formData });
            const data = await response.json();
            if (data.success) {
                step2Form.style.display = 'none';
                step3Form.style.display = 'block';
                pageTitle.textContent = '创建账户 (步骤 3/3)';
                pageDescription.textContent = '请设置您的昵称和密码。';
            } else {
                showMessage(data.message || '验证码错误。');
            }
        } catch (error) {
            showMessage('请求失败，请检查网络连接。');
        } finally {
            toggleButtonLoading(button, false);
        }
    });

    step3Form.addEventListener('submit', async (e) => {
        e.preventDefault();
        const button = e.currentTarget.querySelector('button');
        clearMessage();
        toggleButtonLoading(button, true);
        const formData = new FormData(step3Form);
        formData.append('action', 'create_account');
        try {
            const response = await fetch('../api/register_api.php', { method: 'POST', body: formData });
            const data = await response.json();
            if (data.success) {
                showMessage('恭喜您，注册成功！现在您可以前往登录了。', 'success');
                step3Form.style.display = 'none';
            } else {
                showMessage(data.message || '注册失败。');
            }
        } catch (error) {
            showMessage('请求失败，请检查网络连接。');
        } finally {
            toggleButtonLoading(button, false);
        }
    });
</script>
