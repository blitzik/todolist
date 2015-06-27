<?php

namespace TodoList\Components;

use Nextras\Application\UI\SecuredLinksControlTrait;
use TodoList\Repositories\ProjectRepository;
use TodoList\Repositories\TaskRepository;
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
    public $onNewProject;
    public $onEditProject;
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
     * @var TaskRepository
     */
    private $taskRepository;

    /**
     * @var User
     */
    private $user;

    /**
     * @var EntityManager
     */
    private $em;

    public function __construct(
        IProjectFormControlFactory $projectFormControlFactory,
        EntityManager $em,
        User $user
    ) {
        $this->em = $em;
        $this->user = $user;
        $this->projectFormControlFactory = $projectFormControlFactory;

        $this->projectRepository = $this->em->getRepository(Project::class);
        $this->taskRepository = $this->em->getRepository(Task::class);
    }

    protected function createComponentProjectForm()
    {
        return new Multiplier(function ($parentID) {
            $comp = $this->projectFormControlFactory->create();
            $comp->setParentProjectID($parentID);

            $comp->onCancelClick[] = function ($comp) {
                $this->onProjectFormCancelClick($comp);
            };

            $comp->onNewProject[] = function ($comp) use ($parentID) {
                $this->onNewProject($comp, $parentID);
            };

            $comp->onEditProject[] = function ($comp) use ($parentID) {
                $this->onEditProject($comp, $parentID);
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

        // Tasks that haven't been finished yet
        $tasksCount = $this->taskRepository->createQueryBuilder('t2')
                           ->select('COUNT(t2.id)')
                           ->where('t2.project = p AND t2.done = 0')
                           ->groupBy('t2.project');

        $projects = $this->projectRepository->createQueryBuilder('p')
                         ->select('p.id as id, p.lft as lft, p.rgt as rgt,
                                   p.level as level, p.root as root, p.name as name')
                         ->addSelect("($tasksCount) as numberOfTasks")
                         ->leftJoin(Task::class, 't WITH t.project = p')
                         ->where('p.owner = :owner AND p.lft > 1')
                         ->groupBy('p.lft, p.id')
                         ->setParameter('owner', $this->user->getIdentity()->getId());

        $result = $projects->getQuery()->getArrayResult();

        $template->projects = $this->projectRepository->buildTreeArray($result);

        $template->render();
    }
}