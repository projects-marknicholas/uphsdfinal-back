<?php
class AdminController{
  // Scholarship Type
  public function add_scholarship_type(){
    global $conn;
    date_default_timezone_set('Asia/Manila');
    $response = array();

    $data = json_decode(file_get_contents("php://input"), true);
    $scholarship_type_id = bin2hex(random_bytes(16));
    $scholarship_type = htmlspecialchars($data['scholarship_type'] ?? '');
    $category = htmlspecialchars($data['category'] ?? '');
    $description = htmlspecialchars($data['description'] ?? '');
    $eligibility = htmlspecialchars($data['eligibility'] ?? '');
    $created_at = date('Y-m-d H:i:s');

    // Create a new instance for security key
    $security_key = new SecurityKey($conn);
    $security_response = $security_key->validateBearerToken();

    if ($security_response['status'] === 'error') {
      echo json_encode($security_response);
      return;
    }

    // Check if the user's role is 'admin'
    if ($security_response['role'] !== 'admin') {
      echo json_encode(['status' => 'error', 'message' => 'Unauthorized access']);
      return;
    }

    if(empty($scholarship_type)){
      $response['status'] = 'error';
      $response['message'] = 'Scholarship type cannot be empty';
      echo json_encode($response);
      return;
    }

    if(empty($category)){
      $response['status'] = 'error';
      $response['message'] = 'Category cannot be empty';
      echo json_encode($response);
      return;
    }

    if(empty($description)){
      $response['status'] = 'error';
      $response['message'] = 'Description cannot be empty';
      echo json_encode($response);
      return;
    }

    if(empty($eligibility)){
      $response['status'] = 'error';
      $response['message'] = 'Eligibility cannot be empty';
      echo json_encode($response);
      return;
    }

    // Check if the scholarship type already exists
    $lowered_st = strtolower($scholarship_type);
    $stmt = $conn->prepare("SELECT scholarship_type FROM scholarship_types WHERE scholarship_type = ?");
    $stmt->bind_param("s", $lowered_st);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
      $stmt->close();
      $response['status'] = 'error';
      $response['message'] = 'This scholarship type already exists';
      echo json_encode($response);
      return;
    }

    $stmt->close();

    // Insert data
    $stmt = $conn->prepare('INSERT INTO scholarship_types (scholarship_type_id, scholarship_type, category, description, eligibility, created_at) VALUES (?, ?, ?, ?, ?, ?)');
    $stmt->bind_param('ssssss', $scholarship_type_id, $scholarship_type, $category, $description, $eligibility, $created_at);
    
