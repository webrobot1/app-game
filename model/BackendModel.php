<?php
namespace Edisom\App\game\model;

class BackendModel extends \Edisom\Core\Model
{		
	function get(string $type, array $callback = null, $full = false):array
	{
		if($objects = $this->query('SELECT * FROM '.$type.' '.($callback?'WHERE '.static::explode($callback,' AND '):'')))
		{	
			foreach($objects as &$object)
			{
				switch($type){
					case 'players':
						unset($object['password']);
						if(!$full){
							unset($object['datetime']);
							unset($object['ping']);
							unset($object['ip']);
							unset($object['screen']);		
						}
						elseif(($object['screen'] = '/data/'.static::app().'/'.$object['id'].'.png') && !file_exists(SITE_PATH.'/'.$object['screen']))
							unset($object['screen']);
						
						// если нет карты (например мы ее удалили или поменяли)  то начинаем в старторвой локации
						if(!$object['map_id']){
							$object['map_id'] = 1;
							$object['position'] = [0, 0];	
						}
						else{
							$object['position'] = [$object['position_x'], $object['position_y']];				
							unset($object['position_x']);
							unset($object['position_y']);
						}
						
					break;
					case 'enemys':
						if(!$full)
							unset($object['PID']);
						
						$object['position'] = [$object['position_x'], $object['position_y']];				
						unset($object['position_x']);
						unset($object['position_y']);	
					break;					
				}
			}		
		}

		return $objects;
	}
		
	public function update($type, int $id, array $callback)
	{		
		return $this->query('UPDATE '.$type.' SET '.static::explode($callback).' WHERE id='.$id);
	}			
}