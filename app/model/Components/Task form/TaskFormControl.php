<?php

namespace TodoList\Components;

use Nette\Forms\Controls\SubmitButton;
use TodoList\Facades\TasksFacade;
use Kdyby\Doctrine\EntityManager;
use Nette\Application\UI\Control;
use Nette\Application\UI\Form;
use TodoList\Entities\Project;
use Nette\Utils\Validators;
use TodoList\Entities\Task;
use Tracy\Debugger;

class TaskFormControl extends Control
{
    public $onNewTask;
    public $onEditTask;
    public $onCancelClick;

    /**
     * @var TasksFacade
     */
    private $tasksFacade;

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
        EntityManager $entityManager,
        TasksFacade $tasksFacade
    ) {
        $this->em = $entityManager;
        $this->tasksFacade = $tasksFacade;
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
        $this['form']['isEditForm']->value = $isForEdit;
    }

    /**
     * @param Project $project
     */
    public function setProject(Project $project)
    {
        $this->project = $project;
    }

    public function setFormAsEditable($isEditable)
    {
        Validators::assert($isEditable, 'bool');

        if ($isEditable === false) {
            $this['form']['description']->value = null;
            $this['form']['date']->value = null;
        }

        $this->isEditForm = $isEditable;
        $this['form']['isEditForm']->value = $isEditable;
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
             ->setRequired('Type the description field.');

        $form->addText('date', 'Date:', 10, 10)
             ->setRequired('Type the date field.')
             ->setAttribute('class', 'datepicker form-control');

        if (isset($this->isEditForm)) {
            $form->addHidden('isEditForm'/*, (isset($this->task) ? $this->isEditForm : 'noEditForm')*/);
        }

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
        // for ajax request; after submitting form, the component will
        // disappear and no error is shown. In order to keep component
        // visible, we have to set it's visibility property to true.
        $this->isVisible = true;

        $form = $button->getForm();
        $values = $form->getValues();

        $deadlineDate = new \DateTime($values['date']);
        $currentDate = new \DateTime(date('Y-m-d'));

        if (isset($values['isEditForm'])) {
            if ($values['isEditForm'] == true) {
                $lowestDeadline = $this->tasksFacade->findLowestDeadlineValue($this->task);
                $highestDeadline = $this->tasksFacade->findHighestDeadlineValue($this->task);

                if ($highestDeadline === null) { // root Task
                    if ($lowestDeadline > $deadlineDate) {
                        $form->addError('The lowest possible Deadline to select is: ' . $lowestDeadline->format('d.m.Y'));
                        $this->redrawControl();
                        return;
                    }
                } else { // child
                    if ($lowestDeadline > $deadlineDate or $deadlineDate > $highestDeadline) {
                        $errMsg = 'The Task deadline has to be between: ' . $lowestDeadline->format('d.m.Y') . ' - ' .
                                  $highestDeadline->format('d.m.Y');

                        if ($this->task->isOverdue()) {
                            if ($highestDeadline < $currentDate) { // we can change deadline only in first child or direct lower sibling
                                $form->addError('In order to change deadline in this Task
                                                 you have to change deadline in the most higher sibling.');
                            } else {
                                $form->addError($errMsg);
                            }
                        } else {
                            $form->addError($errMsg);
                        }
                        $this->redrawControl();
                        return;
                    }
                }

                $wasOverdue = $this->task->isOverdue();

                $this->task->setDescription($values['description']);
                $this->task->setDeadline($deadlineDate);
                $this->task->setLastChildDeadline($deadlineDate);

                $parent = $this->task->parent;
                if ($this->tasksFacade->isLastChild($this->task)) {
                    $parent->setLastChildDeadline($deadlineDate);
                    $this->em->persist($parent);
                }

                $this->em->persist($this->task)->flush();

                $this->onEditTask($this, $this->task, $wasOverdue);

            } else { // new Sub Task creation

                $lastChildDeadline = $this->tasksFacade->findLastChildDeadlineValue($this->task);
                if ($deadlineDate < $currentDate  or $deadlineDate > $lastChildDeadline) {
                    $form->addError(
                        'The Task Deadline can be between: ' . ($currentDate->format('d.m.Y')) . ' - ' .
                        $lastChildDeadline->format('d.m.Y')
                    );
                    $this->redrawControl();
                    return;
                }

                $task = new Task(
                    $values['description'],
                    $this->em->getReference(Project::class, $this->task->project->getId()),
                    $deadlineDate,
                    $this->task
                );
                $task->setLastChildDeadline($deadlineDate);
                $this->task->setLastChildDeadline($deadlineDate);

                $this->em->persist($task);
                $this->em->persist($this->task)->flush();

                $this->onNewTask($this, $task);
            }
        } else { // New Root Task creation
            if ($deadlineDate <= $currentDate) {
                $form->addError('The lowest possible Deadline to select is: ' . $currentDate->format('d.m.Y'));
                $this->redrawControl();
                return;
            }

            if (isset($this->project)) {
                $task = new Task(
                    $values['description'],
                    $this->project,
                    $deadlineDate
                );
                $task->setLastChildDeadline($deadlineDate);

                $this->em->persist($task)->flush();

                $this->onNewTask($this, $task);
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

        if ($this->isVisible and isset($this->task)) { // isset($this->task) -> Edit and Sub task creation
            if ($this->isEditForm === true) {
                $template->lowestDeadline = $this->tasksFacade->findLowestDeadlineValue($this->task);
                $template->highestDeadline = $this->tasksFacade->findHighestDeadlineValue($this->task);
            } else {
                $template->lastChildDeadline = $this->tasksFacade->findLastChildDeadlineValue($this->task);
            }
        }

        $template->isVisible = $this->isVisible;
        $template->task = $this->task;
        $template->isEditForm = $this->isEditForm;
        $template->isCancelButtonVisible = $this->isCancelButtonVisible;

        $template->render();
    }
}