<?php

namespace Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use JMac\Testing\Traits\AdditionalAssertions;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class FormRequestTestCase extends TestCase
{
    use RefreshDatabase;

    private $validator;


    protected function setUp(): void
    {
        parent::setUp();
        $this->validator = $this->app['validator'];
    }



    protected function getFieldValidator($field, $value, $rules)
    {
        return $this->validator->make(
            [$field => $value],
            [$field => $rules[$field]]
        );
    }

    protected function validateField($field, $value, $rules)
    {
        return $this->getFieldValidator($field, $value, $rules)->passes();
    }
}
