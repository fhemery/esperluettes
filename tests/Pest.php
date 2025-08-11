<?php

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

// Root tests directory
uses(TestCase::class)->in('Feature');
uses(TestCase::class)->in('Unit');

// Domain tests directories
uses(TestCase::class, RefreshDatabase::class)->in('app/Domains/*/Tests/Feature');
uses(TestCase::class)->in('app/Domains/*/Tests/Unit');
