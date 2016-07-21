<?php

namespace NwLaravel\Iterators;

/**
 * Library Result Csv
 */
class IteratorFileCsv extends AbstractIteratorFile implements IteratorInterface
{
    /**
     * @var array|null
     */
    protected $headers;

    /**
     * @var string
     */
    protected $delimiter;

    /**
     * @var string
     */
    protected $enclosure;

    /**
     * @var string|null
     */
    protected $escape;

    /**
     * @var array
     */
    protected $defaults = array();

    /**
     * @var array
     */
    protected $replace = array();

    const DELIMITER_DEFAULT = ';';
    const ENCLOSURE_DEFAULT = '"';

   /**
     * Abre o arquivo para leitura e define a variaveis
     *
     * @param string $fileName  File Name
     * @param string $headers   Headers [optional]
     * @param string $delimiter Separator Fields [optional]
     * @param string $enclosure Enclosure Fields [optional]
     * @param string $escape    String de Escape [optional]
     *
     * @throws \RuntimeException
     */
    public function __construct(
        $fileName,
        array $headers = array(),
        $delimiter = null,
        $enclosure = null,
        $escape = null
    ) {
        parent::__construct($fileName);

        $this->setHeaders($headers);
        $this->delimiter = (string) !is_null($delimiter) ? $delimiter : self::DELIMITER_DEFAULT;
        $this->enclosure = $enclosure ?: self::ENCLOSURE_DEFAULT;
        $this->escape    = $escape ? $escape : null;
    }

    /**
     * Line to array
     *
     * @return array|null
     */
    private function rowArray()
    {
        $row = null;
        $line = $this->getLine();

        if ($line !== false && !is_null($line)) {
            $row = str_getcsv($line, $this->delimiter, $this->enclosure, $this->escape);
            $row = array_map("trim", $row);
        }

        return $row;
    }

    /**
     * Make Row Current
     *
     * @return array|bool
     */
    public function makeCurrent()
    {
        $row = $this->rowArray();
        if (is_null($row)) {
            return false;
        }

        $validateRow = array_filter($row, function ($value) {
            return ($value === '0') ? true : !empty($value);
        });

        if (!count($validateRow)) {
            return (array) $validateRow; // Vazio
        }

        $headers = $this->getHeaders();
        $count_headers = count($headers);

        // Falta valores, Existe mais Headers
        if ($count_headers > count($row)) {
            $row = array_pad($row, $count_headers, "");

        } else {
            // Sobrando valores, Existe mais valores
            $row = array_slice($row, 0, $count_headers);
        }

        $row = array_combine($headers, $row);
        $row = array_merge($this->defaults, $row, $this->replace);

        return $row;
    }

    /**
     * Conta as linhas pulando o cabeçalho, se existir
     *
     * @return integer
     */
    public function count()
    {
        if (is_null($this->count)) {
            $this->count = parent::count();
            $this->count -= 1;
        }

        return $this->count;
    }

    /**
     * Rewind na segunda linha
     *
     * @see FileIterator::rewind()
     * @return void
     */
    public function rewind()
    {
        parent::rewind();
        $this->key = 1;
        $this->next(); // Pular Cabeçalho
    }

    /**
     * Retorna o headers tendo como chave a posicao das colunas da planilha
     *
     * @return array
     */
    public function getHeaders()
    {
        if (is_null($this->headers)) {
            $tell = ftell($this->fileHandle);
            fseek($this->fileHandle, 0);
            $row = $this->rowArray();
            $headers = array();

            if (is_array($row)) {
                $headers = array_map(function ($title) {
                    return strtolower(str_slug($title, '_'));
                }, $row);
            }

            fseek($this->fileHandle, $tell);
            $this->headers = $headers;
        }

        return $this->headers;
    }

    /**
     * Set Headers
     *
     * @param array $headers Headers
     *
     * @return IteratorFileCsv
     */
    public function setHeaders(array $headers)
    {
        $this->headers = count($headers) ? $headers : null;
        return $this;
    }

    /**
     * Set Fields Defaults
     *
     * @param array $defaults Defaults
     *
     * @return void
     */
    public function setDefaults(array $defaults)
    {
        $this->defaults = $defaults;
        return $this;
    }

    /**
     * Set Fields Replace
     *
     * @param array $replace Replaces
     *
     * @return void
     */
    public function setReplace(array $replace)
    {
        $this->replace = $replace;
        return $this;
    }
}
