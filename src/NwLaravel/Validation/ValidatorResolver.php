<?php

namespace NwLaravel\Validation;

use ReflectionClass;
use Illuminate\Validation\Validator;

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
                    $valided = app($className, $arguments)->validate($parameters[1]);
                }
            }
        }

        if (isset($valided)) {
            return $valided;
        }

        return parent::__call($method, $parameters);
    }
}
