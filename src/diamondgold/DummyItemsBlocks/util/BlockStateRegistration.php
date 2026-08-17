<?php

namespace diamondgold\DummyItemsBlocks\util;

use Closure;
use diamondgold\DummyItemsBlocks\block\BeeHive;
use diamondgold\DummyItemsBlocks\block\BubbleColumn;
use diamondgold\DummyItemsBlocks\block\CalibratedSculkSensor;
use diamondgold\DummyItemsBlocks\block\CardinalFacingBlock;
use diamondgold\DummyItemsBlocks\block\CardinalGrowthBlock;
use diamondgold\DummyItemsBlocks\block\CardinalIntBlock;
use diamondgold\DummyItemsBlocks\block\CherrySapling;
use diamondgold\DummyItemsBlocks\block\CommandBlock;
use diamondgold\DummyItemsBlocks\block\Composter;
use diamondgold\DummyItemsBlocks\block\Crafter;
use diamondgold\DummyItemsBlocks\block\CopperGolemStatue;
use diamondgold\DummyItemsBlocks\block\CreakingHeart;
use diamondgold\DummyItemsBlocks\block\DecoratedPot;
use diamondgold\DummyItemsBlocks\block\Dispenser;
use diamondgold\DummyItemsBlocks\block\enum\CrackedState;
use diamondgold\DummyItemsBlocks\block\enum\CreakingHeartState;
use diamondgold\DummyItemsBlocks\block\enum\DripstoneThickness;
use diamondgold\DummyItemsBlocks\block\enum\FacingDirection;
use diamondgold\DummyItemsBlocks\block\enum\Orientation;
use diamondgold\DummyItemsBlocks\block\enum\SeaGrassType;
use diamondgold\DummyItemsBlocks\block\enum\StructureBlockType;
use diamondgold\DummyItemsBlocks\block\enum\TurtleEggCount;
use diamondgold\DummyItemsBlocks\block\enum\VaultState;
use diamondgold\DummyItemsBlocks\block\Grindstone;
use diamondgold\DummyItemsBlocks\block\Jigsaw;
use diamondgold\DummyItemsBlocks\block\Kelp;
use diamondgold\DummyItemsBlocks\block\MangrovePropagule;
use diamondgold\DummyItemsBlocks\block\Observer;
use diamondgold\DummyItemsBlocks\block\PaleMossCarpet;
use diamondgold\DummyItemsBlocks\block\Piston;
use diamondgold\DummyItemsBlocks\block\PointedDripstone;
use diamondgold\DummyItemsBlocks\block\Scaffolding;
use diamondgold\DummyItemsBlocks\block\SculkCatalyst;
use diamondgold\DummyItemsBlocks\block\SculkSensor;
use diamondgold\DummyItemsBlocks\block\SculkShrieker;
use diamondgold\DummyItemsBlocks\block\SeaGrass;
use diamondgold\DummyItemsBlocks\block\Shelf;
use diamondgold\DummyItemsBlocks\block\SnifferEgg;
use diamondgold\DummyItemsBlocks\block\StructureBlock;
use diamondgold\DummyItemsBlocks\block\SuspiciousFallable;
use diamondgold\DummyItemsBlocks\block\SupportedAgeBitPlant;
use diamondgold\DummyItemsBlocks\block\TallDryGrass;
use diamondgold\DummyItemsBlocks\block\TrialSpawner;
use diamondgold\DummyItemsBlocks\block\TurtleEgg;
use diamondgold\DummyItemsBlocks\block\type\AnyFacingTransparent;
use diamondgold\DummyItemsBlocks\block\type\MultiFaceDirection;
use diamondgold\DummyItemsBlocks\block\Vault;
use diamondgold\DummyItemsBlocks\tile\DummyTile;
use pocketmine\block\Block;
use pocketmine\block\BlockBreakInfo;
use pocketmine\block\BlockIdentifier;
use pocketmine\block\BlockTypeIds;
use pocketmine\block\BlockTypeInfo;
use pocketmine\block\BlockTypeTags;
use pocketmine\block\Chest;
use pocketmine\block\Flower;
use pocketmine\block\Leaves;
use pocketmine\block\Slab;
use pocketmine\block\Stair;
use pocketmine\block\RuntimeBlockStateRegistry;
use pocketmine\block\utils\LeavesType;
use pocketmine\block\utils\SlabType;
use pocketmine\block\utils\WoodType;
use pocketmine\block\Wall;
use pocketmine\block\Wood;
use pocketmine\data\bedrock\block\BlockStateNames;
use pocketmine\data\bedrock\block\BlockStateStringValues;
use pocketmine\data\bedrock\block\BlockTypeNames;
use pocketmine\data\bedrock\block\convert\BlockStateDeserializerHelper;
use pocketmine\data\bedrock\block\convert\BlockStateReader as Reader;
use pocketmine\data\bedrock\block\convert\BlockStateSerializerHelper;
use pocketmine\data\bedrock\block\convert\BlockStateWriter as Writer;
use pocketmine\item\StringToItemParser;
use pocketmine\math\Facing;
use pocketmine\world\format\io\GlobalBlockStateHandlers;

/* @internal */
final class BlockStateRegistration
{
    private function __construct()
    {
    }

    /**
     * @param Block $block
     * @param string[] $stringToItemParserNames
     * @param bool $addToCreative
     * @return void
     */
    private static function register(Block $block, array $stringToItemParserNames, bool $addToCreative = true): void
    {
        RuntimeBlockStateRegistry::getInstance()->register($block);
        foreach ($stringToItemParserNames as $name) {
            StringToItemParser::getInstance()->registerBlock($name, fn() => clone $block);
        }
        if ($addToCreative) {
            CreativeInventoryRegistration::add($stringToItemParserNames[0], $block->asItem());
        }
    }

    private static function registerSimple(string $id, Block $block): void
    {
        self::register($block, [$id]);

        GlobalBlockStateHandlers::getDeserializer()->mapSimple($id, fn() => clone $block);
        GlobalBlockStateHandlers::getSerializer()->mapSimple($block, $id);
    }

    private static function registerStateful(string $id, Block $block, Closure $deserializer, Closure $serializer): void
    {
        self::register($block, [$id]);

        GlobalBlockStateHandlers::getDeserializer()->map($id, $deserializer);
        GlobalBlockStateHandlers::getSerializer()->map($block, $serializer);
    }

