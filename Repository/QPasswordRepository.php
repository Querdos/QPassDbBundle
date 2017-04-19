<?php

namespace Querdos\QPassDbBundle\Repository;

use Doctrine\ORM\EntityRepository;

/**
 * Class QPasswordRepository
 * @package Querdos\QPassDbBundle\Repository
 * @author  Hamza ESSAYEGH <hamza.essayegh@protonmail.com>
 */
class QPasswordRepository extends EntityRepository
{
    public function readByPassId($pass_id)
    {
        $query = $this
            ->getEntityManager()
            ->createQueryBuilder()

            ->select('qpassword')
            ->from('QPassDbBundle:QPassword', 'qpassword')

            ->where('qpassword.pass_id = :pass_id')
            ->setParameter('pass_id', $pass_id)
        ;

        return $query
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
}