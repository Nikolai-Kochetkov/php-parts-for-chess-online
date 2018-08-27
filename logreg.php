<?php
// Get values from JS(sent by AJAX)
$name = $_GET["n"];
$pswd = $_GET["p"];
$status = $_GET["s"];
// Connect to database
require_once("connect.php");
// Select table from database
$allUsers = mysqli_query($link, "SELECT * FROM registrated_users");
// Autorisation
if($status == "logIn"){
    // If table has data
    if(mysqli_num_rows($allUsers) > 0){
        // Checkevery line and convert data to associative array
        while($oneUser = mysqli_fetch_assoc($allUsers)){
            // If name and password changed by md5 are correct
            if($name == $oneUser['nickname'] && md5($pswd) == $oneUser['password']){
                $onlineUsers = mysqli_query($link, "SELECT * FROM online_users");
                // Query for adding user to table for online users
                $addOnlineUser = "INSERT INTO online_users (nickname) VALUES ('$name')";
                // If already someone online
                if(mysqli_num_rows($onlineUsers) > 0){
                    $already_online = false;
                    // Search by name
                    while($oneOnlineUser = mysqli_fetch_assoc($onlineUsers)){
                        //If user with same name already online
                        if($name == $oneOnlineUser['nickname']){
                            $already_online = true;
                        }
                    }
                    // If didn't find same name in table then add user
                    if($already_online == false){
                        // Send query
                        mysqli_query($link, $addOnlineUser);
                        // Return true message to JS code
                        echo "true";
                    }else{
                        // Return false message to JS code
                        echo "false";
                    }
                }else{
                    // If table is empty then add user
                    mysqli_query($link, $addOnlineUser);
                    echo "true";
                }
            }
        }
    }
}
// Registration
if($status == "registrate"){
    // Convert password
    $hidePswd = md5($pswd);
    // Query for adding user
    $addUser = "INSERT INTO registrated_users (nickname, password) VALUES ('$name', '$hidePswd')";
    if(mysqli_num_rows($allUsers) > 0){
        $already_used = false;
        // Serach by name inside table with registrated users
        while($oneUser = mysqli_fetch_assoc($allUsers)){
            // If name already exist
            if($name == $oneUser['nickname']){
                $already_used = true;
            }
        }
        // If didn't find name
        if($already_used == false){
            // Send query
            mysqli_query($link, $addUser);
            echo "true";
        }else{
            // Return false message if found name
            echo "false";
        }
    }else{
        // If table empty then add user
        mysqli_query($link, $addUser);
        echo "true";
    }
}
?>