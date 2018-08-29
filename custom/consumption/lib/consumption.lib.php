<?php
function ConsumptionAdminPrepareHead()
{
    global $langs, $conf;

    $langs->load("consumption@consumption");

    $h = 0;
    $head = array();

    $head[$h][0] = dol_buildpath("/consumption/admin/setup.php", 1);
    $head[$h][1] = $langs->trans("Parameters");
    $head[$h][2] = 'settings';
    $h++;

    complete_head_from_modules($conf, $langs, $object, $head, $h, 'consumption');

    return $head;
}
?>