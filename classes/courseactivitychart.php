<?php
/**
 * Criado com PhpStorm.
 * Autor: Ello Oliveira
 * Data: 15/12/2017
 * Hora: 13:49
 */

require_once("../../../config.php");
require_once($CFG->dirroot . '/report/graphic/classes/renderable.php');
global $DB, $PAGE;
echo header('Content-type: application/json');
$category = required_param('categoryid', PARAM_INT);
$forcechart = optional_param('forcechart', 'false' , PARAM_BOOL);
$activeonly = optional_param('activeonly', 'false' , PARAM_BOOL);
$renderable = new report_graphic_renderable();
$renderer = $PAGE->get_renderer('report_graphic');
$renderable->get_courses_activity($category, $forcechart, $activeonly);
echo json_encode($renderable->mostactivecourses);