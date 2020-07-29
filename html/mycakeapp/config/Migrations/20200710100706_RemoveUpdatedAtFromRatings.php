<?php
use Migrations\AbstractMigration;

class RemoveUpdatedAtFromRatings extends AbstractMigration
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
        $table = $this->table('ratings');
        $table->removeColumn('updated_at');
        $table->update();
    }

    public function down()
    {
        $table->addColumn('updated', 'datetime', [
            'default' => null,
            'null' => false,
        ]);
        $table->create();
    }
}
