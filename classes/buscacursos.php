<?php
/**
 * Criado com PhpStorm.
 * Autor: Ello Oliveira
 * Data: 26/12/2017
 * Hora: 16:23
 */

require_once("../../../config.php");
require_once($CFG->dirroot . '/report/graphic/classes/renderable.php');
global $DB;
echo header('Content-type: application/json');
$category = required_param('categoryid', PARAM_INT);
$renderable = new report_graphic_renderable();
$courses = $renderable->get_courses($category);
echo json_encode($courses);