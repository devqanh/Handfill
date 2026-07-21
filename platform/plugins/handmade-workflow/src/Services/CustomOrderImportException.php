<?php

namespace Botble\HandmadeWorkflow\Services;

use RuntimeException;

/**
 * A file the customer can fix themselves — the message is written for them and is
 * shown as-is, unlike an unexpected failure which is logged and hidden.
 */
class CustomOrderImportException extends RuntimeException
{
}
