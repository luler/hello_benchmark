<?php
declare (strict_types=1);

namespace app\common\command;

use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\facade\Config;
use think\facade\Console;
use think\facade\Db;

class InitDb extends Command
{
    protected function configure()
    {
        // 指令配置
        $this->setName('init_db')->setDescription('初始化数据库表');
    }

    protected function execute(Input $input, Output $output)
    {
        $config = Config::get('database');
        $default = $config['default'];
        if ($default == 'mysql') {
            $temp = $config['connections'][$default];
            $database_name = $temp['database'];
            $temp['database'] = '';
            $config['connections']['temp'] = $temp;
            Config::set($config, 'database');
            Db::connect('temp')->execute('create database if not exists ' . $database_name . ' DEFAULT CHARACTER SET utf8mb4 DEFAULT COLLATE utf8mb4_general_ci');
        }
        Console::call('migrate:run');
    }
}