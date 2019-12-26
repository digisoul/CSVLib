<?php

class CSVReader
{
    const RETURN_TYPE_ARRAY = 1;
    const RETURN_TYPE_ASSOC = 2;
//    const RETURN_TYPE_STRING = 3;
    //states
    const END_OF_FILE = 4;
    //errors
    const ERROR_MISMATCH_COLUMN = 5;
    const ERROR_EMPTY_ROW = 6;
    const ERROR_EMPTY_FILE = 7;
    const ERROR_FILE_IS_NOT_OPENED = 8;

    protected $fileName;
    protected $hasHeader;
    protected $processed_lines;
    /**
     * @var false|resource
     */
    protected $hFile = null;
    /**
     * @var array|false|null
     */
    protected $headers = null;
    protected $minColumnCount = 0;

    protected $exceptionMode = false;

    public function closeOpenedResource()
    {
        if (!is_null($this->hFile)) fclose($this->hFile);
    }

    /**
     * @param $filePath
     * @param bool $hasHeader
     * @param bool $columnCountProtection
     * @param int $minimumColumnCount
     * @throws Exception
     */
    public function openCSV($filePath, $hasHeader = false, $columnCountProtection = false, $minimumColumnCount = 0)
    {
        if (!is_null($this->hFile)) {
            fclose($this->hFile);
            $this->hFile = null;
            $this->processed_lines = 0;
            $this->headers = null;
        }

        $this->fileName = $filePath;
        $this->hFile = fopen($this->fileName, 'r');
        if ($this->hFile === false) {
            throw new Exception("Unable to open CSV File: $filePath");
        }
        $this->processed_lines = 0;
        $this->hasHeader = $hasHeader;
        if ($this->hasHeader) {
            $headers = $this->readline();//read the first line
            if ($headers === self::END_OF_FILE) throw new Exception("Empty CSV File: $filePath", self::ERROR_EMPTY_FILE);
            foreach ($headers as $index => $header) {
                $this->headers[$header] = $index;
            }
//            print_r($this->headers);
        }

        if ($columnCountProtection) {
            if ($minimumColumnCount == 0 && $this->hasHeader == false) {
                $tempRead = $this->readline();
                if ($tempRead === self::END_OF_FILE) throw new Exception("Empty CSV File: $filePath", self::ERROR_EMPTY_FILE);
                //count the number of elements
                $this->minColumnCount = count($tempRead);
                unset($tempRead);
                rewind($this->hFile);
            } elseif ($minimumColumnCount == 0) {
                $this->minColumnCount = count($this->headers);
            } else {
                $this->minColumnCount = $minimumColumnCount;
            }
        } else {
            $this->minColumnCount = -1;
        }
    }

    /**
     * @param int $returnType
     * @param bool $skipEmptyRows
     * @return array|int
     * @throws Exception
     */
    function readline($returnType = CSVReader::RETURN_TYPE_ARRAY, $skipEmptyRows = true)
    {
        if (is_null($this->hFile)) {
            if ($this->exceptionMode) {
                throw new Exception('No csv file is assigned!', self::ERROR_FILE_IS_NOT_OPENED);
            }
        }

        $aLine = null;
        while (true) {
            $aLine = fgetcsv($this->hFile);
            if ($aLine === false) return CSVReader::END_OF_FILE;

            $this->processed_lines++;

            if (is_null($aLine[0]) && !$skipEmptyRows) {
                if ($this->exceptionMode) {
                    throw new Exception('Row is empty', CSVReader::ERROR_EMPTY_ROW);
                }
                return CSVReader::ERROR_EMPTY_ROW;
            } elseif (is_null($aLine[0]) && $skipEmptyRows) {
                continue;
            } else {
                break;
            }
        }
        if ($this->minColumnCount > -1) {
            if (count($aLine) < $this->minColumnCount) {
                if ($this->exceptionMode) {
                    throw new Exception('Miss-match column count @ line # ' . $this->processed_lines, self::ERROR_MISMATCH_COLUMN);
                } else {
                    return self::ERROR_MISMATCH_COLUMN;
                }
            }
        }
        if ($returnType === CSVReader::RETURN_TYPE_ASSOC) {
            $assoc = [];
            foreach ($this->headers as $headerName => $index) {
                $assoc[$headerName] = $aLine[$index];
            }
            return $assoc;
        } else {
            return $aLine;
        }
    }

    /**
     * @return int
     */
    public function getMinColumnCount()
    {
        return $this->minColumnCount;
    }

    /**
     * @return int
     */
    public function getProcessedLines()
    {
        return $this->processed_lines;
    }

    /**
     * @return bool
     */
    public function isExceptionMode()
    {
        return $this->exceptionMode;
    }

    /**
     * @param bool $exceptionMode
     */
    public function setExceptionMode($exceptionMode)
    {
        $this->exceptionMode = $exceptionMode;
    }
}