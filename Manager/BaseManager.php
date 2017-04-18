<?php

namespace Querdos\QPassDbBundle\Manager;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;

/**
 * Class BaseManager
 * @package Querdos\QPassDbBundle\Manager
 * @author  Hamza ESSAYEGH <hamza.essayegh@protonmail.com>
 */
class BaseManager
{
    /**
     * @var EntityManager
     */
    protected $entityManager;

    /**
     * @var EntityRepository
     */
    protected $repository;

    /**
     * @param $entity
     */
    public function create($entity)
    {
        $this->entityManager->persist($entity);
        $this->entityManager->flush($entity);
    }

    /**
     * @param $entity
     */
    public function update($entity)
    {
        $uow = $this->entityManager->getUnitOfWork();

        if (!$uow->isEntityScheduled($entity)) {
            $this->entityManager->persist($entity);
        }

        $this->entityManager->flush($entity);
    }

    /**
     * @param $entity
     */
    public function delete($entity)
    {
        $this->entityManager->remove($entity);
        $this->entityManager->flush($entity);
    }

    /**
     * @param $id
     *
     * @return mixed
     */
    public function readById($id)
    {
        return $this->repository->findOneById($id);
    }

    /**
     * @return array
     */
    public function all()
    {
        return $this->repository->findAll();
    }

    /**
     * @param EntityManager $entityManager
     *
     * @return BaseManager
     */
    public function setEntityManager($entityManager)
    {
        $this->entityManager = $entityManager;
        return $this;
    }

    /**
     * @param EntityRepository $repository
     *
     * @return BaseManager
     */
    public function setRepository($repository)
    {
        $this->repository = $repository;
        return $this;
    }


}