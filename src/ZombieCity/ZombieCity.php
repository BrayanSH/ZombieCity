<?php

namespace ZombieCity;

use pocketmine\plugin\PluginBase;
use pocketmine\event\Listener;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\level\Position;
use pocketmine\level\Level;
use pocketmine\item\Item;
use pocketmine\Player;
use pocketmine\Server;
use pocketmine\utils\Config;
use pocketmine\math\Vector3;
use pocketmine\entity\Entity;
use pocketmine\entity\Zombie;
use pocketmine\level\format\mcregion\Chunk;
use pocketmine\level\format\FullChunk;
use pocketmine\scheduler\PluginTask;
use pocketmine\scheduler\CallbackTask;
use pocketmine\block\Block;
use pocketmine\network\protocol\AddEntityPacket;
use pocketmine\network\protocol\SetEntityMotionPacket;
use pocketmine\network\protocol\MoveEntityPacket;
use pocketmine\event\entity\EntityDeathEvent;
use pocketmine\nbt\NBT;
use pocketmine\nbt\tag\Byte;
use pocketmine\nbt\tag\Compound;
use pocketmine\nbt\tag\Double;
use pocketmine\nbt\tag\Enum;
use pocketmine\nbt\tag\Float;
use pocketmine\nbt\tag\Int;
use pocketmine\nbt\tag\String;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\tile\Sign;
use pocketmine\tile\Tile;
use pocketmine\utils\TextFormat;
use pocketmine\event\player\PlayerDeathEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\Entity\EntityShootBowEvent;
use ZombieCity\Entities\Skeleton;
use pocketmine\level\Position\getLevel;
use pocketmine\level\particle\FloatingTextParticle;
use pocketmine\level\particle\Particle;

class ZombieCity extends PluginBase implements Listener{
	private $zombie = array();
	private $Skeleton= [];
 	public $width = 0.4;  //僵尸宽度
	private $lz; //僵尸最后实体id
	private $nbt; 
	private $dif = 2;
	public $hatred_r = 100;  //仇恨半径
	public $birth = 30;  //僵尸出生间隔秒数
	public $birth_r = 8;  //僵尸出生半径
	private $gameststus;
	private $players = array();
	private $zombiespawn;
	private $gametime;
	private $tile = array();
	private $zpos1 = array();
	private $zpos2 = array();
	private $zpos3 = array();
	private $zpos4 = array();
	private $zpos5 = array();
	private $spos = array();
	private $waitpos1 = array();
	private $waitpos2 = array();
	private $level;
	private $am = 3;
	private $SetStatus = array();
	private $firsthurt = 1;
	public $point = array();
    public $repeattime = array();
	//private $line = array(),$color = array(),$StartSign = array(),$Top1 = array(),$Top2 = array(),$Top3 = array();
	
	public function onEnable(){ 
		$this->getLogger()->info("ZombieCity Is Loading!");
		$this->getServer()->getPluginManager()->registerEvents ( $this, $this );
		$this->getServer()->getScheduler()->scheduleRepeatingTask(new CallbackTask ([$this,"gametimer"]),20);	
		$this->getServer ()->getScheduler ()->scheduleRepeatingTask ( new CallbackTask ( [ $this,"ZombieRandomWalkCalc" ] ), 10 );
		$this->getServer ()->getScheduler ()->scheduleRepeatingTask ( new CallbackTask ( [ $this,"ZombieRandomWalk" ] ), 1 );
		$this->getServer ()->getScheduler ()->scheduleRepeatingTask ( new CallbackTask ( [ $this,"ZombieYaw"] ), 1 );
		$this->getServer ()->getScheduler ()->scheduleRepeatingTask ( new CallbackTask ( [ $this,"ZombieRotation"] ), 1 );
		$this->getServer ()->getScheduler ()->scheduleRepeatingTask ( new CallbackTask ( [ $this,"SkeletonRandomWalkCalc" ] ), 10 );
		$this->getServer ()->getScheduler ()->scheduleRepeatingTask ( new CallbackTask ( [ $this,"SkeletonRandomWalk" ] ), 1 );
		$this->getServer ()->getScheduler ()->scheduleRepeatingTask ( new CallbackTask ( [ $this,"SkeletonYaw"] ), 1 );
		$this->getServer ()->getScheduler ()->scheduleRepeatingTask ( new CallbackTask ( [ $this,"SkeletonRotation"] ), 1 );
		$this->getServer ()->getScheduler ()->scheduleRepeatingTask ( new CallbackTask ( [ $this,"AddHealth" ] ), 20 );
		@mkdir($this->getDataFolder(), 0777, true);
		$this->config = new Config($this->getDataFolder() . "config.yml", Config::YAML, array());
		if($this->config->exists("tile") AND $this->config->get("tile") !== array()){
			$this->tile =  $this->config->get("tile");
			$this->zpos1 =  $this->config->get("zpos1");
			$this->zpos2 =  $this->config->get("zpos2");
			$this->zpos3 =  $this->config->get("zpos3");
			$this->zpos4 =  $this->config->get("zpos4");
			$this->zpos5 =  $this->config->get("zpos5");
			$this->spos =  $this->config->get("spos");
			$this->waitpos1 =  $this->config->get("waitpos1");
			$this->waitpos2 =  $this->config->get("waitpos2");
			$this->level =  $this->config->get("level");
			$this->tile = new Vector3($this->tile["x"],$this->tile["y"],$this->tile["z"]);
			$this->zpos1 = new Vector3($this->zpos1["x"],$this->zpos1["y"],$this->zpos1["z"]);
			$this->zpos2 = new Vector3($this->zpos2["x"],$this->zpos2["y"],$this->zpos2["z"]);
			$this->zpos3 = new Vector3($this->zpos3["x"],$this->zpos3["y"],$this->zpos3["z"]);
			$this->zpos4 = new Vector3($this->zpos4["x"],$this->zpos4["y"],$this->zpos4["z"]);
			$this->zpos5 = new Vector3($this->zpos5["x"],$this->zpos5["y"],$this->zpos5["z"]);
			$this->spos = new Vector3($this->spos["x"],$this->spos["y"],$this->spos["z"]);
			$this->waitpos1 = new Vector3($this->waitpos1["x"],$this->waitpos1["y"],$this->waitpos1["z"]);
			$this->waitpos2 = new Vector3($this->waitpos2["x"],$this->waitpos2["y"],$this->waitpos2["z"]);
			$this->gameststus = "prepare";
		}else{
		$this->getLogger()->info(TextFormat::RED."ZombieCity is Unset !!!");
		$this->gameststus = "unset";
		}
		$this->getLogger()->info("ZombieCity Loaded !!!!");
	}

	public function onCommand(CommandSender $sender, Command $command, $label, array $args){
		switch($command->getName()){
			case "zomset":
				switch ($args[0]) {
					case 'set':
						if($this->config->getAll() !==  array()){
							$sender->sendmessage("小游戏 *僵尸围城* 已经设置,请使用命令 : /zomset del 来删除设置!");
						}else{
							$name = $sender->getName();
							$this->SetStatus[$name] = 0;
							$sender->sendmessage("你已经处于设置状态下,请先设置状态牌");
						}
						break;

					case 'del':
						$this->config->setAll(array());
						$this->config->save();
						unset($this->tile);
						unset($this->zpos1);
						unset($this->zpos2);
						unset($this->zpos3);
						unset($this->zpos4);
						unset($this->zpos5);
						$sender->sendmessage("清除了小游戏 *僵尸围城* 的设置");
						break;

					default:
						$sender->sendtip("命令 : /zomset <set/del>");
						break;
				}
				return true;
			default:
				return false;
		}
	}
	
	public function AddHealth(){
	if($this->gameststus == "start" or $this->gameststus == "round2"){
    foreach($this->players as $pl){
		$p = $this->getServer()->getPlayer($pl["id"]);
			if($p != false){
				$pn = $p->getName();
				if(!isset($this->repeattime[$pn])){
					$this->point[$pn]["X"] = $p->getX();
					$this->point[$pn]["Y"] = $p->getY();
					$this->point[$pn]["Z"] = $p->getZ();
					$this->repeattime[$pn] = 0;
				}else{
					if($this->point[$pn]["X"] == $p->getX() and $this->point[$pn]["Y"] == $p->getY() and $this->point[$pn]["Z"] == $p->getZ()){
						$this->repeattime[$pn]++;
						if($this->repeattime[$pn] >= 3){
							if($p->getHealth() < 20 ){
								$p->setHealth($p->getHealth() + 2);
								$p->sendtip(TextFormat::RED."回血中。。。");
								foreach($this->players as $pl){
									$pp = $this->getServer()->getPlayer($pl["id"]);
									if($pp != false and $pp != $p){
										$pp->sendtip(TextFormat::YELLOW."玩家".$pn."正在回血，快去保护他/她");
									}
								}
							}
						}
					}else{
					unset($this->repeattime[$pn]);
					}
				}
			}
		}
	}
	}
	   
	public function getNBT($v3) {
		$nbt = new Compound("", [
			"Pos" => new Enum("Pos", [
				new Double("", 0),
				new Double("", 0),
				new Double("", 0)
			]),
			"Motion" => new Enum("Motion", [
				new Double("", 0),
				new Double("", 0),
				new Double("", 0)
			]),
			"Rotation" => new Enum("Rotation", [
				new Float("", 0),
				new Float("", 0)
			]),
		]);
		return $nbt;
	}
	
