<?php

namespace diamondgold\DummyItemsBlocks\block;

use pocketmine\block\Block;
use pocketmine\block\BlockTypeTags;
use pocketmine\block\Flowable;
use pocketmine\block\utils\StaticSupportTrait;
use pocketmine\math\AxisAlignedBB;
use pocketmine\math\Facing;

class TallDryGrass extends Flowable
{
    use StaticSupportTrait;

    /** @return AxisAlignedBB[] */
    protected function recalculateCollisionBoxes(): array
    {
        return [new AxisAlignedBB(1 / 16, 0, 1 / 16, 15 / 16, 1, 15 / 16)];
    }

    private function canBeSupportedAt(Block $block): bool
    {
        $supportBlock = $block->getSide(Facing::DOWN);
        return $supportBlock->hasTypeTag(BlockTypeTags::DIRT) || $supportBlock->hasTypeTag(BlockTypeTags::MUD);
    }

    public function getFlameEncouragement(): int
    {
        return 60;
    }

    public function getFlammability(): int
    {
        return 100;
    }
}
