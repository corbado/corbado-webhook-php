<?php

namespace Corbado\Webhook\Classes\Models;

class CommonRequest
{
    public string $id;
    public string $projectID;
    public string $action;

    /**
     * @deprecated
     */
    public string $requestID;
}