	public function ZombieYaw() {//转身
		foreach ($this->getServer()->getLevels() as $level) {
		foreach ($level->getEntities() as $zo){
		if($zo instanceof Zombie){
		if(count($zo->getViewers() != 0)) {
			if(isset($this->zombie[$zo->getId()])){	
				$zom = &$this->zombie[$zo->getId()];
				$yaw0 = $zo->yaw;  //实际yaw
				$yaw = $zom['yaw']; //目标yaw
				if (abs($yaw0 + $yaw) <= 180) {  //-180到+180正方向
					if ($yaw0 <= $yaw) {  //实际在目标左边
						if ($yaw - $yaw0 <= 10) {
							$yaw0 = $yaw;
						}
						else {
							$yaw0 += 10;
						}
					}
					else {  ////实际在目标右边
						if ($yaw0 - $yaw <= 10) {
							$yaw0 = $yaw;
						}
						else {
							$yaw0 -= 10;
						}
					}
				}
				else {  ////+180到-180方向
					if ($yaw0 >= $yaw) {  //实际在目标左边
						if ($yaw0 - $yaw <= 5) {
							$yaw0 = $yaw;
						}
						else {
							$yaw0 -= 5;
							if ($yaw0 <= -180) $yaw0 = $yaw0 + 360;
						}
					}
					else {  ////实际在目标右边
						if ($yaw - $yaw0 <= 5) {
							$yaw0 = $yaw;
						}
						else {
							$yaw0 += 5;
							if ($yaw0 >= 180) $yaw0 = $yaw0 - 360;
						}
					}
				}
				$zo->setRotation($yaw0,0);
			}
		}
		}
		}
		}
	}
	
	public function ZombieRotation() {
	if(count($this->zombie) > 0){
	$filter_res = array_filter($this->zombie);
		if(!empty($filter_res)){
			foreach ($this->zombie as $zoms) {
				$level=$this->getServer()->getLevelByName($zoms['level']);
				$zo = $level->getEntity($zoms['ID']);
				if($zo != false){
					if(count($zo->getViewers()) != 0) {
						if(isset($this->zombie[$zo->getId()])){
							$zom = &$this->zombie[$zo->getId()];
							$yaw0 = $zo->yaw;  //实际yaw
							$yaw = $zom['yaw']; //目标yaw
							//$this->getLogger()->info($yaw0.' '.$yaw);
							if (abs($yaw0 - $yaw) <= 180) {  //-180到+180正方向
								if ($yaw0 <= $yaw) {  //实际在目标左边
									if ($yaw - $yaw0 <= 15) {
										$yaw0 = $yaw;
									}
									else {
										$yaw0 += 15;
									}
								}
								else {  ////实际在目标右边
									if ($yaw0 - $yaw <= 15) {
										$yaw0 = $yaw;
									}
									else {
										$yaw0 -= 15;
									}
								}
							}
							else {  ////+180到-180方向
								if ($yaw0 >= $yaw) {  //实际在目标左边
									if ((180-$yaw0) + ($yaw+180) <= 15) {
										$yaw0 = $yaw;
									}
									else {
										$yaw0 += 15;
										if ($yaw0 >= 180) $yaw0 = $yaw0 - 360;
									}
								}
								else {  ////实际在目标右边
									if ((180-$yaw) - ($yaw0+180) <= 15) {
										$yaw0 = $yaw;
									}
									else {
										$yaw0 -= 15;
										if ($yaw0 <= 180) $yaw0 = $yaw0 + 360;
									}
								}
							}
							$pitch0 = $zo->pitch;  //实际pitch
							$pitch = $zom['pitch']; //目标pitch

							if (abs($pitch0-$pitch) <= 15) {
								$pitch0 = $pitch;
							}
							elseif ($pitch > $pitch0) {
								$pitch0 += 10;
							}
							elseif ($pitch < $pitch0) {
								$pitch0 -= 10;
							}

							$zo->setRotation($yaw0,$pitch0);
						}
					}
				}
			}
		}
	}
	}
	
	public function ZombieRandomWalkCalc(){//计算行进路线
	if(count($this->zombie) > 0){
	$filter_res = array_filter($this->zombie);
	if(!empty($filter_res)){
	foreach ($this->zombie as $zoms) {
	$level=$this->getServer()->getLevelByName($zoms['level']);
	 $zo = $level->getEntity($zoms['ID']);
	 if($zo != false){
			$zom = &$this->zombie[$zo->getId()];
			$zom['x'] = $zo->getX();
			$zom['y'] = $zo->getY();
			$zom['z'] = $zo->getZ();
			$this->lz = $zo->getId() + 1;
			$zo->setMaxHealth(15);		
			$zom = &$this->zombie[$zo->getId()];
			
			if ($zom['IsChasing'] == "0") {  //自由行走模式
							if ($zom['gotimer'] == 0 or $zom['gotimer'] == 10) {
								//限制转动幅度
								$newmx = mt_rand(-5,5)/10;
								while (abs($newmx - $zom['motionx']) >= 0.7) {
									$newmx = mt_rand(-5,5)/10;
								}
								$zom['motionx'] = $newmx;

								$newmz = mt_rand(-5,5)/10;
								while (abs($newmz - $zom['motionz']) >= 0.7) {
									$newmz = mt_rand(-5,5)/10;
								}
								$zom['motionz'] = $newmz;
							}
							elseif ($zom['gotimer'] >= 20 and $zom['gotimer'] <= 24) {
								$zom['motionx'] = 0;
								$zom['motionz'] = 0;
								//僵尸停止
							}

							$zom['gotimer'] += 0.5;
							if ($zom['gotimer'] >= 22) $zom['gotimer'] = 0;  //重置走路计时器

							//$zom['motionx'] = mt_rand(-10,10)/10;
							//$zom['motionz'] = mt_rand(-10,10)/10;
							$zom['yup'] = 0;
							$zom['up'] = 0;

							//boybook的y轴判断法
							//$width = $this->width;
							$pos = new Vector3 ($zom['x'] + $zom['motionx'], floor($zo->getY()) + 1,$zom['z'] + $zom['motionz']);  //目标坐标
							$zy = $this->ifjump($zo->getLevel(),$pos);
							if ($zy === false) {  //前方不可前进
								$pos2 = new Vector3 ($zom['x'], $zom['y'] ,$zom['z']);  //目标坐标
								if ($this->ifjump($zo->getLevel(),$pos2) === false) { //原坐标依然是悬空
									$pos2 = new Vector3 ($zom['x'], $zom['y']-1,$zom['z']);  //下降
									$zom['up'] = 1;
									$zom['yup'] = 0;
								}
								else {
									$zom['motionx'] = - $zom['motionx'];
									$zom['motionz'] = - $zom['motionz'];
									//转向180度，向身后走
									$zom['up'] = 0;
								}
							}
							else {
								$pos2 = new Vector3 ($zom['x'] + $zom['motionx'], $zy - 1 ,$zom['z'] + $zom['motionz']);  //目标坐标
								if ($pos2->y - $zom['y'] < 0) {
									$zom['up'] = 1;
								}
								else {
									$zom['up'] = 0;
								}
							}

							if ($zom['motionx'] == 0 and $zom['motionz'] == 0) {  //僵尸停止
							}
							else {
								//转向计算
								$yaw = $this->getyaw($zom['motionx'], $zom['motionz']);
								//$zo->setRotation($yaw,0);
								$zom['yaw'] = $yaw;
								$zom['pitch'] = 0;
							}

							//更新僵尸坐标
							$zom['x'] = $pos2->getX();
							$zom['z'] = $pos2->getZ();
							$zom['y'] = $pos2->getY();
							$zom['motiony'] = $pos2->getY() - $zo->getY();
							//echo($zo->getY()."\n");
							//var_dump($pos2);
							//var_dump($zom['motiony']);
							$zo->setPosition($pos2);
							//echo "SetPosition \n";
						}
					}
		}
		}
		}
	}
	
	public function SkeletonYaw() {//转身
		foreach ($this->getServer()->getLevels() as $level) {
		foreach ($level->getEntities() as $zo){
		if($zo instanceof Skeleton){
		if(count($zo->getViewers() != 0)) {
			if(isset($this->Skeleton[$zo->getId()])){	
				$zom = &$this->Skeleton[$zo->getId()];
				$yaw0 = $zo->yaw;  //实际yaw
				$yaw = $zom['yaw']; //目标yaw
				if (abs($yaw0 + $yaw) <= 180) {  //-180到+180正方向
					if ($yaw0 <= $yaw) {  //实际在目标左边
						if ($yaw - $yaw0 <= 10) {
							$yaw0 = $yaw;
						}
						else {
							$yaw0 += 10;
						}
					}
					else {  ////实际在目标右边
						if ($yaw0 - $yaw <= 10) {
							$yaw0 = $yaw;
						}
						else {
							$yaw0 -= 10;
						}
					}
				}
				else {  ////+180到-180方向
					if ($yaw0 >= $yaw) {  //实际在目标左边
						if ($yaw0 - $yaw <= 5) {
							$yaw0 = $yaw;
						}
						else {
							$yaw0 -= 5;
							if ($yaw0 <= -180) $yaw0 = $yaw0 + 360;
						}
					}
					else {  ////实际在目标右边
						if ($yaw - $yaw0 <= 5) {
							$yaw0 = $yaw;
						}
						else {
							$yaw0 += 5;
							if ($yaw0 >= 180) $yaw0 = $yaw0 - 360;
						}
					}
				}
				$zo->setRotation($yaw0,0);
			}
		}
		}
		}
		}
	}
	
