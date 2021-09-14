<?php
namespace Edisom\App\game\controller;

class EnemysController extends \Edisom\Core\Backend
{	
	function index()
	{		
		$this->view->assign('enemys', $this->model->get('enemys', null, true));
		$this->view->display('enemys.html');
	}									
}