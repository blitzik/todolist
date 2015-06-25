<?php

namespace TodoList\Facades;

use Kdyby\Doctrine\EntityRepository;
use Kdyby\Doctrine\EntityManager;
use TodoList\Entities\User;
use TodoList\QueryObjects\UsersQuery;
use TodoList\RuntimeExceptions\EmailDuplicityException;
use TodoList\RuntimeExceptions\UsernameDuplicityException;
use TodoList\Transaction;
use Nette\Object;

class UsersFacade extends Object
{
    /**
     * @var EntityManager
     */
    private $em;

    /**
     * @var EntityRepository
     */
    private $userRepository;

    /**
     * @var Transaction
     */
    private $transaction;


    public function __construct(
        EntityManager $em,
        Transaction $transaction
    ) {
        $this->em = $em;
        $this->userRepository = $em->getRepository(User::class);
        $this->transaction = $transaction;
    }

    /**
     * @param User $user
     * @throws UsernameDuplicityException
     * @throws EmailDuplicityException
     */
    public function registerNewUser(User $user)
    {
        $this->em->transactional(function () use ($user) {
            $newUser = $this->em->safePersist($user);
            if ($newUser === false) {
                $countByUsername = (new UsersQuery())
                                       ->byUsername($user->username)
                                       ->count($this->userRepository);

                if ($countByUsername > 0) {
                    throw new UsernameDuplicityException;
                }

                $countByEmail = (new UsersQuery())
                                    ->byEmail($user->email)
                                    ->count($this->userRepository);

                if ($countByEmail > 0) {
                    throw new EmailDuplicityException;
                }
            }
        });
    }
}