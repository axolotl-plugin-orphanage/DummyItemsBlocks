<?php

namespace diamondgold\DummyItemsBlocks\block;

use pocketmine\block\utils\SupportType;
use pocketmine\math\AxisAlignedBB;

class CopperGolemStatue extends CardinalFacingBlock
{
    /** @return AxisAlignedBB[] */
    protected function recalculateCollisionBoxes(): array
    {
        return [new AxisAlignedBB(3 / 16, 0, 3 / 16, 13 / 16, 14 / 16, 13 / 16)];
    }

    public function getSupportType(int $facing): SupportType
    {
        return SupportType::CENTER;
    }
}
