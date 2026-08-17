<?php

namespace diamondgold\DummyItemsBlocks\block;

use diamondgold\DummyItemsBlocks\util\Utils;
use pocketmine\block\Transparent;
use pocketmine\block\utils\FacesOppositePlacingPlayerTrait;
use pocketmine\block\utils\SupportType;
use pocketmine\math\AxisAlignedBB;
use pocketmine\math\Facing;
use pocketmine\data\runtime\RuntimeDataDescriber;

class Shelf extends Transparent
{
    use FacesOppositePlacingPlayerTrait {
        describeBlockOnlyState as describeFacing;
    }

    protected bool $powered = false;
    protected int $poweredShelfType = 0;

    protected function describeBlockOnlyState(RuntimeDataDescriber $w): void
    {
        $this->describeFacing($w);
        $w->bool($this->powered);
        $w->boundedIntAuto(0, 3, $this->poweredShelfType);
    }

    public function isPowered(): bool
    {
        return $this->powered;
    }

    public function getPoweredShelfType(): int
    {
        return $this->poweredShelfType;
    }

    public function setPowered(bool $powered): self
    {
        $this->powered = $powered;
        return $this;
    }

    public function setPoweredShelfType(int $poweredShelfType): self
    {
        Utils::checkWithinBounds($poweredShelfType, 0, 3);
        $this->poweredShelfType = $poweredShelfType;
        return $this;
    }

    /** @return AxisAlignedBB[] */
    protected function recalculateCollisionBoxes(): array
    {
        return match ($this->facing) {
            Facing::NORTH => [
                new AxisAlignedBB(0, 12 / 16, 11 / 16, 1, 1, 13 / 16),
                new AxisAlignedBB(0, 0, 13 / 16, 1, 1, 1),
                new AxisAlignedBB(0, 0, 11 / 16, 1, 4 / 16, 13 / 16),
            ],
            Facing::EAST => [
                new AxisAlignedBB(3 / 16, 12 / 16, 0, 5 / 16, 1, 1),
                new AxisAlignedBB(0, 0, 0, 3 / 16, 1, 1),
                new AxisAlignedBB(3 / 16, 0, 0, 5 / 16, 4 / 16, 1),
            ],
            Facing::SOUTH => [
                new AxisAlignedBB(0, 12 / 16, 3 / 16, 1, 1, 5 / 16),
                new AxisAlignedBB(0, 0, 0, 1, 1, 3 / 16),
                new AxisAlignedBB(0, 0, 3 / 16, 1, 4 / 16, 5 / 16),
            ],
            Facing::WEST => [
                new AxisAlignedBB(11 / 16, 12 / 16, 0, 13 / 16, 1, 1),
                new AxisAlignedBB(13 / 16, 0, 0, 1, 1, 1),
                new AxisAlignedBB(11 / 16, 0, 0, 13 / 16, 4 / 16, 1),
            ],
        };
    }

    public function getSupportType(int $facing): SupportType
    {
        return SupportType::NONE;
    }
}
