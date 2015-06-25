<?php

namespace TodoList\QueryObjects;

use Kdyby\Doctrine\QueryObject;
use Kdyby;
use Nette\Utils\Validators;
use TodoList\Entities\Project;
use TodoList\Entities\User;

class ProjectsQuery extends QueryObject
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
     * @param $id
     * @return $this
     */
    public function byID($id)
    {
        Validators::assert($id, 'numericint:1..');

        $this->filter[] = function (Kdyby\Doctrine\QueryBuilder $qb) use ($id) {
            $qb->andWhere('p.id = :id')
               ->setParameter('id', $id);
        };

        return $this;
    }

    /**
     * @param User $owner
     * @return $this
     */
    public function byOwner(User $owner)
    {
        $this->filter[] = function (Kdyby\Doctrine\QueryBuilder $qb) use ($owner) {
            $qb->andWhere('p.owner = :owner')
                ->setParameter('owner', $owner);
        };

        return $this;
    }

    public function getRoot()
    {
        $this->filter[] = function (Kdyby\Doctrine\QueryBuilder $qb) {
            $qb->andWhere('p.lft = :lft')
               ->setParameter('lft', 1);
        };
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

    private function createBasicDQL(Kdyby\Persistence\Queryable $repository)
    {
        $qb = $repository->createQueryBuilder()
                         ->select('p')
                         ->from(Project::class, 'p');

        foreach ($this->filter as $modifier) {
            $modifier($qb);
        }

        return $qb;
    }

}