<?php

namespace Framework\Mailer\ValueObjects;

class TargetsData
{
    /**
     * @var array $targets
     */
    protected array $targets = [];

    /**
     * @param Target $target
     * @return void
     */
    public function addTarget(Target $target): void
    {
        $this->targets[] = $target;
    }

    /**
     * @return array
     */
    public function getTargets(): array
    {
        return $this->targets;
    }
}