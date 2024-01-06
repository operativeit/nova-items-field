<?php

namespace NovaItemsField;

use Closure;
use Illuminate\Contracts\Validation\Rule;
use Illuminate\Support\Facades\Validator;

class ArrayRules implements Rule
{
    public $rules = [];

    public function __construct(array $rules)
    {
        $this->rules = $rules;
    }

    protected function getValidationAttribute($attribute)
    {
        return str_replace('.', '=>', $attribute);
    }

    protected function getErrorAttribute($validationAttribute, $errorAttribute)
    {
        return preg_replace(
            '/'.$validationAttribute.'\.?/',
            '',
            str_replace('=>', '.', $errorAttribute)
        );
    }

    protected function getRules($attribute): array
    {
        $rules = [];
        foreach ($this->rules as $attr => $rule) {
            if (empty($rule)) {
                continue;
            }
            if ($attr === $attribute.'.*') {
                $rules[$attr] = $rule;
                continue;
            }
            if (empty($attr) || $attr === $attribute) {
                $rules[$attribute] = $rule;
                continue;
            }
            if (strpos($attr, $attribute.'.') === 0) {
                continue;
            }

            $rules[$attribute.'.'.$attr] = $rule;
        }
        return $rules;
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value)
    {
        $validationAttribute = $this->getValidationAttribute($attribute);
        $input = [$validationAttribute => json_decode($value)];
        $validator = Validator::make($input, $this->getRules($validationAttribute), [], ["{$validationAttribute}" => 'list', "{$validationAttribute}.*" => 'input']);
        $errors = [];
        foreach ($validator->errors()->toArray() as $attr => $error) {
            $errors[$this->getErrorAttribute($attribute, $attr)] = $error;
        }
        $this->message = json_encode($errors);

        return $validator->passes();
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return $this->message;
    }

    
}
