<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%log}}`.
 */
class m240319_081308_create_log_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%log}}', [
            'id' => $this->primaryKey()->unsigned(),
            'ip' => $this->string(50)->notNull(),
            'url' => $this->string(1000)->notNull(),
            'userAgent' => $this->string(500)->notNull(),
            'os' => $this->string(50),
            'arch' => $this->string(3),
            'browser' => $this->string(50),
            'date' => $this->date()->notNull(),
            'time' => $this->time()->notNull(),
        ]);

        $this->createIndex('idx_dateTime', 'log', 'date');
        $this->createIndex('idx_browser', 'log', 'browser');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropIndex('idx_date', 'log');
        $this->dropIndex('idx_browser', 'log');

        $this->dropTable('{{%log}}');
    }
}
