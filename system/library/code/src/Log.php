<?php

namespace CodeLibrary;

use ORM;

/**
 * © Copyright 2013-2021 Codemarket - Todos os direitos reservados.
 * Trait Log
 *
 * @package CodeLibrary
 */
trait Log
{
    /**
     * Log constructor.
     *
     * @param $registry
     */
    public function __construct($registry)
    {
        parent::__construct($registry);
        /*
        $this->db->query("CREATE TABLE IF NOT EXISTS `code_log` (
          `log_id` int(25) NOT NULL AUTO_INCREMENT,
          `level` int(1) NULL DEFAULT NULL,
          `name` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
          `group` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
          `file` text CHARACTER SET utf8 COLLATE utf8_general_ci NULL,
          `line` int(6) NULL DEFAULT NULL,
          `content` longtext CHARACTER SET utf8 COLLATE utf8_general_ci NULL,
          `qtd` int(11) NULL DEFAULT NULL,
          `date_added` datetime(0) NULL DEFAULT NULL,
          PRIMARY KEY (`log_id`) USING BTREE
         ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
        ");
        */
    }
}
