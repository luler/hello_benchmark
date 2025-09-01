<?php

use think\migration\Migrator;

class RequestsRecord extends Migrator
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
        $table = $this->table('requests_record', array('engine' => 'InnoDB', 'comment' => '压测对象'));
        $table->addColumn('request_id', 'integer', ['default' => 0, 'comment' => '压测对象id', 'null' => false])
            ->addColumn('content', 'text', ['comment' => '压测日志内容', 'null' => true])
            ->addColumn('request_count', 'integer', ['default' => 0, 'comment' => '请求总数', 'null' => false])
            ->addColumn('fail_count', 'integer', ['default' => 0, 'comment' => '错误请求总数', 'null' => false])
            ->addColumn('request_rate', 'float', ['default' => 0, 'comment' => '吞吐率，N请求/每秒', 'null' => false])
            ->addColumn('creator_uid', 'integer', ['default' => 0, 'comment' => '创建人uid', 'null' => false])
            ->addColumn('status', 'integer', ['limit' => \Phinx\Db\Adapter\MysqlAdapter::INT_TINY, 'default' => 0, 'comment' => '状态，0-等待执行，1-正在执行中，2-执行完成，3-已取消', 'null' => false])
            ->addColumn('create_time', 'integer', ['default' => 0, 'comment' => '创建时间', 'null' => false])
            ->addColumn('update_time', 'integer', ['default' => 0, 'comment' => '更新时间', 'null' => false])
            ->create();
    }
}