    private static function registerCardinalFacing(string $id, Block $block, Closure $setFacing, Closure $writeFacing): void
    {
        self::registerStateful($id, $block,
            fn(Reader $reader): Block => $setFacing(clone $block, $reader->readCardinalHorizontalFacing()),
            fn(Block $block) => $writeFacing($block, Writer::create($id))
        );
    }

    public static function growth(string $id, bool $supportsAnyFullBlock): void
    {
        $block = new CardinalGrowthBlock(new BlockIdentifier(BlockTypeIds::newId()), Utils::generateNameFromId($id), new BlockTypeInfo(BlockBreakInfo::instant()), $supportsAnyFullBlock);
        self::registerStateful($id, $block,
            fn(Reader $reader): CardinalGrowthBlock => (clone $block)
                ->setFacing($reader->readCardinalHorizontalFacing())
                ->setValue($reader->readBoundedInt(BlockStateNames::GROWTH, 0, 3)),
            fn(CardinalGrowthBlock $block) => Writer::create($id)
                ->writeCardinalHorizontalFacing($block->getFacing())
                ->writeInt(BlockStateNames::GROWTH, $block->getValue())
        );
    }

    public static function anyFacingTransparent(string $id): void
    {
        $block = new AnyFacingTransparent(new BlockIdentifier(BlockTypeIds::newId()), Utils::generateNameFromId($id), new BlockTypeInfo(BlockBreakInfo::instant()));
        self::register($block, [$id]);

        GlobalBlockStateHandlers::getDeserializer()->map($id,
            fn(Reader $reader): AnyFacingTransparent => (clone $block)
                ->setFacing($reader->readFacingDirection())
        );
        GlobalBlockStateHandlers::getSerializer()->map($block,
            fn(AnyFacingTransparent $block) => Writer::create($id)
                ->writeFacingDirection($block->getFacing())
        );
    }

    public static function log(string $id, string $strippedId, WoodType $woodType): void
    {
        $block = new Wood(new BlockIdentifier(BlockTypeIds::newId()), Utils::generateNameFromId($id), new BlockTypeInfo(BlockBreakInfo::instant()), $woodType);
        self::register($block, [$id, $strippedId]);

        GlobalBlockStateHandlers::getDeserializer()->mapLog($id, $strippedId,
            fn(): Wood => clone $block
        );
        GlobalBlockStateHandlers::getSerializer()->mapLog($block, $id, $strippedId);
    }

    public static function leaves(string $id, LeavesType $leavesType): void
    {
        $block = new Leaves(new BlockIdentifier(BlockTypeIds::newId()), Utils::generateNameFromId($id), new BlockTypeInfo(BlockBreakInfo::instant()), $leavesType);
        self::register($block, [$id]);

        GlobalBlockStateHandlers::getDeserializer()->map($id,
            fn(Reader $reader): Leaves => BlockStateDeserializerHelper::decodeLeaves(clone $block, $reader)
        );
        GlobalBlockStateHandlers::getSerializer()->map($block,
            fn(Leaves $block) => BlockStateSerializerHelper::encodeLeaves($block, Writer::create($id))
        );
    }

    public static function multiFaceDirection(string $id): void
    {
        $block = new MultiFaceDirection(new BlockIdentifier(BlockTypeIds::newId()), Utils::generateNameFromId($id), new BlockTypeInfo(BlockBreakInfo::instant()));
        self::register($block, [$id]);

        GlobalBlockStateHandlers::getDeserializer()->map($id,
            fn(Reader $reader): MultiFaceDirection => (clone $block)
                ->setMultiFaceDirection($reader->readBoundedInt(BlockStateNames::MULTI_FACE_DIRECTION_BITS, 0, 63))
        );
        GlobalBlockStateHandlers::getSerializer()->map($block,
            fn(MultiFaceDirection $block) => Writer::create($id)
                ->writeInt(BlockStateNames::MULTI_FACE_DIRECTION_BITS, $block->getMultiFaceDirection())
        );
    }

    public static function wall(string $id): void
    {
        $block = new Wall(new BlockIdentifier(BlockTypeIds::newId()), Utils::generateNameFromId($id), new BlockTypeInfo(BlockBreakInfo::instant()));
        self::register($block, [$id]);

        GlobalBlockStateHandlers::getDeserializer()->map($id,
            fn(Reader $in): Wall => BlockStateDeserializerHelper::decodeWall(clone $block, $in)
        );
        GlobalBlockStateHandlers::getSerializer()->map($block,
            fn(Wall $block) => BlockStateSerializerHelper::encodeWall($block, new Writer($id))
        );
    }

    public static function BeeHive(string $id): void
    {
        $block = new BeeHive(new BlockIdentifier(BlockTypeIds::newId(), DummyTile::class), Utils::generateNameFromId($id), new BlockTypeInfo(BlockBreakInfo::instant()));
        self::register($block, [$id]);

        GlobalBlockStateHandlers::getDeserializer()->map($id,
            fn(Reader $reader): BeeHive => (clone $block)
                ->setFacing($reader->readLegacyHorizontalFacing())
                ->setHoneyLevel($reader->readInt(BlockStateNames::HONEY_LEVEL))
        );
        GlobalBlockStateHandlers::getSerializer()->map($block,
            fn(BeeHive $block) => Writer::create($id)
                ->writeLegacyHorizontalFacing($block->getFacing())
                ->writeInt(BlockStateNames::HONEY_LEVEL, $block->getHoneyLevel())
        );
    }

    public static function BubbleColumn(): void
    {
        $id = BlockTypeNames::BUBBLE_COLUMN;
        $block = new BubbleColumn(new BlockIdentifier(BlockTypeIds::newId()), Utils::generateNameFromId($id), new BlockTypeInfo(BlockBreakInfo::indestructible()));
        self::register($block, [$id]);

        GlobalBlockStateHandlers::getDeserializer()->map($id,
            fn(Reader $reader): BubbleColumn => (clone $block)
                ->setDragDown($reader->readBool(BlockStateNames::DRAG_DOWN))
        );
        GlobalBlockStateHandlers::getSerializer()->map($block,
            fn(BubbleColumn $block) => Writer::create($id)
                ->writeBool(BlockStateNames::DRAG_DOWN, $block->getDragDown())
        );
    }