	public function SkeletonRotation() {
	if(count($this->Skeleton) > 0){
	$filter_res = array_filter($this->Skeleton);
		if(!empty($filter_res)){
			foreach ($this->Skeleton as $zoms) {
				$level=$this->getServer()->getLevelByName($zoms['level']);
				$zo = $level->getEntity($zoms['ID']);
				if($zo != false){
					if(count($zo->getViewers()) != 0) {
						if(isset($this->Skeleton[$zo->getId()])){
							$zom = &$this->Skeleton[$zo->getId()];
							$yaw0 = $zo->yaw;  //实际yaw
							$yaw = $zom['yaw']; //目标yaw
							//$this->getLogger()->info($yaw0.' '.$yaw);
							if (abs($yaw0 - $yaw) <= 180) {  //-180到+180正方向
								if ($yaw0 <= $yaw) {  //实际在目标左边
									if ($yaw - $yaw0 <= 15) {
										$yaw0 = $yaw;
									}
									else {
										$yaw0 += 15;
									}
								}
								else {  ////实际在目标右边
									if ($yaw0 - $yaw <= 15) {
										$yaw0 = $yaw;
									}
									else {
										$yaw0 -= 15;
									}
								}
							}
							else {  ////+180到-180方向
								if ($yaw0 >= $yaw) {  //实际在目标左边
									if ((180-$yaw0) + ($yaw+180) <= 15) {
										$yaw0 = $yaw;
									}
									else {
										$yaw0 += 15;
										if ($yaw0 >= 180) $yaw0 = $yaw0 - 360;
									}
								}
								else {  ////实际在目标右边
									if ((180-$yaw) - ($yaw0+180) <= 15) {
										$yaw0 = $yaw;
									}
									else {
										$yaw0 -= 15;
										if ($yaw0 <= 180) $yaw0 = $yaw0 + 360;
									}
								}
							}
							$pitch0 = $zo->pitch;  //实际pitch
							$pitch = $zom['pitch']; //目标pitch

							if (abs($pitch0-$pitch) <= 15) {
								$pitch0 = $pitch;
							}
							elseif ($pitch > $pitch0) {
								$pitch0 += 10;
							}
							elseif ($pitch < $pitch0) {
								$pitch0 -= 10;
							}

							$zo->setRotation($yaw0,$pitch0);
						}
					}
				}
			}
		}
	}
	}
	
	public function SkeletonRandomWalkCalc(){//计算行进路线
	if(count($this->Skeleton) > 0){
	$filter_res = array_filter($this->Skeleton);
	if(!empty($filter_res)){
	foreach ($this->Skeleton as $zoms) {
	$level=$this->getServer()->getLevelByName($zoms['level']);
	 $zo = $level->getEntity($zoms['ID']);
	 if($zo != false){
			$zom = &$this->Skeleton[$zo->getId()];
			$zom['x'] = $zo->getX();
			$zom['y'] = $zo->getY();
			$zom['z'] = $zo->getZ();
			$this->lz = $zo->getId() + 1;
			$zo->setMaxHealth(15);		
			$zom = &$this->Skeleton[$zo->getId()];
			
			if ($zom['IsChasing'] == "0") {  //自由行走模式
							if ($zom['gotimer'] == 0 or $zom['gotimer'] == 10) {
								//限制转动幅度
								$newmx = mt_rand(-5,5)/10;
								while (abs($newmx - $zom['motionx']) >= 0.7) {
									$newmx = mt_rand(-5,5)/10;
								}
								$zom['motionx'] = $newmx;

								$newmz = mt_rand(-5,5)/10;
								while (abs($newmz - $zom['motionz']) >= 0.7) {
									$newmz = mt_rand(-5,5)/10;
								}
								$zom['motionz'] = $newmz;
							}
							elseif ($zom['gotimer'] >= 20 and $zom['gotimer'] <= 24) {
								$zom['motionx'] = 0;
								$zom['motionz'] = 0;
								//僵尸停止
							}

							$zom['gotimer'] += 0.5;
							if ($zom['gotimer'] >= 22) $zom['gotimer'] = 0;  //重置走路计时器

							//$zom['motionx'] = mt_rand(-10,10)/10;
							//$zom['motionz'] = mt_rand(-10,10)/10;
							$zom['yup'] = 0;
							$zom['up'] = 0;

							//boybook的y轴判断法
							//$width = $this->width;
							$pos = new Vector3 ($zom['x'] + $zom['motionx'], floor($zo->getY()) + 1,$zom['z'] + $zom['motionz']);  //目标坐标
							$zy = $this->ifjump($zo->getLevel(),$pos);
							if ($zy === false) {  //前方不可前进
								$pos2 = new Vector3 ($zom['x'], $zom['y'] ,$zom['z']);  //目标坐标
								if ($this->ifjump($zo->getLevel(),$pos2) === false) { //原坐标依然是悬空
									$pos2 = new Vector3 ($zom['x'], $zom['y']-1,$zom['z']);  //下降
									$zom['up'] = 1;
									$zom['yup'] = 0;
								}
								else {
									$zom['motionx'] = - $zom['motionx'];
									$zom['motionz'] = - $zom['motionz'];
									//转向180度，向身后走
									$zom['up'] = 0;
								}
							}
							else {
								$pos2 = new Vector3 ($zom['x'] + $zom['motionx'], $zy - 1 ,$zom['z'] + $zom['motionz']);  //目标坐标
								if ($pos2->y - $zom['y'] < 0) {
									$zom['up'] = 1;
								}
								else {
									$zom['up'] = 0;
								}
							}

							if ($zom['motionx'] == 0 and $zom['motionz'] == 0) {  //僵尸停止
							}
							else {
								//转向计算
								$yaw = $this->getyaw($zom['motionx'], $zom['motionz']);
								//$zo->setRotation($yaw,0);
								$zom['yaw'] = $yaw;
								$zom['pitch'] = 0;
							}

							//更新僵尸坐标
							$zom['x'] = $pos2->getX();
							$zom['z'] = $pos2->getZ();
							$zom['y'] = $pos2->getY();
							$zom['motiony'] = $pos2->getY() - $zo->getY();
							//echo($zo->getY()."\n");
							//var_dump($pos2);
							//var_dump($zom['motiony']);
							$zo->setPosition($pos2);
							//echo "SetPosition \n";
						}
					}
		}
		}
		}
	}
	
	public function getyaw($mx, $mz) {  //根据motion计算转向角度
		//转向计算
		if ($mz == 0) {  //斜率不存在
			if ($mx < 0) {
				$yaw = -90;
			}
			else {
				$yaw = 90;
			}
		}
		else {  //存在斜率
			if ($mx >= 0 and $mz > 0) {  //第一象限
				$atan = atan($mx/$mz);
				$yaw = rad2deg($atan);
			}
			elseif ($mx >= 0 and $mz < 0) {  //第二象限
				$atan = atan($mx/abs($mz));
				$yaw = 180 - rad2deg($atan);
			}
			elseif ($mx < 0 and $mz < 0) {  //第三象限
				$atan = atan($mx/$mz);
				$yaw = -(180 - rad2deg($atan));
			}
			elseif ($mx < 0 and $mz > 0) {  //第四象限
				$atan = atan(abs($mx)/$mz);
				$yaw = -(rad2deg($atan));
			}
		}
		
		$yaw = - $yaw;
		return $yaw;
	}
	
	public function ifjump($level, $v3) {  //boybook Y轴算法核心函数
		$x = floor($v3->getX());
		$y = floor($v3->getY());
		$z = floor($v3->getZ());
		
		//echo ($y." ");
		if ($this->whatBlock($level,new Vector3($x,$y,$z)) == "air") {
			//echo "前方空气 ";
			if ($this->whatBlock($level,new Vector3($x,$y-1,$z)) == "block") {  //方块
				//echo "考虑向前 ";
				if ($this->whatBlock($level,new Vector3($x,$y+1,$z)) == "block" or $this->whatBlock($level,new Vector3($x,$y+1,$z)) == "half") {  //上方一格被堵住了
					//echo "上方卡住 \n";
					return false;  //上方卡住
				}
				else {
					//echo "GO向前走 \n";
					return $y;  //向前走
				}
			}
			elseif ($this->whatBlock($level,new Vector3($x,$y-1,$z)) == "water") {  //水
				//echo "下水游泳 \n";
				return $y-1;  //降低一格向前走（下水游泳）
			}
			elseif ($this->whatBlock($level,new Vector3($x,$y-1,$z)) == "half") {  //半砖
				//echo "下到半砖 \n";
				return $y-0.5;  //向下跳0.5格
			}
			elseif ($this->whatBlock($level,new Vector3($x,$y-1,$z)) == "lava") {  //岩浆
				//echo "前方岩浆 \n";
				return false;  //前方岩浆
			}
			elseif ($this->whatBlock($level,new Vector3($x,$y-1,$z)) == "air") {  //空气
				//echo "考虑向下跳 ";
				if ($this->whatBlock($level,new Vector3($x,$y-2,$z)) == "block") {
					//echo "GO向下跳 \n";
					return $y-1;  //向下跳
				}
				else { //前方悬崖
					//echo "前方悬崖 \n";
					return false;
				}
			}
		}
		elseif ($this->whatBlock($level,new Vector3($x,$y,$z)) == "water") {  //水
			//echo "正在水中";
			if ($this->whatBlock($level,new Vector3($x,$y+1,$z)) == "water") {  //上面还是水
				//echo "向上游 \n";
				return $y+1;  //向上游，防溺水
			}
			elseif ($this->whatBlock($level,new Vector3($x,$y+1,$z)) == "block" or $this->whatBlock($level,new Vector3($x,$y+1,$z)) == "half") {  //上方一格被堵住了
				if ($this->whatBlock($level,new Vector3($x,$y-1,$z)) == "block" or $this->whatBlock($level,new Vector3($x,$y-1,$z)) == "half") {  //下方一格被也堵住了
					//echo "上下都被卡住 \n";
					return false;  //上下都被卡住
				}
				else {
					//echo "向下游 \n";
					return $y-1;  //向下游，防卡住
				}
			}
			else {
				//echo "游泳ing... \n";
				return $y;  //向前游
			}
		}
		elseif ($this->whatBlock($level,new Vector3($x,$y,$z)) == "half") {  //半砖
			//echo "前方半砖 \n";
			if ($this->whatBlock($level,new Vector3($x,$y+1,$z)) == "block" or $this->whatBlock($level,new Vector3($x,$y+1,$z)) == "half") {  //上方一格被堵住了
				//return false;  //上方卡住
			}
			else {
				return $y+0.5;
			}
			
		}
		elseif ($this->whatBlock($level,new Vector3($x,$y,$z)) == "lava") {  //岩浆
			//echo "前方岩浆 \n";
			return false;
		}
		else {  //考虑向上
			//echo "考虑向上 ";
			if ($this->whatBlock($level,new Vector3($x,$y+1,$z)) != "air") {  //前方是面墙
				//echo "前方是墙 \n";
		 		return false;
		 	}
		 	else {
				if ($this->whatBlock($level,new Vector3($x,$y+2,$z)) == "block" or $this->whatBlock($level,new Vector3($x,$y+2,$z)) == "half") {  //上方两格被堵住了
					//echo "2格处被堵 \n";
					return false;
				}
				else {
					//echo "GO向上跳 \n";
					return $y+1;  //向上跳
				}
		 	}
		}
	}
	
