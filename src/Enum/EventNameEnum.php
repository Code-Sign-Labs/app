<?php

namespace App\Enum;

enum EventNameEnum: string
{
    case USER_AUTH_SUCCESS = 'user.auth.success'; // done
    case USER_AUTH_FAILURE = 'user.auth.failure'; // done
    case USER_EDIT_SUCCESS = 'user.edit.success'; // done
    case USER_EDIT_FAILURE = 'user.edit.failure'; // done
    case USER_DELETE_SUCCESS = 'user.delete.success'; // done
    case USER_DELETE_FAILURE = 'user.delete.failure'; // done
    case USER_CREATE_SUCCESS = 'user.create.success'; // done
    case USER_CREATE_FAILURE = 'user.create.failure'; // done
    case LICENSE_CREATE_SUCCESS = 'license.create.success'; // done
    case LICENSE_CREATE_FAILURE = 'license.create.failure'; // done
    case LICENSE_DELETE_SUCCESS = "license.delete.success"; // TODO: Add trigger after implementing license deletion
    case LICENSE_DELETE_FAILURE = "license.delete.failure"; // TODO: Add trigger after implementing license deletion
    case LICENSE_STATUS_SUSPEND = 'license.status.suspend'; // done
    case LICENSE_STATUS_UNSUSPEND = 'license.status.unsuspend'; // done
    case LICENSE_STATUS_REVOKE = 'license.status.revoke'; // done
    case LICENSE_STATUS_UNREVOKE = 'license.status.unrevoke'; // done
    case LICENSE_ACTIVATION_SUCCESS = 'license.activation.success'; // TODO: Add trigger after implementing API
    case LICENSE_ACTIVATION_FAILURE = 'license.activation.failure'; // TODO: Add trigger after implementing API
    case LICENSE_ACTIVATION_REMOVE = 'license.activation.remove'; // TODO: Add trigger after implementing API
    case LICENSE_AUTHORIZATION_SUCCESS = 'license.authorization.success'; // TODO: Add trigger after implementing API
    case LICENSE_AUTHORIZATION_FAILURE = "license.authorization.failure"; // TODO: Add trigger after implementing API
    case PRODUCT_CREATE_SUCCESS = 'product.create.success'; // done
    case PRODUCT_CREATE_FAILURE = 'product.create.failure'; // done
    case PRODUCT_EDIT_SUCCESS = 'product.edit.success'; // done
    case PRODUCT_EDIT_FAILURE = 'product.edit.failure'; // done
    case PRODUCT_DELETE_SUCCESS = 'product.delete.success'; // TODO: Add trigger after implementing product deletion
    case PRODUCT_DELETE_FAILURE = 'product.delete.failure'; // TODO: Add trigger after implementing product deletion
    case PRODUCT_ARCHIVE_SUCCESS = 'product.archive.success'; // TODO: Add trigger after implementing product archiving
    case PRODUCT_ARCHIVE_FAILURE = 'product.archive.failure'; // TODO: Add trigger after implementing product archiving
    case SETTINGS_UPDATE_SUCCESS = 'settings.update.success'; // done
    case SETTINGS_UPDATE_FAILURE = 'settings.update.failure'; // done
    case WEBHOOK_CREATE_SUCCESS = 'webhook.create.success'; // done
    case WEBHOOK_CREATE_FAILURE = 'webhook.create.failure'; // done
    case WEBHOOK_DELETE_SUCCESS = 'webhook.delete.success'; // done
    case WEBHOOK_DELETE_FAILURE = 'webhook.delete.failure'; // done
}