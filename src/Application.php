<?php

namespace MicroStructure;

use ReflectionMethod;
use Exception;

/**
 * Application class responsible for whole Structure.
 *
 * @author Bhavik Patel
 */
class Application
{

    /**
     * Full current request string without GET parameter attached to it.
     * 
     * @var string 
     */
    private static $base_url = NULL;

    /**
     * PATH_INFO as an array. 
     * Exploded by '/'.
     * 
     * @var array 
     */
    private $request_uri = '';

    /**
     * Located controller class name.
     * 
     * @var type 
     */
    private $located_controller;

    /**
     * Located method name.
     * 
     * @var type 
     */
    private $located_method;

    /**
     * Regexed route rules.
     * 
     * @var array 
     */
    private $route_rules = array();

    /**
     * Remaining parts of current request string.
     * 
     * @var array 
     */
    private $remaining_uri = array();

    /**
     * Route Configuration.
     * 
     * @var array 
     */
    private $route_config = array();

    /**
     * Application configuration.
     * 
     * @var array 
     */
    private $app_config = array();

    /**
     * 
     */
    public function __construct()
    {

        set_error_handler(array($this, 'errorHandler'));
        set_exception_handler(array($this, 'exceptionHandler'));

        #Starting system
        $this->start();
    }

    /**
     * Constructs base URL and registers it.
     */
    private function setBaseURL()
    {
        if (isset($_SERVER['HTTP_HOST']))
        {
            $base_url = isset($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) !== 'off' ? 'https' : 'http';
            $base_url .= '://' . $_SERVER['HTTP_HOST'];
            $base_url .= str_replace(basename($_SERVER['SCRIPT_NAME']), '',
                    $_SERVER['SCRIPT_NAME']);
        }
        else
        {
            $base_url = 'http://localhost/';
        }

        self::$base_url = $base_url;
    }

    /**
     * Returns base URL concated with remaining uri parts.
     * 
     * @param   string      $uri
     * @return  string
     */
    public static function getBaseURL($uri = '')
    {
        return self::$base_url . $uri;
    }

    /**
     * Invokes located method.
     * 
     * @param   boolean     $error
     */
    private function invokeMethod()
    {
        $class = "App\\Controller\\" . $this->located_controller;

        if (!class_exists($class))
        {
            throw new Exception("$class class not found.");
        }

        $controller = new $class;
        if (method_exists($controller, $this->located_method))
        {
            $reflection = new ReflectionMethod($controller,
                    $this->located_method);

            # Perpareing method parameter from remaining url.
            $parameter = array();
            $index = 0;
            foreach ($reflection->getParameters() as $param)
            {
                if (array_key_exists($index, $this->remaining_uri))
                {
                    $parameter[$index] = $this->remaining_uri[$index];
                }
                else if ($param->isOptional())
                {
                    $parameter[$index] = $param->getDefaultValue();
                }
                else
                {
                    throw new Exception('Not found.');
                }
                $index++;
            }

            $reflection->invokeArgs($controller, $parameter);
        }
    }

    /**
     * Custom error handler.
     * 
     * @param   int         $code
     * @param   string      $string
     * @param   string      $file
     * @param   int         $line
     * @return  boolean
     */
    public function errorHandler($code, $string, $file, $line)
    {

        if (!(error_reporting() & $code))
        {
            return;
        }

        switch ($code)
        {
            case E_USER_ERROR:
                $title = "FATAL ERROR [$code]";
                break;
            case E_USER_WARNING:
                $title = "WARNING";
                break;
            case E_USER_NOTICE:
                $title = "WARNING";
                break;
            default:
                $title = "Unknown Error";
                break;
        }

        $message = "<p style='color:#000;padding:0;margin:0'><span style='border-bottom:1px dashed #000'>$string</span> on line $line in file <i>$file</i></p>";
        echo "<div style='color:#920000;border:1px solid #CCC;padding:3px;display:inline-block'><b>{$title}</b><br/>$message</div>";
        return true;
    }

    /**
     * Custom exception handler.
     * 
     * @param   object  $ex
     */
    public function exceptionHandler(Exception $ex)
    {
        return $this->errorHandler(E_USER_ERROR, $ex->getMessage(),
                        $ex->getFile(), $ex->getLine());
    }

