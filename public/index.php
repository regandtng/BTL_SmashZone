<?php

define('ROOT', dirname(__DIR__));

$scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
$scriptDir = rtrim($scriptDir, '/');
define('BASE_URL', $scriptDir);


spl_autoload_register(function ($class) {
    $file = ROOT . '/' . str_replace('\\', '/', lcfirst($class)) . '.php';
    if (strpos($class, 'App\\') === 0) {
        $file = ROOT . '/app/' . str_replace('\\', '/', substr($class, 4)) . '.php';
    }
    elseif (strpos($class, 'Core\\') === 0) {
        $file = ROOT . '/core/' . str_replace('\\', '/', substr($class, 5)) . '.php';
    }
    
    if (file_exists($file)) {
        require $file;
    }
});

\Core\Installer::ensureEnvFile(ROOT);

if (file_exists(ROOT . '/.env')) {
    $lines = file(ROOT . '/.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        list($name, $value) = explode('=', $line, 2);
        $name = trim($name);
        if (!isset($_ENV[$name])) {
            $_ENV[$name] = trim($value);
        }
    }
}

try {
    \Core\Installer::install(ROOT);
} catch (\Throwable $e) {
    $GLOBALS['__install_error'] = $e->getMessage();
}

\Core\Session::start();

$router = new \Core\Router();

require ROOT . '/routes/web.php';

$router->dispatch();
