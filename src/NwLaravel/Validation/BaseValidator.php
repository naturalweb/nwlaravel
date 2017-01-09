<?php
namespace NwLaravel\Validation;

use Illuminate\Validation\Factory;
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
     * Construct
     *
     * @param \Illuminate\Validation\Factory $validator
     */
    public function __construct(Factory $validator)
    {
        $this->validator = $validator;
        $this->rules = array_merge_recursive($this->rules, (array) $this->makeRules());
    }

    /**
     * MakeRules
     *
     * @return array
     */
    protected function makeRules()
    {
        return [];
    }

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
     * @param string $keyName
     *
     * @return BaseValidator
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
     * @param string|null $action
     *
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
     * @param array    $rules
     * @param int|null $id
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
                list($name, $params) = array_pad(explode(":", $rule), 2, null);

                // only do someting for the unique rule
                if (strtolower($name) != "unique") {
                    if (preg_match('/\[(.*)\]/', $params, $matches)) {
                        if (array_key_exists($matches[1], $this->data)) {
                            $params = str_replace("[".$matches[1]."]", $this->getValue($matches[1]), $params);
                            $rules[$ruleIdx] = $name.":".$params;
                        }
                    }
                    continue; // continue in foreach loop, nothing left to do here
                }

                $p = array_map("trim", explode(",", $params));

                // set field name to rules key ($field) (laravel convention)
                if (!isset($p[1])) {
                    $p[1] = $field;
                }

                // set 3rd parameter to id given to getValidationRules()
                if (!isset($p[2]) || empty($p[2])) {
                    $p[2] = $id;
                }

                if ($this->keyName && (!isset($p[3]) || empty($p[3]))) {
                    $p[3] = $this->keyName;
                }

                $params = implode(",", $p);
                $rules[$ruleIdx] = $name.":".$params;
            }
        });

        return $rules;
    }

    /**
     * Get the value of a given attribute.
     *
     * @param  string  $attribute
     * @return mixed
     */
    protected function getValue($attribute)
    {
        if (! is_null($value = array_get($this->data, $attribute))) {
            return $value;
        }
    }
}
