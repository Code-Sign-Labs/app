<?php

namespace Framework\Messenger;

enum MessengerEnum
{
    const STATUS_NONE = 0;
    const STATUS_IN_QUEUE = 1;
    const STATUS_IN_DELIVERY = 2;
    const STATUS_DELIVERED = 3;
    const STATUS_FAILED = 4;
}
