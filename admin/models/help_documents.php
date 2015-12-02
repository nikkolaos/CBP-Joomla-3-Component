<?php
/*----------------------------------------------------------------------------------|  www.giz.de  |----/
	Deutsche Gesellschaft für International Zusammenarbeit (GIZ) Gmb 
/-------------------------------------------------------------------------------------------------------/

	@version		3.0.9
	@build			2nd December, 2015
	@created		15th June, 2012
	@package		Cost Benefit Projection
	@subpackage		help_documents.php
	@author			Llewellyn van der Merwe <http://www.vdm.io>	
	@owner			Deutsche Gesellschaft für International Zusammenarbeit (GIZ) Gmb
	@copyright		Copyright (C) 2015. All Rights Reserved
	@license		GNU/GPL Version 2 or later - http://www.gnu.org/licenses/gpl-2.0.html
	
/-------------------------------------------------------------------------------------------------------/
	Cost Benefit Projection Tool.
/------------------------------------------------------------------------------------------------------*/

// No direct access to this file
defined('_JEXEC') or die('Restricted access');

// import the Joomla modellist library
jimport('joomla.application.component.modellist');

/**
 * Help_documents Model
 */
class CostbenefitprojectionModelHelp_documents extends JModelList
{
	public function __construct($config = array())
	{
		if (empty($config['filter_fields']))
        {
			$config['filter_fields'] = array(
				'a.id','id',
				'a.published','published',
				'a.ordering','ordering',
				'a.created_by','created_by',
				'a.modified_by','modified_by',
				'a.title','title',
				'a.type','type',
				'a.location','location',
				'a.admin_view','admin_view',
				'a.site_view','site_view'
			);
		}

		parent::__construct($config);
	}
	
	/**
	 * Method to auto-populate the model state.
	 *
	 * @return  void
	 */
	protected function populateState($ordering = null, $direction = null)
	{
		$app = JFactory::getApplication();

		// Adjust the context to support modal layouts.
		if ($layout = $app->input->get('layout'))
		{
			$this->context .= '.' . $layout;
		}
		$title = $this->getUserStateFromRequest($this->context . '.filter.title', 'filter_title');
		$this->setState('filter.title', $title);

		$type = $this->getUserStateFromRequest($this->context . '.filter.type', 'filter_type');
		$this->setState('filter.type', $type);

		$location = $this->getUserStateFromRequest($this->context . '.filter.location', 'filter_location');
		$this->setState('filter.location', $location);

		$admin_view = $this->getUserStateFromRequest($this->context . '.filter.admin_view', 'filter_admin_view');
		$this->setState('filter.admin_view', $admin_view);

		$site_view = $this->getUserStateFromRequest($this->context . '.filter.site_view', 'filter_site_view');
		$this->setState('filter.site_view', $site_view);
        
		$sorting = $this->getUserStateFromRequest($this->context . '.filter.sorting', 'filter_sorting', 0, 'int');
		$this->setState('filter.sorting', $sorting);
        
		$access = $this->getUserStateFromRequest($this->context . '.filter.access', 'filter_access', 0, 'int');
		$this->setState('filter.access', $access);
        
		$search = $this->getUserStateFromRequest($this->context . '.filter.search', 'filter_search');
		$this->setState('filter.search', $search);

		$published = $this->getUserStateFromRequest($this->context . '.filter.published', 'filter_published', '');
		$this->setState('filter.published', $published);
        
		$created_by = $this->getUserStateFromRequest($this->context . '.filter.created_by', 'filter_created_by', '');
		$this->setState('filter.created_by', $created_by);

		$created = $this->getUserStateFromRequest($this->context . '.filter.created', 'filter_created');
		$this->setState('filter.created', $created);

		// List state information.
		parent::populateState($ordering, $direction);
	}
	
	/**
	 * Method to get an array of data items.
	 *
	 * @return  mixed  An array of data items on success, false on failure.
	 */
	public function getItems()
	{ 
		// [10545] check in items
		$this->checkInNow();

		// load parent items
		$items = parent::getItems();

		// [10620] set values to display correctly.
		if (CostbenefitprojectionHelper::checkArray($items))
		{
			// [10623] get user object.
			$user = JFactory::getUser();
			foreach ($items as $nr => &$item)
			{
				$access = ($user->authorise('help_document.access', 'com_costbenefitprojection.help_document.' . (int) $item->id) && $user->authorise('help_document.access', 'com_costbenefitprojection'));
				if (!$access)
				{
					unset($items[$nr]);
					continue;
				}

				// [10691] decode groups
				$groupsArray = json_decode($item->groups, true);
				if (CostbenefitprojectionHelper::checkArray($groupsArray))
				{
					$groupsNames = '';
					$counter = 0;
					foreach ($groupsArray as $groups)
					{
						if ($counter == 0)
						{
							$groupsNames .= CostbenefitprojectionHelper::getGroupName($groups);
						}
						else
						{
							$groupsNames .= ', '.CostbenefitprojectionHelper::getGroupName($groups);
						}
						$counter++;
					}
					$item->groups = $groupsNames;
				}
			}
		} 

		// [10886] set selection value to a translatable value
		if (CostbenefitprojectionHelper::checkArray($items))
		{
			foreach ($items as $nr => &$item)
			{
				// [10893] convert type
				$item->type = $this->selectionTranslation($item->type, 'type');
				// [10893] convert location
				$item->location = $this->selectionTranslation($item->location, 'location');
			}
		}

        
		// return items
		return $items;
	}

