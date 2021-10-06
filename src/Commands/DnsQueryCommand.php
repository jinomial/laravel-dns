<?php

namespace Jinomial\LaravelDns\Commands;

use Illuminate\Console\Command;
use Jinomial\LaravelDns\Facades\Dns;

class DnsQueryCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dns:query
        {name : The domain name to resolve}
        {type=A : The record type to lookup}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Perform a DNS lookup';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $name = $this->argument('name');
        $type = $this->argument('type');
        $answer = Dns::query($name, $type);
        $this->info(json_encode($answer));

        return 0;
    }
}