    public static function CalibratedSculkSensor(): void
    {
        $id = BlockTypeNames::CALIBRATED_SCULK_SENSOR;
        $block = new CalibratedSculkSensor(new BlockIdentifier(BlockTypeIds::newId(), DummyTile::class), Utils::generateNameFromId($id), new BlockTypeInfo(BlockBreakInfo::instant()));
        self::register($block, [$id]);

        GlobalBlockStateHandlers::getDeserializer()->map($id,
            fn(Reader $reader): CalibratedSculkSensor => (clone $block)
                ->setFacing($reader->readCardinalHorizontalFacing())
                ->setPhase($reader->readBoundedInt(BlockStateNames::SCULK_SENSOR_PHASE, 0, 2))
        );
        GlobalBlockStateHandlers::getSerializer()->map($block,
            fn(CalibratedSculkSensor $block) => Writer::create($id)
                ->writeCardinalHorizontalFacing($block->getFacing())
                ->writeInt(BlockStateNames::SCULK_SENSOR_PHASE, $block->getPhase())
        );
    }

    public static function AgeBit(string $id): void
    {
        $block = new SupportedAgeBitPlant(new BlockIdentifier(BlockTypeIds::newId()), Utils::generateNameFromId($id), new BlockTypeInfo(BlockBreakInfo::instant()));
        self::registerStateful($id, $block,
            fn(Reader $reader): SupportedAgeBitPlant => (clone $block)
                ->setAgeBit($reader->readBool(BlockStateNames::AGE_BIT)),
            fn(SupportedAgeBitPlant $block) => Writer::create($id)
                ->writeBool(BlockStateNames::AGE_BIT, $block->isAgeBit())
        );
    }

    public static function Flower(string $id): void
    {
        $block = new Flower(new BlockIdentifier(BlockTypeIds::newId()), Utils::generateNameFromId($id), new BlockTypeInfo(BlockBreakInfo::instant()));
        self::registerSimple($id, $block);
    }

    public static function TallDryGrass(): void
    {
        $id = BlockTypeNames::TALL_DRY_GRASS;
        $block = new TallDryGrass(new BlockIdentifier(BlockTypeIds::newId()), Utils::generateNameFromId($id), new BlockTypeInfo(BlockBreakInfo::instant()));
        self::registerSimple($id, $block);
    }

    public static function CardinalFacing(string $id): void
    {
        $block = new CardinalFacingBlock(new BlockIdentifier(BlockTypeIds::newId()), Utils::generateNameFromId($id), new BlockTypeInfo(BlockBreakInfo::instant()));
        self::registerCardinalFacing($id, $block,
            fn(CardinalFacingBlock $block, int $facing) => $block->setFacing($facing),
            fn(CardinalFacingBlock $block, Writer $writer) => $writer->writeCardinalHorizontalFacing($block->getFacing())
        );
    }

    public static function CopperChest(string $id): void
    {
        $block = new Chest(new BlockIdentifier(BlockTypeIds::newId()), Utils::generateNameFromId($id), new BlockTypeInfo(BlockBreakInfo::instant()));
        self::registerCardinalFacing($id, $block,
            fn(Chest $block, int $facing) => $block->setFacing($facing),
            fn(Chest $block, Writer $writer) => $writer->writeCardinalHorizontalFacing($block->getFacing())
        );
    }

    public static function CopperGolemStatue(string $id): void
    {
        $block = new CopperGolemStatue(new BlockIdentifier(BlockTypeIds::newId()), Utils::generateNameFromId($id), new BlockTypeInfo(BlockBreakInfo::instant()));
        self::registerCardinalFacing($id, $block,
            fn(CopperGolemStatue $block, int $facing) => $block->setFacing($facing),
            fn(CopperGolemStatue $block, Writer $writer) => $writer->writeCardinalHorizontalFacing($block->getFacing())
        );
    }

    public static function Shelf(string $id): void
    {
        $block = new Shelf(new BlockIdentifier(BlockTypeIds::newId()), Utils::generateNameFromId($id), new BlockTypeInfo(BlockBreakInfo::instant()));
        self::register($block, [$id]);

        GlobalBlockStateHandlers::getDeserializer()->map($id,
            fn(Reader $reader): Shelf => (clone $block)
                ->setFacing($reader->readCardinalHorizontalFacing())
                ->setPowered($reader->readBool(BlockStateNames::POWERED_BIT))
                ->setPoweredShelfType($reader->readBoundedInt(BlockStateNames::POWERED_SHELF_TYPE, 0, 3))
        );
        GlobalBlockStateHandlers::getSerializer()->map($block,
            fn(Shelf $block) => Writer::create($id)
                ->writeCardinalHorizontalFacing($block->getFacing())
                ->writeBool(BlockStateNames::POWERED_BIT, $block->isPowered())
                ->writeInt(BlockStateNames::POWERED_SHELF_TYPE, $block->getPoweredShelfType())
        );
    }

    public static function CreakingHeart(): void
    {
        $id = BlockTypeNames::CREAKING_HEART;
        $block = new CreakingHeart(new BlockIdentifier(BlockTypeIds::newId()), Utils::generateNameFromId($id), new BlockTypeInfo(BlockBreakInfo::instant()));
        self::register($block, [$id]);

        GlobalBlockStateHandlers::getDeserializer()->map($id,
            fn(Reader $reader): CreakingHeart => (clone $block)
                ->setState(match ($state = $reader->readString(BlockStateNames::CREAKING_HEART_STATE)) {
                    BlockStateStringValues::CREAKING_HEART_STATE_AWAKE => CreakingHeartState::AWAKE,
                    BlockStateStringValues::CREAKING_HEART_STATE_DORMANT => CreakingHeartState::DORMANT,
                    BlockStateStringValues::CREAKING_HEART_STATE_UPROOTED => CreakingHeartState::UPROOTED,
                    default => throw $reader->badValueException(BlockStateNames::CREAKING_HEART_STATE, $state),
                })
                ->setNatural($reader->readBool(BlockStateNames::NATURAL))
                ->setAxis($reader->readPillarAxis())
        );
        GlobalBlockStateHandlers::getSerializer()->map($block,
            fn(CreakingHeart $block) => Writer::create($id)
                ->writeString(BlockStateNames::CREAKING_HEART_STATE, $block->getState()->value)
                ->writeBool(BlockStateNames::NATURAL, $block->isNatural())
                ->writePillarAxis($block->getAxis())
        );
    }

    public static function DriedGhast(): void
    {
        $id = BlockTypeNames::DRIED_GHAST;
        $block = new CardinalIntBlock(new BlockIdentifier(BlockTypeIds::newId()), Utils::generateNameFromId($id), new BlockTypeInfo(BlockBreakInfo::instant()));
        self::register($block, [$id]);

        GlobalBlockStateHandlers::getDeserializer()->map($id,
            fn(Reader $reader): CardinalIntBlock => (clone $block)
                ->setFacing($reader->readCardinalHorizontalFacing())
                ->setValue($reader->readBoundedInt(BlockStateNames::REHYDRATION_LEVEL, 0, 3))
        );
        GlobalBlockStateHandlers::getSerializer()->map($block,
            fn(CardinalIntBlock $block) => Writer::create($id)
                ->writeCardinalHorizontalFacing($block->getFacing())
                ->writeInt(BlockStateNames::REHYDRATION_LEVEL, $block->getValue())
        );
    }

