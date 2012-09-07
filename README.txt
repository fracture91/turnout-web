Andrew Hurle
CS 4401 - Software Security Engineering
Turnout Web Patch Assignment

1.  Validated the assignment string on the upload page in students.php to make
sure it indicates an assignment the student belongs to and a user ID that
matches with the session cookie.  Prevents submitting for assignments that the
student shouldn't be able to submit to, or overwriting other users' files.  See
"is_assignment_valid".

2.  Checked file extension server-side instead of just client-side.  Of course,
this doesn't prevent hiding a file of one type behind a different extension,
but it should save gullible profs from running an exe straight from the browser.
See "allowedExts".  Also, this keeps PHP uploads from running.

3.  Saved all files with the sameish file name + extension (filename from user
seems irrelevant and unnecessary, possible attack vector with which one might
mangle the save path).

4.  Students can only upload one file per assignment to prevent denial of
service.  See "scandir".

5.  I stopped the bad SQL being dumped to the screen upon an error - sql
variable is no longer passed to die().  This makes it harder to tell what's
going on during a SQLi attack.

6.  Added uploads/index.html to prevent the directory from being listed and
information from being leaked.

7.  |chmod o-x phpMyAdmin| to turn off the admin site thing, so mucking with it
is no longer possible.

8.  Changed login.php so that it's only allowed to redirect to the student or
staff pages (see "allowedRedirects").  Makes it harder/impossible to spoof what
comes after the login page.  This also prevents the XSS through the redirect
input.

9.  Used mysql_real_escape_string to escape login inputs in auth.inc.php.  This
prevents against SQLi attacks on those inputs.

10.  Sanitized username in login.php with htmlspecialchars to prevent XSS.

