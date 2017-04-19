<?php

namespace Querdos\QPassDbBundle\Util;
use Querdos\QPassDbBundle\Entity\QDatabase;
use Querdos\QPassDbBundle\Entity\QPassword;
use Querdos\QPassDbBundle\Manager\QDatabaseManager;
use Querdos\QPassDbBundle\Manager\QPasswordManager;
use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\ProcessBuilder;
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

        // checking if the database_dir exists and creating it if necessary
        if (!is_dir($this->db_dir)) {
            mkdir($this->db_dir, 0777, true);
        }

        // checking if database exists already or not
        if ($this->qdatabaseManager->readByDatabaseName($dbname) !== null) {
            throw new Exception("Database exists");
        }

        // checking if the file doesn't exists
        if (file_exists($this->db_dir . "/{$dbname}.qdb.enc")) {
            unlink($this->db_dir . "/{$dbname}.qdb.enc"); // if present in dir but not in database <=> problem
        }

        // opening sqlite database
        if ($db = sqlite_open("{$this->db_dir}/{$dbname}.qdb", 0666, $error)) {
            // creating main table
            sqlite_query(
                $db,
                SqlQueryUtil::create_table()
            );
        } else {
            // opening failed, raising exception
            throw new Exception($error);
        }

        // encrypting the newly created database
        $this->lock_database("{$this->db_dir}/{$dbname}.qdb", $dbname, $password);

        // persisting a new object
        $this->qdatabaseManager->create(new QDatabase($dbname, $password));
    }

    /**
     * Add a password entry to the local database
     *
     * @param QDatabase $database
     * @param string    $password
     * @param string    $pass_to_add
     * @param string    $label
     */
    public function add_password(QDatabase $database, $password, $pass_to_add, $label)
    {
        // first of all, checking that the password match
        if (!password_verify($password, $database->getPassword())) {
            throw new Exception("Password doesn't match");
        }

        // unlocking database
        $file_db = $this->unlock_database($database->getDbname(), $password);

        // generating uniq pass_id
        $pass_id = uniqid($database->getDbname() . '.');

        // opening it
        if ($db = sqlite_open($file_db, 0666, $error)) {
            // request for the insertion
            sqlite_query($db, SqlQueryUtil::insert_password($pass_to_add, $pass_id));
        } else {
            // error occured, raising exception
            throw new Exception($error);
        }

        // insert finished, lock the plain database
        $this->lock_database($file_db, $database->getDbname(), $password);

        // adding entity
        $this->qpasswordManager->create(new QPassword($database, $label, $pass_id));
    }

    /**
     * Retrieve all password for the given database with the given master password
     *
     * No association is made
     *
     * @param QDatabase $database
     * @param string    $password
     *
     * @return array
     */
    public function get_all_password(QDatabase $database, $password)
    {
        // checking password
        if (!password_verify($password, $database->getPassword())) {
            throw new Exception("Invalid password");
        }

        // unlocking the database
        $file_db = $this->unlock_database($database->getDbname(), $password);

        // opening it with sqlite
        if ($db = sqlite_open($file_db, 0666, $error)) {
            // retrieving all passwords
            $query          = sqlite_query($db, SqlQueryUtil::select_all_password());
        } else {
            // unable to open the database, raising error
            throw new Exception($error);
        }

        // locking the database
        $this->lock_database($file_db, $database->getDbname(), $password);

        // returning data
        return sqlite_fetch_all($query, SQLITE_ASSOC);
    }

    /**
     * Retrieve a password for the given database with the given pass_id
     *
     * @param QDatabase $database
     * @param string    $password
     * @param QPassword $qpassword
     *
     * @return string
     */
    public function get_password(QDatabase $database, $password, QPassword $qpassword)
    {
        // checking password
        if (!password_verify($password, $database->getPassword())) {
            throw new Exception("Invalid password");
        }

        // unlocking database
        $file_db = $this->unlock_database($database->getDbname(), $password);

        // trying to open the database
        if ($db = sqlite_open($file_db, 0666, $error)) {
            // retrieving the password
            $query = sqlite_query($db, SqlQueryUtil::select_password($qpassword->getPassId()));
        } else {
            // unable to open the database, raising error
            throw new Exception($error);
        }

        // locking the database
        $this->lock_database($file_db, $database->getDbname(), $password);

        // returning the result
        return sqlite_fetch_single($query);
    }

    /**
     * Lock a given database with the given password
     *
     * @param $file
     * @param $db_name
     * @param $password
     */
    private function lock_database($file, $db_name, $password)
    {
        // file that will be created
        $output = "{$this->db_dir}/{$db_name}.qdb.enc";

        // creating the process builder
        $builder = new ProcessBuilder();
        $builder
            ->setPrefix("gpg")
            ->setWorkingDirectory($this->db_dir)
            ->setArguments(array(
                '-c',
                '--cipher-algo', 'AES256',

                '--passphrase', $password,
                '-o', $output,
                $file
            ))
        ;

        // trying to execute the process
        try {
            $builder
                ->getProcess()
                ->mustRun()
            ;
        } catch (ProcessFailedException $e) {
            // execution failed, raising exception
            throw new Exception($e->getMessage());
        }

        // unlinking plain database
        unlink($file);
    }

    /**
     * Unlock a given database with the given password and put it to /tmp
     *
     * @param string $db_name
     * @param string $password
     *
     * @return string
     */
    private function unlock_database($db_name, $password)
    {
        // file that will be created
        $output = "/tmp/" . uniqid();

        $builder = new ProcessBuilder();
        $builder
            ->setPrefix("gpg")
            ->setWorkingDirectory("{$this->db_dir}/")
            ->setArguments(array(
                '-d',
                '-o', $output,
                '--passphrase', $password,
                "{$db_name}.qdb.enc"
            ))
        ;

        try {
            $builder
                ->getProcess()
                ->mustRun()
            ;
        } catch (ProcessFailedException $e) {
            throw new Exception($e->getMessage());
        }

        // removing old encrypted file
        unlink("{$this->db_dir}/{$db_name}.qdb.enc");

        // returning the name of the plain file
        return $output;
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