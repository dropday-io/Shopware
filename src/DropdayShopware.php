<?php declare(strict_types=1);

namespace Dropday\Shopware;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Plugin;
use Shopware\Core\Framework\Plugin\Context\InstallContext;
use Shopware\Core\Framework\Plugin\Context\UninstallContext;
use Shopware\Core\System\CustomField\CustomFieldTypes;

class DropdayShopware extends Plugin
{
    private const CUSTOM_FIELD_SET_NAME = 'dropday_order_data';

    public function install(InstallContext $installContext): void
    {
        parent::install($installContext);

        $this->createCustomFieldSet($installContext->getContext());
    }

    public function uninstall(UninstallContext $uninstallContext): void
    {
        parent::uninstall($uninstallContext);

        if ($uninstallContext->keepUserData()) {
            return;
        }

        $this->removeCustomFieldSet($uninstallContext->getContext());
    }

    private function createCustomFieldSet(Context $context): void
    {
        $this->customFieldSetRepository()->upsert([[
            'name' => self::CUSTOM_FIELD_SET_NAME,
            'config' => [
                'label' => [
                    'en-GB' => 'Dropday',
                    'de-DE' => 'Dropday',
                ],
            ],
            'relations' => [[
                'entityName' => 'order',
            ]],
            'customFields' => [[
                'name' => 'dropday_reference',
                'type' => CustomFieldTypes::TEXT,
                'config' => [
                    'label' => [
                        'en-GB' => 'Dropday reference',
                        'de-DE' => 'Dropday-Referenz',
                    ],
                    'customFieldType' => 'text',
                    'customFieldPosition' => 1,
                ],
            ]],
        ]], $context);
    }

    private function removeCustomFieldSet(Context $context): void
    {
        $repository = $this->customFieldSetRepository();

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('name', self::CUSTOM_FIELD_SET_NAME));

        $ids = $repository->searchIds($criteria, $context)->getIds();

        if ($ids === []) {
            return;
        }

        $repository->delete(array_map(static fn (string $id) => ['id' => $id], $ids), $context);
    }

    private function customFieldSetRepository(): EntityRepository
    {
        /** @var EntityRepository $repository */
        $repository = $this->container->get('custom_field_set.repository');

        return $repository;
    }
}
