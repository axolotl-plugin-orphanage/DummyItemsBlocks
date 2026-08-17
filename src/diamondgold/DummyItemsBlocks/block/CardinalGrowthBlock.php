<?php

namespace diamondgold\DummyItemsBlocks\block;

use diamondgold\DummyItemsBlocks\util\Utils;
use pocketmine\block\Block;
use pocketmine\block\BlockIdentifier;
use pocketmine\block\BlockTypeTags;
use pocketmine\block\BlockTypeInfo;
use pocketmine\block\Flowable;
use pocketmine\block\utils\FacesOppositePlacingPlayerTrait;
use pocketmine\block\utils\StaticSupportTrait;
use pocketmine\data\runtime\RuntimeDataDescriber;
use pocketmine\item\Item;
use pocketmine\math\Facing;
use pocketmine\math\Vector3;
use pocketmine\player\Player;
use pocketmine\world\BlockTransaction;

class CardinalGrowthBlock extends Flowable
{
    use FacesOppositePlacingPlayerTrait {
        describeBlockOnlyState as describeFacing;
        place as placeFacing;
    }
    use StaticSupportTrait {
        canBePlacedAt as supportedWhenPlacedAt;
    }

    protected int $value = 0;
    private bool $supportsAnyFullBlock = false;

    public function __construct(BlockIdentifier $idInfo, string $name, BlockTypeInfo $typeInfo, bool $supportsAnyFullBlock)
    {
        $this->supportsAnyFullBlock = $supportsAnyFullBlock;
        parent::__construct($idInfo, $name, $typeInfo);
    }

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

    public function canBePlacedAt(Block $blockReplace, Vector3 $clickVector, int $face, bool $isClickedBlock): bool
    {
        return ($blockReplace instanceof self && $blockReplace->hasSameTypeId($this) && $blockReplace->value < 3) ||
            ($this->canBeSupportedAt($blockReplace) && parent::canBePlacedAt($blockReplace, $clickVector, $face, $isClickedBlock));
    }

    public function place(BlockTransaction $tx, Item $item, Block $blockReplace, Block $blockClicked, int $face, Vector3 $clickVector, ?Player $player = null): bool
    {
        if ($blockReplace instanceof self && $blockReplace->hasSameTypeId($this) && $blockReplace->value < 3) {
            $this->value = $blockReplace->value + 1;
            $this->facing = $blockReplace->facing;
            return parent::place($tx, $item, $blockReplace, $blockClicked, $face, $clickVector, $player);
        }

        return $this->placeFacing($tx, $item, $blockReplace, $blockClicked, $face, $clickVector, $player);
    }

    private function canBeSupportedAt(Block $block): bool
    {
        $supportBlock = $block->getSide(Facing::DOWN);
        if ($this->supportsAnyFullBlock) {
            return $supportBlock->getSupportType(Facing::UP)->hasCenterSupport();
        }

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

    /** @return Item[] */
    public function getDropsForCompatibleTool(Item $item): array
    {
        return [$this->asItem()->setCount($this->value + 1)];
    }
}
