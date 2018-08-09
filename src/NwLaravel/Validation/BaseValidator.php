<?php
namespace NwLaravel\Validation;

use Illuminate\Validation\Factory;
use Prettus\Validator\AbstractValidator;
use Illuminate\Validation\Rules\Unique;
use Illuminate\Validation\Rule;

/**
 * Class BaseValidator
 * @abstract
 */
abstract class BaseValidator extends AbstractValidator
{
    /**
     * Validator
     *
     * @var \Illuminate\Validation\Factory
     */
    protected $validator;

    /**
     * @var string
     */
    protected $keyName;

    /**
     * @var array
     */
    protected $messages = [];

    /**
     * @var array
     */
    protected $attributes = [];

    /**
     * Construct
     *
     * @param \Illuminate\Validation\Factory $validator
     */
    public function __construct(Factory $validator)
    {
        $this->validator = $validator;
        $this->rules = array_merge_recursive((array) $this->rules, $this->makeRules());
        $this->messages = array_merge_recursive((array) $this->messages, $this->makeMessages());
        $this->attributes = array_merge_recursive((array) $this->attributes, $this->makeAttributes());
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
     * Make Messages
     *
     * @return array
     */
    protected function makeMessages()
    {
        return [];
    }

    /**
     * Make Attributes
     *
     * @return array
     */
    protected function makeAttributes()
    {
        return [];
    }

    /**
     * Get Messages
     *
     * @return array
     */
    public function getMessages()
    {
        return $this->messages;
    }

    /**
     * Get Attributes
     *
     * @return array
     */
    public function getAttributes()
    {
        return $this->attributes;
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
     * Pass the data and the rules to the validator
     *
     * @param string $action
     * @return bool
     */
    public function passes($action = null)
    {
        $rules      = $this->getRules($action);
        $messages   = $this->getMessages();
        $attributes = $this->getAttributes();
        $validator  = $this->validator->make($this->data, $rules, $messages, $attributes);

        if ($validator->fails()) {
            $this->errors = $validator->messages();
            return false;
        }

        return true;
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
        array_walk($rules, function (&$rules, $field) use ($id) {
            if (!is_array($rules)) {
                $rules = explode("|", $rules);
            }

            foreach ($rules as $ruleIdx => $rule) {
                $rule = $this->replaceValuesRules($rule);

                $itens = array_pad(explode(":", $rule), 2, null);
                $nameRule = isset($itens[0]) ? $itens[0] : null;
                $params = implode(":", array_splice($itens, 1));

                if ($nameRule != "unique") {
                    $rule = $nameRule;
                    if (!empty($params)) {
                        $rule .= ":".$params;
                    }
                    $rules[$ruleIdx] = $rule;
                    continue;
                }

                // Atualiza Rule Unique
                $p = array_map("trim", explode(",", $params));

                $table = $p[0];

                // set field name to rules key ($field) (laravel convention)
                if (isset($p[1]) && !empty($p[1])) {
                    $field = $p[1];
                }

                // set 3rd parameter to id given to getValidationRules()
                if (isset($p[2]) && !empty($p[2]) && strtoupper($p[2]) != 'NULL') {
                    $id = intval($p[2]);
                }

                if (isset($p[3]) && !empty($p[3])) {
                    $keyName = $p[3];
                } else {
                    $keyName = 'id';
                }

                if (! $rule instanceof Unique) {
                    $rule = Rule::unique($table, $field);
                }

                if (!empty($id) || $id == '0') {
                    $rule->ignore($id, $keyName);
                }

                // Extra Conditions
                if (isset($p[4])) {
                    $extra = $this->getExtraConditions(array_slice($p, 4));
                    foreach ($extra as $key => $value) {
                        if (strtoupper($value) == 'NULL' || is_null($value)) {
                            $rule->whereNull($key);
                        } else {
                            $rule->where($key, $value);
                        }
                    }
                }

                $rules[$ruleIdx] = $rule;
            }
        });

        return $rules;
    }

    /**
     * Get the extra conditions for a unique / exists rule.
     *
     * @param  array  $segments
     * @return array
     */
    protected function getExtraConditions(array $segments)
    {
        $extra = [];

        $count = count($segments);

        for ($i = 0; $i < $count; $i += 2) {
            $extra[$segments[$i]] = isset($segments[$i + 1]) ? $segments[$i + 1] : null;
        }

        return $extra;
    }

    /**
     * Replace Values Rules
     *
     * @param string $rule
     *
     * @return string
     */
    protected function replaceValuesRules($rule)
    {
        $x = 0;
        while (preg_match('/\[([A-Za-z0-9_]+)\]/', $rule, $match)) {
            $x++;
            $field = $match[1];
            $value = 'NULL';
            if (array_key_exists($field, $this->data)) {
                $value = $this->getValue($field);
            }

            $rule = str_replace("[{$field}]", $value, $rule);
            if ($x>10) {
                break;
            }
        }

        return $rule;
    }

    /**
     * Get the value of a given attribute.
     *
     * @param string $attribute
     *
     * @return mixed
     */
    protected function getValue($attribute)
    {
        $value = array_get($this->data, $attribute);

        return is_null($value) ? 'NULL' : $value;
    }
}
