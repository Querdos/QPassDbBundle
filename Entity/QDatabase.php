<?php

namespace Querdos\QPassDbBundle\Entity;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * Class QDatabase
 * @package Querdos\QPassDbBundle\Entity
 * @author  Hamza ESSAYEGH <hamza.essayegh@protonmail.com>
 */
class QDatabase
{
    /**
     * @var int
     */
    private $id;

    /**
     * @var string
     *
     * @Assert\NotBlank(
     *     message="Database name cannot be blank"
     * )
     */
    private $dbname;

    /**
     * @var string
     *
     * @Assert\NotBlank(
     *     message="Password cannot be blank"
     * )
     */
    private $password;

    /**
     * QDatabase constructor.
     *
     * @param string $dbname
     * @param string $password
     */
    public function __construct($dbname = null, $password = null)
    {
        $this->dbname   = $dbname;
        $this->password = $password;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
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
     * @return string
     */
    public function getDbname()
    {
        return $this->dbname;
    }

    /**
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
     * @return string
     */
    public function getPassword()
    {
        return $this->password;
    }

    /**
     * @param string $password
     *
     * @return QDatabase
     */
    public function setPassword($password)
    {
        $this->password = $password;
        return $this;
    }
}