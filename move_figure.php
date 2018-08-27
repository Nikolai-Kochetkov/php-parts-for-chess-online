<?php
//Get values from JS(sent by AJAX)
$game = $_GET['g']; //Game name
$fromPos = $_GET['f']; //Figure old position
$toPos = $_GET['t']; //Figure new position
$name = $_GET['n']; //Username, who did move
$swap = $_GET['s']; //Type of figure which player choosed instead of pawn or just message "noswap"
require_once("connect.php"); //Connect to database

//Send query and get data from table. Table contain all figures informations(position, color, type, image)
$oneSquare = mysqli_query($link, "SELECT * FROM ".$game." WHERE square_id='$fromPos'");
$squareInfo = mysqli_fetch_assoc($oneSquare);
$figureType = $squareInfo['figure_type'];
$figureColor = $squareInfo['figure_color'];
$figureImage = $squareInfo['image'];

//If player swapped pawn
if($swap != "noswap"){
    //Change current figure type(it is pawn) to new one
    $figureType = $swap;
    //Get new image
    $figureImage = $swap."_".$figureColor.".png";
}

//Check if there is two squares move or en passant move for pawn
$enPassantPos = "none";
if($figureType == "pawn"){
    //If moving pawn color is white
    if($figureColor == "white"){
        //If white pawn did 2 squares move
        if($fromPos[0] == $toPos[0] && intval($toPos[1]) - intval($fromPos[1]) == 2){
            //Get en passant possition (square between old and new pawn position)
            $enPassantPos = $fromPos[0].(intval($fromPos[1])+1);
            //Add new info to table
            $addEnPassant = "UPDATE ".$game." SET figure_type='en_passant', figure_color='white', image='' WHERE square_id='$enPassantPos'";
            mysqli_query($link, $addEnPassant);
        }
    //If moving pawn color is black
    }else{ 
        if($fromPos[0] == $toPos[0] && intval($toPos[1]) - intval($fromPos[1]) == -2){
            $enPassantPos = $fromPos[0].(intval($fromPos[1])-1);
            
            $addEnPassant = "UPDATE ".$game." SET figure_type='en_passant', figure_color='black', image='' WHERE square_id='$enPassantPos'";
            mysqli_query($link, $addEnPassant);
        }
    }
    //Remove pawn from table if another pawn attacked her en passant position
    //Get info about square where pawn did move
    $toPawnPos = mysqli_query($link, "SELECT * FROM ".$game." WHERE square_id='$toPos'");
    $posInfo = mysqli_fetch_assoc($toPawnPos);
    //If it is en passant position
    if($posInfo['figure_type'] == "en_passant"){
        //If black pawn attacked by white
        if($posInfo['figure_color'] == "white"){
            //Get current white pawn position
            $realPos = $toPos[0].(intval($toPos[1])+1);
            //Remove white pawn
            $deleteEnPassant = "UPDATE ".$game." SET figure_type='', figure_color='', image='' WHERE square_id='$realPos'";
            mysqli_query($link, $deleteEnPassant);
        //If white pawn attacked by black
        }else{
            $realPos = $toPos[0].(intval($toPos[1])-1);
            $deleteEnPassant = "UPDATE ".$game." SET figure_type='', figure_color='', image='' WHERE square_id='$realPos'";
            mysqli_query($link, $deleteEnPassant);
        }   
    }
   
}

//Remove en passant. Pawn can attack another pawn's en passant position only on the next move
$deleteOldEnPassant = "UPDATE ".$game." SET figure_type='', figure_color='', image='' WHERE figure_type='en_passant' AND square_id<>'$enPassantPos'";
mysqli_query($link, $deleteOldEnPassant);

