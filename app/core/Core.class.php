<?php

class Core {

    private static $data = [];

    public static function set($key, $value) {
        self::$data[$key] = $value;
    }

    public static function get($key = null) {
        if (!isset($key)) return self::$data;
        return isset(self::$data[$key]) ? self::$data[$key] : null;
    }

    public function start() {

        // Add dependencies

        // + core
        Factory::addDependency($this);

        // + database
        $database = new Database;
        $database->connect('localhost', 'hetic_panel', 'root', 'root');
        Factory::addDependency($database);

        // If we can't connect
        if (!$database->isConnected()) {
            throw new Exception('Can\'t connect to databse.');
        }

        // + session
        $session = new Session;
        $session->start();
        Factory::addDependency($session);

        // Add default data

        // + current user
        $auth = Factory::create(new Auth);
        self::set('user', $auth->current());

        // + flash
        $flash = Factory::create(new Flash);
        self::set('flash', $flash);
        
    }

    public function follow($router) {

        // Add model to array
        Factory::addDependency($router);

        // Get request saved in router
        $request = $router->getRequest();

        // Path is like;
        // Controller/method/args...
        $controllerName = strtolower(array_shift($request));
        $methodName = strtolower(array_shift($request));
        $args = $request;

        // So we get the controller
        $controller = $this->getController($controllerName);

        // Controller exists
        if (isset($controller)) {

            // And get current user
            $auth = Factory::getDependency('Auth');

            // If he has permission
            if ($auth->hasPermission($controller)) {

                if (isset($controller)) {

                    // And the view
                    $view = $this->getView($controller, $methodName, $args);

                    if ($view !== false) {

                        // If we have a string, it is the path of the view
                        if (is_string($view)) {
                            $controller->render($view);
                            return;

                        // If it return nothing, we guess it from the name
                        } else if (is_null($view)) {
                            $controller->render('page/' . $controllerName . '/' . $methodName);
                            return;

                        // Avoid looping in error
                        } else if ($controllerName !== 'error') {
                            $router = new Router;
                            $router->setRequest(['error']);
                            $this->follow($router);
                        }
                    }
                }

            // Current user doesn't have permission
            } else if ($auth->isLogged()) {

                // Get flash
                $flash = Factory::getDependency('Flash');
                $flash->set(false, 'Désolé, vous n\'avez pas la permission.');

                $router->go('auth/login');

            // User is not logged in
            } else {

                // Get flash
                $flash = Factory::getDependency('Flash');
                $flash->set(false, 'Vous devez vous connecter.');

                $router->go('auth/login');

            }

        }

        // Avoid looping in error
        if ($controllerName !== 'error') {
            $router = new Router;
            $router->setRequest(['error']);
            $this->follow($router);

        // Print error
        } else {
            echo 'Error with controller: <b>' . ucfirst($controllerName) . '</b> and method: <b>' . $methodName . '</b>.';
            exit;
        }

    }

    public function getController($controllerName) {
        // Get controller like Controller
        $controllerName = ucfirst(strtolower($controllerName));

        // It has to be a file like Controller.class.php
        if (is_file(CONTROLLER . $controllerName . '.class.php')) {

            // Include it
            require_once CONTROLLER . $controllerName . '.class.php';

            // The name of class is followed by Controller
            $controllerName .= 'Controller';

            // It should be okay
            if (class_exists($controllerName)) {

                // Create it
                $controller = new $controllerName();

                // Inject dependencies
                Factory::create($controller);

                // Return it
                return $controller;
            }

        }

        return null;
    }

    public function view($view, $data = []) {
        // If file exists
        if (is_file(VIEW . $view . '.php')) {

            // Wrap data in array if it isn't one
            if (!is_array($data)) $data = [$data];

            // Include the file
            require VIEW . $view . '.php';

            // Success !
            return true;

        } else {

            // Or return false
            return false;
        }
    }

    public function getView($controller, $method, $args) {

        $methods = get_class_methods($controller);
        $base = get_class_methods('Controller');
        $avaliable = array_diff($methods, $base);

        // Get normal method
        if (in_array($method, $avaliable)) {

            // Just insert $args
            return $controller->$method($args);
        }

        // Get methodPost if it exists
        else if (POST and in_array($method . 'Post', $avaliable)) {

            // Method will be methodPost
            $method = $method . 'Post';

            // We insert $args and $_POST
            return $controller->$method($args, $_POST);
        }

        // Get GET method if it exists
        else if (GET and in_array($method . 'Get', $avaliable)) {

            // Method will be methodGet
            $method = $method . 'Get';

            // We insert $args and $_GET
            return $controller->$method($args, $_GET);

        }

        return false;
    }

}
