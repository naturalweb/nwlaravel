<?php

namespace NwLaravel\Testing;

use Illuminate\Contracts\View;

class ConstraintView extends \PHPUnit_Framework_Constraint
{
    protected $view;

    protected $response;

    public function __construct($response)
    {
        parent::__construct();
        $this->response = $response;
    }

    /**
     * Evaluates the constraint for parameter $other. Returns true if the
     * constraint is met, false otherwise.
     *
     * @param mixed $other Value or object to evaluate.
     *
     * @return bool
     */
    protected function matches($other)
    {
        if (! isset($this->response->original) || ! $this->response->original instanceof View) {
            return false;
        }

        $this->view = $this->response->original->name();

        return (bool) ($other == $this->view);
    }

    /**
     * Returns a string representation of the constraint.
     *
     * @return string
     */
    public function toString()
    {
        if (is_null($this->view)) {
            return "The response view not defined";

        } else {
            return sprintf(
                "The response view actual is '%s'",
                $this->view
            );
        }
    }
}
