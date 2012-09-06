<?php
	require_once 'config.inc.php';
		
	$tpl_main = new TemplatePower('template/master.html');
	$tpl_main->prepare();

	// check to see if the user is logged in and is a student, staff or admin
	if(!$_AUTH->isLoggedIn() || (!$_AUTH->isStudent() && !$_AUTH->isStaff() && !$_AUTH->isAdmin())) {
		// send the user to an error page and that's it
		$tpl = new TemplatePower('template/error.html');
		$tpl->assignGlobal('error_message', 'You are not logged in as a student.');
		$tpl->prepare();
	
	// check to see if the user is in any courses
	} elseif(sizeof(get_user_courses($_DB)) == 0) {
		// send the user to an error page and that's it
		$tpl = new TemplatePower('template/error.html');
		$tpl->assignGlobal('error_message', 'You are not not enrolled in any courses.');
		$tpl->prepare();
	
	// the user is good to go
	} else {
		
		$tpl = new TemplatePower('template/students.html');
		
		// see if a form was submitted
		if(isset($_POST['action'])) {
			$action = $_POST['action'];

			// check to see what the user wanted to do
			switch ($action) {
			
				// uploading a file
				case 'upload':
					$assignment = $_POST['assignment'];
					$user = $_SESSION['userID'];
					$path = $_CONF['upload_path'];
					
					$tpl->assignInclude('upload', 'template/upload.html');
					$tpl->assignGlobal('assignment', $assignment);
					
					// check to see if a file has been uploaded
					if (isset($_FILES['file'])) {
						$target_file =  $path . $assignment . '-' . $_FILES['file']['name'];

						// save the file to the uploads directory
						if(move_uploaded_file($_FILES['file']['tmp_name'], $target_file)) {
							$tpl->assignGlobal('upload_error', 'The file '.  basename( $_FILES['file']['name']). ' has been uploaded as ' . $target_file);
							
							// check to see if the grade already exists.
							$_DB->sql_query("SELECT * FROM grades WHERE gradeAssignment='$assignment' AND gradeUser='$user' LIMIT 1;");
							if ($_DB->sql_numrows() > 0) {
								// use the existing grade
								$gradeID = $_DB->sql_fetchrow();
								$gradeID = $gradeID['gradeID'];
							} else {
								// create a new grade for the user
								$_DB->sql_query("INSERT INTO grades (gradeAssignment, gradeUser) VALUES ('$assignment', '$user')");
								$_DB->sql_query("SELECT * FROM grades WHERE gradeAssignment='$assignment' AND gradeUser='$user' LIMIT 1;");
								$gradeID = $_DB->sql_fetchrow();
								$gradeID = $gradeID['gradeID'];
							}
							
							// add the file to the grade
							$_DB->sql_query("UPDATE  grades SET gradeFile = '$target_file' WHERE  gradeID = '$gradeID' LIMIT 1;") ;
						} else{
							$tpl->assignGlobal('upload_error', 'There was an error uploading the file, please try again!');
						}
					}
					break;
				
				// selecting a new course
				case 'course_change':
					$course = $_POST['course'];
					break;
			}
		}
		
		// pull the template together
		$tpl->prepare();
		
		// display the selected course
		if(isset($course)) {
			$c = get_user_courses($_DB);
			$course = $c[0]['courseID'];
			set_course_template($_DB, $tpl, $course);
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
				
		$db->sql_query("SELECT * FROM courses WHERE courseID = ( SELECT course FROM studentstocourses WHERE student =  '$user' ) ");

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
		
		$db->sql_query("SELECT * FROM assignments WHERE assignmentCourse = '$courseID';");
		$assignments = $db->sql_fetchrowset();
		
		foreach($assignments as $assn) {
			$tpl->newBlock( 'grade_blk' );
			$tpl->assign('g_title', $assn['assignmentTitle']);
			$tpl->assign('g_base', $assn['assignmentBase']);
			$tpl->assign('g_weight', $assn['assignmentWeight']);
			$tpl->assign('g_id', $assn['assignmentID'].'-'.$_SESSION['userID']);
			
			$assnID = $assn['assignmentID'];
			$user = $_SESSION['userID'];
			
			$db->sql_query("SELECT * FROM grades WHERE gradeAssignment = '$assnID' and gradeUser = '$user' LIMIT 1;");
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
?>
