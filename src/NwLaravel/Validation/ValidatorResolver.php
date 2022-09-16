<?php

namespace NwLaravel\Validation;

use ReflectionClass;
use Illuminate\Validation\Validator;
use Illuminate\Support\Arr;

/**
 * Class ValidatorResolver
 *
 * @method bool validateCpf(string $attribute, mixed $value, array $parameters)
 * @method bool validateCnpj(string $attribute, mixed $value, array $parameters)
 */
class ValidatorResolver extends Validator
{

    protected $currentRule;

    /**
     * The validation rules that imply the field is required.
     *
     * @var array
     */
    protected $implicitRules = [
        'Required', 'Filled', 'RequiredWith', 'RequiredWithAll', 'RequiredWithout', 'RequiredWithoutAll',
        'RequiredIf', 'RequiredUnless', 'Accepted', 'Present', 'RequiredIfAll', 'RequiredUnlessAll',
    ];

    /**
     * Validate Pattern Valid
     *
     * @param string $attribute  String Attribute
     * @param string $value      String Value
     * @param array  $parameters Array Parameters
     *
     * @return boolean
     */
    public function validatePattern($attribute, $value, $parameters = array())
    {
        return (bool) (@preg_match($value, "subject") !== false);
    }

    /**
     * Validate Current Password
     *
     * @param string $attribute  String Attribute
     * @param mixed  $value      Mixed Value
     * @param array  $parameters Array Parameters
     *
     * @return bool
     */
    public function validateCurrentPassword($attribute, $value, $parameters = array())
    {
        $guard = isset($parameters[0]) ? $parameters[0] : null;
        $field = isset($parameters[1]) ? $parameters[1] : 'password';
        return password_verify($value, auth($guard)->user()->{$field});
    }

    /**
     * Validate that an attribute is cpf valid
     *
     * @param string $attribute String Attribute
     * @param mixed  $value     Mixed Value
     * @param array  $parameters Array Parameters
     *
     * @return bool
     */
    public function validateDocument($attribute, $value, $parameters = array())
    {
        $value = preg_replace('/[^0-9]/', '', $value);
        if (strlen($value) == 11) {
            return $this->validateCpf($attribute, $value, $parameters);
        }

        return $this->validateCnpj($attribute, $value, $parameters);
    }

    /**
     * Validate currency
     *
     * @param string $attribute String Attribute
     * @param mixed  $value     Mixed Value
     * @param array  $parameters Array Parameters
     *
     * @return bool
     */
    public function validateCurrency($attribute, $value, $parameters = array())
    {
        return !is_null(asCurrency($value));
    }

    /**
     * Validate the not existence of an attribute value in a database table.
     *
     * @param  string  $attribute
     * @param  mixed   $value
     * @param  array   $parameters
     * @return bool
     */
    public function validateNotExists($attribute, $value, $parameters)
    {
        return !$this->validateExists($attribute, $value, $parameters);
    }

   /**
     * Validate that an attribute exists when another attribute has a given value.
     *
     * @param  string  $attribute
     * @param  mixed   $value
     * @param  mixed   $parameters
     * @return bool
     */
    public function validateRequiredIf($attribute, $value, $parameters)
    {
        $this->requireParameterCount(2, $parameters, 'required_if');

        $data = Arr::get($this->data, $parameters[0]);

        $values = array_slice($parameters, 1);

        if (is_bool($data)) {
            array_walk($values, function (&$value) {
                if ($value === 'true') {
                    $value = true;
                } elseif ($value === 'false') {
                    $value = false;
                }
            });
        }

        if (in_array($data, $values)) {
            return $this->validateRequired($attribute, $value);
        }

        $this->addRules([$attribute => ['nullable']]);

        return true;
    }

    /**
     * Validate that an attribute exists when another attribute does not have a given value.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @param  mixed  $parameters
     * @return bool
     */
    public function validateRequiredUnless($attribute, $value, $parameters)
    {
        $this->requireParameterCount(2, $parameters, 'required_unless');

        $data = Arr::get($this->data, $parameters[0]);

        $values = array_slice($parameters, 1);

        if (! in_array($data, $values)) {
            return $this->validateRequired($attribute, $value);
        }

        $this->addRules([$attribute => ['nullable']]);

        return true;
    }

    /**
     * Validate that an attribute exists when another attribute has a given value.
     *
     * @param  string  $attribute
     * @param  mixed   $value
     * @param  mixed   $parameters
     * @return bool
     */
    public function validateRequiredIfAll($attribute, $value, $parameters)
    {
        $this->requireParameterCount(2, $parameters, 'required_if_all');

        $valid = true;
        $count = count($parameters);

        for ($i = 0; $i < $count; $i += 2) {
            $field = $parameters[$i];
            $fieldValue = $parameters[$i + 1];

            $data = $this->getValue($field);
            if (is_bool($data)) {
                if (strtolower($fieldValue) === 'true') {
                    $fieldValue = true;
                } elseif (strtolower($fieldValue) === 'false') {
                    $fieldValue = false;
                }
            }

            if ($fieldValue != $data) {
                $valid = false;
                break;
            }
        }

        if ($valid) {
            return $this->validateRequired($attribute, $value);
        }

        $this->addRules([$attribute => ['nullable']]);

        return true;
    }

    /**
     * Validate that an attribute exists when another attribute does not have a given value.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @param  mixed  $parameters
     * @return bool
     */
    public function validateRequiredUnlessAll($attribute, $value, $parameters)
    {
        $this->requireParameterCount(2, $parameters, 'required_unless_all');

        $valid = true;
        $count = count($parameters);

        for ($i = 0; $i < $count; $i += 2) {
            $field = $parameters[$i];
            $fieldValue = $parameters[$i + 1];

            if ($fieldValue == $this->getValue($field)) {
                $valid = false;
                break;
            }
        }

        if ($valid) {
            return $this->validateRequired($attribute, $value);
        }

        $this->addRules([$attribute => ['nullable']]);

        return true;
    }

    /**
     * Replace all place-holders for the after rule.
     *
     * @param  string  $message
     * @param  string  $attribute
     * @param  string  $rule
     * @param  array   $parameters
     * @return string
     */
    public function replaceBeforeOrEqual($message, $attribute, $rule, $parameters)
    {
        return $this->replaceBefore($message, $attribute, $rule, $parameters);
    }

    /**
     * Replace all place-holders for the after rule.
     *
     * @param  string  $message
     * @param  string  $attribute
     * @param  string  $rule
     * @param  array   $parameters
     * @return string
     */
    public function replaceAfterOrEqual($message, $attribute, $rule, $parameters)
    {
        return $this->replaceAfter($message, $attribute, $rule, $parameters);
    }

    /**
     * {@inheritdoc}
     */
    public function __call($method, $parameters)
    {
        if (preg_match('/^validate([A-Z][a-z][a-zA-Z]*)$/', $method, $match) && count($parameters) >= 2) {
            $className = 'Respect\\Validation\\Rules\\'.ucfirst($match[1]);

            if (class_exists($className)) {
                $reflection = new ReflectionClass($className);
                if (!$reflection->isAbstract() && $reflection->isSubclassOf('Respect\\Validation\\Validatable')) {
                    $arguments = (array) (isset($parameters[2]) ? $parameters[2] : []);
                    $instance = $reflection->newInstanceArgs($arguments);
                    return $instance->validate($parameters[1]);
                }
            }
        }

        return call_user_func_array(array('parent', $method), $parameters);
    }
}