	public function ifjumpinhate($level, $v3) {  //boybook Y轴算法核心函数
		$x = floor($v3->getX());
		$y = floor($v3->getY());
		$z = floor($v3->getZ());
		
		//echo ($y." ");
		if ($this->whatBlock($level,new Vector3($x,$y,$z)) == "air") {
			//echo "前方空气 ";
			if ($this->whatBlock($level,new Vector3($x,$y-1,$z)) == "block") {  //方块
				//echo "考虑向前 ";
				if ($this->whatBlock($level,new Vector3($x,$y+1,$z)) == "block" or $this->whatBlock($level,new Vector3($x,$y+1,$z)) == "half") {  //上方一格被堵住了
					//echo "上方卡住 \n";
					return false;  //上方卡住
				}
				else {
					//echo "GO向前走 \n";
					return $y;  //向前走
				}
			}
			elseif ($this->whatBlock($level,new Vector3($x,$y-1,$z)) == "water") {  //水
				//echo "下水游泳 \n";
				return $y-1;  //降低一格向前走（下水游泳）
			}
			elseif ($this->whatBlock($level,new Vector3($x,$y-1,$z)) == "half") {  //半砖
				//echo "下到半砖 \n";
				return $y-0.5;  //向下跳0.5格
			}
			elseif ($this->whatBlock($level,new Vector3($x,$y-1,$z)) == "lava") {  //岩浆
				//echo "前方岩浆 \n";
				return false;  //前方岩浆
			}
			elseif ($this->whatBlock($level,new Vector3($x,$y-1,$z)) == "air") {  //空气
				//echo "考虑向下跳 ";
				if ($this->whatBlock($level,new Vector3($x,$y-2,$z)) == "block") {
					//echo "GO向下跳 \n";
					return $y-1;  //向下跳
				}
				else { //前方悬崖
					//echo "前方悬崖 \n";
					return $y-1;
				}
			}
		}
		elseif ($this->whatBlock($level,new Vector3($x,$y,$z)) == "water") {  //水
			//echo "正在水中";
			if ($this->whatBlock($level,new Vector3($x,$y+1,$z)) == "water") {  //上面还是水
				//echo "向上游 \n";
				return $y+1;  //向上游，防溺水
			}
			elseif ($this->whatBlock($level,new Vector3($x,$y+1,$z)) == "block" or $this->whatBlock($level,new Vector3($x,$y+1,$z)) == "half") {  //上方一格被堵住了
				if ($this->whatBlock($level,new Vector3($x,$y-1,$z)) == "block" or $this->whatBlock($level,new Vector3($x,$y-1,$z)) == "half") {  //下方一格被也堵住了
					//echo "上下都被卡住 \n";
					return false;  //上下都被卡住
				}
				else {
					//echo "向下游 \n";
					return $y-1;  //向下游，防卡住
				}
			}
			else {
				//echo "游泳ing... \n";
				return $y;  //向前游
			}
		}
		elseif ($this->whatBlock($level,new Vector3($x,$y,$z)) == "half") {  //半砖
			//echo "前方半砖 \n";
			if ($this->whatBlock($level,new Vector3($x,$y+1,$z)) == "block" or $this->whatBlock($level,new Vector3($x,$y+1,$z)) == "half") {  //上方一格被堵住了
				//return false;  //上方卡住
			}
			else {
				return $y+0.5;
			}
			
		}
		elseif ($this->whatBlock($level,new Vector3($x,$y,$z)) == "lava") {  //岩浆
			//echo "前方岩浆 \n";
			return false;
		}
		else {  //考虑向上
			//echo "考虑向上 ";
			if ($this->whatBlock($level,new Vector3($x,$y+1,$z)) != "air") {  //前方是面墙
				//echo "前方是墙 \n";
		 		return false;
		 	}
		 	else {
				if ($this->whatBlock($level,new Vector3($x,$y+2,$z)) == "block" or $this->whatBlock($level,new Vector3($x,$y+2,$z)) == "half") {  //上方两格被堵住了
					//echo "2格处被堵 \n";
					return false;
				}
				else {
					//echo "GO向上跳 \n";
					return $y+3;  //向上跳
				}
		 	}
		}
	}
	
	public function willMove(Entity $entity) {
		foreach($entity->getViewers() as $viewer) {
			if ($entity->distance($viewer->getLocation()) <= 32) return true;
		}
		return false;
	}
	
	public function whatBlock($level, $v3) {  //boybook的y轴判断法 核心 什么方块？
		$block = $level->getBlock($v3);
		$id = $block->getID();
		switch ($id) {
			case 0:
			case 6:
			case 27:
			case 30:
			case 31:
			case 37:
			case 38:
			case 39:
			case 40:
			case 50:
			case 51:
			case 65:
			case 66:
			case 78:
			case 106:
			case 111:
			case 141:
			case 142:
			case 171:
			case 175:
			case 244:
			case 323:
				//透明方块
				return "air";
				break;
			case 8:
			case 9:
				//水
				return "water";
				break;
			case 10:
			case 11:
				//岩浆
				return "lava";
				break;
			case 44:
			case 158:
				//半砖
				return "half";
				break;
			default:
				return "block";
				break;
		}
	}
					
	public function ZombieRandomWalk() {//僵尸运动 Zzm X,Z算法核心函数
	if(count($this->zombie) > 0){
	$filter_res = array_filter($this->zombie);
	if(!empty($filter_res)){
	foreach ($this->zombie as $zoms) {
	$level=$this->getServer()->getLevelByName($zoms['level']);
	 $zo = $level->getEntity($zoms['ID']);
	 if($zo != false){$zom = &$this->zombie[$zo->getId()];
			$zom['yup'] = $zom['yup'] -1;
			$h_r = $this->hatred_r;  //仇恨半径
			$pos = new Vector3($zo->getX(), $zo->getY(), $zo->getZ());
			$hatred = false;
			foreach($zo->getViewers() as $p) {  //获取附近玩家
				if($p->distance($pos) <= $h_r and isset($this->players[$p->getName()])){  //玩家在仇恨半径内
					if ($hatred === false) {
						$hatred = $p;
					}
					else {
						if ($p->distance($pos) <= $hatred->distance($pos) and isset($this->players[$p->getName()])) {  //比上一个更近
							$hatred = $p;
						}
					}
				}
			}
			//echo ($zom['IsChasing']."\n");
			if ($hatred == false or $this->dif == 0) {
				$zom['IsChasing'] = 0;
			}
			else {
				$zom['IsChasing'] = $hatred->getName();
			}
			//echo ($zom['IsChasing']."\n");
			if($zom['IsChasing'] != "0"){
				//echo ("是属于仇恨模式\n");
				$p = $this->getServer()->getPlayer($zom['IsChasing']);
				if (($p instanceof Player) === false){
					$zom['IsChasing'] = 0;  //取消仇恨模式
				}
				else {
				
				
				
					//还不如用旧算法了。。
					$zx =floor($zo->getX());
					$zZ = floor($zo->getZ());
					$xxx = 0.07;
					$zzz = 0.07;
				
					$x1 =$zo->getX () - $p->getX();
				
					//$jumpy = $zo->getY() - 1;
				
					if($x1 >= -0.5 and $x1 <= 0.5) { //直行
						$zx = $zo->getX();
						$xxx = 0;
					}
					elseif($x1 < 0){
						$zx = $zo->getX() +0.07;
						$xxx =0.07;
					}else{
						$zx = $zo->getX() -0.07;
						$xxx = -0.07;
					}
					
					$z1 =$zo->getZ () - $p->getZ() ;
					if($z1 >= -0.5 and $z1 <= 0.5) { //直行
						$zZ = $zo->getZ();
						$zzz = 0;
					}					
					elseif($z1 <0){
						$zZ = $zo->getZ() +0.07;
						$zzz =0.07;
					}else{
						$zZ = $zo->getZ() -0.07;
						$zzz =-0.07;
					}
					
					if ($xxx == 0 and $zzz == 0) {
						$xxx = 0.1;
					}
					
					$zom['xxx'] = $xxx * 10;
					$zom['zzz'] = $zzz * 10;
					
					//计算y轴
					$width = $this->width;
					$pos0 = new Vector3 ($zo->getX(), $zo->getY() + 1 ,$zo->getZ());  //原坐标
					$pos = new Vector3 ($zo->getX()+ $xxx, $zo->getY() + 1,$zo->getZ() + $zzz);  //目标坐标
					$zy = $this->ifjumpinhate($zo->getLevel(),$pos);
					if ($zy === false) {  //前方不可前进
						$xxx = - $xxx;
						$zzz = - $zzz;
						
						//不对啊，既然是从原坐标来的还做这个检测干嘛
						if ($this->ifjumpinhate($zo->getLevel(),$pos0) === false) { //原坐标依然是悬空
							$pos2 = new Vector3 ($zo->getX(), $zo->getY() - 2,$zo->getZ());  //下降
							$zom['up'] = 1;
							$zom['yup'] = 0;
							//var_dump("2");
						}
						else {
							$pos2 = new Vector3 ($zo->getX() + $xxx, floor($zo->getY()),$zo->getZ() + $zzz);  //目标坐标
							//转向180度，向身后走
							$zom['up'] = 0;
							//var_dump("3");
						}
					}
					else {
						$pos2 = new Vector3 ($zo->getX()+ $xxx, $zy - 1 , $zo->getZ() + $zzz);  //目标坐标
						//echo $zy;
						$zom['up'] = 0;
						if ($this->whatBlock($level, $pos2) == "water") {
							$zom['swim'] += 1;
							if ($zom['swim'] >= 20) $zom['swim'] = 0;
						}
						else {
						
						//var_dump("目标:".($zy - 1) );
						//var_dump("原先:".$zo->getY());
						if(($zy - 1)  <   floor($zo->getY()) ){
						//var_dump("跳");
						$zom['swim'] = 150;
						$pk3 = new SetEntityMotionPacket;
					$pk3->entities = [
					[$zo->getID(), $xxx, - $zom['swim'] / 100 , $zzz]
					];
					foreach($zo->getViewers() as $pl){
						$pl->dataPacket($pk3);
					}
						}
						
							//$zom['swim'] = 0;
						}
					}
					
					$yaw = $this->getyaw($xxx , $zzz);
					
					$zo->setPosition($pos2);
					
					$zom['x'] = $zo->getX();
					$zom['y'] = $zo->getY();
					$zom['z'] = $zo->getZ();
					//$zo->setRotation($yaw,0);
					$zom['yaw'] = $yaw;
					$pk3 = new SetEntityMotionPacket;
					$pk3->entities = [
					[$zo->getID(), $xxx, - $zom['swim'] / 100 , $zzz]
					];
					foreach($zo->getViewers() as $pl){
						$pl->dataPacket($pk3);
					}
					
					
					if(0 <= $p->distance($pos) and $p->distance($pos) <= 1.5){
						if($zom['hurt'] >= 0){
							$zom['hurt'] = $zom['hurt'] -1 ;
						}else{
							$p->knockBack($zo, 0, $xxx, $zzz, 0.4);
							if ($p->isSurvival()){
								$realhealth = $p->getHealth() - $this->dif * 2;
								if($realhealth <= 0){
									if(isset($this->players[$p->getName()])){
									unset($this->players[$p->getName()]);
									//var_dump($this->players);
									$p->teleport($this->waitpos2);
									$p->setHealth(20);
									$p->sendMessage("很抱歉，你死了");
									if(count($this->players) > 0){
										foreach($this->players as $pl){
										$p = $this->getServer()->getPlayer($pl["id"]);
											if($p != false){
											$p->sendtip("一位玩家牺牲了,剩余玩家:".count($this->players).",剩余时间:".$this->gametime."秒.");
										}
										$level=$this->getServer()->getLevelByName($this->level);
										$SignVector3 = $this->tile;
										$level->getTile($SignVector3)->setText("僵尸围城","游戏状态:已开始","当前玩家数量:".count($this->players),"当前僵尸数量:".count($this->zombie));
										}
									}
									}
								}else{
									$p->setHealth($p->getHealth() - $this->dif * 2);
								}
							}
							$zom['hurt'] = 10 ;
						}
					}
			}
		}
	}
	}
	}
	}
	}
	
