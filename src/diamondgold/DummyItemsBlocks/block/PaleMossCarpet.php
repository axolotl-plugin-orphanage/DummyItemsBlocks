<?php

namespace diamondgold\DummyItemsBlocks\block;

use pocketmine\block\Block;
use pocketmine\block\BlockTypeIds;
use pocketmine\block\Flowable;
use pocketmine\block\utils\SupportType;
use pocketmine\block\utils\WallConnectionType;
use pocketmine\data\runtime\RuntimeDataDescriber;
use pocketmine\item\Item;
use pocketmine\math\AxisAlignedBB;
use pocketmine\math\Facing;
use pocketmine\math\Vector3;
use pocketmine\player\Player;
use pocketmine\utils\AssumptionFailedError;
use pocketmine\world\BlockTransaction;

class PaleMossCarpet extends Flowable
{
    protected array $connections = [];
    protected bool $post = false;

    protected function describeBlockOnlyState(RuntimeDataDescriber $w): void
    {
        $w->wallConnections($this->connections);
        $w->bool($this->post);
    }

    public function getConnection(int $face): ?WallConnectionType
    {
        return $this->connections[$face] ?? null;
    }

    public function setConnection(int $face, ?WallConnectionType $type): self
    {
        if ($face !== Facing::NORTH && $face !== Facing::SOUTH && $face !== Facing::WEST && $face !== Facing::EAST) {
            throw new \InvalidArgumentException("Facing can only be north, south, west or east");
        }
        if ($type !== null) {
            $this->connections[$face] = $type;
        } else {
            unset($this->connections[$face]);
        }
        return $this;
    }

    public function isPost(): bool
    {
        return $this->post;
    }

    public function setPost(bool $post): self
    {
        $this->post = $post;
        return $this;
    }

    public function isSolid(): bool
    {
        return true;
    }

    /** @return AxisAlignedBB[] */
    protected function recalculateCollisionBoxes(): array
    {
        $boxes = [];
        if (!$this->post) {
            $boxes[] = new AxisAlignedBB(0, 0, 0, 1, 1 / 16, 1);
        }

        foreach ($this->connections as $facing => $connection) {
            $height = $connection === WallConnectionType::TALL ? 1 : 10 / 16;
            $boxes[] = match ($facing) {
                Facing::NORTH => new AxisAlignedBB(0, 0, 0, 1, $height, 1 / 16),
                Facing::EAST => new AxisAlignedBB(15 / 16, 0, 0, 1, $height, 1),
                Facing::SOUTH => new AxisAlignedBB(0, 0, 15 / 16, 1, $height, 1),
                Facing::WEST => new AxisAlignedBB(0, 0, 0, 1 / 16, $height, 1),
                default => throw new AssumptionFailedError("Invalid facing $facing"),
            };
        }

        return $boxes;
    }

    public function canBePlacedAt(Block $blockReplace, Vector3 $clickVector, int $face, bool $isClickedBlock): bool
    {
        return (
            $blockReplace->getSide(Facing::DOWN)->getTypeId() !== BlockTypeIds::AIR ||
            $blockReplace->getSide(Facing::UP)->getTypeId() !== BlockTypeIds::AIR
        ) && parent::canBePlacedAt($blockReplace, $clickVector, $face, $isClickedBlock);
    }

    public function place(BlockTransaction $tx, Item $item, Block $blockReplace, Block $blockClicked, int $face, Vector3 $clickVector, ?Player $player = null): bool
    {
        $this->post = $face === Facing::DOWN;
        return parent::place($tx, $item, $blockReplace, $blockClicked, $face, $clickVector, $player);
    }

    public function onNearbyBlockChange(): void
    {
        $supportFace = $this->post ? Facing::UP : Facing::DOWN;
        if ($this->getSide($supportFace)->getTypeId() === BlockTypeIds::AIR) {
            $this->position->getWorld()->useBreakOn($this->position);
            return;
        }

        if ($this->recalculateConnections()) {
            $this->position->getWorld()->setBlock($this->position, $this);
        }
    }

    private function recalculateConnections(): bool
    {
        $changed = false;
        foreach (Facing::HORIZONTAL as $facing) {
            $block = $this->getSide($facing);
            $connection = $block->getSupportType(Facing::opposite($facing)) === SupportType::FULL ? WallConnectionType::SHORT : null;
            if ($this->getConnection($facing) !== $connection) {
                $this->setConnection($facing, $connection);
                $changed = true;
            }
        }

        return $changed;
    }

    public function getSupportType(int $facing): SupportType
    {
        return SupportType::NONE;
    }
}
