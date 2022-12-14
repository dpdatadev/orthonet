<?php

declare(strict_types=1);

require_once '../lib/database.php';

use App\Database\ExtendedPDO;

$config = require '../config/settings.php';

$postgresPDOImplementation = new ExtendedPDO($config['postgres']['dsn'], $config['postgres']['username'], $config['postgres']['password'], $config['postgres']['opts']);

return [
    'pdoPostgres' => $postgresPDOImplementation
];