//Check castlong for king
//Table game contain information about current games (usernames, gamename, who moving, castling possibility)
$cstlStatus = mysqli_query($link, "SELECT * FROM games WHERE game_name='$game'");
$statusInfo = mysqli_fetch_assoc($cstlStatus);
//If it is king figure
if($figureType == "king"){
    //All 4 castlings
    //If new position is correct and castling is possible
    if($toPos == "c1" && $statusInfo['cstl_wk_l'] == "yes"){
        //Move rook to his new position
        $moveRookTo = "UPDATE ".$game." SET figure_type='rook', figure_color='white', image='rook_white.png' WHERE square_id='d1'";
        mysqli_query($link, $moveRookTo);
        //Clear old rook position
        $moveRookFrom = "UPDATE ".$game." SET figure_type='', figure_color='', image='' WHERE square_id='a1'";
        mysqli_query($link, $moveRookFrom);
    }
    if($toPos == "g1" && $statusInfo['cstl_wk_r'] == "yes"){
        $moveRookTo = "UPDATE ".$game." SET figure_type='rook', figure_color='white', image='rook_white.png' WHERE square_id='f1'";
        mysqli_query($link, $moveRookTo);
        $moveRookFrom = "UPDATE ".$game." SET figure_type='', figure_color='', image='' WHERE square_id='h1'";
        mysqli_query($link, $moveRookFrom);
    }
    if($toPos == "c8" && $statusInfo['cstl_bk_l'] == "yes"){
        $moveRookTo = "UPDATE ".$game." SET figure_type='rook', figure_color='black', image='rook_black.png' WHERE square_id='d8'";
        mysqli_query($link, $moveRookTo);
        $moveRookFrom = "UPDATE ".$game." SET figure_type='', figure_color='', image='' WHERE square_id='a8'";
        mysqli_query($link, $moveRookFrom);
    }
    if($toPos == "g8" && $statusInfo['cstl_bk_r'] == "yes"){
        $moveRookTo = "UPDATE ".$game." SET figure_type='rook', figure_color='black', image='rook_black.png' WHERE square_id='f8'";
        mysqli_query($link, $moveRookTo);
        $moveRookFrom = "UPDATE ".$game." SET figure_type='', figure_color='', image='' WHERE square_id='h8'";
        mysqli_query($link, $moveRookFrom);
    }
}

//If there is no figures between king and rook then in table figure_type of positions between them is "castling" 
//Add information if castling impossible
$checkCastling = mysqli_query($link, "SELECT * FROM ".$game." WHERE square_id='$toPos'");
$cstlInfo = mysqli_fetch_assoc($checkCastling);
//If any figure did move to those postions then castling is impossible
if($cstlInfo['figure_type'] == "castling" && $figureType != "king"){
    //Remove "castling" from figure_type
    if($toPos == "b1" || $toPos == "c1" || $toPos == "d1"){
        $clearCstl = "UPDATE ".$game." SET figure_type='', figure_color='', image='' WHERE square_id='b1' OR square_id='c1' OR square_id='d1'";
    }
    if($toPos == "b8" || $toPos == "c8" || $toPos == "d8"){
        $clearCstl = "UPDATE ".$game." SET figure_type='', figure_color='', image='' WHERE square_id='b8' OR square_id='c8' OR square_id='d8'";
    }
    if($toPos == "f1" || $toPos == "g1"){
        $clearCstl = "UPDATE ".$game." SET figure_type='', figure_color='', image='' WHERE square_id='f1' OR square_id='g1'";
    }
    if($toPos == "f8" || $toPos == "g8"){
        $clearCstl = "UPDATE ".$game." SET figure_type='', figure_color='', image='' WHERE square_id='f8' OR square_id='g8'";
    }
    mysqli_query($link, $clearCstl);
}

//Update table, set new data for square. New figure position
$moveTo = "UPDATE ".$game." SET figure_type='$figureType', figure_color='$figureColor', image='$figureImage' WHERE square_id='$toPos'";
mysqli_query($link, $moveTo);
//Clear old figure position
$moveFrom = "UPDATE ".$game." SET figure_type='', figure_color='', image='' WHERE square_id='$fromPos'";
mysqli_query($link, $moveFrom);

//Remove castling possibility if rook or king moved
if($fromPos == "a1" || $fromPos == "e1"){
    $cstl = "UPDATE games SET cstl_wk_l='no' WHERE game_name='$game'";
    mysqli_query($link, $cstl);
    $cstlBoard = "UPDATE ".$game." SET figure_type='', figure_color='', image='' WHERE figure_type='castling' AND (square_id='b1' OR square_id='c1' OR square_id='d1')";
    mysqli_query($link, $cstlBoard);
}
if($fromPos == "h1" || $fromPos == "e1"){
    $cstl = "UPDATE games SET cstl_wk_r='no' WHERE game_name='$game'";
    mysqli_query($link, $cstl);
    $cstlBoard = "UPDATE ".$game." SET figure_type='', figure_color='', image='' WHERE figure_type='castling' AND (square_id='f1' OR square_id='g1')";
    mysqli_query($link, $cstlBoard);
}
if($fromPos == "a8" || $fromPos == "e8"){
    $cstl = "UPDATE games SET cstl_bk_l='no' WHERE game_name='$game'";
    mysqli_query($link, $cstl);
    $cstlBoard = "UPDATE ".$game." SET figure_type='', figure_color='', image='' WHERE figure_type='castling' AND (square_id='b8' OR square_id='c8' OR square_id='d8')";
    mysqli_query($link, $cstlBoard);
}
if($fromPos == "h8" || $fromPos == "e8"){
    $cstl = "UPDATE games SET cstl_bk_r='no' WHERE game_name='$game'";
    mysqli_query($link, $cstl);
    $cstlBoard = "UPDATE ".$game." SET figure_type='', figure_color='', image='' WHERE figure_type='castling' AND (square_id='f8' OR square_id='g8')";
    mysqli_query($link, $cstlBoard);
}

