<?php
namespace Edisom\App\game\controller;

use JMGQ\AStar\DomainLogicInterface;
use JMGQ\AStar\AStar;

class BackendController extends \Edisom\Core\Backend
{	
	function index()
	{	

		//$domainLogic = new DomainLogic();
		//$aStar = new AStar($domainLogic);

	
		//die(print_r($aStar->run($start, $goal)));
	
		$this->view->display('main.html');
	}									
}


class DomainLogic implements DomainLogicInterface
{
    // ...

    public function getAdjacentNodes(mixed $node): iterable
    {
        // Return a collection of adjacent nodes
    }

    public function calculateRealCost(mixed $node, mixed $adjacent): float | int
    {
        // Return the actual cost between two adjacent nodes
    }

    public function calculateEstimatedCost(mixed $fromNode, mixed $toNode): float | int
    {
        // Return the heuristic estimated cost between the two given nodes
    }

    // ...
}