#!/bin/bash

# Docker Storage Link 修復腳本
# 用於修復 Docker 環境下的 storage 連結問題

echo "正在修復 Docker Storage 連結..."

# 移除現有的連結
rm -rf public/storage

# 重新建立正確的連結
ln -sf ../storage/app/public public/storage

# 設定正確的權限
chmod -R 755 storage/app/public
chown -R www-data:www-data storage/app/public

echo "Storage 連結修復完成！"