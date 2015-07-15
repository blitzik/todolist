<?php

namespace TodoList\Presenters;

use Nextras\Application\UI\SecuredLinksPresenterTrait;
use TodoList\Components\IProjectFormControlFactory;
use TodoList\Components\IProjectListControlFactory;
use TodoList\Components\ProjectListControl;
use TodoList\Components\ProjectFormControl;
use Nette\Application\UI\Presenter;
use Kdyby\Doctrine\EntityManager;
use Nette\Http\UserStorage;
use Tracy\Debugger;

class SecurityPresenter extends Presenter
{
    use SecuredLinksPresenterTrait;

    /**
     * @var IProjectFormControlFactory
     * @inject
     */
    public $newProjectFormFactory;

    /**
     * @var IProjectListControlFactory
     * @inject
     */
    public $projectListFactory;

    /**
     * @persistent
     */
    public $newProjectForm;

    /**
     * @var EntityManager
     * @inject
     */
    public $em;

    protected function startup()
    {
        parent::startup();

        if (!$this->user->isLoggedIn()) {
            if ($this->user->getLogoutReason() === UserStorage::INACTIVITY) {
                $this->flashMessage(
                    'You have been logged out due to your inactivity.
                     Please sign in again.'
                );
                $this->redirect('Sign:default');
            }

            $this->flashMessage('You have to be logged in to use Todo-list app.', 'bg-warning');
            $this->redirect('Sign:default');
        }
    }

    protected function createComponent($name)
    {
        $ucname = ucfirst($name);
        $method = 'createComponent' . $ucname;

        $presenterReflection = $this->getReflection();
        if ($presenterReflection->hasMethod($method)) {
            $methodReflection = $presenterReflection->getMethod($method);
            $this->checkRequirements($methodReflection);

            if ($methodReflection->hasAnnotation('Actions')) {
                $actions = explode(',', $methodReflection->getAnnotation('Actions'));
                foreach ($actions as $key => $action) {
                    $actions[$key] = trim($action);
                }

                if (!empty($actions) and !in_array($this->getAction(), $actions)) {
                    throw new \Nette\Application\ForbiddenRequestException("Creation of component '$name' is forbidden for action '$this->action'.");
                }
            }
        }

        return parent::createComponent($name);
    }


    protected function createComponentProjectList()
    {
        $comp = $this->projectListFactory->create();

        $comp->onProjectFormCancelClick[] = [$this, 'processProjectRootCancelClick'];
        $comp->onFinishedProjectMovement[] = [$this, 'onProjectMovement'];
        $comp->onNewProject[] = [$this, 'onNewProject'];
        $comp->onEditProject[] = [$this, 'onEditProject'];

        return $comp;
    }

    public function onProjectMovement(ProjectListControl $projectListControl)
    {
        if ($this->isAjax()) {
            $this->redrawControl('projectList');
        } else {
            $this->redirect('this');
        }
    }

    public function onEditProject(ProjectFormControl $projectFormControl, $id)
    {
        if (!$this->isAjax()) {
            $this->redirect('this');
        }
    }

    /**
     * @return ProjectFormControl
     */
    protected function createComponentRootProjectForm() // new Root project (not sub project)
    {
        $comp = $this->newProjectFormFactory->create();

        $comp->onCancelClick[] = [$this, 'processProjectRootCancelClick'];
        $comp->onNewProject[] = [$this, 'onNewProject'];

        return $comp;
    }

    public function onNewProject(ProjectFormControl $projectFormControl, $id)
    {
        if ($this->isAjax()) {
            $projectFormControl->hideForm();
            $projectFormControl->redrawControl('projectForm');
            $this->redrawControl('projectList');
        } else {
            $this->redirect('this');
        }
    }

    public function processProjectRootCancelClick(ProjectFormControl $projectFormControl)
    {
        if ($this->isAjax()) {
            $projectFormControl->hideForm();
            $projectFormControl->redrawControl('projectForm');
        } else {
            $this->redirect('this');
        }
    }

    public function handleLogout()
    {
        $this->user->logout();
        $this->redirect('Sign:default');
    }

}