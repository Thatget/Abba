<?php

namespace MW\MultiwarehouseShipping\Setup;

use Magento\Framework\DB\Ddl\Table;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\Setup\UpgradeSchemaInterface;

/**
 * Upgrade the Cms module DB scheme
 */
class UpgradeSchema implements UpgradeSchemaInterface
{
    /**
     * {@inheritdoc}
     */
    public function upgrade(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        if (version_compare($context->getVersion(), '1.0.1', '<')) {
            $setup->startSetup();
            $setup->getConnection()->addColumn(
                $setup->getTable('quote'),
                "multiwarehouse_shipping",
                [
                    'type' => Table::TYPE_TEXT,
                    'length' => null,
                    'nullable' => true,
                    'default'  => null,
                    'comment'  => 'Warehouse Shipping'
                ]
            );
            $setup->getConnection()->addColumn(
                $setup->getTable('sales_order'),
                "multiwarehouse_shipping",
                [
                    'type' => Table::TYPE_TEXT,
                    'length' => null,
                    'nullable' => true,
                    'default'  => null,
                    'comment'  => 'Warehouse Shipping'
                ]
            );
            $setup->endSetup();
        }
    }
}
