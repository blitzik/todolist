<?php

namespace TodoList\Components;

use Nette\Forms\Controls\SubmitButton;
use Kdyby\Doctrine\EntityManager;
use Nette\Application\UI\Control;
use Nette\Application\UI\Form;
use TodoList\Entities\Project;
use Nette\Utils\Validators;
use TodoList\Entities\Task;

class TaskFormControl extends Control
{
    public $onNewRootTask;
    public $onNewSubTask;
    public $onEditTask;
    public $onCancelClick;

    /**
     * @var EntityManager
     */
    private $em;

    /**
     * @var Task
     */
    private $task;

    /**
     * @var Project
     */
    private $project;

    /**
     * @var bool
     */
    private $isVisible = false;
    private $isEditForm;
    private $isCancelButtonVisible = true;

    public function __construct(
        EntityManager $entityManager
    ) {
        $this->em = $entityManager;
    }

    /**
     * @param Task $task
     * @param $isForEdit
     */
    public function setTask(Task $task, $isForEdit)
    {
        $this->task = $task;

        Validators::assert($isForEdit, 'bool');
        $this->isEditForm = $isForEdit;
    }

    /**
     * @param Project $project
     */
    public function setProject(Project $project)
    {
        $this->project = $project;
    }

    /**
     * @return Project
     */
    public function getProject()
    {
        if (isset($this->task)) {
            return $this->task->project;
        }

        if (isset($this->project)) {
            return $this->project;
        }
    }

    public function setFormVisible()
    {
        $this->isVisible = true;
    }

    public function hideForm()
    {
        $this->isVisible = false;
    }

    public function hideCancelButton()
    {
        $this->isCancelButtonVisible = false;
    }

    protected function createComponentForm()
    {
        $form = new Form();

        $form->addTextArea('description', 'Description:', 100)
             ->setRequired();

        $form->addText('date', 'Date:', 10, 10)
             ->setRequired()
             ->setAttribute('class', 'datepicker form-control');

        $form->addSubmit('save', 'Save')
             ->onClick[] = [$this, 'processSave'];

        if ($this->isCancelButtonVisible) {
            $form->addSubmit('cancel', 'Cancel')
                 ->setValidationScope([])
                 ->onClick[] = [$this, 'processCancel'];
        }

        $form->getElementPrototype()->class = "ajax";

        return $form;
    }

    /*

        Vytvoření nového úkolu

        Root: [Description, Deadline], Project

        Sub: [Description, Deadline], Parent Task

        Editace úkolu

        [Description, Deadline], Task


     */

    public function processSave(SubmitButton $button)
    {
        $values = $button->getForm()->getValues();

        // If $isEditForm is set, there is $this->task as well
        if (isset($this->isEditForm)) {
            // If $isEditForm is TRUE, it means that $this->task needs to be updated
            if ($this->isEditForm === true) {
                $this->task->setDescription($values['description']);
                $this->task->setDeadline(new \DateTime($values['date']));
                $this->em->persist($this->task)->flush();

                $this->onEditTask($this, $this->task);

            } else { // otherwise $this->task is Parent for NEW Sub TASK
                $task = new Task(
                    $values['description'],
                    $this->em->getReference(Project::class, $this->task->project->getId()),
                    new \DateTime($values['date']),
                    $this->task
                );
                $this->em->persist($task)->flush();

                $this->onNewSubTask($this, $task);
            }
        } else { // If $isEditForm is NOT set, there is NO $this->task
            // New Root Task creation
            if (isset($this->project)) {
                $task = new Task(
                    $values['description'],
                    $this->project,
                    new \DateTime($values['date'])
                );
                $this->em->persist($task)->flush();

                $this->onNewRootTask($this, $task);
            }
        }
    }

    public function processCancel(SubmitButton $button)
    {
        $this->onCancelClick($this);
    }

    public function render()
    {
        $template = $this->getTemplate();
        $template->setFile(__DIR__ . '/task-form.latte');

        $template->isVisible = $this->isVisible;
        $template->task = $this->task;
        $template->isEditForm = $this->isEditForm;
        $template->isCancelButtonVisible = $this->isCancelButtonVisible;

        $template->render();
    }
}