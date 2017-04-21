<?php

namespace Querdos\QPassDbBundle\Manager;

use Querdos\QPassDbBundle\Entity\QDatabase;
use Querdos\QPassDbBundle\Entity\QPassword;

/**
 * Class QPasswordManager
 * @package Querdos\QPassDbBundle\Manager
 * @author  Hamza ESSAYEGH <hamza.essayegh@protonmail.com>
 */
class QPasswordManager extends BaseManager
{
    /**
     * Return a QPassword with the given pass_id
     *
     * @param string $pass_id
     *
     * @return QPassword
     */
    public function readByPassId($pass_id)
    {
        return $this->repository->readByPassId($pass_id);
    }

    /**
     * Return all QPasswords for the given database
     *
     * @param QDatabase $QDatabase
     *
     * @return mixed
     */
    public function allForQDatabase(QDatabase $QDatabase)
    {
        return $this->repository->findByQdatabase($QDatabase);
    }
}