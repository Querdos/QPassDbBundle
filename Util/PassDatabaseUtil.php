<?php

namespace Querdos\QPassDbBundle\Util;
use Querdos\QPassDbBundle\Entity\QDatabase;
use Querdos\QPassDbBundle\Manager\QDatabaseManager;
use Querdos\QPassDbBundle\Manager\QPasswordManager;
use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Class PassDatabaseUtil
 * @package Querdos\QPassDbBundle\Util
 * @author  Hamza ESSAYEGH <hamza.essayegh@protonmail.com>
 */
class PassDatabaseUtil
{
    /**
     * @var QDatabaseManager
     */
    private $qdatabaseManager;

    /**
     * @var QPasswordManager
     */
    private $qpasswordManager;

    /**
     * @var ValidatorInterface
     */
    private $validator;

    /**
     * @var string
     */
    private $db_dir;

    /**
     * Create a new QDatabase
     *
     * @param string $dbname
     * @param string $password
     */
    public function create_database($dbname, $password)
    {
        // database name validation
        $dbname_val = $this
            ->validator
            ->validatePropertyValue(
                QDatabase::class,
                'dbname',
                $dbname
            )
        ;

        // checking error
        if (0 != count($dbname_val)) {
            throw new Exception((string) $dbname_val);
        }

        // password validation
        $pass_val = $this
            ->validator
            ->validatePropertyValue(
                QDatabase::class,
                'password',
                $password
            )
        ;

        // checking error
        if (0 != count($pass_val)) {
            throw new Exception((string) $dbname_val);
        }

        // checking if database exists
        if (file_exists("{$this->db_dir}/{$dbname}.qdb")) {
            throw new Exception("Database exists");
        }

        // opening sqlite database
        if ($db = sqlite_open("{$this->db_dir}/{$dbname}.qdb", 0666, $error)) {
            // creating main table
            sqlite_query(
                $db, 'CREATE TABLE passwords (id INT PRIMARY KEY NOT NULL, password VARCHAR(255) NOT NULL, pass_id INT NOT NULL)'
            );
        } else {
            throw new Exception($error);
        }

        // encrypting the newly created database
        exec("gpg -c -o {$this->db_dir}/{$dbname}.qdb.enc --cipher-algo AES256 --passphrase {$password} {$this->db_dir}/{$dbname}.qdb");
        unlink("{$this->db_dir}/{$dbname}.qdb");

        // persisting a new object
        $this->qdatabaseManager->create(new QDatabase($dbname, $password));
    }

    /**
     * @param ValidatorInterface $validator
     */
    public function setValidator(ValidatorInterface $validator)
    {
        $this->validator = $validator;
    }

    /**
     * @param QDatabaseManager $databaseManager
     */
    public function setQDatabaseManager(QDatabaseManager $databaseManager)
    {
        $this->qdatabaseManager = $databaseManager;
    }

    /**
     * @param QPasswordManager $passwordManager
     */
    public function setQPasswordManager(QPasswordManager $passwordManager)
    {
        $this->qpasswordManager = $passwordManager;
    }

    /**
     * @param string $db_dir
     */
    public function setDbDir($db_dir)
    {
        $this->db_dir = $db_dir;
    }
}