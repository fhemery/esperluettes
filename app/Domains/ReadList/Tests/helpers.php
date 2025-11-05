<?php

use Illuminate\Testing\TestResponse;
use Tests\TestCase;

function addToReadList(TestCase $t, int $storyId): TestResponse
{
    return $t->post(route('readlist.add', $storyId));
}
