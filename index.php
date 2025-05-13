<?php
session_start();

$conn = mysqli_connect("localhost", "root", "", "todo");

// Check connection
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Sanitize input
    $title = mysqli_real_escape_string($conn, $_POST['title']);
    $description = mysqli_real_escape_string($conn, $_POST['description']);
    $category = mysqli_real_escape_string($conn, $_POST['category']);
    $due_date = mysqli_real_escape_string($conn, $_POST['due_date']);
    $priority = mysqli_real_escape_string($conn, $_POST['priority']);

    // Validate input
    if (empty($title)) {
        $_SESSION['message'] = "Task title cannot be empty!";
        $_SESSION['message_type'] = "error";
    } else {
        // Prepare and bind query using procedural style
        $sql = "INSERT INTO tasks (title, description, category, due_date, priority) VALUES (?, ?, ?, ?, ?)";
        $stmt = mysqli_stmt_init($conn);

        if (mysqli_stmt_prepare($stmt, $sql)) {
            mysqli_stmt_bind_param($stmt, "sssss", $title, $description, $category, $due_date, $priority);

            if (mysqli_stmt_execute($stmt)) {
                $_SESSION['message'] = "Task added successfully!";
                $_SESSION['message_type'] = "success";
            } else {
                $_SESSION['message'] = "Error: " . mysqli_stmt_error($stmt);
                $_SESSION['message_type'] = "error";
            }

            mysqli_stmt_close($stmt);
        } else {
            $_SESSION['message'] = "Error preparing statement!";
            $_SESSION['message_type'] = "error";
        }
    }

    header("Location: index.php");
    exit;
}

function getTasks($conn) {
    $output = '';
    $sql = "SELECT * FROM tasks ORDER BY created_at DESC";
    $result = mysqli_query($conn, $sql);

    if (mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            $statusClass = $row['is_completed'] ? 'completed' : 'active';
            $priorityClass = 'priority-' . $row['priority'];

            $output .= "<li id='task-" . $row['id'] . "' class='todo-item $statusClass $priorityClass'>";
            $output .= "<div class='todo-info'>";
            $output .= "<div class='todo-title'>" . htmlspecialchars($row['title']) . "</div>";

            if (!empty($row['description'])) {
                $output .= "<div class='todo-description'>" . htmlspecialchars($row['description']) . "</div>";
            }

            $output .= "<div class='todo-date'>";
            $output .= "Category: " . htmlspecialchars($row['category']) . " | ";
            $output .= "Due: " . htmlspecialchars($row['due_date']) . " | ";
            $output .= "Priority: " . ucfirst($row['priority']);
            $output .= "</div></div>";

            $output .= "<div class='todo-actions'>";
            $completeIcon = $row['is_completed'] ? '↩️' : '✓';
            $completeTitle = $row['is_completed'] ? 'Mark as Incomplete' : 'Mark as Complete';
            $output .= "<button class='todo-action-btn complete-btn' data-id='" . $row['id'] . "' title='$completeTitle'>$completeIcon</button>";
            $output .= "<button class='todo-action-btn delete-btn' data-id='" . $row['id'] . "' title='Delete Task'>🗑️</button>";
            $output .= "</div></li>";
        }
    } else {
        $output .= "<li class='no-tasks'>No tasks found.</li>";
    }

    return $output;
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Advanced Todo List</title>
    <link rel="stylesheet" href="style.css">
    
    
   
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Advanced To-Do List</h1>
            <p>Organize your tasks with style and efficiency</p>
            <div class="canvas-container" id="canvas-container"></div>
        </div>

        <?php
        // Display messages if any
        if (isset($_SESSION['message'])) {
            echo "<div class='message " . $_SESSION['message_type'] . "'>" . $_SESSION['message'] . "</div>";
            unset($_SESSION['message']);
            unset($_SESSION['message_type']);
        }
        ?>

        <div class="main-content">
            <div class="todo-form-container">
                <h2>Add New Task</h2>
                <form id="todo-form" method="POST" action="">
                    <div class="form-group">
                        <label for="task-title">Task Title</label>
                        <input type="text" id="task-title" name="title" class="todo-input" placeholder="What do you need to do?" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="task-description">Description (Optional)</label>
                        <textarea id="task-description" name="description" class="todo-input" rows="3" placeholder="Add more details about this task..."></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="task-category">Category</label>
                        <select id="task-category" name="category" class="category-dropdown">
                            <option value="personal">Personal</option>
                            <option value="work">Work</option>
                            <option value="study">Study</option>
                            <option value="health">Health</option>
                            <option value="finance">Finance</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="task-due-date">Due Date</label>
                        <input type="date" id="task-due-date" name="due_date" class="todo-input">
                    </div>
                    
                    <div class="form-group">
                        <label>Priority</label>
                        <div class="priority-selector">
                            <div class="priority-option priority-low" data-priority="low">Low</div>
                            <div class="priority-option priority-medium selected" data-priority="medium">Medium</div>
                            <div class="priority-option priority-high" data-priority="high">High</div>
                        </div>
                        <input type="hidden" id="priority-value" name="priority" value="medium">
                    </div>
                    
                    <button type="submit" class="add-button">Add Task</button>
                </form>
            </div>
            
            <div class="todo-list-container">
                <div class="todo-list-header">
                    <h2>Your Tasks</h2>
                    <select id="filter-todos" class="filter-dropdown">
                        <option value="all">All Tasks</option>
                        <option value="active">Active</option>
                        <option value="completed">Completed</option>
                        <option value="low">Low Priority</option>
                        <option value="medium">Medium Priority</option>
                        <option value="high">High Priority</option>
                    </select>
                </div>
                
                <div class="loader" id="loader"></div>
                
                <ul class="todo-list" id="todo-list">
                    <?php echo getTasks($conn); ?>
                </ul>
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js"></script>
    <script src="script.js"></script>



</body>
</html>