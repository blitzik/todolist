<?php

namespace TodoList\Subscribers;

use Kdyby\Doctrine\EntityManager;
use Kdyby\Events\Subscriber;
use TodoList\Entities\User;
use Nette\Http\IRequest;
use Nette\Object;

class AuthenticationListener extends Object implements Subscriber
{
    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * @var IRequest
     */
    private $httpRequest;


    public function __construct(
        EntityManager $entityManager,
        IRequest $httpRequest
    ) {
        $this->entityManager = $entityManager;
        $this->httpRequest = $httpRequest;
    }

    /**
     * Returns an array of events this subscriber wants to listen to.
     *
     * @return array
     */
    public function getSubscribedEvents()
    {
        return [
            'TodoList\Authenticators\Authenticator::onLoggedIn'
        ];
    }

    public function onLoggedIn(User $user)
    {
        $user->last_login = new \DateTime('now');
        $user->last_ip = $this->httpRequest->getRemoteAddress();

        $this->entityManager->persist($user)->flush();
    }

}