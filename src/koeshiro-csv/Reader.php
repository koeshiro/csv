<?php
namespace Koeshiro\Csv;

use Koeshiro\Csv\Interfaces\ReaderInterface;

/**
 * constructor
 *
 * @param string $FilePath
 * @param string $Delimiter
 * @param string $Encoding
 */
class Reader implements ReaderInterface
{

    /**
     * file name
     *
     * @var string
     */
    protected $FileName = '';

    /**
     * file stream
     *
     * @var \SplFileObject
     */
    protected $FileData = null;

    /**
     * rows from file
     *
     * @var array<string>
     */
    protected $CSVRows = [];

    /**
     * current row number
     *
     * @var integer
     */
    protected $index = 0;

    /**
     * lotal length
     *
     * @var integer
     */
    protected $length = 0;

    /**
     * columns count
     *
     * @var integer
     */
    protected $columns = 0;

    /**
     * data encoding type
     *
     * @var string
     */
    protected $Encoding = '';

    /**
     * delimetr char
     *
     * @var string
     */
    protected $Delimiter = ';';

    /**
     * valid delimetr chars for auto detect
     *
     * @var array<string>
     */
    protected $validDelimiters = array(
        ',',
        ';',
        "\t",
        '|',
        ':'
    );

    /**
     * constructor
     *
     * @param string $FilePath
     * @param string $Delimiter
     * @param string $Encoding
     */
    public function __construct(string $FilePath, string $Delimiter = 'AUTO', string $Encoding = 'AUTO')
    {
        if (! file_exists($FilePath)) {
            throw new \Exception("File $FilePath is not exists");
        }
        $this->FileName = $FilePath;
        $this->FileData = new \SplFileObject($FilePath, 'r');
        $this->CSVRows = [];
        $index = 0;
        while ($index < 200) {
            $this->CSVRows[] = $this->FileData->fgets();
            $index ++;
        }
        $this->FileData->seek(0);
        $this->setDelimeter($Delimiter);
        $this->setEncoding($Encoding);
    }

    public function getFilePath()
    {
        return $this->FileName;
    }

    public function getEncoding()
    {
        return $this->Encoding;
    }

    public function next()
    {
        $this->index ++;
    }

    public function valid()
    {
        return ! $this->FileData->eof();
    }

    public function current()
    {
        $Data = $this->FileData->fgetcsv($this->Delimiter, '"');
        $Result = [];
        if (! is_array($Data) || count($Data) === 0) {
            return $Result;
        }
        foreach ($Data as $column) {
            if ($this->Encoding != '' && $this->Encoding != 'AUTO') {
                $strRow = mb_convert_encoding($column, "UTF-8", $this->Encoding);
                if (strlen($strRow) < strlen($column)) {
                    $strRow = $column;
                }
            } else {
                $strRow = $column;
            }
            $Result[] = $strRow;
        }
        return $Result;
    }

    public function setEncoding(string $value)
    {
        if ($value === '' || $value === 'AUTO') {
            $Encoding = $this->DetectEncoding(199);
            if ((is_string($Encoding) && $Encoding == '') || ! is_string($Encoding)) {
                throw new \Exception("Cant detect encoding ($Encoding)");
            }
            $this->setEncoding($Encoding);
        } else {
            $this->Encoding = $value;
        }
    }

    public function rewind()
    {
        $this->index = 0;
        $this->FileData->seek(0);
    }

    public function getDelimeter()
    {
        return $this->Delimiter;
    }

    public function setDelimeter(string $value)
    {
        if ($value === '' || $value === 'AUTO') {
            $Delimiter = $this->DetectDelimiter(199);
            if (! is_string($Delimiter) || (is_string($Delimiter) && $Delimiter === '')) {
                throw new \Exception("Cant detect delimiter ($Delimiter) on file $this->FileName");
            }
            $this->setDelimeter($Delimiter);
        } else {
            $this->Delimiter = $value;
        }
    }

    public function key()
    {
        return $this->index;
    }

    /**
     * Function for detect encoding
     *
     * @param int $depth
     * @return string|number
     */
    public function DetectEncoding(int $depth = 100)
    {
        $index = 0;
        $arResults = [];
        while ($index < $depth) {
            if (array_key_exists($index, $this->CSVRows)) {
                $Encoding = $this->DetectLineEncoding($this->CSVRows[$index], strlen($this->CSVRows[$index]));
                if (array_key_exists($Encoding, $arResults)) {
                    $arResults[$Encoding] = $arResults[$Encoding] + 1;
                } else {
                    $arResults[$Encoding] = 1;
                }
            } else {
                break;
            }
            $index ++;
        }
        $max = 0;
        $encoding = 'UTF-8';
        foreach ($arResults as $PossibleEncoding => $count) {
            if ($count > $max) {
                $max = $count;
                $encoding = $PossibleEncoding;
            }
        }
        return $encoding;
    }

