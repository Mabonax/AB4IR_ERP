<?php

namespace App\Domains\Intelligence\Services;

use App\Domains\Intelligence\Models\PromptTemplate;
use InvalidArgumentException;

class PromptTemplateRenderer
{
    public function render(PromptTemplate $template, array $variables = []): array
    {
        foreach (array_keys((array) ($template->variables_schema['properties'] ?? [])) as $requiredKey) {
            if (! array_key_exists($requiredKey, $variables)) {
                throw new InvalidArgumentException("Missing prompt variable [{$requiredKey}].");
            }
        }

        return [
            'system' => $this->replaceVariables($template->system_prompt ?? '', $variables),
            'developer' => $this->replaceVariables($template->developer_prompt ?? '', $variables),
            'user' => $this->replaceVariables($template->user_prompt_template ?? '', $variables),
        ];
    }

    protected function replaceVariables(string $content, array $variables): string
    {
        foreach ($variables as $key => $value) {
            $content = str_replace('{{'.$key.'}}', (string) $value, $content);
        }

        return $content;
    }
}
