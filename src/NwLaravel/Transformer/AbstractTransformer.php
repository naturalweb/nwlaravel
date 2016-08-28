<?php
namespace NwLaravel\Transformer;

use League\Fractal\TransformerAbstract;
use \DateTime;

/**
 * Class AbstractTransformer
 * @abstract
 */
abstract class AbstractTransformer extends TransformerAbstract
{
    /**
     * @var boolean
     */
    protected $includeData = false;

    /**
     * Construct
     *
     * @param boolean $includeData Boolean Include Data
     */
    public function __construct($includeData = false)
    {
        $this->includeData = $includeData;
    }

    /**
     * Has Include Data
     *
     * @return boolean
     */
    public function hasIncludeData()
    {
        return $this->includeData;
    }

    /**
     * Format Date
     *
     * @param DateTime $date   Date Time
     * @param string   $format String Format
     *
     * @return string
     */
    public function formatDate($date, $format)
    {
        if ($date instanceof DateTime) {
            return $date->format($format);
        }

        return null;
    }
}
