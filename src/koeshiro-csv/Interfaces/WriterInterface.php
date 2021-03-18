<?php
namespace Koeshiro\Csv\Interfaces;

interface WriterInterface
{

    function __construct(string $FileName, string $Delimiter = ',');

    public function write(array $data);
}