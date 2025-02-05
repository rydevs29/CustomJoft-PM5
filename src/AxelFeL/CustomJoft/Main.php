<?php

declare(strict_types=1);

namespace AxelFeL\CustomJoft;

use pocketmine\Server;
use pocketmine\player\Player;

use pocketmine\plugin\PluginBase;

use BlockHorizons\Fireworks\entity\FireworksRocket;
use BlockHorizons\Fireworks\item\Fireworks;

use pocketmine\item\VanillaItems;
use pocketmine\utils\Config;

use pocketmine\network\mcpe\protocol\LevelEventPacket;
use pocketmine\network\mcpe\protocol\types\LevelEvent;

use _64FF00\PurePerms\PurePerms;

use pocketmine\event\Listener;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\player\PlayerLoginEvent;

class Main extends PluginBase implements Listener {
    
    public function onEnable(): void{
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
        $this->saveResource("config.yml");
    }
    
    public function onJoin(PlayerJoinEvent $event): void {
        $player = $event->getPlayer();
        $name = $player->getName();

        // Menggunakan PurePerms untuk mendapatkan rank
        $rank = $this->getPlayerRank($player);

        // Menampilkan pesan join jika diaktifkan dalam config
        if ($this->getConfig()->get("join-message") !== false) {
            $event->setJoinMessage(str_replace(["{name}", "{rank}"], [$name, $rank], $this->getConfig()->get("join-message")));
        }

        // Efek Guardian jika diaktifkan
        if ($this->getConfig()->get("guardian-effect") !== false) {
            $pk = LevelEventPacket::create(LevelEvent::GUARDIAN_CURSE, 1, $player->getPosition());
            $player->getNetworkSession()->sendDataPacket($pk);
        } 

        // Firework jika diaktifkan dan plugin Fireworks tersedia
        if ($this->getConfig()->get("join-firework") !== false && class_exists(Fireworks::class)) {
            $location = $player->getLocation();
            $fw = VanillaItems::FIREWORKS();
            $fw->addExplosion(Fireworks::TYPE_CREEPER_HEAD, Fireworks::COLOR_GREEN, "", false, false);
            $fw->setFlightDuration($this->getConfig()->get("flight-duration"));
            $entity = new FireworksRocket($location, $fw);
            $entity->spawnToAll();
        }
    }
    
    public function onLogin(PlayerLoginEvent $event): void {
        $player = $event->getPlayer();
        $name = $player->getName();

        if ($this->getConfig()->get("join-title") !== false) {
            $player->sendTitle(str_replace("{name}", $name, $this->getConfig()->get("join-title")));
        }
        if ($this->getConfig()->get("join-subtitle") !== false) {
            $player->sendSubTitle(str_replace("{name}", $name, $this->getConfig()->get("join-subtitle")));
        }
    }
    
    public function onQuit(PlayerQuitEvent $event): void {
        $player = $event->getPlayer();
        $name = $player->getName();

        // Menggunakan PurePerms untuk mendapatkan rank
        $rank = $this->getPlayerRank($player);

        if ($this->getConfig()->get("left-message") !== false) {
            $event->setQuitMessage(str_replace(["{name}", "{rank}"], [$name, $rank], $this->getConfig()->get("left-message")));
        }
    }

    /**
     * Fungsi untuk mendapatkan rank pemain menggunakan PurePerms
     */
    private function getPlayerRank(Player $player): string {
        $purePerms = $this->getServer()->getPluginManager()->getPlugin("PurePerms");
        if ($purePerms instanceof PurePerms) {
            $group = $purePerms->getUserDataMgr()->getGroup($player);
            return $group ? $group->getName() : $this->getConfig()->get("default-rank-name");
        }
        return $this->getConfig()->get("default-rank-name");
    }
}