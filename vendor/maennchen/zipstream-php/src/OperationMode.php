<?php

declare(strict_types=1);

namespace ZipStream;

/**
 * ZipStream execution operation modes.
 */
final class OperationMode
{
    public const NORMAL = 'NORMAL';
    public const SIMULATE_STRICT = 'SIMULATE_STRICT';
    public const SIMULATE_LAX = 'SIMULATE_LAX';
}