	public function SkeletonRandomWalk() {//骷髅运动 Zzm X,Z算法核心函数
	if(count($this->Skeleton) > 0){
	$filter_res = array_filter($this->Skeleton);
	if(!empty($filter_res)){
	foreach ($this->Skeleton as $zoms) {
	$level=$this->getServer()->getLevelByName($zoms['level']);
	 $zo = $level->getEntity($zoms['ID']);
	 if($zo != false){$zom = &$this->Skeleton[$zo->getId()];
			$zom['yup'] = $zom['yup'] -1;
			$h_r = $this->hatred_r;  //仇恨半径
			$pos = new Vector3($zo->getX(), $zo->getY(), $zo->getZ());
			$hatred = false;
			foreach($zo->getViewers() as $p) {  //获取附近玩家
				if($p->distance($pos) <= $h_r and isset($this->players[$p->getName()])){  //玩家在仇恨半径内
					if ($hatred === false) {
						$hatred = $p;
					}
					else {
						if ($p->distance($pos) <= $hatred->distance($pos) and isset($this->players[$p->getName()])) {  //比上一个更近
							$hatred = $p;
						}
					}
				}
			}
			//echo ($zom['IsChasing']."\n");
			if ($hatred == false or $this->dif == 0) {
				$zom['IsChasing'] = 0;
			}
			else {
				$zom['IsChasing'] = $hatred->getName();
			}
			//echo ($zom['IsChasing']."\n");
			if($zom['IsChasing'] != "0"){
				//echo ("是属于仇恨模式\n");
				$p = $this->getServer()->getPlayer($zom['IsChasing']);
				if (($p instanceof Player) === false){
					$zom['IsChasing'] = 0;  //取消仇恨模式
				}
				else {
				
				
				
					//还不如用旧算法了。。
					$zx =floor($zo->getX());
					$zZ = floor($zo->getZ());
					$xxx = 0.07;
					$zzz = 0.07;
				
					$x1 =$zo->getX () - $p->getX();
				
					//$jumpy = $zo->getY() - 1;
				
					if($x1 >= -0.5 and $x1 <= 0.5) { //直行
						$zx = $zo->getX();
						$xxx = 0;
					}
					elseif($x1 < 0){
						$zx = $zo->getX() +0.07;
						$xxx =0.07;
					}else{
						$zx = $zo->getX() -0.07;
						$xxx = -0.07;
					}
					
					$z1 =$zo->getZ () - $p->getZ() ;
					if($z1 >= -0.5 and $z1 <= 0.5) { //直行
						$zZ = $zo->getZ();
						$zzz = 0;
					}					
					elseif($z1 <0){
						$zZ = $zo->getZ() +0.07;
						$zzz =0.07;
					}else{
						$zZ = $zo->getZ() -0.07;
						$zzz =-0.07;
					}
					
					if ($xxx == 0 and $zzz == 0) {
						$xxx = 0.1;
					}
					
					$zom['xxx'] = $xxx * 10;
					$zom['zzz'] = $zzz * 10;
					
					//计算y轴
					$width = $this->width;
					$pos0 = new Vector3 ($zo->getX(), $zo->getY() + 1 ,$zo->getZ());  //原坐标
					$pos = new Vector3 ($zo->getX()+ $xxx, $zo->getY() + 1,$zo->getZ() + $zzz);  //目标坐标
					$zy = $this->ifjumpinhate($zo->getLevel(),$pos);
					if ($zy === false) {  //前方不可前进
						$xxx = - $xxx;
						$zzz = - $zzz;
						
						//不对啊，既然是从原坐标来的还做这个检测干嘛
						if ($this->ifjumpinhate($zo->getLevel(),$pos0) === false) { //原坐标依然是悬空
							$pos2 = new Vector3 ($zo->getX(), $zo->getY() - 2,$zo->getZ());  //下降
							$zom['up'] = 1;
							$zom['yup'] = 0;
							//var_dump("2");
						}
						else {
							$pos2 = new Vector3 ($zo->getX() + $xxx, floor($zo->getY()),$zo->getZ() + $zzz);  //目标坐标
							//转向180度，向身后走
							$zom['up'] = 0;
							//var_dump("3");
						}
					}
					else {
						$pos2 = new Vector3 ($zo->getX()+ $xxx, $zy - 1 , $zo->getZ() + $zzz);  //目标坐标
						//echo $zy;
						$zom['up'] = 0;
						if ($this->whatBlock($level, $pos2) == "water") {
							$zom['swim'] += 1;
							if ($zom['swim'] >= 20) $zom['swim'] = 0;
						}
						else {
						
						//var_dump("目标:".($zy - 1) );
						//var_dump("原先:".$zo->getY());
						if(($zy - 1)  <   floor($zo->getY()) ){
						//var_dump("跳");
						$zom['swim'] = 150;
						$pk3 = new SetEntityMotionPacket;
					$pk3->entities = [
					[$zo->getID(), $xxx, - $zom['swim'] / 100 , $zzz]
					];
					foreach($zo->getViewers() as $pl){
						$pl->dataPacket($pk3);
					}
						}
						
							//$zom['swim'] = 0;
						}
					}
					
					$yaw = $this->getyaw($xxx , $zzz);
					
					$zo->setPosition($pos2);
					
					$zom['x'] = $zo->getX();
					$zom['y'] = $zo->getY();
					$zom['z'] = $zo->getZ();
					//$zo->setRotation($yaw,0);
					$zom['yaw'] = $yaw;
					$pk3 = new SetEntityMotionPacket;
					$pk3->entities = [
					[$zo->getID(), $xxx, - $zom['swim'] / 100 , $zzz]
					];
					foreach($zo->getViewers() as $pl){
						$pl->dataPacket($pk3);
					}
					
					$p = $this->getServer()->getPlayer($zom['IsChasing']);
                        if ($p instanceof Player) {
							$zom['shoot'] = $zom['shoot'] -1;
								if ($zom['shoot'] <= 0) {
									$zom['shoot'] = 20;
									$v3 = new Vector3($zo->getX(),$zo->getY()+2,$zo->getZ());
									$chunk = $level->getChunk($v3->x >> 4, $v3->z >> 4, true);
									$posnn = new Vector3($zo->getX(),$p->getY(),$zo->getZ());
									$my =$p->getY() - $zo->getY();
									$d = $p->distance($posnn);
									$pitch = $this->getmypitch($my, $d);
						
									$nbt2 = new Compound("", [
										"Pos" => new Enum("Pos", [
											new Double("", $zo->getX()),
											new Double("", $zo->getY() + 1),
											new Double("", $zo->getZ())
										]),
										"Motion" => new Enum("Motion", [
											new Double("", -\sin($zom['yaw']) * \cos($pitch / 180 * M_PI)),
											new Double("", -\sin($pitch / 180 * M_PI)),
											new Double("", \cos($zom['yaw'] / 180 * M_PI) * \cos($pitch / 180 * M_PI))
										]),
										"Rotation" => new Enum("Rotation", [
											new Float("", $zom['yaw']),
											new Float("", $pitch)
										]),
									]);
									$f = 1.5;  
									   
									   
									   
									   
									   
								 //$ev = new EntityShootBowEvent($this, $bow, Entity::createEntity("Arrow", $this->chunk, $nbt, $this), $f);
									$ev = new EntityShootBowEvent($zo, new ITEM(262,0), Entity::createEntity("Arrow", $chunk, $nbt2, $zo), $f);
									   
                                }
						}
									
			}
		}
	}
	}
	}
	}
	}
	
