<?php

/**
 * Contao Open Source CMS
 *
 * Copyright (C) 2005-2013 Leo Feyer
 *
 * @package   netzmacht-bootstrap
 * @author    netzmacht creative David Molineus
 * @license   MPL/2.0
 * @copyright 2013 netzmacht creative David Molineus
 */

namespace Netzmacht\Bootstrap\DataContainer;

use Netzmacht\Bootstrap\Model\Factory;

/**
 * Class Layout
 * @package Netzmacht\Bootstrap\DataContainer
 */
class Layout extends General
{

	/**
	 * singleton
	 * @var Layout
	 */
	protected static $instance;


	/**
	 * get single instance
	 * @return Layout
	 */
	public static function getInstance()
	{
		if(static::$instance === null)
		{
			static::$instance = new static();
		}

		return static::$instance;
	}


	/**
	 * modify palette if bootstrap is used
	 *
	 * @hook palettes_hook (MetaPalettes)
	 */
	public function generatePalette()
	{
		if(\Input::get('table') != 'tl_layout' || \Input::get('act') != 'edit')
		{
			return;
		}

		// we cannot use the model because of contao/core #6179
		//$layout = \LayoutModel::findByPk(\Input::get('id'));

		$layout = \Database::getInstance()->prepare('SELECT * FROM tl_layout WHERE id=?')->execute(\Input::get('id'));

		if($layout->layoutType == 'bootstrap')
		{
			// dynamically render palette so that extensions can plug into default palette
			$GLOBALS['TL_DCA']['tl_layout']['metapalettes']['__base__'] = $this->getMetaPaletteOfPalette('tl_layout');
			$GLOBALS['TL_DCA']['tl_layout']['metapalettes']['default extends __base__']  = $GLOBALS['BOOTSTRAP']['layout']['metapalette'];

			unset($GLOBALS['TL_DCA']['tl_layout']['palettes']['default']);

			foreach($GLOBALS['BOOTSTRAP']['layout']['metasubselectpalettes'] as $field => $meta)
			{
				foreach($meta as $value => $definition)
				{
					unset($GLOBALS['TL_DCA']['tl_layout']['subpalettes'][$field . '_' . $value]);
					$GLOBALS['TL_DCA']['tl_layout']['metasubselectpalettes'][$field][$value] = $definition;
				}
			}
		}
		else
		{
			\MetaPalettes::appendFields('tl_layout', 'title', array('layoutType'));
		}
	}


	/**
	 * get all uninstalled stylesheets
	 *
	 * @callback options_callback
	 * @param $dc
	 * @return mixed
	 */
	public function getStyleSheets($dc)
	{
		return $this->getUninstalledFiles('css', '\ThemePlus\Model\StylesheetModel',  \LayoutModel::findByPK(\Input::get('id'))->pid);
	}


	/**
	 * get all uninstalled javascript
	 *
	 * @callback options_callback
	 * @param $dc
	 * @return mixed
	 */
	public function getJavaScripts($dc)
	{
		return $this->getUninstalledFiles('js', '\ThemePlus\Model\JavaScriptModel', \LayoutModel::findByPK(\Input::get('id'))->pid);
	}


	/**
	 * import new stylesheet
	 *
	 * @param mixed $value
	 * @param \DC_Table|\MultiColumnWizard $dc
	 *
	 * @callback options_callback
	 */
	public function installStylesheets($value, $dc)
	{
		$class = class_exists('Bit3\Contao\ThemePlus\Model\StylesheetModel') ?
			'Bit3\Contao\ThemePlus\Model\StylesheetModel' :
			'ThemePlus\Model\StylesheetModel';

		$this->installFiles($value, $class, $dc->activeRecord, 'theme_plus_stylesheets', 'bootstrap_importStylesheets');
	}


	/**
	 * import new stylesheet
	 *
	 * @param mixed $value
	 * @param \DC_Table|\MultiColumnWizard $dc
	 *
	 * @callback options_callback
	 */
	public function installJavaScripts($value, $dc)
	{
		$class = class_exists('Bit3\Contao\ThemePlus\Model\JavaScriptModel') ?
			'Bit3\Contao\ThemePlus\Model\JavaScriptModel' :
			'ThemePlus\Model\JavaScriptModel';

		$this->installFiles($value, $class,	$dc->activeRecord, 'theme_plus_javascripts', 'bootstrap_importJavaScripts');
	}


	/**
	 * generic get uninstall files helper method
	 *
	 * @param string $type css|js
	 * @param string $modelClass
	 * @param int    $themeId
	 *
	 * @return mixed
	 */
	protected function getUninstalledFiles($type, $modelClass, $themeId)
	{
		$installed  = array();
		$collection = $modelClass::findBy('type="file" AND pid', $themeId);

		if($collection !== null)
		{
			$installed = $collection->fetchEach('file');
		}

		$available = $GLOBALS['BOOTSTRAP']['assets'][$type];

		foreach($available as $vendor => $files)
		{
			foreach($files as $file => $name)
			{
				if(in_array($file, $installed))
				{
					unset($available[$vendor][$file]);
				}
			}
		}

		return $available;
	}


	/**
	 * generic importer helper for asset files
	 *
	 * @param array|string  $value
	 * @param string        $modelClass
	 * @param \Model|\Result $layout
	 * @param string        $field      fieldname
	 * @param string        $toggle     name of toggle field
	 */
	protected function installFiles($value, $modelClass, $layout, $field, $toggle)
	{
		$value = deserialize($value, true);

		$result = $modelClass::findAll(array('limit' => '1', 'order' => 'sorting DESC'));
		$sorting = $result === null ? 0 : $result->sorting;
		$new = array();

		foreach($value as $file)
		{
			if($file['file'] == '') {
				continue;
			}

			if(substr($file['file'], 0, 6) == 'assets') {
				$source = 'assets';
			}
			elseif(substr($file['file'], 0, 5) == 'files') {
				$source = 'files';
			}
			else {
				$source = 'system/modules';
			}

			/** @var \Model $model */
			$model = new $modelClass();
			$model->tstamp        = time();
			$model->pid           = $layout->pid;
			$model->type          = 'file';
			$model->file          = $file['file'];
			$model->filesource    = $source;
			$model->cc            = $file['conditional'];
			$model->asseticFilter = $file['asseticFilter'];
			$model->sorting       = ++$sorting;
			$model->save();

			$new[] = $model->id;
		}

		$new  = array_merge(deserialize($layout->$field, true), $new);
		$data = array(
			$field => $new,
			$toggle => ''
		);

		\Database::getInstance()
			->prepare('UPDATE tl_layout %s WHERE id=?')
			->set($data)
			->execute($layout->id);
	}

}