    public static function PaleHangingMoss(): void
    {
        $id = BlockTypeNames::PALE_HANGING_MOSS;
        $block = new CherrySapling(new BlockIdentifier(BlockTypeIds::newId()), Utils::generateNameFromId($id), new BlockTypeInfo(BlockBreakInfo::instant()));
        self::registerStateful($id, $block,
            fn(Reader $reader): CherrySapling => (clone $block)
                ->setAgeBit($reader->readBool(BlockStateNames::TIP)),
            fn(CherrySapling $block) => Writer::create($id)
                ->writeBool(BlockStateNames::TIP, $block->isAgeBit())
        );
    }

    public static function PaleMossCarpet(): void
    {
        $id = BlockTypeNames::PALE_MOSS_CARPET;
        $block = new PaleMossCarpet(new BlockIdentifier(BlockTypeIds::newId()), Utils::generateNameFromId($id), new BlockTypeInfo(BlockBreakInfo::instant()));
        self::register($block, [$id]);

        GlobalBlockStateHandlers::getDeserializer()->map($id,
            fn(Reader $reader): PaleMossCarpet => (clone $block)
                ->setConnection(Facing::EAST, $reader->readWallConnectionType(BlockStateNames::PALE_MOSS_CARPET_SIDE_EAST))
                ->setConnection(Facing::NORTH, $reader->readWallConnectionType(BlockStateNames::PALE_MOSS_CARPET_SIDE_NORTH))
                ->setConnection(Facing::SOUTH, $reader->readWallConnectionType(BlockStateNames::PALE_MOSS_CARPET_SIDE_SOUTH))
                ->setConnection(Facing::WEST, $reader->readWallConnectionType(BlockStateNames::PALE_MOSS_CARPET_SIDE_WEST))
                ->setPost($reader->readBool(BlockStateNames::UPPER_BLOCK_BIT))
        );
        GlobalBlockStateHandlers::getSerializer()->map($block,
            fn(PaleMossCarpet $block) => Writer::create($id)
                ->writeWallConnectionType(BlockStateNames::PALE_MOSS_CARPET_SIDE_EAST, $block->getConnection(Facing::EAST))
                ->writeWallConnectionType(BlockStateNames::PALE_MOSS_CARPET_SIDE_NORTH, $block->getConnection(Facing::NORTH))
                ->writeWallConnectionType(BlockStateNames::PALE_MOSS_CARPET_SIDE_SOUTH, $block->getConnection(Facing::SOUTH))
                ->writeWallConnectionType(BlockStateNames::PALE_MOSS_CARPET_SIDE_WEST, $block->getConnection(Facing::WEST))
                ->writeBool(BlockStateNames::UPPER_BLOCK_BIT, $block->isPost())
        );
    }

    public static function Slab(string $id, string $doubleId): void
    {
        $block = new Slab(new BlockIdentifier(BlockTypeIds::newId()), Utils::generateNameFromId($id), new BlockTypeInfo(BlockBreakInfo::instant()));
        self::register($block, [$id]);
        StringToItemParser::getInstance()->registerBlock($doubleId, fn() => (clone $block)->setSlabType(SlabType::DOUBLE));

        GlobalBlockStateHandlers::getDeserializer()->map($id,
            fn(Reader $reader): Slab => BlockStateDeserializerHelper::decodeSingleSlab(clone $block, $reader)
        );
        GlobalBlockStateHandlers::getDeserializer()->map($doubleId,
            fn(Reader $reader): Slab => BlockStateDeserializerHelper::decodeDoubleSlab(clone $block, $reader)
        );
        GlobalBlockStateHandlers::getSerializer()->map($block,
            fn(Slab $block) => BlockStateSerializerHelper::encodeSlab($block, $id, $doubleId)
        );
    }

    public static function Stair(string $id): void
    {
        $block = new Stair(new BlockIdentifier(BlockTypeIds::newId()), Utils::generateNameFromId($id), new BlockTypeInfo(BlockBreakInfo::instant()));
        self::register($block, [$id]);

        GlobalBlockStateHandlers::getDeserializer()->map($id,
            fn(Reader $reader): Stair => BlockStateDeserializerHelper::decodeStairs(clone $block, $reader)
        );
        GlobalBlockStateHandlers::getSerializer()->map($block,
            fn(Stair $block) => BlockStateSerializerHelper::encodeStairs($block, Writer::create($id))
        );
    }

    public static function CommandBlock(string $id): void
    {
        $block = new CommandBlock(new BlockIdentifier(BlockTypeIds::newId(), DummyTile::class), Utils::generateNameFromId($id), new BlockTypeInfo(BlockBreakInfo::indestructible()));
        self::register($block, [$id]);

        GlobalBlockStateHandlers::getDeserializer()->map($id,
            fn(Reader $reader): CommandBlock => (clone $block)
                ->setConditional($reader->readBool(BlockStateNames::CONDITIONAL_BIT))
                ->setFacing($reader->readFacingDirection())
        );
        GlobalBlockStateHandlers::getSerializer()->map($block,
            fn(CommandBlock $block) => Writer::create($id)
                ->writeBool(BlockStateNames::CONDITIONAL_BIT, $block->isConditional())
                ->writeFacingDirection($block->getFacing())
        );
    }

    // obsolete when merged https://github.com/pmmp/PocketMine-MP/pull/6922
    public static function Composter(): void
    {
        $id = BlockTypeNames::COMPOSTER;
        $block = new Composter(new BlockIdentifier(BlockTypeIds::newId()), Utils::generateNameFromId($id), new BlockTypeInfo(BlockBreakInfo::instant()));
        self::register($block, [$id]);

        GlobalBlockStateHandlers::getDeserializer()->map($id,
            fn(Reader $reader): Composter => (clone $block)
                ->setFillLevel($reader->readBoundedInt(BlockStateNames::COMPOSTER_FILL_LEVEL, 0, 8))
        );
        GlobalBlockStateHandlers::getSerializer()->map($block,
            fn(Composter $block) => Writer::create($id)
                ->writeInt(BlockStateNames::COMPOSTER_FILL_LEVEL, $block->getFillLevel())
        );
    }

