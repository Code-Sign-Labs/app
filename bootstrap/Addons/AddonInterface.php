<?php

namespace Framework\Addons;

interface AddonInterface
{
    public function __construct(AddonsManager $addonsManager);

    public function initialize(): void;
}