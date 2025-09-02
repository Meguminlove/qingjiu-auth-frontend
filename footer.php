<?php
// footer.php

// 从主页面加载的 $settings 变量中获取数据，并提供默认值
$site_footer = !empty($settings['site_footer']) ? htmlspecialchars($settings['site_footer']) : '© ' . date('Y') . ' All Rights Reserved.';
$site_icp = !empty($settings['site_icp']) ? htmlspecialchars($settings['site_icp']) : '';
?>
<footer class="text-center text-sm text-gray-500 py-8 border-t border-gray-200 mt-8">
    <p><?php echo $site_footer; ?></p>
    <?php if ($site_icp): ?>
        <p class="mt-1">
            <a href="https://beian.miit.gov.cn/" target="_blank" rel="noopener noreferrer" class="hover:text-blue-600 transition-colors">
                <?php echo $site_icp; ?>
            </a>
        </p>
    <?php endif; ?>
</footer>
