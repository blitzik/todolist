<?php

namespace TodoList\Factories;

use Nette\Application\UI\Form;
use Nette\Object;

class RemoveFormFactory extends Object
{
    public function create()
    {
        $form = new Form();

        $form->addSubmit('remove', 'Remove');

        $form->addSubmit('cancel', 'Cancel');

        return $form;
    }
}