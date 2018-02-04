<?php
/*
 * The MIT License (MIT)
 *
 * Copyright (c) 2018 TwelveTone LLC
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 */

namespace Grav\Plugin;

use Grav\Common\Plugin;

/**
 * Class AdminMediaMovePlugin
 * @package Grav\Plugin
 */
class AdminMediaMovePlugin extends Plugin
{

    const ROUTE = '/admin-media-move';

    public static function getSubscribedEvents()
    {
        return [
            'onPluginsInitialized' => ['onPluginsInitialized', 0]
        ];
    }

    public function getPath()
    {
        return '/' . trim($this->grav['admin']->base, '/') . '/' . trim(self::ROUTE, '/');
    }

    public function buildBaseUrl()
    {
        $ret = rtrim($this->grav['uri']->rootUrl(false), '/') . '/' . trim($this->getPath(), '/');
        return $ret;
    }

    public function onPluginsInitialized()
    {
        if (!$this->isAdmin() || !$this->grav['user']->authenticated) {
            return;
        }

        if ($this->grav['uri']->path() == $this->getPath()) {
            return;
        }

        $this->enable([
            'onTwigTemplatePaths' => ['onTwigTemplatePaths', 0],
            'onPagesInitialized' => ['onTwigExtensions', 0],
        ]);

        $this->grav['media-actions']->addAction("MediaMove", "Move", "arrows", function ($page, $mediaName, $payload) {

            $destination_route = $payload['destination_route'];

            if (!$destination_route || !$page || !$mediaName || !$payload) {
                $this->outputError("Invalid input");
            }

            $basePath = $page->path() . DS;
            $filePath = $basePath . $mediaName;
            if (!file_exists($filePath)) {
                $this->outputError("Media file not found");
            }

            // Locate the target page
            $targetPage = $this->grav['pages']->find($destination_route);
            if (!$targetPage) {
                $this->outputError("Page for route $destination_route not found");
            }

            $path = $targetPage->path();
            try {
                rename($filePath, "$path/$mediaName");
                $this->grav['log']->info("Moved media file '$mediaName' to '$path'");
            } catch (\Exception $e) {
                $this->outputError("Could not move file: " . $e);
            }

            $ret = [
                "error" => false
            ];

            // Redirects will not work for fetch, so send destination url in result
            if (get($payload, "go", false)) {
                // Get the admin edit-page url
                //$url = $this->grav['twig']->twig->getExtension('Grav\Plugin\Admin\AdminTwigExtension')->getPageUrl($this, $targetPage);
                $url = $this->grav['uri']->rootUrl(false) . "/admin/pages" . $targetPage->route();
                $ret["destination_url"] = $url;
            }

            //header('HTTP/1.1 200 OK');
            return $ret;
        });

    }

    public function onTwigTemplatePaths()
    {
        $this->grav['twig']->twig_paths[] = __DIR__ . '/templates';
    }

    public function onTwigExtensions()
    {
        $page = $this->grav['admin']->page(true);
        if (!$page) {
            return;
        }

        $modal_move = $this->grav['twig']->twig()->render('move-modal.twig.html', $this->config->get('plugins.admin-media-move.modal_move'));
        $jsConfig_move = [
            'MODAL' => $modal_move
        ];
        $this->grav['assets']->addInlineJs('var ADMIN_ADDON_MEDIA_MOVE = ' . json_encode($jsConfig_move) . ';', -1000, false);
        $this->grav['assets']->addJs('plugin://admin-media-move/assets/media_move_action.js', -1000, false);
    }

    public function outputError($msg)
    {
        header('HTTP/1.1 400 Bad Request');
        die(json_encode(['error' => ['msg' => $msg]]));
    }
}
