<?php
namespace NwLaravel\Validation;

use Prettus\Validator\LaravelValidator;

/**
 * Class BaseValidator
 * @abstract
 */
abstract class BaseValidator extends LaravelValidator
{
    /**
     * @var string
     */
    protected $keyName;

    /**
     * Get Validator
     *
     * @return \Illuminate\Validation\Factory
     */
    public function getValidator()
    {
        return $this->validator;
    }

    /**
     * Set Key Name
     *
     * @param Key $keyName Key Name
     *
     * @return $this
     */
    public function setKeyName($keyName)
    {
        $this->keyName = $keyName;
    }

    /**
     * Get rule for validation by action ValidatorInterface::RULE_CREATE or ValidatorInterface::RULE_UPDATE
     *
     * Default rule: ValidatorInterface::RULE_CREATE
     *
     * @param null $action
     * @return array
     */
    public function getRules($action = null)
    {
        $rules = [];

        if (isset($this->rules[$action])) {
            $rules = $this->rules[$action];
        }

        return $this->parserValidationRules($rules, $this->id);
    }

    /**
     * Parser Validation Rules
     *
     * @param Unknown $rules Rules
     * @param null    $id    Null Id
     *
     * @return array
     */
    protected function parserValidationRules($rules, $id = null)
    {

        if ($id === null) {
            return $rules;
        }

        array_walk($rules, function (&$rules, $field) use ($id) {
            if (!is_array($rules)) {
                $rules = explode("|", $rules);
            }

            foreach ($rules as $ruleIdx => $rule) {
                // get name and parameters
                @list($name, $params) = array_pad(explode(":", $rule), 2, null);

                // only do someting for the unique rule
                if (strtolower($name) != "unique") {
                    continue; // continue in foreach loop, nothing left to do here
                }

                $p = array_map("trim", explode(",", $params));

                // set field name to rules key ($field) (laravel convention)
                if (!isset($p[1])) {
                    $p[1] = $field;
                }

                // set 3rd parameter to id given to getValidationRules()
                $p[2] = $id;
                if ($this->keyName) {
                    $p[3] = $this->keyName;
                }

                $params = implode(",", $p);
                $rules[$ruleIdx] = $name.":".$params;
            }
        });

        return $rules;
    }
}
