<?php

use think\migration\Migrator;
use think\migration\db\Column;

class Params extends Migrator
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
        $table = $this->table('params', array('engine' => 'InnoDB', 'comment' => '请求参数'));
        $table->addColumn('request_id', 'integer', ['default' => 0, 'comment' => '压测对象id', 'null' => false])
            ->addColumn('type', 'integer', ['limit' => \Phinx\Db\Adapter\MysqlAdapter::INT_TINY, 'default' => 0, 'comment' => '类型，1-普通请求参数，2-raw参数,3-请求头参数', 'null' => false])
            ->addColumn('name', 'string', ['limit' => 255, 'default' => '', 'comment' => '参数名', 'null' => false])
            ->addColumn('value', 'string', ['limit' => 2048, 'default' => '', 'comment' => '参数名', 'null' => false])
            ->addColumn('creator_uid', 'integer', ['default' => 0, 'comment' => '创建人uid', 'null' => false])
            ->addColumn('updator_uid', 'integer', ['default' => 0, 'comment' => '更新人uid', 'null' => false])
            ->addColumn('create_time', 'integer', ['default' => 0, 'comment' => '创建时间', 'null' => false])
            ->addColumn('update_time', 'integer', ['default' => 0, 'comment' => '更新时间', 'null' => false])
            ->create();
    }
}
