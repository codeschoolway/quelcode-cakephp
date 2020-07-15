<?php
use Migrations\AbstractMigration;

class RemoveCreatedAtFromDeliveries extends AbstractMigration
{
    /**
     * Change Method.
     *
     * More information on this method is available here:
     * https://book.cakephp.org/phinx/0/en/migrations.html#the-change-method
     * @return void
     */
    public function up()
    {
        $table = $this->table('deliveries');
        $table->removeColumn('created_at');
        $table->update();
    }

    public function down() {
        $table->addColumn('created_at', 'datetime', [
            'default' => null,
            'null' => false,
        ]);
        $table->create();
    }
}
