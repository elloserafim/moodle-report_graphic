<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Graphic report renderer.
 *
 * @package    report_graphic
 * @copyright  2015 Simey Lameze <simey@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

/**
 * Graphic report renderer class.
 *
 * @package    report_graphic
 * @copyright  2015 Simey Lameze <simey@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class report_graphic_renderer extends plugin_renderer_base {

    /** @var report_graphic_renderable instance of report graphic renderable. */
    protected $renderable;

    /**
     * Renderer constructor.
     *
     * @param report_graphic_renderable $renderable graphic report renderable instance.
     */
    protected function render_report_graphic(report_graphic_renderable $renderable) {
        $this->renderable = $renderable;
        $this->report_selector_form();
    }

    /**
     * This function is used to generate and display course filter.
     *
     */
    public function report_selector_form() {
        $renderable = $this->renderable;
        $courses = $renderable->get_categories_list(0, true);
        //$courses = $renderable->get_course_list();
        $selectedcourseid = empty($renderable->course) ? 0 : $renderable->course->id;

        echo html_writer::start_tag('form', array('class' => 'logselecform', 'action' => 'course.php', 'method' => 'get'));
        echo html_writer::start_div();
        echo html_writer::label(get_string('selectacategory'), 'cat3', false);
        if(!$selectedcourseid) {
            echo html_writer::select($courses, "cat0", $selectedcourseid, array('' => 'choosedots'), array('id' => 'cat0', 'class'=> 'category'));
            echo html_writer::select(array(), "cat1", $selectedcourseid, array('' => 'choosedots'), array('id' => 'cat1', 'disabled' => 'disabled', 'class'=> 'category'));
            echo html_writer::select(array('null' => 'Selecione a categoria...'), "cat2", $selectedcourseid, array('' => 'choosedots'), array('id' => 'cat2', 'disabled' => 'disabled', 'class'=> 'category'));
            echo html_writer::select(array('null' => 'Selecione a categoria...'), "cat3", $selectedcourseid, array('' => 'choosedots'), array('id' => 'cat3', 'disabled' => 'disabled', 'class'=> 'category'));
            echo html_writer::checkbox('ativeonly', 'ativeonly', true, 'Apenas Ativos', array('id'=> 'activeonly'));
        }

        echo html_writer::end_div();

        echo html_writer::start_div('', array('style'=>'text-align:center'));
        echo html_writer::start_tag('i', array('id'=>'spinner','class' => 'fa fa-spinner fa-pulse fa-3x', 'style'=>'display:none'));
        echo html_writer::end_div();
        echo html_writer::end_tag('i');
        echo html_writer::start_div('', array('id'=> 'courseselect', 'style'=>'display:none'));
        echo html_writer::label(get_string('selectacourse'), 'courseid', false);
        echo html_writer::select(array('null' => 'Selecione o curso'), "courseid", null, array('' => 'choosedots'), array('id' => 'courseid'));
        echo html_writer::empty_tag('input', array('id'=> 'btn_gerar_grafico', 'type' => 'button', 'value' => 'Gerar gráfico desta disciplina'));
        echo html_writer::end_div();
        echo html_writer::end_tag('form');
    }
    /**
     * Display course related graphic reports.
     */
    public function report_generate_charts() {
        $renderable = $this->renderable;
        echo $renderable->get_gcharts_data();
        echo $renderable->mostactiveusers;
        echo $renderable->mosttriggeredevents;
        echo $renderable->activitybyperiod;
    }

    /**
     * Display site related graphic reports.
     */
    public function report_course_activity_chart() {
        $this->renderable->get_courses_activity();
        echo '<div id="div_chart" class="div_charts">';
        echo $this->renderable->mostactivecourses;
        echo '</div><div id="div_chart2" class="div_charts"></div><div id="div_chart3_dashboard" class="div_charts"><div id="div_chart3" class="div_charts"></div><div id="div_chart3_filter" class="div_charts"></div></div>';
    }

    public function get_category_activity($category)
    {
        $this->renderable->get_courses_activity($category);
        echo '<div id="div_chart">';
        echo $this->renderable->mostactivecourses;
        echo '</div>';
    }
}
