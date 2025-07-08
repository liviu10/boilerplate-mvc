<?php

namespace LiviuVoica\BoilerplateMVC\Models;

class User
{
    private string $test;

    public function testMethod(): string
    {
        $this->test = '999';

        return "Response from User model: $this->test.";
    }
}