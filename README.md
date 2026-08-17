## 🏠 Adopted by axolotl-plugin-orphanage

This plugin has been adopted by [**axolotl-plugin-orphanage**](https://github.com/axolotl-plugin-orphanage) due to prolonged inactivity from its original author. It will continue to be maintained and updated to follow [**axolotl-pm/PocketMine-MP**](https://github.com/axolotl-pm/PocketMine-MP).

> [!NOTE]
> This plugin is primarily supported on **axolotl-pm**. Compatibility with other PocketMine-MP forks is not guaranteed and no support will be provided for them.

Join the **axolotl-pm** community for support, updates and discussion:
<div align="center">

[![axolotl-pm Discord](https://img.shields.io/discord/1526008653482692768?label=axolotl-pm+Discord&style=for-the-badge&logo=discord&logoColor=white&color=5865F2)](https://discord.gg/XbUXqtVPqp)
</div>

### 📥 Download
<div align="center">

[![Stable Release](https://img.shields.io/github/v/release/axolotl-plugin-orphanage/DummyItemsBlocks?label=Stable+Release&style=for-the-badge&logo=github&logoColor=white&color=2292ee)](https://github.com/axolotl-plugin-orphanage/DummyItemsBlocks/releases/latest/download/DummyItemsBlocks.phar)
[![Nightly Build](https://img.shields.io/badge/dynamic/yaml?url=https://raw.githubusercontent.com/axolotl-plugin-orphanage/DummyItemsBlocks/main/plugin.yml&query=$.version&label=Nightly+Build&prefix=v&color=blueviolet&style=for-the-badge&logo=github&logoColor=white)](https://github.com/axolotl-plugin-orphanage/DummyItemsBlocks/releases/download/nightly/DummyItemsBlocks.phar)

*Stable recommended for production · Nightly always up to date from the latest commit*
</div>

![DummyItemsBlocks](https://socialify.git.ci/diamond-gold/DummyItemsBlocks/image?description=1&font=Inter&forks=1&issues=1&logo=https%3A%2F%2Fgithub.com%2Fdiamond-gold%2FDummyItemsBlocks%2Fraw%2Fmain%2Ficon.gif&name=1&owner=1&pattern=Circuit%20Board&pulls=1&stargazers=1&theme=Light)

> Items and blocks added are not functional, they are just for decoration purposes.
>
> Supports all items and blocks up to Minecraft 1.26.44 (26.44)
>
> Allows worlds with such items or blocks to be loaded
> 
> Freedom to control items and blocks added via `config.yml`

[![](https://poggit.pmmp.io/ci.shield/diamond-gold/DummyItemsBlocks/DummyItemsBlocks?style=for-the-badge)](https://poggit.pmmp.io/ci/diamond-gold/DummyItemsBlocks/~)

[![](https://poggit.pmmp.io/shield.api/DummyItemsBlocks?style=for-the-badge)](https://poggit.pmmp.io/p/DummyItemsBlocks)
[![](https://poggit.pmmp.io/shield.downloads/DummyItemsBlocks?style=for-the-badge)](https://poggit.pmmp.io/p/DummyItemsBlocks)
[![](https://poggit.pmmp.io/shield.downloads.total/DummyItemsBlocks?style=for-the-badge)](https://poggit.pmmp.io/p/DummyItemsBlocks)
[![](https://poggit.pmmp.io/shield.state/DummyItemsBlocks?style=for-the-badge)](https://poggit.pmmp.io/p/DummyItemsBlocks)

Encountered a bug? Want something added? Create an [issue](https://github.com/diamond-gold/DummyItemsBlocks/issues) (＃°Д°)

Have questions? Start a [discussion](https://github.com/diamond-gold/DummyItemsBlocks/discussions) ✍(◔◡◔)

Starring the GitHub repository is like sprinkling stardust—help me create magic! (。・∀・)ノ

Feeling generous? Buy me some [snacks](https://ko-fi.com/diamondgold)! (❤´艸｀❤)

[![Feature Requests](https://img.shields.io/github/issues-raw/diamond-gold/DummyItemsBlocks/Feature%20Request?label=Feature%20Requests&logo=github&style=for-the-badge)](https://github.com/diamond-gold/DummyItemsBlocks/issues)
[![Bug Reports](https://img.shields.io/github/issues-raw/diamond-gold/DummyItemsBlocks/bug?label=Bug%20Reports&logo=github&style=for-the-badge)](https://github.com/diamond-gold/DummyItemsBlocks/issues)

<img src="https://counter.seku.su/cmoe?name=dummyitemsblocks&theme=r34" alt="">

# Known issue when another plugin that add vanilla items or blocks is installed
`
[Server thread/CRITICAL]: InvalidArgumentException: "Deserializer is already assigned for "minecraft:shield"" (EXCEPTION) in "pmsrc/src/data/bedrock/item/ItemDeserializer" at line 55
`

> If you encounter similar error in another plugin, please delete `minecraft:shield` in `config.yml`
> 
> Replace `minecraft:shield` with the item or block specified in the error message.

# Special behavior

By default, the following is required to change block states by right-clicking
- Player is in creative mode
- Have `dummyitemsblocks.changestate` permission, default: op
- Holding an arrow named `Change State` in either main hand or offhand
  - Can be obtained by: ```/give player arrow 1 {display:{Name:"Change State"}}```

Customizable using the `Main::setCanChangeStatesClosure()` method

### Examples of block state changes by right-clicking:
|      Block      | Sneak-right-click |     Right-click     |
|:---------------:|:-----------------:|:-------------------:|
| (Soul) Campfire |    Toggle fire    |     Place item      |
|     Crafter     | Toggle triggered  |   Toggle crafting   |
|  Decorated Pot  | Toggle animation  | Place sherd pattern |
| (Sticky) Piston |         -         |   Toggle extended   |
|   Turtle Egg    |  Increase cracks  | Increase egg Count  |

There are many other blocks that can change state by right-clicking.

Icon created using textures from: https://modrinth.com/resourcepack/vanillaxbr
