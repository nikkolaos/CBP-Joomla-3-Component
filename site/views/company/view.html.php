<?php
/*----------------------------------------------------------------------------------|  www.giz.de  |----/
	Deutsche Gesellschaft für International Zusammenarbeit (GIZ) Gmb 
/-------------------------------------------------------------------------------------------------------/

	@version		3.0.9
	@build			2nd December, 2015
	@created		15th June, 2012
	@package		Cost Benefit Projection
	@subpackage		view.html.php
	@author			Llewellyn van der Merwe <http://www.vdm.io>	
	@owner			Deutsche Gesellschaft für International Zusammenarbeit (GIZ) Gmb
	@copyright		Copyright (C) 2015. All Rights Reserved
	@license		GNU/GPL Version 2 or later - http://www.gnu.org/licenses/gpl-2.0.html
	
/-------------------------------------------------------------------------------------------------------/
	Cost Benefit Projection Tool.
/------------------------------------------------------------------------------------------------------*/

// No direct access to this file
defined('_JEXEC') or die('Restricted access');

// import Joomla view library
jimport('joomla.application.component.view');

/**
 * Company View class
 */
class CostbenefitprojectionViewCompany extends JViewLegacy
{
	/**
	 * display method of View
	 * @return void
	 */
	public function display($tpl = null)
	{
		// Check for errors.
		if (count($errors = $this->get('Errors')))
                {
			JError::raiseError(500, implode('<br />', $errors));
			return false;
		}

		// Assign the variables
		$this->form 		= $this->get('Form');
		$this->item 		= $this->get('Item');
		$this->script 		= $this->get('Script');
		$this->state		= $this->get('State');
                // get action permissions
		$this->canDo		= CostbenefitprojectionHelper::getActions('company',$this->item);
		// get input
		$jinput = JFactory::getApplication()->input;
		$this->ref 		= $jinput->get('ref', 0, 'word');
		$this->refid            = $jinput->get('refid', 0, 'int');
		$this->referral         = '';
		if ($this->refid)
                {
                        // return to the item that refered to this item
                        $this->referral = '&ref='.(string)$this->ref.'&refid='.(int)$this->refid;
                }
                elseif($this->ref)
                {
                        // return to the list view that refered to this item
                        $this->referral = '&ref='.(string)$this->ref;
                }

		// [6488] Get Linked view data
		$this->yosscaling_factors		= $this->get('Yosscaling_factors');

		// [6488] Get Linked view data
		$this->zjtinterventions		= $this->get('Zjtinterventions');

		// Set the toolbar
		$this->addToolBar();

		// Display the template
		parent::display($tpl);

		// Set the document
		$this->setDocument();
	}


	/**
	 * Setting the toolbar
	 */
	protected function addToolBar()
	{
		// adding the joomla edit toolbar to the front
		JLoader::register('JToolbarHelper', JPATH_ADMINISTRATOR.'/includes/toolbar.php');
		JFactory::getApplication()->input->set('hidemainmenu', true);
		$user = JFactory::getUser();
		$userId	= $user->id;
		$isNew = $this->item->id == 0;

		JToolbarHelper::title( JText::_($isNew ? 'COM_COSTBENEFITPROJECTION_COMPANY_NEW' : 'COM_COSTBENEFITPROJECTION_COMPANY_EDIT'), 'pencil-2 article-add');
		// [10278] Built the actions for new and existing records.
		if ($this->refid || $this->ref)
		{
			if ($this->canDo->get('company.create') && $isNew)
			{
				// [10290] We can create the record.
				JToolBarHelper::save('company.save', 'JTOOLBAR_SAVE');
			}
			elseif ($this->canDo->get('company.edit'))
			{
				// [10302] We can save the record.
				JToolBarHelper::save('company.save', 'JTOOLBAR_SAVE');
			}
			if ($isNew)
			{
				// [10307] Do not creat but cancel.
				JToolBarHelper::cancel('company.cancel', 'JTOOLBAR_CANCEL');
			}
			else
			{
				// [10312] We can close it.
				JToolBarHelper::cancel('company.cancel', 'JTOOLBAR_CLOSE');
			}
		}
		else
		{
			if ($isNew)
			{
				// [10320] For new records, check the create permission.
				if ($this->canDo->get('company.create'))
				{
					JToolBarHelper::apply('company.apply', 'JTOOLBAR_APPLY');
					JToolBarHelper::save('company.save', 'JTOOLBAR_SAVE');
					JToolBarHelper::custom('company.save2new', 'save-new.png', 'save-new_f2.png', 'JTOOLBAR_SAVE_AND_NEW', false);
				};
				JToolBarHelper::cancel('company.cancel', 'JTOOLBAR_CANCEL');
			}
			else
			{
				if ($this->canDo->get('company.edit'))
				{
					// [10347] We can save the new record
					JToolBarHelper::apply('company.apply', 'JTOOLBAR_APPLY');
					JToolBarHelper::save('company.save', 'JTOOLBAR_SAVE');
					// [10350] We can save this record, but check the create permission to see
					// [10351] if we can return to make a new one.
					if ($this->canDo->get('company.create'))
					{
						JToolBarHelper::custom('company.save2new', 'save-new.png', 'save-new_f2.png', 'JTOOLBAR_SAVE_AND_NEW', false);
					}
				}
				$canVersion = ($this->canDo->get('core.version') && $this->canDo->get('company.version'));
				if ($this->state->params->get('save_history', 1) && $this->canDo->get('company.edit') && $canVersion)
				{
					JToolbarHelper::versions('com_costbenefitprojection.company', $this->item->id);
				}
				if ($this->canDo->get('company.create'))
				{
					JToolBarHelper::custom('company.save2copy', 'save-copy.png', 'save-copy_f2.png', 'JTOOLBAR_SAVE_AS_COPY', false);
				}
				JToolBarHelper::cancel('company.cancel', 'JTOOLBAR_CLOSE');
			}
		}
		JToolbarHelper::divider();
		// [10387] set help url for this view if found
		$help_url = CostbenefitprojectionHelper::getHelpUrl('company');
		if (CostbenefitprojectionHelper::checkString($help_url))
		{
			JToolbarHelper::help('COM_COSTBENEFITPROJECTION_HELP_MANAGER', false, $help_url);
		}
		// now initiate the toolbar
		$this->toolbar = JToolbar::getInstance();
	}

