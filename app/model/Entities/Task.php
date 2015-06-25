<?php

namespace TodoList\Entities;

use Kdyby\Doctrine\Entities\BaseEntity;
use Gedmo\Mapping\Annotation as Gedmo;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping as ORM;
use Nette\Utils\Validators;

/**
 * @Gedmo\Tree(type="nested")
 * @ORM\Entity(repositoryClass="TodoList\Repositories\TaskRepository")
 * @ORM\Table(name="task")
 */
class Task extends BaseEntity
{
    /**
     * @ORM\Id
     * @ORM\Column(name="task_id", type="integer", options={"unsigned":true})
     * @ORM\GeneratedValue
     * @var int
     */
    private $id;

    /**
     * @Gedmo\TreeLeft
     * @ORM\Column(name="lft", type="integer", nullable=false, unique=false)
     * @var int
     */
    private $lft;

    /**
     * @Gedmo\TreeRight
     * @ORM\Column(name="rgt", type="integer", nullable=false, unique=false)
     * @var int
     */
    private $rgt;

    /**
     * @Gedmo\TreeLevel
     * @ORM\Column(name="level", type="integer", nullable=false, unique=false)
     * @var int
     */
    private $level;

    /**
     * @Gedmo\TreeRoot
     * @ORM\Column(name="root", type="integer", nullable=false, unique=false)
     * @var int
     */
    private $root;

    /**
     * @ORM\ManyToOne(targetEntity="Project")
     * @ORM\JoinColumn(name="project_id", referencedColumnName="project_id", nullable=false, onDelete="cascade")
     * @var Project
     */
     protected $project;

    /**
     * @ORM\Column(name="`description`", type="string", length=1000, nullable=false, unique=false)
     * @var string
     */
    protected $description;

    /**
     * @Gedmo\TreeParent
     * @ORM\ManyToOne(targetEntity="Task")
     * @ORM\JoinColumn(name="parent_id", referencedColumnName="task_id", onDelete="cascade")
     * @var Task
     */
    protected $parent;

    /**
     * @ORM\Column(name="deadline", type="datetime", nullable=true, unique=false)
     * @var \DateTime|null
     */
    protected $deadline;
    
    /**
     * @ORM\Column(name="priority", type="smallint", nullable=true, unique=false)
     * @var int
     */
    protected $priority;

    /**
     * @ORM\Column(name="done", type="boolean", nullable=false, unique=false)
     * @var boolean
     */
    protected $done;


    public function __construct(
        $description,
        Project $project,
        \DateTime $deadline = null,
        Task $parent = null
    ) {
        $this->setDescription($description);
        $this->project = $project;
        $this->deadline = $deadline;
        $this->parent = $parent;
    }

    /**
     * @param string $description
     */
    public function setDescription($description)
    {
        Validators::assert($description, 'string:1..1000');
        $this->description = $description;
    }

    /**
     * @param Task $parent
     */
    public function setParent(Task $parent)
    {
        $this->parent = $parent;
    }

    /**
     * @param \DateTime|null $deadline
     */
    public function setDeadline($deadline)
    {
        $this->deadline = $deadline;
    }

    /**
     * @param int $priority
     */
    public function setPriority($priority)
    {
        Validators::assert($priority, 'numerictint:0..');
        $this->priority = $priority;
    }



    
}