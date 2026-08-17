<?php

namespace diamondgold\DummyItemsBlocks\util;

use pocketmine\crafting\CraftingManagerFromDataHelper;
use pocketmine\data\bedrock\BedrockDataFiles;
use pocketmine\data\bedrock\item\SavedItemData;
use pocketmine\inventory\CreativeCategory;
use pocketmine\inventory\CreativeGroup;
use pocketmine\inventory\CreativeInventory;
use pocketmine\inventory\json\CreativeGroupData;
use pocketmine\item\Item;
use pocketmine\item\StringToItemParser;
use pocketmine\lang\Translatable;
use pocketmine\world\format\io\GlobalItemDataHandlers;
use Symfony\Component\Filesystem\Path;

final class CreativeInventoryRegistration
{
    private static array $metadata = [];
    private static array $groups = [];
    private static array $items = [];
    private static array $pending = [];

    private function __construct()
    {
    }

    public static function add(string $id, Item $item): void
    {
        self::$items[$id] = $item;
        [$category, $groupName, $groupIcon] = self::getMetadata($id);
        CreativeInventory::getInstance()->add($item, $category, self::getGroup($category, $groupName, $groupIcon));
    }

    public static function queue(string $id, Item $item): void
    {
        self::$items[$id] = $item;
        self::$pending[$id] = $item;
    }

    public static function flush(): void
    {
        foreach (self::$pending as $id => $item) {
            self::add($id, $item);
        }
        self::$pending = [];
        self::reorder();
    }

    public static function reorder(): void
    {
        $inventory = CreativeInventory::getInstance();
        $remaining = $inventory->getAllEntries();
        $ordered = [];
        $groups = [];

        foreach ([
                     'construction' => CreativeCategory::CONSTRUCTION,
                     'nature' => CreativeCategory::NATURE,
                     'equipment' => CreativeCategory::EQUIPMENT,
                     'items' => CreativeCategory::ITEMS,
                 ] as $categoryId => $category) {
            $creativeGroups = CraftingManagerFromDataHelper::loadJsonArrayOfObjectsFile(
                Path::join(BedrockDataFiles::CREATIVE, $categoryId . '.json'),
                CreativeGroupData::class
            );
            foreach ($creativeGroups as $groupData) {
                $group = null;
                if ($groupData->group_icon !== null) {
                    $icon = CraftingManagerFromDataHelper::deserializeItemStack($groupData->group_icon) ?? self::$items[$groupData->group_icon->name] ?? null;
                    if ($icon !== null) {
                        $key = $category->name . ':' . $groupData->group_name;
                        $group = $groups[$key] ??= new CreativeGroup(new Translatable($groupData->group_name), $icon);
                    }
                }
                foreach ($groupData->items as $itemData) {
                    $item = CraftingManagerFromDataHelper::deserializeItemStack($itemData) ?? self::$items[$itemData->name] ?? null;
                    if ($item === null) {
                        continue;
                    }
                    $matchKey = null;
                    foreach ($remaining as $key => $entry) {
                        if ($entry->getItem()->equals($item, true, true)) {
                            $matchKey = $key;
                            break;
                        }
                    }
                    if ($matchKey === null) {
                        foreach ($remaining as $key => $entry) {
                            if ($entry->matchesItem($item)) {
                                $matchKey = $key;
                                break;
                            }
                        }
                    }
                    if ($matchKey === null) {
                        continue;
                    }
                    unset($remaining[$matchKey]);
                    $ordered[] = [$item, $category, $group];
                }
            }
        }

        foreach ($remaining as $entry) {
            $group = $entry->getGroup();
            if ($group !== null) {
                $groupName = $group->getName();
                if ($groupName instanceof Translatable) {
                    $groupName = $groupName->getText();
                }
                $key = $entry->getCategory()->name . ':' . $groupName;
                $group = $groups[$key] ??= $group;
            }
            $ordered[] = [$entry->getItem(), $entry->getCategory(), $group];
        }

        $inventory->clear();
        foreach ($ordered as [$item, $category, $group]) {
            $inventory->add($item, $category, $group);
        }
        self::$groups = $groups;
    }

    /** @return array{CreativeCategory, ?string, ?string} */
    private static function getMetadata(string $id): array
    {
        if (self::$metadata === []) {
            foreach ([
                         'construction' => CreativeCategory::CONSTRUCTION,
                         'nature' => CreativeCategory::NATURE,
                         'equipment' => CreativeCategory::EQUIPMENT,
                         'items' => CreativeCategory::ITEMS,
                     ] as $categoryId => $category) {
                $contents = file_get_contents(BedrockDataFiles::CREATIVE . '/' . $categoryId . '.json');
                if ($contents === false) {
                    throw new \RuntimeException("Unable to load creative data for $categoryId");
                }
                $groups = json_decode($contents, true, flags: JSON_THROW_ON_ERROR);
                foreach ($groups as $group) {
                    $groupName = $group['group_name'];
                    $groupIcon = is_string($group['group_icon']) ? $group['group_icon'] : null;
                    foreach ($group['items'] as $entry) {
                        $itemId = is_string($entry) ? $entry : ($entry['name'] ?? null);
                        if (is_string($itemId) && !isset(self::$metadata[$itemId])) {
                            self::$metadata[$itemId] = [$category, $groupName !== '' ? $groupName : null, $groupIcon];
                        }
                    }
                }
            }
        }

        return self::$metadata[$id] ?? [CreativeCategory::ITEMS, null, null];
    }

    private static function getGroup(CreativeCategory $category, ?string $name, ?string $iconId): ?CreativeGroup
    {
        if ($name === null || $iconId === null) {
            return null;
        }
        $key = $category->name . ':' . $name;
        if (isset(self::$groups[$key])) {
            return self::$groups[$key];
        }
        foreach (CreativeInventory::getInstance()->getAllEntries() as $entry) {
            if ($entry->getCategory() !== $category || $entry->getGroup() === null) {
                continue;
            }
            $groupName = $entry->getGroup()->getName();
            if ($groupName instanceof Translatable) {
                $groupName = $groupName->getText();
            }
            if ($groupName === $name) {
                return self::$groups[$key] = $entry->getGroup();
            }
        }
        $icon = self::getIcon($iconId);
        if ($icon === null) {
            return null;
        }
        return self::$groups[$key] = new CreativeGroup(new Translatable($name), $icon);
    }

    private static function getIcon(string $id): ?Item
    {
        if (isset(self::$items[$id])) {
            return clone self::$items[$id];
        }
        $item = StringToItemParser::getInstance()->parse($id);
        if ($item !== null) {
            return $item;
        }
        try {
            return GlobalItemDataHandlers::getDeserializer()->deserializeType(new SavedItemData($id));
        } catch (\Throwable) {
            return null;
        }
    }
}
