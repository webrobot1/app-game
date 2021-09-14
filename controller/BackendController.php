<?php
namespace Edisom\App\game\controller;

class BackendController extends \Edisom\Core\Backend
{	
	function index()
	{		
		$this->view->display('main.html');
	}									
}