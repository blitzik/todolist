<?php

namespace TodoList\Facades;

use TodoList\RuntimeExceptions\TaskNotFoundException;
use TodoList\Repositories\TaskRepository;
use Kdyby\Doctrine\EntityManager;
use TodoList\Entities\Project;
use TodoList\Entities\Task;
use Nette\Security\User;
use Nette\Object;

class TasksFacade extends Object
{
    /**
     * @var TaskRepository
     */
    private $tasksRepository;

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
        $this->tasksRepository = $entityManager->getRepository(Task::class);
        $this->user = $user;
    }

    /**
     * @param $id
     * @param User $owner
     * @return Task
     * @throws TaskNotFoundException
     */
    public function getTask($id, User $owner = null)
    {
        $taskOwner = $this->user->getIdentity();
        if ($owner !== null) {
            $taskOwner = $owner;
        }

        $task = $this->em->createQuery(
            'SELECT t
             FROM ' . Task::class . ' t
             JOIN ' . Project::class . ' p WITH p = t.project
             WHERE t.id = :task_id AND p.owner = :owner'
        )->setParameters(
            ['task_id' => $id,
             'owner' => $taskOwner
            ]
        )->getResult();

        if (empty($task)) {
            throw new TaskNotFoundException;
        }

        return $task[0];
    }

}