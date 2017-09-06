<?php
require 'config.php';
require 'Slim/Slim.php';

\Slim\Slim::registerAutoloader();
$app = new \Slim\Slim();

$app->post('/login','login'); /* User login */
$app->post('/signup','signup'); /* User Signup  */
$app->post('/tes','tes');
$app->post('/project', 'project'); // Send Project
$app->post('/polygon', 'polygon'); // Send Project
$app->post('/ambil', 'ambil'); // Send Project
$app->post('/proyekbaru', 'proyekbaru'); // Send Project




$app->get('/internalUserDetails/:args','internalUserDetails'); /*  */

//$app->post('/userDetails','userDetails'); /* User Details */

$app->run();

/************************* USER LOGIN *************************************/
/* ### User login ### */
function login() {

    $request = \Slim\Slim::getInstance()->request();
    $data = json_decode($request->getBody());
    $username=$data->username;
    $password=$data->password;
    /*var_dump($username);
    var_dump($password);*/

    try {

        $db = getDB();
        $userData ='';
        $sql = "SELECT rf_user.username, user_role.role, rf_user.password FROM rf_user INNER JOIN user_role ON rf_user.username = user_role.username WHERE rf_user.username=:username AND rf_user.password=:password";
        //$sql = "SELECT id_rf_user, username FROM rf_user WHERE username=:username and password=:password ";
        $stmt = $db->prepare($sql);
        $stmt->bindParam("username", $username, PDO::PARAM_STR);
        //$password=hash('sha256',$password);
        $stmt->bindParam("password", $password, PDO::PARAM_STR);
        $stmt->execute();
        $mainCount=$stmt->rowCount();
        $userData = $stmt->fetch(PDO::FETCH_OBJ);
        if(!empty($userData))
        {
            $username=$userData->username;
            $userData->token = apiToken($username);
        }

        $db = null;
         if($userData){
               $userData = json_encode($userData);


               echo '{"userData": ' .$userData . '}';
            } else {
               echo '{"text":"Bad request wrong username and password"}';
            }


    }
    catch(PDOException $e) {
        echo '{"error":{"text":'. $e->getMessage() .'}}';
    }
}


/* ### User registration ### */
function signup() {
    $request = \Slim\Slim::getInstance()->request();
    $data = json_decode($request->getBody());
    $username=$data->username;
    $mPhone=$data->mPhone;
    $email=$data->email;
    $password=$data->password;
    $role = "pengguna";
    try {

        $username_check = preg_match('~^[A-Za-z0-9_]{3,20}$~i', $username);
        $email_check = preg_match('~^[a-zA-Z0-9._-]+@[a-zA-Z0-9._-]+\.([a-zA-Z]{2,4})$~i', $email);
        $password_check = preg_match('~^[A-Za-z0-9!@#$%^&*()_]{6,20}$~i', $password);


      if (strlen(trim($username))>0 && strlen(trim($password))>0 && strlen(trim($email))>0 && $email_check>0 && $username_check>0 && $password_check>0)
        {
            $db = getDB();
            $userData = '';
            $sql = "SELECT * FROM rf_user WHERE username=:username or email=:email";
            $stmt = $db->prepare($sql);
            $stmt->bindParam("username", $username,PDO::PARAM_STR);
            $stmt->bindParam("email", $email,PDO::PARAM_STR);
          //  $stmt->bindParam("m_phone", $m_phone,PDO::PARAM_STR);
            $stmt->execute();
            $mainCount=$stmt->rowCount();
            $created=time();
            if($mainCount==0)
            {

                /*Inserting user values*/
                $sql1="INSERT INTO rf_user (username,email,password,mPhone)VALUES(:username,:email,:password,:mPhone)";
                $stmt1 = $db->prepare($sql1);
                $stmt1->bindParam("username", $username,PDO::PARAM_STR);
                $password=hash('sha256',$data->password);
                $stmt1->bindParam("password", $password,PDO::PARAM_STR);
                $stmt1->bindParam("email", $email,PDO::PARAM_STR);
                $stmt1->bindParam("mPhone", $mPhone,PDO::PARAM_STR);
                $stmt1->execute();

                $sql2="INSERT INTO user_role (username,role) VALUES (:username,:role)";
                $stmt2 = $db->prepare($sql2);
                $stmt2->bindParam("username", $username,PDO::PARAM_STR);
                $stmt2->bindParam("role", $role,PDO::PARAM_STR);
                $stmt2->execute();

                $userData=internalUserDetails($username);

            }


            $db = null;


            if($userData){
               $userData = json_encode($userData);
                echo '{"userData": ' .$userData . '}';
            } else {
               echo '{"text":"Username atau Email sudah digunakan"}';
            }
        }
        else{
            echo '{"text":"Isi Data dengan benar"}';
        }
    }
    catch(PDOException $e) {
        echo '{"error":{"text":'. $e->getMessage() .'}}';
    }
}


