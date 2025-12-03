<?php

declare(strict_types=1);

namespace AxelFeL\CustomJoft;

use pocketmine\Server;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerQuitEvent;

// Imports untuk Item dan Fireworks
use pocketmine\item\VanillaItems;
use pocketmine\item\Fireworks; // Class Item bawaan PM5
use pocketmine\item\FireworksExplosion; // Class Ledakan bawaan PM5
use pocketmine\color\Color; // Diperlukan untuk warna kembang api

// Imports untuk Packet
use pocketmine\network\mcpe\protocol\LevelEventPacket;
use pocketmine\network\mcpe\protocol\types\LevelEvent;

// Imports untuk PurePerms
use _64FF00\PurePerms\PurePerms;

// Import untuk Entity kembang api (Pastikan plugin BlockHorizons\Fireworks terpasang)
use BlockHorizons\Fireworks\entity\FireworksRocket;

class Main extends PluginBase implements Listener {
    
    public function onEnable(): void{
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
        $this->saveResource("config.yml");
    }
    
    public function onJoin(PlayerJoinEvent $event): void {
        $player = $event->getPlayer();
        $name = $player->getName();

        // 1. GET RANK
        $rank = $this->getPlayerRank($player);

        // 2. JOIN MESSAGE
        if ($this->getConfig()->get("join-message") !== false) {
            $event->setJoinMessage(str_replace(["{name}", "{rank}"], [$name, $rank], (string)$this->getConfig()->get("join-message")));
        }

        // 3. TITLE & SUBTITLE (Dipindah dari onLogin ke onJoin agar terlihat)
        if ($this->getConfig()->get("join-title") !== false || $this->getConfig()->get("join-subtitle") !== false) {
            $title = (string)$this->getConfig()->get("join-title", "");
            $subtitle = (string)$this->getConfig()->get("join-subtitle", "");
            
            // Replace placeholder
            $title = str_replace("{name}", $name, $title);
            $subtitle = str_replace("{name}", $name, $subtitle);

            // Kirim Title (Format PM5: Title, Subtitle, FadeIn, Stay, FadeOut)
            $player->sendTitle($title, $subtitle, 20, 60, 20);
        }

        // 4. GUARDIAN EFFECT
        if ($this->getConfig()->get("guardian-effect") !== false) {
            // PM5: Coordinate harus berupa Vector3 object
            $pk = LevelEventPacket::create(LevelEvent::GUARDIAN_CURSE, 1, $player->getPosition());
            $player->getNetworkSession()->sendDataPacket($pk);
        } 

        // 5. FIREWORKS
        // Cek apakah fitur aktif dan Class Entity dari plugin Fireworks ada
        if ($this->getConfig()->get("join-firework") !== false && class_exists(FireworksRocket::class)) {
            $location = $player->getLocation();
            
            /** @var Fireworks $fw */
            $fw = VanillaItems::FIREWORKS();
            
            // Syntax PM5: Membuat object Explosion
            $explosion = new FireworksExplosion(
                FireworksExplosion::TYPE_CREEPER_HEAD, 
                [Color::fromRGB(0, 255, 0)], // Warna Hijau (RGB)
                [], // Fade colors
                false, // Flicker
                false // Trail
            );
            
            $fw->addExplosion($explosion);
            
            // Mengatur durasi terbang (flight duration)
            $fw->setFlightDuration((int)$this->getConfig()->get("flight-duration", 1));
            
            // Spawn Entity (Membutuhkan plugin BlockHorizons/Fireworks)
            $entity = new FireworksRocket($location, $fw);
            $entity->spawnToAll();
        }
    }
    
    // onLogin dihapus karena Title lebih baik dikirim saat onJoin
    
    public function onQuit(PlayerQuitEvent $event): void {
        $player = $event->getPlayer();
        $name = $player->getName();
        $rank = $this->getPlayerRank($player);

        if ($this->getConfig()->get("left-message") !== false) {
            $event->setQuitMessage(str_replace(["{name}", "{rank}"], [$name, $rank], (string)$this->getConfig()->get("left-message")));
        }
    }

    private function getPlayerRank(Player $player): string {
        $purePerms = $this->getServer()->getPluginManager()->getPlugin("PurePerms");
        
        // Cek apakah plugin PurePerms aktif dan valid
        if ($purePerms instanceof PurePerms) {
            $group = $purePerms->getUserDataMgr()->getGroup($player);
            return $group ? $group->getName() : (string)$this->getConfig()->get("default-rank-name");
        }
        
        return (string)$this->getConfig()->get("default-rank-name", "Member");
    }
}
