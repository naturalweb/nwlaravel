<?php

namespace NwLaravel\Repositories\Resultset;

use PDOStatement;

/**
 * Class StatementResultset
 */
class StatementResultset extends AbstractResultset
{
    /**
     * Initialize
     *
     * @param PDOStatement $statement PDO Statement
     */
    public function __construct(PDOStatement $statement)
    {
        $this->statement = $statement;
    }
}
