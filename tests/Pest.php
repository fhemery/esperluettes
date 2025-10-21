<?php

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
// Load custom TestResponse macros (runtime + IDE stubs)
require_once __DIR__ . '/ide/TestResponseMacros.php';
// Domain-scoped test helpers
require_once __DIR__ . '/../app/Domains/Auth/Tests/helpers.php';
require_once __DIR__ . '/../app/Domains/Profile/Tests/helpers.php';
require_once __DIR__ . '/../app/Domains/Story/Tests/helpers.php';
require_once __DIR__ . '/../app/Domains/Comment/Tests/helpers.php';
require_once __DIR__ . '/../app/Domains/Admin/Tests/helpers.php';
require_once __DIR__ . '/../app/Domains/Events/Tests/helpers.php';
require_once __DIR__ . '/../app/Domains/Message/Tests/helpers.php';
require_once __DIR__ . '/../app/Domains/Shared/Tests/helpers.php';
require_once __DIR__ . '/../app/Domains/Discord/Tests/helpers.php';
require_once __DIR__ . '/../app/Domains/Config/Tests/helpers.php';
require_once __DIR__ . '/../app/Domains/Moderation/Tests/helpers.php';

// Root tests directory
uses(TestCase::class)->in('Feature');
uses(TestCase::class)->in('Unit');

// Domain tests directories
uses(TestCase::class, RefreshDatabase::class)->in('app/Domains/*/Tests/Feature');
uses(TestCase::class)->in('app/Domains/*/Tests/Unit');
