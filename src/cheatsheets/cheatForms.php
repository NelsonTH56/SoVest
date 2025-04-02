<?php

// Form Data
$email = $_POST['email'];   // Extracts the POST data from the 'email' form element and saves it as the variable $email
$link = $_GET['link'];      // Extracts the GET data from the 'link' form element and saves it as the variable $link

// Sometimes we need to redirect users to another page
header("Location: index.php");


// We can save users data with a cookie!
setcookie("userID", $id, time() + (86400 * 30), "/"); // Sets cookie for 30 days
?>

<form action="account2.php" method="post">

    <!-- SAMPLE TEXT FORM (WITH REQUIRED) -->
    <div class="form-floating">
          <input type="text" class="form-control" id="sampleName" name="sampleName" required>
          <label for="sampleName">First Name</label>
    </div>
    <br>

    <!-- SAMPLE EMAIL FORM (WITH REQUIRED) -->
    <div class="form-floating">
          <input type="email" class="form-control" id="sampleEmail" name="sampleEmail" required>
          <label for="sampleEmail">Email</label>
    </div>
    <br>

    <!-- SAMPLE PASSWORD FORM (WITH REQUIRED) -->
    <div class="form-floating">
          <input type="password" class="form-control" id="samplePass" name="samplePass" required>
          <label for="samplePass">Password</label>
        </div>
    <br>

    
    <!-- SAMPLE DATE/TIME FORM -->
    <div class="form-floating">
        <input type="datetime-local" class="form-control" id="sampleTime" name="sampleTime">
        <label for="sampleTime">Sample Time</label>
       </div>
    <br>

    <!-- SAMPLE CHECKBOX FORM -->
    <div class="form-check" align="left">
        <input type="checkbox" class="form-check-input" id="check1" name="check1">
        <label for="check1">Sample Checkbox 1</label>
       </div>
    <br>

    <!-- SAMPLE DROPDOWN FORM -->
    <div class="form-floating">
        <select class="form-control" id="colors" name="colors">
            <option value="red">Red</option>
            <option value="green">Green</option>
            <option value="blue">Blue</option>
        </select>
        <label for="colors">Favorite Color</label>
    </div>
    <br>

    <!-- SAMPLE SUBMIT BUTTON -->
    <button class="btn btn-primary w-100 py-2" type="submit">Submit</button>
</form>
