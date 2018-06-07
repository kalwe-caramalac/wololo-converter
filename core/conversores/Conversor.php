<?php

namespace Wololo\Conversor;

abstract class Conversor
{
    abstract public function preparaConversao();
    abstract public function executeConversionProcedures($args);
}

interface Conversor {
    public function preparaConversao();
    public function executeConversionProcedures($args);
}
