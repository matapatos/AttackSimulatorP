<?php
const INVALID_ARGUMENTS = "Invalid request arguments.";
const MISSING_ARGUMENTS = "Request arguments missing.";
const REQUEST_GENERAL_ERROR = "Error during the request.";

if (!session_id()) {
    session_start();
}
//
// Recommended way to include parent theme styles.
//  (Please see http://codex.wordpress.org/Child_Themes#How_to_Create_a_Child_Theme)
//  
add_action( 'wp_enqueue_scripts', 'theme_enqueue_styles' );
function theme_enqueue_styles() {
    wp_enqueue_style( 'parent-style', get_template_directory_uri() . '/style.css' );
    wp_enqueue_style( 'child-style',
        get_stylesheet_directory_uri() . '/style.css',
        array('parent-style')
    );
}

function theme_enqueue_scripts()
{
    wp_enqueue_script( 'attacks-script' );
    wp_register_script('angular', 'https://ajax.googleapis.com/ajax/libs/angularjs/1.3.15/angular.min.js');
    wp_enqueue_script( 'angular' );
}
add_action( 'wp_enqueue_scripts', 'theme_enqueue_scripts' );

$allAttacks;

function getAllAttacks(){
    global $wpdb;
    return $wpdb->get_results( 'SELECT * FROM attacks', OBJECT);
}