    public static function Crafter(): void
    {
        $id = BlockTypeNames::CRAFTER;
        $block = new Crafter(new BlockIdentifier(BlockTypeIds::newId(), DummyTile::class), Utils::generateNameFromId($id), new BlockTypeInfo(BlockBreakInfo::instant()));
        self::register($block, [$id]);

        GlobalBlockStateHandlers::getDeserializer()->map($id,
            fn(Reader $reader): Crafter => (clone $block)
                ->setCrafting($reader->readBool(BlockStateNames::CRAFTING))
                ->setOrientation(match ($reader->readString(BlockStateNames::ORIENTATION)) {
                    BlockStateStringValues::ORIENTATION_DOWN_EAST => Orientation::DOWN_EAST,
                    BlockStateStringValues::ORIENTATION_DOWN_NORTH => Orientation::DOWN_NORTH,
                    BlockStateStringValues::ORIENTATION_DOWN_SOUTH => Orientation::DOWN_SOUTH,
                    BlockStateStringValues::ORIENTATION_DOWN_WEST => Orientation::DOWN_WEST,
                    BlockStateStringValues::ORIENTATION_EAST_UP => Orientation::EAST_UP,
                    BlockStateStringValues::ORIENTATION_NORTH_UP => Orientation::NORTH_UP,
                    BlockStateStringValues::ORIENTATION_SOUTH_UP => Orientation::SOUTH_UP,
                    BlockStateStringValues::ORIENTATION_UP_EAST => Orientation::UP_EAST,
                    BlockStateStringValues::ORIENTATION_UP_NORTH => Orientation::UP_NORTH,
                    BlockStateStringValues::ORIENTATION_UP_SOUTH => Orientation::UP_SOUTH,
                    BlockStateStringValues::ORIENTATION_UP_WEST => Orientation::UP_WEST,
                    BlockStateStringValues::ORIENTATION_WEST_UP => Orientation::WEST_UP,
                    default => throw $reader->badValueException(BlockStateNames::ORIENTATION, $reader->readString(BlockStateNames::ORIENTATION))
                })
                ->setTriggered($reader->readBool(BlockStateNames::TRIGGERED_BIT))
        );
        GlobalBlockStateHandlers::getSerializer()->map($block,
            fn(Crafter $block) => Writer::create($id)
                ->writeBool(BlockStateNames::CRAFTING, $block->isCrafting())
                ->writeString(BlockStateNames::ORIENTATION, strtolower($block->getOrientation()->name))
                ->writeBool(BlockStateNames::TRIGGERED_BIT, $block->isTriggered())
        );
    }

    public static function DecoratedPot(): void
    {
        $id = BlockTypeNames::DECORATED_POT;
        $block = new DecoratedPot(new BlockIdentifier(BlockTypeIds::newId(), DummyTile::class), Utils::generateNameFromId($id), new BlockTypeInfo(BlockBreakInfo::instant()));
        self::register($block, [$id]);

        GlobalBlockStateHandlers::getDeserializer()->map($id,
            fn(Reader $reader): DecoratedPot => (clone $block)
                ->setFacing($reader->readLegacyHorizontalFacing())
        );
        GlobalBlockStateHandlers::getSerializer()->map($block,
            fn(DecoratedPot $block) => Writer::create($id)
                ->writeLegacyHorizontalFacing($block->getFacing())
        );
    }

    public static function Dispenser(string $id): void
    {
        $block = new Dispenser(new BlockIdentifier(BlockTypeIds::newId(), DummyTile::class), Utils::generateNameFromId($id), new BlockTypeInfo(BlockBreakInfo::instant()));
        self::register($block, [$id]);

        GlobalBlockStateHandlers::getDeserializer()->map($id,
            fn(Reader $reader): Dispenser => (clone $block)
                ->setFacing($reader->readFacingDirection())
                ->setTriggered($reader->readBool(BlockStateNames::TRIGGERED_BIT))
        );
        GlobalBlockStateHandlers::getSerializer()->map($block,
            fn(Dispenser $block) => Writer::create($id)
                ->writeFacingDirection($block->getFacing())
                ->writeBool(BlockStateNames::TRIGGERED_BIT, $block->isTriggered())
        );
    }

    public static function Grindstone(): void
    {
        $id = BlockTypeNames::GRINDSTONE;
        $block = new Grindstone(new BlockIdentifier(BlockTypeIds::newId()), Utils::generateNameFromId($id), new BlockTypeInfo(BlockBreakInfo::instant()));
        self::register($block, [$id]);

        GlobalBlockStateHandlers::getDeserializer()->map($id,
            fn(Reader $reader): Grindstone => (clone $block)
                ->setFacing($reader->readLegacyHorizontalFacing())
                ->setAttachmentType($reader->readBellAttachmentType())
        );
        GlobalBlockStateHandlers::getSerializer()->map($block,
            fn(Grindstone $block) => Writer::create($id)
                ->writeLegacyHorizontalFacing($block->getFacing())
                ->writeBellAttachmentType($block->getAttachmentType())
        );
    }

    public static function Jigsaw(): void
    {
        $id = BlockTypeNames::JIGSAW;
        $block = new Jigsaw(new BlockIdentifier(BlockTypeIds::newId()), Utils::generateNameFromId($id), new BlockTypeInfo(BlockBreakInfo::indestructible()));
        self::register($block, [$id]);

        GlobalBlockStateHandlers::getDeserializer()->map($id,
            fn(Reader $reader): Jigsaw => (clone $block)
                ->setFacing($reader->readFacingDirection())
                ->setRotation($reader->readBoundedInt(BlockStateNames::ROTATION, 0, 3))
        );
        GlobalBlockStateHandlers::getSerializer()->map($block,
            fn(Jigsaw $block) => Writer::create($id)
                ->writeFacingDirection($block->getFacing())
                ->writeInt(BlockStateNames::ROTATION, $block->getRotation())
        );
    }

    public static function Kelp(): void
    {
        $id = BlockTypeNames::KELP;
        $block = new Kelp(new BlockIdentifier(BlockTypeIds::newId()), Utils::generateNameFromId($id), new BlockTypeInfo(BlockBreakInfo::instant()));
        self::register($block, [$id . '_block'], false);

        GlobalBlockStateHandlers::getDeserializer()->map($id,
            fn(Reader $reader): Kelp => (clone $block)
                ->setAge($reader->readBoundedInt(BlockStateNames::KELP_AGE, 0, 25))
        );
        GlobalBlockStateHandlers::getSerializer()->map($block,
            fn(Kelp $block) => Writer::create($id)
                ->writeInt(BlockStateNames::KELP_AGE, $block->getAge())
        );
    }

