<?php

declare(strict_types=1);

namespace EMS\CommonBundle\Command\Admin;

use EMS\CommonBundle\Common\Admin\AdminHelper;
use EMS\CommonBundle\Common\Command\AbstractCommand;
use EMS\CommonBundle\Contracts\CoreApi\Exception\NotAuthenticatedExceptionInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;

class LoginCommand extends AbstractCommand
{
    private const ARG_BASE_URL = 'base-url';
    private const OPTION_USERNAME = 'username';
    private const OPTION_PASSWORD = 'password';
    private AdminHelper $adminHelper;
    private ?string $backendUrl;

    public function __construct(AdminHelper $adminHelper, ?string $backendUrl)
    {
        parent::__construct();
        $this->adminHelper = $adminHelper;
        $this->backendUrl = $backendUrl;
    }

    protected function configure(): void
    {
        parent::configure();
        $this
            ->addArgument(self::ARG_BASE_URL, InputArgument::OPTIONAL, 'Elasticms base url (default: EMS_BACKEND_URL)')
            ->addOption(self::OPTION_USERNAME, 'u', InputOption::VALUE_REQUIRED, 'username')
            ->addOption(self::OPTION_PASSWORD, 'p', InputOption::VALUE_REQUIRED, 'password')
        ;
    }

    protected function interact(InputInterface $input, OutputInterface $output): void
    {
        if (null === $this->backendUrl) {
            $this->backendUrl = \strval($this->io->askQuestion(new Question('Elasticm\'s URL')));
        }

        if (null === $input->getOption(self::OPTION_USERNAME)) {
            $input->setOption(self::OPTION_USERNAME, $this->io->askQuestion(new Question('Username')));
        }

        if (null === $input->getOption(self::OPTION_PASSWORD)) {
            $input->setOption(self::OPTION_PASSWORD, $this->io->askHidden('Password'));
        }
    }

    public function initialize(InputInterface $input, OutputInterface $output): void
    {
        parent::initialize($input, $output);
        $baseUrl = $this->getArgumentStringNull(self::ARG_BASE_URL);
        if (null !== $baseUrl) {
            $this->backendUrl = $baseUrl;
        }
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->io->title('Admin - login');
        if (null === $this->backendUrl) {
            throw new \RuntimeException('Backend\'s URL not defined');
        }

        try {
            $coreApi = $this->adminHelper->login(
                $this->backendUrl,
                $this->getOptionString(self::OPTION_USERNAME),
                $this->getOptionString(self::OPTION_PASSWORD)
            );
        } catch (NotAuthenticatedExceptionInterface $e) {
            $this->io->error('Invalid credentials!');

            return self::EXECUTE_ERROR;
        } catch (\Throwable $e) {
            $this->io->error($e->getMessage());

            return self::EXECUTE_ERROR;
        }
        $profile = $coreApi->user()->getProfileAuthenticated();
        $this->io->success(\sprintf('Welcome %s on %s', $profile->getUsername(), $this->adminHelper->getCoreApi()->getBaseUrl()));

        return self::EXECUTE_SUCCESS;
    }
}