/* ### internal Username Details ### */
function internalUserDetails($input) {

    try {
        $db = getDB();
        $sql = "SELECT username FROM rf_user WHERE username=:input";
        $stmt = $db->prepare($sql);
        $stmt->bindParam("input", $input,PDO::PARAM_STR);
        $stmt->execute();
        $usernameDetails = $stmt->fetch(PDO::FETCH_OBJ);
        $usernameDetails->token = apiToken($usernameDetails->username);
        $db = null;
        //return $usernameDetails;
        var_dump($usernameDetails);
        // $usernameDetails = json_encode($usernameDetails );
        //echo '{"userData": ' .$usernameDetails . '}';


    } catch(PDOException $e) {
        echo '{"error":{"text":'. $e->getMessage() .'}}';
    }

}

function tes(){
    $request = \Slim\Slim::getInstance()->request();
    $data = json_decode($request->getBody());
    $username=$data->username;
    $token=$data->token;

    $systemToken=apiToken($username);
    //var_dump($systemToken);
    try {

        if($systemToken == $token){
            $feedData = '';
            $db = getDB();
            $sql = "SELECT * FROM user_role WHERE username=:username";
            $stmt = $db->prepare($sql);
            $stmt->bindParam("username", $username, PDO::PARAM_STR);
            $stmt->execute();
            $feedData = $stmt->fetch(PDO::FETCH_OBJ);
          //  $feedData2 = $feedData->username;

            $db = null;
            $feedData = json_encode($feedData);
          //  $test = $feedData->username;
            echo '{"Data": ' . $feedData . '}';
        } else{
            echo '{"error":{"text":"No access"}}';
        }

    } catch(PDOException $e) {
        echo '{"error":{"text":'. $e->getMessage() .'}}';
    }



}
function project(){
    $request = \Slim\Slim::getInstance()->request();
    $data = json_decode($request->getBody());
   $username=$data->username;
  //  var_dump($username);

  $token=$data->token;
  //  var_dump($token);

    $subject = $data->subject;
  // var_dump($baru);

   $mulai = $data->mulai;
  //  var_dump($mulai);

  $akhir = $data->akhir;
  //  var_dump($akhir);

   $kegunaan = $data->kegunaan;
  //  var_dump($kegunaan);

  $latlng= $data->latlng;
  //var_dump($latlng);
  $city= $data->city;

  $latlng_ar = explode(',', $latlng);
  $odd = array();
  $even = array();

  foreach ($latlng_ar as $key => $value) {
      if ($key % 2 == 0) {
          $even[] = $value;
      }
      else {
          $odd[] = $value;
      }
  }
  //var_dump($odd);
  //  var_dump($even);


  $hasil = $data->hasil;
  $comment=$data->comment;
    $db = getDB();
   $systemToken=apiToken($username);
    //var_dump($systemToken);

      try {

        if($systemToken == $token){

            $sql = "INSERT INTO pesanan (subject, createdby, dtprojectstart, dtprojectend, projecttype, dtcreated, comment) VALUES (:subject,:createdby, :mulai, :akhir, :kegunaan,  NOW(), :comment)";
            $stmt = $db->prepare($sql);
            $stmt->bindParam(":subject", $subject, PDO::PARAM_STR);
            $stmt->bindParam(":createdby", $username, PDO::PARAM_STR);
            $stmt->bindParam(":mulai", $mulai, PDO::PARAM_STR);
            $stmt->bindParam(":akhir", $akhir, PDO::PARAM_STR);
            $stmt->bindParam(":kegunaan", $kegunaan, PDO::PARAM_STR);
            $stmt->bindParam(":comment", $comment, PDO::PARAM_STR);
            $stmt->execute();


            // SELECT ID ORDER
            $sql1 = "SELECT createdby, id_order FROM pesanan WHERE createdby='$username'";
            $result = $db->query($sql1);
            while($row = $result->fetch(PDO::FETCH_ASSOC)){
            $id_order = $row['id_order'];

            }

            // INSERT TO PESANAN OUTPUT
            foreach ($hasil as $value) {
            $sql2 = "INSERT INTO pesanan_output (id_order, output) VALUES ($id_order, '$value')";
            $db->query($sql2); }


            $sql3 = "INSERT INTO order_status (id_order, status, changed_by, dtadded) VALUES ($id_order, 'new', '$username', NOW())";
            $db->query($sql3);


            /*$sql2 = "INSERT INTO pesanan_output (id_order, output) VALUES (:id_order, :hasil)";
            $stmt2 = $db->prepare($sql2);
            $stmt2->bindParam(":id_order" ,$id_order, PDO::PARAM_INT);
            $stmt2->bindParam(":hasil", $hasil, PDO::PARAM_STR);
            $stmt2->execute();
            echo "true";*/

            $count = count($even);
            for($i = 0; $i < $count ; $i++) {
                $sql4="INSERT INTO order_location (id_order, latitude, longitude, kota) VALUES ($id_order, $even[$i], $odd[$i], '$city')";
                $db->query($sql4);
            }
            echo "true";


          } else{
              echo '{"error":{"text":"No access"}}';
          }

    } catch(PDOException $e) {
        echo '{"error":{"text":'. $e->getMessage() .'}}';
    }
}

