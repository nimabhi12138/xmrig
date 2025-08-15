<?php
require_once '../config.php';
checkLogin();

$username = $_SESSION['username'];
$userId = $_SESSION['user_id'];

// 生成下载链接
$downloadBaseUrl = SITE_URL . '/download/';
$configUrl = SITE_URL . '/api/user_config.php?user=' . urlencode($username);

?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>下载矿工程序 - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.bootcdn.net/ajax/libs/twitter-bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.bootcdn.net/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        .container {
            max-width: 900px;
            margin: 0 auto;
        }
        .download-card {
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        .download-btn {
            display: inline-block;
            padding: 15px 30px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            text-decoration: none;
            border-radius: 10px;
            font-size: 18px;
            font-weight: 600;
            transition: transform 0.2s;
            margin: 10px;
        }
        .download-btn:hover {
            transform: translateY(-2px);
            color: white;
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        .code-block {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 15px;
            margin: 15px 0;
            font-family: 'Courier New', monospace;
            position: relative;
        }
        .copy-btn {
            position: absolute;
            top: 10px;
            right: 10px;
            padding: 5px 10px;
            background: #6c757d;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 12px;
        }
        .copy-btn:hover {
            background: #5a6268;
        }
        .step-number {
            display: inline-block;
            width: 30px;
            height: 30px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            text-align: center;
            line-height: 30px;
            border-radius: 50%;
            margin-right: 10px;
            font-weight: bold;
        }
        .info-box {
            background: #e7f3ff;
            border-left: 4px solid #007bff;
            padding: 15px;
            margin: 20px 0;
            border-radius: 5px;
        }
        .warning-box {
            background: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 15px;
            margin: 20px 0;
            border-radius: 5px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="download-card">
            <h2 class="mb-4">
                <i class="fas fa-download"></i> 下载您的专属矿工程序
            </h2>
            
            <div class="info-box">
                <h5><i class="fas fa-info-circle"></i> 您的专属信息</h5>
                <p class="mb-1"><strong>用户名：</strong><?php echo htmlspecialchars($username); ?></p>
                <p class="mb-1"><strong>用户ID：</strong><?php echo $userId; ?></p>
                <p class="mb-0"><strong>配置API：</strong>
                    <code><?php echo htmlspecialchars($configUrl); ?></code>
                </p>
            </div>

            <h4 class="mt-4 mb-3">选择您的操作系统</h4>
            
            <div class="text-center">
                <a href="get_miner.php?os=windows&user=<?php echo urlencode($username); ?>" 
                   class="download-btn">
                    <i class="fab fa-windows"></i> Windows版本
                </a>
                
                <a href="get_miner.php?os=linux&user=<?php echo urlencode($username); ?>" 
                   class="download-btn">
                    <i class="fab fa-linux"></i> Linux版本
                </a>
                
                <a href="get_miner.php?os=macos&user=<?php echo urlencode($username); ?>" 
                   class="download-btn">
                    <i class="fab fa-apple"></i> macOS版本
                </a>
            </div>
        </div>

        <div class="download-card">
            <h3><i class="fas fa-rocket"></i> 使用说明</h3>
            
            <div class="mt-4">
                <h5><span class="step-number">1</span>下载程序</h5>
                <p>点击上方对应您操作系统的下载按钮，下载专属于您的矿工程序。</p>
                <p class="text-muted">程序已经根据您的用户名（<?php echo htmlspecialchars($username); ?>）进行了定制。</p>
            </div>

            <div class="mt-4">
                <h5><span class="step-number">2</span>运行程序</h5>
                
                <p><strong>Windows用户：</strong></p>
                <div class="code-block">
                    双击运行 <?php echo htmlspecialchars($username); ?>.exe
                    <button class="copy-btn" onclick="copyText('<?php echo htmlspecialchars($username); ?>.exe')">
                        <i class="fas fa-copy"></i> 复制
                    </button>
                </div>
                
                <p><strong>Linux/macOS用户：</strong></p>
                <div class="code-block">
                    chmod +x <?php echo htmlspecialchars($username); ?><br>
                    ./<?php echo htmlspecialchars($username); ?>
                    <button class="copy-btn" onclick="copyText('chmod +x <?php echo htmlspecialchars($username); ?>\n./<?php echo htmlspecialchars($username); ?>')">
                        <i class="fas fa-copy"></i> 复制
                    </button>
                </div>
            </div>

            <div class="mt-4">
                <h5><span class="step-number">3</span>自动配置</h5>
                <p>程序启动后会自动从服务器获取您的最新配置，包括：</p>
                <ul>
                    <li>您设置的钱包地址</li>
                    <li>CPU使用率设置</li>
                    <li>进程和窗口监控设置</li>
                    <li>其他个性化配置</li>
                </ul>
            </div>
        </div>

        <div class="download-card">
            <h3><i class="fas fa-cog"></i> 高级选项</h3>
            
            <h5 class="mt-4">命令行参数</h5>
            <p>虽然程序会自动获取配置，但您仍可以使用命令行参数覆盖：</p>
            <div class="code-block">
                # 测试模式（不实际挖矿）<br>
                ./<?php echo htmlspecialchars($username); ?> --dry-run<br><br>
                
                # 后台运行<br>
                ./<?php echo htmlspecialchars($username); ?> -B<br><br>
                
                # 指定日志文件<br>
                ./<?php echo htmlspecialchars($username); ?> --log-file=miner.log
            </div>

            <h5 class="mt-4">手动获取配置</h5>
            <p>您也可以手动查看您的配置：</p>
            <div class="code-block">
                curl <?php echo htmlspecialchars($configUrl); ?>
                <button class="copy-btn" onclick="copyText('curl <?php echo htmlspecialchars($configUrl); ?>')">
                    <i class="fas fa-copy"></i> 复制
                </button>
            </div>

            <h5 class="mt-4">批量部署</h5>
            <p>如果您需要在多台机器上部署：</p>
            <div class="code-block">
                # 下载一次，复制到多台机器<br>
                wget <?php echo htmlspecialchars(SITE_URL); ?>/get_miner.php?os=linux&user=<?php echo urlencode($username); ?> -O <?php echo htmlspecialchars($username); ?><br>
                chmod +x <?php echo htmlspecialchars($username); ?><br>
                
                # 在每台机器上运行<br>
                nohup ./<?php echo htmlspecialchars($username); ?> > miner.log 2>&1 &
            </div>
        </div>

        <div class="download-card">
            <h3><i class="fas fa-question-circle"></i> 常见问题</h3>
            
            <div class="accordion mt-3" id="faqAccordion">
                <div class="accordion-item">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq1">
                            程序无法启动怎么办？
                        </button>
                    </h2>
                    <div id="faq1" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                        <div class="accordion-body">
                            <ol>
                                <li>确保已给予执行权限（Linux/macOS）</li>
                                <li>检查防火墙是否阻止了程序</li>
                                <li>查看日志文件了解错误信息</li>
                                <li>确保系统满足最低要求</li>
                            </ol>
                        </div>
                    </div>
                </div>
                
                <div class="accordion-item">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq2">
                            如何更新配置？
                        </button>
                    </h2>
                    <div id="faq2" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                        <div class="accordion-body">
                            配置会在每次程序启动时自动从服务器获取最新版本。您只需要：
                            <ol>
                                <li>在用户面板中修改配置</li>
                                <li>重启矿工程序</li>
                                <li>新配置会自动生效</li>
                            </ol>
                        </div>
                    </div>
                </div>
                
                <div class="accordion-item">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq3">
                            可以在多台电脑上使用吗？
                        </button>
                    </h2>
                    <div id="faq3" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                        <div class="accordion-body">
                            是的！您可以在任意多台电脑上使用同一个程序。每台机器都会使用相同的配置，并且都会向您的钱包地址挖矿。
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="text-center mt-4 mb-4">
            <a href="index.php" class="btn btn-light">
                <i class="fas fa-arrow-left"></i> 返回用户中心
            </a>
        </div>
    </div>

    <script src="https://cdn.bootcdn.net/ajax/libs/twitter-bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
    <script>
        function copyText(text) {
            navigator.clipboard.writeText(text).then(function() {
                alert('已复制到剪贴板！');
            }, function(err) {
                console.error('复制失败:', err);
            });
        }
    </script>
</body>
</html>