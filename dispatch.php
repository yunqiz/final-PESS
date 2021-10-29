<!doctype html>
<html>
<head>
<meta charset="UTF-8">
<link href="yunqistyle.css" rel="stylesheet" type="text/css">
<div id="banner" style="text-align:center; margin:0 auto;">
<title>Police Emergency Service System</title>
</head>
    
<body>
<?php require 'nav.php';?>
    
<?php // if post back
    if (isset($_POST["btnDispatch"]))
    {
        require_once 'db.php';
        
        // create database connection
        $mysqli = mysqli_connect(DB_SERVER, DB_USER, DB_PASSWORD, DB_DATABASE);
        // check conntection
        if ($mysqli->connect_errno)
        {
            die("Failed to connect to MySQL: ".$mysqli->connect_errno);
        }
        
        $patrolcarDispatched = $_POST["chkPatrolcar"]; // array of patrolcar being dispatched from post back
        $numOfPatrolcarDispatched = count($patrolcarDispatched);
        
        //insert new incident
        $incidentSatus;
        if ($numOfPatrolcarDispatched > 0) {
            $incidentStatus='2'; //incident status to be set as Dispatched
        } else {
            $incidentStatus='1'; // incident status to be set as Pending
        }
        
        $sql = "INSERT INTO incident (callerName, phoneNumber, incidentTypeId, incidentLocation, incidentDesc, incidentStatusId) VALUES (?, ?, ?, ?, ?, ?)";
        
        if (!($stmt = $mysqli->prepare($sql)))
        {
            die("Prepare failed: ".$mysqli->errno);
        }
        
     if (!$stmt->bind_param('ssssss', $_POST['callerName'],
                          $_POST['contactNo'],
                          $_POST['incidentType'],
                          $_POST['location'],
                          $_POST['incidentDesc'],
                          $incidentStatus))
     
     { echo $sql;
         die("Binding parameters failed: ".$stmt->errno);
     }
        
        if (!$stmt->execute())
        {
            die("Insert incident table failed: ".$stmt->errno);
        }
        
        // retrieve incident_id for the newly inserted incident
        $incidentId=mysqli_insert_id($mysqli);;
        
        // update patrolcar status table and add into dispatch table
        for($i=0; $i < $numOfPatrolcarDispatched; $i++)
        {
            //update patro car status
            $sql = "UPDATE patrolcar SET patrolcarStatusId ='1' WHERE patrolcarId = ?";
            
            if(!($stmt = $mysqli->prepare($sql))) {
                die("Prepare failed: ".$stmt->errno);
            }
            
            if(!$stmt->bind_param('s', $patrolcarDispatched[$i])){
                die("Binding parameters failed: ".$stmt->errno);
            }
            
            if(!$stmt->execute()) {
                die("Update patrolcar_status table failed: ".$stmt->errno);
            }
            
            // insert dispatch data
            $sql = "INSERT INTO dispatch (incidentId, patrolcarId, timeDispatched) VALUES (?, ?, NOW())";
            
            if (!($stmt = $mysqli->prepare($sql))) {
                die("Prepare failed: ".$mysqli->errno);
            }
            
            if(!$stmt->bind_param('ss', $incidentId,
                                  $patrolcarDispatched[$i])){
                die("Binding parameters failed: ".$stmt->errno);
            }
            
            if(!$stmt->execute()){
                die("Insert dispatch table failed: ".$stmt->errno);
            }
        }
        
        $stmt->close();
        
        $mysqli->close();
    } ?>
    
<!-- display the incident infomation passed from logcall.php-->
<form name="form1" method="post" action="<?php echo htmlentities($_SERVER['PHP_SELF']); ?> ">
<table style="border:1px solid black;margin-left:auto;margin-right:auto;">
    <tr>
    <td colspan="2">Incident Detail</td>
    </tr>
    <tr>
    <td>Caller's Name : </td>
    <td><?php echo $_POST['callerName'] ?>
        <input type="hidden" name="callerName" id="callerName"
        value="<?php echo $_POST['callerName'] ?>"></td>
    </tr>
    <tr>
    <td>Contact No :</td>
    <td><?php echo $_POST['contactNo']?> <input 
    type="hidden" name="contactNo" id="contactNo"
    value="<?php echo $_POST['contactNo']?>"></td>
    </tr>
    <tr>
    <td>Location :</td>
    <td><?php echo $_POST['location'] ?> <input
    type="hidden" name="location" id="location"
    value="<?php echo $_POST['location'] ?>"></td>
    </tr>
    <tr>
    <td>Incident Type :</td>
    <td><?php echo $_POST['incidentType'] ?> <input
    type="hidden" name="incidentType" id="incidentType"
    value="<?php echo $_POST['incidentType'] ?>"></td>
    </tr>
    <tr>
    <td>Description: </td>
    <td><textarea name="incidentDesc" cols="45" rows="5" readonly id="incidentDesc"><?php echo $_POST['incidentDesc']?></textarea><input name="incidentDesc" type="hidden" id="incidentDesc" value="<?php echo $_POST['incidentDesc']?>"></td>
    </tr>
</table>
    
    <?php
// connect to a database
require_once 'db.php'; 
        
// create a database connection
$mysqli = mysqli_connect(DB_SERVER, DB_USER, DB_PASSWORD, DB_DATABASE);
// check connection 
if($mysqli->connect_errno) {
    die("Failed to connect to my MYSQL: ".$mysqli->connect_errno);
}
        
// retrieve from patrolcar table those patrol cars that are 2:Patrol or 3: Free
$sql = "SELECT patrolCarId, statusDesc FROM patrolcar JOIN patrolcar_status ON patrolcar.patrolCarStatusId=patrolcar_status.StatusId WHERE patrolcar.patrolCarStatusId='2' OR patrolcar.patrolCarStatusId='3'";
        
if (!($stmt = $mysqli->prepare($sql))) {
    die("Prepare failed: ".$mysqli->errno);
}
        
if (!$stmt->execute()) {
    die("Execute failed: ".$stmt->errno);
}
        
if (!($resultset = $stmt->get_result())) {
    die("Getting result set failed: ".$stmt->errno);
}
        
$patrolcarArray;
        
while ($row = $resultset->fetch_assoc()) {
    $patrolcarArray[$row['patrolCarId']] = $row['statusDesc'];
}
        
$stmt->close();
        
$resultset->close();
        
$mysqli->close();      
?>

<!-- populate table with patrol car data -->
<br><br><table border="1" align="center">
    <tr> 
    <td colspan="3">Dispatch Patrolcar Panel</td>
    </tr>
    <?php foreach($patrolcarArray as $key=>$value){
    ?>
    <tr>
    <td><input type="checkbox" name="chkPatrolcar[]"
               value="<?php echo $key?>"></td>
    <td><?php echo $key ?></td>
    <td><?php echo $value ?></td>
    </tr>
<?php } ?>
    <tr>
        <td><input type="reset" name="btnCancel" id="btnCancel" value="Reset"></td>
        <td colspan="2">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<input type="submit" name="btnDispatch" id="btnDispatch" value="Dispatch"
            </td>
            </tr>
    </table>

</form>
    

</body>
</html>
