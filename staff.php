<?php
	require_once 'config.inc.php';
		
	$tpl_main = new TemplatePower('template/master.html');
	$tpl_main->prepare();

	// check to see if the user is logged in and is a staff or admin
	if(!$_AUTH->isLoggedIn() || (!$_AUTH->isStaff() && !$_AUTH->isAdmin())) {
		// send the user to an error page and that's it
		$tpl = new TemplatePower('template/error.html');
		$tpl->assignGlobal('error_message', 'You are not logged in as an administrator or staff.');
		$tpl->prepare();
	
	// the user is good to go
	} else {
		
		$tpl = new TemplatePower('template/staff.html');
		$tpl->prepare();
		
		if (isset($_POST['course_id'])) {
			$course = $_POST['course_id'];
			$c = get_course($_DB, $course);
			$tpl->assignGlobal('course', $c['courseTitle']);
		}
		
		// see if a form was submitted
		if(isset($_POST['action'])) {
			$action = $_POST['action'];

			// check to see what the user wanted to do
			switch ($action) {
				// selecting a new course
				case 'course_change':
					$course = $_POST['course'];
					break;
				case 'student_change':
					$student = $_POST['student'];
					break;
				case 'modify':
					if(isset($_POST['delete'])) {
						$assignment = $_POST['assn_id'];
						$_DB->sql_query("DELETE FROM assignments WHERE assignmentID='$assignment';");
					} elseif(isset($_POST['update'])) {
						$assignment = $_POST['assn_id'];
						$title = $_POST['title'];
						$base = $_POST['base'];
						$weight = $_POST['weight'];
						$upload = isset($_POST['upload'])?1:0;
						
						$_DB->sql_query("UPDATE assignments SET assignmentTitle='$title', assignmentCourse='$course', assignmentBase='$base', assignmentWeight='$weight', assignmentUpload='$upload' WHERE assignmentID='$assignment' ;");
					} elseif(isset($_POST['add'])) {
						if(isset($course)){
							$_DB->sql_query("INSERT INTO assignments (assignmentTitle, assignmentCourse) VALUES ('NEW ASSIGNMENT', '$course');");
						}
					}
					break;
				case 'grademodify':
					$assignment = $_POST['assn_id'];
					$student = $_POST['student_id'];
					$score  = $_POST['score'];
					$_DB->sql_query("SELECT * FROM grades WHERE gradeAssignment='$assignment' AND gradeUser='$student'");
					if($row = $_DB->sql_fetchrow()) {
						$gradeID = $row['gradeID'];
					} else {
						$_DB->sql_query("INSERT INTO grades (gradeUser, gradeAssignment) VALUES ('$student', '$assignment');");
						$_DB->sql_query("SELECT * FROM grades WHERE gradeAssignment='$assignment' AND gradeUser='$student'");
						$row = $_DB->sql_fetchrow();
						$gradeID = $row['gradeID'];
					}
					
					$_DB->sql_query("UPDATE grades SET gradeScore = '$score' WHERE gradeAssignment='$assignment' AND gradeUser='$student'");
			}
		}
		

		if (isset($student)) {
			set_student_template($_DB, $tpl, $course, $student);
			$stud = get_student($_DB, $student);
			$tpl->assignGlobal('student', $stud['userName']);
			$tpl->assignGlobal('student_select_box', gen_student_select_box($_DB, $course));
			$tpl->assignGlobal('course_id', $course);
		} else if(isset($course)) {
			set_course_template($_DB, $tpl, $course);
			$tpl->assignGlobal('student_select_box', gen_student_select_box($_DB, $course));
			$tpl->assignGlobal('course_id', $course);
			
		}
		
		// add the remaining components to the template
		$tpl->assignGlobal('course_select_box', gen_course_select_box($_DB));
		$tpl->assignGlobal('username', $_SESSION['userName']);
	}
	
	// for all of the pages, put the content into the main template
	$tpl->showUnAssigned(false);
	$tpl_main->assignGlobal("content", $tpl->getOutputContent());
	
	$tpl_main->showUnAssigned(false);
	$tpl_main->printToScreen();
	
	/**
	 * Return a list of courses that the user is enrolled in
	 *
	 * @db the MySQL database connection
	 *
	 **/
	function get_user_courses($db) {		
		$user = $_SESSION['userID'];
				
		$db->sql_query("SELECT * FROM stafftocourses JOIN courses ON staff = $user;");

		return $db->sql_fetchrowset();
	}
	
	/**
	 * Return a HTML combo box populated with the courses that the user is in
	 *
	 * @db the MySQL database connection
	 *
	 **/
	function gen_course_select_box($db) {
		$course_select_box = '<select name="course" onChange="frmCourse.submit();"><option value="" selected>Select Course</option>';
		foreach(get_user_courses($db) as $row) {
			$course_select_box .= "<option value='".$row['courseID']."'>".$row['courseTitle']."</option>";
		}
		$course_select_box .= '</select>';
		
		return $course_select_box;
	}
	
	/**
	 * Return a list of courses that the user is enrolled in
	 *
	 * @db the MySQL database connection
	 *
	 **/
	function get_course_students($db, $courseID) {	
		$db->sql_query("SELECT * FROM studentstocourses JOIN users ON userID = student AND course = '$courseID' ");

		return $db->sql_fetchrowset();
	}
	
	/**
	 * Return a HTML combo box populated with the students in a course
	 *
	 * @db the MySQL database connection
	 *
	 **/
	function gen_student_select_box($db, $courseID) {
		$course_select_box = '<select name="student" onChange="frmStudent.submit();"><option value="" selected>Select Student</option>';
		foreach(get_course_students($db, $courseID) as $row) {
			$course_select_box .= "<option value='".$row['userID']."'>".$row['userName']."</option>";
		}
		$course_select_box .= '</select>';
		
		return $course_select_box;
	}
	
	/**
	 * Return a single course's data or nothing if the course doesn't exist
	 *
	 * @db the MySQL database connection
	 *
	 **/
	function get_course($db, $courseID) {
		$courses = get_user_courses($db);
		
		foreach($courses as $row) {
			if($row['courseID'] == $courseID) {
				return $row;
			}
		}
	}

	/**
	 * Return a single course's data or nothing if the course doesn't exist
	 *
	 * @db the MySQL database connection
	 *
	 **/
	function get_student($db, $userID) {
		$db->sql_query("SELECT * FROM users WHERE userID = '$userID' LIMIT 1;");
		return $db->sql_fetchrow();
	}
	
	/**
	 * Populate a template with the individual assignments and user's grades
	 *
	 * @db the MySQL database connection
	 * @tpl the existing template to populate
	 * @courseID the course to populate assignments for
	 *
	 **/
	function set_student_template($db, $tpl, $courseID, $userID) {
		$course  = get_course($db, $courseID);
		$tpl->assignGlobal('course', $course['courseTitle']);
		
		$db->sql_query("SELECT * FROM assignments WHERE assignmentCourse = '$courseID';");
		$assignments = $db->sql_fetchrowset();
		
		$tpl->newBlock( 'grade_head_blk' );
		
		foreach($assignments as $assn) {
			$tpl->newBlock( 'grade_blk' );
			$tpl->assign('g_title', $assn['assignmentTitle']);
			$tpl->assign('g_base', $assn['assignmentBase']);
			$tpl->assign('g_weight', $assn['assignmentWeight']);
			$tpl->assign('g_id', $assn['assignmentID']);
			$tpl->assign('g_student', $userID);
			
			$assnID = $assn['assignmentID'];
			
			$db->sql_query("SELECT * FROM grades WHERE (gradeAssignment = '$assnID' AND gradeUser = '$userID');");
			$grade = $db->sql_fetchrow();
			
			$tpl->assign('g_score', $grade['gradeScore']);
			
			if (!$assn['assignmentUpload']) {
				$tpl->assign('g_upload', 'disabled');
				$tpl->assign('g_download', 'Download');
			} else {
				$tpl->assign('g_download', '<a href="' . $grade['gradeFile'] . '">Download</a>');
			}
		}
		
		$tpl->gotoBlock( "_ROOT" );
	}
	
	/**
	 * Populate a template with the individual assignments and user's grades
	 *
	 * @db the MySQL database connection
	 * @tpl the existing template to populate
	 * @courseID the course to populate assignments for
	 *
	 **/
	function set_course_template($db, $tpl, $courseID) {
		$course  = get_course($db, $courseID);
		$tpl->assignGlobal('course', $course['courseTitle']);
		
		$tpl->newBlock( 'assignment_head_blk');
		
		$db->sql_query("SELECT * FROM assignments WHERE assignmentCourse = '$courseID';");
		$assignments = $db->sql_fetchrowset();
		
		foreach($assignments as $assn) {
			$tpl->newBlock( 'assignment_blk' );
			$tpl->assign('g_title', $assn['assignmentTitle']);
			$tpl->assign('g_base', $assn['assignmentBase']);
			$tpl->assign('g_weight', $assn['assignmentWeight']);
			$tpl->assign('g_id', $assn['assignmentID']);
			
			if($assn['assignmentUpload']) { 
				$tpl->assign('g_upload', 'checked');	
			}
			
			$assnID = $assn['assignmentID'];
			$tpl->assign('g_download', '<a href="staff.php?delete='.$assnID.'">Delete</a>');
			
		}
		
		$tpl->newBlock('assignment_add_blk');
		
		$tpl->gotoBlock( "_ROOT" );
	}
?>
