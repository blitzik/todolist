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

    /**
     * @var array|Project[]
     */
    private $projects;

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
    { // for editing and adding new Sub Projects only
        return new Multiplier(function ($parentID) {

            $comp = $this->projectFormControlFactory->create();
            $comp->setProject($this->em->getReference(Project::class, $parentID));

            $comp->onCancelClick[] = function (ProjectFormControl $comp) {
                $this->onProjectFormCancelClick($comp);
            };

            $comp->onNewProject[] = function (ProjectFormControl $comp, Project $project) {
                $this->onNewProject($comp, $project);
            };

            $comp->onEditProject[] = function (ProjectFormControl $comp, Project $project) {
                $qb = $this->dql();
                $qb->where('p.id = :id')
                   ->groupBy('p.id')
                   ->setParameter('id', $project->getId());

                $this->projects = $qb->getQuery()->getArrayResult();

                $comp->hideForm();
                $this->redrawControl('list');

                $this->onEditProject($comp, $project);
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

    /**
     * @return \Doctrine\ORM\QueryBuilder|\Kdyby\Doctrine\QueryBuilder
     */
    private function dql()
    {
        // Tasks that haven't been finished yet
        $tasksCount = $this->taskRepository->createQueryBuilder('t2')
                           ->select('COUNT(t2.id)')
                           ->where('t2.done = 0 AND t2.deadline >= CURRENT_DATE() AND t2.project = p AND t2.level = 0')
                           ->groupBy('t2.project');

        $projects = $this->projectRepository->createQueryBuilder('p')
                         ->select('p.id as id, p.lft as lft, p.rgt as rgt,
                                                p.level as level, p.root as root, p.name as name')
                         ->addSelect("($tasksCount) as numberOfTasks")
                         ->leftJoin(Task::class, 't WITH t.project = p');

        return $projects;
    }

    public function render()
    {
        $template = $this->getTemplate();
        $template->setFile(__DIR__ . '/projectListTemplate.latte');

        if (empty($this->projects)) {
            $qb = $this->dql($this->user->getIdentity());
            $qb->where('p.owner = :owner AND p.lft > 1')
               ->groupBy('p.lft, p.id')
               ->setParameter('owner', $this->user->getIdentity());

            $this->projects = $qb->getQuery()->getArrayResult();
        }

        $template->projects = $this->projectRepository
                                   ->buildTreeArray($this->projects);

        $template->render();
    }
}