<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%log_daily_url}}`.
 */
class m240321_234833_create_log_daily_url_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%log_daily_url}}', [
            'id' => $this->primaryKey()->unsigned(),
            'date' => $this->date()->notNull(),
            'urlHash' => $this->string(32)->notNull(),
            'total' => $this->integer(10)->unsigned()
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable('{{%log_daily_url}}');
    }
}
