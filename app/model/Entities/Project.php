<?php

namespace TodoList\Entities;

use TodoList\LogicExceptions\InvalidArgumentException;
use Doctrine\Common\Collections\ArrayCollection;
use Kdyby\Doctrine\Entities\BaseEntity;
use Gedmo\Mapping\Annotation as Gedmo;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Index;
use Nette\Utils\Validators;

/**
 * @Gedmo\Tree(type="nested")
 * @ORM\Entity(repositoryClass="TodoList\Repositories\ProjectRepository")
 * @ORM\Table(
 *     name="project",
 *     indexes={
 *         @Index(name="owner_id_lft_project_id", columns={"owner_id", "lft", "project_id"})
 *     }
 * )
 */
class Project extends BaseEntity
{
    /**
     * @ORM\Id
     * @ORM\Column(name="project_id", type="integer", options={"unsigned":true})
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
     * @ORM\ManyToOne(targetEntity="User")
     * @ORM\JoinColumn(name="owner_id", referencedColumnName="user_id", nullable=false)
     * @var User
     */
    private $owner;

    /**
     * @ORM\Column(name="name", type="string", length=30, nullable=false, unique=false)
     * @var string
     */
    protected $name;

    /**
     * @Gedmo\TreeParent
     * @ORM\ManyToOne(targetEntity="Project")
     * @ORM\JoinColumn(name="parent_id", referencedColumnName="project_id", onDelete="cascade")
     * @var Project
     */
    protected $parent;

    public function __construct($name, User $owner, Project $parent = null)
    {
        $this->setName($name);
        $this->parent = $parent;
        $this->owner = $owner;
    }

    /**
     * @param string $name
     */
    public function setName($name)
    {
        Validators::assert($name, 'string:1..30');
        $this->name = $name;
    }

    /**
     * @param Project $parent
     */
    public function setParent(Project $parent = null)
    {
        if (isset($parent) and $this->owner->getId() !== $parent->owner->getId()) {
            throw new InvalidArgumentException(
                'Argument $parent must have same Owner as its child!'
            );
        }
        $this->parent = $parent;
    }

    /**
     * @return User
     */
    public function getOwner()
    {
        return $this->owner;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return int
     */
    public function getLft()
    {
        return $this->lft;
    }

    /**
     * @return int
     */
    public function getRgt()
    {
        return $this->rgt;
    }

    /**
     * @return int
     */
    public function getLevel()
    {
        return $this->level;
    }

    /**
     * @return int
     */
    public function getRoot()
    {
        return $this->root;
    }

}