	/**
	* Method to convert selection values to translatable string.
	*
	* @return translatable string
	*/
	public function selectionTranslation($value,$name)
	{
		// [10919] Array of type language strings
		if ($name == 'type')
		{
			$typeArray = array(
				0 => 'COM_COSTBENEFITPROJECTION_HELP_DOCUMENT_SELECT_AN_OPTION',
				1 => 'COM_COSTBENEFITPROJECTION_HELP_DOCUMENT_JOOMLA_ARTICLE',
				2 => 'COM_COSTBENEFITPROJECTION_HELP_DOCUMENT_TEXT',
				3 => 'COM_COSTBENEFITPROJECTION_HELP_DOCUMENT_URL'
			);
			// [10950] Now check if value is found in this array
			if (isset($typeArray[$value]) && CostbenefitprojectionHelper::checkString($typeArray[$value]))
			{
				return $typeArray[$value];
			}
		}
		// [10919] Array of location language strings
		if ($name == 'location')
		{
			$locationArray = array(
				1 => 'COM_COSTBENEFITPROJECTION_HELP_DOCUMENT_ADMIN',
				2 => 'COM_COSTBENEFITPROJECTION_HELP_DOCUMENT_SITE'
			);
			// [10950] Now check if value is found in this array
			if (isset($locationArray[$value]) && CostbenefitprojectionHelper::checkString($locationArray[$value]))
			{
				return $locationArray[$value];
			}
		}
		return $value;
	}
	
	/**
	 * Method to build an SQL query to load the list data.
	 *
	 * @return	string	An SQL query
	 */
	protected function getListQuery()
	{
		// [7406] Get the user object.
		$user = JFactory::getUser();
		// [7408] Create a new query object.
		$db = JFactory::getDBO();
		$query = $db->getQuery(true);

		// [7411] Select some fields
		$query->select('a.*');

		// [7418] From the costbenefitprojection_item table
		$query->from($db->quoteName('#__costbenefitprojection_help_document', 'a'));

		// [7432] Filter by published state
		$published = $this->getState('filter.published');
		if (is_numeric($published))
		{
			$query->where('a.published = ' . (int) $published);
		}
		elseif ($published === '')
		{
			$query->where('(a.published = 0 OR a.published = 1)');
		}
		// [7529] Filter by search.
		$search = $this->getState('filter.search');
		if (!empty($search))
		{
			if (stripos($search, 'id:') === 0)
			{
				$query->where('a.id = ' . (int) substr($search, 3));
			}
			else
			{
				$search = $db->quote('%' . $db->escape($search, true) . '%');
				$query->where('(a.title LIKE '.$search.' OR a.type LIKE '.$search.' OR a.location LIKE '.$search.' OR a.admin_view LIKE '.$search.' OR a.site_view LIKE '.$search.')');
			}
		}

		// [7772] Filter by Type.
		if ($type = $this->getState('filter.type'))
		{
			$query->where('a.type = ' . $db->quote($db->escape($type, true)));
		}
		// [7772] Filter by Location.
		if ($location = $this->getState('filter.location'))
		{
			$query->where('a.location = ' . $db->quote($db->escape($location, true)));
		}
		// [7772] Filter by Admin_view.
		if ($admin_view = $this->getState('filter.admin_view'))
		{
			$query->where('a.admin_view = ' . $db->quote($db->escape($admin_view, true)));
		}
		// [7772] Filter by Site_view.
		if ($site_view = $this->getState('filter.site_view'))
		{
			$query->where('a.site_view = ' . $db->quote($db->escape($site_view, true)));
		}

		// [7488] Add the list ordering clause.
		$orderCol = $this->state->get('list.ordering', 'a.id');
		$orderDirn = $this->state->get('list.direction', 'asc');	
		if ($orderCol != '')
		{
			$query->order($db->escape($orderCol . ' ' . $orderDirn));
		}

		return $query;
	}

