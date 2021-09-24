<?php
namespace \Edisom\App\game\model\api;

use \Edisom\Core\Model;
use \Edisom\App\server\model\ServerModel;
use \Edisom\App\game\model\BackendModel;

class SigninModel extends BackendModel
{	
	function register(string $login, string $password)
	{		
		$this->query('INSERT INTO players (login, password) VALUES ("'.$login.'", "'.$password.'")');		
		return true;
	}

	function sigin(string $login, string $password)
	{
		if(!$player = end($this->get('players', ['login'=>$login, 'password'=>$password])))
			throw new \Exception('Логин или пароль не верен');

		// если пользователь уже на карте удалим конект (и данные о токене старом)	
		if($player['token'])
		{
			static::redis()->zRem('map:'.$player['map_id'], $player['token']);
			static::redis()->del($player['token']);	
		}

		$player['token'] = Model::guid();
		$player['action'] = 'idle';

		// установим игроку новый токен и дату визита (а то сразу отвалится по таймауту)
		$this->->update('players', $player['id'], ['token'=>$player['token'], 'action'=>$player['action'], 'datetime'=>date("Y-m-d H:i:s")]);	
		
		// внесем в глобальную видимость данные числящиеся за токеном (ид и карту. пока ничего больше не надо)
		foreach(['id', 'map_id'] as $key)
		{
			static::redis()->hSet($player['token'], $key , $player[$key]);
		}
		
		if($response = json_encode(['id'=>$player['id'], 'token'=>$player['token'], 'protocol'=>ServerModel::PROTOCOL], JSON_NUMERIC_CHECK ))
		{	
			static::log('Игрок авторезирован '.$response);
			return $response;
		}
	}

	function screen(string $token)
	{
		if($_FILES['screen'] && $_FILES['screen']['type'] == "image/png")
		{
			if($player_id = static::redis()->hGet($token, 'id'))
			{
				static::upload($_FILES['screen']['tmp_name'] , $player_id.'.png', true);
			}
			else
				throw new \Exception('не найден игрок с токеном: '.$token);				
		}
		else
			throw new \Exception('Ошибка загрузки скриншета');		
	}
}