    public static function MangrovePropagule(): void
    {
        $id = BlockTypeNames::MANGROVE_PROPAGULE;
        $block = new MangrovePropagule(new BlockIdentifier(BlockTypeIds::newId()), Utils::generateNameFromId($id), new BlockTypeInfo(BlockBreakInfo::instant()));
        self::register($block, [$id]);

        GlobalBlockStateHandlers::getDeserializer()->map($id,
            fn(Reader $reader): MangrovePropagule => (clone $block)
                ->setHanging($reader->readBool(BlockStateNames::HANGING))
                ->setStage($reader->readBoundedInt(BlockStateNames::PROPAGULE_STAGE, 0, 4))
        );
        GlobalBlockStateHandlers::getSerializer()->map($block,
            fn(MangrovePropagule $block) => Writer::create($id)
                ->writeBool(BlockStateNames::HANGING, $block->isHanging())
                ->writeInt(BlockStateNames::PROPAGULE_STAGE, $block->getStage())
        );
    }

    public static function Observer(): void
    {
        $id = BlockTypeNames::OBSERVER;
        $block = new Observer(new BlockIdentifier(BlockTypeIds::newId()), Utils::generateNameFromId($id), new BlockTypeInfo(BlockBreakInfo::instant()));
        self::register($block, [$id]);

        GlobalBlockStateHandlers::getDeserializer()->map($id,
            fn(Reader $reader): Observer => (clone $block)
                ->setFacingDirection(match ($reader->readString(BlockStateNames::MC_FACING_DIRECTION)) {
                    BlockStateStringValues::MC_FACING_DIRECTION_DOWN => FacingDirection::DOWN,
                    BlockStateStringValues::MC_FACING_DIRECTION_UP => FacingDirection::UP,
                    BlockStateStringValues::MC_FACING_DIRECTION_NORTH => FacingDirection::NORTH,
                    BlockStateStringValues::MC_FACING_DIRECTION_SOUTH => FacingDirection::SOUTH,
                    BlockStateStringValues::MC_FACING_DIRECTION_WEST => FacingDirection::WEST,
                    BlockStateStringValues::MC_FACING_DIRECTION_EAST => FacingDirection::EAST,
                    default => throw $reader->badValueException(BlockStateNames::MC_FACING_DIRECTION, $reader->readString(BlockStateNames::MC_FACING_DIRECTION)),
                })
                ->setPowered($reader->readBool(BlockStateNames::POWERED_BIT))
        );
        GlobalBlockStateHandlers::getSerializer()->map($block,
            fn(Observer $block) => Writer::create($id)
                ->writeString(BlockStateNames::MC_FACING_DIRECTION, strtolower($block->getFacingDirection()->name))
                ->writeBool(BlockStateNames::POWERED_BIT, $block->isPowered())
        );
    }

    public static function Piston(string $id): void
    {
        $block = new Piston(new BlockIdentifier(BlockTypeIds::newId(), DummyTile::class), Utils::generateNameFromId($id), new BlockTypeInfo(BlockBreakInfo::instant()));
        self::register($block, [$id]);

        GlobalBlockStateHandlers::getDeserializer()->map($id,
            fn(Reader $reader): Piston => (clone $block)
                ->setFacing($reader->readFacingDirection())
        );
        GlobalBlockStateHandlers::getSerializer()->map($block,
            fn(Piston $block) => Writer::create($id)
                ->writeFacingDirection($block->getFacing())
        );
    }

    public static function PointedDripstone(): void
    {
        $id = BlockTypeNames::POINTED_DRIPSTONE;
        $block = new PointedDripstone(new BlockIdentifier(BlockTypeIds::newId()), Utils::generateNameFromId($id), new BlockTypeInfo(BlockBreakInfo::instant()));
        self::register($block, [$id]);

        GlobalBlockStateHandlers::getDeserializer()->map($id,
            fn(Reader $reader): PointedDripstone => (clone $block)
                ->setHanging($reader->readBool(BlockStateNames::HANGING))
                ->setThickness(match ($reader->readString(BlockStateNames::DRIPSTONE_THICKNESS)) {
                    BlockStateStringValues::DRIPSTONE_THICKNESS_BASE => DripstoneThickness::BASE,
                    BlockStateStringValues::DRIPSTONE_THICKNESS_FRUSTUM => DripstoneThickness::FRUSTUM,
                    BlockStateStringValues::DRIPSTONE_THICKNESS_MERGE => DripstoneThickness::MERGE,
                    BlockStateStringValues::DRIPSTONE_THICKNESS_MIDDLE => DripstoneThickness::MIDDLE,
                    BlockStateStringValues::DRIPSTONE_THICKNESS_TIP => DripstoneThickness::TIP,
                    default => throw $reader->badValueException(BlockStateNames::DRIPSTONE_THICKNESS, $reader->readString(BlockStateNames::DRIPSTONE_THICKNESS))
                })
        );
        GlobalBlockStateHandlers::getSerializer()->map($block,
            fn(PointedDripstone $block) => Writer::create($id)
                ->writeBool(BlockStateNames::HANGING, $block->isHanging())
                ->writeString(BlockStateNames::DRIPSTONE_THICKNESS, strtolower($block->getThickness()->name))
        );
    }

    public static function Scaffolding(): void
    {
        $id = BlockTypeNames::SCAFFOLDING;
        $block = new Scaffolding(new BlockIdentifier(BlockTypeIds::newId()), Utils::generateNameFromId($id), new BlockTypeInfo(BlockBreakInfo::instant()));
        self::register($block, [$id]);

        GlobalBlockStateHandlers::getDeserializer()->map($id,
            fn(Reader $reader): Scaffolding => (clone $block)
                ->setStability($reader->readBoundedInt(BlockStateNames::STABILITY, 0, 7))
                ->setStabilityCheck($reader->readBool(BlockStateNames::STABILITY_CHECK))
        );
        GlobalBlockStateHandlers::getSerializer()->map($block,
            fn(Scaffolding $block) => Writer::create($id)
                ->writeInt(BlockStateNames::STABILITY, $block->getStability())
                ->writeBool(BlockStateNames::STABILITY_CHECK, $block->isStabilityCheck())
        );
    }

