小奏授权查询前端 - 专为晴玖授权站打造
这是一个轻量级、美观且功能强大的PHP前端查询系统，专为 晴玖授权系统 设计。它旨在提供一个可独立部署的用户前端，通过API与您的晴玖主授权站无缝对接，为您的客户提供授权查询、域名更换、程序下载等一系列便捷的自助服务。

演示站点 [https://auth.mofuc.cn/](https://auth.mofuc.cn/)

✨ 主要功能
本程序旨在简化授权管理的流程，为终端用户提供最佳体验。

四大核心用户功能:

授权查询: 用户可通过授权域名或密钥，快速查询授权状态。

更换授权: 用户自助验证并更换其授权域名，无需人工干预。

联系授权: 清晰展示您设置的联系方式，引导新用户购买授权。

程序下载: 验证卡密后，用户可直接下载最新程序并查看更新日志。

强大的后台管理:

独立的后台面板: (/admin)，提供全面的配置选项。

API设置: 轻松配置您的晴玖主站API地址和密钥。

网站设置: 自定义网站名称、公告、查询产品ID以及页脚信息。

版本管理: 随时更新前端显示的程序版本号、下载链接和更新日志。

现代化设计:

采用 Tailwind CSS 构建，界面简洁美观，响应式设计适配电脑和手机。

使用 Lucide Icons，图标清晰优雅。

稳定可靠:

所有核心逻辑均由 后端PHP处理，避免了前端API暴露和不稳定的问题。

一键安装:

提供图形化安装向导，只需填写数据库和管理员信息即可完成部署。

🚀 安装与使用
仅需简单几步，即可拥有属于您自己的授权查询站点。

环境要求:

PHP >= 7.4

MySQL 数据库

Nginx / Apache

下载源码: 从本项目 GitHub Releases 页面下载最新版本的源码。

上传并配置:

将源码上传至服务器并创建站点。

在宝塔面板等工具中，将网站的 默认文档 设置为 query.php。

运行安装向导:

直接访问您的域名，程序将自动引导您进入安装界面。

根据提示填写数据库信息和管理员账号，即可完成安装。

详细的图文教程请参考： **[使用教程](https://github.com/Meguminlove/qingjiu-auth-frontend/blob/main/%E5%AE%89%E8%A3%85%E6%95%99%E7%A8%8B.md)**

📜 更新日志
我们致力于不断优化和完善程序。

v1.0.1 (当前版本) - 稳定修复与体验优化版

核心修复: 彻底解决了API调用失败、后台设置无法同步到前端、安装流程卡顿等核心问题。

功能优化: 后台新增“查询产品ID”和“网站公告”功能，并与前台完美对接。

代码重构: 将所有页面重构为后端一体化处理，提升了系统的稳定性和安全性。

详细的更新历史请参考： **[更新日志](https://github.com/Meguminlove/qingjiu-auth-frontend/blob/main/%E6%9B%B4%E6%96%B0%E6%97%A5%E5%BF%97.md)**

🤝 贡献与致谢
欢迎所有开发者为此项目贡献代码。您可以 Fork 本仓库，创建您的功能分支，然后提交 Pull Request。

核心后端: 晴玖授权系统

前端样式: Tailwind CSS

图标库: Lucide Icons

📄 开源许可

本程序采用 [MIT License](https://github.com/Meguminlove/qingjiu-auth-frontend/blob/main/MIT%20License.md) 开源。

<img width="1920" height="1040" alt="授权查询" src="https://github.com/user-attachments/assets/58924367-73bc-4975-92ac-2c0c91ef9848" />
<img width="1920" height="1040" alt="更换授权" src="https://github.com/user-attachments/assets/f301a453-6c1e-412b-859e-be0fbdc78556" />
<img width="1920" height="1040" alt="程序下载" src="https://github.com/user-attachments/assets/167b7650-af9c-4237-8efb-11eccdf88384" />
<img width="1920" height="1040" alt="后台主页" src="https://github.com/user-attachments/assets/31081d0c-2c67-492e-8c8e-4152bd8f5ef6" />
<img width="1920" height="1040" alt="网站设置" src="https://github.com/user-attachments/assets/68dd84d9-4404-48f5-ac62-278fb4b3c5ba" />
<img width="1920" height="1040" alt="API设置" src="https://github.com/user-attachments/assets/190fda5f-0119-408e-97e2-12fb148a8fe8" />
<img width="1920" height="1040" alt="版本设置" src="https://github.com/user-attachments/assets/c86a4d69-9e99-4c0b-98e4-5ec69754f270" />



