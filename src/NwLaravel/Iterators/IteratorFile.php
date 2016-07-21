<?php

namespace NwLaravel\Iterators;

/**
 * Efetua a leitura de um arquivo, implementa o iterator para percorrer as linhas do arquivo
 */
class IteratorFile extends AbstractIteratorFile
{
    /**
     * Make Row Current
     *
     * @return string
     */
    public function makeCurrent()
    {
        return $this->getLine();
    }
}
