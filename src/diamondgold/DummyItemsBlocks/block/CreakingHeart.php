<?php

namespace diamondgold\DummyItemsBlocks\block;

use diamondgold\DummyItemsBlocks\block\enum\CreakingHeartState;
use pocketmine\block\Opaque;
use pocketmine\block\utils\PillarRotationTrait;
use pocketmine\data\runtime\RuntimeDataDescriber;

class CreakingHeart extends Opaque
{
    use PillarRotationTrait {
        describeBlockOnlyState as describeAxis;
    }

    protected CreakingHeartState $state = CreakingHeartState::DORMANT;
    protected bool $natural = false;

    protected function describeBlockOnlyState(RuntimeDataDescriber $w): void
    {
        $this->describeAxis($w);
        $w->enum($this->state);
        $w->bool($this->natural);
    }

    public function getState(): CreakingHeartState
    {
        return $this->state;
    }

    public function isNatural(): bool
    {
        return $this->natural;
    }

    public function setState(CreakingHeartState $state): self
    {
        $this->state = $state;
        return $this;
    }

    public function setNatural(bool $natural): self
    {
        $this->natural = $natural;
        return $this;
    }
}
