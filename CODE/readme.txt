程序实现微信事件推送从公网服务器推送转发到本地进行便捷开发的服务

公网部署代理端口 比如为xx80:

docker run  --rm -v I:/server_data/web_www/swoole_proxy:/data -p 9566:9566 --name prox-server zhiqiangvip/docker-wechat-proxy php /data/server.php





启动本地客户端 本地会和公网的服务建立websocket连接 等待远端的推送至本地 再由本地进行转发

docker run --rm -v D:/vboxshare/server_data/web_www/swoole_proxy:/data zhiqiangvip/docker-wechat-proxy php /data/client.php
