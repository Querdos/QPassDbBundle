<?php

namespace Querdos\QPassDbBundle\Tests\Util;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Tools\SchemaTool;
use Querdos\QPassDbBundle\Entity\QDatabase;
use Querdos\QPassDbBundle\Entity\QPassword;
use Querdos\QPassDbBundle\Exception\InvalidPasswordException;
use Querdos\QPassDbBundle\Manager\QDatabaseManager;
use Querdos\QPassDbBundle\Manager\QPasswordManager;
use Querdos\QPassDbBundle\Util\PassDatabaseUtil;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Class PassDatabaseUtilTest
 * @package Querdos\QPassDbBundle\Tests\Util
 * @author  Hamza ESSAYEGH <hamza.essayegh@protonmail.com>
 *
 * TODO: Exceptions testing
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
        // initial parameters
        $db_name = 'database_name_test';
        $db_pass = 'database_pass_test';

        // creating the database
        $qdatabase = $this->passDbUtil->create_database($db_name, $db_pass);

        // checking that a file has been created
        $this->assertTrue(file_exists("{$this->db_dir}/{$db_name}.qdb.enc"));

        // checking that the file has GPG header
        // TODO: Check why with travis it is a DOS Executable and not GPG encrypted file
//        $this->assertRegExp('/AES256 cipher/', exec("file {$this->db_dir}/{$db_name}.qdb.enc"));

        // checking that the database has been persisted
        $this->assertNotNull($this->qdatabaseManager->readByDatabaseName($qdatabase->getDbname()));
    }

    /**
     * @expectedException \Querdos\QPassDbBundle\Exception\InvalidParameterException
     */
    public function testCreateDatabaseWithNullDbName()
    {
        $this->passDbUtil->create_database(null, 'test');
    }

    /**
     * @expectedException \Querdos\QPassDbBundle\Exception\InvalidParameterException
     */
    public function testCreateDatabaseWithBlankDbName()
    {
        $this->passDbUtil->create_database('', 'test');
    }

    /**
     * @expectedException \Querdos\QPassDbBundle\Exception\InvalidParameterException
     */
    public function testCreateDatabaseWithNullPassword()
    {
        $this->passDbUtil->create_database('test', null);
    }

    /**
     * @expectedException \Querdos\QPassDbBundle\Exception\InvalidParameterException
     */
    public function testCreateDatabaseWithBlankPassword()
    {
        $this->passDbUtil->create_database('test', null);
    }

    /**
     * @expectedException \Querdos\QPassDbBundle\Exception\ExistingDatabaseException
     */
    public function testCreateDatabaseWithExistingDbname()
    {
        $this->passDbUtil->create_database('test', 'testpass');
        $this->passDbUtil->create_database('test', 'test');
    }

    /**
     * @depends testCreateDatabase
     */
    public function testAddPassword()
    {
        // creating a database
        $db_name     = "database_name_test";
        $db_pass     = "database_pass_test";
        $qdatabase   = $this->passDbUtil->create_database($db_name, $db_pass);
        $size_before = filesize("{$this->db_dir}/{$db_name}.qdb.enc");

        // adding a password
        $before = count($this->qpasswordManager->allForQDatabase($qdatabase));
        $this->passDbUtil->add_password($qdatabase, $db_pass, 'test_password', 'label test');
        $size_after = filesize("{$this->db_dir}/{$db_name}.qdb.enc");
        $after      = count($this->qpasswordManager->allForQDatabase($qdatabase));

        // checking that a password has been added
        $this->assertGreaterThan($before, $after);
        $this->assertGreaterThan($size_before, $size_after);
    }

    /**
     * @depends testAddPassword
     */
    public function testGetAllPassword()
    {
        // creating a database
        $db_name   = 'test_name';
        $db_pass   = 'test_pass';
        $qdatabase = $this->passDbUtil->create_database($db_name, $db_pass);

        // first checking that no passwords is in database
        $this->assertEquals(0, count($this->passDbUtil->get_all_password($qdatabase, $db_pass)));

        // add a password
        $passes[] = uniqid();
        $passes[] = uniqid();

        $labe_1 = uniqid();
        $labe_2 = uniqid();

        $qpassword[] = $this->passDbUtil->add_password($qdatabase, $db_pass, $passes[0], $labe_1);
        $qpassword[] = $this->passDbUtil->add_password($qdatabase, $db_pass, $passes[1], $labe_2);

        // retrieving all passwords
        $passwords = $this->passDbUtil->get_all_password($qdatabase, $db_pass);

        // checking that 1 passwords has been added
        $this->assertEquals(2, count($passwords));

        // checking that the password are correct
        foreach ($passwords as $index => $pass) {
            $this->assertEquals($pass['pass_id'], $qpassword[$index]->getPassId());
            $this->assertEquals($pass['password'],$passes[$index]);
        }
    }

    /**
     * @depends testGetAllPassword
     */
    public function testRemovePassword()
    {
        // creating a database
        $db_name   = uniqid();
        $db_pass   = uniqid();
        $qdatabase = $this->passDbUtil->create_database($db_name, $db_pass);
        $db_file = "{$this->db_dir}/{$qdatabase->getDbname()}.qdb.enc";

        // creating two passwords
        $qpasswords[] = $this->passDbUtil->add_password($qdatabase, $db_pass, uniqid(), uniqid());
        $qpasswords[] = $this->passDbUtil->add_password($qdatabase, $db_pass, uniqid(), uniqid());
        $size[] = filesize($db_file);

        // retrieving the count
        $count = count($this->passDbUtil->get_all_password($qdatabase, $db_pass));
        $this->assertEquals(2, $count);

        // removing the first password
        $this->passDbUtil->remove_password($qdatabase, $db_pass, $qpasswords[0]);
        $size[] = filesize($db_file);
        // TODO: See why doesn't work with travis
//        $this->assertLessThan($size[0], $size[1]);

        $count = count($this->passDbUtil->get_all_password($qdatabase, $db_pass));
        $this->assertEquals(1, $count);
        $this->assertEquals(1, count($this->qpasswordManager->allForQDatabase($qdatabase)));

        // removing the second password
        $this->passDbUtil->remove_password($qdatabase, $db_pass, $qpasswords[1]);
        $size[] = filesize($db_file);
        // TODO: See why doesn't work with travis
//        $this->assertLessThan($size[1], $size[2]);

        $count = count($this->passDbUtil->get_all_password($qdatabase, $db_pass));
        $this->assertEquals(0, $count);
        $this->assertEquals(0, count($this->qpasswordManager->allForQDatabase($qdatabase)));
    }

    /**
     * @depends testGetAllPassword
     */
    public function testGetPassword()
    {
        // creating a database
        $db_name = uniqid();
        $db_pass = uniqid();
        $qdatabase = $this->passDbUtil->create_database($db_name, $db_pass);

        // adding a password
        $pass_expected = uniqid();
        $qpassword = $this->passDbUtil->add_password($qdatabase, $db_pass, $pass_expected, uniqid());

        // retrieving the password
        $pass_value = $this->passDbUtil->get_password($qdatabase, $db_pass, $qpassword);
        $this->assertEquals($pass_expected, $pass_value);
    }

    protected function tearDown()
    {
        parent::tearDown();

        $this->entityManager->close();
        $this->entityManager = null;
    }
}