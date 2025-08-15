# XMRig Windows PowerShell 编译脚本

Write-Host "========================================" -ForegroundColor Green
Write-Host "XMRig Windows 编译脚本 (PowerShell)" -ForegroundColor Green
Write-Host "========================================" -ForegroundColor Green
Write-Host ""

# 检查是否以管理员身份运行
if (-NOT ([Security.Principal.WindowsPrincipal] [Security.Principal.WindowsIdentity]::GetCurrent()).IsInRole([Security.Principal.WindowsBuiltInRole] "Administrator")) {
    Write-Warning "建议以管理员身份运行此脚本以获得最佳性能"
    Write-Host ""
}

# 检查必要的工具
Write-Host "检查编译环境..." -ForegroundColor Yellow

# 检查Visual Studio
try {
    $clPath = Get-Command cl -ErrorAction Stop
    Write-Host "✓ 找到Visual Studio编译器: $($clPath.Source)" -ForegroundColor Green
} catch {
    Write-Error "✗ 未找到Visual Studio编译器 (cl.exe)"
    Write-Host "请安装Visual Studio 2019或更新版本" -ForegroundColor Red
    Write-Host "或者运行 'Developer Command Prompt for VS'" -ForegroundColor Red
    Read-Host "按任意键退出"
    exit 1
}

# 检查CMake
try {
    $cmakePath = Get-Command cmake -ErrorAction Stop
    Write-Host "✓ 找到CMake: $($cmakePath.Source)" -ForegroundColor Green
} catch {
    Write-Error "✗ 未找到CMake"
    Write-Host "请安装CMake 3.10或更新版本" -ForegroundColor Red
    Read-Host "按任意键退出"
    exit 1
}

# 检查Git
try {
    $gitPath = Get-Command git -ErrorAction Stop
    Write-Host "✓ 找到Git: $($gitPath.Source)" -ForegroundColor Green
} catch {
    Write-Error "✗ 未找到Git"
    Write-Host "请安装Git" -ForegroundColor Red
    Read-Host "按任意键退出"
    exit 1
}

Write-Host "编译环境检查完成" -ForegroundColor Green
Write-Host ""

# 创建构建目录
if (-not (Test-Path "build")) {
    New-Item -ItemType Directory -Name "build" | Out-Null
    Write-Host "创建构建目录: build" -ForegroundColor Yellow
}

Set-Location "build"

# 配置CMake
Write-Host "配置CMake..." -ForegroundColor Yellow

$cmakeArgs = @(
    "..",
    "-G", "Visual Studio 16 2019",
    "-A", "x64",
    "-DWITH_OPENCL=OFF",
    "-DWITH_CUDA=OFF",
    "-DWITH_NVML=OFF",
    "-DWITH_ADL=OFF",
    "-DWITH_DMI=OFF",
    "-DWITH_EMBEDDED_CONFIG=OFF",
    "-DWITH_DEBUG_LOG=OFF",
    "-DWITH_PROFILING=OFF",
    "-DWITH_BENCHMARK=OFF",
    "-DWITH_SECURE_JIT=OFF",
    "-DWITH_STRICT_CACHE=OFF",
    "-DWITH_INTERLEAVE_DEBUG_LOG=OFF",
    "-DWITH_CN_LITE=ON",
    "-DWITH_CN_HEAVY=ON",
    "-DWITH_CN_PICO=ON",
    "-DWITH_CN_FEMTO=ON",
    "-DWITH_RANDOMX=ON",
    "-DWITH_ARGON2=ON",
    "-DWITH_KAWPOW=ON",
    "-DWITH_GHOSTRIDER=ON",
    "-DWITH_HTTP=ON",
    "-DWITH_TLS=ON",
    "-DWITH_ASM=ON",
    "-DWITH_MSR=ON",
    "-DWITH_ENV_VARS=ON",
    "-DWITH_SSE4_1=ON",
    "-DWITH_AVX2=ON",
    "-DWITH_VAES=ON",
    "-DWITH_HWLOC=ON"
)

$cmakeResult = & cmake $cmakeArgs

if ($LASTEXITCODE -ne 0) {
    Write-Error "CMake配置失败"
    Read-Host "按任意键退出"
    exit 1
}

Write-Host "CMake配置成功" -ForegroundColor Green
Write-Host ""

# 编译项目
Write-Host "开始编译..." -ForegroundColor Yellow
Write-Host "这可能需要10-30分钟，请耐心等待..." -ForegroundColor Cyan

$buildResult = & cmake --build . --config Release --parallel

if ($LASTEXITCODE -ne 0) {
    Write-Error "编译失败"
    Read-Host "按任意键退出"
    exit 1
}

Write-Host ""
Write-Host "========================================" -ForegroundColor Green
Write-Host "编译成功！" -ForegroundColor Green
Write-Host "========================================" -ForegroundColor Green
Write-Host ""

# 检查可执行文件
$exePath = "Release\xmrig.exe"
if (Test-Path $exePath) {
    Write-Host "可执行文件位置: build\$exePath" -ForegroundColor Green
    
    # 复制到根目录
    Copy-Item $exePath "..\xmrig.exe" -Force
    Write-Host "已将xmrig.exe复制到项目根目录" -ForegroundColor Green
    Write-Host ""
    
    # 显示文件信息
    $fileInfo = Get-Item $exePath
    Write-Host "文件大小: $([math]::Round($fileInfo.Length / 1MB, 2)) MB" -ForegroundColor Cyan
    Write-Host "创建时间: $($fileInfo.CreationTime)" -ForegroundColor Cyan
    Write-Host ""
} else {
    Write-Warning "未找到编译后的可执行文件"
}

# 返回根目录
Set-Location ".."

Write-Host "使用方法:" -ForegroundColor Yellow
Write-Host "xmrig.exe --remote-config=https://your-server.com/config.json" -ForegroundColor White
Write-Host ""
Write-Host "测试命令:" -ForegroundColor Yellow
Write-Host "xmrig.exe --remote-config=https://your-server.com/config.json --dry-run" -ForegroundColor White
Write-Host ""

# 检查是否有配置文件
if (Test-Path "windows_config.json") {
    Write-Host "发现配置文件: windows_config.json" -ForegroundColor Green
    Write-Host "您可以参考此文件创建远程配置文件" -ForegroundColor Cyan
    Write-Host ""
}

Write-Host "编译完成！" -ForegroundColor Green
Read-Host "按任意键退出"