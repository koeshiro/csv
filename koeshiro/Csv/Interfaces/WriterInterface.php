<?php
namespace koeshiro\Csv\Interfaces;

interface WriterInterface
{

    function __construct(string $FileName, string $Delimiter = ',');

    public function write(array $data);
}