    public static function SculkCatalyst(): void
    {
        $id = BlockTypeNames::SCULK_CATALYST;
        $block = new SculkCatalyst(new BlockIdentifier(BlockTypeIds::newId(), DummyTile::class), Utils::generateNameFromId($id), new BlockTypeInfo(BlockBreakInfo::instant()));
        self::register($block, [$id]);

        GlobalBlockStateHandlers::getDeserializer()->map($id,
            fn(Reader $reader): SculkCatalyst => (clone $block)
                ->setBloom($reader->readBool(BlockStateNames::BLOOM))
        );
        GlobalBlockStateHandlers::getSerializer()->map($block,
            fn(SculkCatalyst $block) => Writer::create($id)
                ->writeBool(BlockStateNames::BLOOM, $block->isBloom())
        );
    }

    public static function SculkSensor(): void
    {
        $id = BlockTypeNames::SCULK_SENSOR;
        $block = new SculkSensor(new BlockIdentifier(BlockTypeIds::newId(), DummyTile::class), Utils::generateNameFromId($id), new BlockTypeInfo(BlockBreakInfo::instant()));
        self::register($block, [$id]);

        GlobalBlockStateHandlers::getDeserializer()->map($id,
            fn(Reader $reader): SculkSensor => (clone $block)
                ->setPhase($reader->readBoundedInt(BlockStateNames::SCULK_SENSOR_PHASE, 0, 2))
        );
        GlobalBlockStateHandlers::getSerializer()->map($block,
            fn(SculkSensor $block) => Writer::create($id)
                ->writeInt(BlockStateNames::SCULK_SENSOR_PHASE, $block->getPhase())
        );
    }

    public static function SculkShrieker(): void
    {
        $id = BlockTypeNames::SCULK_SHRIEKER;
        $block = new SculkShrieker(new BlockIdentifier(BlockTypeIds::newId(), DummyTile::class), Utils::generateNameFromId($id), new BlockTypeInfo(BlockBreakInfo::instant()));
        self::register($block, [$id]);

        GlobalBlockStateHandlers::getDeserializer()->map($id,
            fn(Reader $reader): SculkShrieker => (clone $block)
                ->setActive($reader->readBool(BlockStateNames::ACTIVE))
                ->setCanSummon($reader->readBool(BlockStateNames::CAN_SUMMON))
        );
        GlobalBlockStateHandlers::getSerializer()->map($block,
            fn(SculkShrieker $block) => Writer::create($id)
                ->writeBool(BlockStateNames::ACTIVE, $block->isActive())
                ->writeBool(BlockStateNames::CAN_SUMMON, $block->canSummon())
        );
    }

    public static function SeaGrass(): void
    {
        $id = BlockTypeNames::SEAGRASS;
        $block = new SeaGrass(new BlockIdentifier(BlockTypeIds::newId()), Utils::generateNameFromId($id), new BlockTypeInfo(BlockBreakInfo::instant()));
        self::register($block, [$id]);

        GlobalBlockStateHandlers::getDeserializer()->map($id,
            fn(Reader $reader): SeaGrass => (clone $block)
                ->setType(match ($reader->readString(BlockStateNames::SEA_GRASS_TYPE)) {
                    BlockStateStringValues::SEA_GRASS_TYPE_DEFAULT => SeaGrassType::DEFAULT,
                    BlockStateStringValues::SEA_GRASS_TYPE_DOUBLE_TOP => SeaGrassType::DOUBLE_TOP,
                    BlockStateStringValues::SEA_GRASS_TYPE_DOUBLE_BOT => SeaGrassType::DOUBLE_BOT,
                    default => throw $reader->badValueException(BlockStateNames::SEA_GRASS_TYPE, $reader->readString(BlockStateNames::SEA_GRASS_TYPE))
                })
        );
        GlobalBlockStateHandlers::getSerializer()->map($block,
            fn(SeaGrass $block) => Writer::create($id)
                ->writeString(BlockStateNames::SEA_GRASS_TYPE, strtolower($block->getType()->name))
        );
    }

    public static function SnifferEgg(): void
    {
        $id = BlockTypeNames::SNIFFER_EGG;
        $block = new SnifferEgg(new BlockIdentifier(BlockTypeIds::newId()), Utils::generateNameFromId($id), new BlockTypeInfo(BlockBreakInfo::instant()));
        self::register($block, [$id]);

        GlobalBlockStateHandlers::getDeserializer()->map($id,
            fn(Reader $reader): SnifferEgg => (clone $block)
                ->setCrackedState(match ($reader->readString(BlockStateNames::CRACKED_STATE)) {
                    BlockStateStringValues::CRACKED_STATE_NO_CRACKS => CrackedState::NO_CRACKS,
                    BlockStateStringValues::CRACKED_STATE_CRACKED => CrackedState::CRACKED,
                    BlockStateStringValues::CRACKED_STATE_MAX_CRACKED => CrackedState::MAX_CRACKED,
                    default => throw $reader->badValueException(BlockStateNames::CRACKED_STATE, $reader->readString(BlockStateNames::CRACKED_STATE))
                })
        );
        GlobalBlockStateHandlers::getSerializer()->map($block,
            fn(SnifferEgg $block) => Writer::create($id)
                ->writeString(BlockStateNames::CRACKED_STATE, strtolower($block->getCrackedState()->name))
        );
    }

    public static function StructureBlock(): void
    {
        $id = BlockTypeNames::STRUCTURE_BLOCK;
        $block = new StructureBlock(new BlockIdentifier(BlockTypeIds::newId(), DummyTile::class), Utils::generateNameFromId($id), new BlockTypeInfo(BlockBreakInfo::indestructible()));
        self::register($block, [$id]);

        GlobalBlockStateHandlers::getDeserializer()->map($id,
            fn(Reader $reader): StructureBlock => (clone $block)
                ->setType(match ($reader->readString(BlockStateNames::STRUCTURE_BLOCK_TYPE)) {
                    BlockStateStringValues::STRUCTURE_BLOCK_TYPE_SAVE => StructureBlockType::SAVE,
                    BlockStateStringValues::STRUCTURE_BLOCK_TYPE_LOAD => StructureBlockType::LOAD,
                    BlockStateStringValues::STRUCTURE_BLOCK_TYPE_CORNER => StructureBlockType::CORNER,
                    BlockStateStringValues::STRUCTURE_BLOCK_TYPE_DATA => StructureBlockType::DATA,
                    BlockStateStringValues::STRUCTURE_BLOCK_TYPE_EXPORT => StructureBlockType::EXPORT,
                    BlockStateStringValues::STRUCTURE_BLOCK_TYPE_INVALID => StructureBlockType::INVALID,
                    default => throw $reader->badValueException(BlockStateNames::STRUCTURE_BLOCK_TYPE, $reader->readString(BlockStateNames::STRUCTURE_BLOCK_TYPE))
                })
        );
        GlobalBlockStateHandlers::getSerializer()->map($block,
            fn(StructureBlock $block) => Writer::create($id)
                ->writeString(BlockStateNames::STRUCTURE_BLOCK_TYPE, strtolower($block->getType()->name))
        );
    }

