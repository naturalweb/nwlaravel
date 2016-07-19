<?php

namespace NwLaravel\Iterators;

/**
 * Efetua a leitura de um arquivo, iplementa o iterator para percorrer as linhas do arquivo
 */
class IteratorFile implements \Iterator, \Countable
{
    /**
     * @var string
     */
    protected $fileName;

    /**
     * @var resource
     */
    protected $fileHandle;

    /**
     * @var string
     */
    protected $lineCurrent;

    /**
     * @var int
     */
    protected $key;

    /**
     * @var int
     */
    protected $count;

    /**
     * Abre o arquivo para leitura
     *
     * @param string $fileName Path
     *
     * @throws \RuntimeException
     */
    public function __construct($fileName)
    {
        $this->fileName = (string) $fileName;

        if (!file_exists($fileName) || !$this->fileHandle = fopen($fileName, 'r')) {
            throw new \RuntimeException('Couldn\'t open file "' . $fileName . '"');
        }
    }

    /**
     * Contagem de linhas
     *
     * @return int
     */
    public function count()
    {
        if (is_null($this->count)) {
            $this->count = 0;

            // Salva a posição do ponteiro de leitura
            $tell = ftell($this->fileHandle);
            rewind($this->fileHandle);

            while (!feof($this->fileHandle) && false !== fgets($this->fileHandle)) {
                $this->count += 1;
            }

            // Restabelece a posição do ponteiro
            fseek($this->fileHandle, $tell);
        }

        return $this->count;
    }

    /**
     * Inicia a leitura do arquivo do inicio
     *
     * @return void
     */
    public function rewind()
    {
        rewind($this->fileHandle);
        $this->key = -1;
        $this->next();
    }

    /**
     * Retorna o indice atual
     *
     * @return int
     */
    public function key()
    {
        return $this->key;
    }

    /**
     * Valida se havera proxima linha
     *
     * @return bool
     */
    public function valid()
    {
        return $this->lineCurrent !== false;
    }

    /**
     * Retorna a linha atual
     *
     * @return string
     */
    public function current()
    {
        return $this->lineCurrent;
    }

    /**
     * Busca a proxima linha
     *
     * @return void
     */
    public function next()
    {
        $this->lineCurrent = $this->getLine();
        $this->key++;
    }

    /**
     * Le alinha no arquivo, formata o encodig caso seja necessario
     *
     * @return string
     */
    protected function getLine()
    {
        $encoding = 'UTF-8';
        $line = false;

        if (! feof($this->fileHandle)) {
            $line = fgets($this->fileHandle);

            if ($line !== false) {
                $line = trim($line);
                if (mb_detect_encoding($line, $encoding, true) != $encoding) {
                    $line = utf8_encode($line);
                }
            }
        }

        return $line;
    }

    /**
     * Ao destruir fecha o arquivo
     *
     * @return void
     */
    public function __destruct()
    {
        if (is_resource($this->fileHandle)) {
            fclose($this->fileHandle);
        }
    }
}
