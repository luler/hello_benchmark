#!/bin/bash

#初始化数据库表格,创建数据库(框架问题，sqlite数据库必须执行两次)
cd /home/wwwroot/api && php think init_db
cd /home/wwwroot/api && php think init_db

#解决目录权限问题
chown -R www.www /home/wwwroot/api

#安装wrk软件环境
if [ "`command -v wrk`" = "" ]; then
    cd /home/wwwroot/ && unzip wrk.zip && mv wrk /usr/local/
    cd /usr/local/wrk && make && ln -s /usr/local/wrk/wrk /usr/local/bin/wrk
fi

#定时任务
cat >/etc/crontab <<EOF
SHELL=/bin/bash
PATH=/sbin:/bin:/usr/sbin:/usr/bin
MAILTO=root

#* * * * * root cd /home/wwwroot/api && php backup.php
EOF
