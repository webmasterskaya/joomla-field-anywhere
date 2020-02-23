<?php
/**
 * @package    Joomla - Field anywhere
 * @version    __DEPLOY_VERSION__
 * @author     Artem Vasilev - Webmasterskaya
 * @copyright  Copyright (c) 2018 - 2020 Webmasterskaya. All rights reserved.
 * @license    GNU/GPL license: https://www.gnu.org/copyleft/gpl.html
 * @link       https://webmasterskaya.xyz/
 */

use Joomla\CMS\Categories\Categories;

defined('_JEXEC') or die;

// Load the model
JLoader::register('ContactModelCategory', JPATH_ROOT . '/components/com_contact/models/category.php');

class ContactModelExtendedCategories extends ContactModelCategory
{
	public function &getItem($pk = 'root')
	{
		$this->setState('category.id', $pk);
		return $this->getCategory($pk);
	}

	public function getCategory($pk = 'root')
	{
		if (!is_object($this->_item))
		{
			$options['countItems'] = 0;
			$categories = Categories::getInstance('Contact', $options);
			$this->_item = $categories->get($pk);
		}

		return $this->_item;
	}
}

// Load the model
JLoader::register('ContentModelCategory', JPATH_ROOT . '/components/com_content/models/category.php');

class ContentModelExtendedCategories extends ContentModelCategory
{
	public function &getItem($pk = 'root')
	{
		$this->setState('category.id', $pk);
		return $this->getCategory($pk);
	}

	/**
	 * Method to get category data for the current category
	 *
	 * @param   string  $pk
	 *
	 * @return  object
	 *
	 * @since   1.0.0
	 */
	public function getCategory($pk = 'root')
	{
		if (!is_object($this->_item))
		{
			$options['countItems'] = 0;
			$categories  = Categories::getInstance('Content', $options);
			$this->_item = $categories->get($pk);
		}

		return $this->_item;
	}
}