	public function MobDeath(EntityDeathEvent $event){//死亡移除数组
	if(isset($this->zombie[$event->getEntity()->getId()])){
		unset($this->zombie[$event->getEntity()->getId()]);
		if(count($this->players) > 0){
			foreach($this->players as $pl){
				$p = $this->getServer()->getPlayer($pl["id"]);
				if($p != false){
				$p->sendtip("干掉了一个僵尸,剩余玩家:".count($this->players).",剩余僵尸:".count($this->zombie).",剩余时间:".$this->gametime."秒.");
				}	
					$SignVector3 = $this->tile;
					$event->getEntity()->getLevel()->getTile($SignVector3)->setText("僵尸围城","游戏状态:已开始","当前玩家数量:".count($this->players),"当前僵尸数量:".count($this->zombie));
			}
		}
	//var_dump($this->zombie);
	}
	if(isset($this->Skeleton[$event->getEntity()->getId()])){
		unset($this->Skeleton[$event->getEntity()->getId()]);
		if(count($this->players) > 0){
			foreach($this->players as $pl){
				$p = $this->getServer()->getPlayer($pl["id"]);
				if($p != false){
				$p->sendtip("干掉了一个骷髅,剩余玩家:".count($this->players).",剩余骷髅:".count($this->Skeleton).",剩余时间:".$this->gametime."秒.");
				}	
					$SignVector3 = $this->tile;
					$event->getEntity()->getLevel()->getTile($SignVector3)->setText("僵尸围城","游戏状态:已开始|第二轮","当前玩家数量:".count($this->players),"当前骷髅数量:".count($this->Skeleton));
			}
		}
	//var_dump($this->Skeleton);
	}
	$cause = $event->getEntity()->getLastDamageCause();
        if ($cause instanceof EntityDamageByEntityEvent) {
            if ($cause->getEntity() instanceof Entity) {
                return;
            }
            if ($cause->getEntity()->getName() !== 'Zombie' or  $cause->getEntity()->getName() !== 'Skeleton') {
                return;
            }
            $killer = $cause->getDamager()->getPlayer();
           // $killer->sendMessage(TextFormat::RED . '[' . TextFormat::DARK_AQUA . '帝国之战' . TextFormat::RED . ']' . TextFormat::RESET . '成功击杀一只僵尸，获得一积分');
           //fan $this->addPoint($killer->getName(), 1);
            return;
        }
	}
	
	public function ZombieGenerate($p){//僵尸生成函数
	$level=$this->getServer()->getLevelByName($this->level);
			$v3 = new Vector3($p->getX() + mt_rand(-$this->birth_r,$this->birth_r), $p->getY(), $p->getZ() + mt_rand(-$this->birth_r,$this->birth_r));
			for ($y0 = $p->getY()-10; $y0 <= $p->getY()+10; $y0++) {
				$v3->y = $y0;
				if ($level->getBlock($v3)->getID() != 0) {
					$v3_1 = $v3;
					$v3_1->y = $y0 + 1;
					$v3_2 = $v3;
					$v3_2->y = $y0 + 2;
					if ($level->getBlock($v3_1)->getID() == 0 and $level->getBlock($v3_2)->getID() == 0) {  //找到地面
							$chunk = $level->getChunk($v3->x >> 4, $v3->z >> 4, false);
							$nbt = $this->getNBT($v3);
							$zo = new Zombie($chunk,$nbt);
							$zo->setPosition($v3);
							$zo->spawnToAll();
							
							$this->zombie[$zo->getId()] = array(
								'ID' => $zo->getId(),
                                'IsChasing' => false,
                                'motionx' => 0,
                                'motiony' => 0,
                                'motionz' => 0,
                                'hurt' => 10,
                                'time'=>10,
                                'x' => 0,
                                'y' => 0,
                                'z' => 0,
                                'oldv3' => $zo->getLocation(),
                                'yup' => 20,
                                'up' => 0,
                                'yaw' => $zo->yaw,
                                'pitch' => 0,
                                'level' => $zo->getLevel()->getName(),
                                'xxx' => 0,
                                'zzz' => 0,
                                'gotimer' => 10,
                                'swim' => 0,
                                'jump' => 0.01,
                                'canjump' => true,
                                'drop' => false,
                                'canAttack' => 0,
								'shoot' => 20,
                                'knockBack' => false,
							);
							$zom = &$this->zombie[$zo->getId()];
							$zo->setMaxHealth(20);
							//$zo = Entity::createEntity("Zombie", $level->getChunk($v3->x >> 4, $v3->z >> 4, false), $nbt, $level);
							//$zo->spawnToAll();
							//$this->getLogger()->info("生成了一只僵尸".$zo->getId());
							//var_dump($this->zombie);
							break;
					}
				}
			}
		}
	
	public function SkeletonGenerate($p){//僵尸生成函数
	$level=$this->getServer()->getLevelByName($this->level);
			$v3 = new Vector3($p->getX() + mt_rand(-$this->birth_r,$this->birth_r), $p->getY(), $p->getZ() + mt_rand(-$this->birth_r,$this->birth_r));
			for ($y0 = $p->getY()-10; $y0 <= $p->getY()+10; $y0++) {
				$v3->y = $y0;
				if ($level->getBlock($v3)->getID() != 0) {
					$v3_1 = $v3;
					$v3_1->y = $y0 + 1;
					$v3_2 = $v3;
					$v3_2->y = $y0 + 2;
					if ($level->getBlock($v3_1)->getID() == 0 and $level->getBlock($v3_2)->getID() == 0) {  //找到地面
							$chunk = $level->getChunk($v3->x >> 4, $v3->z >> 4, false);
							$nbt = $this->getNBT($v3);
							$zo = new Skeleton($chunk,$nbt);
							$zo->setPosition($v3);
							$zo->spawnToAll();
							
							$this->Skeleton[$zo->getId()] = array(
							 'ID' => $zo->getId(),
                                'IsChasing' => false,
                                'motionx' => 0,
                                'motiony' => 0,
                                'motionz' => 0,
                                'hurt' => 10,
                                'time'=>10,
                                'x' => 0,
                                'y' => 0,
                                'z' => 0,
                                'oldv3' => $zo->getLocation(),
                                'yup' => 20,
                                'up' => 0,
                                'yaw' => $zo->yaw,
                                'pitch' => 0,
                                'level' => $zo->getLevel()->getName(),
                                'xxx' => 0,
                                'zzz' => 0,
                                'gotimer' => 10,
                                'swim' => 0,
                                'jump' => 0.01,
                                'canjump' => true,
                                'drop' => false,
                                'canAttack' => 0,
								'shoot' => 20,
                                'knockBack' => false,
							);
							$zom = &$this->Skeleton[$zo->getId()];
							$zo->setMaxHealth(20);
							//$zo = Entity::createEntity("Skeleton", $level->getChunk($v3->x >> 4, $v3->z >> 4, false), $nbt, $level);
							//$zo->spawnToAll();
							//$this->getLogger()->info("生成了一只骷髅".$zo->getId());
							//var_dump($this->Skeleton);
							break;
					}
				}
			}
		}
	
	public function EntityDamage(EntityDamageEvent $event){//僵尸击退修复
		if($event instanceof EntityDamageByEntityEvent){
			$p = $event->getDamager();
			$zo = $event->getEntity();
			if ($p instanceof Player and $zo instanceof Player) {
				if(isset($this->players[$p->getName()]) and isset($this->players[$zo->getName()])){
					$p->sendtip(TextFormat::RED."不要攻击队友");
					$event->setCancelled();
				}
			}
			if(isset($this->zombie[$zo->getId()])){
				if ($p instanceof Player) {
					 if($this->firsthurt > 0){
					Server::getInstance()->broadcastMessage(TextFormat::BLUE."玩家 ".$p->getName()."完成了第一次攻击");
					$this->firsthurt = 0 ;
					}
					$weapon = $p->getInventory()->getItemInHand()->getID();  //得到玩家手中的武器
					$high = 0;
					if ($weapon == 258 or $weapon == 271 or $weapon == 275) {  //击退x5
						$back = 0.7;
					}
					elseif ($weapon == 267 or $weapon == 272 or $weapon == 279 or $weapon == 283 or $weapon == 286) {  //击退x1
						$back = 1;
					}
					elseif ($weapon == 276) {  //击退x2
						$back = 2;
					}
					elseif ($weapon == 292) {  //击退x10
						$back = 10;
						$high = 5;
					}
					else {
						$back = 0.5;
					}

					$zom = &$this->zombie[$zo->getId()];
					@$zo->knockBack($p, 0, - $zom['xxx'] * $back, - $zom['zzz'] * $back, 0.4);
					//var_dump("玩家".$p->getName()."攻击了ID为".$zo->getId()."的僵尸");
					$zom['x'] = $zom['x'] - $zom['xxx'] * $back;
					$zom['y'] = $zo->getY() + $high;
					$zom['z'] = $zom['z'] - $zom['zzz'] * $back;
					$pos2 = new Vector3 ($zom['x'],$zom['y'],$zom['z']);  //目标坐标
					$zo->setPosition($pos2);
				}
			}
		}
	}
	
	public function spawnzombie(){
			for($i1 = 0; $i1 <= $this->am; $i1 ++){
				$this->ZombieGenerate($this->zpos1);
			}
			for($i1 = 0; $i1 <= $this->am; $i1 ++){
				$this->ZombieGenerate($this->zpos2);
			}
			for($i1 = 0; $i1 <= $this->am; $i1 ++){
				$this->ZombieGenerate($this->zpos3);
			}
			for($i1 = 0; $i1 <= $this->am; $i1 ++){
				$this->ZombieGenerate($this->zpos4);
			}
			for($i1 = 0; $i1 <= $this->am; $i1 ++){
				$this->ZombieGenerate($this->zpos5);
			}
		//Server::getInstance()->broadcastMessage(TextFormat::GREEN."僵尸已生成!");
	}
	
