FROM registry.cn-hangzhou.aliyuncs.com/zhiqiangvip/docker-php-7.0.30-fpm
MAINTAINER qiang <zhiqiangvip999@gmail.com>

#禁用xdebug
RUN rm /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini

#代码
ADD CODE/ /data

EXPOSE 9566

WORKDIR /data

CMD ["php server.php"]
