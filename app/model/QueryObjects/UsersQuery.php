<?php

namespace TodoList\QueryObjects;

use Kdyby\Persistence\Queryable;
use Kdyby\Doctrine\QueryObject;
use TodoList\Entities\User;
use Kdyby;

class UsersQuery extends QueryObject
{
    /**
     * @var \Closure[]
     */
    private $select = [];

    /**
     * @var \Closure[]
     */
    private $filter = [];

    /**
     * @param $email
     * @return $this
     */
    public function byEmail($email)
    {
        $this->filter[] = function (Kdyby\Doctrine\QueryBuilder $qb) use ($email) {
            $qb->andWhere('u.email = :email')
               ->setParameter('email', $email);
        };

        return $this;
    }

    /**
     * @param $username
     * @return $this
     */
    public function byUsername($username)
    {
        $this->filter[] = function (Kdyby\Doctrine\QueryBuilder $qb) use ($username) {
            $qb->andWhere('u.username = :username')
                ->setParameter('username', $username);
        };

        return $this;
    }

    /**
     * @param Queryable $repository
     * @return Kdyby\Doctrine\QueryBuilder
     */
    protected function doCreateCountQuery(Queryable $repository)
    {
        return $this->createBasicDQL($repository)->select('COUNT(u.id)');
    }


    /**
     * @param \Kdyby\Persistence\Queryable $repository
     * @return \Doctrine\ORM\Query|\Doctrine\ORM\QueryBuilder
     */
    protected function doCreateQuery(Kdyby\Persistence\Queryable $repository)
    {
        $qb = $this->createBasicDQL($repository);

        foreach ($this->select as $modifier) {
            $modifier($qb);
        }

        return $qb;
    }

    /**
     * @param Queryable $repository
     * @return Kdyby\Doctrine\QueryBuilder
     */
    private function createBasicDQL(Kdyby\Persistence\Queryable $repository)
    {
        $qb = $repository->createQueryBuilder()
                         ->select('u')
                         ->from(User::class, 'u');

        foreach ($this->filter as $modifier) {
            $modifier($qb);
        }

        return $qb;
    }

}