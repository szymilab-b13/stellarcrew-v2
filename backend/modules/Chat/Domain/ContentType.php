<?php

declare(strict_types=1);

namespace Modules\Chat\Domain;

enum ContentType: string
{
    case Text = 'text';
    case Markdown = 'markdown';
    case TaskDispatch = 'task_dispatch';
    case ToolRequest = 'tool_request';
    case Forwarded = 'forwarded';
    case Notification = 'notification';
}
