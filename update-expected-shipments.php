#!/usr/bin/env php
<?php
//echo "Start:".date("Y-m-d H:i:s ")."\n";
require("set-doc-root.php");

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");

$shortopts  = "";
$shortopts .= "m::"; // код модификации, для которой обновляем срок
$shortopts .= "e::"; // ожидаемые поставки

$options = getopt($shortopts);
// var_dump($options);

/**/
if ($options["m"] == "") 
{
        fwrite(STDERR, $argv[0]." ERROR: parameter -m modification_code is required.\n" );
        exit(1);
}

$expected_shipments = '';
if ( $options["e"] != "" ) 
{
  //echo "options_e=". $options["e"] ."\n";
  $expected_shipments = $options["e"];
}

$mod_id = $options["m"];
$cod_min = (double) $mod_id - 0.1  ;
$cod_max = (double) $mod_id + 0.1  ;
//echo "cod_min=". (double)$cod_min ."\n";
//echo "cod_max=". (double)$cod_max ."\n";
/**/

$arFilter = array(
    "IBLOCK_ID" => "30",
    //"PROPERTY_COD" => intval($options["m"]),
    //"PROPERTY_COD" => round((double)$mod_id, 0),
    "><PROPERTY_COD" => array($cod_min, $cod_max),
    "ACTIVE" => "Y",
);

//$arSelect = Array("ID", "NAME", "PROPERTY_*");
$arSelect = Array();
$rsItems = CIBlockElement::GetList(Array("SORT" => "ASC"), $arFilter, false, false, $arSelect);
//echo "ib30:".date("Y-m-d H:i:s ")."\n";
$notFound = True;
while($ob = $rsItems->GetNextElement())
{
    $arFields = $ob->GetFields();
    $notFound = False;
    $ib30_id = $arFields["ID"];
    /**
    echo "id=". $ib30_id
        . " name=" . $arFields["NAME"]
        . " section_id=" . $arFields["IBLOCK_SECTION_ID"]
        . " xml_id=". $arFields["XML_ID"]
        . "\n";
    **/

    $arFilter29 = array(
        "IBLOCK_ID" => "29",
        "PROPERTY_MOD_SECTION_ID" => $arFields["IBLOCK_SECTION_ID"],
        "ACTIVE" => "Y",
    );
    $notFound29 = True;
    $rsItems29 = CIBlockElement::GetList(Array("SORT" => "ASC"), $arFilter29, false, false, array() );
    //echo "ib29:".date("Y-m-d H:i:s ")."\n";
    while($ob29 = $rsItems29->GetNextElement())
    {
        $notFound29 = False;
        $arFields29 = $ob29->GetFields();

        $el30 = new CIBlockElement;
        $el30->SetPropertyValues($ib30_id, 30, $expected_shipments, "EXPECTED_SHIPMENTS");

        $res = $el30->Update($ib30_id, array("MODIFIED_BY" => 6938));
        if (! ($res) ) {fwrite(STDERR, "Update ib30 failed: ". $el30->LAST_ERROR . "\n" );}
        //echo "after Update ib30:".date("Y-m-d H:i:s ")."\n";
    }
    if ($notFound29) {fwrite(STDERR, "Device with Active=Y and PROPERTY_MOD_SECTION_ID=[". $arFields["IBLOCK_SECTION_ID"] . "] not found\n");}

}

if ($notFound) {fwrite(STDERR, "Modification_code=[". $options["m"] . "] not found\n");}

?>
