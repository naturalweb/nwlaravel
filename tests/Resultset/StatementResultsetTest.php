<?php

namespace Tests\Resultset;

use PHPUnit_Framework_TestCase;
use NwLaravel\Resultset\StatementResultset;

class StatementResultsetTest extends PHPUnit_Framework_TestCase
{
    public function testConstruct()
    {
        $statement = $this->getMock('PDOStatement');
        $resultSet = new StatementResultset($statement);

        $this->assertInstanceOf('NwLaravel\Resultset\AbstractResultset', $resultSet);
        $this->assertAttributeSame($statement, 'statement', $resultSet);
    }
}
