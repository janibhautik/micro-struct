<?php

namespace MicroStructure;

/**
 * Base Functionality which are applicable to everything.
 *
 * @author      Bhavik Patel
 * @license     New BSD License
 */
class BaseFunctionality
{

    /**
     * Entity manager instance.
     * 
     * @var \Doctrine\ORM\EntityManager 
     */
    protected $entityManager;

    /**
     * Database instance.
     * 
     * @var \Doctrine\DBAL\Connection
     */
    protected $db;

    /**
     * 
     */
    public function __construct()
    {
        $this->entityManager = Application::getEntityManager();
        $this->db = Application::getDatabase();
    }

}