function polygon (){
  $request = \Slim\Slim::getInstance()->request();
  $data = json_decode($request->getBody());
  $username=$data->username;
  $id_order=$data->id_order;
  $token=$data->token;


  $db = getDB();

  $sql = "SELECT id_order, latitude, longitude FROM order_location WHERE id_order=$id_order";
  $result = $db->query($sql);

  while($row = $result->fetch(PDO::FETCH_ASSOC)){
  $latitude[] =  $row["latitude"];
  $longitude [] =  $row["longitude"]; }
  $string = implode(",", $latitude);
  $string2 = implode(",", $longitude);

  //var_dump( $comma_separated );
  //$comma_separatedd = explode(" ", $comma_separated );
  //var_dump( $comma_separatedd );

  $polygon = json_encode($string);
  $polygon2 = json_encode($string2);
  //echo '{"latitude":' .$polygon .', "longitude" : '.$polygon2.'}';

  $sql1 = "SELECT * FROM pesanan_output WHERE id_order='$id_order'";
  $result1 = $db->query($sql1);
  while($row = $result1->fetch(PDO::FETCH_ASSOC)){
   $output[] = $row["output"];
   //$output = implode(",", $output);

   $hasil = json_encode($output);
  }
  echo '{"latitude":' .$polygon .', "longitude" : '.$polygon2.', "output": ' . $hasil . '}';

}

function ambil (){
  $request = \Slim\Slim::getInstance()->request();
  $data = json_decode($request->getBody());
  $username=$data->username;
  $token=$data->token;
  $db = getDB();
  $sql = "SELECT * FROM pesanan WHERE createdby = '$username'";
  $result = $db->query($sql);
  $feedData = $result->fetchAll(PDO::FETCH_OBJ);
  $db = null;
  echo '{"feedData": ' . json_encode($feedData) . '}';
}

function proyekbaru (){
  $request = \Slim\Slim::getInstance()->request();
  $data = json_decode($request->getBody());
  $username=$data->username;
  $token=$data->token;
  $db = getDB();
  /*$sql = "SELECT pesanan.createdby, pesanan.id_order, pesanan_output.id_order, pesanan_output.output FROM pesanan INNER JOIN pesanan_output WHERE pesanan.createdby = '$username' AND pesanan.id_order = pesanan_output.id_order ORDER BY pesanan.id_order DESC";*/
  $sql = "SELECT * FROM pesanan  WHERE createdby = '$username' ORDER BY id_order DESC";
  $result = $db->query($sql);
  $proyekBaru = $result->fetchAll(PDO::FETCH_OBJ);
  $db = null;
  echo '{"proyekBaru": ' . json_encode($proyekBaru) . '}';
}

?>
