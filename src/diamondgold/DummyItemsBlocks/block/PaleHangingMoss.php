<?php

namespace diamondgold\DummyItemsBlocks\block;

use pocketmine\block\Block;
use pocketmine\block\Flowable;
use pocketmine\block\utils\StaticSupportTrait;
use pocketmine\block\utils\SupportType;
use pocketmine\data\runtime\RuntimeDataDescriber;
use pocketmine\item\Item;
use pocketmine\math\Facing;
use pocketmine\math\Vector3;
use pocketmine\player\Player;
use pocketmine\world\BlockTransaction;

class PaleHangingMoss extends Flowable
{
    use StaticSupportTrait;

    protected bool $tip = true;

    protected function describeBlockOnlyState(RuntimeDataDescriber $w): void
    {
        $w->bool($this->tip);
    }

    public function isTip(): bool
    {
        return $this->tip;
    }

    public function setTip(bool $tip): self
    {
        $this->tip = $tip;
        return $this;
    }

    public function place(BlockTransaction $tx, Item $item, Block $blockReplace, Block $blockClicked, int $face, Vector3 $clickVector, ?Player $player = null): bool
    {
        $this->tip = !$blockReplace->getSide(Facing::DOWN)->hasSameTypeId($this);
        $above = $blockReplace->getSide(Facing::UP);
        if ($above instanceof self && $above->hasSameTypeId($this)) {
            $tx->addBlock($above->getPosition(), (clone $above)->setTip(false));
        }
        return parent::place($tx, $item, $blockReplace, $blockClicked, $face, $clickVector, $player);
    }

    public function onNearbyBlockChange(): void
    {
        if (!$this->canBeSupportedAt($this)) {
            $this->position->getWorld()->useBreakOn($this->position);
            return;
        }

        $tip = !$this->getSide(Facing::DOWN)->hasSameTypeId($this);
        if ($tip !== $this->tip) {
            $this->position->getWorld()->setBlock($this->position, $this->setTip($tip));
        }
    }

    private function canBeSupportedAt(Block $block): bool
    {
        $supportBlock = $block->getSide(Facing::UP);
        return $supportBlock->getSupportType(Facing::DOWN) === SupportType::FULL || $supportBlock->hasSameTypeId($this);
    }
}
