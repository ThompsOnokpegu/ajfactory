<?php

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| Bind the Laravel TestCase (and a fresh database) to every Feature test so
| function-style Pest tests get the HTTP/auth helpers and a migrated schema.
|
*/

uses(
    Tests\TestCase::class,
    Illuminate\Foundation\Testing\RefreshDatabase::class,
)->in('Feature');