function showAttacks(){
    global $allAttacks;
    $allAttacks = getAllAttacks();
    $size = count($allAttacks);
    $i = 0;
    echo '<div ng-app="attacks">
		<div ng-controller="AttacksController">
			<form id="myForm" name="myForm" ng-submit="submit()" novalidate>
				<div id="messages"></div>
				<label>Search:
					<input ng-model="search.$">
				</label>
				<br/>
				<label>Operating System:
					<select ng-model="search.os">
						<option value="">All</option>
						<option value="Windows">Windows</option>
						<option value="Linux">Linux</option>
					</select>
				</label>
				<br/>
				<label>Select:
					<select ng-model="search.select">
						<option value="">All</option>
						<option value="true">Yes</option>
						<option value="false">No</option>
					</select>
				</label>
				<br/>
				<label>Generate type:
					<select ng-model="geneType" ng-change="checkIsRemotely()">
						<option value="">Instructions</option>
						<option value="exe">Executable file</option>
						<option value="remotely">Remotely</option>
					</select>
				</label>
				<br/>
				<table>
					<tr>
						<td>Name</td>
						<td>Description</td>
						<td>Operating System</td>
						<td>Select</td>
					</tr>
					<tr ng-repeat="a in attacks | filter:search">
						<td>{{ a.name }}</td>
						<td>{{ a.description }}</td>
						<td>{{ a.os }}</td>
						<td>
							<input type="checkbox" ng-model="a.select" ng-change="attackAddedOrRemoved({{a}})"/>
						</td>
					</tr>
				</table>
				<div id="configs" ng-show="isRemotely">
					<h3>Configurations</h3>
					<label>IP address:
						<input id="ip" type="text" placeholder="IP address" name="ip_address" ng-pattern="/^(?:(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.){3}(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)$/" required/>
						<span id="rf_ip" class="required_field">*</span>
					</label>
					<br/>
					<label>Username:
						<input id="username" type="text" placeholder="Username" ng-minlength="1" required/>
						<span class="required_field">*</span>
					</label>
					<br/>
					<label>Password:
						<input id="password" type="password" placeholder="Password" ng-minlength="1" required/>
						<span class="required_field">*</span>
					</label>
				</div>
				<input id="myAttacks" type="hidden" name="myAttacks"/>
				<input id="btnSubmit" type="submit" value="Submit" />
			</form>
			<div id="loader_panel">
				<ul class="loader">
					<li></li>
					<li></li>
					<li></li>
				</ul>
			</div>
		</div>
	</div>
	<script type="text/javascript">
		var ERROR_MULTIPLE_OS_SELECTED_REMOTELY = "You can\'t select attacks from different Operating Systems.",
			INFO_REMOTELY_SELECTED = "You must have SSH installed in port 22 in your remotely machine to work successfully.",
			WARN_ATTACK_FIELD_REQUIRED = "You must select at least one attack before submit.",
			SUCCESS_OPERATION = "The operation was a success.";

		var MSG_TYPES = {success: "success", error: "error", warn: "warning", info: "info"},
			OS_TYPES = {windows: "windows", linux: "linux"},
			GENE_TYPES = {remotely: "remotely", exe: "exe", instructions: ""};

		var app = angular.module("attacks", []);
		app.controller("AttacksController", ["$scope", "$http",
        function ($scope, $http) {
				//----------------- SCOPE VARIABLES/FUNCTIONS ------------
				$scope.attacks = [
            ';
    foreach ( $allAttacks as $r ) {
        $hasSoftware = ($r->attack_action == "software" ? "true" : "false");
        echo '{name: "' . $r->name . '", description: "' . $r->description . '", os: "' . $r->so . '", select: false, id: ' . $r->id . ', hasSoftware: ' . $hasSoftware . '}';
        $i+=1;
        if($i < $size)
            echo ',';
    }
    echo '];
				$scope.geneType = "";
				$scope.isRemotely = false;
				$scope.selectedAttacksID = [];
				$scope.selectedAttacksWithSoftware = [];
				$scope.numWindowsAttacks = 0;
				$scope.numLinuxAttacks = 0;

				$scope.submit = function () {
					resetSpansMsg();
					$scope.selectedAttacksID = getSelectedAttacksID();
					if ($scope.selectedAttacksID.length > 0) { //Check if array it"s empty
						if ($scope.geneType == GENE_TYPES.exe)
							downloadFile();
						else if($scope.geneType == GENE_TYPES.remotely){
							var ipAddress = document.getElementById("ip").value;
							if(!isValidIPAddress(ipAddress))
								showSpanMsg("rf_ip", "* Invalid IP Address.");
							else remotely();

						}
						else askForInstructions();
					} else {
						showMsg(WARN_ATTACK_FIELD_REQUIRED, MSG_TYPES.warn);
					}
				};
				$scope.checkIsRemotely = function () {
					enableSubmit();
					disableMsg();
					if ($scope.geneType == GENE_TYPES.remotely) {
						$scope.isRemotely = true;
						changeConfigsState(true);
						if($scope.numWindowsAttacks > 0 && $scope.numLinuxAttacks > 0){
							showMsg(ERROR_MULTIPLE_OS_SELECTED_REMOTELY, MSG_TYPES.error);
							disableSubmit();
						}
						else{
							showMsg(INFO_REMOTELY_SELECTED, MSG_TYPES.info);
						}
					} else {
						$scope.isRemotely = false;
						//RESET FIELDS
						changeConfigsState(false);
						resetConfigs();
					}
				}
				$scope.attackAddedOrRemoved = function (attack) {
					enableSubmit();
					disableMsg();
					if (attack.os.toLowerCase() == OS_TYPES.windows) {
						if (!attack.select)	$scope.numWindowsAttacks += 1;
						else $scope.numWindowsAttacks -= 1;
					} else { //LINUX
						if (!attack.select)	$scope.numLinuxAttacks += 1;
						else $scope.numLinuxAttacks -= 1;
					}
					if($scope.isRemotely){
						if($scope.numWindowsAttacks > 0 && $scope.numLinuxAttacks > 0){
							showMsg(ERROR_MULTIPLE_OS_SELECTED_REMOTELY, MSG_TYPES.error);
							disableSubmit();
						}
						else{
							showMsg(INFO_REMOTELY_SELECTED, MSG_TYPES.info);
						}
					}

				};
				//----------------- END SCOPE VARIABLES/FUNCTIONS ------------

				//-------------------- AUXILIARY METHODS ----------------------
				function resetSpansMsg(){
					var span = document.getElementById("rf_ip");
					span.innerHTML = "*";
				}

				function showSpanMsg(spanID, msg){
					var span = document.getElementById(spanID);
					span.innerHTML = msg;
				}

				function disableSubmit(){
					changeSubmitState("none");
				}

				function enableSubmit(){
					changeSubmitState("block");
				}

				function changeSubmitState(state){
					var submit = document.getElementById("btnSubmit");
					submit.style.display = state;
				}

				function showMsg(msg, msg_type){
					changeMsgState(msg, "block", msg_type);
				}

				function disableMsg(){
					changeMsgState("", "none", "");
				}

				function changeMsgState(msg, visibility, newClass){
						var msg_div = document.getElementById("messages");
						msg_div.innerHTML = msg;
						msg_div.className = newClass;
						msg_div.style.display = visibility;
				}

				function getSelectedAttacksID() {
					var attacksID = [];
					for (var i = 0; i < $scope.attacks.length; i++) {
						var attack = $scope.attacks[i];
						if (attack.select == true){
							attacksID.push(attack.id);
							if(attack.hasSoftware)
								$scope.selectedAttacksWithSoftware.push(attacksID);
						}
					}

					return attacksID;
				}

				function askForInstructions() {
					var attacksElem = document.getElementById("myAttacks");
					attacksElem.value = $scope.selectedAttacksID;

					var form = document.getElementById("myForm");
						form.setAttribute("action", "instructions");
						form.setAttribute("method", "post");
						form.submit();
				}

				function downloadSoftwareAttacks(){
					for(var i = 0; i < $scope.selectedAttacksWithSoftware.length; i++){
						id = $scope.selectedAttacksWithSoftware[i];
						var link = document.createElement("a");
						link.setAttribute("href", "../wp-content/themes/AttackSimulatorP/twenty-fifteen-child/handle-get.php?attack_id=" + id);
						link.setAttribute("download", "");
						link.click();
					}
				}

				function downloadFile() {
					var data = {
						"attacks" : $scope.selectedAttacksID
					};
					var callback = function (recData) {
						var json = JSON.parse(recData);
						if ($scope.numLinuxAttacks > 0)
							getLinkFilename(json["linux"], "linux.sh").click();
						if ($scope.numWindowsAttacks > 0)
							getLinkFilename(json["windows"], "windows.bat").click();
						alert("Don \'t forget to execute the executable files in root/admin mode.");
					};

					sendPostRequest(data, callback, "downloadFile");
					//downloadSoftwareAttacks();
				}

				function setLoading(bool){
					var loader = document.getElementById("loader_panel");
				    if(bool) loader.style.display = "block";
				    else loader.style.display = "none";
				}

				function remotely() {
					var ipAddress = document.getElementById("ip").value,
						user = document.getElementById("username").value,
						pass = document.getElementById("password").value;
					var callback = function (recData) {
						showMsg(recData, MSG_TYPES.success);
						alert("DATA: " + recData);
					};
					var data = {
						"attacks" : $scope.selectedAttacksID,
						"ip" : ipAddress,
						"username" : user,
						"password" : pass
					};
					sendPostRequest(data, callback, "remotely");
				}

				function changeConfigsState(bool) {
					document.getElementById("myForm").noValidate = !bool;
				}

				function sendPostRequest(obj, callback, r_action) {
				    setLoading(true);
					$http({
						method: "POST",
						url:  "../wp-admin/admin-ajax.php",
						params: {
							"action" : r_action,
							"data" : obj
						}
					}).
					success( function( data, status, headers, config ) {
						setLoading(false);
						if(data.success)
							callback(data.data);
						else alert("Ocorreu um erro. Erro: " + data.data);
					}).
					error(function(data, status, headers, config) {
						setLoading(false);
						alert("An error ocourrs during the remotely process. Make sure that all your data its correct!");
					});

				}

				function resetConfigs() {
					document.getElementById("ip").value = "";
					document.getElementById("username").value = "";
					document.getElementById("password").value = "";
				}

				function getLinkFilename(data, filename) {
					var anchor = angular.element("<a/>");
					return anchor.attr({
						href: "data:attachment/pl;charset=utf-8," + encodeURI(data),
						target: "_blank",
						download: filename
					})[0];
				}

				//It checks if it\'s IPv4 or IPv6 IP Address.
				function isValidIPAddress(ip) {
					var valid = isIPv4(ip);
					if (valid)
                        return true;
					return isIPv6(ip);
				}

				//WARN IPs por exemplo: 125.23.32 são ips válidos?
				function isIPv4(ip) {
                    var regEx = "^(?:(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.){3}(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)$";
                    return ip.match(regEx);
                }

				function isIPv6(ip) {
                    var regEx = "^(?:[A-F0-9]{1,4}:){7}[A-F0-9]{1,4}$";
                    return ip.match(regEx);
                }

				//-------------------- END AUXILIARY METHODS -------------------
        }]);
</script>';
}

//TODO VER BUG DO CHOOSE FILE. NÃO SELECIONA BEM. PRINCIPALMENTE NO INICIO.
function addAttacks() {
	if(isset($_SESSION['hasAddAttack'])){
		echo '<div id="usp-success-message">'.$_SESSION['hasAddAttack'].'</div>';
		unset($_SESSION['hasAddAttack']);
	}
	if(isset($_SESSION['hasErrorAddAttack'])){
		echo '<div id="usp-success-message">'.$_SESSION['hasErrorAddAttack'].'</div>';
		unset($_SESSION['hasErrorAddAttack']);
	}
    echo '<form action="../wp-admin/admin-post.php" method="POST" enctype="multipart/form-data">
    		<input type="hidden" name="action" value="insert_attack">
            <div id="attack">
                Name:*<br>
                <input type="text" name="name" required>
                <br>
                Description:*<br>
                <input type="text" name="desc" required>
                <fieldset >
                    <legend>Operative system:*</legend>
                    <input type="radio" name="so" value="windows" required>Windows<br>
                    <input type="radio" name="so" value="linux">Linux<br>
                </fieldset><br>
                Action:
                <select id="select_action" name="act" onchange="onSelectChange(this)">
                  <option value="file">File</option>
                  <option value="software">Software</option>
                </select>
                <br><br>
            </div>
            <div id="field_soft">
                Software:
                <input type="file" name="soft" id="software">
                <br>
            </div>
            <fieldset id="field_files">
                <legend>Files</legend>
                <div id="files">
                    <div id="file0">
                        File path:<br>
                        <input type="text" id="fp0" name="file_path0">
                        <br>
                        String:<br>
                        <input type="text" id="s0" name="string0">
                        <br><br>
                    </div>
                </div>
                <button id="addfiles" type="button" onclick="addFile()" style="float: right;">Add file</button><br>
            </fieldset>
            <input type="hidden" id="nf" name="numberFile" value="0"/>
            <br>
            <input type="submit" value="Submit" style="float: right;">
            <br>
        </form>
        <script>

            var fileNumber=1;
            document.getElementById("fp0").required=true;
            document.getElementById("s0").required=true;
            document.getElementById("field_soft").style.display="none";
            function onSelectChange(p1){
                var txt = p1.value;
                if(txt=="file"){
                    for(var i=0;i<fileNumber;i++){
                        if(document.getElementById("fp"+i)!=null){
                            document.getElementById("fp"+i).required=true;
                            document.getElementById("s"+i).required=true;
                        }
                    }
                    document.getElementById("software").required=false;
                    document.getElementById("field_soft").style.display="none";
                    document.getElementById("field_files").style.display="block";
                }else{
                    for(var i=0;i<fileNumber;i++){
                        if(document.getElementById("fp"+i)!=null){
                            document.getElementById("fp"+i).required=false;
                            document.getElementById("s"+i).required=false;
                        }
                    }
                    document.getElementById("field_soft").style.display="block";
                    document.getElementById("field_files").style.display="none";
                    document.getElementById("software").required=true;
                }
            }
            function addFile(){
                var node = document.createElement("DIV");
                node.id="file"+fileNumber;   
                document.getElementById("files").appendChild(node);    
                addElement("SPAN","File path:");
                document.getElementById("file"+fileNumber).appendChild(document.createElement("BR"));
                node = addElement("INPUT","File path:");
                node.id="fp"+fileNumber;
                node.name="file_path"+fileNumber;
                node.type="text";
                node.required=true;
                document.getElementById("file"+fileNumber).appendChild(document.createElement("BR"));
                addElement("SPAN","String:");
                document.getElementById("file"+fileNumber).appendChild(document.createElement("BR"));
                node = addElement("INPUT","String:");
                node.id="s"+fileNumber;
                node.name="string"+fileNumber;
                node.type = "text";
                node.required=true;
                document.getElementById("file"+fileNumber).appendChild(document.createElement("BR"));
                addElement("BUTTON","Remove").id=fileNumber;
                document.getElementById("nf").value=fileNumber;
                
                
                fileNumber++; 
            }
            function addElement(p1,p2){
                var node = document.createElement(p1);                
                var textnode = document.createTextNode(p2);         
                node.appendChild(textnode);
                if(p1=="BUTTON"){
                    node.type="button";
                    node.onclick = function(){
                        removeFile(this);
                    };
                }
                document.getElementById("file"+fileNumber).appendChild(node);
                return node;
            }
            function removeFile(p1){
                var id = p1.id;
                var node = document.getElementById("file"+id);
                document.getElementById("files").removeChild(node);
            }
        </script>';
}


//---------------------------- AUXILIARY METHODS ----------------------------------
function get_filesByAttackID($ID)
{
    global $wpdb;
    return $wpdb->get_results("SELECT * FROM files WHERE attack_id=" . $ID, OBJECT);
}

function get_linuxAttacksByID($attacksID)
{
    global $wpdb;
    return $wpdb->get_results("SELECT * FROM attacks WHERE id IN (" . implode(",", $attacksID) . ") AND LCASE(so)='linux'", OBJECT);
}

function get_windowsAttacksByID($attacksID)
{
    global $wpdb;
    return $wpdb->get_results("SELECT * FROM attacks WHERE id IN (" . implode(",", $attacksID) . ") AND LCASE(so)='windows'", OBJECT);
}

//---------------------------- END AUXILIARY METHODS ----------------------

function showInstructions(){
    try{

        $data = $_REQUEST["myAttacks"];
        if( empty($data) )
            wp_die(MISSING_ARGUMENTS);
        $attacksID = explode(",", $data);
        if(empty($attacksID))
            wp_die(INVALID_ARGUMENTS);
        $lin_attacks = get_linuxAttacksByID($attacksID);
        $win_attacks = get_windowsAttacksByID($attacksID);
        $i = 0;

        echo '<div ng-app="instructions">
            <div ng-controller="InstructionsController">
                <h2 ng-show="win_attacks.length > 0">Windows attacks</h2>
                <div ng-repeat="a in win_attacks">
                    <h3>{{ a.name }}</h3>
                    <div ng-repeat="f in a.files">
                        <p>Copy this text:</p>
                        <p><strong>{{ f.string }}</strong></p>
                        <p>To:</p>
                        <p><strong>{{ f.file_path }}</strong></p>
                    </div>
                    <div ng-show="a.hasSoftware">
                        <p>You need to download this file.</p>
                        <a href="../wp-content/themes/AttackSimulatorP/twenty-fifteen-child/handle-get.php?attack_id={{ a.id }}" download>Software</a>
                    </div>
                </div>
                <h2 ng-show="lin_attacks.length > 0">Linux attacks</h2>
                <div ng-repeat="a in lin_attacks">
                    <h3>{{ a.name }}</h3>
                    <div ng-repeat="f in a.files">
                        <p>Copy this text:</p>
                        <p><strong>{{ f.string }}</strong></p>
                        <p>To:</p>
                        <p><strong>{{ f.file_path }}</strong></p>
                    </div>
                    <div ng-show="a.hasSoftware">
                        <p>You need to download this file.</p>
                        <a href="../wp-content/themes/AttackSimulatorP/twenty-fifteen-child/handle-get.php?attack_id={{ a.id }}" download>Software</a>
                    </div>
                </div>
            </div>
            <input type="button" value="Back" onclick="goBack()"/>
        </div>
        <script type="text/javascript">
            function goBack() {
                window.history.back();
            }
            var app = angular.module("instructions", []);
            app.controller("InstructionsController", ["$scope",
               function ($scope){
                  $scope.win_attacks = [
                ';
        $size = count($win_attacks);
        foreach ( $win_attacks as $r ) {
            $files = get_filesByAttackID($r->id);
            $hasSoftware = ($r->attack_action == "software" ? "true" : "false");
            echo '{id: ' . $r->id .', name: "' . $r->name . '", hasSoftware: ' . $hasSoftware . ', files: [';
            $files_size = count($files);
            $j = 0;
            foreach ($files as $f) {
                echo '{ string: "' . $f->string . '", file_path: "' . $f->file_path . '" }';
                $j += 1;
                if($j < $files_size)
                    echo ',';
            }

            echo ']}';
            $i+=1;
            if($i < $size)
                echo ',';
        }
        echo '];
                  $scope.lin_attacks = [
                ';
        $i = 0;
        $size = count($lin_attacks);
        foreach ( $lin_attacks as $r ) {
            $files = get_filesByAttackID($r->id);
            $hasSoftware = ($r->software == "software" ? "false" : "true");
            echo '{id: ' . $r->id .', name: "' . $r->name . '", hasSoftware: ' . $hasSoftware . ', files: [';
            $files_size = count($files);
            $j = 0;
            foreach ($files as $f) {
                echo '{ string: "' . $f->string . '", file_path: "' . $f->file_path . '" }';
                $j += 1;
                if($j < $files_size)
                    echo ',';
            }

            echo ']}';
            $i+=1;
            if($i < $size)
                echo ',';
        }
        echo '];
              }]);
        </script>';
    }catch (Exception $ex){
        wp_die(REQUEST_GENERAL_ERROR);
    }
}