<?php

namespace Manager;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Tools\SchemaTool;
use Querdos\QPassDbBundle\Entity\QDatabase;
use Querdos\QPassDbBundle\Manager\QDatabaseManager;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Config\Definition\Exception\Exception;

/**
 * Class QDatabaseManager
 * @package Manager
 * @author  Hamza ESSAYEGH <hamza.essayegh@protonmail.com>
 */
class QDatabaseManagerTest extends KernelTestCase
{
    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * @var QDatabaseManager
     */
    private $qdatabaseManager;

    public function setUp()
    {
        self::bootKernel();

        $this->entityManager = static::$kernel->getContainer()
            ->get('doctrine')
            ->getManager()
        ;

        $this->qdatabaseManager = static::$kernel->getContainer()
            ->get('qpdb.manager.qdatabase')
        ;

        $metadata = $this->entityManager->getMetadataFactory()->getAllMetadata();
        if (!empty($metadata)) {
            $tool = new SchemaTool($this->entityManager);
            $tool->dropSchema($metadata);
            $tool->createSchema($metadata);
        }
    }

    public function testCreateQDatabase()
    {
        // creating a new entity
        $db_name   = "database_test";
        $db_pass   = "database_test_password";
        $qdatabase = new QDatabase($db_name, $db_pass);

        // calling the manager
        $this->qdatabaseManager->create($qdatabase);

        // building a request to check if the entity has been persisted
        $result = $this->entityManager
            ->createQueryBuilder()

            ->select("qdatabase")
            ->from("QPassDbBundle:QDatabase", "qdatabase")

            ->where("qdatabase.dbname = :db_name")
            ->setParameter('db_name', $db_name)

            ->getQuery()
            ->getOneOrNullResult()
        ;

        // asserting not null
        $this->assertNotNull($result);

        // asserting that the retrieved password isn't plain
        $this->assertNotEquals($db_pass, $result->getPassword());

        // asserting that the hash is correct
        $this->assertTrue(password_verify($db_pass, $result->getPassword()));
    }

    protected function tearDown()
    {
        parent::tearDown();

        $this->entityManager->close();
        $this->entityManager = null;
    }
}