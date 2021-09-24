<?php
namespace Edisom\App\game\model\api;

use \Edisom\App\map\model\Tiled\GD\Map;
use \Edisom\Core\Cli;

class ApiModel extends \Edisom\App\game\model\BackendModel
{	
	protected $player;
			
	protected function __construct()
	{
		if (PHP_SAPI !== 'cli') 
			throw new \Exception('Только CLI режим');
		
		global $argv;
		
		$argv[2] = Cli::decode($argv[2]);
		
		if(!$argv[2]['token'])
			throw new \Exception('отсутствует токен');	
		// сразу соберем из редиса данные об игроке воедино (те что мы записали в SigninController строка 32)
		elseif(!$this->player = static::redis()->hGetAll($argv[2]['token']))
			throw new \Exception('Ошибка авторизации');

		static::log('Пришла команда '.$argv[1]['action'].' от '.$argv[2]['token']);					
	}

	// переопределим исключения, нам заголовки не нужны а текст не выводим а отправляем по подписке
	// пусть клиентское приложение решает что с этим делать (рвать коннект или просто выводить ошибку)
	public function exceptionHandler($ex)
	{
		if($this->player['token'])
			static::redis()->publish('token:'.$this->player['token'], json_encode(['error'=>$ex->getMessage()]));
		
		parent::exceptionHandler($ex);
	}

	public function load()
	{	
		$return = ['action'=>'load'];
	
		// загружаем из БД всех кто на карте
		if($tokens = static::redis()->zRange('map:'.$this->player['map_id'], 0, -1))
		{
			if(!$return['players'] = $this->get('players', ['token'=>$tokens]))
				throw new \Exception('не найдено игроков по токенам '.print_r($tokens, true));
				
			// удалим данные что передавать клиентам не нужно
			foreach($return['players'] as &$player){
				unset($player['token']);
			}	
		}
				
		// данные что мы вышлем себе (о других игроках , монстрах и объектах)
		$return['enemys'] = $this->get('enemys', ['map_id'=>$this->player['map_id']]);	
		
		// объекты из бд
		$return['objects'] = $this->get('objects', ['map_id'=>$this->player['map_id']]);
				
		// добавим себя на на карту
		if($self = end($this->get('players', ['id'=>$this->player['id']])))
		{	
			if(!static::redis()->geoAdd('map:'.$this->player['map_id'], $self['position'][0], $self['position'][1], $self['token']))
				throw new \Exception('Ошибка добавления на карту');	
			
			// добавим всем на карту себя (себе тоже)
			static::redis()->publish('map:'.$this->player['map_id'], json_encode(['players'=>[ $self ]],JSON_NUMERIC_CHECK));			
		}
		else
			throw new \Exception('не найден игрок');
		
	 	ob_start(); 
			imagepng((new Map($this->player['map_id']))->load()->resource);
			$return['map']['data'] = base64_encode(ob_get_contents());
		ob_end_clean (); 
		
		if($return)
		{
			static::redis()->publish('token:'.$this->player['token'], json_encode(array_filter($return), JSON_NUMERIC_CHECK));
			static::log('отправили авторизацию');
		}
	}	
	
	public function save()
	{	
		if($data = $this->player)
		{
			if($position = static::redis()->geoPos('map:'.$this->player['map_id'], $this->player['token'])[0]){
				$data['position_x'] = round($position[0], 2);
				$data['position_y'] = round($position[1], 2);
			}
			$this->update('players', $this->player['id'], $data);	
		}		
	}
}