    /**
     * Parses REQUEST_URI and set it to 'request_uri' property of this class.
     * 
     */
    private function setRequestURI()
    {

        if (!isset($_SERVER['REQUEST_URI']) || !isset($_SERVER['SCRIPT_NAME']))
        {
            $this->request_uri = '';
        }
        else
        {

            $uri = $_SERVER['REQUEST_URI'];
            $script = $_SERVER['SCRIPT_NAME'];

            if (strpos($uri, $script) === 0)
            {
                $uri = substr($uri, strlen($script));
            }
            elseif (strpos($uri, dirname($script)) === 0)
            {
                $uri = substr($uri, strlen(dirname($script)));
            }

            if (strncmp($uri, '?/', 2) === 0)
            {
                $uri = substr($uri, 2);
            }

            $parts = preg_split('#\?#i', $uri, 2);
            $uri = $parts[0];

            if ($uri == '/' || empty($uri))
            {
                $this->request_uri = '';
            }

            $uri = parse_url($uri, PHP_URL_PATH);
            $this->request_uri = str_replace(array('//', '../'), '/',
                    trim($uri, '/'));
        }
    }

    /**
     * Starts system by first loading configuration then processing request.
     * Locates module,controller and method based request set and calls it.
     * Connects to database before invoking located method if configured.
     */
    private function start()
    {
        $this->setBaseURL();
        $this->setRequestURI();
        $this->fetchRouteRules();
        $this->locateMethod();
        $this->invokeMethod();
    }

    /**
     * 
     * @return Doctrine\ORM\EntityManager
     */
    public static function getEntityManager()
    {
        return self::$entityManager;
    }

    /**
     * Fetches routes from route config.
     * Replace placeholder with their regex.
     * Store it to route rules property.
     */
    private function fetchRouteRules()
    {

        # User defined routes.
        $this->route_config = require_once ROOT . 'config' . DSC . 'routes.php';

        # Route placeholder and their regex.
        $regex = array(
            '(:string)' => "([a-zA-Z0-9-_.]+)",
            '(:int)' => "([0-9]+)"
        );

        # Iterate over and preparing regex route rules.
        foreach ($this->route_config['rule'] as $rule => $value)
        {
            $new_rule = array();
            foreach (explode('/', $rule) as $split)
            {
                if (array_key_exists($split, $regex))
                {
                    $new_rule[] = str_replace($split, $regex[$split], $split);
                }
                else
                {
                    $new_rule[] = preg_quote($split);
                }
            }
            $this->route_rules[implode(preg_quote('/'), $new_rule)] = $value;
        }
    }

    /**
     * 
     * @return boolean
     */
    protected function locateMethod()
    {

        # When no request string, use default route.
        if ($this->request_uri === '')
        {
            $callback = $this->route_rules['/'];
            $this->located_controller = $callback['controller'];
            $this->located_method = $callback['method'];
            return TRUE;
        }

        # Finding matched route.
        foreach ($this->route_rules as $rule => $callback)
        {
            preg_match('#' . $rule . '#', $this->request_uri, $matches);
            if (count($matches) > 1)
            {
                $controller = $callback['controller'];
                $method = $callback['method'];
                $index = 0;

                # Locating controller class.
                if (strpos($controller, '@') !== FALSE)
                {
                    $at = substr($controller, 1);
                    if ($at == 0 || !isset($matches[$at]))
                    {
                        throw new Exception('@' . $at . ' not found in regex.');
                    }

                    $controller = $matches[$at];
                    $index = ++$at;
                }

                # Locating controller method name.
                if (strpos($method, '@') !== FALSE)
                {
                    $at = substr($method, 1);
                    if ($at == 0 || !isset($matches[$at]))
                    {
                        throw new Exception('@' . $at . ' not found in regex.');
                    }
                    $method = $matches[$at];
                    $index = ++$at;
                }

                # Setting located controller class,method and remaining part
                # which are going to be method parameter to this class property.
                $this->located_controller = $controller;
                $this->located_method = $method;
                $this->remaining_uri = array_slice(explode('/',
                                $this->request_uri), $index);
                break;
            }
        }
    }

    /**
     * Return current url of the request.
     * 
     * @return string
     */
    public static function getCurrentURL()
    {
        $pageURL = 'http';
        if (isset($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] == "on")
        {
            $pageURL .= "s";
        }
        $pageURL .= "://";
        if ($_SERVER["SERVER_PORT"] != "80")
        {
            $pageURL .= $_SERVER["SERVER_NAME"] . ":" . $_SERVER["SERVER_PORT"] . $_SERVER["REQUEST_URI"];
        }
        else
        {
            $pageURL .= $_SERVER["SERVER_NAME"] . $_SERVER["REQUEST_URI"];
        }
        return $pageURL;
    }

}
