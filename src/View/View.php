<?php

declare(strict_types=1);

namespace App\View;

class View
{
    private string $basePath;
    private array $sections = [];
    private string $currentSection = '';
    private ?string $layout = null;
    private array $data = [];
    private array $contentStack = [];
    private ?string $currentTemplate = null;
    private array $sectionContents = [];

    public function __construct(string $basePath)
    {
        $this->basePath = rtrim($basePath, '/') . '/';
    }

    /**
     * Get the base URL
     */
    public function baseUrl(string $path = ''): string
    {

        return rtrim(defined('BASE_URL') ? BASE_URL : '/', '/') . '/' . ltrim($path, '/');
    }

    /**
     * Generate an asset URL
     */
    public function asset(string $path): string
    {
        return $this->baseUrl('/public/assets/' . ltrim($path, '/'));
    }

    /**
     * Generate a URL for the application
     */
    public function url(string $path = ''): string
    {
        return $this->baseUrl(ltrim($path, '/'));
    }

    /**
     * Set the layout to extend
     */
    public function extend(string $layout): void
    {
        $this->layout = $layout;
    }

    /**
     * Start a section
     */
    public function section(string $name): void
    {
        $this->currentSection = $name;
        ob_start();
    }

    /**
     * End the current section
     */
    public function endSection(): void
    {
        if ($this->currentSection) {
            $this->sections[$this->currentSection] = ob_get_clean();
            $this->currentSection = '';
        }
    }

    /**
     * Yield a section
     */
    public function yield(string $section): string
    {
        return $this->sections[$section] ?? '';
    }

    /**
     * Include a partial
     */
    public function include(string $template, array $data = []): void
    {
        $this->includeTemplate($template, array_merge($this->data, $data));
    }

    /**
     * Render a template
     */
    public function render(string $template, array $data = []): string
    {
        // Store the current template
        $previousTemplate = $this->currentTemplate;
        $this->currentTemplate = $template;
        
        // Merge with existing data
        $this->data = array_merge($this->data, $data);
        
        // Start output buffering
        ob_start();
        
        try {
            // Include the template
            $this->includeTemplate($template);
            $content = ob_get_clean();
            
            // If a layout is set, render it
            if ($this->layout) {
                // Store the content in the 'content' section if not already set
                if (!isset($this->sections['content'])) {
                    $this->sections['content'] = $content;
                }
                
                // Render the layout
                ob_start();
                $this->includeTemplate($this->layout);
                $content = ob_get_clean();
            }
            
            return $content;
        } catch (\Throwable $e) {
            ob_end_clean();
            throw $e;
        } finally {
            // Restore the previous template
            $this->currentTemplate = $previousTemplate;
        }
    }

    /**
     * Include a template file
     */
    private function includeTemplate(string $template, array $data = []): void
    {
        $file = $this->basePath . $template . '.php';
        
        if (!file_exists($file)) {
            throw new \RuntimeException("View template not found: {$file}");
        }
        
        // Make view methods available in templates
        $view = $this;
        
        // Extract data for the template
        extract($this->data, EXTR_SKIP);
        
        include $file;
    }


    /**
     * Set template data
     */
    public function setData(array $data): void
    {
        $this->data = array_merge($this->data, $data);
    }

    /**
     * Format bytes to human-readable format
     */
    public function formatBytes($bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max((float)$bytes, 0);
        $pow = $bytes > 0 ? floor(log($bytes) / log(1024)) : 0;
        $pow = min($pow, count($units) - 1);
        $bytes = $bytes / (1024 ** $pow);
        return round($bytes, $precision) . ' ' . $units[$pow];
    }

    /**
     * Format a timestamp
     */
    public function formatDate(int $timestamp, string $format = 'Y-m-d H:i:s'): string
    {
        return date($format, $timestamp);
    }

    /**
     * Format a duration in seconds to a human-readable format
     */
    public function formatDuration(int $seconds): string
    {
        if ($seconds < 1) {
            return '0s';
        }

        $parts = [];
        $dtF = new \DateTime('@0');
        $dtT = new \DateTime("@$seconds");
        $diff = $dtF->diff($dtT);

        if ($diff->d > 0) {
            $parts[] = $diff->d . 'd';
        }
        if ($diff->h > 0) {
            $parts[] = $diff->h . 'h';
        }
        if ($diff->i > 0) {
            $parts[] = $diff->i . 'm';
        }
        if ($diff->s > 0) {
            $parts[] = $diff->s . 's';
        }

        return implode(' ', array_slice($parts, 0, 2)); // Return first two parts
    }

    /**
     * Format uptime from seconds to a human-readable format.
     */
    public function formatUptime(int $seconds): string
    {
        $days = floor($seconds / (3600 * 24));
        $hours = floor(($seconds % (3600 * 24)) / 3600);
        $minutes = floor(($seconds % 3600) / 60);
        $seconds = $seconds % 60;

        $parts = [];
        if ($days > 0) {
            $parts[] = $days . 'd';
        }
        if ($hours > 0) {
            $parts[] = $hours . 'h';
        }
        if ($minutes > 0) {
            $parts[] = $minutes . 'm';
        }
        if ($seconds > 0) {
            $parts[] = $seconds . 's';
        }

        return empty($parts) ? '0s' : implode(' ', $parts);
    }

    /**
     * Escape HTML special characters in a string
     *
     * @param string $value
     * @return string
     */
    public function e(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8', false);
    }

    /**
     * Get CSRF token field HTML
     */
    public function csrfField(): string
    {
        return \App\View\Helper\FormHelper::csrfField();
    }

    /**
     * Get method field for spoofing HTTP methods
     */
    public function method(string $method): string
    {
        return \App\View\Helper\FormHelper::method($method);
    }

    /**
     * Get CSRF token
     */
    public function csrfToken(): string
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if (empty($_SESSION['_csrf_token'])) {
            $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
        }
        
        return $_SESSION['_csrf_token'];
    }
}
