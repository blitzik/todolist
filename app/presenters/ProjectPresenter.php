<?php

namespace TodoList\Presenters;

use TodoList\RuntimeExceptions\TaskNotFoundException;
use TodoList\Components\IProjectFormControlFactory;
use TodoList\Components\IFilterBarControlFactory;
use TodoList\Components\ITasksListControlFactory;
use TodoList\Components\ITaskFormControlFactory;
use TodoList\Components\ProjectFormControl;
use TodoList\Factories\RemoveFormFactory;
use TodoList\Components\TaskFormControl;
use Nette\Forms\Controls\SubmitButton;
use TodoList\Facades\ProjectsFacade;
use TodoList\Facades\TasksFacade;
use Kdyby\Doctrine\QueryBuilder;
use TodoList\Entities\Project;
use Nette\Application\UI\Form;
use TodoList\Entities\Task;

class ProjectPresenter extends SecurityPresenter
{
    /**
     * @var IProjectFormControlFactory
     * @inject
     */
    public $projectFormControlFactory;

    /**
     * @var RemoveFormFactory
     * @inject
     */
    public $removeFormFactory;

    /**
     * @var ITaskFormControlFactory
     * @inject
     */
    public $taskFormFactory;

    /**
     * @var ITasksListControlFactory
     * @inject
     */
    public $tasksListFactory;

    /**
     * @var IFilterBarControlFactory
     * @inject
     */
    public $filterBarFactory;

    /**
     * @var ProjectsFacade
     * @inject
     */
    public $projectsFacade;

    /**
     * @var TasksFacade
     * @inject
     */
    public $tasksFacade;

    /**
     * @var Project
     */
    private $project;

    /**
     * @var QueryBuilder
     */
    private $tasksQb;

    protected function createComponentFilterBar()
    {
        $comp = $this->filterBarFactory->create();

        return $comp;
    }

