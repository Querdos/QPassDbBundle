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
            password_hash($qdatabase->getPlainPassword(), PASSWORD_BCRYPT)
        );

        // setting the plain password to null
        $qdatabase->setPlainPassword(null);

        parent::create($qdatabase);
    }

    /**
     * @param QDatabase $qdatabase
     */
    public function update($qdatabase)
    {
        // checking if a plain password has been specified
        if ($qdatabase->getPlainPassword() !== null) {
            // in this case, changing the actual password
            $qdatabase->setPassword(
                password_hash($qdatabase->getPlainPassword(), PASSWORD_BCRYPT)
            );
        }

        parent::update($qdatabase);
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