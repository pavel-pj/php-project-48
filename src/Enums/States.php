<?php

namespace Hexlet\Code\Enums;

enum States: string
{
    case Deleted = 'deleted';
    case Added = 'added';
    case Matched = 'matched';
}
