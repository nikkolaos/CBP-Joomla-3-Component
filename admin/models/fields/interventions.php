<?php
/*----------------------------------------------------------------------------------|  www.giz.de  |----/
	Deutsche Gesellschaft für International Zusammenarbeit (GIZ) Gmb 
/-------------------------------------------------------------------------------------------------------/

	@version		3.0.9
	@build			2nd December, 2015
	@created		15th June, 2012
	@package		Cost Benefit Projection
	@subpackage		interventions.php
	@author			Llewellyn van der Merwe <http://www.vdm.io>	
	@owner			Deutsche Gesellschaft für International Zusammenarbeit (GIZ) Gmb
	@copyright		Copyright (C) 2015. All Rights Reserved
	@license		GNU/GPL Version 2 or later - http://www.gnu.org/licenses/gpl-2.0.html
	
/-------------------------------------------------------------------------------------------------------/
	Cost Benefit Projection Tool.
/------------------------------------------------------------------------------------------------------*/

// No direct access to this file
defined('_JEXEC') or die('Restricted access');

// import the list field type
jimport('joomla.form.helper');
JFormHelper::loadFieldClass('list');

/**
 * Interventions Form Field class for the Costbenefitprojection component
 */
class JFormFieldInterventions extends JFormFieldList
{
	/**
	 * The interventions field type.
	 *
	 * @var		string
	 */
	public $type = 'interventions'; 
	/**
	 * Override to add new button
	 *
	 * @return  string  The field input markup.
	 *
	 * @since   3.2
	 */
	protected function getInput()
	{
		// [7700] see if we should add buttons
		$setButton = $this->getAttribute('button');
		// [7702] get html
		$html = parent::getInput();
		// [7704] if true set button
		if ($setButton === 'true')
		{
			$user = JFactory::getUser();
			// [7708] only add if user allowed to create intervention
			if ($user->authorise('intervention.create', 'com_costbenefitprojection'))
			{
				// [7726] get the input from url
				$jinput = JFactory::getApplication()->input;
				// [7728] get the view name & id
				$values = $jinput->getArray(array(
					'id' => 'int',
					'view' => 'word'
				));
				// [7733] check if new item
				$ref = '';
				if (!is_null($values['id']) && strlen($values['view']))
				{
					// [7737] only load referal if not new item.
					$ref = '&amp;ref=' . $values['view'] . '&amp;refid=' . $values['id'];
				}
				// [7740] build the button
				$button = '<a class="btn btn-small btn-success"
					href="index.php?option=com_costbenefitprojection&amp;view=intervention&amp;layout=edit'.$ref.'" >
					<span class="icon-new icon-white"></span>' . JText::_('COM_COSTBENEFITPROJECTION_NEW') . '</a>';
				// [7744] return the button attached to input field
				return $html . $button;
			}
		}
		return $html;
	}

	/**
	 * Method to get a list of options for a list input.
	 *
	 * @return	array		An array of JHtml options.
	 */
	public function getOptions()
	{
		// get the input from url
		$jinput = JFactory::getApplication()->input;
		// get the view name & id
		$interId = $jinput->getInt('id', 0);
		// Get the user object.
		$user = JFactory::getUser();
		$userIs = CostbenefitprojectionHelper::userIs($user->id);
		$db = JFactory::getDBO();
		$query = $db->getQuery(true);
		$query->select($db->quoteName(array('a.id','a.name','a.company','a.share'),array('id','interventions_name','company','share')));
		$query->from($db->quoteName('#__costbenefitprojection_intervention', 'a'));
		$query->where($db->quoteName('a.published') . ' = 1');
		$query->where($db->quoteName('a.id') . ' != ' . $interId);
		if (!$user->authorise('core.admin'))
		{
			$companies = CostbenefitprojectionHelper::hisCompanies($user->id);
			if (CostbenefitprojectionHelper::checkArray($companies))
			{
				$companies = implode(',',$companies);
				// only load this users companies
				$query->where('a.company IN (' . $companies . ')');
			}
			else
			{
				// dont allow user to see any companies
				$query->where('a.company = -4');
			}
		}
		$query->order('a.name ASC');
		$db->setQuery((string)$query);
		$items = $db->loadObjectList();
		$options = array();
		if ($items)
		{
			foreach($items as $item)
			{
				if (!CostbenefitprojectionHelper::checkIntervetionAccess($item->id,$item->share,$item->company))
				{
					continue;
				}
				if (1 == $userIs)
				{
					$options[] = JHtml::_('select.option', $item->id, $item->interventions_name);
				}
				else
				{
					$compName = CostbenefitprojectionHelper::getId('company', $item->company, 'id', 'name');
					$options[] = JHtml::_('select.option', $item->id, $item->interventions_name . ' ('.$compName.')');
				}
			}
		}
		return $options;
	}
}