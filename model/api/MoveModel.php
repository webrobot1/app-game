<?php
namespace Edisom\App\game\model\api;
//use JMGQ\AStar\DomainLogicInterface;

class MoveModel extends ApiModel
{	
	private $position;
	
	protected function __construct(string $token)
	{
		parent::__construct($token);
		$this->position = static::redis()->geoPos('map:'.$this->player['map_id'], $this->token)[0];
		
		if(\Edisom\App\server\model\ServerModel::PROTOCOL == 'Udp')
			DEFINE("SPEED", 0.1);
		else
			DEFINE("SPEED", 0.5);	
	}	
	
	function __destruct()
	{
		// установим нвоые координаты игроку
		static::redis()->geoAdd('map:'.$this->player['map_id'], $this->position[0], $this->position[1], $this->token);
		
		// сообщим всем на карте что мы двинулись
		static::redis()->publish('map:'.$this->player['map_id'], json_encode(['players'=>[['id'=>$this->player['id'], 'action'=>$this->player['action'], 'position'=>$this->position]]],JSON_NUMERIC_CHECK));	

		static::log('Движение игрока '.$this->token);		
	}
	
	
	public function up()
	{	
		$this->position[0] = round($this->position[0], 2);
		$this->position[1] = round($this->position[1] + SPEED, 2);
		$this->player['action'] = 'move_up';
	}	
	
	public function down()
	{	
		$this->position[0] = round($this->position[0], 2);
		$this->position[1] = round($this->position[1] - SPEED, 2);
		$this->player['action'] = 'move_down';
	}	
	
	public function left()
	{		
		$this->position[0] = round($this->position[0] - SPEED, 2);
		$this->position[1] = round($this->position[1], 2);
		$this->player['action'] = 'move_left';
	}	
	
	public function right()
	{		
		$this->position[0] = round($this->position[0] + SPEED, 2);
		$this->position[1] = round($this->position[1], 2);
		$this->player['action'] = 'move_right';
	}	
}
