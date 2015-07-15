<?php

namespace TodoList\Components;

use Kdyby\Doctrine\EntityManager;
use Nette\Application\UI\Control;
use TodoList\Entities\Task;

class TaskLabelControl extends Control
{
    /** @persistent */
    public $backlink = null;

    /**
     * @var ITaskFormControlFactory
     */
    private $taskFormControlFactory;

    /**
     * @var array
     */
    private $task;

    /**
     * @var EntityManager
     */
    private $em;

    public function __construct(
        array $task,
        ITaskFormControlFactory $taskFormControlFactory,
        EntityManager $entityManager
    ) {
        $this->task = $task;
        $this->taskFormControlFactory = $taskFormControlFactory;
        $this->em = $entityManager;
    }

    protected function createComponentTaskForm()
    {
        $comp = $this->taskFormControlFactory->create();
        $comp->setTask(
            $this->em->getReference(Task::class, $this->task['id']),
            true
        );

        $comp->onEditTask[] = function (
            TaskFormControl $taskFormControl,
            Task $task
        ) {
            $this->task['description'] = $task->description;
            $taskFormControl->hideForm();
            $this->redrawControl();
        };

        $comp->onCancelClick[] = function (TaskFormControl $taskFormControl) {
            $taskFormControl->hideForm();
            $this->redrawControl();
        };

        return $comp;
    }

    public function handleEditLabel()
    {
        if ($this->presenter->isAjax()) {
            $this['taskForm']->setFormVisible();
            $this['taskForm']->redrawControl();
        } else {
            $this->presenter->redirect('Task:edit', ['id' => $this->task['id']]);
        }
    }

    public function render()
    {
        $template = $this->getTemplate();
        $template->setFile(__DIR__ . '/task-label.latte');

        $template->task = $this->task;

        $template->render();
    }
}