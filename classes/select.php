<?php
/**
 * Criado com PhpStorm.
 * Autor: Ello Oliveira
 * Data: 14/12/2017
 * Hora: 17:01
 */
require_once("../../../config.php");
require_once($CFG->dirroot . '/report/graphic/classes/renderable.php');
global $PAGE;
echo header('Content-type: application/json');

$categoryid = optional_param('categoryid',  0,  PARAM_INT);
if($categoryid){

    $renderable = new report_graphic_renderable();
    $renderer = $PAGE->get_renderer('report_graphic');

    $courses = $renderable->get_categories_list($categoryid, false);

    echo json_encode($courses);
}
else{
    echo json_encode($categoryid);
}