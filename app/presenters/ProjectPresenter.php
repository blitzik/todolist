<?php

namespace TodoList\Presenters;

use Doctrine\ORM\AbstractQuery;
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


    public function actionTasks($id)
    {

    }

    public function renderTasks($id)
    {

    }

    public function actionAdd($id)
    {

    }

    public function renderAdd($id)
    {

    }

    public function actionRename($id)
    {
        $projectName = $this->em
                            ->createQuery('SELECT p.name as name FROM ' .Project::class.' p
                                           WHERE p.id = :id AND p.owner = :owner')
                            ->setParameters(['id' => $id,
                                             'owner' => $this->user->getIdentity()])
                            ->getResult(AbstractQuery::HYDRATE_SINGLE_SCALAR);

        $this['newProjectForm']['form']['name']->setDefaultValue($projectName);
        $this['newProjectForm']['form']['editForm']->value = true;
        $this['newProjectForm']['form']['save']->caption = 'Rename project';
    }

    public function renderRename($id)
    {

    }

    protected function createComponentNewProjectForm()
    {
        $comp = $this->projectFormControlFactory->create();
        $comp->setParentProjectID($this->getParameter('id'));
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

    public function onNewProject(ProjectFormControl $projectFormControl, $id)
    {
        $this->presenter->flashMessage('New Project has been successfully created.', 'bg-success');
        $this->redirect('Project:tasks', ['id' => $id]);
    }

    public function onEditProject(ProjectFormControl $projectFormControl, $id)
    {
        $this->flashMessage('Project has been successfully renamed.', 'bg-success');
        $this->redirect('Project:tasks', ['id' => $id]);
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