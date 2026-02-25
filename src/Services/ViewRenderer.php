<?php

// src/Services/ViewRenderer.php

namespace Sura\Services;

class ViewRenderer
{
    private string $viewsPath;

    public function __construct(string $viewsPath = '')
    {
        $this->viewsPath = $viewsPath ?: __DIR__ . '/../../resources/views';
    }

    public function render(string $view, array $data = []): string
    {
        $file = $this->viewsPath . '/' . $view . '.php';

        if (!file_exists($file)) {
            throw new \InvalidArgumentException("View file not found: $file");
        }

        // Экранивание данных для безопасности
        $escape = function (string $value): string {
            return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
        };

        extract($data, EXTR_SKIP);

        ob_start();
        include $file;
        return ob_get_clean();
    }
}