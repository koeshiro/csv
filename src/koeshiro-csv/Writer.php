<?php
namespace Koeshiro\Csv;

use Koeshiro\Csv\Interfaces\WriterInterface;

/**
 *
 * @author rustam
 *
 */
class Writer implements WriterInterface
{

    /**
     *
     * @var \SplFileObject
     */
    protected $File = null;

    protected $Delimiter = ',';

    /**
     * (non-PHPdoc)
     *
     * @see \Koeshiro\Csv\Interfaces\WriterInterface::__construct()
     */
    public function __construct(string $FilePath, string $Delimiter = ',')
    {
        $this->File = new \SplFileObject($FilePath, 'w');
        $this->Delimiter = $Delimiter;
    }

    /**
     * (non-PHPdoc)
     *
     * @see \Koeshiro\Csv\Interfaces\WriterInterface::write()
     */
    public function write(array $data)
    {
        return $this->File->fputcsv($data, $this->Delimiter);
    }
}

