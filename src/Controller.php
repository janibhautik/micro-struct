<?php

namespace MicroStructure;

/**
 * Description of Controller
 *
 * @author Bhavik Patel
 */
class Controller extends BaseFunctionality
{

    /**
     *
     * @var type 
     */
    protected $view_data = array();

    /**
     * Module Name.
     * 
     * @var type 
     */
    protected $module_name = NULL;

    /**
     *
     * @var \MicroStructure\View 
     */
    protected $view = array();

    /**
     * 
     * @param string $module_name
     */
    public function __construct($module_name = NULL)
    {
        parent::__construct($module_name);
        $this->module_name = $module_name === NULL ? 'main' : $module_name;
    }

    /**
     * Returns path to view file.
     * 
     * @param   string      $name
     * @return  string
     */
    private function getViewPath($name)
    {

        $module = $this->module_name !== NULL ? ($this->module_name) . DSC : '';
        return PUBLIC_DIR . 'views' . DSC . $module . $name . '.php';
    }

    /**
     * Render View.
     * 
     * @param   string          $name
     * @param   array           $data
     * @param   boolean         $return
     * @return  string|NULL
     */
    public function renderView($name, $data = array(), $return = FALSE)
    {
        $file = $this->getViewPath($name);
        return $this->loadView($file, $data, $return);
    }

    /**
     * Load View.
     * 
     * @param   string      $file
     * @param   array       $data
     * @param   boolean     $return
     * @return  string
     */
    private function loadView($file, $data = array(), $return = FALSE)
    {
        ob_start();
        if (file_exists($file))
        {
            # Preventing $data of this method to be passed to view.
            $__data = $data;
            unset($data);

            # Merging with already added data to class property.
            $___data = array_merge($this->view_data, $__data);

            extract($___data);

            # Now we don't want __data and ___data.
            unset($__data, $___data);

            include $file;
        }

        # Returning view if requested to return.
        if ($return === TRUE)
        {
            $content = ob_get_contents();
            ob_end_clean();
            return $content;
        }
    }

    /**
     * Add view to template.
     * Added view will be know to template by $known's value.
     * $known must be stirng.
     * 
     * @param   string      $name
     * @param   string      $known
     * @param   array       $data
     */
    public function addView($name, $known, $data = array())
    {
        $this->view[$known] = $this->loadView($this->getViewPath($name), $data,
                TRUE);
    }

    /**
     * Render given template with all added view.
     * 
     * @param string $name
     */
    public function renderTemplate($name)
    {
        $data = array_merge($this->view_data, $this->view);
        $template = PUBLIC_DIR . 'template' . DSC . $name . '.php';
        $this->loadView($template, $data);
    }

}
