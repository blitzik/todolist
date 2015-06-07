<?php

namespace TodoList\Presenters;

use TodoList\Factories\AuthenticationFormFactory;
use Nette\Application\UI\Presenter;
use Nette\Application\UI\Form;

class AccountPresenter extends Presenter
{
    /**
     * @var AuthenticationFormFactory
     * @inject
     */
    public $authenticationFormFactory;

    protected function createComponentRegistrationForm()
    {
        $form = $this->authenticationFormFactory->create();

        $form->addText('username', 'Username')
                ->setRequired('Type your username.')
                ->setAttribute('class', 'form-control');

        $form['password']->addRule(
            Form::MIN_LENGTH,
            'Your password must have at least %d characters', 5
        );

        $form->addText('passConfirm', 'Confirm your Password')
                ->setRequired('Confirm your password, please.')
                ->setAttribute('class', 'form-control')
                ->addRule(
                    Form::EQUAL,
                    'Your Password doesn\'t match the confirmation.',
                    $form['password']
                );

        $form['submit']->caption = 'Create an Account';

        return $form;
    }
}