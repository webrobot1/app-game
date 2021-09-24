<?php
namespace Edisom\App\game\controller;

class SigninController extends \Edisom\Core\Controller
{	
	function __construct()
	{	
		parent::__construct();
		header('Content-Type: application/json');

		// без этого заголовка Unity не примет пакет		
		header('Access-Control-Allow-Origin: *');	
	}
	
	public function signin()
	{
		return $this->model->signin($this->login, $this->password);
	}	
	
	public function register()
	{
		$this->model->register($this->login, $this->password);
		exit('Успешно зарегистрирован');
	}
	
	public function screen()
	{	
		$this->model->screen($this->token);
	}		
}