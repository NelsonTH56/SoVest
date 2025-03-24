<?php

namespace App\Models\Traits;

trait ValidationTrait
{
    /**
     * Validation errors
     * 
     * @var array
     */
    protected $errors = [];
    
    /**
     * Get validation rules
     * Override this in your model to set specific rules
     * 
     * @return array
     */
    protected function getValidationRules()
    {
        return [];
    }
    
    /**
     * Get custom validation messages
     * Override this in your model to set specific messages
     * 
     * @return array
     */
    protected function getValidationMessages()
    {
        return [];
    }

    /**
     * Validate all attributes based on the defined rules
     * 
     * @return boolean
     */
    public function validate()
    {
        $this->clearErrors();
        $valid = true;

        foreach ($this->getValidationRules() as $attribute => $rules) {
            if (!$this->validateAttribute($attribute, $this->{$attribute})) {
                $valid = false;
            }
        }

        return $valid;
    }

    /**
     * Validate a single attribute
     * 
     * @param string $attribute Attribute name
     * @param mixed $value Attribute value
     * @return boolean
     */
    public function validateAttribute($attribute, $value)
    {
        $rules = $this->getValidationRules();
        if (!isset($rules[$attribute])) {
            return true;
        }

        $validationRules = $this->getValidationRules();
        $rules = is_array($validationRules[$attribute]) 
               ? $validationRules[$attribute] 
               : explode('|', $validationRules[$attribute]);

        foreach ($rules as $rule) {
            $parameters = [];
            
            // Check if rule has parameters
            if (is_string($rule) && str_contains($rule, ':')) {
                list($rule, $parameterStr) = explode(':', $rule, 2);
                $parameters = explode(',', $parameterStr);
            }

            $method = 'validate' . ucfirst($rule);

            if (method_exists($this, $method)) {
                if (!$this->{$method}($attribute, $value, $parameters)) {
                    return false;
                }
            } else {
                $this->addError($attribute, "Validation rule '$rule' not found");
                return false;
            }
        }

        return true;
    }

    /**
     * Validate required field
     * 
     * @param string $attribute
     * @param mixed $value
     * @param array $parameters
     * @return boolean
     */
    public function validateRequired($attribute, $value, $parameters = [])
    {
        if (empty($value) && $value !== 0 && $value !== '0') {
            $this->addError($attribute, $this->getMessage($attribute, 'required', 'The :attribute field is required.'));
            return false;
        }
        
        return true;
    }

    /**
     * Validate numeric field
     * 
     * @param string $attribute
     * @param mixed $value
     * @param array $parameters
     * @return boolean
     */
    public function validateNumeric($attribute, $value, $parameters = [])
    {
        if (!empty($value) && !is_numeric($value)) {
            $this->addError($attribute, $this->getMessage($attribute, 'numeric', 'The :attribute must be a number.'));
            return false;
        }
        
        return true;
    }

    /**
     * Validate email field
     * 
     * @param string $attribute
     * @param mixed $value
     * @param array $parameters
     * @return boolean
     */
    public function validateEmail($attribute, $value, $parameters = [])
    {
        if (!empty($value) && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
            $this->addError($attribute, $this->getMessage($attribute, 'email', 'The :attribute must be a valid email address.'));
            return false;
        }
        
        return true;
    }

    /**
     * Validate minimum length
     * 
     * @param string $attribute
     * @param mixed $value
     * @param array $parameters
     * @return boolean
     */
    public function validateMin($attribute, $value, $parameters = [])
    {
        $length = isset($parameters[0]) ? (int)$parameters[0] : 0;
        
        if (!empty($value) && mb_strlen($value) < $length) {
            $this->addError($attribute, $this->getMessage($attribute, 'min', "The :attribute must be at least $length characters."));
            return false;
        }
        
        return true;
    }

