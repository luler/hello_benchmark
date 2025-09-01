<?php

use think\migration\Migrator;
use think\migration\db\Column;

class Requests extends Migrator
{
    /**
     * Change Method.
     *
     * Write your reversible migrations using this method.
     *
     * More information on writing migrations is available here:
     * http://docs.phinx.org/en/latest/migrations.html#the-abstractmigration-class
     *
     * The following commands can be used in this method and Phinx will
     * automatically reverse them when rolling back:
     *
     *    createTable
     *    renameTable
     *    addColumn
     *    renameColumn
     *    addIndex
     *    addForeignKey
     *
     * Remember to call "create()" or "update()" and NOT "save()" when working
     * with the Table class.
     */
    public function change()
    {
        $table = $this->table('requests', array('engine' => 'InnoDB', 'comment' => '压测对象'));
        $table->addColumn('type', 'string', ['limit' => 20, 'default' => '', 'comment' => '压测类型，GET,POST,PATCH,PUT,DELETE,WEBSOCKET,', 'null' => false])
            ->addColumn('title', 'string', ['limit' => 255, 'default' => '', 'comment' => '名称', 'null' => false])
            ->addColumn('scheme', 'string', ['limit' => 50, 'default' => '', 'comment' => '请求协议', 'null' => false])
            ->addColumn('host', 'string', ['limit' => 50, 'default' => '', 'comment' => '主机ip/域名', 'null' => false])
            ->addColumn('port', 'string', ['limit' => 10, 'default' => '', 'comment' => '主机端口', 'null' => false])
            ->addColumn('path', 'string', ['limit' => 255, 'default' => '', 'comment' => '接口路径', 'null' => false])
            ->addColumn('connect_count', 'integer', ['default' => 0, 'comment' => '模拟用户数', 'null' => false])
            ->addColumn('timeout', 'integer', ['default' => 0, 'comment' => '请求超时时间，当一个请求响应时间超过该值时视为失败，立即终止，继续后续的请求', 'null' => false])
            ->addColumn('driver', 'string', ['limit' => 10, 'default' => '', 'comment' => '压测驱动，co-swoole多进程&协程，wrk-wrk多线程压测工具', 'null' => false])
            ->addColumn('duration', 'integer', ['default' => 0, 'comment' => 'wrk驱动时要指定压测持续时间，单位秒', 'null' => false])
            ->addColumn('creator_uid', 'integer', ['default' => 0, 'comment' => '创建人uid', 'null' => false])
            ->addColumn('updator_uid', 'integer', ['default' => 0, 'comment' => '更新人uid', 'null' => false])
            ->addColumn('create_time', 'integer', ['default' => 0, 'comment' => '创建时间', 'null' => false])
            ->addColumn('update_time', 'integer', ['default' => 0, 'comment' => '更新时间', 'null' => false])
            ->create();
    }
}
