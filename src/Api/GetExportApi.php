<?php

class GetExportApi
{
    public function __construct()
    {

    }

    /**
     * API metoda k zavolání -> připraví export
     */
    public function getExport()
    {
        global $partneri;

        // ----------------------------------------------------------------
        // kontrola tokenu & ip

        $token = $_GET["token"];
        $ip = $_SERVER["REMOTE_ADDR"];

        // zalogovat požadavek
        $logData = [
            "token" => $token,
            "ip" => $ip
        ];

        dibi::query("INSERT INTO log_export_api", $logData);
        $logId = dibi::getInsertId();

        // ověření dat
        $user = $this->findUser($token, $ip);

        if ($user === null)
        {
            header("HTTP/1.1 401 Unauthorized");
            exit('Unauthorized - neplatny uzivatel');
        }

        // ----------------------------------------------------------------
        // validace volané akce & formátu

        $action = $_GET["action"];
        $format = $_GET["format"];

        // zalogovat požadavek
        dibi::query("UPDATE log_export_api SET action = ?", $action, ", format = ?", $format ,"WHERE id = ?", $logId);

        if (!in_array($action, $partneri[$user]["actions"]) || !in_array($format, $partneri[$user]["formats"]))
        {
            header("HTTP/1.1 401 Unauthorized");
            exit('Unauthorized - neplatna akce/format');
        }

        // ------------------------------------------------------------------------
        // uživatel i akce jsou validní: stažení souboru

        $fileName = "export_".$user."_".$action.".".$format;
        $filePath = __DIR__ . "/../../public/exports/".$fileName;
        $fileHash = hash_file("md5", $filePath);    // případně jiný algoritmus

        // zalogovat požadavek
        dibi::query("UPDATE log_export_api SET downloaded = 1, file_hash = ?", $fileHash ,"WHERE id = ?", $logId);

        http_response_code(200);
        header("Content-Type: text/csv; charset=UTF-8");
        header("Content-Length:".filesize($filePath));
        header("Content-Disposition: attachment; filename=".$fileName);

        readfile($filePath);
    }

    /**
     * Prohledá parnery a vrátí ID partnera podle tokenu a ip adresy
     * @param $token
     * @param $ip
     * @return int|null id partnera
     */
    private function findUser($token, $ip)
    {
        global $partneri;

        foreach ($partneri as $partnerId => $partnerData)
        {
            if ($partnerData["token"] == $token && in_array($ip, $partnerData["ips"]))
            {
                return $partnerId;
            }
        }

        // nenalezeno
        return null;
    }
}