    /**
     * Validate maximum length
     * 
     * @param string $attribute
     * @param mixed $value
     * @param array $parameters
     * @return boolean
     */
    public function validateMax($attribute, $value, $parameters = [])
    {
        $length = isset($parameters[0]) ? (int)$parameters[0] : 0;
        
        if (!empty($value) && mb_strlen($value) > $length) {
            $this->addError($attribute, $this->getMessage($attribute, 'max', "The :attribute may not be greater than $length characters."));
            return false;
        }
        
        return true;
    }

    /**
     * Validate date
     * 
     * @param string $attribute
     * @param mixed $value
     * @param array $parameters
     * @return boolean
     */
    public function validateDate($attribute, $value, $parameters = [])
    {
        if (!empty($value)) {
            $date = date_parse($value);
            if ($date['error_count'] > 0 || !checkdate($date['month'], $date['day'], $date['year'])) {
                $this->addError($attribute, $this->getMessage($attribute, 'date', 'The :attribute is not a valid date.'));
                return false;
            }
        }
        
        return true;
    }

    /**
     * Validate a value is in a list of values
     * 
     * @param string $attribute
     * @param mixed $value
     * @param array $parameters Valid values
     * @return boolean
     */
    public function validateIn($attribute, $value, $parameters = [])
    {
        if (!empty($value) && !in_array($value, $parameters)) {
            $validValues = implode(', ', $parameters);
            $this->addError($attribute, $this->getMessage($attribute, 'in', "The :attribute must be one of the following: $validValues."));
            return false;
        }
        
        return true;
    }

    /**
     * Validate against a regular expression
     * 
     * @param string $attribute
     * @param mixed $value
     * @param array $parameters [pattern]
     * @return boolean
     */
    public function validateRegex($attribute, $value, $parameters = [])
    {
        if (!empty($value) && isset($parameters[0])) {
            if (!preg_match($parameters[0], $value)) {
                $this->addError($attribute, $this->getMessage($attribute, 'regex', 'The :attribute format is invalid.'));
                return false;
            }
        }
        
        return true;
    }

    /**
     * Validate uniqueness of the value in the database
     * This is a placeholder - actual implementation would need database access
     * 
     * @param string $attribute
     * @param mixed $value
     * @param array $parameters [table, column, except_id]
     * @return boolean
     */
    public function validateUnique($attribute, $value, $parameters = [])
    {
        // This would need to be implemented with actual database access
        // For now, just placeholder functionality
        
        return true;
    }

    /**
     * Add an error message for an attribute
     * 
     * @param string $attribute
     * @param string $message
     */
    public function addError($attribute, $message)
    {
        $this->errors[$attribute][] = str_replace(':attribute', $attribute, $message);
    }

    /**
     * Get all validation errors
     * 
     * @return array
     */
    public function getErrors()
    {
        return $this->errors;
    }

    /**
     * Get validation errors for a specific attribute
     * 
     * @param string $attribute
     * @return array
     */
    public function getErrorsFor($attribute)
    {
        return $this->errors[$attribute] ?? [];
    }

    /**
     * Check if the model has any validation errors
     * 
     * @return boolean
     */
    public function hasErrors()
    {
        return !empty($this->errors);
    }

    /**
     * Clear all validation errors
     */
    public function clearErrors()
    {
        $this->errors = [];
    }

    /**
     * Get custom message for a validation rule
     * 
     * @param string $attribute
     * @param string $rule
     * @param string $default
     * @return string
     */
    protected function getMessage($attribute, $rule, $default)
    {
        $messages = $this->getValidationMessages();
        if (isset($messages["$attribute.$rule"])) {
            return $messages["$attribute.$rule"];
        }
        
        if (isset($messages[$rule])) {
            return $messages[$rule];
        }
        
        return $default;
    }

    /**
     * Validate a model and save if valid
     * 
     * @return boolean
     */
    public function validateAndSave()
    {
        if ($this->validate()) {
            return $this->save();
        }
        
        return false;
    }
}