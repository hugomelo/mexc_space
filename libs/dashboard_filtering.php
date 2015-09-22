<?php

/**
 *
 * Copyright 2011-2013, Museu Exploratório de Ciências da Unicamp (http://www.museudeciencias.com.br)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2011-2013, Museu Exploratório de Ciências da Unicamp (http://www.museudeciencias.com.br)
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 * @link          https://github.com/museudecienciasunicamp/mexc_space.git Mexc Space public repository
 */

class DashboardFiltering
{	

/**
 * Custom filter conditions
 * 
 * @access public
 */
	public static function getPermissionConditions(&$Controller, $conditions = array())
	{
		$spaces = $Controller->MexcSpace->find('list');
		
		$allowedSpaces = array();
		foreach ($spaces as $mexc_space_id => $name)
		{
			if ($Controller->JjAuth->can($mexc_space_id))
			{
				$spaceConditions = $Controller->MexcSpace->getConditionsForSpaceFiltering($mexc_space_id);
				if (!empty($spaceConditions['mexc_space_id']))
				{
					$allowedSpaces = array_merge($spaceConditions['mexc_space_id'], $allowedSpaces);
				}
			}
		}
		
		if (!empty($conditions['mexc_space_id']))
			$conditions['mexc_space_id'] = array_intersect($conditions['mexc_space_id'], $allowedSpaces);
		
		return $conditions;
	}
	
/**
 * Check a permission to edit modules by filter
 * 
 * @access public
 */
	public static function can(&$Controller, $data)
	{
		if (!isset($data['mexc_space_id']))
		{
			return true;
		}

		$parent = $Controller->MexcSpace->getParentNode($data['mexc_space_id']);
		if (!empty($parent))
		{
			$space = $parent['MexcSpace']['id'];
		}
		else
		{
			$space = $data['mexc_space_id'];
		}
		
		return $Controller->JjAuth->can($space);
	}
}

