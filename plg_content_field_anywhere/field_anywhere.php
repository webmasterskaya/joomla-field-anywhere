<?php

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Filesystem\Path;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\Plugin\CMSPlugin;

defined('_JEXEC') or die;

class PlgContentField_anywhere extends CMSPlugin
{
	/**
	 * Load the language file on instantiation.
	 *
	 * @var    boolean
	 * @since  1.0.0
	 */
	protected $autoloadLanguage = true;

	protected $itemsCache;
	protected $fieldsCache;

	/**
	 * PlgContentField_anywhere constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct(&$subject, $config = array())
	{
		$this->itemsCache  = new Joomla\Registry\Registry();
		$this->fieldsCache = new Joomla\Registry\Registry();
		parent::__construct($subject, $config);
	}


	/**
	 * Plugin that shows a custom field anywhere
	 *
	 * @param   string   $context  The context of the content being passed to the plugin.
	 * @param   object  &$item     The item object.  Note $article->text is also available
	 * @param   object  &$params   The article params
	 * @param   int      $page     The 'page' number
	 *
	 * @return void
	 *
	 * @throws Exception
	 * @since  1.0.0
	 */
	public function onContentPrepare($context, &$item, &$params, $page = 0)
	{
		// If the item has a context, overwrite the existing one
		if ($context == 'com_finder.indexer' && !empty($item->context))
		{
			$context = $item->context;
		}
		elseif ($context == 'com_finder.indexer')
		{
			// Don't run this plugin when the content is being indexed and we have no real context
			return;
		}

		// Don't run if there is no text property (in case of bad calls) or it is empty
		if (empty($item->text))
		{
			return;
		}

		// Simple performance check to determine whether bot should process further
		if (strpos($item->text, 'loadfield') === false)
		{
			return;
		}

		// Register FieldsHelper
		JLoader::register('FieldsHelper', JPATH_ADMINISTRATOR . '/components/com_fields/helpers/fields.php');
		// Add a directory of extended models classes
		BaseDatabaseModel::addIncludePath(Path::clean(__DIR__ . '/models/'));

		// Prepare the text
		if (isset($item->text))
		{
			$item->text = $this->prepare($item->text, $context, $item);
		}

		// Prepare the intro text
		if (isset($item->introtext))
		{
			$item->introtext = $this->prepare($item->introtext, $context, $item);
		}
	}

	/**
	 * Prepares the given string by parsing {loadfield} and {loadfieldgroup} groups and replacing them.
	 *
	 * @param   string  $content  The text to prepare
	 * @param   string  $context  The context of the content
	 * @param   object  $item     The item object
	 *
	 * @return string
	 *
	 * @throws Exception
	 * @since  1.0.0
	 */
	private function prepare($content, $context, $item)
	{
		preg_match_all('/{(loadfield|fieldgroup)(.*?)}/i', $content, $matches, PREG_SET_ORDER);

		if ($matches)
		{
			$parts = FieldsHelper::extract($context);

			if (count($parts) < 2)
			{
				$context = null;
			}
			else
			{
				$context = $parts[0] . '.' . $parts[1];
				if (!$this->itemsCache->get($context . '.' . $item->id, ''))
				{
					$this->itemsCache->set($context . '.' . $item->id, $item, '.');
				}
				$fields = FieldsHelper::getFields($context, $item, true);
				if ($fields)
				{
					foreach ($fields as $field)
					{
						if (!$this->fieldsCache->get($context . '.' . $item->id . '.' . $field->id, ''))
						{
							$this->fieldsCache->set($context . '.' . $item->id . '.' . $field->id, $field, '.');
						}
					}
				}
			}

			foreach ($matches as $match)
			{
				if (!$match[2])
				{
					continue;
				}

				$matchType   = strtolower(trim($match[1]));
				$matchParams = explode(';', $match[2]);

				//Если отсутсвуют необходимые параметры - пропускаем
				if (!array_key_exists(2, $matchParams))
				{
					continue;
				}
				else
				{
					$fieldId = (int) $matchParams[2];
				}

				//Если указан ID удалённого источника
				if (array_key_exists(3, $matchParams))
				{
					$destItemId  = trim($matchParams[3]);
					$destContext = strtolower(trim($matchParams[1]));
					$destParts   = FieldsHelper::extract($destContext);
					//Если контекст удалённого источника не одходит - пропускаем
					if (count($destParts) < 2)
					{
						continue;
					}
					$destContext = $destParts[0] . '.' . $destParts[1];

					//Получаем удалённый источник из кэша или из модели
					if ($destContext == $context && $destItemId == $item->id)
					{
						$destItem    = &$item;
						$destContext = $context;

					}
					else
					{
						$destItem = $this->itemsCache->get($destContext . '.' . $destItemId, false);
						if (!$destItem)
						{
							$modelComponent = ucfirst(substr($destParts[0], 4));
							$modelType      = ucfirst($destParts[1]);

							/** @var ContentModelExtendedArticle $model */
							$model = BaseDatabaseModel::getInstance($modelType, $modelComponent . 'ModelExtended');
							if (!$model)
							{
								continue;
							}
							$params = ComponentHelper::getParams($destParts[0]);
							$model->setState('params', $params);
							$destItem = $model->getItem($destItemId);
							if (!$destItem)
							{
								continue;
							}
							$this->itemsCache->set($destContext . '.' . $destItem->id, $destItem, '.');
						}
					}
					$destItemId = $destItem->id;
				}
				else
				{
					$destItem    = &$item;
					$destItemId  = $item->id;
					$destContext = $context;
				}

				if (!$destContext)
				{
					continue;
				}

				$fieldsById     = [];
				$fieldsByGroups = [];

				//Получаем поля из кэша или из модели
				$destFields = $this->fieldsCache->get($destContext . '.' . $destItemId, false);
				if (!$destFields)
				{
					$destFields = FieldsHelper::getFields($destContext, $destItem, true);
					if (!$destFields)
					{
						continue;
					}
					foreach ($destFields as $field)
					{
						$this->fieldsCache->set($destContext . '.' . $destItemId . '.' . $field->id,
							$field, '.');
						$fieldsById[$field->id]             = $field;
						$fieldsByGroups[$field->group_id][] = $field;
					}
				}
				else
				{
					foreach ($destFields as $field)
					{
						$fieldsById[$field->id]             = $field;
						$fieldsByGroups[$field->group_id][] = $field;
					}
				}

				$output = '';

				if ($matchType == 'loadfield' && $fieldId)
				{
					if (isset($fieldsById[$fieldId]))
					{
						//TODO: Сделать возможность, передавать layout в шоткоде
						$layout = $fieldsById[$fieldId]->params->get('layout', 'render');
						$output = FieldsHelper::render(
							$context,
							'field.' . $layout,
							array(
								'item'    => $destItem,
								'context' => $destContext,
								'field'   => $fieldsById[$fieldId]
							)
						);
					}
				}

				$content = preg_replace("|$match[0]|", addcslashes($output, '\\$'), $content, 1);
			}
		}


		return $content;
	}
}