<?php
namespace Edisom\App\game\model;

class SigninModel extends BackendModel
{	
	function register(string $login, string $password)
	{		
		$this->query('INSERT INTO players (login, password) VALUES ("'.$login.'", "'.$password.'")');		
		return true;
	}			
}