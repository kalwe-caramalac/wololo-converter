<?php

// require __DIR__ . "/../vendor/autoload.php";

// use Core\ConversorUTF8;

include __DIR__ . "/conversores/ConversorUTF8.php";
include __DIR__ . "/conversores/ConversorDatabase.php";

class Padre
{
    private $encodeType;

    private $conversivel;
    private $conversor;

    private $io;
    private $args;

    public function __construct($args) {

        $this->args = (object)$args;

        $this->io = $this->args->io;
        $this->conversivel = $this->args->conversivel;

        $this->chooseConversor();
    }

    public function converter() {
        $this->conversor->preparaConversao();
        $this->conversor->executeConversionProcedures($this->args);
    }

    private function chooseConversor() {
        $conversor = NULL;
        if ($this->conversivel[0] == "code")
            $conversor = new ConversorUTF8($this->args);
        else if ($this->conversivel[0] == "database")
            $conversor = new ConversorDatabase($this->args);

        $this->conversor = $conversor;
    }
}
