<?php

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xls;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class PartnerExportCron
{
    public function __construct()
    {

    }

    /**
     * @param int $partnerId Kód partnera pro generování
     * @param string $exportType Typ exportu, lze nahradit za konstantu etc.
     * @param array $parameters Parametry pro sql
     */
    public function run($partnerId, $exportType, array $parameters = [])
    {
        global $partneri;

        $data = null;

        // -------------------------------------------------------------------
        // získávání dat
        if ($exportType === "smlouvy")
        {
            // sql, případně zavolání datové vrstvy (modelu)
            $sql = dibi::query("SELECT id, name 
                                  FROM
                                        (
                                            (SELECT 1 AS id, 'first' AS name) 
                                            UNION 
                                            (SELECT 2 AS id, 'second' AS name)
                                        ) AS x
                                ORDER BY id ASC");
            // @todo přidat další zpracování parametrů v případě potřeby, omezit na data za poslední den etc.

            $data = $sql->fetchAll();
        }
        // @todo přidat další typy exportů

        // -------------------------------------------------------------------
        // generování souborů
        if ($data !== null)
        {
            foreach ($partneri[$partnerId]["formats"] as $format)
            {
                switch ($format)
                {
                    case "csv":
                        $this->createCsvExport($partnerId, $exportType, $data);
                        break;
                    case "xls":
                        $this->createXlsExport($partnerId, $exportType, $data);
                        break;
                    case "xlsx":
                        $this->createXlsxExport($partnerId, $exportType, $data);
                        break;
                }
            }
        }
    }

    /**
     * Vygeneruje export ve formátu CSV
     * @param int $partnerId
     * @param string $exportType
     * @param array $data Data ze sql dotazu
     */
    private function createCsvExport($partnerId, $exportType, $data)
    {
        $fileName = 'export_'.$partnerId.'_'.$exportType.'.csv';
        $this->removeOldFile($fileName);
        $file = fopen(__DIR__ . '/../../public/exports/'.$fileName, 'w');

        // vytvořit první řádek s názvem sloupců
        $head = $this->getHeaderData($data);
        fputcsv($file, $head);

        // vkládáme obsah
        foreach ($data as $values)
        {
            fputcsv($file, $values->toArray());
        }

        fclose($file);
    }

    /**
     * Vygeneruje export ve formátu XLS
     * @param int $partnerId
     * @param string $exportType
     * @param array $data Data ze sql dotazu
     */
    private function createXlsExport($partnerId, $exportType, $data)
    {
        $fileName = 'export_'.$partnerId.'_'.$exportType.'.xls';
        $this->removeOldFile($fileName);

        // vytvořit první řádek s názvem sloupců
        $processedData = [];
        $processedData[] = $this->getHeaderData($data);

        // vkládáme obsah
        foreach ($data as $values)
        {
            $processedData[] = $values->toArray();
        }

        // uložíme do xlsx
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->fromArray($processedData, NULL, 'A1');
        $writer = new Xls($spreadsheet);
        $writer->save(__DIR__ . '/../../public/exports/'.$fileName);
    }

    /**
     * Vygeneruje export ve formátu XLSX
     * @param int $partnerId
     * @param string $exportType
     * @param array $data Data ze sql dotazu
     */
    private function createXlsxExport($partnerId, $exportType, $data)
    {
        $fileName = 'export_'.$partnerId.'_'.$exportType.'.xlsx';
        $this->removeOldFile($fileName);

        // vytvořit první řádek s názvem sloupců
        $processedData = [];
        $processedData[] = $this->getHeaderData($data);

        // vkládáme obsah
        foreach ($data as $values)
        {
            $processedData[] = $values->toArray();
        }

        // uložíme do xlsx
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->fromArray($processedData, NULL, 'A1');
        $writer = new Xlsx($spreadsheet);
        $writer->save(__DIR__ . '/../../public/exports/'.$fileName);
    }

    /**
     * Vrátí hlavičku pro export - názvy sloupců z DB
     * @param $data
     * @return array
     */
    private function getHeaderData($data)
    {
        $head = [];
        foreach ($data[0] as $key => $value)
        {
            $head[] = $key;
        }

        return $head;
    }

    /**
     * Pokud bude mít partner vždy jen jeden soubor daného typu a formátu, pak staré smažeme
     * @param $fileName
     */
    private function removeOldFile($fileName)
    {
        if (file_exists(__DIR__ . '/../../public/exports/'.$fileName))
        {
            unlink(__DIR__ . '/../../public/exports/'.$fileName);
        }
    }
}