<?php

namespace Platform\Canvas\Tools;

use Platform\Canvas\Tools\Concerns\ResolvesCanvasTeam;
use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Contracts\ToolResult;

abstract class AbstractCanvasTool implements ToolContract, ToolMetadataContract
{
    use ResolvesCanvasTeam;

    abstract public function getName(): string;

    abstract public function getDescription(): string;

    abstract public function getSchema(): array;

    abstract public function getMetadata(): array;

    abstract protected function doExecute(array $arguments, ToolContext $context, int $teamId): ToolResult;

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        try {
            $resolved = $this->resolveTeam($arguments, $context);
            if ($resolved['error']) {
                return $resolved['error'];
            }

            return $this->doExecute($arguments, $context, (int) $resolved['team_id']);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', $this->getErrorPrefix() . $e->getMessage());
        }
    }

    protected function getErrorPrefix(): string
    {
        return 'Fehler: ';
    }

    protected function requireUser(ToolContext $context): ?ToolResult
    {
        if (! $context->user) {
            return ToolResult::error('AUTH_ERROR', 'Kein User im Kontext gefunden.');
        }

        return null;
    }
}
