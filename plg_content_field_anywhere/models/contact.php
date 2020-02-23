<?php
/**
 * @package    Joomla - Field anywhere
 * @version    __DEPLOY_VERSION__
 * @author     Artem Vasilev - Webmasterskaya
 * @copyright  Copyright (c) 2018 - 2020 Webmasterskaya. All rights reserved.
 * @license    GNU/GPL license: https://www.gnu.org/copyleft/gpl.html
 * @link       https://webmasterskaya.xyz/
 * @since      1.0.0
 */

use Joomla\CMS\Factory;
use Joomla\Registry\Registry;

defined('_JEXEC') or die;

// Load the model
JLoader::register('ContactModelContact', JPATH_ROOT . '/components/com_contact/models/contact.php');

class ContactModelExtendedContact extends ContactModelContact
{
	/**
	 * Gets a contact
	 *
	 * @param   integer  $pk  Id for the contact
	 *
	 * @return  mixed Object or null
	 *
	 * @since   1.6.0
	 */
	public function &getItem($pk = null)
	{
		$pk = (!empty($pk)) ? $pk : (int) $this->getState('contact.id');

		if ($this->_item === null)
		{
			$this->_item = array();
		}

		if (!isset($this->_item[$pk]))
		{
			try
			{
				$db    = $this->getDbo();
				$query = $db->getQuery(true);

				// Changes for sqlsrv
				$case_when = ' CASE WHEN ';
				$case_when .= $query->charLength('a.alias', '!=', '0');
				$case_when .= ' THEN ';
				$a_id      = $query->castAsChar('a.id');
				$case_when .= $query->concatenate(array($a_id, 'a.alias'), ':');
				$case_when .= ' ELSE ';
				$case_when .= $a_id . ' END as slug';

				$case_when1 = ' CASE WHEN ';
				$case_when1 .= $query->charLength('c.alias', '!=', '0');
				$case_when1 .= ' THEN ';
				$c_id       = $query->castAsChar('c.id');
				$case_when1 .= $query->concatenate(array($c_id, 'c.alias'), ':');
				$case_when1 .= ' ELSE ';
				$case_when1 .= $c_id . ' END as catslug';

				$query->select($this->getState('item.select', 'a.*') . ',' . $case_when . ',' . $case_when1)
					->from('#__contact_details AS a')

					// Join on category table.
					->select('c.title AS category_title, c.alias AS category_alias, c.access AS category_access')
					->join('LEFT', '#__categories AS c on c.id = a.catid')

					// Join over the categories to get parent category titles
					->select('parent.title as parent_title, parent.id as parent_id, parent.path as parent_route, parent.alias as parent_alias')
					->join('LEFT', '#__categories as parent ON parent.id = c.parent_id')
					->where('a.id = ' . (int) $pk);

				// Filter by start and end dates.
				$nullDate = $db->quote($db->getNullDate());
				$nowDate  = $db->quote(JFactory::getDate()->toSql());

				// Filter by published state.
				$published = $this->getState('filter.published');
				$archived  = $this->getState('filter.archived');

				if (is_numeric($published))
				{
					$query->where('(a.published = ' . (int) $published . ' OR a.published =' . (int) $archived . ')')
						->where('(a.publish_up = ' . $nullDate . ' OR a.publish_up <= ' . $nowDate . ')')
						->where('(a.publish_down = ' . $nullDate . ' OR a.publish_down >= ' . $nowDate . ')');
				}

				$db->setQuery($query);
				$data = $db->loadObject();

				if (empty($data))
				{
					return false;
				}

				// Check for published state if filter set.
				if ((is_numeric($published) || is_numeric($archived)) && (($data->published != $published) && ($data->published != $archived)))
				{
					return false;
				}

				/**
				 * In case some entity params have been set to "use global", those are
				 * represented as an empty string and must be "overridden" by merging
				 * the component and / or menu params here.
				 */
				$registry = new Registry($data->params);

				$data->params = clone $this->getState('params');
				$data->params->merge($registry);

				$registry       = new Registry($data->metadata);
				$data->metadata = $registry;

				// Some contexts may not use tags data at all, so we allow callers to disable loading tag data
				if ($this->getState('load_tags', true))
				{
					$data->tags = new JHelperTags;
					$data->tags->getItemTags('com_contact.contact', $data->id);
				}

				// Compute access permissions.
				if (($access = $this->getState('filter.access')))
				{
					// If the access filter has been set, we already know this user can view.
					$data->params->set('access-view', true);
				}
				else
				{
					// If no access filter is set, the layout takes some responsibility for display of limited information.
					$user   = Factory::getUser();
					$groups = $user->getAuthorisedViewLevels();

					if ($data->catid == 0 || $data->category_access === null)
					{
						$data->params->set('access-view', in_array($data->access, $groups));
					}
					else
					{
						$data->params->set('access-view',
							in_array($data->access, $groups) && in_array($data->category_access, $groups));
					}
				}

				$this->_item[$pk] = $data;
			}
			catch (Exception $e)
			{
				$this->setError($e);
				$this->_item[$pk] = false;
			}
		}

		if ($this->_item[$pk])
		{
			$this->buildContactExtendedData($this->_item[$pk]);
		}

		return $this->_item[$pk];
	}
}