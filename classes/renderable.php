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
 * Graphic report renderer class.
 *
 * @package    report_graphic
 * @copyright  2015 Simey Lameze <simey@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die;
require_once($CFG->dirroot . '/report/graphic/classes/report_graphic.php');

/**
 * Graphic report renderable class.
 *
 * @package    report_graphic
 * @copyright  2015 Simey Lameze <simey@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class report_graphic_renderable implements renderable {

    /**
     * @var stdClass the course object.
     */
    public $course;
    /**
     * @var int|\stdClass controls course visibility.
     */
    public $showcourses;
    /**
     * @var  string stores users activity events return from google charts.
     */
    public $mostactiveusers;
    /**
     * @var  string stores the most triggered events return from google charts.
     */
    public $mosttriggeredevents;
    /**
     * @var  string stores the activity by period return from google charts.
     */
    public $activitybyperiod;
    /**
     * @var string stores the course activity return from google charts.
     */
    public $mostactivecourses;

    /**
     * Constructor.
     *
     * @param stdClass|int $course (optional) course object or id.
     */
    public function __construct($course = null) {
        if (!empty($course)) {
            if (is_int($course)) {
                $course = get_course($course);
            }
            $this->course = $course;
        }
    }

    /**
     * Return list of courses to show in selector.
     *
     * @return array list of courses.
     */
    public function get_course_list() {
        global $DB;

        $courses = array();
        $sitecontext = context_system::instance();
        // First check to see if we can override showcourses and showusers.
        $numcourses = $DB->count_records("course", array('visible' => '1'));
        if ($numcourses < COURSE_MAX_COURSES_PER_DROPDOWN && !$this->showcourses) {
            $this->showcourses = 1;
        }
        // Check if course filter should be shown.
        if ($this->showcourses) {
            if ($courserecords = $DB->get_records("course", array('visible' => '1'), "fullname", "id,shortname,fullname,category")) {
                foreach ($courserecords as $course) {
                    if ($course->id == SITEID) {
                        $courses[$course->id] = format_string($course->fullname) . ' (' . get_string('site') . ')';
                    } else {
                        $courses[$course->id] = format_string(get_course_display_name_for_list($course));
                    }
                }
            }
            core_collator::asort($courses);
        }
        return $courses;
    }

    public function get_categories_list($parent = 0, $keys = false) {
        global $DB;

        $courses = array();
        $sitecontext = context_system::instance();


            if ($courserecords = $DB->get_records("course_categories", array('parent' => $parent), "name", "id,name")) {
                if($keys){
                    foreach ($courserecords as $course) {
                        $courses[$course->id] = format_string($course->name);
                    }
                }
                else{
                    foreach ($courserecords as $course) {
                        $courses[] = $course;
                    }
                    //core_collator::asort($courserecords);
                    return $courses;
                }

            }
        return $courses;
    }

    public function get_courses($category){
        global $DB;
        $courses = $DB->get_records('course', array('category' => $category), 'fullname', 'id, fullname');
        $return = array();
        foreach ($courses as $course){
            $return[] = $course;
        }
        return $return;
    }


    /**
     * Displays course related graph charts.
     */
    public function get_gcharts_data() {
        $graphreport = new report_graphic($this->course->id);

        // User Activity Pie Chart.
        $this->mostactiveusers = $graphreport->get_most_active_users();

        // Most triggered events. rename this attr
        $this->mosttriggeredevents = $graphreport->get_most_triggered_events();

        // Monthly user activity.
        $this->activitybyperiod = $graphreport->get_monthly_user_activity();
    }

    public function get_gcharts_data_info() {
        $graphreport = new report_graphic($this->course->id);

        // User Activity Pie Chart.
        $this->mostactiveusers = $graphreport->get_most_active_users_data();

        // Most triggered events. rename this attr
        $this->mosttriggeredevents = $graphreport->get_most_triggered_events_data();

        // Monthly user activity.
        $this->activitybyperiod = $graphreport->get_monthly_user_activity_data();
    }

    /**
     * Displays site related charts.
     */
    public function get_courses_activity($category, $forcechart, $activeonly) {
        $graphreport = new report_graphic();
        $this->mostactivecourses = $graphreport->get_category_activity($category, $forcechart, $activeonly);
    }
}
