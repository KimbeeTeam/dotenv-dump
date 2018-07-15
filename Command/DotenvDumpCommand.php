<?php

namespace KimbeeTeam\DotenvDump\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Dotenv\Dotenv;

class DotenvDumpCommand extends ContainerAwareCommand
{
    protected static $defaultName = 'dotenv:dump';
    const MARKER = '.env';

    private $projectDir;

    public function __construct($projectDir)
    {
        $this->projectDir = $projectDir;
        parent::__construct();
    }


    protected function configure()
    {
        $this
            ->setDescription('Dumps environment variables from `.env` to `.htaccess` or plain PHP-file.')
            ->addArgument('output_file', InputArgument::OPTIONAL, 'Where to store results', $this->projectDir . DIRECTORY_SEPARATOR . '.htaccess')
            ->addArgument('env_file', InputArgument::OPTIONAL, 'Your `.env` file', $this->projectDir . DIRECTORY_SEPARATOR . '.env')
            ->addOption('htaccess', null, InputOption::VALUE_NONE, 'Dump as .htaccess')
            ->addOption('php', null, InputOption::VALUE_NONE, 'Dump as php-file');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);

        if (!class_exists(Dotenv::class)) {
            throw new \RuntimeException('You need "symfony/dotenv" as a Composer dependency to load variables from a .env file.');
        }
        $envFile = $input->getArgument('env_file');
        if (!file_exists($envFile)) {
            $io->error('There is no such .env file.');
            return;
        }
        $outputFile = $input->getArgument('output_file');
        if (file_exists($outputFile) && !is_writable($outputFile)) {
            $io->error('Output file is not writable.');
            return;
        }
        $io->comment(sprintf('Reading environment variables from %s', $envFile));
        $env = (new Dotenv())->parse(file_get_contents($envFile));

        $outputData = '';
        if ($input->getOption('php')) {
            $outputData = '<?php return ' . var_export($env, true) . ';';
        } else {
            if (file_exists($outputFile)) {
                $outputData = file_get_contents($outputFile);
                if ($outputData === false) {
                    $io->error('Output file already exist and is not available for reading.');
                    return;
                }
                $io->comment(sprintf('Removing environment variables from %s', $outputFile));
                $outputData = preg_replace(sprintf('{###> %s ###.*###< %s ###%s?}s', self::MARKER, self::MARKER, "\n"), '', $outputData, -1, $count);
            }

            $multilineWarning = false;
            $data = '';
            foreach ($env as $key => $value) {
                $key = addcslashes($key, "\"\\");
                $value = addcslashes($value,"\"\\");
                $value = str_replace("\n", "\\\n", $value, $count);
                $multilineWarning |= $count > 0;
                $data .= 'SetEnv "' . $key . '" "' . $value . "\"\n";
            }
            if ($multilineWarning) {
                $io->caution('Multiline environment variables are not supported by Apache. Please review output file.');
            }

            $outputData = sprintf('###> %s ###%s%s%s###< %s ###%s', self::MARKER, "\n", rtrim($data, "\n"), "\n", self::MARKER, "\n") . $outputData;
        }

        if (file_put_contents($outputFile, $outputData) === false) {
            $io->error('Can\'t write output file.');
        }

        if ($input->getOption('php') && ('cli' !== PHP_SAPI || ini_get('opcache.enable_cli'))) {
            @opcache_invalidate($outputFile, true);
        }

        $io->success(sprintf('Dumped environment variables to %s', $outputFile));

    }

}