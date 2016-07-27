<?php

namespace Tests\Repositories\Criterias;

use NwLaravel\Entities\AbstractEntity;

class StubModel extends AbstractEntity
{
    protected $table = 'stub_table';

    public function scopeExample($query, $value)
    {
        //
    }

    public function columns()
    {
        return ['field1', 'field2', 'field3', 'field_date'];
    }

    public function getDates()
    {
        return ['field_date'];
    }
}
