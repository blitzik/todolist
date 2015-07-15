<?php

namespace TodoList\Components;

use Nette\Application\UI\Control;
use Nette\Application\UI\Form;

class FilterBarControl extends Control
{

    public function __construct()
    {

    }

    protected function createComponentTasksFilterForm()
    {
        $form = new Form();



        return $form;
    }

    public function render()
    {
        $template = $this->getTemplate();
        $template->setFile(__DIR__ . '/filter-bar-template.latte');


        $template->render();
    }
}