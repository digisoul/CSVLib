<?php
include 'CSVReader.php';
$csv = new CSVReader;
$csv->setExceptionMode(true);
try {
    $csv->openCSV(
        "sample.csv",
        true,
        true,
        4
    );

    while ($row = $csv->readline(CSVReader::RETURN_TYPE_ASSOC)) {
        print_r($row);
    }
} catch (Exception $exception) {
    echo $exception->getMessage();
}
