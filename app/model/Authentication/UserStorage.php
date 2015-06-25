<?php

namespace TodoList\Authentication;

use Kdyby\Doctrine\EntityManager;
use Nette\Security\IIdentity;
use Nette\Http\Session;

class UserStorage extends \Nette\Http\UserStorage
{
    /**
     * @var EntityManager
     */
    private $entityManager;

    public function  __construct(
        Session $sessionHandler,
        EntityManager $entityManager
    ) {
        parent::__construct($sessionHandler);
        
        $this->entityManager = $entityManager;
    }

    /**
     * Sets the user identity.
     * @return UserStorage Provides a fluent interface
     */
    public function setIdentity(IIdentity $identity = null)
    {
        if ($identity !== NULL) {
            $class = get_class($identity);
            // we want to convert identity entities into fake identity
            // so only the identifier fields are stored,
            // but we are only interested in identities which are correctly
            // mapped as doctrine entities
            if ($this->entityManager->getMetadataFactory()->hasMetadataFor($class)) {
                $cm = $this->entityManager->getClassMetadata($class);
                $identifier = $cm->getIdentifierValues($identity);
                $identity = new FakeIdentity($identifier, $class);
            }
        }
        return parent::setIdentity($identity);
    }

    /**
     * Returns current user identity, if any.
     * @return IIdentity|NULL
     */
    public function getIdentity()
    {
        $identity = parent::getIdentity();
        // if we have our fake identity, we now want to
        // convert it back into the real entity
        // returning reference provides potentially lazy behavior
        if ($identity instanceof FakeIdentity) {
            return $this->entityManager->getReference($identity->getClass(), $identity->getId());
        }
        return $identity;
    }


}