<?php

namespace TodoList\Presenters;

use TodoList\Factories\AuthenticationFormFactory;
use Nette\Application\UI\Presenter;

class PasswordResetPresenter extends Presenter
{
    /**
     * @var AuthenticationFormFactory
     * @inject
     */
    public $authenticationFormFactory;

    protected function createComponentPasswordResetForm()
    {
        $form = $this->authenticationFormFactory->create();

        $form['email']->setAttribute('placeholder', 'Enter your E-mail address');

        unset($form['password']);

        return $form;
    }
}