<?php

namespace TodoList\Components;

use Nextras\Application\UI\SecuredLinksControlTrait;
use TodoList\Repositories\TaskRepository;
use Nette\Application\UI\Multiplier;
use Kdyby\Doctrine\EntityManager;
use Nette\Application\UI\Control;
use Kdyby\Doctrine\QueryBuilder;
use TodoList\Entities\Task;
use Nette\Security\User;
use Nette\Utils\Arrays;
use Tracy\Debugger;

class TasksListControl extends Control
{
    use SecuredLinksControlTrait;

    /**
     * @var ITaskLabelControlFactory
     */
    private $taskLabelControlFactory;

    /**
     * @var ITaskFormControlFactory
     */
    private $taskFormFactory;

    /**
     * @var TaskRepository
     */
    private $tasksRepository;

    /**
     * @var User
     */
    private $user;

    /**
     * @var array
     */
    private $tasks;

    /**
     * @var QueryBuilder
     */
    private $qb;

    /**
     * @var EntityManager
     */
    private $em;

    // ------

    private $isAddTaskVisible = true;
    private $isEditButtonVisible = true;
    private $isRemoveButtonVisible = true;

    public function __construct(
        QueryBuilder $qb,
        User $user,
        EntityManager $entityManager,
        ITaskFormControlFactory $taskFormFactory,
        ITaskLabelControlFactory $taskLabelControlFactory
    ) {
        $this->qb = $qb;
        $this->em = $entityManager;
        $this->user = $user;
        $this->tasksRepository = $entityManager->getRepository(Task::class);
        $this->taskFormFactory = $taskFormFactory;
        $this->taskLabelControlFactory = $taskLabelControlFactory;
    }

    public function hideAddTaskButton()
    {
        $this->isAddTaskVisible = false;
    }

    public function hideEditButton()
    {
        $this->isEditButtonVisible = false;
    }

    public function hideRemoveButton()
    {
        $this->isRemoveButtonVisible = false;
    }

    protected function createComponentTaskLabel()
    {
        return new Multiplier(function ($taskID) {

            if (empty($this->tasks)) {
                // For Ajax requests and when is standard signal invoked
                // (handleEditLabel from TaskLabelControl)
                $t = $this->qb->where('t.id = :id')
                              ->groupBy('t.id')
                              ->setParameters(['id' => $taskID])
                              ->getQuery()->getArrayResult();
                $task = $t[0];
            } else {
                $task = $this->tasks[$taskID];
            }

            $comp = $this->taskLabelControlFactory->create($task);
            $comp['taskForm']['form']['description']->setDefaultValue($task['description']);
            $comp['taskForm']['form']['date']->setDefaultValue(
                $task['deadline']->format('d.m.Y')
            );

            return $comp;
        });
    }

    /**
     * @secured
     * @param $taskID
     */
    public function handleMarkAsDone($taskID)
    {

    }

    /**
     * @secured
     * @param $taskID
     */
    public function handleRemoveTask($taskID)
    {
        $this->em->remove($this->em->getReference(Task::class, $taskID));
        $this->em->flush();

        $this->flashMessage('Task has been removed.', 'bg-success');
        $this->redirect('this');
    }

    public function render()
    {
        $template = $this->getTemplate();
        $template->setFile(__DIR__ . '/tasks-list.latte');

        if (empty($this->tasks)) {
            $this->tasks = Arrays::associate($this->qb->getQuery()->getArrayResult(), 'id');
        }

        $nodes = Arrays::associate($this->tasks, 'root|id');

        $tasks = [];
        foreach ($nodes as $root => $task) {
            $tasks[$root] = $this->tasksRepository->buildTreeArray($task);
        }

        $template->tasks = $tasks;
        $template->isAddTaskVisible = $this->isAddTaskVisible;
        $template->isEditButtonVisible = $this->isEditButtonVisible;
        $template->isRemoveButtonVisible = $this->isRemoveButtonVisible;

        $template->render();
    }

}