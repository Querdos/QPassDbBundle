<?php

namespace Querdos\QPassDbBundle\Manager;

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
        return $this->repository->findByOneByPassId($pass_id);
    }
}