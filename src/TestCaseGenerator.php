<?php

namespace AjayMakwana\LaravelTestGenerator;

use Faker;
use Faker\Generator;

class TestCaseGenerator
{
    protected Generator $faker;

    protected array|string $params;

    protected array $cases;

    protected array|string $rules;


    public function __construct()
    {
        $this->faker = Faker\Factory::create();
        $this->cases = [];
    }

    public function generate(array|string $rules): array
    {
        $this->params = array_keys($rules);
        $this->rules = array_values($rules);
        return $this->generateCase();
    }


    protected function generateCase(): array
    {
        $this->generateFailureCase();
        $this->generateSuccessCase();
        return $this->cases;
    }


    protected function generateSuccessCase(): void
    {
        $case = [];
        foreach ($this->params as $key => $val) {
            $case[$val] = $this->getValue(is_string($val) ? $val : strval($val), $this->rules[$key]);
        }

        $this->cases['success'] = $case;
    }


    protected function getValue(array|string $param, array|string $rules): string
    {
        if (is_string($rules)) {
            $rules = explode('|', $rules);
        }
        $value = '';

        switch ($rules) {
            case $this->isEmail($rules):
                $value = $this->faker->email;
                break;
            case $this->isCompanyName($rules, $param):
                $value = $this->faker->company;
                break;
            case $this->isAddress($rules, $param):
                $value = $this->faker->address;
                break;
            case $this->isName($rules, $param):
                $value = $this->faker->name;
                break;
            case $this->isStreetName($rules, $param):
                $value = $this->faker->streetName;
                break;
            case $this->isStreetAddress($rules, $param):
                $value = $this->faker->streetAddress;
                break;
            case $this->isCity($rules, $param):
                $value = $this->faker->city;
                break;
            case $this->isState($rules, $param):
                $value = $this->faker->state;
                break;
            case $this->isCountry($rules, $param):
                $value = $this->faker->country;
                break;
            case $this->isZip($rules, $param):
                $value = $this->faker->postcode;
                break;
            case $this->isLatitude($param):
                $value = $this->faker->latitude;
                break;
            case $this->isLongitude($param):
                $value = $this->faker->longitude;
                break;
            case $this->isPhone($param):
                $value = $this->faker->e164PhoneNumber;
                break;
            case $this->isBoolean($rules):
                $value = rand(0, 1);
                break;
            case $this->isDate($rules):
                $value = $this->faker->date;
                break;
            case $this->isDateFormat($rules):
                $format = array_values(array_filter($rules, function ($val) {
                    return preg_match('/^date_format/', $val);
                }));
                $format = str_replace('date_format:', '', $format[0]);
                $value = $this->faker->date($format);
                break;
        }

        return $value;
    }


    protected function isEmail(array|string $rules): bool
    {
        return in_array('email', $rules);
    }


    protected function isCompanyName(array|string $rules, array|string $param): bool
    {
        return str_contains('company', $param) !== false && in_array('string', $rules);
    }


    protected function isAddress(array|string $rules, array|string $param): bool
    {
        return str_contains('address', $param) !== false && in_array('string', $rules);
    }


    protected function isName(array|string $rules, array|string $param): bool
    {
        return str_contains('name', $param) !== false && in_array('string', $rules);
    }


    protected function isStreetName(array|string $rules, array|string $param): bool
    {
        return str_contains('street', $param) !== false && in_array('string', $rules);
    }


    protected function isStreetAddress(array|string $rules, array|string $param): bool
    {
        return str_contains('street_address', $param) !== false && in_array('string', $rules);
    }


    protected function isCity(array|string $rules, array|string $param): bool
    {
        return str_contains('city', $param) !== false && in_array('string', $rules);
    }


    protected function isState(array|string $rules, array|string $param): bool
    {
        return str_contains('state', $param) !== false && in_array('string', $rules);
    }


    protected function isCountry(array|string $rules, array|string $param): bool
    {
        return str_contains('country', $param) !== false && in_array('string', $rules);
    }


    protected function isZip(array|string $rules, array|string $param): bool
    {
        return (str_contains('zip', $param) !== false || str_contains('pin', $param) !== false) && in_array('string', $rules);
    }


    protected function isLatitude(array|string $param): bool
    {
        return str_contains('latitude', $param) !== false;
    }


    protected function isLongitude(array|string $param): bool
    {
        return str_contains('longitude', $param) !== false;
    }


    protected function isPhone(array|string $param): bool
    {
        return str_contains('phone', $param) || str_contains('mobile', $param) !== false;
    }


    protected function isBoolean(array|string $rules): bool
    {
        return in_array('boolean', $rules);
    }


    protected function isDate(array|string $rules): bool
    {
        return in_array('date', $rules);
    }


    protected function isDateFormat(array|string $rules): bool
    {
        $format = array_filter($rules, function ($val) {
            if(is_string($val)) {
                return preg_match('/^date_format/', $val);
            }
            return false;
        });
        return count($format);
    }

    /**
     * Generate failure test case
     *
     * @return void
     */
    protected function generateFailureCase()
    {
        $this->cases['failure'] = array_fill_keys($this->params, '');
    }

}