	public function spawnSkeleton(){
			for($i1 = 0; $i1 <= $this->am; $i1 ++){
				$this->SkeletonGenerate($this->zpos1);
			}
			for($i1 = 0; $i1 <= $this->am; $i1 ++){
				$this->SkeletonGenerate($this->zpos2);
			}
			for($i1 = 0; $i1 <= $this->am; $i1 ++){
				$this->SkeletonGenerate($this->zpos3);
			}
			for($i1 = 0; $i1 <= $this->am; $i1 ++){
				$this->SkeletonGenerate($this->zpos4);
			}
			for($i1 = 0; $i1 <= $this->am; $i1 ++){
				$this->SkeletonGenerate($this->zpos5);
			}
		//Server::getInstance()->broadcastMessage(TextFormat::GREEN."骷髅已生成!");
	}
	
	public function PlayerDeath(PlayerDeathEvent $event){
	if($this->gameststus == "start" or $this->gameststus == "round2"){
	if(isset($this->players[$event->getEntity()->getName()])){
	unset($this->players[$event->getEntity()->getName()]);
	//var_dump($this->players);
	$event->getEntity()->teleport($this->waitpos2);
	$event->getEntity()->setHealth(20);
	$event->getEntity()->sendMessage("很抱歉，你死了");
				if(count($this->players) > 0){
					foreach($this->players as $pl){
					$p = $this->getServer()->getPlayer($pl["id"]);
					if($p != false){
					$p->sendtip("一位玩家牺牲了,剩余玩家:".count($this->players).",剩余时间:".$this->gametime."秒.");
					}
					$SignVector3 = $this->tile;
					$event->getEntity()->getLevel()->getTile($SignVector3)->setText("僵尸围城","游戏状态:已开始","当前玩家数量:".count($this->players),"当前僵尸数量:".count($this->zombie));
					}
				}
			}
		}
	}
	
	public function getmypitch($my, $d) {  //根据距离计算转向角度
		//转向计算
		if ($d == 0) {  //斜率不存在
			if ($my < 0) {
				$yaw = -90;
			}
			else {
				$yaw = 90;
			}
		}
		else {  //存在斜率
			if ($my >= 0 and $d > 0) {  //第一象限
				$atan = atan($my/$d);
				$yaw = rad2deg($atan);
			}
			elseif ($my >= 0 and $d < 0) {  //第二象限
				$atan = atan($my/abs($d));
				$yaw = 180 - rad2deg($atan);
			}
			elseif ($my < 0 and $d < 0) {  //第三象限
				$atan = atan($my/$d);
				$yaw = -(180 - rad2deg($atan));
			}
			elseif ($my < 0 and $d > 0) {  //第四象限
				$atan = atan(abs($my)/$d);
				$yaw = -(rad2deg($atan));
			}
		}
		
		$yaw = - $yaw;
		return $yaw;
	}
	
	public function gametimer(){
	if($this->gameststus == "prepare"){
		if(count($this->players) >= 5){
			foreach($this->players as $pl){
				$p = $this->getServer()->getPlayer($pl["id"]);
				if($p != false){
				$p->sendtip("人员已满，游戏即将开始，请做好准备！");
				$p->sendtip("享受这次游戏吧");
				$p->teleport($this->spos);
				}
			}
		$this->gameststus = "loading";
		$this->gametime = 4;
		$this->firsthurt = 1 ;
		}
	}
	if($this->gameststus == "loading"){	
	$this->gametime = $this->gametime - 1;
		if($this->gametime <= 0){
		$this->gameststus = "finalwaiting";
		$this->gametime = 5;
		Server::getInstance()->broadcastMessage(TextFormat::GREEN."游戏开始!");
		if($this->config->get("tile") == array()){
			$this->tile = new Vector3($this->tile["x"],$this->tile["y"],$this->tile["z"]);
			$this->zpos1 = new Vector3($this->zpos1["x"],$this->zpos1["y"],$this->zpos1["z"]);
			$this->zpos2 = new Vector3($this->zpos2["x"],$this->zpos2["y"],$this->zpos2["z"]);
			$this->zpos3 = new Vector3($this->zpos3["x"],$this->zpos3["y"],$this->zpos3["z"]);
			$this->zpos4 = new Vector3($this->zpos4["x"],$this->zpos4["y"],$this->zpos4["z"]);
			$this->zpos5 = new Vector3($this->zpos5["x"],$this->zpos5["y"],$this->zpos5["z"]);
			$this->spos = new Vector3($this->spos["x"],$this->spos["y"],$this->spos["z"]);
			}
			foreach($this->players as $pl){
				$p = $this->getServer()->getPlayer($pl["id"]);
				if($p != false){
				$p->sendtip("游戏开始，请杀光僵尸们！");
				$p->setMaxHealth(20);
				$p->setHealth(20);
				$p->teleport($this->spos);
				$this->level = $p->getLevel()->getName();
				}
			}
			$level=$this->getServer()->getLevelByName($this->level);
			$SignVector3 = $this->tile;
			$level->getTile($SignVector3)->setText("僵尸围城","游戏状态:已开始|第一轮","当前玩家数量:".count($this->players),"当前僵尸数量:".count($this->zombie));			
		}
	}
	if($this->gameststus == "finalwaiting"){
	$this->gametime = $this->gametime - 1;
	if($this->gametime <= 0){
		$this->gameststus = "start";
		$this->gametime = 60;
	$this->spawnzombie();
	}else{
		foreach($this->players as $pl){
			$p = $this->getServer()->getPlayer($pl["id"]);
			if($p != false){
			$p->sendtip("僵尸还有".$this->gametime."秒开始生成!");
			}
		}
	}
	}
	if($this->gameststus == "start" or $this->gameststus == "round2"){
		if(count($this->zombie) <= 0){
		if($this->gameststus == "start"){
		//Server::getInstance()->broadcastMessage(TextFormat::YELLOW."游戏结束，人类胜利！");
		$this->zombie = array();
		foreach($this->players as $pl){
				$p = $this->getServer()->getPlayer($pl["id"]);
				if($p != false){
				//$p->setHealth(20);
				$p->sendTip("一大波骷髅正在逼近");
				$p->teleport($this->spos);
			}
		}
		//$this->players = array();
		$this->gameststus = "round2";
		$lv=$this->getServer()->getLevelByName($this->level);
		$SignVector3 = $this->tile;
		$lv->getTile($SignVector3)->setText("僵尸围城","游戏状态:已开始|第二轮","当前玩家数量:".count($this->players),"当前僵尸数量:".count($this->zombie));
		//$lv->getTile($SignVector3)->setText("僵尸围城","游戏状态:未开始","当前玩家数量:0","作者:Zzm");	
		$this->gametime = 60;
		$this->spawnSkeleton();
		}
		}
		if(count($this->Skeleton) <= 0){
		if($this->gameststus == "round2"){
		Server::getInstance()->broadcastMessage(TextFormat::YELLOW."游戏结束，人类胜利！");
		$this->Skeleton = array();
		foreach($this->players as $pl){
				$p = $this->getServer()->getPlayer($pl["id"]);
				if($p != false){
				$p->setHealth(20);
				$p->teleport($this->waitpos2);
			}
		}
		$this->players = array();
		$this->gameststus = "prepare";
		$lv=$this->getServer()->getLevelByName($this->level);
		$SignVector3 = $this->tile;
		$lv->getTile($SignVector3)->setText("僵尸围城","游戏状态:未开始","当前玩家数量:0","作者:Zzm");	
		//$this->gametime = 60;
		}
		}
		if(count($this->players) <= 0){
		if($this->gameststus == "start"){
		Server::getInstance()->broadcastMessage(TextFormat::YELLOW."游戏结束，人类失败!原因：人类被团灭");
			foreach($this->zombie as $zoms){
				$level=$this->getServer()->getLevelByName($zoms['level']);
				$level->removeEntity($level->getEntity($zoms['ID']));
				}
				
				$level=$this->getServer()->getLevelByName($this->level);
				$SignVector3 = $this->tile;
				$level->getTile($SignVector3)->setText("僵尸围城","游戏状态:未开始","当前玩家数量:0","作者:Zzm");
		$this->zombie = array();
		$this->players = array();
		$this->gameststus = "prepare";
		}
		}
		$this->gametime = $this->gametime - 1;
		if($this->gametime <= 0){
			if(count($this->zombie) > 0 or count($this->Skeleton) > 0){
				Server::getInstance()->broadcastMessage(TextFormat::YELLOW."游戏结束，人类失败!原因：超时");
				foreach($this->zombie as $zoms){
				$level=$this->getServer()->getLevelByName($zoms['level']);
				$level->removeEntity($level->getEntity($zoms['ID']));
				}
				foreach($this->players as $pl){
				$p = $this->getServer()->getPlayer($pl["id"]);
					if($p != false){
				$p->setHealth(20);
				$p->teleport($this->waitpos2);
					}
		}
				$this->zombie = array();
				$this->players = array();
				$this->gameststus = "prepare";
				$level = $this->getServer()->getLevelByName($this->level);
				$SignVector3 = $this->tile;
				$level->getTile($SignVector3)->setText("僵尸围城","游戏状态:未开始","当前玩家数量:0","作者:Zzm");
			}
		}
		if($this->gametime >= 0 and $this->gametime <=5 ){
			foreach($this->players as $pl){
				$p = $this->getServer()->getPlayer($pl["id"]);
				if($p != false){
				$p->sendtip("游戏还有".$this->gametime."秒结束，剩余僵尸数量：".count($this->zombie));
				}
			}
		}
	
	}
	}
		
