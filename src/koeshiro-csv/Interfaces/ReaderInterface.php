<?php
namespace Koeshiro\Csv\Interfaces;

interface ReaderInterface extends \Iterator
{

    function __construct(string $FilePath, string $Delimiter = 'AUTO', string $Encoding = 'AUTO');

    public function setEncoding(string $value);

    public function getEncoding();

    public function setDelimeter(string $value);

    public function getDelimeter();

    public function getFilePath();
}