<?php

namespace NwLaravel\Repositories\Criterias\Filters;

class FilterSearch implements FilterInterface
{
    /**
     * @var string
     */
    protected $nameSearchable;

    /**
     * @var array
     */
    protected $searchables;

    /**
     * @var string
     */
    protected $table = '';

    /**
     * Construct
     *
     * @param string $nameSearchable
     * @param array  $searchables
     * @param string $table
     */
    public function __construct($nameSearchable, $searchables, $table = '')
    {
        $this->nameSearchable = $nameSearchable;
        $this->searchables = $searchables;
        $this->table = $table;
    }

    /**
     * Filter
     *
     * @param Query\Builder $query
     * @param int|string    $key
     * @param mixed         $value
     *
     * @return boolean
     */
    public function filter($query, $key, $value)
    {
        if ($key === $this->nameSearchable) {
            $fieldsSearchable = $this->searchables;
            $query = $query->where(function ($query) use ($fieldsSearchable, $value) {
                foreach ($fieldsSearchable as $field => $condition) {
                    if (is_numeric($field)) {
                        $field = $condition;
                        $condition = "=";
                    }

                    $condition  = trim(strtolower($condition));

                    if (!empty($value)) {
                        $search = in_array($condition, ["like", "ilike"]) ? "%{$value}%" : $value;
                        if ($field == 'id') {
                            $search = trim($search);
                            if (strlen($search) > 9) {
                                // Caso seja maior de 9 digitos
                                continue;
                            }
                            $search = intval($search);
                        }
                        $query->orWhere($this->table.'.'.$field, $condition, $search);
                    }
                }
                return $query;
            });

            return true;
        }

        return false;
    }
}
