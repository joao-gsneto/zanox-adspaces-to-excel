<?php

require './api_client_php-master/ApiClient.php';


$api = ApiClient::factory(PROTOCOL_JSON, VERSION_DEFAULT);
$_id = "@id";
$connectId = '__connect_key';
$secretKey = '__secret_key';

$api->setConnectId($connectId);
$api->setSecretKey($secretKey);
$res = json_decode($api->getProgramApplications($programId = NULL, $adspaceId = NULL, $status = NULL, $page = 0, $items = 50));
$line = array(array(
            'partner',
            'name',
            'title',
            'description',
            'type',
            'category',
            'adrank',
            'format',
            'code',
            'link_ppc',
            'link_ppv'
        ));

foreach ($res->programApplicationItems->programApplicationItem as $item) {
    if ($item->status == 'confirmed') {
        _do_action($item->program);
    }
}

$output_handle = @fopen('php://output', 'w');
ob_start();

foreach ($line as $Result) {
    fputcsv($output_handle, $Result, ';', '"');
}

// Close output file stream
$result_csv = ob_get_contents();
// Já podemos encerrar o buffer e limpar tudo que há nele
ob_end_clean();
fclose($output_handle);

file_put_contents( 'zanox-fdo.csv', mb_convert_encoding( $result_csv, "ISO-8859-1", "UTF-8" ) );

function _do_action($item) {
    global $api, $_id;
    $res = json_decode($api->getProgram($item->$_id));

    if (!isset($res->programItem)) {
        return;
    }
    foreach ($res->programItem as $r) {
        _do_ads($r);
    }
}

function _do_ads($item) {
    global $api, $_id, $line;
    $_dollar = "$";

    $res = json_decode($api->getAdmedia($item->$_id));

    foreach ($res->admediumItems->admediumItem as $r) {
        if (!is_object($r->trackingLinks)) {
            continue;
        }
        
        if( $r->name == '20OFF14' ) {
            print_r($r);
        }
        
        $line[] = array(
            'partner' => $item->name,
            'name' => $r->name,
            'title' => isset( $r->title ) ? $r->title : '',
            'description' => isset( $r->description ) ? $r->description : '',
            'type' => isset( $r->type ) ? $r->type : '',
            'category' => isset( $r->category ) ? $r->category->$_dollar : '',
            'adrank' => $r->adrank,
            'format' => isset( $r->format ) ? $r->format->$_dollar : '',
            'code' => strlen( $r->code ) > 100 ? 'html' : $r->code,
            'link_ppc' => $r->trackingLinks->trackingLink[0]->ppc,
            'link_ppv' => $r->trackingLinks->trackingLink[0]->ppv
        );
    }

}
