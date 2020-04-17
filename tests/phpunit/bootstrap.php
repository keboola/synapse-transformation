<?php

declare(strict_types=1);

use Doctrine\DBAL\FetchMode;
use \Keboola\SynapseTransformation\Tests\Tools\TestConnectionFactory;

require __DIR__ . '/../../vendor/autoload.php';

// Check environment
$environments = [
    'SYNAPSE_SERVER',
    'SYNAPSE_PORT',
    'SYNAPSE_DATABASE',
    'SYNAPSE_UID',
    'SYNAPSE_PWD',
];
foreach ($environments as $environment) {
    if (empty(getenv($environment))) {
        throw new \Exception(sprintf('Missing environment var "%s".', $environment));
    }
}

// Wait for the end of the other db session.
// For tests we need clean and isolated environment,
// ... but new database cannot be created using SQL in Synapse DB.
// Synapse DB also doesn't support a lock that could be used here.
// So, we wait until no other session is active.
$maxRetries = 100;
$i = 0;
echo "boostrap.php: Waiting for the end of the other db session ...\n";
while (true) {
    $i++;
    $connection = TestConnectionFactory::createConnection();
    $activeSessions = (int) $connection
        // Select active sessions or closed in last 3 seconds
        ->query(sprintf(
            'SELECT COUNT(*) FROM sys.dm_pdw_exec_sessions WHERE login_name = SYSTEM_USER ' .
            "AND (status IN ('ACTIVE', 'IDLE') OR login_time > DATEADD(SECOND,-3, getdate()))",
        ))
        ->fetch(FetchMode::COLUMN, 0);
    $connection->close();

    if ($activeSessions === 1) {
        echo "boostrap.php: OK\n";
        break;
    } else {
        echo "boostrap.php: ... $activeSessions active sessions, waiting\n";
    }

    if ($i > $maxRetries) {
        throw new RuntimeException('boostrap.php: Timeout');
    }

    sleep(6);
}
