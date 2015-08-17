<?php

namespace TodoList\Presenters;

use TodoList\RuntimeExceptions\TaskNotFoundException;
use TodoList\Components\ITaskFormControlFactory;
use TodoList\Components\TaskFormControl;
use TodoList\Facades\TasksFacade;
use TodoList\Entities\Project;
use TodoList\Entities\Task;

class TaskPresenter extends SecurityPresenter
{
    /**
     * @var ITaskFormControlFactory
     * @inject
     */
    public $taskFormFactory;

    /**
     * @var TasksFacade
     * @inject
     */
    public $taskFacade;

    /**
     * @var Project
     */
    public $project;

    /**
     * @var Task
     */
    private $task;

    private function getTaskByID($id)
    {
        try {
            return $this->taskFacade->getTask($id);

        } catch (TaskNotFoundException $e) {
            $this->flashMessage('Requested Task does not exist.', 'bg-warning');
            $this->redirect('Homepage:default');
        }
    }

    public function actionEdit($id)
    {
        $this->task = $this->getTaskByID($id);
        $this['taskForm']->setTask($this->task, true);

        $this['taskForm']['form']['description']->setDefaultValue($this->task->description);
        $this['taskForm']['form']['date']->setDefaultValue(
            $this->task
                 ->deadline
                 ->format('d.m.Y')
        );
    }

    public function renderEdit($id)
    {

    }

    /**
     * @Actions edit
     */
    protected function createComponentTaskForm()
    {
        $comp = $this->taskFormFactory->create();
        $comp->hideCancelButton();
        $comp->setFormVisible();

        $comp->onEditTask[] = [$this, 'onEditTask'];

        return $comp;
    }

    public function onEditTask(TaskFormControl $formControl, Task $task, $wasOverdue)
    {
        $this->flashMessage('Your Task has been successfully edited.', 'bg-success');
        if ($task->deadline < (new \DateTime('now'))) {
            $this->redirect('Homepage:default');
        }

        $this->redirect('Project:tasks', ['id' => $task->project->getId()]);
    }
}