<?php

namespace JBartels\Memcacheddiag2\Controller;


use TYPO3\CMS\Backend\Template\ModuleTemplateFactory;

/***************************************************************
 *
 *  Copyright notice
 *
 *  (c) 2016 Jan Bartels <j.bartels@arcor.de>
 *
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

/**
 * MemcacheController
 */
class MemcacheController extends \TYPO3\CMS\Extbase\Mvc\Controller\ActionController
{
    protected ModuleTemplateFactory $moduleTemplateFactory;

    public function injectModuleTemplateFactory(ModuleTemplateFactory $moduleTemplateFactory)
    {
        $this->moduleTemplateFactory = $moduleTemplateFactory;
    }

    public function listAction(): \Psr\Http\Message\ResponseInterface
    {
        $memcaches = $this->getCaches();
        $this->view->assign('memcaches', $memcaches);

        return $this->moduleTemplateFactory
            ->create($this->request)
            ->setFlashMessageQueue($this->getFlashMessageQueue())
            ->assign('memcaches', $memcaches)
            ->renderResponse('Memcache/List');

        return $this->htmlResponse();
    }

    protected function getCaches(): ?array
    {

        /*
        **  Example configuration:
        **
        **	'SYS' => array(
        **		'caching' => array(
        **			'cacheConfigurations' => array(
        **				'cache_hash' => array(
        **					'backend' => 'TYPO3\\CMS\\Core\\Cache\\Backend\\MemcachedBackend',
        **					'options' => array(
        **						'servers' => array(
        **							'localhost:11211',
        **						),
        **					),
        **				),
        */

        // any cache configured?
        if (!is_array($GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations'])) {
            return null;
        }

        // collect all memcached-servers

        $backendsToCheck = [
            'TYPO3\\CMS\\Core\\Cache\\Backend\\MemcachedBackend',
            'JBartels\\MemcachedBackend\\MemcachedBackend',
        ];

        $servers = array();
        foreach ($GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations'] as $keyCache => $valueCache) {
            if (isset($valueCache['backend'])
                && in_array($valueCache['backend'], $backendsToCheck) && isset($valueCache['options']['servers'])
            ) {
                foreach ($valueCache['options']['servers'] as $server)
                    $servers[] = $server;
            }
        }

        if (count($servers) === 0) {
            return null;
        }

        // add all memcached-servers
        $usedPeclModule = '';
        if (extension_loaded('memcache')) {
            $usedPeclModule = 'memcache';
        } elseif (extension_loaded('memcached')) {
            $usedPeclModule = 'memcached';
        }
        $memcachedPlugin = '\\' . ucfirst($usedPeclModule);

        // @phpstan-ignore-next-line
        $memcache_obj = new $memcachedPlugin;

        foreach ($servers as $server) {
            $serveroptions = explode(':', $server);
            $memcache_obj->addServer($serveroptions[0], $serveroptions[1]);
        }

        $stats = array();
        if ($usedPeclModule == 'memcache') {
            $stats = $memcache_obj->getExtendedStats();
        } elseif ($usedPeclModule == 'memcached') {
            $stats = $memcache_obj->getStats();
        }

        $caches = array();
        foreach ($servers as $server) {
            $caches[$server] = $stats[$server];
        }

        return $caches;
    }

}