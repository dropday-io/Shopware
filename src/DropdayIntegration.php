<?php

declare(strict_types=1);

namespace Dropday\DropdayIntegration;

use Shopware\Core\Framework\Plugin;
use Shopware\Core\Framework\Plugin\Context\ActivateContext;
use Shopware\Core\Framework\Plugin\Context\DeactivateContext;
use Shopware\Core\Framework\Plugin\Context\InstallContext;
use Shopware\Core\Framework\Plugin\Context\UninstallContext;
use Shopware\Core\Framework\Plugin\Context\UpdateContext;

/**
 * Dropday Integration Plugin for Shopware 6
 *
 * This plugin integrates your Shopware store with Dropday.io,
 * automatically sending orders to the Dropday fulfillment platform.
 *
 * @package Dropday\DropdayIntegration
 */
class DropdayIntegration extends Plugin
{
    public function install(InstallContext $installContext): void
    {
        parent::install($installContext);
    }

    public function uninstall(UninstallContext $uninstallContext): void
    {
        parent::uninstall($uninstallContext);

        if ($uninstallContext->keepUserData()) {
            return;
        }

        // Clean up custom fields if needed
    }

    public function activate(ActivateContext $activateContext): void
    {
        parent::activate($activateContext);
    }

    public function deactivate(DeactivateContext $deactivateContext): void
    {
        parent::deactivate($deactivateContext);
    }

    public function update(UpdateContext $updateContext): void
    {
        parent::update($updateContext);
    }
}

