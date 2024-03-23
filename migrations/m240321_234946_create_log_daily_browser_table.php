<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%log_daily_browser}}`.
 */
class m240321_234946_create_log_daily_browser_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%log_daily_browser}}', [
            'id' => $this->primaryKey()->unsigned(),
            'date' => $this->date()->notNull(),
            'browser' => $this->string(50)->notNull(),
            'total' => $this->integer(10)->unsigned()
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable('{{%log_daily_browser}}');
    }
}
