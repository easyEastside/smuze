<?php

test('agent release command registers version and computes checksum', function () {
    $dir = storage_path('app/agent');
    $versionPath = $dir.'/version.json';
    $binaryPath = $dir.'/smuze-agent';

    $originalVersion = file_exists($versionPath) ? file_get_contents($versionPath) : null;
    $originalBinary = file_exists($binaryPath) ? file_get_contents($binaryPath) : null;

    try {
        $tmpBinary = tempnam(sys_get_temp_dir(), 'smuze-agent');
        file_put_contents($tmpBinary, 'fake-binary-content');

        $this->artisan('agent:release', [
            '--release-version' => '0.2.0',
            '--binary' => $tmpBinary,
        ])->assertSuccessful();

        expect(file_exists($versionPath))->toBeTrue();

        $versionData = json_decode(file_get_contents($versionPath), true);
        expect($versionData['version'])->toBe('0.2.0')
            ->and($versionData['checksum'])->not->toBe('');

        $expectedChecksum = hash_file('sha256', $tmpBinary);
        expect($versionData['checksum'])->toBe($expectedChecksum);

        expect(file_exists($binaryPath))->toBeTrue()
            ->and(file_get_contents($binaryPath))->toBe('fake-binary-content');
    } finally {
        if (file_exists($tmpBinary ?? '')) {
            unlink($tmpBinary);
        }

        // Restore originals
        if ($originalVersion !== null) {
            file_put_contents($versionPath, $originalVersion);
        } elseif (file_exists($versionPath) && ! isset($originalVersion)) {
            unlink($versionPath);
        }

        if ($originalBinary !== null) {
            file_put_contents($binaryPath, $originalBinary);
        } elseif (isset($originalBinary) && $originalBinary === null && file_exists($binaryPath)) {
            unlink($binaryPath);
        }
    }
});

test('agent release command requires release-version option', function () {
    $this->artisan('agent:release')
        ->assertFailed();
});

test('agent release command fails on missing binary', function () {
    $this->artisan('agent:release', [
        '--release-version' => '0.2.0',
        '--binary' => '/nonexistent/path',
    ])->assertFailed();
});

test('agent release command can build versioned binary', function () {
    $dir = storage_path('app/agent');
    $versionPath = $dir.'/version.json';
    $binaryPath = $dir.'/smuze-agent';

    $originalVersion = file_exists($versionPath) ? file_get_contents($versionPath) : null;
    $originalBinary = file_exists($binaryPath) ? file_get_contents($binaryPath) : null;

    try {
        $this->artisan('agent:release', [
            '--release-version' => '9.9.9',
            '--build' => true,
        ])->assertSuccessful();

        $checkBinary = tempnam(sys_get_temp_dir(), 'smuze-agent-check');
        copy($binaryPath, $checkBinary);
        chmod($checkBinary, 0755);

        expect(shell_exec($checkBinary.' --version'))->toBe("9.9.9\n");
    } finally {
        if (isset($checkBinary) && file_exists($checkBinary)) {
            unlink($checkBinary);
        }

        if ($originalVersion !== null) {
            file_put_contents($versionPath, $originalVersion);
        } elseif (file_exists($versionPath)) {
            unlink($versionPath);
        }

        if ($originalBinary !== null) {
            file_put_contents($binaryPath, $originalBinary);
            chmod($binaryPath, 0755);
        } elseif (file_exists($binaryPath)) {
            unlink($binaryPath);
        }

        $buildPath = storage_path('app/agent/smuze-agent.build');
        if (file_exists($buildPath)) {
            unlink($buildPath);
        }
    }
});
