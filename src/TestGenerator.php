<?php

namespace Vigneshc91\LaravelTestGenerator;

use Illuminate\Console\Command;

class TestGenerator extends Command
{

    protected $signature = 'laravel-test:generate
                            {--filter= : Filter to a specific route prefix, such as /api or /v2/api}
                            {--sync= : Whether @depends attribute to be added to each function inside the test file}';


    protected $description = 'Automatically generates unit test cases for this application';


    public function __construct()
    {
        parent::__construct();
    }

    public function handle(): void
    {
        $options = [
            'sync'      => (bool)$this->option('sync'),
            'filter'    => $this->option('filter'),
        ];
        $generator = new Generator($options);
        $generator->generate();
    }
}
