<?php
// 事件定义文件
return [
    'bind' => [],

    'listen' => [
        'AppInit' => [
            \app\listener\InitListener::class,
        ],
        'HttpRun' => [],
        'HttpEnd' => [],
        'LogLevel' => [],
        'LogWrite' => [],

//        'swoole.task' => [ //swoole异步任务监听
//            \app\listener\SwooleTask::class
//        ]
    ],

    'subscribe' => [],
];
