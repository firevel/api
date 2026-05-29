<?php

namespace Firevel\Api\Tests;

use Firevel\Api\ServiceProvider as ApiServiceProvider;
use Firevel\Includes\ServiceProvider as IncludesServiceProvider;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Orchestra\Testbench\TestCase as BaseTestCase;
use Spatie\Fractal\FractalServiceProvider;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('posts', function (Blueprint $table) {
            $table->increments('id');
            $table->string('title')->nullable();
            $table->timestamps();
        });

        Schema::create('comments', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('post_id');
            $table->string('body')->nullable();
            $table->string('status')->nullable();
            $table->boolean('visible')->default(true);
            $table->timestamps();
        });

        Schema::create('videos', function (Blueprint $table) {
            $table->increments('id');
            $table->string('title')->nullable();
            $table->string('status')->nullable();
            $table->boolean('visible')->default(true);
            $table->timestamps();
        });

        Schema::create('activities', function (Blueprint $table) {
            $table->increments('id');
            $table->nullableMorphs('subject');
            $table->timestamps();
        });
    }

    protected function getPackageProviders($app)
    {
        return [ApiServiceProvider::class, IncludesServiceProvider::class, FractalServiceProvider::class];
    }

    protected function defineEnvironment($app)
    {
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
    }
}
