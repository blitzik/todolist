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

    /**
     * If method returns NULL, it means that given Task is direct child
     * and does not have "upper sibling".
     *
     * @param Task $task
     * @return \DateTime|null
     */
    public function getUpperSiblingDeadline(Task $task)
    {
        $usd = $asd = $this->em
            ->createQuery(
                'SELECT t.deadline FROM ' . Task::class . ' t
                 WHERE t.root = :root AND t.rgt = :rgt'
            )->setParameters(['root' => $task->root,
                              'rgt' => ($task->lft - 1)])
            ->getArrayResult();

        return empty($usd[0]) ? null : $usd[0]['deadline'];
    }

    /**
     * @param Task $task
     * @return \DateTime|null
     */
    public function getDirectChildDeadline(Task $task)
    {
        $dcd = $this->em
            ->createQuery(
                'SELECT t.deadline FROM ' . Task::class . ' t
                 WHERE t.root = :root AND t.lft = :lft'
            )->setParameters(['root' => $task->root,
                              'lft' => ($task->lft + 1)])
            ->getArrayResult();

        return empty($dcd[0]) ? null : $dcd[0]['deadline'];
    }

    /**
     * @param Task $task
     * @return \DateTime|null
     */
    public function getLowerSiblingDeadline(Task $task)
    {
        $lsd = $this->em
            ->createQuery(
                'SELECT t.deadline FROM ' . Task::class . ' t
                 WHERE t.root = :root AND t.lft = :lft'
            )->setParameters(['root' => $task->root,
                              'lft' => ($task->rgt + 1)])
            ->getArrayResult();

        return empty($lsd[0]) ? null : $lsd[0]['deadline'];
    }

    /**
     * If method return NULL, Task has NO children.
     *
     * @param Task $task
     * @return \DateTime|null
     */
    public function getLastChildDeadline(Task $task)
    {
        $lcd = $this->em->createQuery(
            'SELECT t.deadline FROM ' . Task::class . ' t
             WHERE t.root = :root AND t.rgt = :rgt'
        )->setParameters(['root' => $task->root,
                          'rgt' => $task->rgt - 1])
        ->getArrayResult();

        return empty($lcd[0]) ? null : $lcd[0]['deadline'];
    }

    /**
     * @param Task $task
     * @return bool
     */
    public function isLastChild(Task $task)
    {
        $parentRgt = $this->em->createQuery(
            'SELECT t.rgt FROM ' . Task::class . ' t
             WHERE t.id = :parent'
        )->setParameter('parent', $task->parent)
        ->getSingleScalarResult();

        return ($task->getRgt() == ($parentRgt - 1));
    }

    /**
     * Returns lowest possible deadline that User can set to the Task.
     * Lowest possible deadline is always current date or lower sibling
     * deadline that is not in the past.
     *
     * @param Task $task
     * @return \DateTime
     */
    public function findLowestDeadlineValue(Task $task)
    {
        $currentDate = new \DateTime(date('Y-m-d'));
        $lowestDateValue = null;

        $directChildDeadline = $this->getDirectChildDeadline($task);
        $lowerSiblingDeadline = $this->getLowerSiblingDeadline($task);

        if ($directChildDeadline === null) { // Task does not have any children
            if ($lowerSiblingDeadline === null) { // neither lower sibling
                $lowestDateValue = $currentDate;
            } else {
                $lowestDateValue = $lowerSiblingDeadline;
            }
        } else { // Task has child(ren)

            if ($lowerSiblingDeadline === null) { // Task has child but no lower sibling
                $lowestDateValue = $directChildDeadline;
            } else { // Task have both child and lower sibling

                if ($directChildDeadline < $lowerSiblingDeadline) {
                    $lowestDateValue = $lowerSiblingDeadline;
                } else {
                    $lowestDateValue = $directChildDeadline;
                }
            }
        }

        /*
         For overdue Tasks, User could not be able to select deadline value lower than
         current date for Root Task and NO deadline value for subTasks.
         (in form template, date picker minDate and maxDate are crossed)
        */
        if ($lowestDateValue < $currentDate) {
            $lowestDateValue = $currentDate;
        }

        return $lowestDateValue;
    }

    /**
     * @param Task $task
     * @return \DateTime|null
     */
    public function findHighestDeadlineValue(Task $task)
    {
        $highestDeadlineValue = null;
        $upperSiblingDeadline = $this->getUpperSiblingDeadline($task);

        if ($task->parent !== null) { // Sub task
            if ($upperSiblingDeadline === null) { // no upper sibling => Task is direct child of Root Task
                $highestDeadlineValue = $task->parent->deadline;
            } else {
                $highestDeadlineValue = $upperSiblingDeadline;
            }
        } // There is no upper sibling for Root Task => method returns NULL

        return $highestDeadlineValue;
    }

    /**
     * @param Task $task
     * @return \DateTime
     */
    public function findLastChildDeadlineValue(Task $task)
    {
        $lastChildDeadlineValue = $this->getLastChildDeadline($task);
        if ($lastChildDeadlineValue === null) { // Task has no children and thus no last one
            $lastChildDeadlineValue = $task->deadline;
        }

        return $lastChildDeadlineValue;
    }

    /**
     * @param Task $task
     * @throws \Exception
     */
    public function removeTask(Task $task)
    {
        $this->em->beginTransaction();
        try {
            $parent = $task->parent;
            $isLastChild = $this->isLastChild($task);

            $this->em->remove($task)->flush();

            if ($parent !== null and $isLastChild) {
                $lastChildDeadline = $this->findLastChildDeadlineValue($parent);
                $parent->setLastChildDeadline($lastChildDeadline);
                $this->em->persist($parent)->flush();
            }

            $this->em->commit();
        } catch (\Exception $e) {
            $this->em->rollback();
            $this->em->close();

            throw $e;
        }
    }

}