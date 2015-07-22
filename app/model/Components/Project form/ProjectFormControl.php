<?php

namespace TodoList\Components;

use Nextras\Application\UI\SecuredLinksControlTrait;
use TodoList\Repositories\ProjectRepository;
use Nette\Forms\Controls\SubmitButton;
use TodoList\Facades\ProjectsFacade;
use Kdyby\Doctrine\EntityManager;
use Nette\Application\UI\Control;
use Nette\Application\UI\Form;
use TodoList\Entities\Project;
use Nette\Security\User;

/**
 * If there is no Project set, a new Root Project will be created.
 *
 */
class ProjectFormControl extends Control
{
    use SecuredLinksControlTrait;

    // Events
    public $onCancelClick;
    public $onNewProject;
    public $onEditProject;

    // --------------------------

    /**
     * @var ProjectRepository
     */
    private $projectRepository;

    /**
     * @var ProjectsFacade
     */
    private $projectsFacade;

    /**
     * @var
     */
    private $entityManager;

    /**
     * @var User
     */
    private $user;

    /**
     * @var bool
     */
    private $visible = false;

    /**
     * @var Project
     */
    private $project;

    public function __construct(
        ProjectsFacade $projectsFacade,
        EntityManager $entityManager,
        User $user
    ) {
        $this->projectsFacade = $projectsFacade;
        $this->entityManager = $entityManager;
        $this->user = $user;

        $this->projectRepository = $this->entityManager->getRepository(Project::class);
    }

    /**
     * @param Project $project
     */
    public function setProject(Project $project = null)
    {
        $this->project = $project;
    }

    public function setAsVisible()
    {
        $this->visible = true;
    }

    public function hideForm()
    {
        $this->visible = false;
    }

    protected function createComponentForm()
    {
        $form = new Form();

        $form->addText('name', 'New Project name:', null, 30)
                ->setRequired('Please enter Project name.')
                ->setAttribute('class', 'input-project-name');

        $form->addSubmit('save', 'Create new project')
                ->setAttribute('class', 'btn btn-primary btn-sm')
                ->setHtmlId('project-form-save-button')
                ->onClick[] = [$this, 'processSaveProject'];

        $form->addSubmit('cancel', 'Cancel')
                ->setValidationScope([])
                ->setAttribute('class', 'btn btn-default btn-sm')
                ->onClick[] = [$this, 'processCancel'];

        $form->addHidden('parent', (isset($this->project) ? $this->project->getId() : null));
        $form->addHidden('isEditForm', false); // default form is for Projects addition

        $form->getElementPrototype()->id = 'new-project-form';
        $form->getElementPrototype()->class = 'ajax';

        $form->addProtection();

        return $form;
    }

    public function processSaveProject(SubmitButton $button)
    {
        $values = $button->getForm()->getValues();
        if ($values['isEditForm'] == true) {
            $this->project->setName($values['name']);
            $this->entityManager->persist($this->project)->flush();

            $this->onEditProject($this, $this->project);

        } else { // root or sub project edition

            $parent = $this->project;
            if ($parent === null) {
                $parent = $this->projectRepository
                               ->findOneBy(['owner' => $this->user->getIdentity(),
                                            'lft' => 1]);
            }

            $project = new Project(
                $values['name'],
                $this->user->getIdentity(),
                $parent
            );

            $this->entityManager->persist($project)->flush();

            $this->onNewProject($this, $project);
        }
    }

    public function processCancel(SubmitButton $button)
    {
        $this->onCancelClick($this);
    }

    /**
     * @secured
     */
    public function handleShowForm($editForm)
    {
        if ($this->presenter->isAjax()) {
            $this->setAsVisible();
            if ($editForm == true) {
                $this['form']['name']->setDefaultValue($this->project->name);
                $this['form']['save']->caption = 'Rename project';
                $this['form']['isEditForm']->value = true;
            }

            $this->redrawControl('projectForm');
        } else {
            $link = $editForm == true ? 'Project:rename' : 'Project:add';
            $params = [];
            if (isset($this->project)) {
                $params['id'] = $this->project->getId();
            }

            $this->presenter->redirect($link, $params);
        }
    }

    public function render()
    {
        $template = $this->getTemplate();
        $template->setFile(__DIR__ . '/template.latte');
        $template->visible = $this->visible;

        $template->render();
    }
}