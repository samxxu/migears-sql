<?php

declare(strict_types=1);

namespace MiGears\Sql\Exception;

class RecordNotFoundException extends SqlException
{
    public function __construct(string $table = '', string|int $id = '')
    {
        $message = match (true) {
            $table !== '' && $id !== '' => "Record not found in table '{$table}' with id '{$id}'",
            $table !== '' => "Record not found in table '{$table}'",
            default => 'Record not found',
        };
        parent::__construct($message);
    }
}
