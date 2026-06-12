<?php

declare(strict_types=1);

namespace ZipStream;

final class Version
{
    public const STORE = 0x000A; // 1.00
    public const DEFLATE = 0x0014; // 2.00
    public const ZIP64 = 0x002D; // 4.50
}