//Change information about move priority
//Table contain 2 player names
$curGame = mysqli_query($link, "SELECT * FROM games WHERE who_move='$name'");
$gameInfo = mysqli_fetch_assoc($curGame);
//If currently moving 1st player then get 2nd name
if($gameInfo['from_user'] == $name){
    $newMover = $gameInfo['to_user'];
}
//If currently moving 2nd player then get 1st name
if($gameInfo['to_user'] == $name){
    $newMover = $gameInfo['from_user'];
}
//Change current player to another
$whoMove = "UPDATE games SET who_move='$newMover' WHERE who_move='$name'";
mysqli_query($link, $whoMove);

//If king or rook didn't move then castling is possible, but another figures can prevent
//All 4 castlings
if($gameInfo['cstl_wk_l'] == "yes"){
    $canBeCastling = mysqli_query($link, "SELECT * FROM ".$game." WHERE square_id='b1' OR square_id='c1' OR square_id='d1'");
    $castlingIsReal = 1;
    //Check positions beteen king and rook
    while($castlingInfo = mysqli_fetch_assoc($canBeCastling)){
        if($castlingInfo['figure_type'] != ""){
            $castlingIsReal = 0;
        }
    }
    //If positions are free then king can move
    if($castlingIsReal == 1){
        $setCastling = "UPDATE ".$game." SET figure_type='castling', figure_color='white', image='' WHERE square_id='b1' OR square_id='c1' OR square_id='d1'";
        mysqli_query($link, $setCastling);
    }
}
if($gameInfo['cstl_wk_r'] == "yes"){
    $canBeCastling = mysqli_query($link, "SELECT * FROM ".$game." WHERE square_id='f1' OR square_id='g1'");
    $castlingIsReal = 1;
    while($castlingInfo = mysqli_fetch_assoc($canBeCastling)){
        if($castlingInfo['figure_type'] != ""){
            $castlingIsReal = 0;
        }
    }
    if($castlingIsReal == 1){
        $setCastling = "UPDATE ".$game." SET figure_type='castling', figure_color='white', image='' WHERE square_id='f1' OR square_id='g1'";
        mysqli_query($link, $setCastling);
    }
}
if($gameInfo['cstl_bk_l'] == "yes"){
    $canBeCastling = mysqli_query($link, "SELECT * FROM ".$game." WHERE square_id='b8' OR square_id='c8' OR square_id='d8'");
    $castlingIsReal = 1;
    while($castlingInfo = mysqli_fetch_assoc($canBeCastling)){
        if($castlingInfo['figure_type'] != ""){
            $castlingIsReal = 0;
        }
    }
    if($castlingIsReal == 1){
        $setCastling = "UPDATE ".$game." SET figure_type='castling', figure_color='black', image='' WHERE square_id='b8' OR square_id='c8' OR square_id='d8'";
        mysqli_query($link, $setCastling);
    }
}
if($gameInfo['cstl_bk_r'] == "yes"){
    $canBeCastling = mysqli_query($link, "SELECT * FROM ".$game." WHERE square_id='f8' OR square_id='g8'");
    $castlingIsReal = 1;
    while($castlingInfo = mysqli_fetch_assoc($canBeCastling)){
        if($castlingInfo['figure_type'] != ""){
            $castlingIsReal = 0;
        }
    }
    if($castlingIsReal == 1){
        $setCastling = "UPDATE ".$game." SET figure_type='castling', figure_color='black', image='' WHERE square_id='f8' OR square_id='g8'";
        mysqli_query($link, $setCastling);
    }
}
?>