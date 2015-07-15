<?php

namespace TodoList\Components;

use Nextras\Application\UI\SecuredLinksControlTrait;
use TodoList\Facades\ProjectsFacade;
use TodoList\Repositories\ProjectRepository;
use Nette\Forms\Controls\SubmitButton;
use Kdyby\Doctrine\EntityManager;
use Nette\Application\UI\Control;
use Doctrine\ORM\AbstractQuery;
use Nette\Application\UI\Form;
use TodoList\Entities\Project;
use Nette\Security\User;
use Tracy\Debugger;

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
     * @var int
     */
    private $parentProjectID;

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

    protected function createComponentForm()
    {
        $form = new Form();

        $form->addText('name', 'New Project name:', null, 30)
                ->setRequired('Please enter Project name.')
                ->setAttribute('class', 'input-project-name');

        $form->addSubmit('save', 'Create new project')
                ->setAttribute('class', 'ajax btn btn-primary btn-sm')
                ->setHtmlId('project-form-save-button')
                ->onClick[] = [$this, 'processSaveProject'];

        $form->addSubmit('cancel', 'Cancel')
                ->setValidationScope([])
                ->setAttribute('class', 'ajax btn btn-default btn-sm')
                ->onClick[] = [$this, 'processCancel'];

        $form->addHidden('parent', $this->parentProjectID);
        $form->addHidden('editForm', false);

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
                                        'lft' => 1]
                           );
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

        if ($values['editForm'] == false) {
            $project = new Project(
                $values['name'],
                $this->user->getIdentity(),
                $parent
            );

            $this->projectRepository->persistAsLastChildOf($project, $parent);
            $this->entityManager->flush();

            $this->onNewProject($this, $project->getId());

        } else {
            $parent->setName($values['name']);
            $this->entityManager->persist($parent)->flush();

            $this->onEditProject($this, $parent->getId());
        }
    }

    public function processCancel(SubmitButton $button)
    {
        $this->onCancelClick($this);
    }

    /**
     * @secured
     */
    public function handleShowForm($edit = false)
    {
        if ($this->presenter->isAjax()) {
            $this->setAsVisible();

            if ($edit == true) {
                $projectName = $this->projectsFacade
                                    ->getProjectName(
                                        $this->parentProjectID,
                                        $this->user->getIdentity()
                                    );

                $this['form']['name']->setDefaultValue($projectName);
                $this['form']['editForm']->value = true;
                $this['form']['save']->caption = 'Rename project';
            }
            $this->redrawControl('projectForm');
        } else {
            $link = $edit == true ? 'Project:rename' : 'Project:add';
            $this->presenter->redirect($link, ['id' => $this->parentProjectID]);
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