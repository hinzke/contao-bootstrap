<?php

namespace Netzmacht\Bootstrap\Form;


use Netzmacht\Bootstrap\Bootstrap;
use Netzmacht\Bootstrap\Helper\Icons;
use Netzmacht\FormHelper\Event\Events;
use Netzmacht\FormHelper\Event\GenerateEvent;
use Netzmacht\FormHelper\Event\SelectLayoutEvent;
use Netzmacht\FormHelper\Html\Element;
use Netzmacht\FormHelper\Transfer\Container;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;


class Subscriber implements EventSubscriberInterface
{

	/**
	 * Returns an array of event names this subscriber wants to listen to.
	 *
	 * The array keys are event names and the value can be:
	 *
	 *  * The method name to call (priority defaults to 0)
	 *  * An array composed of the method name to call and the priority
	 *  * An array of arrays composed of the method names to call and respective
	 *    priorities, or 0 if unset
	 *
	 * For instance:
	 *
	 *  * array('eventName' => 'methodName')
	 *  * array('eventName' => array('methodName', $priority))
	 *  * array('eventName' => array(array('methodName1', $priority), array('methodName2'))
	 *
	 * @return array The event names to listen to
	 *
	 * @api
	 */
	public static function getSubscribedEvents()
	{
		return array(
			Events::SELECT_LAYOUT => 'selectLayout',
			Events::GENERATE => 'generate',
		);
	}


	/**
	 * @param SelectLayoutEvent $event
	 */
	public function selectLayout(SelectLayoutEvent $event)
	{
		if(Bootstrap::isEnabled()) {
			$event->setLayout('bootstrap');
		}
	}


	/**
	 * @param GenerateEvent $event
	 */
	public function generate(GenerateEvent $event)
	{
		$container = $event->getContainer();
		$element   = $event->getContainer()->getElement();
		$widget    = $event->getWidget();
		$label     = $event->getLabel();
		$errors    = $event->getErrors();

		// add label class
		$label->addClass('control-label');

		// apply form control class to the element
		if(!$this->getConfig($widget->type, 'noFormControl')) {
			$element->addClass('form-control');
		}

		// add column layout
		if(!$widget->tableless) {
			$container->setRenderContainer(true);
			$container->addClass($GLOBALS['BOOTSTRAP']['form']['tableFormat']['control']);

			if(!$widget->label || $this->getConfig($widget->type, 'noLabel')) {
				$container->addClass($GLOBALS['BOOTSTRAP']['form']['tableFormat']['offset']);
			}
			else {
				$label->addClass($GLOBALS['BOOTSTRAP']['form']['tableFormat']['label']);
			}
		}

		// enable styled select
		if($GLOBALS['BOOTSTRAP']['form']['styleSelect']['enabled'] && $this->getConfig($widget->type, 'styleSelect')) {
			$element->addClass($GLOBALS['BOOTSTRAP']['form']['styleSelect']['class']);
			$element->setAttribute('data-style', $GLOBALS['BOOTSTRAP']['form']['styleSelect']['style']);
		}

		// generate input group
		if($this->getConfig($widget->type, 'allowInputGroup') &&
			($widget->bootstrap_addIcon ||
				$widget->bootstrap_addUnit ||
				$container->has('submit') ||
				$widget->type == 'captcha'
			)
		) {
			$inputGroup = new InputGroup();
			$inputGroup->setElement($element);
			$container->setWrapper($inputGroup);

			// add icon
			if($widget->bootstrap_addIcon) {
				$icon = Icons::generateIcon($widget->bootstrap_icon);

				if($widget->bootstrap_iconPosition == 'right') {
					$inputGroup->setRight($icon);
				}
				else {
					$inputGroup->setLeft($icon);
				}
			}

			// add unit
			if($widget->bootstrap_addUnit) {
				if($widget->$this->bootstrap_unit == 'right') {
					$inputGroup->setRight($widget->bootstrap_unit);
				}
				else {
					$inputGroup->setLeft($widget->bootstrap_unit);
				}
			}

			// add submit button into input group
			if($container->has('submit')) {
				$submit = $container->remove('submit');
				$submit->addClass('btn');

				$inputGroup->setRight($submit, $inputGroup::BUTTON);
			}

			// add captcha as form input group
			if($widget instanceof \FormCaptcha) {
				$captcha = $container->remove('question');
				$inputGroup->setRight($captcha);
			}
		}

		// inject errors into container
		$container->add('errors', $errors);
		$errors->addClass('help-block');

		if($event->getWidget()->type = 'upload') {
			$this->generateUpload($container);
		}
	}


	/**
	 * @param Container $container
	 */
	protected function generateUpload(Container $container)
	{
		$element   = $container->getElement();
		$element->addClass('invisible');

		$input = Element::createElement('input', array('type' => 'text'))
			->setAttribute('name', $element->getAttribute('name') . '_value');

		$click = sprintf('$(%s).click();', $element->getId());
		$submit = Element::createElement('button', array('type' => 'submit', 'value' => 'Datei auswählen'))
			->setAttribute('onclick', $click)
			->addClass('btn btn-default');

		$inputGroup = new InputGroup();
		$inputGroup->setElement($input);
		$inputGroup->setRight($submit);
		$container->add('upload', $inputGroup);
	}


	/**
	 * @param $type
	 * @param $name
	 * @return mixed
	 */
	protected function getConfig($type, $name)
	{
		if(!isset($GLOBALS['BOOTSTRAP']['form']['widgets'][$type])) {
			return false;
		}

		if(isset($GLOBALS['BOOTSTRAP']['form']['widgets'][$type][$name])) {
			return $GLOBALS['BOOTSTRAP']['form']['widgets'][$type][$name];
		}

		return false;
	}

}