<?php

namespace TodoList\Components;

use Nextras\Application\UI\SecuredLinksControlTrait;
use TodoList\Repositories\ProjectRepository;
use Nette\Application\UI\Multiplier;
use Kdyby\Doctrine\EntityManager;
use Nette\Application\UI\Control;
use TodoList\Entities\Project;
use TodoList\Entities\Task;
use Nette\Security\User;

class ProjectListControl extends Control
{
    use SecuredLinksControlTrait;

    // Events
    public $onProjectFormCancelClick;

    public $onFinishedProjectMovement;

    // -----------------------------------

    /**
     * @var IProjectFormControlFactory
     */
    private $projectFormControlFactory;

    /**
     * @var ProjectRepository
     */
    private $projectRepository;

    /**
     * @var EntityManager
     */
    private $em;

    /**
     * @var User
     */
    private $user;

    public function __construct(
        IProjectFormControlFactory $projectFormControlFactory,
        EntityManager $em,
        User $user
    ) {
        $this->em = $em;
        $this->user = $user;
        $this->projectFormControlFactory = $projectFormControlFactory;

        $this->projectRepository = $this->em->getRepository(Project::class);
    }

    protected function createComponentProjectForm()
    {
        return new Multiplier(function ($parentID) {
            $comp = $this->projectFormControlFactory->create();
            $comp->setParentProjectID($parentID);

            $comp->onCancelClick[] = function ($comp) {
                $this->onProjectFormCancelClick($comp);
            };

            return $comp;
        });
    }

    /**
     * @secured
     */
    public function handleMoveDown($projectID)
    {
        $this->projectRepository
             ->moveDown($this->em->getReference(Project::class, $projectID));

        $this->onFinishedProjectMovement($this);
    }

    /**
     * @secured
     */
    public function handleMoveUp($projectID)
    {
        $this->projectRepository
             ->moveUp($this->em->getReference(Project::class, $projectID));

        $this->onFinishedProjectMovement($this);
    }

    public function render()
    {
        $template = $this->getTemplate();
        $template->setFile(__DIR__ . '/projectListTemplate.latte');

        $a = $this->em->createQuery(
                'SELECT p.id as id, p.lft as lft, p.rgt as rgt,
                 p.level as level, p.root as root, p.name as name,
                 COUNT(t.id) as numberOfTasks FROM ' .Project::class. ' p
                 LEFT JOIN ' .Task::class. ' t WITH t.project = p
                 WHERE p.owner = :owner AND p.lft > 1
                 GROUP BY p.lft, p.id'
             )->setParameters(['owner' => $this->user->getIdentity()->getId()])
              ->getArrayResult();

        $template->projects = $this->em->getRepository(Project::class)->buildTreeArray($a);

        $template->render();
    }
}