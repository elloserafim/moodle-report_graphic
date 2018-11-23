<?php
/**
 * Criado com PhpStorm.
 * Autor: Ello Oliveira
 * Data: 26/12/2017
 * Hora: 17:38
 */

require_once("../../../config.php");
require_once($CFG->dirroot . '/report/graphic/classes/renderable.php');
global $DB, $PAGE;
echo header('Content-type: application/json');
$course = required_param('courseid', PARAM_INT);
$renderable = new report_graphic_renderable($course);


$renderer = $PAGE->get_renderer('report_graphic');

$renderable->get_gcharts_data_info();
$charts = array($renderable->mostactiveusers,  $renderable->mosttriggeredevents, $renderable->activitybyperiod, $renderable->course->fullname);

echo json_encode($charts);