    public static function SuspiciousFallable(string $id): void
    {
        if ($id === BlockTypeNames::SUSPICIOUS_SAND) {
            $tags = [BlockTypeTags::SAND];
        } else {
            $tags = [];
        }
        // Note: does not implement Fallable nor use the FallableTrait
        $block = new SuspiciousFallable(new BlockIdentifier(BlockTypeIds::newId(), DummyTile::class), Utils::generateNameFromId($id), new BlockTypeInfo(BlockBreakInfo::instant(), $tags));
        self::register($block, [$id]);

        GlobalBlockStateHandlers::getDeserializer()->map($id,
            fn(Reader $reader): SuspiciousFallable => (clone $block)
                ->setBrushedProgress($reader->readBoundedInt(BlockStateNames::BRUSHED_PROGRESS, 0, 3))
                ->setHanging($reader->readBool(BlockStateNames::HANGING))
        );
        GlobalBlockStateHandlers::getSerializer()->map($block,
            fn(SuspiciousFallable $block) => Writer::create($id)
                ->writeInt(BlockStateNames::BRUSHED_PROGRESS, $block->getBrushedProgress())
                ->writeBool(BlockStateNames::HANGING, $block->isHanging())
        );
    }

    public static function TrialSpawner(): void
    {
        $id = BlockTypeNames::TRIAL_SPAWNER;
        $block = new TrialSpawner(new BlockIdentifier(BlockTypeIds::newId(), DummyTile::class), Utils::generateNameFromId($id), new BlockTypeInfo(BlockBreakInfo::instant()));
        self::register($block, [$id]);

        GlobalBlockStateHandlers::getDeserializer()->map($id,
            fn(Reader $reader): TrialSpawner => (clone $block)
                ->setOminous($reader->readBool(BlockStateNames::OMINOUS))
                ->setState($reader->readInt(BlockStateNames::TRIAL_SPAWNER_STATE))
        );
        GlobalBlockStateHandlers::getSerializer()->map($block,
            fn(TrialSpawner $block) => Writer::create($id)
                ->writeBool(BlockStateNames::OMINOUS, $block->isOminous())
                ->writeInt(BlockStateNames::TRIAL_SPAWNER_STATE, $block->getState())
        );
    }

    public static function TurtleEgg(): void
    {
        $id = BlockTypeNames::TURTLE_EGG;
        $block = new TurtleEgg(new BlockIdentifier(BlockTypeIds::newId()), Utils::generateNameFromId($id), new BlockTypeInfo(BlockBreakInfo::instant()));
        self::register($block, [$id]);

        GlobalBlockStateHandlers::getDeserializer()->map($id,
            fn(Reader $reader): TurtleEgg => (clone $block)
                ->setEggCount(match ($reader->readString(BlockStateNames::TURTLE_EGG_COUNT)) {
                    BlockStateStringValues::TURTLE_EGG_COUNT_ONE_EGG => TurtleEggCount::ONE_EGG,
                    BlockStateStringValues::TURTLE_EGG_COUNT_TWO_EGG => TurtleEggCount::TWO_EGG,
                    BlockStateStringValues::TURTLE_EGG_COUNT_THREE_EGG => TurtleEggCount::THREE_EGG,
                    BlockStateStringValues::TURTLE_EGG_COUNT_FOUR_EGG => TurtleEggCount::FOUR_EGG,
                    default => throw $reader->badValueException(BlockStateNames::TURTLE_EGG_COUNT, $reader->readString(BlockStateNames::TURTLE_EGG_COUNT))
                })
                ->setCrackedState(match ($reader->readString(BlockStateNames::CRACKED_STATE)) {
                    BlockStateStringValues::CRACKED_STATE_NO_CRACKS => CrackedState::NO_CRACKS,
                    BlockStateStringValues::CRACKED_STATE_CRACKED => CrackedState::CRACKED,
                    BlockStateStringValues::CRACKED_STATE_MAX_CRACKED => CrackedState::MAX_CRACKED,
                    default => throw $reader->badValueException(BlockStateNames::CRACKED_STATE, $reader->readString(BlockStateNames::CRACKED_STATE))
                })
        );
        GlobalBlockStateHandlers::getSerializer()->map($block,
            fn(TurtleEgg $block) => Writer::create($id)
                ->writeString(BlockStateNames::TURTLE_EGG_COUNT, strtolower($block->getEggCount()->name))
                ->writeString(BlockStateNames::CRACKED_STATE, strtolower($block->getCrackedState()->name))
        );
    }

    public static function Vault(): void
    {
        $id = BlockTypeNames::VAULT;
        $block = new Vault(new BlockIdentifier(BlockTypeIds::newId(), DummyTile::class), Utils::generateNameFromId($id), new BlockTypeInfo(BlockBreakInfo::indestructible()));
        self::register($block, [$id]);

        GlobalBlockStateHandlers::getDeserializer()->map($id,
            fn(Reader $reader): Vault => (clone $block)
                ->setFacing($reader->readCardinalHorizontalFacing())
                ->setOminous($reader->readBool(BlockStateNames::OMINOUS))
                ->setState(match ($reader->readString(BlockStateNames::VAULT_STATE)) {
                    BlockStateStringValues::VAULT_STATE_INACTIVE => VaultState::INACTIVE,
                    BlockStateStringValues::VAULT_STATE_ACTIVE => VaultState::ACTIVE,
                    BlockStateStringValues::VAULT_STATE_UNLOCKING => VaultState::UNLOCKING,
                    BlockStateStringValues::VAULT_STATE_EJECTING => VaultState::EJECTING,
                    default => throw $reader->badValueException(BlockStateNames::VAULT_STATE, $reader->readString(BlockStateNames::VAULT_STATE))
                })
        );
        GlobalBlockStateHandlers::getSerializer()->map($block,
            fn(Vault $block) => Writer::create($id)
                ->writeCardinalHorizontalFacing($block->getFacing())
                ->writeBool(BlockStateNames::OMINOUS, $block->isOminous())
                ->writeString(BlockStateNames::VAULT_STATE, strtolower($block->getState()->name))
        );
    }
}
