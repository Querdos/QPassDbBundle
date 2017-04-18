<?php

namespace Querdos\QPassDbBundle\Manager;

use Querdos\QPassDbBundle\Entity\QDatabase;

/**
 * Class QDatabaseManager
 * @package Querdos\QPassDbBundle\Manager
 * @author  Hamza ESSAYEGH <hamza.essayegh@protonmail.com>
 */
class QDatabaseManager extends BaseManager
{
    /**
     * @var QDatabase $qdatabase
     */
    public function create($qdatabase)
    {
        // hashing password
        $qdatabase->setPassword(
            password_hash($qdatabase->getPassword(), PASSWORD_BCRYPT)
        );

        parent::create($qdatabase);
    }

    /**
     * Return a QDatabase with the given dbname
     *
     * @param $dbname
     *
     * @return QDatabase
     */
    public function readByDatabaseName($dbname)
    {
        return $this->repository->findOneByDbname($dbname);
    }
}