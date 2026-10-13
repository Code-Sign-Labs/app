<?php
declare(strict_types=1);
namespace Framework\Addons;

interface AddonInterface
{
    public function __construct(AddonsManager $addonsManager);

    public function initialize(): void;
}