	public function playerBlockTouch(PlayerInteractEvent $event){
	$player = $event->getPlayer();
	$username = $player->getName();
	$block = $event->getBlock();
	$levelname = $player->getLevel()->getFolderName();
	if(isset($this->SetStatus[$username])){
			switch ($this->SetStatus[$username]) {
				case 0:
				if($event->getBlock()->getID() == 323 || $event->getBlock()->getID() == 63 || $event->getBlock()->getID() == 68){
					$this->tile = array(
								"x" =>$block->x,
								"y" =>$block->y,
								"z" =>$block->z,
								"level" =>$levelname,
							);
					$this->config->set("tile",$this->tile);
					$this->config->save();
					$this->SetStatus[$username]++;
					$player->sendtip(TextFormat::GREEN." * 状态牌 x=".$block->x." y=".$block->y." z=".$block->z." level=".$levelname);
					$player->sendtip(TextFormat::GREEN." * 请点击生成点1");
					$SignVector3 = new Vector3($block->x,$block->y,$block->z);
					$player->getLevel()->getTile($SignVector3)->setText("僵尸围城","游戏状态:未开始","当前玩家数量:0","作者:Zzm");
					break;
					}else{
					$player->sendtip(TextFormat::RED." * 点击的不是木牌！");
					break;
					}
				case 1:
				 $this->zpos1 = array(
										"x" =>$block->x,
										"y" =>$block->y,
										"z" =>$block->z,
										"level" =>$levelname,
									);
							$this->config->set("zpos1",$this->zpos1);
							$this->config->save();
							$this->SetStatus[$username]++;
							$player->sendtip(TextFormat::GREEN." * 生成点1 x=".$block->x." y=".$block->y." z=".$block->z." level=".$levelname);
					$player->sendtip(TextFormat::GREEN." * 请点击生成点2");
					break;
				case 2:
				 $this->zpos2 = array(
										"x" =>$block->x,
										"y" =>$block->y,
										"z" =>$block->z,
										"level" =>$levelname,
									);
							$this->config->set("zpos2",$this->zpos2);
							$this->config->save();
							$this->SetStatus[$username]++;
							$player->sendtip(TextFormat::GREEN." * 生成点2 x=".$block->x." y=".$block->y." z=".$block->z." level=".$levelname);
					$player->sendtip(TextFormat::GREEN." * 请点击生成点3");
					break;	
				case 3:
				 $this->zpos3 = array(
										"x" =>$block->x,
										"y" =>$block->y,
										"z" =>$block->z,
										"level" =>$levelname,
									);
							$this->config->set("zpos3",$this->zpos3);
							$this->config->save();
							$this->SetStatus[$username]++;
							$player->sendtip(TextFormat::GREEN." * 生成点3 x=".$block->x." y=".$block->y." z=".$block->z." level=".$levelname);
					$player->sendtip(TextFormat::GREEN." * 请点击生成点4");
					break;	
				case 4:
				 $this->zpos4= array(
										"x" =>$block->x,
										"y" =>$block->y,
										"z" =>$block->z,
										"level" =>$levelname,
									);
							$this->config->set("zpos4",$this->zpos4);
							$this->config->save();
							$this->SetStatus[$username]++;
							$player->sendtip(TextFormat::GREEN." * 生成点4 x=".$block->x." y=".$block->y." z=".$block->z." level=".$levelname);
					$player->sendtip(TextFormat::GREEN." * 请点击生成点5");
					break;	
				case 5:
				 $this->zpos5 = array(
										"x" =>$block->x,
										"y" =>$block->y,
										"z" =>$block->z,
										"level" =>$levelname,
									);
							$this->config->set("zpos5",$this->zpos5);
							$this->config->save();
						$this->SetStatus[$username]++;
						$player->sendtip(TextFormat::GREEN." * 生成点5 x=".$block->x." y=".$block->y." z=".$block->z." level=".$levelname);
						$player->sendtip(TextFormat::GREEN." * 请点击起始点");
						break;
				case 6:
				 $this->spos = array(
										"x" =>$block->x,
										"y" =>$block->y,
										"z" =>$block->z,
										"level" =>$levelname,
									);
						$this->config->set("spos",$this->spos);
						$this->config->save();
						$this->SetStatus[$username]++;
						$player->sendtip(TextFormat::GREEN." * 起始点 x=".$block->x." y=".$block->y." z=".$block->z." level=".$levelname);
						$player->sendtip(TextFormat::GREEN." * 请点击等待点1(加入游戏后等待区)");
						break;	
				case 7:
				 $this->waitpos1 = array(
										"x" =>$block->x,
										"y" =>$block->y,
										"z" =>$block->z,
										"level" =>$levelname,
									);
							$this->config->set("waitpos1",$this->waitpos1);
							$this->config->save();
						$this->SetStatus[$username]++;
						$player->sendtip(TextFormat::GREEN." * 等待点1 x=".$block->x." y=".$block->y." z=".$block->z." level=".$levelname);
						$player->sendtip(TextFormat::GREEN." * 请点击等待点2(退出时传送区域)");
						break;
				case 8:
				 $this->waitpos2 = array(
										"x" =>$block->x,
										"y" =>$block->y,
										"z" =>$block->z,
										"level" =>$levelname,
									);
							$this->config->set("waitpos2",$this->waitpos2);
							$this->config->save();			
						unset($this->SetStatus[$username]);
						Server::getInstance()->broadcastMessage(TextFormat::YELLOW." * 僵尸围城 全部设置完成 , 可以进行游戏了!");
			$this->tile = new Vector3($this->tile["x"],$this->tile["y"],$this->tile["z"]);
			$this->zpos1 = new Vector3($this->zpos1["x"],$this->zpos1["y"],$this->zpos1["z"]);
			$this->zpos2 = new Vector3($this->zpos2["x"],$this->zpos2["y"],$this->zpos2["z"]);
			$this->zpos3 = new Vector3($this->zpos3["x"],$this->zpos3["y"],$this->zpos3["z"]);
			$this->zpos4 = new Vector3($this->zpos4["x"],$this->zpos4["y"],$this->zpos4["z"]);
			$this->zpos5 = new Vector3($this->zpos5["x"],$this->zpos5["y"],$this->zpos5["z"]);
			$this->spos = new Vector3($this->spos["x"],$this->spos["y"],$this->spos["z"]);	
			$this->waitpos1 = new Vector3($this->waitpos1["x"],$this->waitpos1["y"],$this->waitpos1["z"]);
			$this->waitpos2 = new Vector3($this->waitpos2["x"],$this->waitpos2["y"],$this->waitpos2["z"]);				
			$this->level = $levelname;	
			$this->config->set("level",$this->level);
			$this->config->save();	
			$this->gameststus = "prepare";			
				}
		}else{				
       if($event->getBlock()->getID() == 323 || $event->getBlock()->getID() == 63 || $event->getBlock()->getID() == 68){
            $sign = $event->getPlayer()->getLevel()->getTile($event->getBlock());
            if(!($sign instanceof Sign)){
                return;
            }
            $sign = $sign->getText();
            if($sign[0]== '加入游戏'){
				if($sign[1]== '僵尸围城'){
					if($this->gameststus == "prepare"){
						if(!isset($this->players[$event->getPlayer()->getName()])){		
						$this->players[$event->getPlayer()->getName()] = array(
						"id"=>$event->getPlayer()->getName(),
						"Isonline"=>1,
						"kill"=>0,
						);
						$event->getPlayer()->sendtip("加入游戏成功！");
						Server::getInstance()->broadcastMessage(TextFormat::GREEN."玩家".$event->getPlayer()->getName()."加入了游戏！");
						Server::getInstance()->broadcastMessage(TextFormat::GREEN."还需要".(5 - count($this->players))."个玩家才能开始游戏！");
						//$event->getPlayer()->setPostion();
						$event->getPlayer()->teleport($this->waitpos1);
						$level=$this->getServer()->getLevelByName($this->level);
						$SignVector3 = $this->tile;
						$player->getLevel()->getTile($SignVector3)->setText("僵尸围城","游戏状态:未开始","当前玩家数量:".count($this->players),"作者:Zzm");
						
						}else{
						$event->getPlayer()->sendtip("你已经加入游戏了！");
						}
					}else{
					$event->getPlayer()->sendtip("现在不能加入游戏。。");
					}
				}
			}
			if($sign[0]== '退出游戏'){
				if($sign[1]== '僵尸围城'){
					if($this->gameststus == "prepare"){
						if(!isset($this->players[$event->getPlayer()->getName()])){
						$event->getPlayer()->sendtip("你不在游戏队伍中！");
						}else{
						//var_dump($event->getEntity()->getId());
						if(isset($this->players[$event->getPlayer()->getName()])){	
						unset($this->players[$event->getPlayer()->getName()]);
						}
						$event->getPlayer()->sendtip("你已经成功的退出了游戏！");
						Server::getInstance()->broadcastMessage(TextFormat::RED."玩家".$event->getPlayer()->getName()."退出了游戏！");
						$level=$this->getServer()->getLevelByName($this->level);
						$SignVector3 = $this->tile;
						$player->getLevel()->getTile($SignVector3)->setText("僵尸围城","游戏状态:未开始","当前玩家数量:".count($this->players),"作者:Zzm");
						$event->getPlayer()->teleport($this->waitpos2);
						//$event->getPlayer()->setPostion();
						}
					}else{
					$event->getPlayer()->sendtip("现在不能退出游戏。。");
					}
				}
			}
		}
	}
	}
	
	public function PlayerQuit(PlayerQuitEvent $event){
	if(isset($this->players[$event->getPlayer()->getName()])){	
	$p = &$this->players[$event->getPlayer()->getName()];
	$p['Isonline'] = 0;
	Server::getInstance()->broadcastMessage(TextFormat::RED."玩家".$event->getPlayer()->getName()."掉线了");
	}
	}
	
	public function PlayerJoin(PlayerJoinEvent $event){
	$pl = $event->getPlayer();
	if(isset($this->players[$event->getPlayer()->getName()])){	
	$p = &$this->players[$event->getPlayer()->getName()];
	$p['Isonline'] = 1;
	Server::getInstance()->broadcastMessage(TextFormat::RED."玩家".$event->getPlayer()->getName()."重连了");
	$pl->teleport($this->spos);
	}
	}
	
	public function onDisable(){
		$this->getLogger()->info("ZombieCity Unload Success!");
	}
	
}
