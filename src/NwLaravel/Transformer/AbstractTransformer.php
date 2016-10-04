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
