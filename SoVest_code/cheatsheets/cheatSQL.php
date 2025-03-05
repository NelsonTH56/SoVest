<?php

    require_once 'includes/db_config.php';
    // ************************************************
    // Connect to a Database
    // ************************************************
    
    // Connect to a database (in this case 2023_399)
    $conn = getDbConnection();
    if (!$conn) {die("Connection failed: " . mysqli_connect_error());}

    // ************************************************
    // Insert Info into a Table
    // ************************************************

    // Let's set up some variables to put into a database
    $fName = "Zane";
    $lName = "Cochran";
    $color = "Pink";		
    $number = 13;		    
    // INSERT Statement
    //                    Table   Fields in Table               Variables to Insert
    $query = "INSERT INTO sample (fName, lName, color, number) VALUES ('$fName', '$lName', '$color', '$number')";
    $result = mysqli_query($conn, $query) or die ("Could not insert.");

    echo "Successfully Inserted!<br>";
    



    // ************************************************
    // SELECT Statement Examples
    // ************************************************

    // Select all the records in the database (order not important)
    echo "<h2>All Records (any order)</h2>";
    $query = "SELECT * FROM sample";
    $result = mysqli_query($conn, $query) or die ("Could not select.");
    while ($row = mysqli_fetch_array($result)){
        extract($row);
        echo "$id | $fName | $lName | $color | $number<br>";
    }			


     // Select all the records in the database (order by last name)
     echo "<h2>All Records (alphabetical by last name)</h2>";
     $query = "SELECT * FROM sample ORDER BY lname";
     $result = mysqli_query($conn, $query) or die ("Could not select.");
     while ($row = mysqli_fetch_array($result)){
         extract($row);
         echo "$id | $fName | $lName | $color | $number<br>";
     }			
 
 
     // Select all the records in the database (order by favorite number, largest to smallest)
     echo "<h2>All Records (order by favorite number, largest to smallest)</h2>";
     $query = "SELECT * FROM sample ORDER BY number DESC";
     $result = mysqli_query($conn, $query) or die ("Could not select.");
     while ($row = mysqli_fetch_array($result)){
         extract($row);
         echo "$id | $fName | $lName | $color | $number<br>";
     }		


     // Select only the records in the database (where favorite color is pink)
     echo "<h2>Only Records (where favorite color is pink)</h2>";
     $query = "SELECT * FROM sample WHERE color = 'Pink' ";
     $result = mysqli_query($conn, $query) or die ("Could not select.");
     while ($row = mysqli_fetch_array($result)){
         extract($row);
         echo "$id | $fName | $lName | $color | $number<br>";
     }	
     	

     // Select only the records in the database (where favorite number is great than 100)
     echo "<h2>Only Records (where favorite number is great than 100)</h2>";
     $query = "SELECT * FROM sample WHERE number > 100 ";
     $result = mysqli_query($conn, $query) or die ("Could not select.");
     while ($row = mysqli_fetch_array($result)){
         extract($row);
         echo "$id | $fName | $lName | $color | $number<br>";
     }		




    // ************************************************
    // Update Info in a Table
    // ************************************************

     $query = "UPDATE sample SET color = 'Cyan' WHERE color = 'Pink' ";
     $result = mysqli_query($conn, $query) or die ("Could not update.");
     echo "Color Updated!<br>";



    // ************************************************
    // Delete Info in a Table // WARNING!!!!
    // ************************************************
    $query = "DELETE FROM sample WHERE color = 'Pink'";
    $result = mysqli_query($conn, $query) or die ("Could not update.");
    echo "Record Deleted!<br>";


?>