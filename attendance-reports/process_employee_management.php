<?php
include "config.php"; 

$action = $_POST['action'] ?? '';

// --- Fetch Employee Data ---
if($action == 'fetch'){
    $stmt = $conn->query("SELECT id, user_name, email, department, role FROM users");
    $data = [];
    while($row = $stmt->fetch(PDO::FETCH_ASSOC)){
        $row['actions'] = '<button class="btn btn-sm btn-primary editBtn" data-id="'.$row['id'].'">Edit</button> 
                           <button class="btn btn-sm btn-danger deleteBtn" data-id="'.$row['id'].'">Delete</button>';
        $data[] = $row;
    }
    echo json_encode(['data'=>$data]);
    exit;
}

// --- Add or Update Employee ---
elseif($action == 'add' || $action == 'update'){
    $id = $_POST['employee_id'] ?? null;
    $name = $_POST['name'];
    $email = $_POST['email'];
    $department = $_POST['department'];    
    $role = $_POST['role'];
    $password = $_POST['password'] ?? '';

    // Duplicate email check
    $sql = ($action == 'add') ? "SELECT COUNT(*) FROM users WHERE email=?" 
                              : "SELECT COUNT(*) FROM users WHERE email=? AND id!=?";
    $stmt = $conn->prepare($sql);
    $stmt->execute(($action == 'add') ? [$email] : [$email, $id]);
    if($stmt->fetchColumn() > 0){
        echo json_encode(['status'=>'error','message'=>'Email already exists']);
        exit;
    }

    if($action == 'add'){
        if (empty($password)) {
            echo json_encode(['status'=>'error','message'=>'Password is required']);
            exit;
        }
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        $stmt = $conn->prepare("INSERT INTO users (user_name, email, password, department, role) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$name, $email, $hashed_password, $department, $role]);

        echo json_encode(['status'=>'success','message'=>'Employee added successfully']);
    } 
    else {
        $params = [$name, $email, $department, $role, $id];
        $sql = "UPDATE users SET user_name=?, email=?, department=?, role=? WHERE id=?";

        if(!empty($password)){
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $sql = "UPDATE users SET user_name=?, email=?, password=?, department=?, role=? WHERE id=?";
            $params = [$name, $email, $hashed_password, $department, $role, $id];
        }

        $stmt = $conn->prepare($sql);
        $stmt->execute($params);

        echo json_encode(['status'=>'success','message'=>'Employee updated successfully']);
    }
    
    exit;
}

// --- Get Employee Data ---
elseif($action == 'get'){
    $id = $_POST['employee_id'];
    $stmt = $conn->prepare("SELECT id, user_name, email, department, role FROM users WHERE id=?");
    $stmt->execute([$id]);
    echo json_encode($stmt->fetch(PDO::FETCH_ASSOC));
    exit;
}

// --- Delete Employee ---
elseif($action == 'delete'){
    $id = $_POST['employee_id'];
    $stmt = $conn->prepare("DELETE FROM users WHERE id=?");
    $stmt->execute([$id]);

    echo json_encode(['status'=>'success','message'=>'Employee deleted successfully']);
    exit;
}
?>
