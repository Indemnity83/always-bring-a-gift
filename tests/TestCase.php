<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $testingRoot = storage_path('framework/testing');
        $diskRoot = storage_path('framework/testing/disks');
        if ((is_dir($testingRoot) && ! is_writable($testingRoot)) || (is_dir($diskRoot) && ! is_writable($diskRoot))) {
            $tempStorage = rtrim(sys_get_temp_dir(), '/').'/abag-storage';
            if (! is_dir($tempStorage)) {
                mkdir($tempStorage, 0777, true);
            }

            app()->useStoragePath($tempStorage);
        }

        $manifestPath = base_path('public/build/manifest.json');
        if (! file_exists($manifestPath)) {
            $directory = dirname($manifestPath);
            if (! is_dir($directory)) {
                mkdir($directory, 0777, true);
            }

            $manifest = [
                'resources/js/app.js' => [
                    'file' => 'assets/app.js',
                    'src' => 'resources/js/app.js',
                    'isEntry' => true,
                    'css' => ['assets/app.css'],
                ],
                'resources/css/app.css' => [
                    'file' => 'assets/app.css',
                    'src' => 'resources/css/app.css',
                ],
            ];

            file_put_contents(
                $manifestPath,
                json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
            );
        }

        $diskRoot = storage_path('framework/testing/disks');
        if (! is_dir($diskRoot)) {
            mkdir($diskRoot, 0777, true);
        }

        foreach (['public', 'tmp-for-tests'] as $disk) {
            $path = $diskRoot.'/'.$disk;
            if (! is_dir($path)) {
                mkdir($path, 0777, true);
            }
        }
    }
}
