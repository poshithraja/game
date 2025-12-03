<?php
// api.php
header('Content-Type: application/json');

$file = 'scores.json';

// Get the current high score
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if (file_exists($file)) {
        echo file_get_contents($file);
    } else {
        echo json_encode(["name" => "None", "score" => 0]);
    }
    exit;
}

// Update the high score
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['name']) || !isset($input['score'])) {
        echo json_encode(["status" => "error", "message" => "Invalid input"]);
        exit;
    }

    // Lock file to prevent conflicts if two people win at once
    $fp = fopen($file, 'c+');
    if (flock($fp, LOCK_EX)) {
        $currentData = json_decode(fread($fp, filesize($file)), true);
        
        // Only update if the new score is strictly higher
        if ($input['score'] > $currentData['score']) {
            ftruncate($fp, 0); // Clear file
            rewind($fp);
            fwrite($fp, json_encode($input));
            echo json_encode(["status" => "success", "new_high_score" => true]);
        } else {
            echo json_encode(["status" => "success", "new_high_score" => false]);
        }
        
        flock($fp, LOCK_UN);
    } else {
        echo json_encode(["status" => "error", "message" => "File lock failed"]);
    }
    fclose($fp);
}
?>
