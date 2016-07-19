<?php

namespace Tests\Testing;

use Tests\TestCase;
use NwLaravel\Testing\WebAssertTrait;

class TestStub
{
    use WebAssertTrait;

    protected function protegido($param1, $param2)
    {
        return [$param1 => $param2];
    }
}
