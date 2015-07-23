<?php

namespace MicroStructure;

use MicroStructure\System;

/**
 * Description of Controller
 *
 * @author Bhavik Patel
 */
class Controller {

    /**
     *
     * @var type 
     */
    protected $view_data = array();

    /**
     *
     * @var type 
     */
    protected $module_name = NULL;

    /**
     *
     * @var Doctrine\ORM\EntityManager 
     */
    protected $entityManager;

    /**
     *
     * @var \MicroStructure\View 
     */
    protected $view;

    /**
     * 
     */
    public function __construct() {
    }


}
