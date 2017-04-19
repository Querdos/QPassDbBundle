<?php

namespace Manager;

use Doctrine\ORM\EntityManager;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Class QPasswordManagerTest
 * @package Manager
 * @author  Hamza ESSAYEGH <hamza.essayegh@protonmail.com>
 */
class QPasswordManagerTest extends KernelTestCase
{
    /**
     * @var EntityManager
     */
    private $entityManager;

    public function setUp()
    {
        self::bootKernel();

        $this->entityManager = static::$kernel->getContainer()
            ->get('doctrine')
            ->getManager()
        ;
    }

    public function testCreateQPassword()
    {
        //
    }

    public function tearDown()
    {
        parent::tearDown();

        $this->entityManager->close();
        $this->entityManager = null;
    }
}