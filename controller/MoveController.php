<?php
namespace Edisom\App\game\controller;

class MoveController extends ApiController
{	
	private $position;
	
	protected function __construct()
	{
		parent::__construct();
		$this->position = $this->model::redis()->geoPos('map:'.$this->model->player['map_id'], $this->token)[0];
		
		if(\Edisom\App\server\model\ServerModel::PROTOCOL == 'Udp')
			DEFINE("SPEED", 0.1);
		else
			DEFINE("SPEED", 0.5);	
	}	
	
	function __destruct()
	{
		// установим нвоые координаты игроку
		$this->model::redis()->geoAdd('map:'.$this->model->player['map_id'], $this->position[0], $this->position[1], $this->token);
		
		// сообщим всем на карте что мы двинулись
		$this->model::redis()->publish('map:'.$this->model->player['map_id'], json_encode(['players'=>[['id'=>$this->model->player['id'], 'action'=>$this->model->player['action'], 'position'=>$this->position]]],JSON_NUMERIC_CHECK));	

		$this->model::log('Движение игрока '.$this->token);		
		exit();
	}
	
	
	public function up()
	{	
		$this->position[0] = round($this->position[0], 2);
		$this->position[1] = round($this->position[1] + SPEED, 2);
		$this->model->player['action'] = 'move_up';
	}	
	
	public function down()
	{	
		$this->position[0] = round($this->position[0], 2);
		$this->position[1] = round($this->position[1] - SPEED, 2);
		$this->model->player['action'] = 'move_down';
	}	
	
	public function left()
	{		
		$this->position[0] = round($this->position[0] - SPEED, 2);
		$this->position[1] = round($this->position[1], 2);
		$this->model->player['action'] = 'move_left';
	}	
	
	public function right()
	{		
		$this->position[0] = round($this->position[0] + SPEED, 2);
		$this->position[1] = round($this->position[1], 2);
		$this->model->player['action'] = 'move_right';
	}	
}