    if ($stmt->execute()){
      $response['status'] = 'success';
      $response['message'] = 'Scholarship type created successfully';

      // Log the activity
      $activityLogger = new ActivityLogger($conn);
      $logResponse = $activityLogger->logActivity(
        $security_response['user_id'],         
        'scholarship type',          
        'added a scholarship type',      
        'Added new scholarship type: ' . $scholarship_type 
      );
      
      // Handle the logging response
      if ($logResponse['status'] === 'error') {
        $response['activity_log'] = $logResponse['message'];
      } else {
        $response['activity_log'] = 'Activity logged successfully';
      }

      echo json_encode($response);
      return;
    } else{
      $response['status'] = 'error';
      $response['message'] = 'Error creating scholarship type: ' . $conn->error;
      echo json_encode($response);
      return;
    }
  }
  
  public function get_scholarship_type() {
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
    
    // Check if the user's role is 'admin'
    if ($security_response['role'] !== 'admin') {
      echo json_encode(['status' => 'error', 'message' => 'Unauthorized access']);
      return;
    }
    
    // Get the current page and the number of records per page from the request
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $records_per_page = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;
    
    // Calculate the starting record for the query
    $offset = ($page - 1) * $records_per_page;
    
    // Fetch scholarship types with pagination
    $stmt = $conn->prepare("
      SELECT scholarship_type_id, scholarship_type, category, 
           description, eligibility, archive, created_at
      FROM scholarship_types 
      ORDER BY created_at DESC
      LIMIT ?, ?
    ");
    
    if (!$stmt) {
      echo json_encode(['status' => 'error', 'message' => 'SQL error: ' . $conn->error]);
      return;
    }
  
    $stmt->bind_param("ii", $offset, $records_per_page);
    $stmt->execute();
    $result = $stmt->get_result();
    
    // Get total number of records for pagination info
    $total_stmt = $conn->prepare("SELECT COUNT(*) as total FROM scholarship_types");
    
    if (!$total_stmt) {
      echo json_encode(['status' => 'error', 'message' => 'SQL error: ' . $conn->error]);
      return;
    }
    
    $total_stmt->execute();
    $total_result = $total_stmt->get_result();
    $total_row = $total_result->fetch_assoc();
    $total_records = $total_row['total'];
    
    if ($result->num_rows > 0) {
      $scholarship_types = array();
    
      while ($row = $result->fetch_assoc()) {
        // Fetch associated types for each scholarship type
        $type_id = $row['scholarship_type_id'];
        $type_stmt = $conn->prepare("SELECT type_id, type, description, eligibility, archive, start_date, end_date, created_at 
                        FROM types 
                        WHERE scholarship_type_id = ?");
        if ($type_stmt) {
          $type_stmt->bind_param("s", $type_id);
          $type_stmt->execute();
          $type_result = $type_stmt->get_result();
  
          // Construct type_list
          $type_list = array();
          while ($type_row = $type_result->fetch_assoc()) {
            $type_list[] = $type_row;
          }
  
          // Add type_list and count to the current scholarship type
          $row['type_list'] = $type_list;
          $row['type'] = count($type_list); // Count of associated types
          $type_stmt->close();
        } else {
          // Handle error in fetching types
          $row['type_list'] = [];
          $row['type'] = 0; // No types found
        }
  
        $scholarship_types[] = $row;
      }
    
      $response['status'] = 'success';
      $response['data'] = $scholarship_types;
      $response['pagination'] = array(
        'current_page' => $page,
        'records_per_page' => $records_per_page,
        'total_records' => $total_records,
        'total_pages' => ceil($total_records / $records_per_page)
      );
    } else {
      $response['status'] = 'error';
      $response['message'] = 'No scholarship types found';
    }
    
    $stmt->close();
    $total_stmt->close();
    echo json_encode($response);
  }

  public function update_scholarship_type() {
    global $conn;
    date_default_timezone_set('Asia/Manila');
    $response = array();
  
    $data = json_decode(file_get_contents("php://input"), true);
    $scholarship_type_id = htmlspecialchars($_GET['stid'] ?? '');
    $scholarship_type = htmlspecialchars($data['scholarship_type'] ?? '');
    $category = htmlspecialchars($data['category'] ?? '');
    $description = htmlspecialchars($data['description'] ?? '');
    $eligibility = htmlspecialchars($data['eligibility'] ?? '');
  
    // Create a new instance for security key
    $security_key = new SecurityKey($conn);
    $security_response = $security_key->validateBearerToken();
  
    if ($security_response['status'] === 'error') {
      echo json_encode($security_response);
      return;
    }

    // Check if the user's role is 'admin'
    if ($security_response['role'] !== 'admin') {
      echo json_encode(['status' => 'error', 'message' => 'Unauthorized access']);
      return;
    }
  
    if (empty($scholarship_type_id)) {
      $response['status'] = 'error';
      $response['message'] = 'Scholarship type ID cannot be empty';
      echo json_encode($response);
      return;
    }
  
    // Check if the scholarship type does not exist
    $lowered_st = strtolower($scholarship_type);
    $stmt = $conn->prepare("SELECT scholarship_type FROM scholarship_types WHERE scholarship_type = ? AND scholarship_type_id != ?");
    $stmt->bind_param("ss", $lowered_st, $scholarship_type_id);
    $stmt->execute();
    $result = $stmt->get_result();
  
    // Check if no records were found
    if ($result->num_rows > 0) {
      $stmt->close();
      $response['status'] = 'error';
      $response['message'] = 'This scholarship type already exists';
      echo json_encode($response);
      return;
    }
  
    $stmt->close();
  
    if (empty($scholarship_type)) {
      $response['status'] = 'error';
      $response['message'] = 'Scholarship type cannot be empty';
      echo json_encode($response);
      return;
    }

    if (empty($category)) {
      $response['status'] = 'error';
      $response['message'] = 'Category cannot be empty';
      echo json_encode($response);
      return;
    }
  
    if (empty($description)) {
      $response['status'] = 'error';
      $response['message'] = 'Description cannot be empty';
      echo json_encode($response);
      return;
    }
  
    if (empty($eligibility)) {
      $response['status'] = 'error';
      $response['message'] = 'Eligibility cannot be empty';
      echo json_encode($response);
      return;
    }
  
    // Update the scholarship type
    $stmt = $conn->prepare('UPDATE scholarship_types SET scholarship_type = ?, category = ?, description = ?, eligibility = ? WHERE scholarship_type_id = ?');
    $stmt->bind_param('sssss', $scholarship_type, $category, $description, $eligibility, $scholarship_type_id);
  
    if ($stmt->execute()) {
      $response['status'] = 'success';
      $response['message'] = 'Scholarship type updated successfully';

      // Log the activity
      $activityLogger = new ActivityLogger($conn);
      $logResponse = $activityLogger->logActivity(
        $security_response['user_id'],         
        'scholarship type',          
        'updated a scholarship type',      
        'Updated the scholarship type: ' . $scholarship_type 
      );
      
      // Handle the logging response
      if ($logResponse['status'] === 'error') {
        $response['activity_log'] = $logResponse['message'];
      } else {
        $response['activity_log'] = 'Activity logged successfully';
      }

      echo json_encode($response);
      return;
    } else {
      $response['status'] = 'error';
      $response['message'] = 'Error updating scholarship type: ' . $conn->error;
      echo json_encode($response);
      return;
    }
  }  

  public function delete_scholarship_type() {
    global $conn;
    date_default_timezone_set('Asia/Manila');
    $response = array();
  
    // Extract scholarship type ID
    $scholarship_type_id = htmlspecialchars($_GET['stid'] ?? '');
  
    // Create a new instance for security key
    $security_key = new SecurityKey($conn);
    $security_response = $security_key->validateBearerToken();
  
    if ($security_response['status'] === 'error') {
      echo json_encode($security_response);
      return;
    }

    // Check if the user's role is 'admin'
    if ($security_response['role'] !== 'admin') {
      echo json_encode(['status' => 'error', 'message' => 'Unauthorized access']);
      return;
    }
  
    if (empty($scholarship_type_id)) {
      $response['status'] = 'error';
      $response['message'] = 'Scholarship type ID cannot be empty';
      echo json_encode($response);
      return;
    }
  
    // Check if the scholarship type exists
    $stmt = $conn->prepare("SELECT scholarship_type_id FROM scholarship_types WHERE scholarship_type_id = ?");
    $stmt->bind_param("s", $scholarship_type_id);
    $stmt->execute();
    $result = $stmt->get_result();
  
    if ($result->num_rows === 0) {
      $stmt->close();
      $response['status'] = 'error';
      $response['message'] = 'This scholarship type does not exist';
      echo json_encode($response);
      return;
    }
  
    $stmt->close();
  
    // Delete the scholarship type
    $stmt = $conn->prepare('DELETE FROM scholarship_types WHERE scholarship_type_id = ?');
    $stmt->bind_param('s', $scholarship_type_id);
  
    if ($stmt->execute()) {
      $response['status'] = 'success';
      $response['message'] = 'Scholarship type deleted successfully';
      echo json_encode($response);
      return;
    } else {
      $response['status'] = 'error';
      $response['message'] = 'Error deleting scholarship type: ' . $conn->error;
      echo json_encode($response);
      return;
    }
  }  

  public function hide_scholarship_archive() {
    global $conn;
    date_default_timezone_set('Asia/Manila');
    $response = array();

    // Extract scholarship type ID and archive status
    $scholarship_type_id = htmlspecialchars($_GET['stid'] ?? '');
    $archive = htmlspecialchars($_GET['archive'] ?? ''); // Expecting 'true' or 'false'

    // Create a new instance for security key
    $security_key = new SecurityKey($conn);
    $security_response = $security_key->validateBearerToken();

    if ($security_response['status'] === 'error') {
      echo json_encode($security_response);
      return;
    }

    // Check if the user's role is 'admin'
    if ($security_response['role'] !== 'admin') {
      echo json_encode(['status' => 'error', 'message' => 'Unauthorized access']);
      return;
    }

    if (empty($scholarship_type_id)) {
      $response['status'] = 'error';
      $response['message'] = 'Scholarship type ID cannot be empty';
      echo json_encode($response);
      return;
    }

    // Validate archive value
    if ($archive !== '' && $archive !== 'hide') {
      $response['status'] = 'error';
      $response['message'] = 'Invalid archive value. It must be either "true" or "false".';
      echo json_encode($response);
      return;
    }

    // Update the archive status in the scholarship_types table for the specified scholarship_type_id
    $stmt = $conn->prepare('UPDATE scholarship_types SET archive = ? WHERE scholarship_type_id = ?');
    $stmt->bind_param('ss', $archive, $scholarship_type_id); // Assuming scholarship_type_id is an integer

    if ($stmt->execute()) {
      $response['status'] = 'success';
      $response['message'] = 'Archive status updated successfully in the scholarship_types table';
    } else {
      $response['status'] = 'error';
      $response['message'] = 'Error updating archive status in the scholarship_types table: ' . $conn->error;
    }

    // Update the archive status in the types table for all records based on scholarship_type_id
    $stmt = $conn->prepare('UPDATE types SET archive = ? WHERE scholarship_type_id = ?');
    $stmt->bind_param('si', $archive, $scholarship_type_id);

    if ($stmt->execute()) {
      $response['status'] = 'success';
      $response['message'] = 'Archive status updated successfully in the types table';
    } else {
      $response['status'] = 'error';
      $response['message'] = 'Error updating archive status in the types table: ' . $conn->error;
    }

    $stmt->close();
    echo json_encode($response);
  }

  // Type
  public function add_type(){
    global $conn;
    date_default_timezone_set('Asia/Manila');
    $response = array();

    $data = json_decode(file_get_contents("php://input"), true);
    $type_id = bin2hex(random_bytes(16));
    $scholarship_type_id = htmlspecialchars($data['scholarship_type_id'] ?? '');
    $type = htmlspecialchars($data['type'] ?? '');
    $description = htmlspecialchars($data['description'] ?? '');
    $eligibility = htmlspecialchars($data['eligibility'] ?? '');
    $created_at = date('Y-m-d H:i:s');

    // Create a new instance for security key
    $security_key = new SecurityKey($conn);
    $security_response = $security_key->validateBearerToken();

    if ($security_response['status'] === 'error') {
      echo json_encode($security_response);
      return;
    }

    // Check if the user's role is 'admin'
    if ($security_response['role'] !== 'admin') {
      echo json_encode(['status' => 'error', 'message' => 'Unauthorized access']);
      return;
    }

    // Validate required fields
    if (empty($scholarship_type_id)) {
      $response['status'] = 'error';
      $response['message'] = 'Scholarship type ID cannot be empty';
      echo json_encode($response);
      return;
    }

    if (empty($type)) {
      $response['status'] = 'error';
      $response['message'] = 'Type cannot be empty';
      echo json_encode($response);
      return;
    }

    if (empty($description)) {
      $response['status'] = 'error';
      $response['message'] = 'Description cannot be empty';
      echo json_encode($response);
      return;
    }

    if (empty($eligibility)) {
      $response['status'] = 'error';
      $response['message'] = 'Eligibility cannot be empty';
      echo json_encode($response);
      return;
    }

    // Check if the type already exists under the same scholarship_type_id
    $stmt = $conn->prepare("SELECT type FROM types WHERE scholarship_type_id = ? AND type = ?");
    $stmt->bind_param("ss", $scholarship_type_id, $type);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
      $stmt->close();
      $response['status'] = 'error';
      $response['message'] = 'This type already exists for the given scholarship type';
      echo json_encode($response);
      return;
    }

    $stmt->close();

    // Insert data into the 'types' table
    $stmt = $conn->prepare('INSERT INTO types (type_id, scholarship_type_id, type, description, eligibility, created_at) VALUES (?, ?, ?, ?, ?, ?)');
    $stmt->bind_param('ssssss', $type_id, $scholarship_type_id, $type, $description, $eligibility, $created_at);

    if ($stmt->execute()) {
      $response['status'] = 'success';
      $response['message'] = 'Type added successfully';

      // Log the activity
      $activityLogger = new ActivityLogger($conn);
      $logResponse = $activityLogger->logActivity(
        $security_response['user_id'],         
        'type',          
        'added a type',      
        'Added new type: ' . $type 
      );
      
      // Handle the logging response
      if ($logResponse['status'] === 'error') {
        $response['activity_log'] = $logResponse['message'];
      } else {
        $response['activity_log'] = 'Activity logged successfully';
      }
    } else {
      $response['status'] = 'error';
      $response['message'] = 'Error adding type: ' . $conn->error;
    }

    $stmt->close();
    echo json_encode($response);
  }

  public function get_type() {
    global $conn;
    $response = array();
  
    // Create a new instance for security key
    $security_key = new SecurityKey($conn);
    $security_response = $security_key->validateBearerToken();
  
    if ($security_response['status'] === 'error') {
      echo json_encode($security_response);
      return;
    }
  
    // Check if the user's role is 'admin'
    if ($security_response['role'] !== 'admin') {
      echo json_encode(['status' => 'error', 'message' => 'Unauthorized access']);
      return;
    }
  
    // Get the current page and the number of records per page from the request
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $records_per_page = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;
  
    // Calculate the starting record for the query
    $offset = ($page - 1) * $records_per_page;
  
    // Fetch types with pagination
    $stmt = $conn->prepare("SELECT type_id, type, description, eligibility, archive, start_date, end_date, created_at FROM types LIMIT ?, ?");
    $stmt->bind_param("ii", $offset, $records_per_page);
    $stmt->execute();
    $result = $stmt->get_result();
  
    // Get total number of records for pagination info
    $total_stmt = $conn->prepare("SELECT COUNT(*) as total FROM types");
    $total_stmt->execute();
    $total_result = $total_stmt->get_result();
    $total_row = $total_result->fetch_assoc();
    $total_records = $total_row['total'];
  
    if ($result->num_rows > 0) {
      $types = array();
  
      while ($row = $result->fetch_assoc()) {
        $types[] = array(
          'type_id' => $row['type_id'],
          'type' => $row['type'],
          'description' => nl2br($row['description']),
          'eligibility' => nl2br($row['eligibility']),
          'archive' => $row['archive'],
          'start_date' => $row['start_date'],
          'end_date' => $row['end_date'],
          'archive' => $row['archive'],
          'created_at' => $row['created_at']
        );
      }
  
      $response['status'] = 'success';
      $response['data'] = $types;
      $response['pagination'] = array(
        'current_page' => $page,
        'records_per_page' => $records_per_page,
        'total_records' => $total_records,
        'total_pages' => ceil($total_records / $records_per_page)
      );
    } else {
      $response['status'] = 'error';
      $response['message'] = 'No types found';
    }
  
    $stmt->close();
    $total_stmt->close();
    echo json_encode($response);
  }  

  public function update_type() {
    global $conn;
    date_default_timezone_set('Asia/Manila');
    $response = array();
  
    $data = json_decode(file_get_contents("php://input"), true);
    $type_id = htmlspecialchars($_GET['tid'] ?? '');
    $archive = htmlspecialchars($data['archive'] ?? '');
    $type = htmlspecialchars($data['type'] ?? '');
    $description = htmlspecialchars($data['description'] ?? '');
    $eligibility = htmlspecialchars($data['eligibility'] ?? '');
    $start_date = htmlspecialchars($data['start_date'] ?? ''); 
    $end_date = htmlspecialchars($data['end_date'] ?? ''); 
  
    // Create a new instance for security key
    $security_key = new SecurityKey($conn);
    $security_response = $security_key->validateBearerToken();
  
    if ($security_response['status'] === 'error') {
      echo json_encode($security_response);
      return;
    }
  
    // Check if the user's role is 'admin'
    if ($security_response['role'] !== 'admin') {
      echo json_encode(['status' => 'error', 'message' => 'Unauthorized access']);
      return;
    }
  
    if (empty($type)) {
      $response['status'] = 'error';
      $response['message'] = 'Type cannot be empty';
      echo json_encode($response);
      return;
    }
  
    if (empty($description)) {
      $response['status'] = 'error';
      $response['message'] = 'Description cannot be empty';
      echo json_encode($response);
      return;
    }
  
    if (empty($eligibility)) {
      $response['status'] = 'error';
      $response['message'] = 'Eligibility cannot be empty';
      echo json_encode($response);
      return;
    }

    if (!empty($start_date) && !strtotime($start_date)) {
      $response['status'] = 'error';
      $response['message'] = 'Invalid start date format';
      echo json_encode($response);
      return;
    }

    if (!empty($end_date) && !strtotime($end_date)) {
      $response['status'] = 'error';
      $response['message'] = 'Invalid end date format';
      echo json_encode($response);
      return;
    }
  
    // Update data in the 'types' table
    $stmt = $conn->prepare('UPDATE types SET archive = ?, type = ?, description = ?, eligibility = ?, start_date = ?, end_date = ? WHERE type_id = ?');
    $stmt->bind_param('sssssss', $archive, $type, $description, $eligibility, $start_date, $end_date, $type_id);
  
    if ($stmt->execute()) {
      $response['status'] = 'success';
      $response['message'] = 'Type updated successfully';

      // Log the activity
      $activityLogger = new ActivityLogger($conn);
      $logResponse = $activityLogger->logActivity(
        $security_response['user_id'],         
        'type',          
        'updated a type',      
        'Updated the type: ' . $type 
      );
      
      // Handle the logging response
      if ($logResponse['status'] === 'error') {
        $response['activity_log'] = $logResponse['message'];
      } else {
        $response['activity_log'] = 'Activity logged successfully';
      }
    } else {
      $response['status'] = 'error';
      $response['message'] = 'Error updating type: ' . $conn->error;
    }
  
    $stmt->close();
    echo json_encode($response);
  }  

  // Scholar Category
  public function get_scholarship_type_by_category() {
    global $conn;
    $response = array();
  
    // Retrieve the request data
    $category = htmlspecialchars($_GET['category'] ?? '');
  
    // Validate security
    $security_key = new SecurityKey($conn);
    $security_response = $security_key->validateBearerToken();
  
    if ($security_response['status'] === 'error') {
      echo json_encode($security_response);
      return;
    }
  
    // Check if the user is authorized (admin or any authorized role)
    if ($security_response['role'] !== 'admin' && $security_response['role'] !== 'authorized_user') {
      echo json_encode(['status' => 'error', 'message' => 'Unauthorized access']);
      return;
    }
  
    // Validate category input
    if (empty($category)) {
      $response['status'] = 'error';
      $response['message'] = 'Category cannot be empty';
      echo json_encode($response);
      return;
    }
  
    // Fetch scholarship types based on the category (internal or external)
    $stmt = $conn->prepare("SELECT * FROM scholarship_types WHERE category = ?");
    $stmt->bind_param("s", $category);
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
      $response['message'] = 'No scholarship types found for the selected category';
      echo json_encode($response);
      return;
    }
  
    $stmt->close();
  }  

  // Account Approval
  public function get_users() {
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

    // Check if the user's role is 'admin'
    if ($security_response['role'] !== 'admin') {
      echo json_encode(['status' => 'error', 'message' => 'Unauthorized access']);
      return;
    }
  
    // Get the current page and the number of records per page from the request
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $records_per_page = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;
  
    // Calculate the starting record for the query
    $offset = ($page - 1) * $records_per_page;
  
    // Fetch users with pagination
    $stmt = $conn->prepare("SELECT profile, user_id, student_number, first_name, last_name, email, role, joined_at 
                            FROM users 
                            WHERE role = 'pending'
                            LIMIT ?, ?");
    $stmt->bind_param("ii", $offset, $records_per_page);
    $stmt->execute();
    $result = $stmt->get_result();
  
    // Get total number of records for pagination info
    $total_stmt = $conn->prepare("SELECT COUNT(*) as total FROM users WHERE role = 'pending'");
    $total_stmt->execute();
    $total_result = $total_stmt->get_result();
    $total_row = $total_result->fetch_assoc();
    $total_records = $total_row['total'];
  
    if ($result->num_rows > 0) {
      $users = array();
  
      while ($row = $result->fetch_assoc()) {
        $users[] = $row;
      }
  
      $response['status'] = 'success';
      $response['data'] = $users;
      $response['pagination'] = array(
        'current_page' => $page,
        'records_per_page' => $records_per_page,
        'total_records' => $total_records,
        'total_pages' => ceil($total_records / $records_per_page)
      );
    } else {
      $response['status'] = 'error';
      $response['message'] = 'No users found';
    }
  
    $stmt->close();
    $total_stmt->close();
    echo json_encode($response);
  }  

  public function search_users() {
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

    // Check if the user's role is 'admin'
    if ($security_response['role'] !== 'admin') {
      echo json_encode(['status' => 'error', 'message' => 'Unauthorized access']);
      return;
    }
  
    // Get the search query, page, and limit from the request
    $search_query = isset($_GET['query']) ? '%' . $_GET['query'] . '%' : '';
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $records_per_page = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;
  
    // Calculate the starting record for the query
    $offset = ($page - 1) * $records_per_page;
  
    // Prepare the search SQL query
    $stmt = $conn->prepare("SELECT profile, user_id, student_number, first_name, last_name, email, role, joined_at 
                            FROM users 
                            WHERE role = 'pending' AND (student_number LIKE ? OR first_name LIKE ? OR last_name LIKE ? OR email LIKE ?)
                            LIMIT ?, ?");
    $stmt->bind_param("ssssii", $search_query, $search_query, $search_query, $search_query, $offset, $records_per_page);
    $stmt->execute();
    $result = $stmt->get_result();
  
    // Get total number of matching records for pagination info
    $total_stmt = $conn->prepare("SELECT COUNT(*) as total FROM users 
                                  WHERE role = 'pending' 
                                  AND (first_name LIKE ? OR last_name LIKE ? OR email LIKE ?)");
    $total_stmt->bind_param("sss", $search_query, $search_query, $search_query);
    $total_stmt->execute();
    $total_result = $total_stmt->get_result();
    $total_row = $total_result->fetch_assoc();
    $total_records = $total_row['total'];
  
    if ($result->num_rows > 0) {
      $users = array();
  
      while ($row = $result->fetch_assoc()) {
        $users[] = $row;
      }
  
      $response['status'] = 'success';
      $response['data'] = $users;
      $response['pagination'] = array(
        'current_page' => $page,
        'records_per_page' => $records_per_page,
        'total_records' => $total_records,
        'total_pages' => ceil($total_records / $records_per_page)
      );
    } else {
      $response['status'] = 'error';
      $response['message'] = 'No users found';
    }
  
    $stmt->close();
    $total_stmt->close();
    echo json_encode($response);
  }  

  public function update_user_role() {
    global $conn;
    date_default_timezone_set('Asia/Manila');
    $response = array();
    $email = new SendEmail();
    
    // Create a new instance for security key
    $security_key = new SecurityKey($conn);
    $security_response = $security_key->validateBearerToken();
    
    if ($security_response['status'] === 'error') {
      echo json_encode($security_response);
      return;
    }
  
    // Check if the user's role is 'admin'
    if ($security_response['role'] !== 'admin') {
      echo json_encode(['status' => 'error', 'message' => 'Unauthorized access']);
      return;
    }
    
    // Get the user ID and new role from the request
    $user_id = isset($_GET['uid']) ? trim($_GET['uid']) : '';
    $new_role = isset($_GET['role']) ? trim($_GET['role']) : '';
  
    if(empty($new_role)){
      $response['status'] = 'error';
      $response['message'] = 'Role cannot be empty';
      echo json_encode($response);
      return;
    }
    
    // Validate the new role
    $allowed_roles = ['student', 'admin', 'dean', 'adviser'];
    if (!in_array($new_role, $allowed_roles)) {
      $response['status'] = 'error';
      $response['message'] = 'Invalid role';
      echo json_encode($response);
      return;
    }
    
    // Check if the user ID is valid
    if ($user_id === null || $user_id <= 0) {
      $response['status'] = 'error';
      $response['message'] = 'Invalid user ID.';
      echo json_encode($response);
      return;
    }
  
    // Check if the user ID exists
    $stmt = $conn->prepare("SELECT user_id, first_name, last_name, email FROM users WHERE user_id = ?");
    $stmt->bind_param("s", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
      $stmt->close();
      $response['status'] = 'error';
      $response['message'] = 'This user does not exist';
      echo json_encode($response);
      return;
    }
  
    $user = $result->fetch_assoc();
    $first_name = $user['first_name'];
    $last_name = $user['last_name'];
    $recipientEmail = $user['email'];
  
    $stmt->close();
    
    // Update the user's role in the database
    $stmt = $conn->prepare("UPDATE users SET role = ? WHERE user_id = ?");
    $stmt->bind_param("ss", $new_role, $user_id);
    
    if ($stmt->execute()) {
      $response['status'] = 'success';
      $response['message'] = 'User role updated successfully';
  
      // Send email notification to user
      $senderName = 'UPHSD-Calamba Scholarship';
      $senderEmail = 'razonmarknicholas.cdlb@gmail.com';
      $subject = 'Account Role Update';
      $body = "
        <h1>Account Role Update</h1>
        <p>Hello $first_name $last_name,</p>
        <p>Your account role has been updated to: <strong>$new_role</strong>.</p>
        <p>Thank you for being a part of our community!</p>
      ";
  
      // Send the email
      $emailResponse = $email->sendMail($senderName, $senderEmail, $recipientEmail, $subject, $body);
      $response['email_status'] = $emailResponse;
  
      // Log the activity
      $activityLogger = new ActivityLogger($conn);
      $logResponse = $activityLogger->logActivity(
        $security_response['user_id'],         
        'users',          
        'update a user role',      
        'Updated the role of user to: ' . $new_role 
      );
      
      // Handle the logging response
      if ($logResponse['status'] === 'error') {
        $response['activity_log'] = $logResponse['message'];
      } else {
        $response['activity_log'] = 'Activity logged successfully';
      }
    } else {
      $response['status'] = 'error';
      $response['message'] = 'Failed to update user role. Please try again.';
    }
  
    $stmt->close();
    echo json_encode($response);
  }

  public function delete_user() {
    global $conn;
    date_default_timezone_set('Asia/Manila');
    $response = array();

    // Extract user ID from the request
    $user_id = htmlspecialchars($_GET['uid'] ?? '');

    // Create a new instance for security key
    $security_key = new SecurityKey($conn);
    $security_response = $security_key->validateBearerToken();

    if ($security_response['status'] === 'error') {
      echo json_encode($security_response);
      return;
    }

    // Check if the user's role is 'admin'
    if ($security_response['role'] !== 'admin') {
      echo json_encode(['status' => 'error', 'message' => 'Unauthorized access']);
      return;
    }

    if (empty($user_id)) {
      $response['status'] = 'error';
      $response['message'] = 'User ID cannot be empty';
      echo json_encode($response);
      return;
    }

    // Check if the user exists
    $stmt = $conn->prepare("SELECT user_id FROM users WHERE user_id = ?");
    $stmt->bind_param("s", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
      $stmt->close();
      $response['status'] = 'error';
      $response['message'] = 'This user does not exist';
      echo json_encode($response);
      return;
    }

    $stmt->close();

    // Delete the user
    $stmt = $conn->prepare('DELETE FROM users WHERE user_id = ?');
    $stmt->bind_param('s', $user_id);

    if ($stmt->execute()) {
      $response['status'] = 'success';
      $response['message'] = 'User deleted successfully';
      echo json_encode($response);
      return;
    } else {
      $response['status'] = 'error';
      $response['message'] = 'Error deleting user: ' . $conn->error;
      echo json_encode($response);
      return;
    }
  }

  // Active Accounts
  public function get_active_users() {
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

    // Check if the user's role is 'admin'
    if ($security_response['role'] !== 'admin') {
      echo json_encode(['status' => 'error', 'message' => 'Unauthorized access']);
      return;
    }
  
    // Get the current page and the number of records per page from the request
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $records_per_page = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;
  
    // Calculate the starting record for the query
    $offset = ($page - 1) * $records_per_page;
  
    // Fetch users with pagination
    $stmt = $conn->prepare("SELECT profile, user_id, student_number, first_name, last_name, email, role, status, last_login, joined_at 
                            FROM users 
                            WHERE role != 'pending'
                            LIMIT ?, ?");
    $stmt->bind_param("ii", $offset, $records_per_page);
    $stmt->execute();
    $result = $stmt->get_result();
  
    // Get total number of records for pagination info
    $total_stmt = $conn->prepare("SELECT COUNT(*) as total FROM users WHERE role = 'pending'");
    $total_stmt->execute();
    $total_result = $total_stmt->get_result();
    $total_row = $total_result->fetch_assoc();
    $total_records = $total_row['total'];
  
    if ($result->num_rows > 0) {
      $users = array();
  
      while ($row = $result->fetch_assoc()) {
        $users[] = $row;
      }
  
      $response['status'] = 'success';
      $response['data'] = $users;
      $response['pagination'] = array(
        'current_page' => $page,
        'records_per_page' => $records_per_page,
        'total_records' => $total_records,
        'total_pages' => ceil($total_records / $records_per_page)
      );
    } else {
      $response['status'] = 'error';
      $response['message'] = 'No users found';
    }
  
    $stmt->close();
    $total_stmt->close();
    echo json_encode($response);
  }

  public function search_active_users() {
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

    // Check if the user's role is 'admin'
    if ($security_response['role'] !== 'admin') {
      echo json_encode(['status' => 'error', 'message' => 'Unauthorized access']);
      return;
    }
  
    // Get the search query, page, and limit from the request
    $search_query = isset($_GET['query']) ? '%' . $_GET['query'] . '%' : '';
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $records_per_page = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;
  
    // Calculate the starting record for the query
    $offset = ($page - 1) * $records_per_page;
  
    // Prepare the search SQL query
    $stmt = $conn->prepare("SELECT profile, user_id, student_number, first_name, last_name, email, role, status, last_login, joined_at 
                            FROM users 
                            WHERE role != 'pending' AND (student_number LIKE ? OR first_name LIKE ? OR last_name LIKE ? OR email LIKE ?)
                            LIMIT ?, ?");
    $stmt->bind_param("ssssii", $search_query, $search_query, $search_query, $search_query, $offset, $records_per_page);
    $stmt->execute();
    $result = $stmt->get_result();
  
    // Get total number of matching records for pagination info
    $total_stmt = $conn->prepare("SELECT COUNT(*) as total FROM users 
                                  WHERE role = 'pending' 
                                  AND (first_name LIKE ? OR last_name LIKE ? OR email LIKE ?)");
    $total_stmt->bind_param("sss", $search_query, $search_query, $search_query);
    $total_stmt->execute();
    $total_result = $total_stmt->get_result();
    $total_row = $total_result->fetch_assoc();
    $total_records = $total_row['total'];
  
    if ($result->num_rows > 0) {
      $users = array();
  
      while ($row = $result->fetch_assoc()) {
        $users[] = $row;
      }
  
      $response['status'] = 'success';
      $response['data'] = $users;
      $response['pagination'] = array(
        'current_page' => $page,
        'records_per_page' => $records_per_page,
        'total_records' => $total_records,
        'total_pages' => ceil($total_records / $records_per_page)
      );
    } else {
      $response['status'] = 'error';
      $response['message'] = 'No users found';
    }
  
    $stmt->close();
    $total_stmt->close();
    echo json_encode($response);
  } 

  public function update_active_users() {
    global $conn;
    date_default_timezone_set('Asia/Manila');
    $response = array();

    // Extract user ID and status from the request
    $user_id = htmlspecialchars($_GET['uid'] ?? '');
    $status = htmlspecialchars($_GET['status'] ?? '');

    // Create a new instance for the security key
    $security_key = new SecurityKey($conn);
    $security_response = $security_key->validateBearerToken();

    if ($security_response['status'] === 'error') {
      echo json_encode($security_response);
      return;
    }

    // Check if the user's role is 'admin'
    if ($security_response['role'] !== 'admin') {
      echo json_encode(['status' => 'error', 'message' => 'Unauthorized access']);
      return;
    }

    if (empty($user_id)) {
      $response['status'] = 'error';
      $response['message'] = 'User ID cannot be empty';
      echo json_encode($response);
      return;
    }

    if ($status !== 'deactivated' && $status !== '') {
      $response['status'] = 'error';
      $response['message'] = 'Invalid status provided. It must be either "deactivated" or blank.';
      echo json_encode($response);
      return;
    }

    // Check if the user exists
    $stmt = $conn->prepare("SELECT user_id FROM users WHERE user_id = ?");
    $stmt->bind_param("s", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
      $stmt->close();
      $response['status'] = 'error';
      $response['message'] = 'This user does not exist';
      echo json_encode($response);
      return;
    }

    $stmt->close();

    // Update the user's status based on the provided value
    $stmt = $conn->prepare('UPDATE users SET status = ? WHERE user_id = ?');
    $stmt->bind_param('ss', $status, $user_id);

    if ($stmt->execute()) {
      $response['status'] = 'success';
      $response['message'] = $status === 'deactivated' ? 'User deactivated successfully' : 'User activated successfully';
      
      // Log the activity
      $activityLogger = new ActivityLogger($conn);
      $logResponse = $activityLogger->logActivity(
        $security_response['user_id'],         
        'user status',          
        'updated a user status',      
        'Updated the user status: ' . $status
      );
      
      // Handle the logging response
      if ($logResponse['status'] === 'error') {
        $response['activity_log'] = $logResponse['message'];
      } else {
        $response['activity_log'] = 'Activity logged successfully';
      }
      
      echo json_encode($response);
      return;
    } else {
      $response['status'] = 'error';
      $response['message'] = 'Error updating user status: ' . $conn->error;
      echo json_encode($response);
      return;
    }
  } 

  // Applications
  public function get_applications() {
    global $conn;
    date_default_timezone_set('Asia/Manila');
    
    $response = [];
    
    // Validate Bearer Token
    $security_key = new SecurityKey($conn);
    $security_response = $security_key->validateBearerToken();
    
    if ($security_response['status'] === 'error') {
      echo json_encode($security_response);
      return;
    }
  
    // Ensure the user has admin privileges
    if ($security_response['role'] !== 'admin') {
      echo json_encode(['status' => 'error', 'message' => 'Unauthorized access']);
      return;
    }
    
    // Pagination parameters
    $page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
    $records_per_page = isset($_GET['limit']) && is_numeric($_GET['limit']) ? (int)$_GET['limit'] : 50;
    $offset = ($page - 1) * $records_per_page;
    
    // Query for fetching applications
    $query = "
      SELECT 
        a.application_id, 
        a.scholarship_type_id, 
        a.type_id, 
        a.status, 
        a.created_at, 
        u.user_id, 
        u.profile, 
        u.student_number, 
        u.email, 
        u.first_name, 
        u.middle_name, 
        u.last_name, 
        t.type AS form_type, 
        f.program, 
        f.referral_id, 
        f.attachment, 
        f.academic_year, 
        f.year_level, 
        f.semester, 
        f.general_weighted_average, 
        f.contact_number, 
        f.honors_received,
        CONCAT(r.first_name, ' ', r.middle_name, ' ', r.last_name) AS referral_name
      FROM applications a
      LEFT JOIN users u ON a.user_id = u.user_id
      LEFT JOIN types t ON a.type_id = t.type_id
      LEFT JOIN (
          SELECT 
            scholarship_type_id, 
            type_id, 
            user_id, 
            program, 
            referral_id, 
            attachment, 
            academic_year, 
            year_level, 
            semester,
            general_weighted_average, 
            contact_number, 
            honors_received
          FROM forms
          GROUP BY scholarship_type_id, type_id, user_id
      ) f ON a.scholarship_type_id = f.scholarship_type_id 
        AND a.type_id = f.type_id 
        AND a.user_id = f.user_id
      LEFT JOIN users r ON f.referral_id = r.user_id
      WHERE a.status = 'approved'
      LIMIT ?, ?
    ";
    
    $stmt = $conn->prepare($query);
    if (!$stmt) {
      echo json_encode(['status' => 'error', 'message' => 'Failed to prepare query']);
      return;
    }
    
    $stmt->bind_param("ii", $offset, $records_per_page);
    $stmt->execute();
    $result = $stmt->get_result();
    
    // Query to get the total number of pending applications
    $total_stmt = $conn->prepare("SELECT COUNT(*) as total FROM applications WHERE status = 'approved'");
    if (!$total_stmt) {
      echo json_encode(['status' => 'error', 'message' => 'Failed to prepare count query']);
      $stmt->close();
      return;
    }
    
    $total_stmt->execute();
    $total_result = $total_stmt->get_result();
    $total_records = $total_result->fetch_assoc()['total'];
    
    // Process results
    if ($result->num_rows > 0) {
      $scholars = [];
      
      while ($row = $result->fetch_assoc()) {
        // Fetch subjects for each application
        $subjects_query = $conn->prepare("SELECT subject_id, subject_code, units, name_of_instructor, grade FROM subjects WHERE form_id = ?");
        $subjects_query->bind_param("s", $row['application_id']);
        $subjects_query->execute();
        $subjects_result = $subjects_query->get_result();
        
        $subjects = [];
        while ($subject = $subjects_result->fetch_assoc()) {
          $subjects[] = $subject;
        }
        
        $subjects_query->close();

        $scholars[] = [
          'user_id' => $row['user_id'] ?? '',
          'application_id' => $row['application_id'],
          'scholarship_type_id' => $row['scholarship_type_id'],
          'type_id' => $row['type_id'],
          'form_type' => $row['form_type'] ?? '',
          'status' => $row['status'],
          'created_at' => date('F j, Y g:i A', strtotime($row['created_at'])),
          'profile' => $row['profile'] ?? '',
          'email' => $row['email'] ?? '',
          'first_name' => $row['first_name'] ?? '',
          'student_number' => $row['student_number'] ?? '',
          'middle_name' => $row['middle_name'] ?? '',
          'last_name' => $row['last_name'] ?? '',
          'contact_number' => $row['contact_number'] ?? '',
          'honors_received' => $row['honors_received'] ?? '',
          'course' => $row['program'] ?? '',
          'attachment' => $row['attachment'] ?? '',
          'year_level' => isset($row['year_level']) ? match ((int)$row['year_level']) {
            1 => '1st Year',
            2 => '2nd Year',
            3 => '3rd Year',
            4 => '4th Year',
            default => 'N/A',
          } : 'N/A',
          'academic_year' => $row['academic_year'] ?? '',
          'semester' => $row['semester'] ?? '',
          'general_weighted_average' => is_numeric($row['general_weighted_average']) ? $row['general_weighted_average'] : '',
          'referral_name' => $row['referral_name'] ?? '',
          'subjects' => $subjects
        ];
      }
      
      $response = [
        'status' => 'success',
        'data' => $scholars,
        'pagination' => [
          'current_page' => $page,
          'records_per_page' => $records_per_page,
          'total_records' => $total_records,
          'total_pages' => ceil($total_records / $records_per_page)
        ]
      ];
    } else {
      $response = [
        'status' => 'error',
        'message' => 'No applications found'
      ];
    }
    
    // Clean up
    $stmt->close();
    $total_stmt->close();
    
    echo json_encode($response);
  }  

  public function search_applications() {
    global $conn;
    date_default_timezone_set('Asia/Manila');
    $response = array();
  
    $security_key = new SecurityKey($conn);
    $security_response = $security_key->validateBearerToken();
  
    if ($security_response['status'] === 'error') {
      echo json_encode($security_response);
      return;
    }
  
    if ($security_response['role'] !== 'admin') {
      echo json_encode(['status' => 'error', 'message' => 'Unauthorized access']);
      return;
    }
  
    // Handle search, pagination, and limit defaults
    $search_query = isset($_GET['query']) ? '%' . $conn->real_escape_string($_GET['query']) . '%' : '';
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $records_per_page = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;
    $offset = ($page - 1) * $records_per_page;
  
    // Prepare main query with necessary joins and WHERE clause
    $stmt = $conn->prepare("
      SELECT 
        a.application_id, 
        a.scholarship_type_id, 
        a.type_id, 
        a.status, 
        a.created_at, 
        u.user_id, 
        u.profile, 
        u.student_number, 
        u.email, 
        u.first_name, 
        u.middle_name, 
        u.last_name, 
        t.type, 
        f.program, 
        f.referral_id,
        f.attachment, 
        f.academic_year, 
        f.year_level, 
        f.semester, 
        f.general_weighted_average, 
        f.contact_number, 
        f.honors_received,
        CONCAT(r.first_name, ' ', r.middle_name, ' ', r.last_name) AS referral_name
      FROM applications a
      LEFT JOIN users u ON a.user_id = u.user_id
      LEFT JOIN types t ON a.type_id = t.type_id
      LEFT JOIN (
          SELECT 
            scholarship_type_id, 
            type_id, 
            user_id, 
            program,  
            referral_id,
            attachment, 
            academic_year,
            year_level, 
            semester,
            general_weighted_average, 
            contact_number, 
            honors_received
          FROM forms
          GROUP BY scholarship_type_id, type_id, user_id
      ) f ON a.scholarship_type_id = f.scholarship_type_id 
        AND a.type_id = f.type_id 
        AND a.user_id = f.user_id
      LEFT JOIN users r ON f.referral_id = r.user_id
      WHERE a.status = 'approved'
      AND (u.student_number LIKE ? OR u.first_name LIKE ? OR u.last_name LIKE ? OR u.email LIKE ?)
      LIMIT ?, ?
    ");
  
    $stmt->bind_param("sssssi", $search_query, $search_query, $search_query, $search_query, $offset, $records_per_page);
    $stmt->execute();
    $result = $stmt->get_result();
  
    // Adjust total count query to consider the search query
    $total_stmt = $conn->prepare("
      SELECT COUNT(*) as total 
      FROM applications a 
      LEFT JOIN users u ON a.user_id = u.user_id 
      WHERE a.status = 'approved' 
      AND (u.student_number LIKE ? OR u.first_name LIKE ? OR u.last_name LIKE ? OR u.email LIKE ?)
    ");
    $total_stmt->bind_param("ssss", $search_query, $search_query, $search_query, $search_query);
    $total_stmt->execute();
    $total_result = $total_stmt->get_result();
    $total_row = $total_result->fetch_assoc();
    $total_records = $total_row['total'];
  
    // Fetch data and build the response
    if ($result->num_rows > 0) {
      $scholars = array();
  
      while ($row = $result->fetch_assoc()) {
        // Fetch subjects for each application
        $subjects_query = $conn->prepare("SELECT subject_id, subject_code, units, name_of_instructor, grade FROM subjects WHERE form_id = ?");
        $subjects_query->bind_param("s", $row['application_id']);
        $subjects_query->execute();
        $subjects_result = $subjects_query->get_result();
        
        $subjects = [];
        while ($subject = $subjects_result->fetch_assoc()) {
          $subjects[] = $subject;
        }
        
        $subjects_query->close();

        $scholars[] = array(
          'user_id' => $row['user_id'] ?? '',
          'application_id' => $row['application_id'],
          'scholarship_type_id' => $row['scholarship_type_id'],
          'type_id' => $row['type_id'],
          'form_type' => $row['type'] ?? '',
          'status' => $row['status'],
          'created_at' => date('F j, Y g:i A', strtotime($row['created_at'])),
          'profile' => $row['profile'] ?? '',
          'student_number' => $row['student_number'] ?? '',
          'email' => $row['email'] ?? '',
          'first_name' => $row['first_name'] ?? '',
          'middle_name' => $row['middle_name'] ?? '',
          'last_name' => $row['last_name'] ?? '',
          'contact_number' => $row['contact_number'] ?? '',
          'honors_received' => $row['honors_received'] ?? '',
          'course' => $row['program'] ?? '',
          'attachment' => $row['attachment'] ?? '',
          'academic_year' => $row['academic_year'] ?? '',
          'year_level' => $row['year_level'] ?? '',
          'semester' => $row['semester'] ?? '',
          'general_weighted_average' => is_numeric($row['general_weighted_average']) ? $row['general_weighted_average'] : '',
          'referral_name' => $row['referral_name'] ?? '',
          'subjects' => $subjects
        );
      }
  
      $response['status'] = 'success';
      $response['data'] = $scholars;
      $response['pagination'] = array(
        'current_page' => $page,
        'records_per_page' => $records_per_page,
        'total_records' => $total_records,
        'total_pages' => ceil($total_records / $records_per_page)
      );
    } else {
      $response['status'] = 'error';
      $response['message'] = 'No applications found';
    }
  
    $stmt->close();
    $total_stmt->close();
    echo json_encode($response);
  }    

  public function update_application() {
    global $conn;
    date_default_timezone_set('Asia/Manila');
    $response = array();
    $email = new SendEmail();
    
    // Create a new instance for security key
    $security_key = new SecurityKey($conn);
    $security_response = $security_key->validateBearerToken();
    
    if ($security_response['status'] === 'error') {
      echo json_encode($security_response);
      return;
    }
    
    // Check if the user's role is 'admin'
    if ($security_response['role'] !== 'admin') {
      echo json_encode(['status' => 'error', 'message' => 'Unauthorized access']);
      return;
    }
    
    // Get application ID and new status from the request
    $application_id = isset($_GET['aid']) ? $_GET['aid'] : null;
    $new_status = isset($_GET['status']) ? $_GET['status'] : null;
    
    // Validate inputs
    if (is_null($application_id) || !in_array($new_status, ['accepted', 'declined'])) {
      echo json_encode(['status' => 'error', 'message' => 'Invalid application ID or status']);
      return;
    }
    
    // Get user details based on application_id
    $stmt = $conn->prepare("SELECT u.user_id, u.first_name, u.middle_name, u.last_name, u.email
                            FROM applications a
                            JOIN users u ON a.user_id = u.user_id
                            WHERE a.application_id = ?");
    $stmt->bind_param("s", $application_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
      // Fetch user details
      $user = $result->fetch_assoc();
      $user_id = $user['user_id'];
      $first_name = $user['first_name'];
      $middle_name = $user['middle_name'];
      $last_name = $user['last_name'];
      $recipientEmail = $user['email'];
    
      // Update the application status
      $stmt = $conn->prepare("UPDATE applications SET status = ? WHERE application_id = ? AND status = 'approved'");
      $stmt->bind_param("ss", $new_status, $application_id);
    
      if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
          $response['status'] = 'success';
          $response['message'] = 'Application status updated successfully';
    
          // Send email confirmation
          $senderName = 'UPHSD-Calamba Scholarship';
          $senderEmail = 'uphsd840.cdlb@gmail.com';
          $subject = 'Application Status Update';
          $body = "
            <h1>Application Status Update</h1>
            <p>Hello $first_name $middle_name $last_name,</p>
            <p>Your application has been updated to: <strong>$new_status</strong>.</p>
            <p>Thank you for your attention.</p>
          ";
    
          // Send the email
          $response['email_status'] = $email->sendMail($senderName, $senderEmail, $recipientEmail, $subject, $body);
          
          // Log the activity using the retrieved user_id
          $activityLogger = new ActivityLogger($conn);
          $logResponse = $activityLogger->logActivity(
            $security_response['user_id'],           
            'applications',                      
            'updated an application',                  
            'Updated the application of: ' . $application_id . ' to ' . $new_status
          );
    
          // Handle the logging response
          if ($logResponse['status'] === 'error') {
            $response['activity_log'] = $logResponse['message'];
          } else {
            $response['activity_log'] = 'Activity logged successfully';
          }
        } else {
          $response['status'] = 'error';
          $response['message'] = 'Application not found or already updated';
        }
      } else {
        $response['status'] = 'error';
        $response['message'] = 'Failed to update application status';
      }
    } else {
      $response['status'] = 'error';
      $response['message'] = 'No user found for this application';
    }
  
    $stmt->close();
    echo json_encode($response);
  }   
  
  public function delete_application() {
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
  
    // Check if the user's role is 'admin'
    if ($security_response['role'] !== 'admin') {
      echo json_encode(['status' => 'error', 'message' => 'Unauthorized access']);
      return;
    }
  
    // Get application ID from the request
    $application_id = isset($_GET['aid']) ? $_GET['aid'] : null; 
  
    // Validate inputs
    if (is_null($application_id)) {
      echo json_encode(['status' => 'error', 'message' => 'Invalid application ID']);
      return;
    }
  
    // Delete the application
    $stmt = $conn->prepare("DELETE FROM applications WHERE application_id = ?");
    $stmt->bind_param("s", $application_id);
  
    if ($stmt->execute()) {
      if ($stmt->affected_rows > 0) {
        $response['status'] = 'success';
        $response['message'] = 'Application deleted successfully';
      } else {
        $response['status'] = 'error';
        $response['message'] = 'Application not found';
      }
    } else {
      $response['status'] = 'error';
      $response['message'] = 'Failed to delete application';
    }
  
    $stmt->close();
    echo json_encode($response);
  }  

  // Scholars
  public function get_scholars() {
    global $conn;
    date_default_timezone_set('Asia/Manila');
    $response = array();
  
    $security_key = new SecurityKey($conn);
    $security_response = $security_key->validateBearerToken();
  
    if ($security_response['status'] === 'error') {
      echo json_encode($security_response);
      return;
    }
  
    if ($security_response['role'] !== 'admin') {
      echo json_encode(['status' => 'error', 'message' => 'Unauthorized access']);
      return;
    }
  
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $records_per_page = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;
    $offset = ($page - 1) * $records_per_page;
  
    $stmt = $conn->prepare("
      SELECT 
        a.application_id, 
        a.scholarship_type_id, 
        a.type_id, 
        a.status, 
        a.created_at, 
        u.user_id, 
        u.profile, 
        u.student_number, 
        u.email, 
        u.first_name, 
        u.middle_name, 
        u.last_name, 
        t.type, 
        f.program, 
        f.referral_id,
        f.attachment, 
        f.academic_year, 
        f.year_level, 
        f.semester, 
        f.general_weighted_average, 
        f.contact_number, 
        f.honors_received,
        CONCAT(r.first_name, ' ', r.middle_name, ' ', r.last_name) AS referral_name
      FROM applications a
      LEFT JOIN users u ON a.user_id = u.user_id
      LEFT JOIN types t ON a.type_id = t.type_id
      LEFT JOIN (
          SELECT 
            scholarship_type_id, 
            type_id, 
            user_id, 
            program, 
            referral_id,
            attachment, 
            academic_year,
            year_level, 
            semester,
            general_weighted_average, 
            contact_number, 
            honors_received
          FROM forms
          GROUP BY scholarship_type_id, type_id, user_id
      ) f ON a.scholarship_type_id = f.scholarship_type_id 
        AND a.type_id = f.type_id 
        AND a.user_id = f.user_id
      LEFT JOIN users r ON f.referral_id = r.user_id
      WHERE a.status = 'accepted'
      LIMIT ?, ?
    ");
    $stmt->bind_param("ii", $offset, $records_per_page);
    $stmt->execute();
    $result = $stmt->get_result();
  
    $total_stmt = $conn->prepare("SELECT COUNT(*) as total FROM applications WHERE status = 'accepted'");
    $total_stmt->execute();
    $total_result = $total_stmt->get_result();
    $total_row = $total_result->fetch_assoc();
    $total_records = $total_row['total'];
  
    if ($result->num_rows > 0) {
      $scholars = array();
  
      while ($row = $result->fetch_assoc()) {
        // Fetch subjects for each application
        $subjects_query = $conn->prepare("SELECT subject_id, subject_code, units, name_of_instructor, grade FROM subjects WHERE form_id = ?");
        $subjects_query->bind_param("s", $row['application_id']);
        $subjects_query->execute();
        $subjects_result = $subjects_query->get_result();
        
        $subjects = [];
        while ($subject = $subjects_result->fetch_assoc()) {
          $subjects[] = $subject;
        }
        
        $subjects_query->close();

        $scholar = array(
          'user_id' => $row['user_id'] ?? '',
          'application_id' => $row['application_id'],
          'scholarship_type_id' => $row['scholarship_type_id'],
          'type_id' => $row['type_id'],
          'form_type' => $row['type'] ?? '',
          'status' => $row['status'],
          'created_at' => date('F j, Y g:i A', strtotime($row['created_at'])),
          'profile' => $row['profile'] ?? '',
          'student_number' => $row['student_number'] ?? '',
          'email' => $row['email'] ?? '',
          'first_name' => $row['first_name'] ?? '',
          'middle_name' => $row['middle_name'] ?? '',
          'last_name' => $row['last_name'] ?? '',
          'contact_number' => $row['contact_number'] ?? '',
          'honors_received' => $row['honors_received'] ?? '',
          'course' => $row['program'] ?? '',
          'attachment' => $row['attachment'] ?? '',
          'academic_year' => $row['academic_year'] ?? '',
          'year_level' => $row['year_level'] ?? '',
          'semester' => $row['semester'] ?? '',
          'general_weighted_average' => is_numeric($row['general_weighted_average']) ? $row['general_weighted_average'] : '',
          'referral_name' => $row['referral_name'] ?? '',
          'subjects' => $subjects
        );
  
        $scholars[] = $scholar;
      }
  
      $response['status'] = 'success';
      $response['data'] = $scholars;
      $response['pagination'] = array(
        'current_page' => $page,
        'records_per_page' => $records_per_page,
        'total_records' => $total_records,
        'total_pages' => ceil($total_records / $records_per_page)
      );
    } else {
      $response['status'] = 'error';
      $response['message'] = 'No applications found';
    }
  
    $stmt->close();
    $total_stmt->close();
    echo json_encode($response);
  } 

  public function search_scholars() {
    global $conn;
    date_default_timezone_set('Asia/Manila');
    $response = array();
  
    $security_key = new SecurityKey($conn);
    $security_response = $security_key->validateBearerToken();
  
    if ($security_response['status'] === 'error') {
      echo json_encode($security_response);
      return;
    }
  
    if ($security_response['role'] !== 'admin') {
      echo json_encode(['status' => 'error', 'message' => 'Unauthorized access']);
      return;
    }
  
    // Handle search, pagination, and limit defaults
    $search_query = isset($_GET['query']) ? '%' . $conn->real_escape_string($_GET['query']) . '%' : '';
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $records_per_page = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;
    $offset = ($page - 1) * $records_per_page;
  
    // Prepare main query with necessary joins and WHERE clause
    $stmt = $conn->prepare("
      SELECT 
        a.application_id, 
        a.scholarship_type_id, 
        a.type_id, 
        a.status, 
        a.created_at, 
        u.user_id, 
        u.profile, 
        u.student_number, 
        u.email, 
        u.first_name, 
        u.middle_name, 
        u.last_name, 
        t.type, 
        f.program, 
        f.referral_id,
        f.attachment, 
        f.academic_year, 
        f.year_level, 
        f.semester, 
        f.general_weighted_average, 
        f.contact_number, 
        f.honors_received,
        CONCAT(r.first_name, ' ', r.middle_name, ' ', r.last_name) AS referral_name
      FROM applications a
      LEFT JOIN users u ON a.user_id = u.user_id
      LEFT JOIN types t ON a.type_id = t.type_id
      LEFT JOIN (
          SELECT 
            scholarship_type_id, 
            type_id, 
            user_id, 
            program,
            referral_id, 
            attachment, 
            academic_year,
            year_level, 
            semester,
            general_weighted_average, 
            contact_number, 
            honors_received
          FROM forms
          GROUP BY scholarship_type_id, type_id, user_id
      ) f ON a.scholarship_type_id = f.scholarship_type_id 
        AND a.type_id = f.type_id 
        AND a.user_id = f.user_id
      LEFT JOIN users r ON f.referral_id = r.user_id
      WHERE a.status = 'accepted'
      AND (u.student_number LIKE ? OR u.first_name LIKE ? OR u.last_name LIKE ? OR u.email LIKE ?)
      LIMIT ?, ?
    ");
  
    $stmt->bind_param("sssssi", $search_query, $search_query, $search_query, $search_query, $offset, $records_per_page);
    $stmt->execute();
    $result = $stmt->get_result();
  
    // Adjust total count query to consider the search query
    $total_stmt = $conn->prepare("
      SELECT COUNT(*) as total 
      FROM applications a 
      LEFT JOIN users u ON a.user_id = u.user_id 
      WHERE a.status = 'accepted' 
      AND (u.student_number LIKE ? OR u.first_name LIKE ? OR u.last_name LIKE ? OR u.email LIKE ?)
    ");
    $total_stmt->bind_param("ssss", $search_query, $search_query, $search_query, $search_query);
    $total_stmt->execute();
    $total_result = $total_stmt->get_result();
    $total_row = $total_result->fetch_assoc();
    $total_records = $total_row['total'];
  
    // Fetch data and build the response
    if ($result->num_rows > 0) {
      // Fetch subjects for each application
      $subjects_query = $conn->prepare("SELECT subject_id, subject_code, units, name_of_instructor, grade FROM subjects WHERE form_id = ?");
      $subjects_query->bind_param("s", $row['application_id']);
      $subjects_query->execute();
      $subjects_result = $subjects_query->get_result();
      
      $subjects = [];
      while ($subject = $subjects_result->fetch_assoc()) {
        $subjects[] = $subject;
      }
      
      $subjects_query->close();

      $scholars = array();
  
      while ($row = $result->fetch_assoc()) {
        $scholars[] = array(
          'user_id' => $row['user_id'] ?? '',
          'application_id' => $row['application_id'],
          'scholarship_type_id' => $row['scholarship_type_id'],
          'type_id' => $row['type_id'],
          'form_type' => $row['type'] ?? '',
          'status' => $row['status'],
          'created_at' => date('F j, Y g:i A', strtotime($row['created_at'])),
          'profile' => $row['profile'] ?? '',
          'student_number' => $row['student_number'] ?? '',
          'email' => $row['email'] ?? '',
          'first_name' => $row['first_name'] ?? '',
          'middle_name' => $row['middle_name'] ?? '',
          'last_name' => $row['last_name'] ?? '',
          'contact_number' => $row['contact_number'] ?? '',
          'honors_received' => $row['honors_received'] ?? '',
          'course' => $row['program'] ?? '',
          'attachment' => $row['attachment'] ?? '',
          'academic_year' => $row['academic_year'] ?? '',
          'year_level' => $row['year_level'] ?? '',
          'semester' => $row['semester'] ?? '',
          'general_weighted_average' => is_numeric($row['general_weighted_average']) ? $row['general_weighted_average'] : '',
          'referral_name' => $row['referral_name'] ?? '',
          'subjects' => $subjects
        );
      }
  
      $response['status'] = 'success';
      $response['data'] = $scholars;
      $response['pagination'] = array(
        'current_page' => $page,
        'records_per_page' => $records_per_page,
        'total_records' => $total_records,
        'total_pages' => ceil($total_records / $records_per_page)
      );
    } else {
      $response['status'] = 'error';
      $response['message'] = 'No applications found';
    }
  
    $stmt->close();
    $total_stmt->close();
    echo json_encode($response);
  } 
  
  public function delete_scholar() {
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
  
    // Check if the user's role is 'admin'
    if ($security_response['role'] !== 'admin') {
      echo json_encode(['status' => 'error', 'message' => 'Unauthorized access']);
      return;
    }
  
    // Get application ID from the request
    $application_id = isset($_GET['aid']) ? $_GET['aid'] : null; 
  
    // Validate inputs
    if (is_null($application_id)) {
      echo json_encode(['status' => 'error', 'message' => 'Invalid application ID']);
      return;
    }
  
    // Delete the application
    $stmt = $conn->prepare("DELETE FROM applications WHERE application_id = ?");
    $stmt->bind_param("s", $application_id);
  
    if ($stmt->execute()) {
      if ($stmt->affected_rows > 0) {
        $response['status'] = 'success';
        $response['message'] = 'Application deleted successfully';
      } else {
        $response['status'] = 'error';
        $response['message'] = 'Application not found';
      }
    } else {
      $response['status'] = 'error';
      $response['message'] = 'Failed to delete application';
    }
  
    $stmt->close();
    echo json_encode($response);
  }   

  // Departments
  public function add_department() {
    global $conn;
    date_default_timezone_set('Asia/Manila');
    $response = array();
  
    $data = json_decode(file_get_contents("php://input"), true);
    $department_id = bin2hex(random_bytes(8)); // 16 characters (hex)
    $department_code = htmlspecialchars($data['department_code'] ?? '');
    $department_name = htmlspecialchars($data['department_name'] ?? '');
    $created_at = date('Y-m-d H:i:s');
  
    // Create a new instance for security key
    $security_key = new SecurityKey($conn);
    $security_response = $security_key->validateBearerToken();
  
    if ($security_response['status'] === 'error') {
      echo json_encode($security_response);
      return;
    }
  
    // Check if the user's role is 'admin'
    if ($security_response['role'] !== 'admin') {
      echo json_encode(['status' => 'error', 'message' => 'Unauthorized access']);
      return;
    }
  
    // Validate input fields
    if (empty($department_code)) {
      $response['status'] = 'error';
      $response['message'] = 'Department code cannot be empty';
      echo json_encode($response);
      return;
    }
  
    if (empty($department_name)) {
      $response['status'] = 'error';
      $response['message'] = 'Department name cannot be empty';
      echo json_encode($response);
      return;
    }
  
    // Check if the department code or name already exists
    $stmt = $conn->prepare("SELECT department_code, department_name FROM departments WHERE department_code = ? OR department_name = ?");
    $stmt->bind_param("ss", $department_code, $department_name);
    $stmt->execute();
    $result = $stmt->get_result();
  
    if ($result->num_rows > 0) {
      $stmt->close();
      $response['status'] = 'error';
      $response['message'] = 'Department code or name already exists';
      echo json_encode($response);
      return;
    }
  
    $stmt->close();
  
    // Insert data into departments table
    $stmt = $conn->prepare('INSERT INTO departments (department_id, department_code, department_name, created_at) VALUES (?, ?, ?, ?)');
    $stmt->bind_param('ssss', $department_id, $department_code, $department_name, $created_at);
  
    if ($stmt->execute()) {
      $response['status'] = 'success';
      $response['message'] = 'Department created successfully';
  
      // Log the activity
      $activityLogger = new ActivityLogger($conn);
      $logResponse = $activityLogger->logActivity(
        $security_response['user_id'],
        'department',
        'added a department',
        'Added new department with code: ' . $department_code
      );
  
      // Handle the logging response
      if ($logResponse['status'] === 'error') {
        $response['activity_log'] = $logResponse['message'];
      } else {
        $response['activity_log'] = 'Activity logged successfully';
      }
  
      echo json_encode($response);
      return;
    } else {
      $response['status'] = 'error';
      $response['message'] = 'Error creating department: ' . $conn->error;
      echo json_encode($response);
      return;
    }
  }  

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
  
    // Check if the user's role is 'admin'
    if ($security_response['role'] !== 'admin') {
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
  
  public function update_department() {
    global $conn;
    date_default_timezone_set('Asia/Manila');
    $response = array();
  
    // Extract department_id from the GET request
    $department_id = htmlspecialchars($_GET['department_id'] ?? '');
  
    // Extract update fields from JSON input
    $data = json_decode(file_get_contents("php://input"), true);
    $department_code = htmlspecialchars($data['department_code'] ?? '');
    $department_name = htmlspecialchars($data['department_name'] ?? '');
    $created_at = htmlspecialchars($data['created_at'] ?? '');
  
    // Create a new instance for the security key
    $security_key = new SecurityKey($conn);
    $security_response = $security_key->validateBearerToken();
  
    if ($security_response['status'] === 'error') {
      echo json_encode($security_response);
      return;
    }
  
    // Check if the user's role is 'admin'
    if ($security_response['role'] !== 'admin') {
      echo json_encode(['status' => 'error', 'message' => 'Unauthorized access']);
      return;
    }
  
    if (empty($department_id)) {
      $response['status'] = 'error';
      $response['message'] = 'Department ID cannot be empty';
      echo json_encode($response);
      return;
    }

    if (empty($department_code)) {
      $response['status'] = 'error';
      $response['message'] = 'Department Code cannot be empty';
      echo json_encode($response);
      return;
    }

    if (empty($department_name)) {
      $response['status'] = 'error';
      $response['message'] = 'Department Name cannot be empty';
      echo json_encode($response);
      return;
    }
  
    // Check if the department exists
    $stmt = $conn->prepare("SELECT department_id FROM departments WHERE department_id = ?");
    $stmt->bind_param("s", $department_id);
    $stmt->execute();
    $result = $stmt->get_result();
  
    if ($result->num_rows === 0) {
      $stmt->close();
      $response['status'] = 'error';
      $response['message'] = 'This department does not exist';
      echo json_encode($response);
      return;
    }
  
    $stmt->close();
  
    // Prepare update query
    $update_query = "UPDATE departments SET ";
    $params = [];
    $types = '';
  
    // Add fields to update dynamically
    if (!empty($department_code)) {
      $update_query .= "department_code = ?, ";
      $params[] = $department_code;
      $types .= 's';
    }
  
    if (!empty($department_name)) {
      $update_query .= "department_name = ?, ";
      $params[] = $department_name;
      $types .= 's';
    }
  
    if (!empty($created_at)) {
      $update_query .= "created_at = ?, ";
      $params[] = $created_at;
      $types .= 's';
    }
  
    // Remove trailing comma and space, then add WHERE clause
    $update_query = rtrim($update_query, ', ') . " WHERE department_id = ?";
    $params[] = $department_id;
    $types .= 's';
  
    // Execute the prepared statement
    $stmt = $conn->prepare($update_query);
    $stmt->bind_param($types, ...$params);
  
    if ($stmt->execute()) {
      $response['status'] = 'success';
      $response['message'] = 'Department updated successfully';
  
      // Log the activity
      $activityLogger = new ActivityLogger($conn);
      $logResponse = $activityLogger->logActivity(
        $security_response['user_id'],         
        'department',          
        'updated a department',      
        'Updated the department with ID: ' . $department_id
      );
  
      // Handle the logging response
      if ($logResponse['status'] === 'error') {
        $response['activity_log'] = $logResponse['message'];
      } else {
        $response['activity_log'] = 'Activity logged successfully';
      }
  
      echo json_encode($response);
      return;
    } else {
      $response['status'] = 'error';
      $response['message'] = 'Error updating department: ' . $conn->error;
      echo json_encode($response);
      return;
    }
  }   
  
  // Program
  public function add_program() {
    global $conn;
    date_default_timezone_set('Asia/Manila');
    $response = array();
  
    $data = json_decode(file_get_contents("php://input"), true);
    $program_id = bin2hex(random_bytes(8)); // 16 characters (hex)
    $department_id = htmlspecialchars($data['department_id'] ?? '');
    $program_code = htmlspecialchars($data['program_code'] ?? '');
    $program_name = htmlspecialchars($data['program_name'] ?? '');
    $created_at = date('Y-m-d H:i:s');
  
    // Create a new instance for security key
    $security_key = new SecurityKey($conn);
    $security_response = $security_key->validateBearerToken();
  
    if ($security_response['status'] === 'error') {
      echo json_encode($security_response);
      return;
    }
  
    // Check if the user's role is 'admin'
    if ($security_response['role'] !== 'admin') {
      echo json_encode(['status' => 'error', 'message' => 'Unauthorized access']);
      return;
    }
  
    // Validate input fields
    if (empty($department_id)) {
      $response['status'] = 'error';
      $response['message'] = 'Department ID cannot be empty';
      echo json_encode($response);
      return;
    }
  
    if (empty($program_code)) {
      $response['status'] = 'error';
      $response['message'] = 'Program code cannot be empty';
      echo json_encode($response);
      return;
    }
  
    if (empty($program_name)) {
      $response['status'] = 'error';
      $response['message'] = 'Program name cannot be empty';
      echo json_encode($response);
      return;
    }
  
    // Check if the department exists
    $stmt = $conn->prepare("SELECT department_id FROM departments WHERE department_id = ?");
    $stmt->bind_param("s", $department_id);
    $stmt->execute();
    $result = $stmt->get_result();
  
    if ($result->num_rows === 0) {
      $stmt->close();
      $response['status'] = 'error';
      $response['message'] = 'Department not found';
      echo json_encode($response);
      return;
    }
  
    $stmt->close();
  
    // Check if the program code or name already exists
    $stmt = $conn->prepare("SELECT program_code, program_name FROM programs WHERE program_code = ? OR program_name = ?");
    $stmt->bind_param("ss", $program_code, $program_name);
    $stmt->execute();
    $result = $stmt->get_result();
  
    if ($result->num_rows > 0) {
      $stmt->close();
      $response['status'] = 'error';
      $response['message'] = 'Program code or name already exists';
      echo json_encode($response);
      return;
    }
  
    $stmt->close();
  
    // Insert data into programs table
    $stmt = $conn->prepare('INSERT INTO programs (program_id, department_id, program_code, program_name, created_at) VALUES (?, ?, ?, ?, ?)');
    $stmt->bind_param('sssss', $program_id, $department_id, $program_code, $program_name, $created_at);
  
    if ($stmt->execute()) {
      $response['status'] = 'success';
      $response['message'] = 'Program created successfully';
  
      // Log the activity
      $activityLogger = new ActivityLogger($conn);
      $logResponse = $activityLogger->logActivity(
        $security_response['user_id'],
        'program',
        'added a program',
        'Added new program with code: ' . $program_code
      );
  
      // Handle the logging response
      if ($logResponse['status'] === 'error') {
        $response['activity_log'] = $logResponse['message'];
      } else {
        $response['activity_log'] = 'Activity logged successfully';
      }
  
      echo json_encode($response);
      return;
    } else {
      $response['status'] = 'error';
      $response['message'] = 'Error creating program: ' . $conn->error;
      echo json_encode($response);
      return;
    }
  }

  public function get_program() {
    global $conn;
    date_default_timezone_set('Asia/Manila');
    $response = array();
  
    // Security validation
    $security_key = new SecurityKey($conn);
    $security_response = $security_key->validateBearerToken();
  
    if ($security_response['status'] === 'error') {
      echo json_encode($security_response);
      return;
    }
  
    if ($security_response['role'] !== 'admin') {
      echo json_encode(['status' => 'error', 'message' => 'Unauthorized access']);
      return;
    }
  
    // Pagination and search parameters
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $records_per_page = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;
    $search_query = htmlspecialchars($_GET['search'] ?? '');
  
    $offset = ($page - 1) * $records_per_page;
  
    // Base SQL query
    $query = "SELECT p.program_id, p.department_id, p.program_code, p.program_name, 
                     p.created_at, d.department_name 
              FROM programs p
              LEFT JOIN departments d ON p.department_id = d.department_id";
    $count_query = "SELECT COUNT(*) as total 
                    FROM programs p
                    LEFT JOIN departments d ON p.department_id = d.department_id";
  
    $search_term = '';
    if (!empty($search_query)) {
      $search_term = '%' . $search_query . '%';
      $query .= " WHERE p.program_code LIKE ? OR p.program_name LIKE ? OR d.department_name LIKE ?";
      $count_query .= " WHERE p.program_code LIKE ? OR p.program_name LIKE ? OR d.department_name LIKE ?";
    }
    $query .= " ORDER BY p.program_id DESC LIMIT ?, ?";
  
    // Prepare SQL statements
    $stmt = $conn->prepare($query);
    $count_stmt = $conn->prepare($count_query);
  
    if (!$stmt || !$count_stmt) {
      echo json_encode(['status' => 'error', 'message' => 'SQL preparation failed', 'error' => $conn->error]);
      return;
    }
  
    // Bind parameters
    if (!empty($search_query)) {
      $stmt->bind_param("sssii", $search_term, $search_term, $search_term, $offset, $records_per_page);
      $count_stmt->bind_param("sss", $search_term, $search_term, $search_term);
    } else {
      $stmt->bind_param("ii", $offset, $records_per_page);
    }
  
    $stmt->execute();
    $result = $stmt->get_result();
  
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

  public function update_program() {
    global $conn;
    date_default_timezone_set('Asia/Manila');
    $response = array();
  
    // Extract program_id from the GET request
    $program_id = htmlspecialchars($_GET['program_id'] ?? '');
  
    // Extract update fields from JSON input
    $data = json_decode(file_get_contents("php://input"), true);
    $department_id = htmlspecialchars($data['department_id'] ?? '');
    $program_code = htmlspecialchars($data['program_code'] ?? '');
    $program_name = htmlspecialchars($data['program_name'] ?? '');
    $created_at = htmlspecialchars($data['created_at'] ?? '');
  
    // Create a new instance for the security key
    $security_key = new SecurityKey($conn);
    $security_response = $security_key->validateBearerToken();
  
    if ($security_response['status'] === 'error') {
      echo json_encode($security_response);
      return;
    }
  
    // Check if the user's role is 'admin'
    if ($security_response['role'] !== 'admin') {
      echo json_encode(['status' => 'error', 'message' => 'Unauthorized access']);
      return;
    }

    // Check if the program exists
    $stmt = $conn->prepare("SELECT program_id FROM programs WHERE program_id = ?");
    $stmt->bind_param("s", $program_id);
    $stmt->execute();
    $result = $stmt->get_result();
  
    if ($result->num_rows === 0) {
      $stmt->close();
      $response['status'] = 'error';
      $response['message'] = 'Program not found with the provided Program ID';
      echo json_encode($response);
      return;
    }
  
    $stmt->close();
  
    if (empty($department_id)) {
      $response['status'] = 'error';
      $response['message'] = 'Department ID cannot be empty';
      echo json_encode($response);
      return;
    }
  
    if (empty($program_id)) {
      $response['status'] = 'error';
      $response['message'] = 'Program ID cannot be empty';
      echo json_encode($response);
      return;
    }
  
    if (empty($program_code)) {
      $response['status'] = 'error';
      $response['message'] = 'Program Code cannot be empty';
      echo json_encode($response);
      return;
    }
  
    if (empty($program_name)) {
      $response['status'] = 'error';
      $response['message'] = 'Program Name cannot be empty';
      echo json_encode($response);
      return;
    }

    // Validate that the department_id exists in the database
    $stmt = $conn->prepare("SELECT department_id FROM departments WHERE department_id = ?");
    $stmt->bind_param("s", $department_id);
    $stmt->execute();
    $result = $stmt->get_result();
  
    if ($result->num_rows === 0) {
      $stmt->close();
      $response['status'] = 'error';
      $response['message'] = 'Department not found with the provided Department ID';
      echo json_encode($response);
      return;
    }

    $stmt->close();
  
    // Prepare update query
    $update_query = "UPDATE programs SET ";
    $params = [];
    $types = '';
  
    // Add fields to update dynamically
    if (!empty($department_id)) {
      $update_query .= "department_id = ?, ";
      $params[] = $department_id;
      $types .= 's';
    }

    if (!empty($program_code)) {
      $update_query .= "program_code = ?, ";
      $params[] = $program_code;
      $types .= 's';
    }
  
    if (!empty($program_name)) {
      $update_query .= "program_name = ?, ";
      $params[] = $program_name;
      $types .= 's';
    }
  
    if (!empty($created_at)) {
      $update_query .= "created_at = ?, ";
      $params[] = $created_at;
      $types .= 's';
    }
  
    // Remove trailing comma and space, then add WHERE clause
    $update_query = rtrim($update_query, ', ') . " WHERE program_id = ?";
    $params[] = $program_id;
    $types .= 's';
  
    // Execute the prepared statement
    $stmt = $conn->prepare($update_query);
    $stmt->bind_param($types, ...$params);
  
    if ($stmt->execute()) {
      $response['status'] = 'success';
      $response['message'] = 'Program updated successfully';
  
      // Log the activity
      $activityLogger = new ActivityLogger($conn);
      $logResponse = $activityLogger->logActivity(
        $security_response['user_id'],         
        'program',          
        'updated a program',      
        'Updated the program with ID: ' . $program_id
      );
  
      // Handle the logging response
      if ($logResponse['status'] === 'error') {
        $response['activity_log'] = $logResponse['message'];
      } else {
        $response['activity_log'] = 'Activity logged successfully';
      }
  
      echo json_encode($response);
      return;
    } else {
      $response['status'] = 'error';
      $response['message'] = 'Error updating program: ' . $conn->error;
      echo json_encode($response);
      return;
    }
  }

  // Accounts
  public function get_user_accounts() {
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

    // Check if the user's role is 'admin'
    if ($security_response['role'] !== 'admin') {
        echo json_encode(['status' => 'error', 'message' => 'Unauthorized access']);
        return;
    }

    // Get the current page and the number of records per page from the request
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $records_per_page = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;

    // Calculate the starting record for the query
    $offset = ($page - 1) * $records_per_page;

    // Fetch users with pagination where role is NOT 'pending'
    $stmt = $conn->prepare("SELECT user_id, first_name, last_name, email, role, joined_at 
                            FROM users 
                            WHERE role != 'pending'
                            LIMIT ?, ?");
    $stmt->bind_param("ii", $offset, $records_per_page);
    $stmt->execute();
    $result = $stmt->get_result();

    // Get total number of records for pagination info
    $total_stmt = $conn->prepare("SELECT COUNT(*) as total FROM users WHERE role != 'pending'");
    $total_stmt->execute();
    $total_result = $total_stmt->get_result();
    $total_row = $total_result->fetch_assoc();
    $total_records = $total_row['total'];

    if ($result->num_rows > 0) {
        $users = array();

        while ($row = $result->fetch_assoc()) {
            $users[] = $row;
        }

        $response['status'] = 'success';
        $response['data'] = $users;
        $response['pagination'] = array(
            'current_page' => $page,
            'records_per_page' => $records_per_page,
            'total_records' => $total_records,
            'total_pages' => ceil($total_records / $records_per_page)
        );
    } else {
        $response['status'] = 'error';
        $response['message'] = 'No users found';
    }

    $stmt->close();
    $total_stmt->close();
    echo json_encode($response);
  }

  public function delete_user_accounts() {
    global $conn;
    date_default_timezone_set('Asia/Manila');
    $response = array();

    // Extract user ID from the request
    $user_id = htmlspecialchars($_GET['uid'] ?? '');

    // Create a new instance for security key
    $security_key = new SecurityKey($conn);
    $security_response = $security_key->validateBearerToken();

    if ($security_response['status'] === 'error') {
      echo json_encode($security_response);
      return;
    }

    // Check if the user's role is 'admin'
    if ($security_response['role'] !== 'admin') {
      echo json_encode(['status' => 'error', 'message' => 'Unauthorized access']);
      return;
    }

    if (empty($user_id)) {
      $response['status'] = 'error';
      $response['message'] = 'User ID cannot be empty';
      echo json_encode($response);
      return;
    }

    // Check if the user exists
    $stmt = $conn->prepare("SELECT user_id FROM users WHERE user_id = ?");
    $stmt->bind_param("s", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
      $stmt->close();
      $response['status'] = 'error';
      $response['message'] = 'This user does not exist';
      echo json_encode($response);
      return;
    }

    $stmt->close();

    // Delete the user
    $stmt = $conn->prepare('DELETE FROM users WHERE user_id = ?');
    $stmt->bind_param('s', $user_id);

    if ($stmt->execute()) {
      $response['status'] = 'success';
      $response['message'] = 'User deleted successfully';
      echo json_encode($response);
      return;
    } else {
      $response['status'] = 'error';
      $response['message'] = 'Error deleting user: ' . $conn->error;
      echo json_encode($response);
      return;
    }
  }
}
?>