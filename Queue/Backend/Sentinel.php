<?php
/**
 * Piwik - free/libre analytics platform
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */
namespace Piwik\Plugins\QueuedTracking\Queue\Backend;

use Piwik\Plugins\QueuedTracking\Queue\Backend;
use Piwik\Tracker;

include_once PIWIK_INCLUDE_PATH . '/plugins/QueuedTracking/libs/credis/Client.php';
include_once PIWIK_INCLUDE_PATH . '/plugins/QueuedTracking/libs/credis/Cluster.php';
include_once PIWIK_INCLUDE_PATH . '/plugins/QueuedTracking/libs/credis/Sentinel.php';

class Sentinel extends Redis
{
    protected function connect()
    {
        $sentinelclient = new \Credis_Client($this->host, $this->port, $timeout = 2.5, $persistent = false); // Credis defaults to a timeout of 2.5 s
        $sentinel = new \Credis_Sentinel($sentinelclient);

        $name = 'top_secret_redis';
        $master = $sentinel->getMasterAddressByName($name);

        $client = new \Credis_Client($master[0], $master[1], $this->timeout, $persistent = false, $this->database, $this->password);
        $client->connect();

        $this->redis = $client;

        return true;
    }

    protected function evalScript($script, $keys, $args)
    {
        return $this->redis->eval($script, $keys, $args);
    }

}