	/**
	* Method to get list export data.
	*
	* @return mixed  An array of data items on success, false on failure.
	*/
	public function getExportData($pks)
	{
		// [7196] setup the query
		if (CostbenefitprojectionHelper::checkArray($pks))
		{
			// [7199] Get the user object.
			$user = JFactory::getUser();
			// [7201] Create a new query object.
			$db = JFactory::getDBO();
			$query = $db->getQuery(true);

			// [7204] Select some fields
			$query->select('a.*');

			// [7206] From the costbenefitprojection_help_document table
			$query->from($db->quoteName('#__costbenefitprojection_help_document', 'a'));
			$query->where('a.id IN (' . implode(',',$pks) . ')');

			// [7223] Order the results by ordering
			$query->order('a.ordering  ASC');

			// [7225] Load the items
			$db->setQuery($query);
			$db->execute();
			if ($db->getNumRows())
			{
				$items = $db->loadObjectList();

				// [10620] set values to display correctly.
				if (CostbenefitprojectionHelper::checkArray($items))
				{
					// [10623] get user object.
					$user = JFactory::getUser();
					foreach ($items as $nr => &$item)
					{
						$access = ($user->authorise('help_document.access', 'com_costbenefitprojection.help_document.' . (int) $item->id) && $user->authorise('help_document.access', 'com_costbenefitprojection'));
						if (!$access)
						{
							unset($items[$nr]);
							continue;
						}

						// [10833] unset the values we don't want exported.
						unset($item->asset_id);
						unset($item->checked_out);
						unset($item->checked_out_time);
					}
				}
				// [10842] Add headers to items array.
				$headers = $this->getExImPortHeaders();
				if (CostbenefitprojectionHelper::checkObject($headers))
				{
					array_unshift($items,$headers);
				}
				return $items;
			}
		}
		return false;
	}

	/**
	* Method to get header.
	*
	* @return mixed  An array of data items on success, false on failure.
	*/
	public function getExImPortHeaders()
	{
		// [7245] Get a db connection.
		$db = JFactory::getDbo();
		// [7247] get the columns
		$columns = $db->getTableColumns("#__costbenefitprojection_help_document");
		if (CostbenefitprojectionHelper::checkArray($columns))
		{
			// [7251] remove the headers you don't import/export.
			unset($columns['asset_id']);
			unset($columns['checked_out']);
			unset($columns['checked_out_time']);
			$headers = new stdClass();
			foreach ($columns as $column => $type)
			{
				$headers->{$column} = $column;
			}
			return $headers;
		}
		return false;
	} 
	
	/**
	 * Method to get a store id based on model configuration state.
	 *
	 * @return  string  A store id.
	 *
	 */
	protected function getStoreId($id = '')
	{
		// [10168] Compile the store id.
		$id .= ':' . $this->getState('filter.id');
		$id .= ':' . $this->getState('filter.search');
		$id .= ':' . $this->getState('filter.published');
		$id .= ':' . $this->getState('filter.ordering');
		$id .= ':' . $this->getState('filter.created_by');
		$id .= ':' . $this->getState('filter.modified_by');
		$id .= ':' . $this->getState('filter.title');
		$id .= ':' . $this->getState('filter.type');
		$id .= ':' . $this->getState('filter.location');
		$id .= ':' . $this->getState('filter.admin_view');
		$id .= ':' . $this->getState('filter.site_view');

		return parent::getStoreId($id);
	}

	/**
	* Build an SQL query to checkin all items left checked out longer then a set time.
	*
	* @return  a bool
	*
	*/
	protected function checkInNow()
	{
		// [10561] Get set check in time
		$time = JComponentHelper::getParams('com_costbenefitprojection')->get('check_in');
		
		if ($time)
		{

			// [10566] Get a db connection.
			$db = JFactory::getDbo();
			// [10568] reset query
			$query = $db->getQuery(true);
			$query->select('*');
			$query->from($db->quoteName('#__costbenefitprojection_help_document'));
			$db->setQuery($query);
			$db->execute();
			if ($db->getNumRows())
			{
				// [10576] Get Yesterdays date
				$date = JFactory::getDate()->modify($time)->toSql();
				// [10578] reset query
				$query = $db->getQuery(true);

				// [10580] Fields to update.
				$fields = array(
					$db->quoteName('checked_out_time') . '=\'0000-00-00 00:00:00\'',
					$db->quoteName('checked_out') . '=0'
				);

				// [10585] Conditions for which records should be updated.
				$conditions = array(
					$db->quoteName('checked_out') . '!=0', 
					$db->quoteName('checked_out_time') . '<\''.$date.'\''
				);

				// [10590] Check table
				$query->update($db->quoteName('#__costbenefitprojection_help_document'))->set($fields)->where($conditions); 

				$db->setQuery($query);

				$db->execute();
			}
		}

		return false;
	}
}