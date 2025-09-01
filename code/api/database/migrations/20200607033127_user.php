<?php

use think\migration\Migrator;
use think\migration\db\Column;

class User extends Migrator
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
        $table = $this->table('user', array('engine' => 'InnoDB', 'comment' => '用户'));
        $table->addColumn('title', 'string', ['limit' => 50, 'default' => '', 'comment' => '名称', 'null' => false])
            ->addColumn('appid', 'string', ['limit' => 50, 'default' => '', 'comment' => '账号', 'null' => false])
            ->addColumn('appsecret', 'string', ['limit' => 100, 'default' => '', 'comment' => '密码', 'null' => false])
            ->addColumn('is_admin', 'integer', ['limit' => \Phinx\Db\Adapter\MysqlAdapter::INT_TINY, 'default' => 0, 'comment' => '是否管理员：0-否，1-是', 'null' => false])
            ->addColumn('is_use', 'integer', ['limit' => \Phinx\Db\Adapter\MysqlAdapter::INT_TINY, 'default' => 1, 'comment' => '是否禁用：0-禁用，1-启用', 'null' => false])
            ->addColumn('creator_uid', 'integer', ['default' => 0, 'comment' => '创建人uid', 'null' => false])
            ->addColumn('cas_open_id', 'string', ['limit' => 50, 'default' => '', 'comment' => 'CAS开放id', 'null' => false])
            ->addColumn('create_time', 'integer', ['default' => 0, 'comment' => '创建时间', 'null' => false])
            ->addColumn('update_time', 'integer', ['default' => 0, 'comment' => '更新时间', 'null' => false])
            ->addIndex(['appid'], ['unique' => true])
            ->create();
    }
}
