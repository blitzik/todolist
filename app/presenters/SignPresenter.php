<?php

namespace TodoList\Presenters;

use TodoList\Factories\AuthenticationFormFactory;
use Nette\Application\UI\Presenter;
use Nette\Application\UI\Form;
use TodoList\RuntimeExceptions\AuthenticationException;

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

        $form->onSuccess[] = [$this, 'processLogin'];

        return $form;
    }

    public function processLogin(Form $form, $values)
    {
        $this->user->setExpiration('+1 hour'); // logout after browser is closed
        if ($values['keepLoggedIn']) {
            $this->user->setExpiration('+1 month', false);
        }

        try {
            $this->user->login($values['email'], $values['password']);
            $this->redirect('Homepage:default');

        } catch (AuthenticationException $e) {
            $form->addError($e->getMessage());
        }
    }
}