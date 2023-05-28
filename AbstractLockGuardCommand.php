<?php

namespace App\Command;

use Monolog\Formatter\LineFormatter;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Handler\StreamHandler;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

abstract class AbstractLockGuardCommand extends Command
{
    private LoggerInterface $logger;
    private string $logPath;
    protected bool $isLocked = true;
    protected string $lockFile = '';
    public function __construct(LoggerInterface $logger = null)
    {
        $this->logger = $logger;
        $this->logPath = __DIR__ . '/../../var/log';

        parent::__construct();
    }

    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        if ($this->isLocked) {
            $lockFile = $this->logPath . '/lock/' . $this->getCurrentFileName() . '.lock';

            if (file_exists($lockFile)) {
                $this->getLogger()->warning('Another instance of the command is already running.');
                exit();
            }

            touch($lockFile);
            $this->lockFile = $lockFile;
            $this->getLogger()->info('Lock file created: ' . $lockFile);
        } else {
            $this->getLogger()->warning('Running without lock mode on');
        }

        $logFile = $this->logPath . '/command/' . $this->getCurrentFileName();

        $streamHandler = new StreamHandler('php://stderr', 100);
        $streamHandler->setFormatter(new LineFormatter(null, 'Y-m-d H:i:s'));
        $this->logger->pushHandler($streamHandler);

        $fileHandler = new RotatingFileHandler($logFile, 0, 100);
        $fileHandler->setFormatter(new LineFormatter(null, 'Y-m-d H:i:s'));
        $fileHandler->setFilenameFormat('{filename}.{date}.log', 'Y');
        $this->logger->pushHandler($fileHandler);

        parent::initialize($input, $output);
    }

    public function run(InputInterface $input, OutputInterface $output): int
    {
        try {
            $parentRun = parent::run($input, $output);
        } finally {
            if ($this->isLocked) {
                unlink($this->lockFile);
                $this->getLogger()->info('Lock file removed: ' . $this->lockFile);
            }
        }

        return $parentRun;
    }

    /**
     * @return LoggerInterface|null
     */
    public function getLogger(): ?LoggerInterface
    {
        return $this->logger;
    }

    /**
     * @return string
     */
    private function getCurrentFileName()
    {
        return basename(str_replace('\\', '/', static::class) , '.php');
    }

    public function setIsLocked(bool $isLocked)
    {
        $this->isLocked = $isLocked;
    }
}
