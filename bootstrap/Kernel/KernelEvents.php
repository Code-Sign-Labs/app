<?php
declare(strict_types=1);
namespace Framework\Kernel;

final class KernelEvents
{
    public const BOOT = 'kernel.boot';

    public const CONFIGURE_ERROR_HANDLING = 'kernel.configure_error_handling';
    public const CONFIGURE_DEBUG = 'kernel.configure_debug';
    public const CONFIGURE_VIEW_ENGINE = 'kernel.configure_view_engine';
    public const CONFIGURE_ORM = 'kernel.configure_orm';
    public const CONFIGURE_ADDONS = 'kernel.configure_addons';
    public const CONFIGURE_ROUTER = 'kernel.configure_router';
    public const CONFIGURE_REDIS = 'kernel.configure_redis';

    public const HANDLE_REQUEST = 'kernel.handle_request';
    public const HANDLE_TESTS = 'kernel.handle_tests';
}

