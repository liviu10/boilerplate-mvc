<?php

namespace LiviuVoica\BoilerplateMVC\Core;

/**
 * Class Validation
 * 
 * Provides a generic validation mechanism to validate payload data
 * against a set of rules. Validation logic and error message generation
 * are separated for better maintainability.
 */
class Validation
{
    /**
     * Validates a set of fields in the provided payload based on specified validation rules.
     *
     * @param array $rules An associative array of validation rules.
     * @param array $payload The data to be validated.
     * @return array An associative array where keys are field names and values are arrays of failed validation rules.
     */
    public function validate(array $rules, array $payload): array
    {
        $errors = [];

        foreach ($rules as $field => $constraints) {
            if (in_array('sometimes', $constraints, true) && !array_key_exists($field, $payload)) {
                continue;
            }

            $value = $payload[$field] ?? null;

            foreach ($constraints as $rule) {
                if ($rule === 'sometimes') {
                    continue;
                }

                switch (true) {
                    case $rule === 'required':
                        if ($value === null || $value === '') {
                            $errors[$field][] = $rule;
                        }
                        break;

                    case $rule === 'null':
                        if ($value === null || $value === '') {
                            continue 2; // skip other validations for this field
                        }
                        break;

                    case $rule === 'string':
                        if ($value !== null && !is_string($value)) {
                            $errors[$field][] = $rule;
                        }
                        break;

                    case str_starts_with($rule, 'max:'):
                        $max = (int)substr($rule, 4);
                        if ($value !== null && (is_string($value) || is_numeric($value)) && mb_strlen((string)$value) > $max) {
                            $errors[$field][] = $rule;
                        }
                        break;

                    case str_starts_with($rule, 'min:'):
                        $min = (int)substr($rule, 4);
                        if ($value !== null && (is_string($value) || is_numeric($value)) && mb_strlen((string)$value) < $min) {
                            $errors[$field][] = $rule;
                        }
                        break;

                    case $rule === 'integer':
                        if ($value !== null && (!is_numeric($value) || intval($value) != $value)) {
                            $errors[$field][] = $rule;
                        }
                        break;

                    case $rule === 'boolean':
                        if ($value !== null && !is_bool($value) && !in_array($value, [0,1,'0','1'], true)) {
                            $errors[$field][] = $rule;
                        }
                        break;

                    case $rule === 'numeric':
                        if ($value !== null && !is_numeric($value)) {
                            $errors[$field][] = $rule;
                        }
                        break;

                    case $rule === 'email':
                        if ($value !== null && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                            $errors[$field][] = $rule;
                        }
                        break;

                    case $rule === 'date':
                        if ($value !== null && strtotime($value) === false) {
                            $errors[$field][] = $rule;
                        }
                        break;

                    case str_starts_with($rule, 'in:'):
                        $allowed = explode(',', substr($rule, 3));
                        if ($value !== null && !in_array($value, $allowed, true)) {
                            $errors[$field][] = $rule;
                        }
                        break;

                    case $rule === 'url':
                        if ($value !== null && !filter_var($value, FILTER_VALIDATE_URL)) {
                            $errors[$field][] = $rule;
                        }
                        break;
                }
            }
        }

        return $errors;
    }

    /**
     * Returns the error message corresponding to the failed validation rule for a given field.
     *
     * @param string $field The field name.
     * @param string $rule The failed validation rule.
     * @return string The error message for the rule.
     */
    public function messages(string $field, string $rule): string
    {
        switch (true) {
            case $rule === 'required':
                return ucfirst($field) . ' is required.';

            case $rule === 'string':
                return ucfirst($field) . ' must be a string.';

            case $rule === 'integer':
                return ucfirst($field) . ' must be an integer.';

            case $rule === 'boolean':
                return ucfirst($field) . ' must be true or false.';

            case $rule === 'numeric':
                return ucfirst($field) . ' must be a number.';

            case $rule === 'email':
                return ucfirst($field) . ' must be a valid email address.';

            case $rule === 'date':
                return ucfirst($field) . ' must be a valid date.';

            case str_starts_with($rule, 'max:'):
                $max = (int)substr($rule, 4);
                return ucfirst($field) . " must not be greater than {$max} characters.";

            case str_starts_with($rule, 'min:'):
                $min = (int)substr($rule, 4);
                return ucfirst($field) . " must be at least {$min} characters.";

            case str_starts_with($rule, 'in:'):
                $values = substr($rule, 3);
                return ucfirst($field) . " must be one of the following values: {$values}.";

            case $rule === 'url':
                return ucfirst($field) . ' must be a valid URL.';

            default:
                return "Validation error on {$field} for rule {$rule}.";
        }
    }
}
