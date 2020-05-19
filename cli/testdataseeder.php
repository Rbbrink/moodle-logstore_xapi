
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

define('CLI_SCRIPT', 1);
require_once(__DIR__.'/../../../../../../config.php');
require_once($CFG->libdir . '/testing/generator/lib.php');

function get_object() {
    $obj = new stdClass();
    $obj->eventname = '';
    $obj->component = '';
    $obj->action = '';
    $obj->target = '';
    $obj->objecttable = '';
    $obj->objectid = '';
    $obj->crud = '';
    $obj->edulevel = '';
    $obj->contextid = '';
    $obj->contextlevel = '';
    $obj->contextinstanceid = '';
    $obj->userid = '';
    $obj->courseid = '';
    $obj->relateduserid = '';
    $obj->anonymous = '';
    $obj->other = '';
    $obj->timecreated = time();
    $obj->origin = '';
    $obj->ip = '';
    $obj->realuserid = '';
    $obj->logstorestandardlogid = 0;
    $obj->type = 0;
    return $obj;
}

function clean_string($val) {
    $clean = $val;
    $clean = str_replace("'", "", $val);
    return $clean;
}

function insert_row($table, $rowcsv) {
    global $DB;
    $obj = get_object();
    $strarr = explode(",", $rowcsv);

    $n = 0;
    foreach ($obj as $key => $value) {
        $clean = clean_string($strarr[$n]);
        $obj->$key = clean_string($strarr[$n]);
        $n++;
    }

    // add in some failed data
    if ($table == "logstore_xapi_failed_log") {
        $obj->errortype = "401";
        $obj->response = '{"errorId":"4f442d54-a027-4084-bf79-2e6571ded994","message":"Unauthorised"}';
    }

    // we don't have a corresponding logstore_standard_log entry so clear it
    $obj->logstorestandardlogid = 0;

    // if this is not set, unset it
    if ($obj->eventname == '\core\event\course_viewed') {
        unset($obj->objecttable);
    }

    if ($obj->objectid == "NULL") {
        unset($obj->objectid);
    }

    // unset these so they are NULL
    unset($obj->relateduserid);
    unset($obj->realuserid);

    // insert
    $DB->insert_record($table, $obj);
}

function create_user_logged_in($table) {
    $str = "'\\core\\event\\user_loggedin','core','loggedin','user','user',2,'r',0,1,10,0,2,0,NULL,0,'a:1:{s:8:\"username\";s:5:\"admin\";}',".time().",'web','172.22.0.1',NULL,'0','0'";
    insert_row($table, $str);
}

function create_user_logged_out($table) {
    $str = "'\\core\\event\\user_loggedout','core','loggedout','user','user',2,'r',0,1,10,0,2,0,NULL,0,'a:1:{s:9:\"sessionid\";s:32:\"684a3da55670cffb147d80903908e3b0\";}',".time().",'web','172.19.0.1',NULL,'0','0'";
    insert_row($table, $str);
}

function create_user_course_viewed($table) {
    $str = "'\\core\\event\\course_viewed','core','viewed','course',NULL,NULL,'r',2,2,50,1,0,1,NULL,0,'N;',".time().",'web','172.19.0.1',NULL,'0','0'";
    insert_row($table, $str);
}

function create_quiz_answered_question($table) {
    $str = "'\\mod_quiz\\event\\attempt_started','mod_quiz','started','attempt','quiz_attempts',2,'c',2,4946,70,6,3,7,3,0,'N;',1589845169,'web','172.19.0.1',NULL,'0','0'";
    insert_row($table, $str);
}

function create_quiz_submitted($table) {
    $str = "'\\mod_quiz\\event\\attempt_submitted','mod_quiz','submitted','attempt','quiz_attempts',2,'u',2,4946,70,6,3,7,3,0,'a:2:{s:11:\"submitterid\";s:1:\"3\";s:6:\"quizid\";s:1:\"1\";}',".time().",'web','172.19.0.1',NULL,'0','0'";
    insert_row($table, $str);
}

function create_forum_post($table) {
    $str = "'\\mod_forum\\event\\discussion_created','mod_forum','created','discussion','forum_discussions',1,'c',2,4947,70,7,3,7,NULL,0,'a:1:{s:7:\"forumid\";i:2;}',".time().",'web','172.19.0.1',NULL,'0','0'";
    insert_row($table, $str);
}

function create_assignment_submitted($table) {
    $str = "'\\mod_assign\\event\\assessable_submitted','mod_assign','submitted','assessable','assign_submission',2,'u',2,4948,70,8,3,7,NULL,0,'a:1:{s:19:\"submission_editable\";b:1;}',".time().",'web','172.19.0.1',NULL,'0','0'";
    insert_row($table, $str);
}

function create_assignment_graded($table) {
    $str = "'\\mod_assign\\event\\submission_graded','mod_assign','graded','submission','assign_grades',1,'u',1,4948,70,8,2,7,3,0,'N;',".time().",'web','172.19.0.1',NULL,'0','0'";
    insert_row($table, $str);
}

/**
 * Create a number of rows in the table.
 * Add one row for each type, at least 10, pad the rest out with rows
 * minus the number of message types.
 *
 * @param string $table tablename
 * @param int $rows number of rows
 */
function create_test_data($table, $rows) {
    if ($rows < 10) {
        $rows = 10;
    }
    $course_viewed = $rows - 8;

    create_user_logged_in($table);
    for ($n = 0; $n < $course_viewed; $n++) {
        create_user_course_viewed($table);
    }
    create_assignment_submitted($table);
    create_assignment_graded($table);

    create_quiz_answered_question($table);
    create_quiz_submitted($table);
    create_forum_post($table);

    create_user_logged_out($table);
}

/**
 * Create a user and return the userid.
 * If the user already exists then return the userid. 
 *
 * @param string $username username
 * @param string $firstname firstname
 * @param string $lastname lastname
 * @return int userid
 */
function create_user($username, $firstname, $lastname) {
    global $DB;

    // check if user exists
    $user = $DB->get_record('user', array('username' => $username), "id,username");
    if ($user) {
        return $user->id;
    }

    // generate user
    $generator = new testing_data_generator();
    try {
        $user = $generator->create_user([
            'username' => $username,
            'firstname' => $firstname,
            'lastname' => $lastname
        ]);
    } catch (Exception $e) {
        echo $e->getMessage().PHP_EOL;
        return 0;
    }

    return $user->id;
}

function create_standing_data() {
    $user1 = create_user("user1", "User", "One");
    $user2 = create_user("user2", "User", "Two");
    echo "UserID 1: ".$user1.PHP_EOL;
    echo "UserID 2: ".$user2.PHP_EOL;

    // TODO: create course we cannot restore a real course programmatically at the moment
    // course should contain a quiz, forum and assignment
    // the forum post and assignment submissions are an added complication
    // assignment grade is related to the assignment submission
}

function create_data_set() {
    // create n-rows in each table
    // so we get one of each event and pad out the rest with course_viewed events
    $rows = 10;

    create_test_data("logstore_xapi_log", $rows);
    create_test_data("logstore_xapi_failed_log", $rows);
}

create_standing_data();
create_data_set();

echo "Script complete".PHP_EOL;
