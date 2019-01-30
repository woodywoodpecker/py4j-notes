<?php
/**
 * Created by PhpStorm.
 * User: root
 * Date: 8/30/18
 * Time: 6:18 PM
 */

namespace Rri\Bundle\ProductBundle\Migrations\Schema\v1_0;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\EntityBundle\EntityConfig\DatagridScope;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendExtension;
use Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendExtensionAwareInterface;

class RriProductImageZoneMigration implements Migration,ExtendExtensionAwareInterface
{

    /**
     * @var ExtendExtension
     */
    protected $extendExtension;

    /**
     * Sets the ExtendExtension
     *
     * @param ExtendExtension $extendExtension
     */
    public function setExtendExtension(ExtendExtension $extendExtension)
    {
        $this->extendExtension = $extendExtension;
    }

    /**
     * Modifies the given schema to apply necessary changes of a database
     * The given query bag can be used to apply additional SQL queries before and after schema changes
     *
     * @param Schema $schema
     * @param QueryBag $queries
     * @return void
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $table = $schema->createTable('rri_product_image_zone');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('name', 'string',
            [
                'length' => 255,
                'oro_options' => [
                    'extend'   => ['owner' => ExtendScope::OWNER_CUSTOM],
                    'datagrid' => ['is_visible' => DatagridScope::IS_VISIBLE_FALSE],
                    'merge'    => ['display' => true],
                ]]);
        $table->addColumn('comment', 'text',
            [
                'oro_options' => [
                    'extend'   => ['owner' => ExtendScope::OWNER_CUSTOM],
                    'datagrid' => ['is_visible' => DatagridScope::IS_VISIBLE_FALSE],
                    'merge'    => ['display' => true],
                ]
            ]);
        $table->addColumn('rotation', 'integer', [
            'oro_options' => [
                'extend'   => ['owner' => ExtendScope::OWNER_CUSTOM],
                'datagrid' => ['is_visible' => DatagridScope::IS_VISIBLE_FALSE],
                'merge'    => ['display' => true],
            ]
        ]);
        $table->addColumn('cx', 'integer', [
            'oro_options' => [
                'extend'   => ['owner' => ExtendScope::OWNER_CUSTOM],
                'datagrid' => ['is_visible' => DatagridScope::IS_VISIBLE_FALSE],
                'merge'    => ['display' => true],
            ]
        ]);
        $table->addColumn('cy', 'integer', [
            'oro_options' => [
                'extend'   => ['owner' => ExtendScope::OWNER_CUSTOM],
                'datagrid' => ['is_visible' => DatagridScope::IS_VISIBLE_FALSE],
                'merge'    => ['display' => true],
            ]
        ]);
        $table->addColumn('height', 'integer', [
            'oro_options' => [
                'extend'   => ['owner' => ExtendScope::OWNER_CUSTOM],
                'datagrid' => ['is_visible' => DatagridScope::IS_VISIBLE_FALSE],
                'merge'    => ['display' => true],
            ]
        ]);
        $table->addColumn('width', 'integer', [
            'oro_options' => [
                'extend'   => ['owner' => ExtendScope::OWNER_CUSTOM],
                'datagrid' => ['is_visible' => DatagridScope::IS_VISIBLE_FALSE],
                'merge'    => ['display' => true],
            ]
        ]);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['name'], 'rri_product_image_zone_name_idx', []);

        // =============================== add relation ====================================
        $this->extendExtension->addManyToOneRelation(
            $schema,
            $schema->getTable('rri_product_image_zone'),
            'productImage',
            $schema->getTable('oro_product_image'),
            'id',
            ['extend' => ['owner' => ExtendScope::OWNER_CUSTOM, 'without_default' => true]]
        );


        $this->extendExtension->addManyToOneInverseRelation(
            $schema,
            $schema->getTable('rri_product_image_zone'),
            'productImage',
            $schema->getTable('oro_product_image'),
            'imageZones',
            $schema->getTable('oro_product_image')->getPrimaryKeyColumns(),
            $schema->getTable('oro_product_image')->getPrimaryKeyColumns(),
            $schema->getTable('oro_product_image')->getPrimaryKeyColumns(),
            ['extend' => ['owner' => ExtendScope::OWNER_CUSTOM, 'without_default' => true]]
        );
    }
}
