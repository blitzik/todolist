<?php

namespace TodoList\Authenticators;

use TodoList\RuntimeExceptions\AuthenticationException;
use TodoList\Authentication\FakeIdentity;
use TodoList\QueryObjects\UsersQuery;
use Kdyby\Doctrine\EntityRepository;
use Nette\Security\IAuthenticator;
use Kdyby\Doctrine\EntityManager;
use Nette\Security\IIdentity;
use Nette\Security\Passwords;
use TodoList\Entities\User;
use Nette\Object;

class Authenticator extends Object implements IAuthenticator
{
    /**
     * @var array
     */
    public $onLoggedIn = [];

    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * @var EntityRepository
     */
    private $userRepository;

    public function __construct(
        EntityManager $entityManager
    ) {
        $this->entityManager = $entityManager;
        $this->userRepository = $entityManager->getRepository(User::class);
    }

    /**
     * Performs an authentication against e.g. database.
     * and returns IIdentity on success or throws AuthenticationException
     * @return IIdentity
     * @throws AuthenticationException
     */
    function authenticate(array $credentials)
    {
        list($email, $password) = $credentials;

        $user = $this->userRepository->fetchOne(
            (new UsersQuery())->byEmail($email)
        );

        if ($user === null) {
            throw new AuthenticationException('Wrong e-mail.');
        }

        if (!Passwords::verify($password, $user->password)) {
            throw new AuthenticationException('Wrong password.');

        } elseif (Passwords::needsRehash($user->password)) {
            $user->password = Passwords::hash($password);
        }

        $this->onLoggedIn($user);

        return new FakeIdentity($user->id, get_class($user));
    }

}