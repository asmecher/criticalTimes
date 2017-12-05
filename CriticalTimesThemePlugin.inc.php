<?php

/**
 * @file plugins/themes/criticalTimes/CriticalTimesThemePlugin.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class CriticalTimesThemePlugin
 * @ingroup plugins_themes_criticaltimes
 *
 * @brief OJS 3 theme for Critical Times, a journal run by the International
 *  Consortium of Critical Theory
 */

import('lib.pkp.classes.plugins.ThemePlugin');

class CriticalTimesThemePlugin extends ThemePlugin {

	/**
	 * @copydoc ThemePlugin::init()
	 */
	public function init() {

		$this->addStyle(
			'fontSourceSerifPro',
			'//fonts.googleapis.com/css?family=Source+Serif+Pro:400,700',
			array('baseUrl' => '')
		);

		$this->addStyle(
			'fontAwesome',
			'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.css',
			array('baseUrl' => '')
		);

		$this->addStyle('stylesheet', 'styles/index.less');

		$this->addScript('jQuery', '//code.jquery.com/jquery-3.1.1.slim.min.js', array('baseUrl' => ''));
		$this->addScript('popper', 'js/lib/popper/popper.js');
		$this->addScript('bsUtil', 'js/lib/bootstrap/util.js');
		$this->addScript('bsDropdown', 'js/lib/bootstrap/dropdown.js');
		$this->addScript('criticalTimes', 'js/main.js');

		$this->addMenuArea(array('primary', 'footer'));

		// Get extra data for templates
		HookRegistry::register ('TemplateManager::display', array($this, 'loadTemplateData'));

		// Add custom settings to issues
		HookRegistry::register('issuedao::getAdditionalFieldNames', array($this, 'addIssueDaoFields'));
		HookRegistry::register('LoadComponentHandler', array($this, 'loadIssueTocHandler'));
		HookRegistry::register ('TemplateManager::fetch', array($this, 'loadIssueTocTemplateData'));
	}

	/**
	 * Get the display name of this plugin
	 * @return string
	 */
	public function getDisplayName() {
		return __('plugins.themes.criticalTimes.name');
	}

	/**
	 * Get the description of this plugin
	 * @return string
	 */
	public function getDescription() {
		return __('plugins.themes.criticalTimes.description');
	}

	/**
	 * Load custom data for templates
	 *
	 * @param string $hookName
	 * @param array $args [
	 *		@option TemplateManager
	 *		@option string Template file requested
	 *		@option string
	 *		@option string
	 *		@option string output HTML
	 * ]
	 */
	public function loadTemplateData($hookName, $args) {
		$request = Application::getRequest();
		$templateMgr = $args[0];
		$template = $args[1];

		$templateMgr->assign('ctThemePath', $request->getBaseUrl() . '/' . $this->getPluginPath());

		if ($template === 'frontend/pages/article.tpl') {
			$this->loadArticleTemplateData($hookName, $args);
		}

		if ($template === 'frontend/pages/issue.tpl' || $template === 'frontend/pages/indexJournal.tpl') {
			$this->loadIssueTemplateData($hookName, $args);
		}
	}

	/**
	 * Load custom data for article templates
	 *
	 * @see CriticalTimesThemePlugin::loadTemplateData()
	 */
	public function loadArticleTemplateData($hookName, $args) {
		$request = Application::getRequest();
		$context = $request->getContext();
		$contextId = $context ? $context->getId() : CONTEXT_ID_NONE;
		$dispatcher = $request->getDispatcher();
		$templateMgr = $args[0];
		$article = $templateMgr->get_template_vars('article');

		$authorString = join(', ', array_map(function($author) {
			return $author->getFullName();
		}, $article->getAuthors()));

		$section = $templateMgr->get_template_vars('section');
		$sectionDao = DAORegistry::getDAO('SectionDAO');
		$section = $sectionDao->getById($section->getId(), $contextId);

		$templateMgr->assign(array(
			'authorString' => $authorString,
			'sectionPath' => $section->getData('browseByPath'),
		));
	}

