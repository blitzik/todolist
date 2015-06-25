<?php

namespace TodoList\Components;

use Nextras\Application\UI\SecuredLinksControlTrait;
use TodoList\Repositories\ProjectRepository;
use Nette\Forms\Controls\SubmitButton;
use Kdyby\Doctrine\EntityManager;
use Nette\Application\UI\Control;
use Nette\Application\UI\Form;
use TodoList\Entities\Project;
use Nette\Security\User;

class ProjectFormControl extends Control
{
    use SecuredLinksControlTrait;

    // Events
    public $onCancelClick;

    // --------------------------

    /**
     * @var ProjectRepository
     */
    private $projectRepository;

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
     * @var int
     */
    private $parentProjectID;

    public function __construct(
        EntityManager $entityManager,
        User $user
    ) {
        $this->entityManager = $entityManager;
        $this->user = $user;

        $this->projectRepository = $this->entityManager->getRepository(Project::class);
    }

    /**
     * @param int $parentProjectID
     */
    public function setParentProjectID($parentProjectID)
    {
        $this->parentProjectID = $parentProjectID;
    }

    /**
     * @return Project
     */
    public function getParentProjectID()
    {
        return $this->parentProjectID;
    }

    public function setAsVisible()
    {
        $this->visible = true;
    }

    public function hideForm()
    {
        $this->visible = false;
    }

    protected function createComponentNewProjectForm()
    {
        $form = new Form();

        $form->addText('name', 'Project name', null, 30)
                ->setRequired('Please enter Project name.')
                ->setAttribute('class', 'input-project-name');

        $form->addSubmit('save', 'Save')
                ->setAttribute('class', 'ajax btn btn-primary btn-sm')
                ->onClick[] = [$this, 'processSaveProject'];

        $form->addSubmit('cancel', 'Cancel')
                ->setValidationScope([])
                ->setAttribute('class', 'ajax btn btn-default btn-sm')
                ->onClick[] = [$this, 'processCancel'];

        $form->addHidden('parent', $this->parentProjectID);

        $form->getElementPrototype()->id = 'new-project-form';

        return $form;
    }

    public function processSaveProject(SubmitButton $button)
    {
        $values = $button->getForm()->getValues();

        $parent = null;
        if (empty($values['parent'])) {
            $parent = $this->projectRepository
                           ->findOneBy(['owner' => $this->user->getIdentity(),
                                        'lft' => 1]);
        } else {
            $parent = $this->projectRepository
                           ->findOneBy(['id' => $values['parent'],
                                        'owner' => $this->user->getIdentity()]
                           );
        }

        if ($parent === null) {
            $this->flashMessage('An Error occurred while adding new Project.', 'bg-danger');
            if ($this->presenter->isAjax()) {
                $this->redrawControl('projectForm');
                return;
            } else {
                $this->redirect('this');
            }
        }

        $project = new Project(
            $values['name'],
            $this->user->getIdentity(),
            $parent
        );

        $this->projectRepository->persistAsLastChildOf($project, $parent);
        $this->entityManager->flush();

        if ($this->presenter->isAjax()) {
            $this->visible = false;
            $this->redrawControl('projectForm');
            $this->presenter->redrawControl('projectList');
        } else {
            $this->presenter->flashMessage('New Project has been successfully created.', 'bg-success');
            $this->presenter->redirect('Project:tasks', ['id' => $project->getId()]);
        }
    }

    public function processCancel(SubmitButton $button)
    {
        $this->onCancelClick($this);
    }

    /**
     * @secured
     */
    public function handleShowForm()
    {
        $this->setAsVisible();
        if ($this->presenter->isAjax()) {
            $this->redrawControl('projectForm');
        } else {
            $this->presenter->redirect('Project:add', ['id' => $this->parentProjectID]);
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