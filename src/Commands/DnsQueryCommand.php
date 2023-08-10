<?php

namespace Jinomial\LaravelDns\Commands;

use Illuminate\Console\Command;
use Jinomial\LaravelDns\Facades\Dns;

class DnsQueryCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'dns:query
        {name : The domain name to resolve}
        {type=A : The record type to lookup}';

    /**
     * The console command description.
     */
    protected $description = 'Perform a DNS lookup';

    /**
     * Execute the console command.
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
