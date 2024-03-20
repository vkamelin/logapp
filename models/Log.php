<?php

namespace app\models;

use yii\db\ActiveRecord;

/**
 * @property integer $id
 * @property string $ip
 * @property string $url
 * @property string $userAgent
 * @property string $os
 * @property string $arch
 * @property string $browser
 * @property string $dateTime
 */
class Log extends ActiveRecord
{

    /**
     * {@inheritdoc}
     * @return string
     */
    public static function tableName(): string
    {
        return 'log';
    }

    /**
     * {@inheritdoc}
     * @return array
     */
    public function attributeLabels(): array
    {
        return [
            'id' => 'ID',
            'ip' => 'IP',
            'url' => 'UEL',
            'userAgent' => 'User Agent',
            'os' => 'Operating System',
            'arch' => 'Architecture',
            'browser' => 'Browser',
            'date' => 'Date',
            'time' => 'Time',
        ];
    }

    /**
     * {@inheritdoc}
     * @return array
     */
    public function rules(): array
    {
        return [
            // name, email, subject и body атрибуты обязательны
            [['ip', 'url', 'userAgent', 'dateTime'], 'required'],
            ['ip', 'string', 'max' => 50],
            ['ip', 'ip'],
            ['url', 'string', 'max' => 1000],
            ['userAgent', 'string', 'max' => 200],
            [['os', 'browser'], 'string', 'max' => 20],
            ['arch', 'string', 'max' => 3],
            ['arch', 'in', 'range' => ['x86', 'x64']],
            ['date', 'date'],
            ['time', 'time'],
        ];
    }

}