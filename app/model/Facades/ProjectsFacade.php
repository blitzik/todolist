<?php

namespace TodoList\Facades;

use TodoList\Repositories\ProjectRepository;
use Doctrine\ORM\NoResultException;
use Kdyby\Doctrine\EntityManager;
use Doctrine\ORM\AbstractQuery;
use TodoList\Entities\Project;
use Nette\Security\User;
use Nette\Object;

class ProjectsFacade extends Object
{
    /**
     * @var ProjectRepository
     */
    private $projectsRepository;

    /**
     * @var EntityManager
     */
    private $em;

    /**
     * @var User
     */
    private $user;

    public function __construct(
        EntityManager $entityManager,
        User $user
    ) {
        $this->em = $entityManager;
        $this->user = $user;

        $this->projectsRepository = $this->em->getRepository(Project::class);
    }

    /**
     * @param $id
     * @param \TodoList\Entities\User $owner
     * @return null|string
     */
    public function getProjectName($id, \TodoList\Entities\User $owner)
    {
        try {
            return $this->em
                ->createQuery('SELECT p.name as name FROM ' . Project::class . ' p
                               WHERE p.id = :id AND p.owner = :owner')
                ->setParameters(['id' => $id,
                                 'owner' => $owner])
                ->getResult(AbstractQuery::HYDRATE_SINGLE_SCALAR);

        } catch (NoResultException $e) {
            return null;
        }
    }

    /**
     * @param $id
     * @param \TodoList\Entities\User $owner
     * @return null|Project
     */
    public function getProject($id, \TodoList\Entities\User $owner = null)
    {
        if ($owner === null) {
            $owner = $this->user->getIdentity();
        }

        $project = $this->projectsRepository
                        ->findOneBy(
                            ['id' => $id, 'owner' => $owner]
                        );

        return $project;
    }
}