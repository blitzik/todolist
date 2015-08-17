<?php

namespace TodoList\Components;

use Kdyby\Doctrine\EntityManager;
use Nette\Application\UI\Control;
use TodoList\Entities\Task;
use Tracy\Debugger;

class TaskLabelControl extends Control
{
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

        $comp->onNewTask[] = function (
            TaskFormControl $taskFormControl,
            Task $task
        ) {
            $this->presenter->flashMessage('New Task has been successfully created.', 'bg-success');
            $this->redirect('this#task=' . $task->getId());
        };

        $comp->onEditTask[] = function (
            TaskFormControl $taskFormControl,
            Task $task,
            $wasOverdue
        ) {
            // was Task overdue before change?
            if ($wasOverdue and $task->deadline >= (new \DateTime('now'))) {
                $this->redirect('this');
            }
            $this->task['description'] = $task->description;
            $this->task['deadline'] = $task->deadline;
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

    public function handleAddSubTask()
    {
        if ($this->presenter->isAjax()) {
            $this['taskForm']->setFormVisible();
            $this['taskForm']->setFormAsEditable(false);
            $this['taskForm']->redrawControl();
        } else {
            $this->presenter->redirect(
                'Project:addTask',
                ['id' => $this->task['project_id'],
                 'parentTaskID' => $this->task['id']]
            );
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