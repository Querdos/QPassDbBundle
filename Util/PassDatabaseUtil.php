<?php

namespace Querdos\QPassDbBundle\Util;
use PDO;
use Querdos\QPassDbBundle\Entity\QDatabase;
use Querdos\QPassDbBundle\Entity\QPassword;
use Querdos\QPassDbBundle\Exception\ExistingDatabaseException;
use Querdos\QPassDbBundle\Exception\InvalidParameterException;
use Querdos\QPassDbBundle\Exception\InvalidPasswordException;
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
     *
     * @throws ExistingDatabaseException
     * @throws InvalidParameterException
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
            throw new InvalidParameterException((string) $dbname_val);
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
            throw new InvalidParameterException((string) $dbname_val);
        }

        // checking if the database_dir exists and creating it if necessary
        if (!is_dir($this->db_dir)) {
            mkdir($this->db_dir, 0777, true);
        }

        // checking if database exists already or not
        if ($this->qdatabaseManager->readByDatabaseName($dbname) !== null) {
            throw new ExistingDatabaseException("Database exists");
        }

        // checking if the file doesn't exists
        if (file_exists($this->db_dir . "/{$dbname}.qdb.enc")) {
            unlink($this->db_dir . "/{$dbname}.qdb.enc"); // if present in dir but not in database <=> problem
        }

        // opening sqlite database
        try {
            $pdo = new PDO("sqlite:{$this->db_dir}/{$dbname}.qdb");
            $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            $pdo->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION);
        } catch (\SQLiteException $e) {
            throw new Exception($e->getMessage());
        }

        // creating the table
        $pdo->query(SqlQueryUtil::create_table());

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
     *
     * @return string
     * @throws InvalidParameterException
     * @throws InvalidPasswordException
     */
    public function add_password(QDatabase $database, $password, $pass_to_add, $label)
    {
        // first of all, checking that the password match
        if (!password_verify($password, $database->getPassword())) {
            throw new InvalidPasswordException("Passwords doesn't match");
        }

        // checking label
        $label_er = $this->validator->validatePropertyValue(QPassword::class, 'label', $label);
        if (0 != count($label_er)) {
            throw new InvalidParameterException((string) $label_er);
        }

        // checking pass_to_add
        $pass_er = $this->validator->validatePropertyValue(QPassword::class, 'password', $pass_to_add);
        if (0 != count($pass_er)) {
            throw new InvalidParameterException("Invalid password");
        }

        // unlocking database
        $file_db = $this->unlock_database($database->getDbname(), $password);

        // generating uniq pass_id
        $pass_id = uniqid($database->getDbname() . '.');

        // opening it
        try {
            $pdo = new PDO("sqlite:{$file_db}");
            $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (\SQLiteException $e) {
            throw new Exception($e->getMessage());
        }

        // adding the password
        $statement = $pdo->prepare(SqlQueryUtil::insert_password());
        $statement->execute(array(
            'password' => $pass_to_add,
            'pass_id'  => $pass_id
        ));

        // insert finished, lock the plain database
        $this->lock_database($file_db, $database->getDbname(), $password);

        // adding entity
        $this->qpasswordManager->create(new QPassword($database, $label, $pass_id));

        // returning the pass_id of the newly added password
        return $pass_id;
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
     * @throws InvalidPasswordException
     */
    public function get_all_password(QDatabase $database, $password)
    {
        // checking password
        if (!password_verify($password, $database->getPassword())) {
            throw new InvalidPasswordException("Invalid password");
        }

        // unlocking the database
        $file_db = $this->unlock_database($database->getDbname(), $password);

        // opening it
        try {
            $pdo = new PDO("sqlite:{$file_db}");
            $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (\SQLiteException $e) {
            throw new Exception($e->getMessage());
        }

        // executing query
        $statement = $pdo->prepare(SqlQueryUtil::select_all_password());
        $statement->execute();

        // locking the database
        $this->lock_database($file_db, $database->getDbname(), $password);

        // returning data
        return $statement->fetchAll();
    }

    /**
     * Retrieve a password for the given database with the given pass_id
     *
     * @param QDatabase $database
     * @param string    $password
     * @param QPassword $qpassword
     *
     * @return string
     * @throws InvalidPasswordException
     */
    public function get_password(QDatabase $database, $password, QPassword $qpassword)
    {
        // checking password
        if (!password_verify($password, $database->getPassword())) {
            throw new InvalidPasswordException("Invalid password");
        }

        // unlocking database
        $file_db = $this->unlock_database($database->getDbname(), $password);

        // trying to open the database
        try {
            $pdo = new PDO("sqlite:{$file_db}");
            $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }

        // querying the database
        $statement = $pdo->prepare(SqlQueryUtil::select_password());
        $statement->execute(array(
            'pass_id' => $qpassword->getPassId()
        ));

        // locking the database
        $this->lock_database($file_db, $database->getDbname(), $password);

        // returning the result
        return $statement->fetchColumn();
    }

    /**
     * @param QDatabase $database
     * @param string    $password
     * @param string    $pass_id
     *
     * @throws InvalidPasswordException
     */
    public function remove_password(QDatabase $database, $password, $pass_id)
    {
        // checking password
        if (!password_verify($pass_id, $database->getPassword())) {
            throw new InvalidPasswordException("Invalid password");
        }

        // unlocking the database
        $file_db = $this->unlock_database($database->getDbname(), $password);

        // trying to open it
        try {
            $pdo = new PDO("sqlite:{$file_db}");
            $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (\Exception $e) {
            throw new \SQLiteException($e->getMessage());
        }

        // removing the password
        $statement = $pdo->prepare(SqlQueryUtil::remove_password());
        $statement->execute(array(
            'pass_id' => $pass_id
        ));

        // locking the database
        $this->lock_database($file_db, $database->getDbname(), $password);
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