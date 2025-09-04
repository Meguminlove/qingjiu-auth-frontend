<?php
// footer.php
// 版权所有：小奏 (https://blog.mofuc.cn/)
// 本软件是小奏独立开发的开源项目，二次开发请务必保留原作者的版权信息。
// 博客: https://blog.mofuc.cn/
// B站: https://space.bilibili.com/63216596
// GitHub: https://github.com/Meguminlove/qingjiu-auth-frontend

$site_footer = !empty($settings['site_footer']) ? htmlspecialchars($settings['site_footer']) : '© ' . date('Y') . ' All Rights Reserved.';
$site_icp = !empty($settings['site_icp']) ? htmlspecialchars($settings['site_icp']) : '';
?>
        </main> <!-- Closes the main tag from header.php -->

        <!-- 移动端底部导航栏 -->
        <nav class="md:hidden fixed bottom-0 left-0 right-0 bg-white border-t border-gray-200 shadow-[0_-2px_5px_rgba(0,0,0,0.05)] flex justify-around">
            <?php
                $current_page_for_footer = basename($_SERVER['PHP_SELF']);
                // [新增] 首页选项
                render_bottom_nav_link('index.php', 'home', '首页', $current_page_for_footer);
                render_bottom_nav_link('activate.php', 'user-check', '自助授权', $current_page_for_footer);
                render_bottom_nav_link('key_query.php', 'key-round', '密钥查询', $current_page_for_footer);
                render_bottom_nav_link('download.php', 'download', '下载程序', $current_page_for_footer);
                render_bottom_nav_link('auth.php', 'message-circle', '联系客服', $current_page_for_footer);
            ?>
        </nav>

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

    </div> <!-- Closes the container div from header.php -->
</body>
</html>

