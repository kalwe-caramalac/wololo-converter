<?php

spl_autoload_register(function ($class) {

    $availables = [
        "core/" . $clas . ".php",
        "core/conversores/" . $class . ".php",
        $class
    ];

    $registers = [
        "Wololo\\Conversor" => 'core/conversores'
    ];

    foreach ($possibilidades as $file) {
        $file = loadByNamespace($registers, $file);
        $file = __DIR__ . "/" . $file;
        $file = str_replace(array("/", "\\"), DIRECTORY_SEPARATOR, $file);

        if (file_exists($file)) {
            require_once $file;
            return true;
        }
    }
});

function loadByNamespace($registers, $file)
{
    foreach ($registers as $namespace => $path) {
        if (strpos($file, $namespace) !== false) {
            return str_replace($namespace, $path, $file) . '.php';
        }
    }

    return $file;
}
