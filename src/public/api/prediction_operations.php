<?php
/**
 * Prediction Operations API
 * 
 * Handles CRUD operations for stock predictions using Eloquent ORM
 */

// Include Eloquent configuration
require_once '../bootstrap/database.php';

// Include models
require_once '../database/models/User.php';
require_once '../database/models/Stock.php';
require_once '../database/models/Prediction.php';

// Use the Models namespace
use Database\Models\User;
use Database\Models\Stock;
use Database\Models\Prediction;

session_start();

// Check if user is logged in
if (!isset($_COOKIE["userID"])) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'User not logged in',
        'redirect' => 'login.php'
    ]);
    exit;
}

$userID = $_COOKIE["userID"];

try {
    // Verify user exists using Eloquent
    $user = User::find($userID);
    if (!$user) {
        respond_json(false, 'User not found');
        exit;
    }
} catch (Exception $e) {
    respond_json(false, 'Database connection failed: ' . $e->getMessage());
    exit;
}

// Determine action
$action = isset($_POST['action']) ? $_POST['action'] : (isset($_GET['action']) ? $_GET['action'] : '');

switch ($action) {
    case 'create':
        create_prediction($userID);
        break;
    case 'update':
        update_prediction($userID);
        break;
    case 'delete':
        delete_prediction($userID);
        break;
    case 'get':
        get_prediction($userID);
        break;
    default:
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => 'Invalid action specified'
        ]);
        break;
}

/**
 * Create a new prediction
 */
function create_prediction($userID) {
    // Validate required fields
    $required_fields = ['stock_id', 'prediction_type', 'end_date', 'reasoning'];
    foreach ($required_fields as $field) {
        if (!isset($_POST[$field]) || empty($_POST[$field])) {
            respond_json(false, "Missing required field: $field");
            return;
        }
    }
    
    try {
        // Validate inputs
        $stock_id = $_POST['stock_id'];
        $prediction_type = $_POST['prediction_type'];
        $target_price = isset($_POST['target_price']) && !empty($_POST['target_price']) ? 
                        (float) $_POST['target_price'] : null;
        $end_date = $_POST['end_date'];
        $reasoning = $_POST['reasoning'];
        
        // Validate prediction type
        if ($prediction_type !== 'Bullish' && $prediction_type !== 'Bearish') {
            respond_json(false, "Invalid prediction type. Must be 'Bullish' or 'Bearish'");
            return;
        }
        
        // Validate stock exists
        $stock = Stock::find($stock_id);
        if (!$stock) {
            respond_json(false, "Stock not found");
            return;
        }
        
        // Validate end date (must be in the future)
        $end_date_obj = new DateTime($end_date);
        $now = new DateTime();
        if ($end_date_obj <= $now) {
            respond_json(false, "End date must be in the future");
            return;
        }
        
        // Create new prediction using Eloquent
        $prediction = new Prediction([
            'user_id' => $userID,
            'stock_id' => $stock_id,
            'prediction_type' => $prediction_type,
            'target_price' => $target_price,
            'end_date' => $end_date,
            'reasoning' => $reasoning,
            'prediction_date' => date('Y-m-d H:i:s'),
            'is_active' => 1,
            'accuracy' => null
        ]);
        
        $prediction->save();
        
        respond_json(true, "Prediction created successfully", ['prediction_id' => $prediction->prediction_id], 'my_predictions.php');
    } catch (Exception $e) {
        respond_json(false, "Error creating prediction: " . $e->getMessage());
    }
}

/**
 * Update an existing prediction
 */
