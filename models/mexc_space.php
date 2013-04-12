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

class MexcSpace extends MexcSpaceAppModel
{
	var $name = 'MexcSpace';
	
	var $actsAs = array(
		'Containable',
		'Tree'
	);

	var $validate = array(
		'id' => array(
			'unique' => array(
				'rule' => 'isUnique'
			),
			'notEmpty' => array(
				'rule' => 'notEmpty'
			),
			'only_ansii' => array(
				'rule' => '/[a-zA-Z0-9_]+/'
			)
		),
		'name' => array(
			'notEmpty' => array(
				'rule' => 'notEmpty'
			)
		)
	);
	
	function beforeSave() 
	{
        $this->old = $this->find(array($this->primaryKey => $this->id));
		
		return true;
    } 
	
	/** 
	 * Save a profile to each MexcSpace
	 */
	function afterSave($created)
	{
		if (isset($this->data['MexcSpace']['id']) && !empty($this->data['MexcSpace']['id']))
		{
			
			if(isset($this->old['MexcSpace']['id']))
			{
				if ($this->data['MexcSpace']['id'] != $this->old['MexcSpace']['id'])
				{
					$slug = $this->old['MexcSpace']['id'];
				}
				else
				{
					$slug = $this->data['MexcSpace']['id'];
				}
			}
			else
			{
				$slug = $this->data['MexcSpace']['id'];
			}
			
			App::import('Model', 'JjUsers.UserProfile');
			$userProfile = new UserProfile();
			
			App::import('Model', 'JjUsers.UserPermission');
			$userPermission = new UserPermission();
			
			$search = $userPermission->findBySlug($slug);
			if (empty($search))
			{
				$data = array('UserPermission' => array(
					'slug' => $this->data['MexcSpace']['id'],
					'name' => 'Espaço - ' . $this->data['MexcSpace']['name'],
					'description' => $this->data['MexcSpace']['name']
				));
				$userPermission->saveAll($data, array('atomic' => false, 'callbacks' => false));
				$user_permission_id = $userPermission->id;
			}
			else
			{
				$userPermission->updateAll(
					array('slug' => '"'.$this->data['MexcSpace']['id'].'"', 'name' => '"Espaço - ' . $this->data['MexcSpace']['name']. '"'), 
					array('id' => $search['UserPermission']['id'])
				);
				$user_permission_id = $search['UserPermission']['id'];
			}
			
			$search = $userProfile->findBySlug($slug);
			if (empty($search))
			{
				$data = array(
					'UserProfile' => array(
						'slug' => $this->data['MexcSpace']['id'],
						'name' => 'Espaço - ' . $this->data['MexcSpace']['name'],
						'description' => $this->data['MexcSpace']['name']
					),
					'UserPermission' => array(
						'UserPermission' => array($user_permission_id)
					)
				);
				$userProfile->saveAll($data, array('atomic' => false, 'callbacks' => false));
				$user_profile_id = $userProfile->id;
				
			}
			else
			{
				$userProfile->updateAll(
					array('slug' => '"'. $this->data['MexcSpace']['id'] . '"', 'name' => '"Espaço - ' . $this->data['MexcSpace']['name'] . '"'), 
					array('id' => $search['UserProfile']['id'])
				);
				$user_profile_id = $search['UserProfile']['id'];
			}
			
			$techie = $userProfile->findBySlug('techie');			
			$permissions = array();
			foreach($techie['UserPermission'] as $permission)
			{
				if ($permission['id'] != $user_permission_id)
					$permissions[] = $permission['id'];
			}
			$permissions[] = $user_permission_id;
			$techie['UserPermission']['UserPermission'] = $permissions;
			$userProfile->saveAll($techie, array('atomic' => false, 'validate' => false, 'callbacks' => false));
			
			
			$god = $userProfile->findBySlug('god');
			$permissions = array();
			foreach($god['UserPermission'] as $permission)
			{
				if ($permission['id'] != $user_permission_id)
					$permissions[] = $permission['id'];
			}
			$permissions[] = $user_permission_id;
			$god['UserPermission']['UserPermission'] = $permissions;
			$userProfile->saveAll($god, array('atomic' => false, 'validate' => false, 'callbacks' => false));
			
		}
		
		return true;
	}
	
	/** 
	 * Returns an array with it and its children spaces.
	 */
	function getSpaceFamilyIds($spaceId)
	{
		return am(array($spaceId),Set::extract('/MexcSpace/id', $this->children($spaceId, false, 'id')));
	}
	
	/** 
	 * Test wheter a given space is a children space of a parent space
	 */
	function DNATest($id, $parentId)
	{
		return in_array($id, $this->getSpaceFamilyIds($parentId));
	}
	
	/** 
	 *  Given a certain space, it returns the conditions to retrieve all
	 *  its entries. (i. e. it includes children spaces in the conditions)
	 */
	function getConditionsForSpaceFiltering($spaceId, $modelAlias = null)
	{
		if (empty($spaceId))
			return array();
		
		$fieldName = 'mexc_space_id';
		if (!empty($modelAlias))
			$fieldName = $modelAlias . '.' . $fieldName;
			
		return array($fieldName => $this->getSpaceFamilyIds($spaceId));
	}
}
