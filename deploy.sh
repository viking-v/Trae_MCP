#!/bin/bash

# 梦之缘创业投资平台 - 管理员登录页面部署脚本

echo "🚀 开始部署管理员登录页面..."

# 检查参数
if [ "$1" = "test" ]; then
    echo "✅ 测试部署模式"
    echo "📁 验证文件完整性..."
    
    # 检查关键文件
    if [ -f "admin_login_production.html" ]; then
        echo "✅ 生产环境文件存在"
    else
        echo "❌ 生产环境文件不存在"
        exit 1
    fi
    
    if [ -f ".inscode" ]; then
        echo "✅ 配置文件存在"
    else
        echo "❌ 配置文件不存在"
        exit 1
    fi
    
    echo "✅ 测试部署完成"
    echo "🌐 访问地址: https://inscode.run"
    
elif [ "$1" = "production" ]; then
    echo "✅ 生产部署模式"
    echo "📝 部署步骤:"
    echo "1. 上传文件到服务器"
    echo "2. 配置Web服务器"
    echo "3. 设置SSL证书"
    echo "4. 配置安全策略"
    echo "5. 启动监控和日志"
    echo ""
    echo "📋 请参考 DEPLOYMENT_GUIDE.md 获取详细部署说明"
    
else
    echo "使用方法: ./deploy.sh [test|production]"
    echo "  test       - 测试部署"
    echo "  production - 生产部署"
fi

echo "✅ 部署脚本执行完成"