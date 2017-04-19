<?php

namespace Querdos\QPassDbBundle\Tests\Util;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Tools\SchemaTool;
use Querdos\QPassDbBundle\Exception\InvalidPasswordException;
use Querdos\QPassDbBundle\Manager\QDatabaseManager;
use Querdos\QPassDbBundle\Manager\QPasswordManager;
use Querdos\QPassDbBundle\Util\PassDatabaseUtil;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Class PassDatabaseUtilTest
 * @package Querdos\QPassDbBundle\Tests\Util
 * @author  Hamza ESSAYEGH <hamza.essayegh@protonmail.com>
 */
class PassDatabaseUtilTest extends KernelTestCase
{
    const DB_NAME_1 = 'database_test_1';
    const DB_PASS_1 = 'database_test_1_password';

    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * @var PassDatabaseUtil
     */
    private $passDbUtil;

    /**
     * @var QDatabaseManager
     */
    private $qdatabaseManager;

    /**
     * @var QPasswordManager
     */
    private $qpasswordManager;

    /**
     * @var string
     */
    private $db_dir;

    /**
     * @var array
     */
    private $input_global;

    protected function setUp()
    {
        self::bootKernel();

        // entity manager
        $this->entityManager = static::$kernel->getContainer()
            ->get('doctrine')
            ->getManager();

        // pass database util
        $this->passDbUtil = static::$kernel->getContainer()
            ->get('qpdb.util.pass_database');

        // QDatabase manager
        $this->qdatabaseManager = static::$kernel->getContainer()
            ->get('qpdb.manager.qdatabase');

        // QPasswordManager
        $this->qpasswordManager = static::$kernel->getContainer()
            ->get('qpdb.manager.qpassword');

        // db_dir
        $this->db_dir = static::$kernel->getContainer()
            ->getParameter('q_pass_db.db_dir');

        $this->input_global = array(
            'database_test_1' => 'database_test_1_password',
            'database_test_2' => 'database_test_2_password',
            'database_test_3' => 'database_test_3_password',
            'database_test_4' => 'database_test_4_password',
            'database_test_5' => 'database_test_5_password'
        );

        // cleaning the database
        $metadata = $this->entityManager->getMetadataFactory()->getAllMetadata();
        if (!empty($metadata)) {
            $tool = new SchemaTool($this->entityManager);
            $tool->dropSchema($metadata);
            $tool->createSchema($metadata);
        }
    }

    public function testCreateDatabase()
    {
        // launching the process
        foreach ($this->input_global as $dbname => $password) {
            $this->passDbUtil->create_database($dbname, $password);

            // checking if the file has been created
            $this->assertTrue(file_exists(sprintf('%s/%s.qdb.enc', $this->db_dir, $dbname)));

            // checking if this same file is encrypted using gpg
            $res = exec("file " . $this->db_dir . "/{$dbname}.qdb.enc");
            preg_match('/.*: (GPG symmetrically encrypted data \(AES256 cipher\))$/', $res, $match);
            $this->assertEquals(2, count($match));

            // checking entity in database
            $this->assertNotNull($this->qdatabaseManager->readByDatabaseName($dbname));
        }
    }

    /**
     * @depends                  testCreateDatabase
     *
     * @expectedException        \Querdos\QPassDbBundle\Exception\InvalidParameterException
     * @expectedExceptionMessage Database name cannot be blank
     */
    public function testNullDbnameForDatabaseCreation()
    {
        $this->passDbUtil->create_database(null, 'azerty');
    }

    /**
     * @depends                  testCreateDatabase
     *
     * @expectedException        \Querdos\QPassDbBundle\Exception\InvalidParameterException
     * @expectedExceptionMessage Database name cannot be blank
     */
    public function testBlankDbnameForDatabaseCreation()
    {
        $this->passDbUtil->create_database('', 'azerty');
    }

    /**
     * @depends testCreateDatabase
     *
     * @expectedException        \Querdos\QPassDbBundle\Exception\InvalidParameterException
     */
    public function testNullPasswordForDatabaseCreation()
    {
        $this->passDbUtil->create_database('querdos', null);
    }

    /**
     * @depends testCreateDatabase
     *
     * @expectedException        \Querdos\QPassDbBundle\Exception\InvalidParameterException
     */
    public function testBlankPasswordForDatabaseCreation()
    {
        $this->passDbUtil->create_database('querdos', '');
    }

    /**
     * @depends                  testCreateDatabase
     *
     * @expectedException        \Querdos\QPassDbBundle\Exception\ExistingDatabaseException
     * @expectedExceptionMessage Database exists
     */
    public function testExistingDatabaseForCreation()
    {
        $this->passDbUtil->create_database('querdos', 'testpass');
        $this->passDbUtil->create_database('querdos', 'testpass'); // fail
    }