	/**
	 * Load custom data for an issue (homepage and single issue)
	 *
	 * @see CriticalTimesThemePlugin::loadTemplateData()
	 */
	public function loadIssueTemplateData($hookName, $args) {
		$request = Application::getRequest();
		$context = $request->getContext();
		$contextId = $context ? $context->getId() : CONTEXT_ID_NONE;
		$dispatcher = $request->getDispatcher();
		$templateMgr = $args[0];
		$issue = $templateMgr->get_template_vars('issue');

		// Get data for grouped issue table of contents
		$publishedArticleDao = DAORegistry::getDAO('PublishedArticleDAO');
		$toc = array();
		for ($i = 1; $i < 7; $i++) {
			$items = explode(',', trim($issue->getData('group' . $i . 'Items')));
			$items = array_values(array_unique($items));
			$articles = array();
			foreach ($items as $item) {
				if (!ctype_digit($item)) {
					continue;
				}
				$article = $publishedArticleDao->getById($item);
				if ($article) {
					$articles[] = $article;
				}
			}

			if (!empty($articles)) {
				$toc[] = array(
					'name' => $issue->getData('group' . $i . 'Name'),
					'description' => $issue->getData('group' . $i . 'Description'),
					'articles' => $articles,
					'isSpecial' => $issue->getData('group' . $i . 'IsSpecial'),
				);
			}
		}

		$templateMgr->assign(array(
			'ctToc' => $toc,
		));
	}

	/**
	 * Add the additional sttings fields to the issue dao
	 *
	 * @param $hookName string
	 * @param $args array [
	 *		@option SectionDAO
	 *		@option array List of additional fields
	 * ]
	 */
	public function addIssueDaoFields($hookName, $args) {
		$fields =& $args[1];
		$fields[] = 'group1Items';
		$fields[] = 'group1IsSpecial';
		$fields[] = 'group2Name';
		$fields[] = 'group2Description';
		$fields[] = 'group2Items';
		$fields[] = 'group2IsSpecial';
		$fields[] = 'group3Name';
		$fields[] = 'group3Description';
		$fields[] = 'group3Items';
		$fields[] = 'group3IsSpecial';
		$fields[] = 'group4Name';
		$fields[] = 'group4Description';
		$fields[] = 'group4Items';
		$fields[] = 'group4IsSpecial';
		$fields[] = 'group5Name';
		$fields[] = 'group5Description';
		$fields[] = 'group5Items';
		$fields[] = 'group5IsSpecial';
		$fields[] = 'group6Name';
		$fields[] = 'group6Description';
		$fields[] = 'group6Items';
		$fields[] = 'group6IsSpecial';
	}

	/**
	 * Load the handler to deal with browse by section page requests
	 *
	 * @param $hookName string `LoadComponentHandler`
	 * @param $args array [
	 * 		@option string component
	 * 		@option string op
	 * ]
	 * @return bool
	 */
	public function loadIssueTocHandler($hookName, $args) {
		$component = $args[0];

		if ($component === 'plugins.themes.criticalTimes.controllers.CriticalTimesIssueTocHandler') {
			$op = $args[1];
			$this->import('controllers.CriticalTimesIssueTocHandler');
			$handler = new CriticalTimesIssueTocHandler();
			$handler->_plugin = $this;
			if (method_exists($handler, $op)) {
				$request = Application::getRequest();
				$router = $request->getRouter();
				$serviceEndpoint = array($handler, $op);
				$router->_authorizeInitializeAndCallRequest($serviceEndpoint, $request, $args);
				exit;
			}
		}
	}

	/**
	 * Load custom data for the issue table of contents form
	 *
	 * @param string $hookName
	 * @param array $args [
	 *		@option TemplateManager
	 *		@option string Template file requested
	 *		@option string
	 *		@option string
	 *		@option string output HTML
	 * ]
	 */
	public function loadIssueTocTemplateData($hookName, $args) {
		$templateMgr =& $args[0];
		$template = $args[1];

		if ($template === 'controllers/grid/issues/issueToc.tpl') {
			$this->import('controllers.CriticalTimesIssueTocFormHandler');
			$issueTocForm = new CriticalTimesIssueTocFormHandler($templateMgr->get_template_vars('issue'));
			$issueTocForm->initData($request);
			$templateMgr->assign($issueTocForm->_data);
		}
	}

}

?>