function update_prediction($userID) {
    try {
        // Validate required fields
        if (!isset($_POST['prediction_id']) || empty($_POST['prediction_id'])) {
            respond_json(false, "Missing prediction ID");
            return;
        }
        
        $prediction_id = $_POST['prediction_id'];
        
        // Check if prediction exists and belongs to user using Eloquent
        $prediction = Prediction::where('prediction_id', $prediction_id)
                              ->where('user_id', $userID)
                              ->first();
        
        if (!$prediction) {
            respond_json(false, "Prediction not found or you don't have permission to edit it");
            return;
        }
        
        // Check if prediction can be edited (is still active)
        if (!$prediction->is_active) {
            respond_json(false, "Cannot edit inactive predictions");
            return;
        }
        
        // Get inputs with fallbacks to current values
        $prediction_type = isset($_POST['prediction_type']) && !empty($_POST['prediction_type']) ? 
                        $_POST['prediction_type'] : $prediction->prediction_type;
        
        $target_price = isset($_POST['target_price']) && $_POST['target_price'] !== '' ? 
                        (float) $_POST['target_price'] : $prediction->target_price;
        
        $end_date = isset($_POST['end_date']) && !empty($_POST['end_date']) ? 
                    $_POST['end_date'] : $prediction->end_date;
        
        $reasoning = isset($_POST['reasoning']) && !empty($_POST['reasoning']) ? 
                    $_POST['reasoning'] : $prediction->reasoning;
        
        // Validate prediction type
        if ($prediction_type !== 'Bullish' && $prediction_type !== 'Bearish') {
            respond_json(false, "Invalid prediction type. Must be 'Bullish' or 'Bearish'");
            return;
        }
        
        // Validate end date (must be in the future)
        $end_date_obj = new DateTime($end_date);
        $now = new DateTime();
        if ($end_date_obj <= $now) {
            respond_json(false, "End date must be in the future");
            return;
        }
        
        // Update prediction using Eloquent
        $prediction->prediction_type = $prediction_type;
        $prediction->target_price = $target_price;
        $prediction->end_date = $end_date;
        $prediction->reasoning = $reasoning;
        $prediction->save();
        
        respond_json(true, "Prediction updated successfully", [], 'my_predictions.php');
    } catch (Exception $e) {
        respond_json(false, "Error updating prediction: " . $e->getMessage());
    }
}

/**
 * Delete a prediction
 */
function delete_prediction($userID) {
    try {
        // Validate required fields
        if (!isset($_POST['prediction_id']) || empty($_POST['prediction_id'])) {
            respond_json(false, "Missing prediction ID");
            return;
        }
        
        $prediction_id = $_POST['prediction_id'];
        
        // Check if prediction exists and belongs to user using Eloquent
        $prediction = Prediction::where('prediction_id', $prediction_id)
                              ->where('user_id', $userID)
                              ->first();
        
        if (!$prediction) {
            respond_json(false, "Prediction not found or you don't have permission to delete it");
            return;
        }
        
        // Delete prediction using Eloquent
        $prediction->delete();
        
        respond_json(true, "Prediction deleted successfully");
    } catch (Exception $e) {
        respond_json(false, "Error deleting prediction: " . $e->getMessage());
    }
}

/**
 * Get a single prediction
 */
function get_prediction($userID) {
    try {
        if (!isset($_GET['prediction_id']) || empty($_GET['prediction_id'])) {
            respond_json(false, "Missing prediction ID");
            return;
        }
        
        $prediction_id = $_GET['prediction_id'];
        
        // Use Eloquent with eager loading to get prediction with related stock data
        $prediction = Prediction::with('stock')
                              ->where('prediction_id', $prediction_id)
                              ->where('user_id', $userID)
                              ->first();
        
        if ($prediction) {
            // Format data to match the old response structure
            $predictionData = $prediction->toArray();
            $predictionData['symbol'] = $prediction->stock->symbol;
            $predictionData['company_name'] = $prediction->stock->company_name;
            
            respond_json(true, "Prediction retrieved successfully", $predictionData);
        } else {
            respond_json(false, "Prediction not found or you don't have permission to view it");
        }
    } catch (Exception $e) {
        respond_json(false, "Error retrieving prediction: " . $e->getMessage());
    }
}

/**
 * Output a JSON response
 */
function respond_json($success, $message, $data = [], $redirect = null) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data,
        'redirect' => $redirect
    ]);
    
    exit;
}
?>