<?php

namespace Sura\Database;

abstract class Migration
{
    protected QueryBuilder $db;

    public function __construct()
    {
        $this->db = \Sura\Container::getInstance()->get('db.query');
    }

    abstract public function up();
    abstract public function down();
}