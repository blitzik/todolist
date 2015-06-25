<?php

namespace TodoList\Presenters;

use Nextras\Application\UI\SecuredLinksPresenterTrait;
use TodoList\Components\IProjectFormControlFactory;
use TodoList\Components\IProjectListControlFactory;
use TodoList\Components\ProjectFormControl;
use Nette\Application\UI\Presenter;
use Kdyby\Doctrine\EntityManager;
use Nette\Http\UserStorage;
use TodoList\Components\ProjectListControl;
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

    protected function createComponentProjectList()
    {
        $comp = $this->projectListFactory->create();

        $comp->onProjectFormCancelClick[] = [$this, 'processProjectFormCancelClickInProjectList'];
        $comp->onFinishedProjectMovement[] = [$this, 'doAfterProjectMovement'];

        return $comp;
    }

    public function doAfterProjectMovement(ProjectListControl $projectListControl)
    {
        if ($this->isAjax()) {
            $projectListControl->redrawControl('list');
        } else {
            $this->redirect('this');
        }
    }

    protected function createComponentProjectForm()
    {
        $comp = $this->newProjectFormFactory->create();

        $comp->onCancelClick[] = [$this, 'processProjectFormCancelClickInProjectList'];

        return $comp;
    }

    public function processProjectFormCancelClickInProjectList(ProjectFormControl $projectFormControl)
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