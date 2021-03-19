<?php
// index určený pro test aplikace

include __DIR__ . "/config/autoload.php";
include __DIR__ . "/src/Cron/PartnerExportCron.php";
include __DIR__ . "/src/Api/GetExportApi.php";

// vytvořit nový export
/*
$partnerExportCron = new PartnerExportCron();
$partnerExportCron->run(1, "smlouvy");
*/

// test stažení z api
$exportApi = new GetExportApi();
$exportApi->getExport();