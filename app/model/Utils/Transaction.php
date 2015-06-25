<?php

namespace TodoList;

use Kdyby\Doctrine\EntityManager;
use Nette\Object;

class Transaction extends Object
{
    /**
     * @var EntityManager
     */
    private $entityManager;

    public function __construct(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function begin()
    {
        $this->entityManager->getConnection()->beginTransaction();
    }

    public function commit()
    {
        $this->entityManager->getConnection()->commit();
    }

    public function rollback()
    {
        $this->entityManager->getConnection()->rollBack();
    }
}