        /**
	 * Escapes a value for output in a view script.
	 *
	 * @param   mixed  $var  The output to escape.
	 *
	 * @return  mixed  The escaped value.
	 */
	public function escape($var)
	{
		if(strlen($var) > 30)
		{
    		// use the helper htmlEscape method instead and shorten the string
			return CostbenefitprojectionHelper::htmlEscape($var, $this->_charset, true, 30);
		}
                // use the helper htmlEscape method instead.
		return CostbenefitprojectionHelper::htmlEscape($var, $this->_charset);
	}

	/**
	 * Method to set up the document properties
	 *
	 * @return void
	 */
	protected function setDocument()
	{
		$isNew = ($this->item->id < 1);
		$document = JFactory::getDocument();
		$document->setTitle(JText::_($isNew ? 'COM_COSTBENEFITPROJECTION_COMPANY_NEW' : 'COM_COSTBENEFITPROJECTION_COMPANY_EDIT'));
		// we need this to fix the form display
		$document->addStyleSheet(JURI::root()."administrator/templates/isis/css/template.css");
		$document->addScript(JURI::root()."administrator/templates/isis/js/template.js");
		// the default style of this view
		$document->addStyleSheet(JURI::root()."components/com_costbenefitprojection/assets/css/company.css"); 

		// [6523] Add the CSS for Footable.
		$document->addStyleSheet(JURI::root() .'media/com_costbenefitprojection/footable/css/footable.core.min.css');

		// [6525] Use the Metro Style
		if (!isset($this->fooTableStyle) || 0 == $this->fooTableStyle)
		{
			$document->addStyleSheet(JURI::root() .'media/com_costbenefitprojection/footable/css/footable.metro.min.css');
		}
		// [6530] Use the Legacy Style.
		elseif (isset($this->fooTableStyle) && 1 == $this->fooTableStyle)
		{
			$document->addStyleSheet(JURI::root() .'media/com_costbenefitprojection/footable/css/footable.standalone.min.css');
		}

		// [6535] Add the JavaScript for Footable
		$document->addScript(JURI::root() .'media/com_costbenefitprojection/footable/js/footable.js');
		$document->addScript(JURI::root() .'media/com_costbenefitprojection/footable/js/footable.sort.js');
		$document->addScript(JURI::root() .'media/com_costbenefitprojection/footable/js/footable.filter.js');
		$document->addScript(JURI::root() .'media/com_costbenefitprojection/footable/js/footable.paginate.js');

		$footable = "jQuery(document).ready(function() { jQuery(function () { jQuery('.footable').footable(); }); jQuery('.nav-tabs').on('click', 'li', function() { setTimeout(tableFix, 10); }); }); function tableFix() { jQuery('.footable').trigger('footable_resize'); }";
		$document->addScriptDeclaration($footable);

		// default javascript of this view
		$document->addScript(JURI::root().$this->script);
		$document->addScript(JURI::root(). "components/com_costbenefitprojection/views/company/submitbutton.js");
		JText::script('view not acceptable. Error');
	}
}