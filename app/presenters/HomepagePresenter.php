<?php

namespace TodoList\Presenters;

use TodoList\Components\ITasksListControlFactory;
use Kdyby\Doctrine\QueryBuilder;
use TodoList\Entities\Project;
use TodoList\Entities\Task;

class HomepagePresenter extends SecurityPresenter
{
    /**
     * @var ITasksListControlFactory
     * @inject
     */
    public $tasksListFactory;

    /**
     * @var QueryBuilder
     */
    private $overdueTasksQb;

    public function actionDefault()
    {
        $projectsQb = $this->em->createQueryBuilder()
                         ->select('pr')
                         ->from(Project::class, 'pr')
                         ->where('pr.owner = :pr_owner');

        $tasksQb = $this->em->createQueryBuilder()
                   ->select('t.id, t.lft, t.rgt, t.level, t.root,
                             t.description, t.deadline, t.priority,
                             t.done, p.name as project_name, p.id as project_id')
                   ->from(Task::class, 't')
                   ->join(Project::class, 'p WITH p = t.project')
                   ->where('t.done = 0 AND
                            t.deadline < CURRENT_DATE()')
                   ->andWhere("t.project IN($projectsQb)")
                   ->orderBy('t.root DESC, t.lft')
                   ->setParameter('pr_owner', $this->user->getIdentity());

        $this->overdueTasksQb = $tasksQb;
    }

	public function renderDefault()
	{

	}

    /**
     * @Actions default
     */
    protected function createComponentOverdueTasks()
    {
        $comp = $this->tasksListFactory->create($this->overdueTasksQb);

        return $comp;
    }

}