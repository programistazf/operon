<?php

declare(strict_types=1);

use App\Kernel;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Dotenv\Dotenv;

require dirname(__DIR__).'/vendor/autoload.php';

if (method_exists(Dotenv::class, 'bootEnv')) {
    (new Dotenv())->bootEnv(dirname(__DIR__).'/.env');
}

if ($_SERVER['APP_DEBUG']) {
    umask(0000);
}

$kernel = new Kernel('test', true);
$kernel->boot();
$application = new Application($kernel);
$application->setAutoExit(false);

foreach ([
    ['command' => 'doctrine:database:create', '--if-not-exists' => true],
    ['command' => 'doctrine:schema:drop', '--force' => true, '--full-database' => true],
    ['command' => 'doctrine:migrations:migrate', '--allow-no-migration' => true],
    ['command' => 'doctrine:fixtures:load', '--purge-with-truncate' => true],
] as $command) {
    $input = new ArrayInput($command);
    $input->setInteractive(false);
    if (0 !== $application->run($input, new ConsoleOutput(ConsoleOutput::VERBOSITY_QUIET))) {
        throw new RuntimeException(sprintf('Test database setup failed at "%s".', $command['command']));
    }
}

$kernel->shutdown();
