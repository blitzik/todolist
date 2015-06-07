<?php

namespace TodoList\Entities;

use Exceptions\Logic\InvalidArgumentException;
use Kdyby\Doctrine\Entities\BaseEntity;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Index;
use Nette\Security\Passwords;
use Nette\Utils\Validators;
use Nette\Utils\Random;

/**
 * @ORM\Entity
 * @ORM\Table(name="user")
 */
class User extends BaseEntity
{
    /**
     * @ORM\Id
     * @ORM\Column(name="user_id", type="integer", options={"unsigned":true})
     * @ORM\GeneratedValue
     * @var int
     */
    private $id;

    /**
     * @ORM\Column(name="username", type="string", length=25, nullable=false, unique=true)
     * @var string
     */
    protected $username;
    
    /**
     * @ORM\Column(name="`password`", type="string", length=60, nullable=false, unique=false)
     * @var string
     */
    protected $password;

    /**
     * @ORM\Column(name="email", type="string", length=70, nullable=false, unique=true)
     * @var string
     */
    protected $email;

    /**
     * @ORM\Column(name="role", type="string", length=20, nullable=false, unique=false, options={"default":"member"})
     * @var string
     */
    protected $role;
    
    /**
     * @ORM\Column(name="ip", type="string", length=39, nullable=false, unique=false)
     * @var string
     */
    protected $ip;
    
    /**
     * @ORM\Column(name="last_login", type="datetime", nullable=false, unique=false)
     * @var \DateTime
     */
    protected $last_login;
    
    /**
     * @ORM\Column(name="last_ip", type="string", length=39, nullable=false, unique=false)
     * @var string
     */
    protected $last_ip;

    /**
     * @ORM\Column(name="token", type="string", length=32, nullable=true, unique=false)
     * @var string
     */
    private $token;

    /**
     * @ORM\Column(name="token_validity", type="datetime", nullable=true, unique=false)
     * @var \DateTime
     */
    private $token_validity;

    /**
     * @param $username
     * @param $password
     * @param $email
     * @param $ip
     * @param null $role
     * @throws InvalidArgumentException
     */
    public function __construct(
        $username,
        $password,
        $email,
        $ip,
        $role = null
    ) {
        Validators::assert($username, 'string:1..25');
        $this->username = $username;

        $pass = Passwords::hash($password);
        Validators::assert($pass, 'string:60');
        $this->password = $pass;

        Validators::assert($ip, 'string:1..39');
        $this->ip = $ip;
        $this->last_ip = $this->ip;

        $this->last_login = new \DateTime('now');

        Validators::assert($email, 'email');
        Validators::assert($email, 'string:1..70');
        $this->email = $email;

        Validators::assert($role, 'null|string:1..20');
        $this->role = $role;
    }

    /**
     * @param \DateTime $token_validity
     */
    public function generateToken(\DateTime $token_validity)
    {
        $this->token = Random::generate(32);
        $this->token_validity = $token_validity;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getToken()
    {
        return $this->token;
    }

    /**
     * @return \DateTime
     */
    public function getTokenValidity()
    {
        return $this->token_validity;
    }

}