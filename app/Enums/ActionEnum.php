<?php

declare(strict_types=1);

namespace App\Enums;

enum ActionEnum: string
{
    case FETCH_CRATE = 'fetch_crate';
    case RETURN_CRATE = 'return_crate';
    case PICK = 'pick';
    case DELIVER = 'deliver';
    case PACKAGE = 'package';
    case IDLE = 'idle';
}
