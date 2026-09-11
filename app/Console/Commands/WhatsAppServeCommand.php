<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Process\Process;

class WhatsAppServeCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'whatsapp:serve {--port=3333 : The port to serve the WhatsApp bridge on}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Start the local Node.js WhatsApp Multi-Device bridge service for Food-POS';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $port = (int) $this->option('port');
        $serviceDir = base_path('whatsapp-service');
        $serverScript = $serviceDir.DIRECTORY_SEPARATOR.'server.js';

        if (! file_exists($serverScript)) {
            $this->error("WhatsApp server script not found at: {$serverScript}");

            return Command::FAILURE;
        }

        $this->info("Starting Food-POS WhatsApp Bridge on http://127.0.0.1:{$port}...");
        $this->comment('Press Ctrl+C to stop the service.');

        $process = new Process(['node', 'server.js'], $serviceDir, [
            'PORT' => (string) $port,
        ]);
        $process->setTimeout(null);

        $process->run(function ($type, $buffer) {
            $this->output->write($buffer);
        });

        return Command::SUCCESS;
    }
}