    /**
     * Function for detect string encoding.
     * (Source http://forum.dklab.ru/viewtopic.php?t=37833&postdays=0&postorder=asc&highlight=)
     * Alternative method https://github.com/anton-siardziuk/detect_encoding
     *
     * @link http://forum.dklab.ru/viewtopic.php?t=37833&postdays=0&postorder=asc&highlight=
     * @param string $string
     *            - string what of need detect encoding
     * @param int $pattern_size
     *            - length of testing charset
     * @return string - name of encoding one of ('cp1251', 'utf-8', 'ascii', '855', 'KOI8R', 'ISO-IR-111', 'CP866', 'KOI8U')
     */
    public function DetectLineEncoding(string $string, int $pattern_size = 100)
    {
        $list = array(
            'CP1251',
            'UTF-8',
            'ASCII',
            'KOI8R',
            'CP866'
        );
        $testString = str_replace(';', '', $string);
        $testString = str_replace(',', '', $testString);
        $testString = str_replace('"', '', $testString);
        $testString = str_replace('\'', '', $testString);
        $testString = str_replace('.', '', $testString);
        $testString = str_replace('-', '', $testString);
        $testString = str_replace('_', '', $testString);
        $testString = str_replace('+', '', $testString);
        $testString = str_replace('=', '', $testString);
        $c = strlen($testString);
        if ($c > $pattern_size) {
            $testString = substr($testString, floor(($c - $pattern_size) / 2), $pattern_size);
            $c = $pattern_size;
        }
        // national chars
        $reg1 = '/(\xE0|\xE5|\xE8|\xEE|\xF3|\xFB|\xFD|\xFE|\xFF)/i';
        $reg2 = '/(\xE1|\xE2|\xE3|\xE4|\xE6|\xE7|\xE9|\xEA|\xEB|\xEC|\xED|\xEF|\xF0|\xF1|\xF2|\xF4|\xF5|\xF6|\xF7|\xF8|\xF9|\xFA|\xFC)/i';

        $mk = 10000;
        $enc = 'UTF-8';
        foreach ($list as $item) {
            $sample1 = @iconv($item, 'CP1251', $testString);
            $gl = preg_match_all($reg1, $sample1);
            $sl = preg_match_all($reg2, $sample1);
            if (! $gl || ! $sl) {
                continue;
            }
            $k = abs(3 - ($sl / $gl));
            $k += $c - $gl - $sl;
            if ($k < $mk) {
                $enc = $item;
                $mk = $k;
            }
        }
        if ($enc) {}
        return $enc;
    }

    /**
     * function for detect delimetr
     *
     * @param int $depth
     * @return mixed
     */
    public function DetectDelimiter(int $depth = 100)
    {
        $chars = $this->validDelimiters;
        try {
            $arFrequency = array();
            $Rows = $this->CSVRows;
            foreach ($Rows as $index => $value) {
                foreach ($chars as $char) {
                    if (strlen($value) > 0) {
                        $cache = explode($char, $value);
                        // $cache=str_getcsv(str_replace("\\\"".$char,'"'.$char,$value),$char,'"',"\\");
                        if ($cache[0] != $value) {
                            $arFrequency[$char][$index] = count($cache);
                        }
                    }
                }
                if ($index == $depth - 1) {
                    break;
                }
            }
        } catch (\Exception $E) {
            throw $E;
        }

        $arMatches = array();
        foreach ($arFrequency as $char => $values) {
            if (is_array($values) && count($values) > 0) {
                foreach ($values as $value) {
                    if (array_key_exists($char, $arMatches)) {
                        $arMatches[$char] = $arMatches[$char] + 1;
                    } else {
                        $arMatches[$char] = 0;
                    }
                }
            }
        }
        if (count($arMatches) === 0) {
            return null;
        } else {
            return array_search(max($arMatches), $arMatches);
        }
    }

    /**
     * help function
     * checks the file for metadata compliance
     *
     * @param string $FilePath
     * @return boolean
     */
    public static function isCsv(string $FilePath)
    {
        $MimeType = mime_content_type($FilePath);
        $allowedMimeTypes = [
            'text/plain',
            'text/csv',
            'text/x-csv',
            'application/csv',
            'application/x-csv',
            'text/comma-separated-values',
            'text/x-comma-separated-values',
            'text/tab-separated-values'
        ];
        if (in_array($MimeType, $allowedMimeTypes)) {
            return true;
        } else {
            return false;
        }
    }
}

