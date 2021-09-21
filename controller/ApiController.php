<?php
namespace Edisom\App\game\controller;

class ApiController extends \Edisom\Core\Api
{	
	protected function __construct()
	{
		if (PHP_SAPI !== 'cli') 
			throw new \Exception('Только CLI режим');	
		
		global $argv;
		
		parent::__construct(json_decode(base64_decode($argv[2]), true));

		if(!$this->token)
			throw new \Exception('отсутствует токен');	
		// сразу соберем из редиса данные об игроке воедино (те что мы записали в SigninController строка 32)
		elseif(!$this->model->player = $this->model::redis()->hGetAll($this->token))
			throw new \Exception('Ошибка авторизации');	
	}	
	
	public function load()
	{	
		$return = ['action'=>'load'];
	
		// загружаем из БД всех кто на карте
		if($tokens = $this->model::redis()->zRange('map:'.$this->model->player['map_id'], 0, -1))
		{
			if(!$return['players'] = $this->model->get('players', ['token'=>$tokens]))
				throw new \Exception('не найдено игроков по токенам '.print_r($tokens, true));
				
			// удалим данные что передавать клиентам не нужно
			foreach($return['players'] as &$player){
				unset($player['token']);
			}	
		}
				
		// данные что мы вышлем себе (о других игроках , монстрах и объектах)
		if($enemys = $this->model->get('enemys', ['map_id'=>$this->model->player['map_id']])){
			$return['enemys'] = $enemys;	
		}
		if($objects = $this->model->get('objects', ['map_id'=>$this->model->player['map_id']]))
			$return['objects'] = $objects;
				
		// добавим себя на на карту
		if($self = end($this->model->get('players', ['id'=>$this->model->player['id']])))
		{	
			if(!$this->model::redis()->geoAdd('map:'.$this->model->player['map_id'], $self['position'][0], $self['position'][1], $self['token']))
				throw new \Exception('Ошибка добавления на карту');	
			
			// добавим всем на карту себя (себе тоже)
			$this->model::redis()->publish('map:'.$this->model->player['map_id'], json_encode(['players'=>[ $self ]],JSON_NUMERIC_CHECK));			
		}
		else
			throw new \Exception('не найден игрок');
		
	 	ob_start(); 
			imagepng((new \Edisom\App\map\model\Tiled\GD\Map($this->model->player['map_id']))->load()->resource);
			$return['map']['data'] = base64_encode(ob_get_contents());
		ob_end_clean (); 
		
		if($return)
			exit(json_encode($return, JSON_NUMERIC_CHECK));
	}	
	
	public function save()
	{	
		if($data = $this->model->player)
		{
			if($position = $this->model::redis()->geoPos('map:'.$this->model->player['map_id'], $this->token)[0]){
				$data['position_x'] = round($position[0], 2);
				$data['position_y'] = round($position[1], 2);
			}
			$this->model->update('players', $this->model->player['id'], $data);	
		}		
	}	
}