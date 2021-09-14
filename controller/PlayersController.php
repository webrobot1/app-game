<?php
namespace Edisom\App\game\controller;

class PlayersController extends \Edisom\Core\Backend
{	
	function index()
	{		
		$this->view->assign('players', $this->model->get('players', null, true));
		$this->view->display('players.html');
	}									
}