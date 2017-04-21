<?php

namespace Querdos\QPassDbBundle\Entity;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * Class QDatabase
 *
 * Represent the encrypted database
 *
 * @package Querdos\QPassDbBundle\Entity
 * @author  Hamza ESSAYEGH <hamza.essayegh@protonmail.com>
 */
class QDatabase
{
    /**
     * QDatabase id
     *
     * @var int
     */
    private $id;

    /**
     * The database name
     *
     * @var string
     *
     * @Assert\NotBlank(
     *     message="Database name cannot be blank"
     * )
     */
    private $dbname;

    /**
     * The plain password, used to create or update a password
     *
     * @var string
     */
    private $plainPassword;

    /**
     * The password for the database.
     * Will be set only if the plainPassword is not null.
     *
     * @var string
     *
     * @Assert\NotBlank(
     *     message="Password cannot be blank"
     * )
     */
    private $password;

    /**
     * List of associated qpassword
     *
     * @var QPassword[]
     */
    private $qpasswords;

    /**
     * QDatabase constructor.
     *
     * @param string $dbname
     * @param string $password
     */
    public function __construct($dbname = null, $password = null)
    {
        $this->dbname        = $dbname;
        $this->plainPassword = $password;
        $this->qpasswords    = array();
    }

    /**
     * Return the id
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set the id
     *
     * @param int $id
     *
     * @return QDatabase
     */
    public function setId($id)
    {
        $this->id = $id;
        return $this;
    }

    /**
     * Return the database name
     *
     * @return string
     */
    public function getDbname()
    {
        return $this->dbname;
    }

    /**
     * Set the database name
     *
     * @param string $dbname
     *
     * @return QDatabase
     */
    public function setDbname($dbname)
    {
        $this->dbname = $dbname;
        return $this;
    }

    /**
     * Return the real password of the database
     *
     * @return string
     */
    public function getPassword()
    {
        return $this->password;
    }

    /**
     * Set the password for the database
     *
     * @param string $password
     *
     * @return QDatabase
     */
    public function setPassword($password)
    {
        $this->password = $password;
        return $this;
    }

    /**
     * Return the list of associated passwords
     *
     * @return QPassword[]
     */
    public function getQpasswords()
    {
        return $this->qpasswords;
    }

    /**
     * Set the list of passwords
     *
     * @param QPassword[] $qpasswords
     *
     * @return QDatabase
     */
    public function setQpasswords($qpasswords)
    {
        $this->qpasswords = $qpasswords;
        return $this;
    }

    /**
     * Return the plain password
     *
     * @return string
     */
    public function getPlainPassword()
    {
        return $this->plainPassword;
    }

    /**
     * Set the plain password
     *
     * @param string $plainPassword
     *
     * @return QDatabase
     */
    public function setPlainPassword($plainPassword)
    {
        $this->plainPassword = $plainPassword;
        return $this;
    }
}