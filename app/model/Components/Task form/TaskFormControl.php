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

    public function processSave(SubmitButton $button)
    {
        $form = $button->getForm();
        $values = $form->getValues();
        $deadlineDate = new \DateTime($values['date']);

        if (isset($this->task)) {
            $currentDate = new \DateTime(date('Y-m-d'));

            if ($this->isEditForm === true) {
                $lowestDeadline = $this->findLowestDeadlineValue($this->task);
                $highestDeadline = $this->findHighestDeadlineValue($this->task);

                if ($highestDeadline === null) {
                    if ($deadlineDate < $lowestDeadline) {
                        $form->addError('The lowest possible Deadline can be: ' . $lowestDeadline->format('d.m.Y'));
                        return;
                    }
                } else {
                    if ($deadlineDate < $lowestDeadline or $deadlineDate > $highestDeadline) {
                        $form->addError(
                            'The Task deadline has to be between: ' . $lowestDeadline->format('d.m.Y') . ' - ' .
                            $highestDeadline->format('d.m.Y')
                        );
                        return;
                    }
                }

                $wasOverdue = false;
                if ($this->task->deadline < $currentDate) {
                    $wasOverdue = true;
                }

                $this->task->setDescription($values['description']);
                $this->task->setDeadline($deadlineDate);
                $this->em->persist($this->task)->flush();

                $this->onEditTask($this, $this->task, $wasOverdue);

            } else { // new Sub Task creation

                $lastChildDeadline = $this->findLastChildDeadlineValue($this->task);
                if ($deadlineDate < $currentDate  or $deadlineDate > $lastChildDeadline) {
                    $form->addError(
                        'The Task Deadline can be between: ' . ($currentDate->format('d.m.Y')) . ' - ' .
                        $lastChildDeadline->format('d.m.Y')
                    );
                    return;
                }

                $task = new Task(
                    $values['description'],
                    $this->em->getReference(Project::class, $this->task->project->getId()),
                    $deadlineDate,
                    $this->task
                );
                $this->em->persist($task)->flush();

                $this->onNewSubTask($this, $task);
            }
        } else { // New Root Task creation
            if (isset($this->project)) {
                $task = new Task(
                    $values['description'],
                    $this->project,
                    $deadlineDate
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

    /**
     * If method returns NULL, it means that given Task is direct child
     * and does not have "upper sibling".
     *
     * @param Task $task
     * @return \DateTime|null
     */
    private function getUpperSiblingDeadline(Task $task)
    {
        $usd = $asd = $this->em
            ->createQuery(
                'SELECT t.deadline FROM ' . Task::class . ' t
                 WHERE t.root = :root AND t.rgt = :rgt'
            )->setParameters(['root' => $task->root,
                              'rgt' => ($task->lft - 1)])
             ->getArrayResult();

        return empty($usd[0]) ? null : $usd[0]['deadline'];
    }

    /**
     * @param Task $task
     * @return \DateTime|null
     */
    private function getDirectChildDeadline(Task $task)
    {
        $dcd = $this->em
            ->createQuery(
                'SELECT t.deadline FROM ' . Task::class . ' t
                 WHERE t.root = :root AND t.lft = :lft'
            )->setParameters(['root' => $task->root,
                              'lft' => ($task->lft + 1)])
             ->getArrayResult();

        return empty($dcd[0]) ? null : $dcd[0]['deadline'];
    }

    /**
     * @param Task $task
     * @return \DateTime|null
     */
    private function getLowerSiblingDeadline(Task $task)
    {
        $lsd = $this->em
            ->createQuery(
                'SELECT t.deadline FROM ' . Task::class . ' t
                 WHERE t.root = :root AND t.lft = :lft'
            )->setParameters(['root' => $task->root,
                              'lft' => ($task->rgt + 1)])
             ->getArrayResult();

        return empty($lsd[0]) ? null : $lsd[0]['deadline'];
    }

    /**
     * If method return NULL, Task has NO children.
     *
     * @param Task $task
     * @return \DateTime|null
     */
    private function getLastChildDeadline(Task $task)
    {
        $lcd = $this->em->createQuery(
            'SELECT t.deadline FROM ' . Task::class . ' t
             WHERE t.root = :root AND t.rgt = :rgt'
        )->setParameters(['root' => $this->task->root,
                          'rgt' => $this->task->rgt - 1])
         ->getArrayResult();

        return empty($lcd[0]) ? null : $lcd[0]['deadline'];
    }

    /**
     * @param Task $task
     * @return \DateTime
     */
    private function findLowestDeadlineValue(Task $task)
    {
        $currentDate = new \DateTime(date('Y-m-d'));
        $lowestDateValue = null;

        $directChildDeadline = $this->getDirectChildDeadline($task);
        $lowerSiblingDeadline = $this->getLowerSiblingDeadline($task);

        if ($directChildDeadline === null) { // Task does not have any children
            if ($lowerSiblingDeadline === null) { // neither lower sibling
                $lowestDateValue = $currentDate;
            } else {
                $lowestDateValue = $lowerSiblingDeadline;
            }
        } else { // Task has child(ren)

            if ($lowerSiblingDeadline === null) { // Task has child but no lower sibling
                $lowestDateValue = $directChildDeadline;
            } else { // Task have both child and lower sibling

                if ($directChildDeadline < $lowerSiblingDeadline) {
                    $lowestDateValue = $lowerSiblingDeadline;
                } else {
                    $lowestDateValue = $directChildDeadline;
                }
            }
        }

        /*
         For overdue Tasks, User could not be able to select deadline value lower than
         current date for Root Task and NO deadline value for subTasks.
         (in form template, date picker minDate and maxDate are crossed)
        */
        if ($lowestDateValue < $currentDate) {
            $lowestDateValue = $currentDate;
        }

        return $lowestDateValue;
    }

    /**
     * @param Task $task
     * @return \DateTime|null
     */
    private function findHighestDeadlineValue(Task $task)
    {
        $highestDeadlineValue = null;
        $upperSiblingDeadline = $this->getUpperSiblingDeadline($task);

        if ($task->parent !== null) { // Sub task
            if ($upperSiblingDeadline === null) { // no upper sibling => Task is direct child of Root Task
                $highestDeadlineValue = $task->parent->deadline;
            } else {
                $highestDeadlineValue = $upperSiblingDeadline;
            }
        } // There is no upper sibling for Root Task => method returns NULL

        return $highestDeadlineValue;
    }

    /**
     * @param Task $task
     * @return \DateTime
     */
    private function findLastChildDeadlineValue(Task $task)
    {
        $lastChildDeadlineValue = $this->getLastChildDeadline($task);
        if ($lastChildDeadlineValue === null) { // Task has no children and thus no last one
            $lastChildDeadlineValue = $task->deadline;
        }

        return $lastChildDeadlineValue;
    }

    public function render()
    {
        $template = $this->getTemplate();
        $template->setFile(__DIR__ . '/task-form.latte');

        if ($this->isVisible and isset($this->task)) { // isset($this->task) -> Edit and Sub task creation
            if ($this->isEditForm === true) {
                $template->lowestDeadline = $this->findLowestDeadlineValue($this->task);
                $template->highestDeadline = $this->findHighestDeadlineValue($this->task);
            } else {
                $template->lastChildDeadline = $this->findLastChildDeadlineValue($this->task);
            }
        }

        $template->isVisible = $this->isVisible;
        $template->task = $this->task;
        $template->isEditForm = $this->isEditForm;
        $template->isCancelButtonVisible = $this->isCancelButtonVisible;

        $template->render();
    }
}