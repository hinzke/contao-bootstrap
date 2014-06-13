<?php

/**
 * Contao Open Source CMS
 *
 * Copyright (C) 2005-2012 Leo Feyer
 *
 * @package   netzmacht-bootstrap
 * @author    David Molineus <http://www.netzmacht.de>
 * @license   GNU/LGPL
 * @copyright Copyright 2012 David Molineus netzmacht creative
 *
 **/

namespace Netzmacht\Bootstrap\ContentElement;

/**
 * Class ContentColumnSet provides an column set based on subcolumns_bootstrap_customize for including articles
 *
 * @package Netzmacht\Bootstrap
 */
class ColumnSet extends BootstrapAbstract
{

	/**
	 * @var array
	 */
	protected $arrBootstrapAttributes = array('columnset', 'articleMarkup');

	/**
	 * @var string
	 */
	protected $strTemplate = 'ce_bootstrap_columnset';


	/**
	 * generate column set
	 *
	 * @return string
	 */
	public function generate()
	{
		$output    = parent::generate();
		$columnSet = \Netzmacht\ColumnSet\ColumnsetModel::findByPK($this->columnset_id);

		if(TL_MODE == 'BE') {
			$output = sprintf(
				'<h1>%s <small>[%s: %s]</small></h1>%s',
				$this->headline,
				$GLOBALS['TL_LANG']['CTE']['bootstrap_columnset'][0],
				$columnSet->title,
				$output
			);
		}

		return $output;
	}


	/**
	 * compile column set
	 */
	protected function compile()
	{
		$data = deserialize($this->columnset, true);
		$articles = array();

		$container = \Netzmacht\ColumnSet\ColumnSet::prepareContainer($this->columnset_id);
		$i=0;

		foreach($data as $article) {
			$articles[] = array(
				'article' => $this->getArticle($article['article'], false, !((bool) $this->articleMarkup)),
				'class'   => $container[$i++][0],
				'id'      => $article['article'],
			);
		}

		$this->Template->articles = $articles;
	}

}