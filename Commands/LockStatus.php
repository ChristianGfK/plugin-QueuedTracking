<?php
/**
 * Piwik - free/libre analytics platform
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */

namespace Piwik\Plugins\QueuedTracking\Commands;

use Piwik\Access;
use Piwik\Option;
use Piwik\Plugin;
use Piwik\Plugin\ConsoleCommand;
use Piwik\Plugins\QueuedTracking\Queue;
use Piwik\Plugins\QueuedTracking\Queue\Processor;
use Piwik\Plugins\QueuedTracking\SystemCheck;
use Piwik\Tracker;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class LockStatus extends ConsoleCommand
{

    protected function configure()
    {
        $this->setName('queuedtracking:lock-status');
        $this->setDescription('Outputs information for the status of each locked queue. Unlocking a queue is possible as well.');
        $this->addOption('unlock', null, InputOption::VALUE_REQUIRED, 'If set will unlock the given queue.');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $systemCheck = new SystemCheck();
        $systemCheck->checkRedisIsInstalled();

        $backend = Queue\Factory::makeBackend();
        $lock    = Queue\Factory::makeLock($backend);
        $keys    = $lock->getAllAcquiredLockKeys();

        $keyToUnlock = $input->getOption('unlock');

        if ($keyToUnlock && in_array($keyToUnlock, $keys)) {
            $backend->delete($keyToUnlock);
            $this->writeSuccessMessage($output, array(sprintf('Key %s unlocked', $keyToUnlock)));
        } elseif ($keyToUnlock) {
            $output->writeln(sprintf('<error>%s is not or no longer locked</error>', $keyToUnlock));
            $output->writeln(' ');
        }

        foreach ($keys as $lockKey) {
            $time = $backend->getTimeToLive($lockKey);
            $output->writeln(sprintf('"%s" is locked for <comment>%d ms</comment>', $lockKey, $time));
            $output->writeln(sprintf('Set option <comment>--unlock=%s</comment> to unlock the queue.', $lockKey));
            $output->writeln(' ');
        }
    }
}
