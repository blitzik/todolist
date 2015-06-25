<?php

namespace TodoList\Presenters;

use TodoList\RuntimeExceptions\UsernameDuplicityException;
use TodoList\RuntimeExceptions\EmailDuplicityException;
use TodoList\Factories\AuthenticationFormFactory;
use Nette\Application\UI\Presenter;
use Kdyby\Doctrine\EntityManager;
use TodoList\Facades\UsersFacade;
use Nette\Application\UI\Form;
use TodoList\Entities\User;

class AccountPresenter extends Presenter
{
    /**
     * @var AuthenticationFormFactory
     * @inject
     */
    public $authenticationFormFactory;

    /**
     * @var UsersFacade
     * @inject
     */
    public $usersFacade;

    /**
     * @var \Nette\Http\Request
     * @inject
     */
    public $request;

    /**
     * @var EntityManager
     * @inject
     */
    public $em;

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

        $form->addPassword('passConfirm', 'Confirm your Password')
                ->setRequired('Confirm your password, please.')
                ->setOmitted()
                ->setAttribute('class', 'form-control')
                ->addRule(
                    Form::EQUAL,
                    'Your Password doesn\'t match the confirmation.',
                    $form['password']
                );

        $form['submit']->caption = 'Create an Account';

        $form->onSuccess[] = [$this, 'processRegistration'];

        return $form;
    }

    public function processRegistration(Form $form, $values)
    {
        $user = new User(
            $values['username'],
            $values['password'],
            $values['email'],
            $this->request->getRemoteAddress()
        );

        try {
            $this->usersFacade->registerNewUser($user);
            $this->flashMessage('Your Account has been successfully created.', 'bg-success');
            $this->redirect('Sign:default');

        } catch (UsernameDuplicityException $ude) {
            $form->addError('Username is already taken.');

        } catch (EmailDuplicityException $ede) {
            $form->addError('E-mail is already taken.');
        }

    }
}