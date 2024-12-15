<?php
class StudentController{
  public function get_account_by_id() {
    global $conn;
    $response = array();

    // Variables
    $user_id = htmlspecialchars($_GET['uid'] ?? '');

    // Validate security
    $security_key = new SecurityKey($conn);
    $security_response = $security_key->validateBearerToken();
  
    if ($security_response['status'] === 'error') {
      echo json_encode($security_response);
      return;
    }
  
    if ($security_response['role'] !== 'student' && $security_response['role'] !== 'authorized_user') {
      echo json_encode(['status' => 'error', 'message' => 'Unauthorized access']);
      return;
    }

    // Validate input
    if (empty($user_id)) {
      $response['status'] = 'error';
      $response['message'] = 'User ID cannot be empty';
      echo json_encode($response);
      return;
    }

    // Fetch user data based on the user_id
    $stmt = $conn->prepare("SELECT 
                              user_id,
                              student_number,
                              profile,
                              first_name,
                              middle_name,
                              last_name,
                              date_of_birth,
                              place_of_birth,
                              department,
                              program
                            FROM users WHERE user_id = ?");
    $stmt->bind_param("s", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
      $user_data = array();

      while ($row = $result->fetch_assoc()) {
        $user_data[] = $row;
      }

      $response['status'] = 'success';
      $response['user'] = $user_data;
      echo json_encode($response);
      return;
    } else {
      $response['status'] = 'error';
      $response['message'] = 'No user found';
      echo json_encode($response);
      return;
    }
  
    $stmt->close();
  }

  public function update_account() {
    global $conn;
    $response = array();

    // Variables
    $data = json_decode(file_get_contents("php://input"), true);
    $user_id = htmlspecialchars($_GET['uid'] ?? '');
    $student_number = htmlspecialchars($data['student_number'] ?? '');
    $first_name = htmlspecialchars($data['first_name'] ?? '');
    $middle_name = htmlspecialchars($data['middle_name'] ?? '');
    $last_name = htmlspecialchars($data['last_name'] ?? '');
    $date_of_birth = htmlspecialchars($data['date_of_birth'] ?? '');
    $place_of_birth = htmlspecialchars($data['place_of_birth'] ?? '');
    $department = htmlspecialchars($data['department'] ?? '');
    $program = htmlspecialchars($data['program'] ?? '');

    // Validate security
    $security_key = new SecurityKey($conn);
    $security_response = $security_key->validateBearerToken();

    if ($security_response['status'] === 'error') {
      echo json_encode($security_response);
      return;
    }

    if ($security_response['role'] !== 'student' && $security_response['role'] !== 'authorized_user') {
      echo json_encode(['status' => 'error', 'message' => 'Unauthorized access']);
      return;
    }

    // Validate input
    if (empty($user_id)) {
      $response['status'] = 'error';
      $response['message'] = 'User ID cannot be empty';
      echo json_encode($response);
      return;
    }
    
    if (empty($first_name)) {
      $response['status'] = 'error';
      $response['message'] = 'First name cannot be empty';
      echo json_encode($response);
      return;
    }
    
    if (empty($last_name)) {
      $response['status'] = 'error';
      $response['message'] = 'Last name cannot be empty';
      echo json_encode($response);
      return;
    }
    
    if (empty($department)) {
      $response['status'] = 'error';
      $response['message'] = 'Department cannot be empty';
      echo json_encode($response);
      return;
    }
    
    if (empty($program)) {
      $response['status'] = 'error';
      $response['message'] = 'Program cannot be empty';
      echo json_encode($response);
      return;
    }

    // Update user data
    $stmt = $conn->prepare("UPDATE users SET 
                              student_number = ?,
                              first_name = ?, 
                              middle_name = ?, 
                              last_name = ?, 
                              date_of_birth = ?, 
                              place_of_birth = ?, 
                              department = ?,
                              program = ? 
                            WHERE user_id = ?");
    $stmt->bind_param(
      "sssssssss", 
      $student_number, 
      $first_name, 
      $middle_name, 
      $last_name, 
      $date_of_birth, 
      $place_of_birth, 
      $department,
      $program,
      $user_id
    );
    
    if ($stmt->execute()) {
      $response['status'] = 'success';
      $response['message'] = 'User account updated successfully';
    } else {
      $response['status'] = 'error';
      $response['message'] = 'Failed to update user account';
    }

    echo json_encode($response);
    $stmt->close();
  }
   
  public function get_applications() {
    global $conn;
    $response = array();

    // Variables
    $user_id = htmlspecialchars($_GET['uid'] ?? '');
    $search_query = htmlspecialchars($_GET['search'] ?? '');
    $page = isset($_GET['page']) ? intval($_GET['page']) : 1; // Default to page 1
    $records_per_page = isset($_GET['limit']) ? intval($_GET['limit']) : 10; // Default to 10 records per page
    $offset = ($page - 1) * $records_per_page; // Calculate the offset for the SQL query

    // Validate security
    $security_key = new SecurityKey($conn);
    $security_response = $security_key->validateBearerToken();

    if ($security_response['status'] === 'error') {
      echo json_encode($security_response);
      return;
    }

    if ($security_response['role'] !== 'student' && $security_response['role'] !== 'authorized_user') {
      echo json_encode(['status' => 'error', 'message' => 'Unauthorized access']);
      return;
    }

    // Validate input
    if (empty($user_id)) {
      $response['status'] = 'error';
      $response['message'] = 'User ID cannot be empty';
      echo json_encode($response);
      return;
    }

    // Get total records count for pagination
    $count_query_str = "SELECT COUNT(DISTINCT forms.created_at) AS total_records 
                        FROM forms 
                        LEFT JOIN applications ON forms.created_at = applications.created_at
                        WHERE forms.user_id = ?";

    if (!empty($search_query)) {
      $count_query_str .= " AND (forms.first_name LIKE ? OR forms.last_name LIKE ? OR forms.email_address LIKE ?)";
    }

    $count_query = $conn->prepare($count_query_str);

    if (!empty($search_query)) {
      $search_query_param = "%$search_query%";
      $count_query->bind_param("ssss", $user_id, $search_query_param, $search_query_param, $search_query_param);
    } else {
      $count_query->bind_param("s", $user_id);
    }

    if (!$count_query) {
      die(json_encode(['status' => 'error', 'message' => 'Count query preparation failed', 'error' => $conn->error]));
    }

    $count_query->execute();
    $count_result = $count_query->get_result();
    $total_records = $count_result->fetch_assoc()['total_records'] ?? 0;
    $count_query->close();

    // Fetch user data with pagination
    $stmt_str = "SELECT 
                  forms.created_at,
                  forms.first_name,
                  forms.last_name,
                  forms.semester,
                  forms.year_level,
                  forms.program,
                  forms.contact_number,
                  forms.honors_received,
                  forms.general_weighted_average,
                  forms.academic_year,
                  forms.email_address,
                  forms.attachment,
                  scholarship_types.scholarship_type, 
                  types.type, 
                  applications.application_id,
                  applications.status
                FROM 
                  forms
                LEFT JOIN 
                  scholarship_types ON forms.scholarship_type_id = scholarship_types.scholarship_type_id
                LEFT JOIN 
                  types ON forms.type_id = types.type_id
                LEFT JOIN 
                  applications ON forms.application_id = applications.application_id
                WHERE 
                  forms.user_id = ?";

    if (!empty($search_query)) {
      $stmt_str .= " AND (forms.first_name LIKE ? OR forms.last_name LIKE ? OR forms.email_address LIKE ?)";
    }

    $stmt_str .= " ORDER BY forms.created_at DESC LIMIT ?, ?";

    $stmt = $conn->prepare($stmt_str);

    if (!empty($search_query)) {
      $search_query_param = "%$search_query%";
      $stmt->bind_param("sssssii", $user_id, $search_query_param, $search_query_param, $search_query_param, $offset, $records_per_page);
    } else {
      $stmt->bind_param("sii", $user_id, $offset, $records_per_page);
    }

    if (!$stmt) {
      die(json_encode(['status' => 'error', 'message' => 'Data query preparation failed', 'error' => $conn->error]));
    }

    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
      $applications = array();

      while ($row = $result->fetch_assoc()) {
        // Check if application_id and status are null and set proper values
        $row['application_id'] = $row['application_id'] ?? 'No ID'; 
        $row['status'] = $row['status'] ?? 'No Status';

        // Fetch subjects for each application
        $subjects_query = $conn->prepare("SELECT subject_id, subject_code, units, name_of_instructor, grade FROM subjects WHERE form_id = ?");
        $subjects_query->bind_param("s", $row['application_id']);
        $subjects_query->execute();
        $subjects_result = $subjects_query->get_result();
        
        $subjects = array();
        while ($subject = $subjects_result->fetch_assoc()) {
          $subjects[] = $subject;
        }

        $subjects_query->close();

        // Add subjects to the application data
        $row['subjects'] = $subjects;
        $applications[] = $row;
      }

      // Pagination response
      $response['pagination'] = array(
        'current_page' => $page,
        'records_per_page' => $records_per_page,
        'total_records' => $total_records,
        'total_pages' => ceil($total_records / $records_per_page)
      );

      $response['status'] = 'success';
      $response['data'] = $applications;
      echo json_encode($response);
      return;
    } else {
      $response['status'] = 'error';
      $response['message'] = 'No applications found';
      echo json_encode($response);
      return;
    }

    $stmt->close();
  }              

  public function get_scholarship_types() {
    global $conn;
    $response = array();

    // Variables
    $filter = htmlspecialchars($_GET['filter'] ?? 'internal');

    // Validate security
    $security_key = new SecurityKey($conn);
    $security_response = $security_key->validateBearerToken();
  
    if ($security_response['status'] === 'error') {
      echo json_encode($security_response);
      return;
    }
  
    if ($security_response['role'] !== 'student' && $security_response['role'] !== 'authorized_user') {
      echo json_encode(['status' => 'error', 'message' => 'Unauthorized access']);
      return;
    }

    // Validate input
    if (empty($filter)) {
      $response['status'] = 'error';
      $response['message'] = 'Filter cannot be empty';
      echo json_encode($response);
      return;
    }

    // Fetch user data based on the user_id
    $stmt = $conn->prepare("SELECT 
                              scholarship_type_id,
                              scholarship_type,
                              category,
                              description,
                              eligibility,
                              archive,
                              created_at
                            FROM scholarship_types WHERE category = ?");
    $stmt->bind_param("s", $filter);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
      $scholarship_types = array();

      while ($row = $result->fetch_assoc()) {
        $scholarship_types[] = $row;
      }

      $response['status'] = 'success';
      $response['data'] = $scholarship_types;
      echo json_encode($response);
      return;
    } else {
      $response['status'] = 'error';
      $response['message'] = 'No scholarship types found';
      echo json_encode($response);
      return;
    }
  
    $stmt->close();
  }

  public function get_type_by_stid() {
    global $conn;
    $response = array();

    // Variables
    $scholarship_type_id = htmlspecialchars($_GET['stid'] ?? '');

    // Validate security
    $security_key = new SecurityKey($conn);
    $security_response = $security_key->validateBearerToken();
  
    if ($security_response['status'] === 'error') {
      echo json_encode($security_response);
      return;
    }
  
    if ($security_response['role'] !== 'student' && $security_response['role'] !== 'authorized_user') {
      echo json_encode(['status' => 'error', 'message' => 'Unauthorized access']);
      return;
    }

    // Validate input
    if (empty($scholarship_type_id)) {
      $response['status'] = 'error';
      $response['message'] = 'Scholarship Type ID cannot be empty';
      echo json_encode($response);
      return;
    }

    // Fetch user data based on the user_id
    $stmt = $conn->prepare("SELECT 
                              *
                            FROM types WHERE scholarship_type_id = ? AND archive IS NULL OR archive = ''");
    $stmt->bind_param("s", $scholarship_type_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
      $types = array();

      while ($row = $result->fetch_assoc()) {
        $types[] = array(
          "id" => $row['id'],
          "scholarship_type_id" => $row['scholarship_type_id'],
          "type_id" => $row['type_id'],
          "type" => $row['type'],
          "description" => nl2br($row["description"]),
          "eligibility" => nl2br($row['eligibility']),
          "archive" => $row['archive'],
          "start_date" => $row['start_date'],
          "end_date" => $row['end_date'],
          "created_at" => $row['created_at']
        );
      }

      $response['status'] = 'success';
      $response['data'] = $types;
      echo json_encode($response);
      return;
    } else {
      $response['status'] = 'error';
      $response['message'] = 'No types found';
      echo json_encode($response);
      return;
    }
  
    $stmt->close();
  }

  // Application forms
  public function insert_entrance_application() {
    global $conn;
    date_default_timezone_set('Asia/Manila');
    $response = array();
    
    // Variables
    $data = json_decode(file_get_contents("php://input"), true);
    $application_id = bin2hex(random_bytes(16));
    $user_id = htmlspecialchars($_GET['uid'] ?? '');
    $scholarship_type_id = htmlspecialchars($_GET['stid'] ?? '');
    $type_id = htmlspecialchars($_GET['tid'] ?? '');
    $first_name = htmlspecialchars($data['first_name'] ?? '');
    $middle_name = htmlspecialchars($data['middle_name'] ?? '');
    $last_name = htmlspecialchars($data['last_name'] ?? '');
    $suffix = htmlspecialchars($data['suffix'] ?? '');
    $academic_year = htmlspecialchars($data['academic_year'] ?? '');
    $year_level = htmlspecialchars($data['year_level'] ?? '');
    $semester = htmlspecialchars($data['semester'] ?? '');
    $program = htmlspecialchars($data['program'] ?? '');
    $email_address = htmlspecialchars($data['email_address'] ?? '');
    $contact_number = htmlspecialchars($data['contact_number'] ?? '');
    $honors_received = htmlspecialchars($data['honors_received'] ?? '');
    $general_weighted_average = htmlspecialchars($data['general_weighted_average'] ?? '');
    
    // Validate security
    $security_key = new SecurityKey($conn);
    $security_response = $security_key->validateBearerToken();
    
    if ($security_response['status'] === 'error') {
      echo json_encode($security_response);
      return;
    }
    
    if ($security_response['role'] !== 'student' && $security_response['role'] !== 'authorized_user') {
      echo json_encode(['status' => 'error', 'message' => 'Unauthorized access']);
      return;
    }
    
    // Validation checks
    $required_fields = [
      'uid' => $user_id,
      'stid' => $scholarship_type_id,
      'tid' => $type_id,
      'first_name' => $first_name,
      'middle_name' => $middle_name,
      'last_name' => $last_name,
      'academic_year' => $academic_year,
      'semester' => $semester,
      'program' => $program,
      'email_address' => $email_address,
      'contact_number' => $contact_number,
      'honors_received' => $honors_received,
      'general_weighted_average' => $general_weighted_average
    ];
    
    foreach ($required_fields as $field => $value) {
      if (empty($value)) {
        $response['status'] = 'error';
        $response['message'] = ucfirst(str_replace('_', ' ', $field)) . ' cannot be empty';
        echo json_encode($response);
        return;
      }
    }
  
    // Check if user_id exists in the users table
    $check_user_id = $conn->prepare("SELECT user_id, student_number, first_name, middle_name, last_name, email, department, program FROM users WHERE user_id = ?");
    $check_user_id->bind_param("s", $user_id);
    $check_user_id->execute();
    $check_user_id->store_result();
    
    if ($check_user_id->num_rows === 0) {
      $response['status'] = 'error';
      $response['message'] = 'Invalid user ID';
      echo json_encode($response);
      $check_user_id->close();
      return;
    }
  
    // Fetch user data and validate that fields are not empty or null
    $check_user_id->bind_result($db_user_id, $student_number, $db_first_name, $db_middle_name, $db_last_name, $db_email, $db_department, $db_program);
    $check_user_id->fetch();
    
    if (
      empty($student_number) || 
      empty($db_first_name) || 
      empty($db_middle_name) || 
      empty($db_last_name) || 
      empty($db_email) || 
      empty($db_department) || 
      empty($db_program)
    ) {
      $response['status'] = 'error';
      $response['message'] = 'User information is incomplete. Application cannot be submitted.';
      echo json_encode($response);
      $check_user_id->close();
      return;
    }
  
    $check_user_id->close();
  
    // Check if scholarship_type_id exists in the scholarship_types table
    $check_scholarship_type = $conn->prepare("SELECT scholarship_type_id FROM scholarship_types WHERE scholarship_type_id = ?");
    $check_scholarship_type->bind_param("s", $scholarship_type_id);
    $check_scholarship_type->execute();
    $check_scholarship_type->store_result();
  
    if ($check_scholarship_type->num_rows === 0) {
      $response['status'] = 'error';
      $response['message'] = 'Invalid scholarship type ID';
      echo json_encode($response);
      $check_scholarship_type->close();
      return;
    }
    $check_scholarship_type->close();
  
    // Check if type_id exists in the types table and if the archive field allows submission
    $check_type_id = $conn->prepare("SELECT type_id, start_date, end_date, archive FROM types WHERE type_id = ?");
    $check_type_id->bind_param("s", $type_id);
    $check_type_id->execute();
    $check_type_id->store_result();
    $check_type_id->bind_result($db_type_id, $start_date, $end_date, $archive);

    if ($check_type_id->num_rows === 0) {
      $response['status'] = 'error';
      $response['message'] = 'Invalid type ID';
      echo json_encode($response);
      $check_type_id->close();
      return;
    }

    $check_type_id->fetch();

    if (!empty($archive) || $archive === 'hide') {
      $response['status'] = 'error';
      $response['message'] = 'Application submission is closed';
      echo json_encode($response);
      $check_type_id->close();
      return;
    }

    $check_type_id->close();

    // // Validate if the user has already applied for the same type_id within the date range
    // $current_date = date('Y-m-d');

    // if ($current_date < $start_date || $current_date > $end_date) {
    //   $response['status'] = 'error';
    //   $response['message'] = 'Application period is not active.';
    //   echo json_encode($response);
    //   return;
    // }

    // $check_duplicate_application = $conn->prepare("
    //   SELECT application_id 
    //   FROM applications 
    //   WHERE user_id = ? 
    //     AND type_id = ? 
    //     AND DATE(created_at) BETWEEN ? AND ?
    // ");
    // $check_duplicate_application->bind_param("ssss", $user_id, $type_id, $start_date, $end_date);
    // $check_duplicate_application->execute();
    // $check_duplicate_application->store_result();

    // if ($check_duplicate_application->num_rows > 0) {
    //   $response['status'] = 'error';
    //   $response['message'] = 'You have already applied for this type during the active application period.';
    //   echo json_encode($response);
    //   $check_duplicate_application->close();
    //   return;
    // }

    // $check_duplicate_application->close();

    // Step 1: Validate the application period
    $current_date = date('Y-m-d');

    if ($current_date < $start_date || $current_date > $end_date) {
      $response['status'] = 'error';
      $response['message'] = 'Application period is not active.';
      echo json_encode($response);
      return;
    }

    // Step 2: Fetch the category for the provided scholarship_type_id
    $query_category = $conn->prepare("
      SELECT c.category 
      FROM categories c
      INNER JOIN scholarship_types st ON c.category_id = st.category_id
      WHERE st.scholarship_type_id = ?
    ");
    $query_category->bind_param("s", $scholarship_type_id);
    $query_category->execute();
    $query_category->bind_result($category);
    $query_category->fetch();

    if (!$category) {
      $response['status'] = 'error';
      $response['message'] = 'Category not found.';
      echo json_encode($response);
      $query_category->close();
      return;
    }
    $query_category->close();

    // Step 3: Check for duplicate applications in the same category
    $check_duplicate_application = $conn->prepare("
      SELECT a.application_id 
      FROM applications a
      INNER JOIN types t ON a.type_id = t.type_id
      INNER JOIN scholarship_types st ON t.scholarship_type_id = st.scholarship_type_id
      INNER JOIN categories c ON st.category_id = c.category_id
      WHERE a.user_id = ? 
        AND c.category = ?
        AND DATE(a.created_at) BETWEEN ? AND ?
    ");
    $check_duplicate_application->bind_param("ssss", $user_id, $category, $start_date, $end_date);
    $check_duplicate_application->execute();
    $check_duplicate_application->store_result();

    if ($check_duplicate_application->num_rows > 0) {
      $response['status'] = 'error';
      $response['message'] = 'You already applied within this type or category';
      echo json_encode($response);
      $check_duplicate_application->close();
      return;
    }

    $check_duplicate_application->close();

    // Proceed with application logic
    $response['status'] = 'success';
    $response['message'] = 'You can apply for this scholarship.';
    echo json_encode($response);

    // Insert data into `forms`
    $stmt = $conn->prepare("INSERT INTO forms (
                              user_id,
                              application_id,
                              scholarship_type_id,
                              type_id,
                              first_name,
                              middle_name,
                              last_name,
                              suffix,
                              academic_year,
                              year_level,
                              semester,
                              program,
                              email_address,
                              contact_number,
                              honors_received,
                              general_weighted_average,
                              created_at
                            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
  
    $stmt->bind_param(
      "sssssssssssssssd", 
      $user_id,
      $application_id,
      $scholarship_type_id,
      $type_id,
      $first_name,
      $middle_name,
      $last_name,
      $suffix,
      $academic_year,
      $year_level,
      $semester,
      $program,
      $email_address,
      $contact_number,
      $honors_received,
      $general_weighted_average
    );
    
    if ($stmt->execute()) {
      // Get the last inserted form ID (application_id)
      $status = 'pending';
      $created_at = date('Y-m-d H:i:s'); // Get current timestamp
      $stmt_app = $conn->prepare("INSERT INTO applications (
                                   application_id,
                                   user_id,
                                   scholarship_type_id,
                                   type_id,
                                   status,
                                   created_at
                                 ) VALUES (?, ?, ?, ?, ?, ?)");
      
      $stmt_app->bind_param("ssssss", $application_id, $user_id, $scholarship_type_id, $type_id, $status, $created_at);
      
      if ($stmt_app->execute()) {
        $response['status'] = 'success';
        $response['message'] = 'Entrance application and application record submitted successfully';
        echo json_encode($response);
      } else {
        $response['status'] = 'error';
        $response['message'] = 'Failed to insert into applications table';
        echo json_encode($response);
      }
      
      $stmt_app->close();
      return;
    } else {
      $response['status'] = 'error';
      $response['message'] = 'Failed to submit entrance application';
      echo json_encode($response);
      return;
    }
    
    $stmt->close();
  } 
  
  public function update_entrance_application() {
    global $conn;
    date_default_timezone_set('Asia/Manila'); // Correct timezone setting
    $response = array();
  
    // Variables
    $data = json_decode(file_get_contents("php://input"), true);
    $user_id = htmlspecialchars($_GET['uid'] ?? '');
    $scholarship_type_id = htmlspecialchars($_GET['stid'] ?? '');
    $type_id = htmlspecialchars($_GET['tid'] ?? '');
    $first_name = htmlspecialchars($data['first_name'] ?? '');
    $middle_name = htmlspecialchars($data['middle_name'] ?? '');
    $last_name = htmlspecialchars($data['last_name'] ?? '');
    $suffix = htmlspecialchars($data['suffix'] ?? '');
    $academic_year = htmlspecialchars($data['academic_year'] ?? '');
    $year_level = htmlspecialchars($data['year_level'] ?? '');
    $semester = htmlspecialchars($data['semester'] ?? '');
    $program = htmlspecialchars($data['program'] ?? '');
    $email_address = htmlspecialchars($data['email_address'] ?? '');
    $contact_number = htmlspecialchars($data['contact_number'] ?? '');
    $honors_received = htmlspecialchars($data['honors_received'] ?? '');
    $general_weighted_average = htmlspecialchars($data['general_weighted_average'] ?? '');
  
    // Validate security
    $security_key = new SecurityKey($conn);
    $security_response = $security_key->validateBearerToken();
  
    if ($security_response['status'] === 'error') {
      echo json_encode($security_response);
      return;
    }
  
    if ($security_response['role'] !== 'student' && $security_response['role'] !== 'authorized_user') {
      echo json_encode(['status' => 'error', 'message' => 'Unauthorized access']);
      return;
    }
  
    // Validation checks
    $required_fields = [
      'uid' => $user_id,
      'stid' => $scholarship_type_id,
      'tid' => $type_id,
      'first_name' => $first_name,
      'middle_name' => $middle_name,
      'last_name' => $last_name,
      'academic_year' => $academic_year,
      'year_level' => $year_level,
      'semester' => $semester,
      'program' => $program,
      'email_address' => $email_address,
      'contact_number' => $contact_number,
      'honors_received' => $honors_received,
      'general_weighted_average' => $general_weighted_average
    ];
  
    foreach ($required_fields as $field => $value) {
      if (empty($value)) {
        $response['status'] = 'error';
        $response['message'] = ucfirst(str_replace('_', ' ', $field)) . ' cannot be empty';
        echo json_encode($response);
        return;
      }
    }
  
    // Check if user_id exists in the users table
    $check_user_id = $conn->prepare("SELECT user_id FROM users WHERE user_id = ?");
    $check_user_id->bind_param("s", $user_id);
    $check_user_id->execute();
    $check_user_id->store_result();
  
    if ($check_user_id->num_rows === 0) {
      $response['status'] = 'error';
      $response['message'] = 'Invalid user ID';
      echo json_encode($response);
      $check_user_id->close();
      return;
    }
    $check_user_id->close();
  
    // Check if scholarship_type_id exists in the scholarship_types table
    $check_scholarship_type = $conn->prepare("SELECT scholarship_type_id FROM scholarship_types WHERE scholarship_type_id = ?");
    $check_scholarship_type->bind_param("s", $scholarship_type_id);
    $check_scholarship_type->execute();
    $check_scholarship_type->store_result();
  
    if ($check_scholarship_type->num_rows === 0) {
      $response['status'] = 'error';
      $response['message'] = 'Invalid scholarship type ID';
      echo json_encode($response);
      $check_scholarship_type->close();
      return;
    }
    $check_scholarship_type->close();
  
    // Check if type_id exists in the type table and if the archive field allows submission
    $check_type_id = $conn->prepare("SELECT type_id, archive FROM type WHERE type_id = ?");
    $check_type_id->bind_param("s", $type_id);
    $check_type_id->execute();
    $check_type_id->store_result();
    $check_type_id->bind_result($db_type_id, $archive);
  
    if ($check_type_id->num_rows === 0) {
      $response['status'] = 'error';
      $response['message'] = 'Invalid type ID';
      echo json_encode($response);
      $check_type_id->close();
      return;
    }
  
    $check_type_id->fetch();
  
    if (!empty($archive) || $archive === 'hide') {
      $response['status'] = 'error';
      $response['message'] = 'Application submission is closed';
      echo json_encode($response);
      $check_type_id->close();
      return;
    }
  
    $check_type_id->close();
  
    // Update data in `forms`
    $stmt = $conn->prepare("UPDATE forms SET
                              scholarship_type_id = ?,
                              type_id = ?,
                              first_name = ?,
                              middle_name = ?,
                              last_name = ?,
                              suffix = ?,
                              academic_year = ?,
                              year_level = ?,
                              semester = ?,
                              program = ?,
                              email_address = ?,
                              contact_number = ?,
                              honors_received = ?,
                              general_weighted_average = ?
                            WHERE user_id = ?");
  
    $stmt->bind_param(
      "sssssssssssssss",
      $scholarship_type_id,
      $type_id,
      $first_name,
      $middle_name,
      $last_name,
      $suffix,
      $academic_year,
      $year_level,
      $semester,
      $program,
      $email_address,
      $contact_number,
      $honors_received,
      $general_weighted_average,
      $user_id
    );
  
    if ($stmt->execute()) {
      $response['status'] = 'success';
      $response['message'] = 'Entrance application updated successfully';
      echo json_encode($response);
      return;
    } else {
      $response['status'] = 'error';
      $response['message'] = 'Failed to update entrance application';
      echo json_encode($response);
      return;
    }
  
    $stmt->close();
  }  

  // Deans List
  public function insert_deans_list() {
    global $conn;
    date_default_timezone_set('Asia/Manila');
    $response = array();
  
    // Decode input JSON
    $data = json_decode(file_get_contents("php://input"), true);
  
    // Extract and sanitize variables
    $user_id = htmlspecialchars($_GET['uid'] ?? '');
    $application_id = bin2hex(random_bytes(16));
    $scholarship_type_id = htmlspecialchars($_GET['stid'] ?? '');
    $type_id = htmlspecialchars($_GET['tid'] ?? '');
    $semester = htmlspecialchars($data['semester'] ?? '');
    $academic_year = htmlspecialchars($data['academic_year'] ?? '');
    $first_name = htmlspecialchars($data['first_name'] ?? '');
    $middle_name = htmlspecialchars($data['middle_name'] ?? ''); // Optional
    $last_name = htmlspecialchars($data['last_name'] ?? '');
    $suffix = htmlspecialchars($data['suffix'] ?? ''); // Optional
    $year_level = htmlspecialchars($data['year_level'] ?? '');
    $program = htmlspecialchars($data['program'] ?? '');
    $email_address = htmlspecialchars($data['email_address'] ?? '');
    $contact_number = htmlspecialchars($data['contact_number'] ?? '');
    $subjects = $data['subjects'] ?? [];
    $date_applied = date('Y-m-d');
  
    // Security validation
    $security_key = new SecurityKey($conn);
    $security_response = $security_key->validateBearerToken();
  
    if ($security_response['status'] === 'error') {
      echo json_encode($security_response);
      return;
    }
  
    if (!in_array($security_response['role'], ['student', 'authorized_user'])) {
      echo json_encode(['status' => 'error', 'message' => 'Unauthorized access']);
      return;
    }
  
    // Basic validation
    $required_fields = [
      'uid' => $user_id,
      'stid' => $scholarship_type_id,
      'tid' => $type_id,
      'semester' => $semester,
      'academic_year' => $academic_year,
      'first_name' => $first_name,
      'last_name' => $last_name,
      'year_level' => $year_level,
      'program' => $program,
      'email_address' => $email_address,
      'contact_number' => $contact_number
    ];
  
    foreach ($required_fields as $field => $value) {
      if (empty($value)) {
        $response['status'] = 'error';
        $response['message'] = ucfirst(str_replace('_', ' ', $field)) . ' cannot be empty';
        echo json_encode($response);
        return;
      }
    }
  
    // Check if user_id exists in the users table
    $check_user_id = $conn->prepare("SELECT user_id, student_number, first_name, middle_name, last_name, email, department, program FROM users WHERE user_id = ?");
    $check_user_id->bind_param("s", $user_id);
    $check_user_id->execute();
    $check_user_id->store_result();
    
    if ($check_user_id->num_rows === 0) {
      $response['status'] = 'error';
      $response['message'] = 'Invalid user ID';
      echo json_encode($response);
      $check_user_id->close();
      return;
    }
  
    // Fetch user data and validate that fields are not empty or null
    $check_user_id->bind_result($db_user_id, $student_number, $db_first_name, $db_middle_name, $db_last_name, $db_email, $db_department, $db_program);
    $check_user_id->fetch();
    
    if (
      empty($student_number) || 
      empty($db_first_name) || 
      empty($db_middle_name) || 
      empty($db_last_name) || 
      empty($db_email) || 
      empty($db_department) || 
      empty($db_program)
    ) {
      $response['status'] = 'error';
      $response['message'] = 'User information is incomplete. Application cannot be submitted.';
      echo json_encode($response);
      $check_user_id->close();
      return;
    }
  
    $check_user_id->close();

    // Check if scholarship_type_id exists in the scholarship_types table
    $check_scholarship_type = $conn->prepare("SELECT scholarship_type_id FROM scholarship_types WHERE scholarship_type_id = ?");
    $check_scholarship_type->bind_param("s", $scholarship_type_id);
    $check_scholarship_type->execute();
    $check_scholarship_type->store_result();
  
    if ($check_scholarship_type->num_rows === 0) {
      $response['status'] = 'error';
      $response['message'] = 'Invalid scholarship type ID';
      echo json_encode($response);
      $check_scholarship_type->close();
      return;
    }
    $check_scholarship_type->close();
  
    // Check if type_id exists in the types table and if the archive field allows submission
    $check_type_id = $conn->prepare("SELECT type_id, start_date, end_date, archive FROM types WHERE type_id = ?");
    $check_type_id->bind_param("s", $type_id);
    $check_type_id->execute();
    $check_type_id->store_result();
    $check_type_id->bind_result($db_type_id, $start_date, $end_date, $archive);

    if ($check_type_id->num_rows === 0) {
      $response['status'] = 'error';
      $response['message'] = 'Invalid type ID';
      echo json_encode($response);
      $check_type_id->close();
      return;
    }

    $check_type_id->fetch();

    if (!empty($archive) || $archive === 'hide') {
      $response['status'] = 'error';
      $response['message'] = 'Application submission is closed';
      echo json_encode($response);
      $check_type_id->close();
      return;
    }

    $check_type_id->close();

    // Validate if the user has already applied for the same type_id within the date range
    $current_date = date('Y-m-d');

    if ($current_date < $start_date || $current_date > $end_date) {
      $response['status'] = 'error';
      $response['message'] = 'Application period is not active.';
      echo json_encode($response);
      return;
    }

    $check_duplicate_application = $conn->prepare("
      SELECT application_id 
      FROM applications 
      WHERE user_id = ? 
        AND type_id = ? 
        AND DATE(created_at) BETWEEN ? AND ?
    ");
    $check_duplicate_application->bind_param("ssss", $user_id, $type_id, $start_date, $end_date);
    $check_duplicate_application->execute();
    $check_duplicate_application->store_result();

    if ($check_duplicate_application->num_rows > 0) {
      $response['status'] = 'error';
      $response['message'] = 'You have already applied for this type during the active application period.';
      echo json_encode($response);
      $check_duplicate_application->close();
      return;
    }

    $check_duplicate_application->close();
  
    // Insert into `forms`
    $stmt = $conn->prepare("INSERT INTO forms (
      user_id, application_id, scholarship_type_id, type_id, semester, academic_year,
      first_name, middle_name, last_name, suffix, year_level,
      program, email_address, contact_number, created_at
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
  
    $stmt->bind_param(
      "sssssssssssssss",
      $user_id, $application_id, $scholarship_type_id, $type_id, $semester, $academic_year,
      $first_name, $middle_name, $last_name, $suffix, $year_level,
      $program, $email_address, $contact_number, $date_applied
    );
  
    if ($stmt->execute()) {
      $status = 'pending';
      $created_at = date('Y-m-d H:i:s'); // Get current timestamp
      $stmt_app = $conn->prepare("INSERT INTO applications (
                                   application_id,
                                   user_id,
                                   scholarship_type_id,
                                   type_id,
                                   status,
                                   created_at
                                 ) VALUES (?, ?, ?, ?, ?, ?)");
      
      $stmt_app->bind_param("ssssss", $application_id, $user_id, $scholarship_type_id, $type_id, $status, $created_at);
      
      if ($stmt_app->execute()) {
        // $response['status'] = 'success';
        // $response['message'] = 'Entrance application and application record submitted successfully';
        // echo json_encode($response);
      } else {
        // $response['status'] = 'error';
        // $response['message'] = 'Failed to insert into applications table';
        // echo json_encode($response);
      }
      
      $stmt_app->close();
  
      // Insert subjects
      foreach ($subjects as $subject) {
        $subject_id = bin2hex(random_bytes(8));
        $subject_code = htmlspecialchars($subject['subject_code'] ?? '');
        $units = htmlspecialchars($subject['units'] ?? '');
        $name_of_instructor = htmlspecialchars($subject['name_of_instructor'] ?? '');
        $grade = htmlspecialchars($subject['grade'] ?? '');
  
        if (empty($subject_code) || empty($units) || empty($name_of_instructor) || empty($grade)) {
          $response['status'] = 'error';
          $response['message'] = 'Subject fields cannot be empty';
          echo json_encode($response);
          return;
        }
  
        $subject_stmt = $conn->prepare("INSERT INTO subjects (form_id, subject_id, subject_code, units, name_of_instructor, grade) VALUES (?, ?, ?, ?, ?, ?)");
        $subject_stmt->bind_param("ssssss", $application_id, $subject_id, $subject_code, $units, $name_of_instructor, $grade);
        $subject_stmt->execute();
        $subject_stmt->close();
      }
  
      $response['status'] = 'success';
      $response['message'] = 'Dean\'s List application submitted successfully';
    } else {
      $response['status'] = 'error';
      $response['message'] = 'Unable to submit application';
    }
  
    echo json_encode($response);
  }  

  // form attachment (temporarily)
  public function insert_form_attachment() {
    global $conn;
    date_default_timezone_set('Asia/Manila');
    $response = array();
  
    $application_id = bin2hex(random_bytes(16));
    $user_id = htmlspecialchars($_GET['uid'] ?? '');
    $type_id = htmlspecialchars($_GET['tid'] ?? '');
    $scholarship_type_id = htmlspecialchars($_GET['stid'] ?? '');
    $attachment = $_FILES['attachment'] ?? null;
    $created_at = date('Y-m-d H:i:s');
  
    // Security validation
    $security_key = new SecurityKey($conn);
    $security_response = $security_key->validateBearerToken();
  
    if ($security_response['status'] === 'error') {
      echo json_encode($security_response);
      return;
    }
  
    if (!in_array($security_response['role'], ['student', 'authorized_user'])) {
      echo json_encode(['status' => 'error', 'message' => 'Unauthorized access']);
      return;
    }
  
    if (empty($user_id)) {
      $response['status'] = 'error';
      $response['message'] = 'User ID cannot be empty';
      echo json_encode($response);
      return;
    }
  
    if (empty($scholarship_type_id)) {
      $response['status'] = 'error';
      $response['message'] = 'Scholarship Type ID cannot be empty';
      echo json_encode($response);
      return;
    }
  
    if (empty($type_id)) {
      $response['status'] = 'error';
      $response['message'] = 'Type ID cannot be empty';
      echo json_encode($response);
      return;
    }
  
    if (empty($attachment) || $attachment['error'] !== UPLOAD_ERR_OK) {
      $response['status'] = 'error';
      $response['message'] = 'Attachment file is required and must be uploaded correctly';
      echo json_encode($response);
      return;
    }
  
    // Check if the uploaded file is a PDF
    $file_extension = strtolower(pathinfo($attachment['name'], PATHINFO_EXTENSION));
    if ($file_extension !== 'pdf') {
      $response['status'] = 'error';
      $response['message'] = 'Only PDF files are allowed';
      echo json_encode($response);
      return;
    }
  
    // Check if user_id exists in the users table
    $check_user_id = $conn->prepare("SELECT user_id, student_number, first_name, middle_name, last_name, email, department, program FROM users WHERE user_id = ?");
    $check_user_id->bind_param("s", $user_id);
    $check_user_id->execute();
    $check_user_id->store_result();
  
    if ($check_user_id->num_rows === 0) {
      $response['status'] = 'error';
      $response['message'] = 'Invalid user ID';
      echo json_encode($response);
      $check_user_id->close();
      return;
    }
  
    // Fetch user data and validate that fields are not empty or null
    $check_user_id->bind_result($db_user_id, $student_number, $db_first_name, $db_middle_name, $db_last_name, $db_email, $db_department, $db_program);
    $check_user_id->fetch();
  
    if (
      empty($student_number) || 
      empty($db_first_name) || 
      empty($db_middle_name) || 
      empty($db_last_name) || 
      empty($db_email) || 
      empty($db_department) || 
      empty($db_program)
    ) {
      $response['status'] = 'error';
      $response['message'] = 'User information is incomplete. Application cannot be submitted.';
      echo json_encode($response);
      $check_user_id->close();
      return;
    }
  
    $check_user_id->close();
  
    // Check if scholarship_type_id exists in the scholarship_types table
    $check_scholarship_type = $conn->prepare("SELECT scholarship_type_id FROM scholarship_types WHERE scholarship_type_id = ?");
    $check_scholarship_type->bind_param("s", $scholarship_type_id);
    $check_scholarship_type->execute();
    $check_scholarship_type->store_result();
  
    if ($check_scholarship_type->num_rows === 0) {
      $response['status'] = 'error';
      $response['message'] = 'Invalid scholarship type ID';
      echo json_encode($response);
      $check_scholarship_type->close();
      return;
    }
    $check_scholarship_type->close();
  
    // Check if type_id exists in the types table and if the archive field allows submission
    $check_type_id = $conn->prepare("SELECT type_id, start_date, end_date, archive FROM types WHERE type_id = ?");
    $check_type_id->bind_param("s", $type_id);
    $check_type_id->execute();
    $check_type_id->store_result();
    $check_type_id->bind_result($db_type_id, $start_date, $end_date, $archive);
  
    if ($check_type_id->num_rows === 0) {
      $response['status'] = 'error';
      $response['message'] = 'Invalid type ID';
      echo json_encode($response);
      $check_type_id->close();
      return;
    }
  
    $check_type_id->fetch();
  
    if (!empty($archive) || $archive === 'hide') {
      $response['status'] = 'error';
      $response['message'] = 'Application submission is closed';
      echo json_encode($response);
      $check_type_id->close();
      return;
    }
  
    $check_type_id->close();
  
    // Validate if the user has already applied for the same type_id within the date range
    $current_date = date('Y-m-d');
  
    if ($current_date < $start_date || $current_date > $end_date) {
      $response['status'] = 'error';
      $response['message'] = 'Application period is not active.';
      echo json_encode($response);
      return;
    }
  
    $check_duplicate_application = $conn->prepare("
      SELECT application_id 
      FROM applications 
      WHERE user_id = ? 
        AND type_id = ? 
        AND DATE(created_at) BETWEEN ? AND ?
    ");
    $check_duplicate_application->bind_param("ssss", $user_id, $type_id, $start_date, $end_date);
    $check_duplicate_application->execute();
    $check_duplicate_application->store_result();
  
    if ($check_duplicate_application->num_rows > 0) {
      $response['status'] = 'error';
      $response['message'] = 'You have already applied for this type during the active application period.';
      echo json_encode($response);
      $check_duplicate_application->close();
      return;
    }
  
    $check_duplicate_application->close();
  
    // Handle file upload with new name format
    $upload_dir = 'uploads/';
    $timestamp = time();
    $attachment_name = "application-{$type_id}-{$timestamp}.pdf";
    $attachment_path = $upload_dir . $attachment_name;
  
    if (!move_uploaded_file($attachment['tmp_name'], $attachment_path)) {
      $response['status'] = 'error';
      $response['message'] = 'Failed to upload attachment';
      echo json_encode($response);
      return;
    }
  
    // Insert into `forms`
    $stmt = $conn->prepare("INSERT INTO forms (
                              user_id,
                              application_id,
                              scholarship_type_id,
                              type_id,
                              program,
                              attachment,
                              created_at
                            ) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssssss", $user_id, $application_id, $scholarship_type_id, $type_id, $db_program, $attachment_path, $created_at);
  
    if ($stmt->execute()) {
      // Insert into `applications`
      $status = 'pending';
      $stmt_app = $conn->prepare("INSERT INTO applications (
        application_id,
        user_id,
        scholarship_type_id,
        type_id,
        status,
        created_at
      ) VALUES (?, ?, ?, ?, ?, ?)");
      $stmt_app->bind_param("ssssss", $application_id, $user_id, $scholarship_type_id, $type_id, $status, $created_at);
      $stmt_app->execute();
      $stmt_app->close();
  
      $response['status'] = 'success';
      $response['message'] = 'Application submitted successfully';
      echo json_encode($response);
      return;
    } else {
      $response['status'] = 'error';
      $response['message'] = 'Unable to submit application';
    }
  
    echo json_encode($response);
  }    

  // Department
  public function get_department() {
    global $conn;
    date_default_timezone_set('Asia/Manila');
    $response = array();
  
    // Create a new instance for security key
    $security_key = new SecurityKey($conn);
    $security_response = $security_key->validateBearerToken();
  
    if ($security_response['status'] === 'error') {
      echo json_encode($security_response);
      return;
    }
  
    // Check if the user's role is 'student'
    if ($security_response['role'] !== 'student') {
      echo json_encode(['status' => 'error', 'message' => 'Unauthorized access']);
      return;
    }
  
    // Get the current page, number of records per page, and search query from the request
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $records_per_page = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;
    $search_query = htmlspecialchars($_GET['search'] ?? '');
  
    // Calculate the starting record for the query
    $offset = ($page - 1) * $records_per_page;
  
    // Adjust the SQL query for searching
    $query = "SELECT department_id, department_code, department_name, created_at 
              FROM departments";
    $count_query = "SELECT COUNT(*) as total FROM departments";
  
    // Append search condition if a search query is provided
    if (!empty($search_query)) {
      $search_term = '%' . $search_query . '%';
      $query .= " WHERE department_code LIKE ? OR department_name LIKE ?";
      $count_query .= " WHERE department_code LIKE ? OR department_name LIKE ?";
    }
  
    $query .= " ORDER BY id DESC LIMIT ?, ?";
  
    // Prepare and bind parameters for the main query
    $stmt = $conn->prepare($query);
    $count_stmt = $conn->prepare($count_query);
  
    if (!empty($search_query)) {
      // Search query is provided
      $stmt->bind_param("ssii", $search_term, $search_term, $offset, $records_per_page);
      $count_stmt->bind_param("ss", $search_term, $search_term);
    } else {
      // No search query, bind only LIMIT parameters
      $stmt->bind_param("ii", $offset, $records_per_page);
      // No bind_param for count_stmt, as no parameters are needed
    }
  
    $stmt->execute();
    $result = $stmt->get_result();
  
    // Get total number of records for pagination info
    $count_stmt->execute();
    $count_result = $count_stmt->get_result();
    $count_row = $count_result->fetch_assoc();
    $total_records = $count_row['total'];
  
    if ($result->num_rows > 0) {
      $departments = array();
  
      while ($row = $result->fetch_assoc()) {
        $departments[] = $row;
      }
  
      $response['status'] = 'success';
      $response['data'] = $departments;
      $response['pagination'] = array(
        'current_page' => $page,
        'records_per_page' => $records_per_page,
        'total_records' => $total_records,
        'total_pages' => ceil($total_records / $records_per_page)
      );
    } else {
      $response['status'] = 'error';
      $response['message'] = 'No departments found';
    }
  
    $stmt->close();
    $count_stmt->close();
    echo json_encode($response);
  }

  // Program
  public function get_program() {
    global $conn;
    date_default_timezone_set('Asia/Manila');
    $response = array();
  
    // Create a new instance for security key
    $security_key = new SecurityKey($conn);
    $security_response = $security_key->validateBearerToken();
  
    if ($security_response['status'] === 'error') {
      echo json_encode($security_response);
      return;
    }
  
    // Check if the user's role is 'student'
    if ($security_response['role'] !== 'student') {
      echo json_encode(['status' => 'error', 'message' => 'Unauthorized access']);
      return;
    }
  
    // Get the current page, number of records per page, and search query from the request
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $records_per_page = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;
    $search_query = htmlspecialchars($_GET['search'] ?? '');
  
    // Calculate the starting record for the query
    $offset = ($page - 1) * $records_per_page;
  
    // Adjust the SQL query for searching
    $query = "SELECT p.program_id, p.department_id, p.program_code, p.program_name, 
                     p.created_at, d.department_name 
              FROM programs p
              LEFT JOIN departments d ON p.department_id = d.department_id ORDER BY p.id DESC";
    $count_query = "SELECT COUNT(*) as total 
                    FROM programs p
                    LEFT JOIN departments d ON p.department_id = d.department_id";
  
    // Append search condition if a search query is provided
    if (!empty($search_query)) {
      $search_term = '%' . $search_query . '%';
      $query .= " WHERE p.program_code LIKE ? OR p.program_name LIKE ? 
                  OR d.department_name LIKE ?";
      $count_query .= " WHERE p.program_code LIKE ? OR p.program_name LIKE ? 
                        OR d.department_name LIKE ?";
    }
  
    $query .= " LIMIT ?, ?";
  
    // Prepare and bind parameters for the main query
    $stmt = $conn->prepare($query);
    $count_stmt = $conn->prepare($count_query);
  
    if (!empty($search_query)) {
      // Search query is provided
      $stmt->bind_param("sssii", $search_term, $search_term, $search_term, $offset, $records_per_page);
      $count_stmt->bind_param("sss", $search_term, $search_term, $search_term);
    } else {
      // No search query, bind only LIMIT parameters
      $stmt->bind_param("ii", $offset, $records_per_page);
    }
  
    $stmt->execute();
    $result = $stmt->get_result();
  
    // Get total number of records for pagination info
    $count_stmt->execute();
    $count_result = $count_stmt->get_result();
    $count_row = $count_result->fetch_assoc();
    $total_records = $count_row['total'];
  
    if ($result->num_rows > 0) {
      $programs = array();
  
      while ($row = $result->fetch_assoc()) {
        $programs[] = $row;
      }
  
      $response['status'] = 'success';
      $response['data'] = $programs;
      $response['pagination'] = array(
        'current_page' => $page,
        'records_per_page' => $records_per_page,
        'total_records' => $total_records,
        'total_pages' => ceil($total_records / $records_per_page)
      );
    } else {
      $response['status'] = 'error';
      $response['message'] = 'No programs found';
    }
  
    $stmt->close();
    $count_stmt->close();
    echo json_encode($response);
  }
}

?>