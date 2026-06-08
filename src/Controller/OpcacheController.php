<?php

declare(strict_types=1);

namespace App\Controller;

use App\Http\Request;
use App\Http\Response;
use App\Services\OpcacheService;
use App\View\View;

class OpcacheController
{
    private View $view;
    private OpcacheService $opcacheService;

    public function __construct(View $view, OpcacheService $opcacheService)
    {
        $this->view = $view;
        $this->opcacheService = $opcacheService;
    }

    public function index(Request $request): Response
    {
        if (!$this->opcacheService->isEnabled()) {
            return new Response($this->view->render('opcache/disabled'));
        }

        $status = $this->opcacheService->getStatus();
        $config = $this->opcacheService->getConfiguration();

        // Provide defaults for status array to prevent errors.
        // Use deep merge so sub-array keys (e.g. opcache_statistics) keep their defaults
        // when only partially present in the opcache_get_status() output.
        $defaultStatus = [
            'opcache_enabled' => false,
            'cache_full' => false,
            'restart_pending' => false,
            'restart_in_progress' => false,
            'memory_usage' => [
                'used_memory' => 0,
                'free_memory' => 0,
                'wasted_memory' => 0,
            ],
            'opcache_statistics' => [
                'num_cached_keys' => 0,
                'num_pending_deletion' => 0,
                'max_cached_keys' => 0,
            ],
            'jit' => [
                'enabled' => false,
                'on' => false,
                'kind' => 'n/a',
                'opt_level' => 'n/a',
                'opt_flags' => 'n/a',
                'buffer_size' => 0,
                'buffer_free' => 0,
            ]
        ];

        $status = array_merge($defaultStatus, $status);
        // Deep merge known sub-arrays so partial real data doesn't wipe out defaults
        foreach (['memory_usage', 'opcache_statistics', 'jit'] as $key) {
            if (isset($status[$key]) && is_array($status[$key])) {
                $status[$key] = array_merge($defaultStatus[$key], $status[$key]);
            }
        }

        return new Response($this->view->render('opcache/status', [
            'status' => $status,
            'config' => $config,
            'service' => $this->opcacheService
        ]));
    }

    public function reset(Request $request): Response
    {
        if (!$this->opcacheService->isEnabled()) {
            return new Response(json_encode([
                'success' => false,
                'message' => 'OPcache is not enabled'
            ]), 400, ['Content-Type' => 'application/json']);
        }

        $result = $this->opcacheService->reset();

        if ($request->isAjax()) {
            return new Response(json_encode([
                'success' => $result,
                'message' => $result ? 'OPcache reset successful' : 'Failed to reset OPcache'
            ]), $result ? 200 : 500, ['Content-Type' => 'application/json']);
        }

        $_SESSION['flash'][] = $result
            ? ['type' => 'success', 'message' => 'OPcache has been reset successfully']
            : ['type' => 'danger', 'message' => 'Failed to reset OPcache'];

        return new Response('', 302, ['Location' => $this->view->url('/opcache')]);
    }

    public function scripts(Request $request): Response
    {
        if (!$this->opcacheService->isEnabled()) {
            return new Response($this->view->render('opcache/disabled'));
        }

        $status = $this->opcacheService->getStatus();
        $scripts = $status['scripts'];

        return new Response($this->view->render('opcache/scripts', [
            'scripts' => $scripts,
            'service' => $this->opcacheService
        ]));
    }
}
