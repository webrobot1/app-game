<?php
namespace Edisom\App\game\controller;

class SigninController extends \Edisom\Core\Controller
{	
	protected function __construct()
	{	
		parent::__construct();
		header('Content-Type: application/json');

		// без этого заголовка Unity не примет пакет		
		header('Access-Control-Allow-Origin: *');	
	}
	
	public function signin()
	{
		if($this->login && $this->password)
		{
			if(!$player = end($this->model->get('players', ['login'=>$this->login, 'password'=>$this->password])))
				throw new \Exception('Логин или пароль не верен');
	
			// если пользователь уже на карте удалим конект (и данные о токене старом)	
			if($player['token'])
			{
				$this->model::redis()->zRem('map:'.$player['map_id'], $player['token']);
				$this->model::redis()->del($player['token']);	
			}
	
			$player['token'] = \Edisom\Core\Model::guid();
			$player['action'] = 'idle';

			// установим игроку новый токен и дату визита (а то сразу отвалится по таймауту)
			$this->model->update('players', $player['id'], ['token'=>$player['token'], 'action'=>$player['action'], 'datetime'=>date("Y-m-d H:i:s")]);	
			
			// внесем в глобальную видимость данные числящиеся за токеном (ид и карту. пока ничего больше не надо)
			foreach(['id', 'map_id'] as $key)
			{
				$this->model::redis()->hSet($player['token'], $key , $player[$key]);
			}
			
			if($response = json_encode(['id'=>$player['id'], 'token'=>$player['token'], 'protocol'=>\Edisom\App\server\model\ServerModel::PROTOCOL], JSON_NUMERIC_CHECK ))
			{	
				$this->model::log('Отправили игроку '.$response);
				exit($response);
			}
		}
		else
			throw new \Exception('Логин или пароль отсутствует');
	}	
	
	public function register(){
		if($this->login && $this->password && $this->model->register($this->login, $this->password))
		{
			exit('Успешно зарегистрирован');
		}
	}
	
	public function screen()
	{	
		if($this->token && $_FILES['screen'] && $_FILES['screen']['type'] == "image/png")
		{
			if($player_id = $this->model::redis()->hGet($this->token, 'id'))
			{
				$this->model::upload($_FILES['screen']['tmp_name'] , $player_id.'.png', true);
			}
			else
				throw new \Exception('не найден игрок с токеном: '.$this->token);				
		}
		else
			throw new \Exception('Ошибка загрузки скриншета');	
	}		
}