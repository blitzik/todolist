<?php

namespace TodoList\Presenters;

use TodoList\Components\IProjectFormControlFactory;
use TodoList\Repositories\ProjectRepository;
use TodoList\Components\ProjectFormControl;
use TodoList\Factories\RemoveFormFactory;
use Nette\Forms\Controls\SubmitButton;
use TodoList\Entities\Project;
use Nette\Application\UI\Form;

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
     * @var ProjectRepository
     */
    private $projectRepository;

    /**
     * @var Project
     */
    private $project;
    

    protected function startup()
    {
        parent::startup();
        $this->projectRepository = $this->em->getRepository(Project::class);
    }


    public function actionTasks()
    {

    }

    public function renderTasks()
    {

    }

    public function actionAdd($id)
    {

    }

    public function renderAdd($id)
    {

    }

    protected function createComponentNewProjectForm()
    {
        $comp = $this->projectFormControlFactory->create();
        $comp->setParentProjectID($this->getParameter('id'));
        $comp->setAsVisible();

        $comp->onCancelClick[] = [$this, 'processProjectFormCancelClick'];

        return $comp;
    }

    public function processProjectFormCancelClick(ProjectFormControl $projectFormControl)
    {
        $this->redirect('Project:tasks', ['id' => $projectFormControl->getParentProjectID()]);
    }

    public function actionRemove($id)
    {
        $this->project = $this->projectRepository
                              ->findOneBy(
                                  ['owner' => $this->user->getIdentity(),
                                   'id' => $id]
                              );

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

    protected function createComponentRemoveProjectForm()
    {
        $form = $this->removeFormFactory->create();

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
}