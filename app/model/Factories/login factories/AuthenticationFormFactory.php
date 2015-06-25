<?php

namespace TodoList\Factories;

use Nette\Application\UI\Form;
use Nette\Object;

class AuthenticationFormFactory extends Object
{
    /**
     * @return Form
     */
    public function create()
    {
        $form = new Form();

        $form->addText('email', 'E-mail address')
                ->setRequired('Type your E-mail.')
                ->setAttribute('class', 'form-control')
                ->addRule(Form::EMAIL, 'Enter valid E-mail address, please.');

        $form->addPassword('password', 'Password')
                ->setRequired('Type your password.')
                ->setAttribute('class', 'form-control');

        $form->addSubmit('submit', 'Submit')
                ->setAttribute('class', 'btn btn-default');

        return $form;
    }
}