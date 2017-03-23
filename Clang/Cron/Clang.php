<?php
namespace Clang\Clang\Cron;

class Clang {

    protected $clangApi;
    protected $logger;
    public function __construct(
        \Clang\Clang\Helper\ClangApi $clangApi,

        \Psr\Log\LoggerInterface $logger
    )
    {
        $this->clangApi             = $clangApi;

        $this->logger = $logger;
        $this->logger->info('TEST');
    }

    public function execute() {
        $this->clangApi->postCronStatus();
    }
}