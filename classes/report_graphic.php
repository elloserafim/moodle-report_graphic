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
 * Graphic report
 *
 * @package    report_graphic
 * @copyright  2014 onwards Simey Lameze <lameze@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();
require_once($CFG->dirroot . '/report/graphic/lib/gcharts.php');
/**
 * Graphic report class.
 *
 * Retrieve log data, organize in the required format and send to google charts API.
 *
 * @package    report_graphic
 * @copyright  2015 onwards Simey Lameze <lameze@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class report_graphic extends Gcharts {

    /**
     * @var int|null the course id.
     */
    protected $courseid;
    /**
     * @var int the current year.
     */
    protected $year;
    /**
     * @var \core\log\sql_SELECT_reader instance.
     */
    protected $logreader;
    /**
     * @var  string Log reader table name.
     */
    protected $logtable;

    /**
     * Graphic report constructor.
     *
     * Retrieve events log data to be used by other methods.
     *
     * @param int|null $courseid course id.
     */
    public function __construct($courseid = null) {
        $this->courseid = $courseid;
        $this->year = date('Y');

        // Get the log manager.
        $logreader = get_log_manager()->get_readers();
        $logreader = reset($logreader);
        $this->logreader = $logreader;

        // Set the log table.
        $this->logtable = $logreader->get_internal_log_table_name();
    }

    /**
     * Get most triggered events by course id.
     *
     * @return string google charts data.
     */
    public function get_most_triggered_events() {
        global $DB;

        $sql = "SELECT l.eventname, COUNT(*) as quant
                  FROM {" . $this->logtable . "} l
                 WHERE l.courseid = ".$this->courseid."
                 GROUP BY l.eventname
                 ORDER BY quant DESC";
        $result = $DB->get_records_sql($sql);

        // Graphic header, must be always the first element of the array.
        $events[0] = array(get_string('event', 'report_graphic'), get_string('quantity', 'report_graphic'));

        $i = 1;
        foreach ($result as $eventdata) {
            $event = $eventdata->eventname;
            $events[$i] = array($event::get_name(), (int)$eventdata->quant);
            $i++;
        }

        $this->load(array('graphic_type' => 'ColumnChart'));
        $this->set_options(array('title' => get_string('eventsmosttriggered', 'report_graphic')));

        return $this->generate($events);
    }

    public function get_most_triggered_events_data() {
        global $DB;

        $sql = "SELECT l.eventname, COUNT(*) as quant
                  FROM {" . $this->logtable . "} l
                 WHERE l.courseid = ".$this->courseid."
                 GROUP BY l.eventname
                 ORDER BY quant DESC";
        $result = $DB->get_records_sql($sql);

        // Graphic header, must be always the first element of the array.
        $events[0] = array(get_string('event', 'report_graphic'), get_string('quantity', 'report_graphic'));

        $i = 1;
        foreach ($result as $eventdata) {
            $event = $eventdata->eventname;
            $events[$i] = array($event::get_name(), (int)$eventdata->quant);
            $i++;
        }

        return $events;
    }

    /**
     * Get users that most triggered events by course id.
     *
     * @return string google charts data.
     */
    public function get_most_active_users() {
        $useractivity = get_most_active_users_data();
        $this->load(array('graphic_type' => 'PieChart'));
        $this->set_options(array('title' => get_string('usersactivity', 'report_graphic')));
        return $this->generate($useractivity);
    }

    public function get_most_active_users_data() {
        global $DB;

        $sql = "SELECT l.relateduserid, u.firstname, u.lastname, COUNT(*) as quant
                  FROM {" . $this->logtable . "} l
            INNER JOIN {user} u ON u.id = l.relateduserid
                 WHERE l.courseid = " . $this->courseid . "
              GROUP BY l.relateduserid, u.firstname, u.lastname
              ORDER BY quant DESC";
        $result = $DB->get_records_sql($sql);

        // Graphic header, must be the first element of the array.
        $useractivity[0] = array(get_string('user'), get_string('percentage', 'report_graphic'));

        // Organize the data in the required format.
        $i = 1;
        foreach ($result as $userdata) {
            $username = $userdata->firstname . ' ' . $userdata->lastname;
            $useractivity[$i] = array($username, (int)$userdata->quant);
            $i++;
        }
        return $useractivity;
    }


    public function get_monthly_user_activity_data(){
        global $DB;
        $today = new DateTime('now');
        $courseid = $this->courseid;
        $months = cal_info(0);
        $yeararr = array(array('year' => $this->year-1, 'vals'=>array()), array('year' => $this->year, 'vals'=>array()));
        $montharr = array();

        // Build the query to get how many events each user has triggered grouping by month.
        // This piece of code has few hacks to deal with cross-db issues but certainly can be improved.
        // Also create required arrays of months and etc.
        $sql = "SELECT u.id, u.firstname, u.lastname, ";
        $comma = '';
        foreach($yeararr as $yeardata ) {

            $sql.=$comma;
            for ($m = 1; $m <= count($months['abbrevmonths']); $m++) {

                // Get and format month name and number.
                $monthname = $months['months'][$m];
                $monthabbrev = $months['abbrevmonths'][$m];
                $month = sprintf("%02d", $m);
                // Get the first and the last day of the month.
                $ymdfrom = "{$yeardata['year']}-$month-01";
                $ymdto = date('Y-m-t', strtotime($ymdfrom));

                // Convert to timestamp.
                $date = new DateTime($ymdfrom);
                $datefrom = $date->getTimestamp();
                $date = new DateTime($ymdto);
                $dateto = $date->getTimestamp();

                // Get the quantity of triggered events for each month.
                $sql .= "(SELECT COUNT(id) AS quant
                        FROM {" . $this->logtable . "} l
                       WHERE l.courseid = $courseid
                         AND timecreated >= $datefrom
                         AND timecreated < $dateto
                         AND u.id = l.userid
                     ) AS $monthname"."_".$yeardata['year'];

                // Add comma after the month name.
                $sql .= ($m < 12 ? ',' : ' ');

                // Create a empty array that will be filled after the results of this query.
                $montharr[$monthabbrev][0] = $monthabbrev;

            }
            $comma=', ';
        }
        $sql .= "FROM {user} u
                WHERE u.id IN (SELECT DISTINCT (l.userid) FROM {" . $this->logtable . "} l WHERE courseid = $courseid )
                ORDER BY u.id";


        $result = $DB->get_records_sql($sql);

        $usersarr[0] = 'Month';

        foreach ($result as $userid => $data) {


            $usersarr[] = $data->firstname . ' ' . $data->lastname;

            // Fill the array with the quantity of triggered events in the month, by user id.

            foreach ($yeararr as $iyear =>$yeardata) {

                $yeararr[$iyear]['vals']['Jan'][] = (int)$data->{january_.$yeardata['year']};
                $yeararr[$iyear]['vals']['Feb'][] = (int)$data->{february_.$yeardata['year']};
                $yeararr[$iyear]['vals']['Mar'][] = (int)$data->{march_.$yeardata['year']};
                $yeararr[$iyear]['vals']['Apr'][] = (int)$data->{april_.$yeardata['year']};
                $yeararr[$iyear]['vals']['May'][] = (int)$data->{may_.$yeardata['year']};
                $yeararr[$iyear]['vals']['Jun'][] = (int)$data->{june_.$yeardata['year']};
                $yeararr[$iyear]['vals']['Jul'][] = (int)$data->{july_.$yeardata['year']};
                $yeararr[$iyear]['vals']['Aug'][] = (int)$data->{august_.$yeardata['year']};
                $yeararr[$iyear]['vals']['Sep'][] = (int)$data->{september_.$yeardata['year']};
                $yeararr[$iyear]['vals']['Oct'][] = (int)$data->{october_.$yeardata['year']};
                $yeararr[$iyear]['vals']['Nov'][] = (int)$data->{november_.$yeardata['year']};
                $yeararr[$iyear]['vals']['Dec'][] = (int)$data->{december_.$yeardata['year']};
            }

        }


        $final = array();
        // Organize the data in the required format of the chart.
        $i = 0;
        // The header of the report, must be all users
        $final[] = $usersarr;
        foreach($yeararr as $year) {

            for ($m = 1; $m <= count($months['abbrevmonths']); $m++) {
                $monthabbrev = $months['abbrevmonths'][$m];
                $pos = array();
                $pos[] = $m.'/'.$year['year'];
                $pos = array_merge( $pos,  $year['vals'][$monthabbrev]);
                $current = new DateTime($year['year'].'-'.$m.'-01' );
                if($current<=$today) {
                    $final[] = $pos;
                }
            }
            $i++;
        }
        return $final;
    }
    /**
     * Get monthly activity (events by month x users).
     *
     * @return string the google charts data.
     */
    public function get_monthly_user_activity() {
        $year = $this->year;
        $final = $this->get_monthly_user_activity_data();

        $this->load(array('graphic_type' => 'linechart'));
        $this->set_options(array('title' => get_string('eventsbymonth', 'report_graphic', $year), 'curveType' => 'function'));

        return $this->generate($final);
    }

    /**
     * Create a chart of events triggered by courses.
     *
     * @return string the google charts data.
     */
    public function get_courses_activity() {
        global $DB;

        $sql = "SELECT l.courseid, c.shortname, COUNT(*) AS quant
                  FROM {" . $this->logtable . "} l
            INNER JOIN mdl_course c ON c.id = l.courseid
                 WHERE l.courseid = c.id
              GROUP BY l.courseid, c.fullname
              ORDER BY l.courseid";
        $result = $DB->get_records_sql($sql);

        // Format the data to google charts.
        $i = 1;
        $courseactivity[0] = array(get_string('course'), get_string('percentage', 'report_graphic'));
        foreach ($result as $courseid => $coursedata) {
            $courseactivity[$i] = array($coursedata->fullname, (int)$coursedata->quant);
            $i++;
        }

        $this->load(array('graphic_type' => 'PieChart'));
        $this->set_options(array('title' => get_string('coursesactivity', 'report_graphic')));

        return $this->generate($courseactivity);
    }


    public function get_category_activity($category, $forcechart = false, $activeonly = false) {
        if($category == null){
            return "<h3>Por favor, refine sua pesquisa.</h3>";
        }
        global $DB;
        $cat = $DB->get_record('course_categories', array('id'=> $category), 'id, name, depth');
        $categorieslist[$cat->id] = $cat;

        //get ids of categories chindren of $category:
        $sql = "select cat.id, cat.name, cat.depth from mdl_course_categories cat
          where cat.parent = ". $category;
        $result = $DB->get_records_sql($sql);

        $categorieslist = $categorieslist + $result;

        for($i = 0; $i<4; $i++) {

            if (count($result) > 0) {
                $sql = "select cat.id, cat.name, cat.depth from mdl_course_categories cat
          where cat.parent in (" . implode(', ', array_keys($result)) . ")";
                $result = $DB->get_records_sql($sql);
                $categorieslist = $categorieslist + $result;
            }
        }


        $sql = "SELECT l.courseid, c.fullname, COUNT(*) AS quant
                  FROM {" . $this->logtable . "} l
            INNER JOIN mdl_course c ON c.id = l.courseid
                 WHERE l.courseid = c.id";

        if($activeonly){
            $sql .= " AND visible = 1 ";
        }
        $sql.= " AND c.category IN (".implode(', ', array_keys($categorieslist)).")
              GROUP BY l.courseid, c.fullname
              ORDER BY quant DESC";

        $result = $DB->get_records_sql($sql);


        if($forcechart || count($result) < 50) {
            // Format the data to google charts.
            $i = 1;
            $courseactivity[0] = array(get_string('course'), get_string('percentage', 'report_graphic'));
            foreach ($result as $courseid => $coursedata) {
                $courseactivity[$i] = array($coursedata->fullname, (int)$coursedata->quant, $courseid);
                $i++;
            }
            return $courseactivity;

        }
        else{
            return "<h3>Muitos cursos encontrados, por favor, refine sua pesquisa.</h3>";
        }
        return $this->generate($courseactivity);
    }

}
