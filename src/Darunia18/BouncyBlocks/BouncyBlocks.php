<?php

namespace Darunia18\BouncyBlocks;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\event\Listener;
use pocketmine\math\Vector3;
use pocketmine\Player;
use pocketmine\plugin\PluginBase;

class BouncyBlocks extends PluginBase implements Listener{
    
    private $max;
    private $blocks;
    
    public $fall;
    public $bounceVelocity;
    public $disabled;
    
    public function onEnable(){
        $this->saveDefaultConfig();

        $this->max = $this->getConfig("max");
        $this->blocks = $this->getConfig("blocks");
        $this->fall = new \SplObjectStorage();
        $this->bounceVelocity = new \SplObjectStorage();
        $this->disabled = new \SplObjectStorage();
    }

    public function onDisable(){
        $this->saveConfig();
    }
    
    public function onCommand(CommandSender $sender, Command $command, $label, array $args){
        switch($command->getName()){
            case "bounce":
                if(isset($args[0])){
                    switch($args[0]){
                
                        case "false":
                            $this->disabled->attach($sender);
                            $sender->sendMessage("You will no longer bounce on blocks");
                            return true;
                        break;
                
                        case "true":
                            $this->disabled->detach($sender);
                            $sender->sendMessage("You will now bounce on blocks");
                            return true;
                        break;
                    
                        default:
                            $sender->sendMessage("Usage: /bounce <true|false>");
                            return true;
                        break;
                    }
                }
                else{
                    $sender->sendMessage("Usage: /bounce <true|false>");
                    return true;
                }
            break;
        
            default:
                return false;
        }
    }
    
    public function onEntityDamage(EntityDamageEvent $event){
        
        if($event->getEntity() instanceof Player){
            $player = $event->getEntity();
            
            if(isset($this->fall[$player]) && $event->getCause() == 4 && (!$player->hasPermission("bouncyblocks.takedamage") || $player->isOp())){
                $event->setCancelled();
            }
        }
    }
    
    public function onPlayerMove(PlayerMoveEvent $event){
        $player = $event->getPlayer();
        
        if($player->hasPermission("bouncyblocks.bounce") && !isset($this->disabled[$player])){
            $block = $player->getLevel()->getBlockIdAt($player->x, ($player->y -0.1), $player->z);
            
            if($block != 0 && in_array($block, $this->blocks)){
                
                if(!isset($this->bounceVelocity[$player]) || $this->bounceVelocity[$player] == 0.0){
                    $this->bounceVelocity[$player] = ($player->getMotion()->getY() + 0.2);
                }
                
                if($this->bounceVelocity[$player] <= $this->max){
                    $this->bounceVelocity[$player] = ($this->bounceVelocity[$player] + 0.2);
                }
                
                $this->fall->attach($player);
                $motion = new Vector3($player->motionX, $player->motionY, $player->motionZ);
                $motion->y = $this->bounceVelocity[$player];
                $player->setMotion($motion);
            }
            
            if(isset($this->fall[$player])){
                
                if(!$block == 0 && !in_array($block, $this->blocks)){
                    $this->fall->detach($player);
                    $this->bounceVelocity[$player] = 0.0;
                }
            }
        }
    }
}
