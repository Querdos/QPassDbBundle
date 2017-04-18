<?php

namespace Querdos\QPassDbBundle\Util;


/**
 * Class RequestUtil
 * @package Querdos\QPassDbBundle\Util
 * @author  Hamza ESSAYEGH <hamza.essayegh@protonmail.com>
 */
class SqlQueryUtil
{
    /**
     * Build the table creation sql request
     *
     * @return string
     */
    public static function create_table()
    {
        return <<<EOT
CREATE TABLE passwords (
    id INTEGER AUTOINCREMENT PRIMARY KEY NOT NULL,
    password VARCHAR(255) NOT NULL,
    pass_id VARCHAR(100) NOT NULL
)
EOT;
    }

    /**
     * Build the insert password sql request
     *
     * @param string $password
     * @param string $pass_id
     *
     * @return string
     */
    public static function insert_password($password, $pass_id)
    {
        return <<<EOT
INSERT INTO passwords 
      (password, pass_id) VALUES
      ('{$password}', '{$pass_id}')
EOT;
    }

    /**
     * Build the select all password sql request
     *
     * @return string
     */
    public static function select_all_password()
    {
        return <<<EOT
SELECT passwords.password as password, passwords.pass_id as pass_id 
FROM passwords
EOT;
    }

    public static function select_password($pass_id)
    {
        return <<<EOT
SELECT passwords.password as password, passwords.pass_id as pass_id
FROM passwords
WHERE passwords.pass_id = '{$pass_id}'
EOT;
    }
}