    public function actionTasks($id)
    {
        $this->project = $this->projectsFacade->getProject($id);
        if ($this->project === null) {
            $this->flashMessage('Requested Project does not exist.', 'bg-warning');
            $this->redirect('Homepage:default');
        }

        $this->tasksQb = $this->em->createQueryBuilder()
            ->select('t.id, t.lft, t.rgt, t.level, t.root,
                      t.description, t.deadline, t.priority,
                      t.done, p.name as project_name, p.id as project_id')
            ->from(Task::class, 't')
            ->join(Project::class, 'p WITH p = t.project')
            ->where('t.done = 0')
            ->andWhere('t.deadline >= CURRENT_DATE()')
            ->andWhere('t.project = :project')
            ->orderBy('t.root DESC, t.lft')
            ->setParameter('project', $this->project);
    }

    public function renderTasks($id)
    {
        $this->template->project = $this->project;
    }

    /**
     * @Actions tasks
     * @return \TodoList\Components\TasksListControl
     */
    protected function createComponentTasksList()
    {
        $comp = $this->tasksListFactory->create($this->tasksQb);

        return $comp;
    }

    /*
     * ADD Project
     */

    public function actionAdd($id)
    {
        if ($id != null) {
            $this->project = $this->projectsFacade->getProject($id);
            if ($this->project === null) {
                $this->flashMessage('Requested Project does not exist.', 'bg-warning');
                $this->redirect('Homepage:default');
            }
        }
    }

    public function renderAdd($id)
    {
        $this->template->project = $this->project;
    }

    /*
     * RENAME - PROJECT
     */

    public function actionRename($id)
    {
        $this->project = $this->projectsFacade->getProject($id);
        if ($this->project === null) {
            $this->flashMessage('Requested Project does not exist.', 'bg-warning');
            $this->redirect('Homepage:default');
        }

        $this['projectForm']['form']['name']->setDefaultValue($this->project->name);
        $this['projectForm']['form']['isEditForm']->value = true;
        $this['projectForm']['form']['save']->caption = 'Rename project';
    }

    public function renderRename($id)
    {
        $this->template->project = $this->project;
    }

    /**
     * @Actions add, rename
     */
    protected function createComponentProjectForm()
    {
        $comp = $this->projectFormControlFactory->create();
        $comp->setProject($this->project);
        $comp->setAsVisible();

        $comp->onCancelClick[] = [$this, 'processProjectFormCancelClick'];
        $comp->onNewProject[] = [$this, 'onNewProject'];
        $comp->onEditProject[] = [$this, 'onEditProject'];

        return $comp;
    }

    public function processProjectFormCancelClick(ProjectFormControl $projectFormControl)
    {
        $this->redirect('Project:tasks', ['id' => $projectFormControl->getParentProjectID()]);
    }

    public function onNewProject(ProjectFormControl $projectFormControl, Project $project)
    {
        $this->presenter->flashMessage('New Project has been successfully created.', 'bg-success');
        $this->redirect('Project:tasks', ['id' => $project->getId()]);
    }

    public function onEditProject(ProjectFormControl $projectFormControl, Project $project)
    {
        $this->flashMessage('Project has been successfully renamed.', 'bg-success');
        $this->redirect('Project:tasks', ['id' => $project->getId()]);
    }

    /*
     * REMOVE - PROJECT
     */

    public function actionRemove($id)
    {
        $this->project = $this->projectsFacade->getProject($id);

        if ($this->project === null) {
            $this->flashMessage('The Project you are looking for is no longer available.', 'bg-warning');
            $this->redirect('Homepage:default');
        }
    }

    public function renderRemove($id)
    {
        $r = $this->em->getRepository(Project::class);

        $this->template->project = $this->project;
        $this->template->projectChildren = $r->getChildren($this->project);
    }

    /**
     * @Actions remove
     */
    protected function createComponentRemoveProjectForm()
    {
        $form = $this->removeFormFactory->create();
        $form->addProtection();

        $form->addText('test', 'Type "delete" without quotes')
                //->setDefaultValue('delete')
                ->addRule(Form::FILLED, 'In order to REMOVE Project you have to type test string.')
                ->addRule(Form::EQUAL, 'Please type the right text string.', 'delete');

        $form['remove']->onClick[] = [$this, 'processRemoving'];

        $form['cancel']->setValidationScope([])
                       ->onClick[] = [$this, 'processCanceling'];

        return $form;
    }

    public function processRemoving(SubmitButton $button)
    {
        $this->em->transactional(function () {
            $this->em->remove($this->project);
        });

        $this->flashMessage('Project has been successfully removed.', 'bg-success');
        $this->redirect('Homepage:default');
    }

    public function processCanceling(SubmitButton $button)
    {
        $this->redirect('Project:tasks', ['id' => $this->project->id]);
    }

    /**
     * @param $id Project ID
     * @param $parentTaskID
     */
    public function actionAddTask($id, $parentTaskID)
    {
        $this->project = $this->projectsFacade->getProject($id);

        if ($this->project === null) {
            $this->flashMessage('Requested Project does not exist.', 'bg-warning');
            $this->redirect('Homepage:default');
        }

        if (isset($parentTaskID)) { // Add sub Task
            try {
                $parentTask = $this->tasksFacade->getTask($parentTaskID);
                $this['taskForm']->setTask($parentTask, false);

            } catch (TaskNotFoundException $e) {
                $this->flashMessage('Requested Task does not exist.', 'bg-warning');
                $this->redirect('Project:tasks', ['id' => $id]);
            }
        } else { // New root Task
            $this['taskForm']->setProject($this->project);
        }

    }

    public function renderAddTask($id, $parentTaskID)
    {
        $this->template->project = $this->project;
    }

    /**
     * @Actions addTask
     */
    protected function createComponentTaskForm()
    {
        $comp = $this->taskFormFactory->create();
        $comp->hideCancelButton();
        $comp->setFormVisible();

        $comp->onNewRootTask[] = [$this, 'onNewRootTask'];
        $comp->onNewSubTask[] = [$this, 'onNewSubTask'];

        return $comp;
    }

    public function onNewRootTask(TaskFormControl $formControl, Task $task)
    {
        $this->flashMessage('New Root Task has been successfully created.', 'bg-success');
        $this->redirect('Project:tasks', ['id' => $task->project->getId()]);
    }

    public function onNewSubTask(TaskFormControl $formControl, Task $task)
    {
        $this->flashMessage('New Root Task has been successfully created.', 'bg-success');
        $this->redirect('Project:tasks', ['id' => $task->project->getId()]);
    }

}