    /**
     * @depends testCreateDatabase
     */
    public function testAddPassword()
    {
        // foreach database
        foreach ($this->input_global as $dbname => $password) {
            // creating the database
            $this->passDbUtil->create_database($dbname, $password);

            // retrieving the database
            $db = $this->qdatabaseManager->readByDatabaseName($dbname);
            $this->assertNotNull($db);

            // adding 5 passwords
            $pass_id = $this->passDbUtil->add_password($db, $password, uniqid(), 'label test 1');
            $this->assertNotNull($this->qpasswordManager->readByPassId($pass_id));

            $pass_id = $this->passDbUtil->add_password($db, $password, uniqid(), 'label test 2');
            $this->assertNotNull($this->qpasswordManager->readByPassId($pass_id));

            $pass_id = $this->passDbUtil->add_password($db, $password, uniqid(), 'label test 3');
            $this->assertNotNull($this->qpasswordManager->readByPassId($pass_id));

            $pass_id = $this->passDbUtil->add_password($db, $password, uniqid(), 'label test 4');
            $this->assertNotNull($this->qpasswordManager->readByPassId($pass_id));

            $pass_id = $this->passDbUtil->add_password($db, $password, uniqid(), 'label test 5');
            $this->assertNotNull($this->qpasswordManager->readByPassId($pass_id));
        }
    }

    /**
     * @depends                  testAddPassword
     *
     * @expectedException        \Querdos\QPassDbBundle\Exception\InvalidPasswordException
     * @expectedExceptionMessage Passwords doesn't match
     */
    public function testWrongPasswordForAddingPassword()
    {
        $db = $this->qdatabaseManager->readByDatabaseName('database_test_1');
        $this->passDbUtil->add_password($db, 'wrongPasswordtest', uniqid(), 'label test wrong');
    }

    /**
     * @depends testAddPassword
     *
     * @expectedException \Querdos\QPassDbBundle\Exception\InvalidParameterException
     */
    public function testBlankPasswordToAddForAddingPassword()
    {
        $db = $this->qdatabaseManager->readByDatabaseName('database_test_1');
        $this->passDbUtil->add_password($db, 'database_test_1_password', '', 'label good');
    }

    /**
     * @depends testAddPassword
     *
     * @expectedException \Querdos\QPassDbBundle\Exception\InvalidParameterException
     */
    public function testNullPasswordToAddForAddingPassword()
    {
        $db = $this->qdatabaseManager->readByDatabaseName('database_test_1');
        $this->passDbUtil->add_password($db, 'database_test_1_password', null, 'label good');
    }

    /**
     * @depends testAddPassword
     *
     * @expectedException \Querdos\QPassDbBundle\Exception\InvalidParameterException
     */
    public function testBlankLabelForAddingPassword()
    {
        $db = $this->qdatabaseManager->readByDatabaseName('database_test_1');
        $this->passDbUtil->add_password($db, 'database_test_1_password', 'test', '');
    }

    /**
     * @depends testAddPassword
     *
     * @expectedException \Querdos\QPassDbBundle\Exception\InvalidParameterException
     */
    public function testNullLabelForAddingPassword()
    {
        $db = $this->qdatabaseManager->readByDatabaseName('database_test_1');
        $this->passDbUtil->add_password($db, 'database_test_1_password', 'test', null);
    }

    /**
     * @depends testAddPassword
     */
    public function testGetAllPassword()
    {
        $pass_ids = [];

        // foreach input global
        foreach ($this->input_global as $dbname => $password) {
            // adding database
            $this->passDbUtil->create_database($dbname, $password);
            $db = $this->qdatabaseManager->readByDatabaseName($dbname);

            // adding 5 passwords by database
            for ($i = 1; $i <= 5; $i++) {
                $pass_expected            = uniqid();
                $pass_ids[$pass_expected] = $this->passDbUtil->add_password($db, $password, $pass_expected, "label test $i");
            }

            // checking wrong password
            $this->expectException(InvalidPasswordException::class);
            $this->passDbUtil->get_all_password($db, 'testWrongPassword');

            var_dump("test");
            die;

            // retrieving all passwords
            $passwords = $this->passDbUtil->get_all_password($db, $password);

            // checking count
            $this->assertEquals(6, count($passwords));
            dump($passwords);
            die;

            // checking each password
            foreach ($passwords as $p) {
                var_dump($p);
                die;
                $this->assertNotNull($this->qpasswordManager->readByPassId($p['pass_id']));
            }
            $pass_ids = [];
        }
    }

    /**
     * @depends testGetAllPassword
     */
    public function testGetPassword()
    {
        // foreach input global
        foreach ($this->input_global as $dbname => $password) {
            $this->passDbUtil->create_database($dbname, $password);
            $db = $this->qdatabaseManager->readByDatabaseName($dbname);

            // adding 5 password by database
            for ($i = 1; $i <= 5; $i++) {
                $pass_expected = uniqid();
                $pass_ids      = $this->passDbUtil->add_password($db, $password, $pass_expected, "label test $i");
                $qpass         = $this->qpasswordManager->readByPassId($pass_ids);

                $pass_retrieved = $this->passDbUtil->get_password($db, $password, $qpass);
                $this->assertEquals($pass_expected, $pass_retrieved);
            }
        }
    }

    protected function tearDown()
    {
        parent::tearDown();

        $this->entityManager->close();
        $this->entityManager = null;
    }
}