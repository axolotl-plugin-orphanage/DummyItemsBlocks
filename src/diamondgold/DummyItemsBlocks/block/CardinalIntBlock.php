<?php

namespace diamondgold\DummyItemsBlocks\block;

use diamondgold\DummyItemsBlocks\util\Utils;
use pocketmine\block\Opaque;
use pocketmine\block\utils\FacesOppositePlacingPlayerTrait;
use pocketmine\block\utils\SupportType;
use pocketmine\math\AxisAlignedBB;
use pocketmine\data\runtime\RuntimeDataDescriber;

class CardinalIntBlock extends Opaque
{
    use FacesOppositePlacingPlayerTrait {
        describeBlockOnlyState as describeFacing;
    }

    protected int $value = 0;

    protected function describeBlockOnlyState(RuntimeDataDescriber $w): void
    {
        $this->describeFacing($w);
        $w->boundedIntAuto(0, 3, $this->value);
    }

    public function getValue(): int
    {
        return $this->value;
    }

    public function setValue(int $value): self
    {
        Utils::checkWithinBounds($value, 0, 3);
        $this->value = $value;
        return $this;
    }

    /** @return AxisAlignedBB[] */
    protected function recalculateCollisionBoxes(): array
    {
        return [new AxisAlignedBB(3 / 16, 0, 3 / 16, 13 / 16, 10 / 16, 13 / 16)];
    }

    public function getSupportType(int $facing): SupportType
    {
        return SupportType::CENTER;
    }
}
