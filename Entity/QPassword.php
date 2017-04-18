<?php

namespace Querdos\QPassDbBundle\Entity;


/**
 * Class QPassword
 * @package Querdos\QPassDbBundle\Entity
 * @author  Hamza ESSAYEGH <hamza.essayegh@protonmail.com>
 */
class QPassword
{
    /**
     * @var int
     */
    private $id;

    /**
     * @var QDatabase
     */
    private $qdatabase;

    /**
     * @var string
     */
    private $label;

    /**
     * @var int
     */
    private $pass_id;

    /**
     * QPassword constructor.
     *
     * @param QDatabase $qdatabase
     * @param string    $label
     * @param int       $pass_id
     */
    public function __construct(QDatabase $qdatabase = null, $label = null, $pass_id = null)
    {
        $this->qdatabase = $qdatabase;
        $this->label     = $label;
        $this->pass_id   = $pass_id;
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
     * @return QPassword
     */
    public function setId($id)
    {
        $this->id = $id;
        return $this;
    }

    /**
     * @return QDatabase
     */
    public function getQdatabase()
    {
        return $this->qdatabase;
    }

    /**
     * @param QDatabase $qdatabase
     *
     * @return QPassword
     */
    public function setQdatabase($qdatabase)
    {
        $this->qdatabase = $qdatabase;
        return $this;
    }

    /**
     * @return string
     */
    public function getLabel()
    {
        return $this->label;
    }

    /**
     * @param string $label
     *
     * @return QPassword
     */
    public function setLabel($label)
    {
        $this->label = $label;
        return $this;
    }

    /**
     * @return int
     */
    public function getPassId()
    {
        return $this->pass_id;
    }

    /**
     * @param int $pass_id
     *
     * @return QPassword
     */
    public function setPassId($pass_id)
    {
        $this->pass_id = $pass_id;
        return $this;
    }
}