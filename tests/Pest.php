<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind different classes or traits.
|
*/

pest()->extend(TestCase::class)
 // ->use(RefreshDatabase::class)
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

/**
 * Save the current agent version.json content for later restoration.
 */
function saveVersionFile(): ?string
{
    $path = storage_path('app/agent/version.json');

    return file_exists($path) ? file_get_contents($path) : null;
}

/**
 * Restore a previously saved version.json.
 */
function restoreVersionFile(?string $content): void
{
    $path = storage_path('app/agent/version.json');

    if ($content !== null) {
        file_put_contents($path, $content);
    } elseif (file_exists($path)) {
        unlink($path);
    }
}

/**
 * Write a version.json with the given version and checksum.
 */
function writeVersionFile(string $version, string $checksum): void
{
    $dir = storage_path('app/agent');
    if (! is_dir($dir)) {
        mkdir($dir, 0755, true);
    }

    file_put_contents($dir.'/version.json', json_encode([
        'version' => $version,
        'checksum' => $checksum,
    ]));
}
