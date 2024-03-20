<?php

namespace app\components;

use yii\base\Component;
use Exception;

class NginxLogParser extends Component
{
    private const CAPTURE_VALUE = '/^(\w*)(.*?)$/';
    private array $identifiers = [];
    private string $pattern = '';

    public function __construct($config = [])
    {
        $this->pattern = $this->pattern();

        parent::__construct($config);
    }

    /**
     * @throws \Exception
     */
    public function parse(string $line): object
    {
        preg_match($this->pattern, $line, $values);
        array_shift($values);

        $identifiers = $this->getIdentifiers();

        if (count($identifiers) !== count($values)) {
            throw new Exception(sprintf('Line `%s` does not match `%s`', $line, $this->pattern));
        }

        return (object) array_combine($identifiers, $values);
    }

    private function pattern(): string
    {
        $logFormat = '$ip - $remote_user [$time_local] "$request" $status $bytes_sent "$http_referer" "$http_user_agent"';

        $identifiers = [];

        $pieces = explode('$', $logFormat);
        $delimiters = [];

        $delimiters[] = array_shift($pieces);

        foreach ($pieces as $piece) {
            preg_match(self::CAPTURE_VALUE, $piece, $token);

            $this->identifiers[] = $token[1];
            $delimiters[]        = preg_quote($token[2]);
        }

        return sprintf('/^%s$/', implode('(.+?)', $delimiters));
    }

    public function getIdentifiers(): array
    {
        return $this->identifiers;
    }
}