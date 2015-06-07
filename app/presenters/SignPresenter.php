<?php

namespace TodoList\Presenters;

use TodoList\Factories\AuthenticationFormFactory;
use Nette\Application\UI\Presenter;

class SignPresenter extends Presenter
{
    /**
     * @var AuthenticationFormFactory
     * @inject
     */
    public $authenticationFormFactory;

    protected function createComponentSignForm()
    {
        $form = $this->authenticationFormFactory->create();

        $form->addCheckbox('keepLoggedIn', 'Keep me logged in')
                ->setDefaultValue(true);

        $form['submit']->caption = 'Sign In';

        return $form;
    }
}