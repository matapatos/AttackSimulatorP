<?php
if (!session_id()) {
    session_start();
}
function showAttacksOLD(){
    global $allAttacks;
    $allAttacks = getAllAttacks();
    $size = count($allAttacks);
    $i = 0;
    echo '<div ng-app="attacks">
    <div ng-controller="AttacksController">
        <label>Search: <input ng-model="search.$"></label><br/>
        <label>Operating System: 
                    <select ng-model="search.os">
                        <option value="">All</option>
                        <option value="Windows">Windows</option>
                        <option value="Linux" selected>Linux</option>
                    </select></label><br/>
        <label>Select: 
                    <select ng-model="search.select">
                        <option value="">All</option>
                        <option value="true">Yes</option>
                        <option value="false" selected>No</option>
                    </select></label><br/>
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
                <td><input type="checkbox" ng-model="a.select"/></td>
            </tr>
        </table>
    </div>
</div>
<script type="text/javascript">
    var app = angular.module("attacks", []);
    app.controller("AttacksController", ["$scope",
        function($scope){

            $scope.attacks = [
            ';
        foreach ( $allAttacks as $r ) {
            echo '{name: "' . $r->name . '", description: "' . $r->description . '", os: "' . $r->so . '", select: false}';
                $i+=1;
                if($i < $size)
                    echo ',';
            }
        echo '];
        }]);
</script>';
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
			<form id="myForm" ng-submit="submit()" novalidate>
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
					<select ng-model="geneType" ng-change="checkIsRemotly()">
						<option value="">Instructions</option>
						<option value="exe">Executable file</option>
						<option value="remotly">Remotly</option>
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
				<div id="configs" ng-show="isRemotly">
					<h3>Configurations</h3>
					<label>IP address:
						<input id="ip" type="text" placeholder="IP address" ng-pattern="^(?:(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.){3}(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)$" required/>
						<span class="required_field">*</span>
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
				<input type="submit" value="Submit" />
			</form>
		</div>
	</div>
	<script type="text/javascript">
		var app = angular.module("attacks", []);
		app.controller("AttacksController", ["$scope", "$http",
        function ($scope, $http) {
				//----------------- SCOPE VARIABLES/FUNCTIONS ------------
				$scope.attacks = [{
					id: 1,
					name: "Brute force",
					description: "desc1",
					os: "Windows",
					select: false
                },
								{
					id: 2,
					name: "Linux force",
					description: "desc2",
					os: "Linux",
					select: false
                } ];
				$scope.geneType = "";
				$scope.isRemotly = false;
				$scope.selectedAttacksID = [];
				$scope.numWindowsAttacks = 0;
				$scope.numLinuxAttacks = 0;

				$scope.submit = function () {
					$scope.selectedAttacksID = getSelectedAttacksID();
					if ($scope.selectedAttacksID.length > 0) { //Check if array it"s empty
						if ($scope.geneType == "")
							askForInstructions();
						else if ($scope.geneType == "exe")
							downloadFile();
						else remotly();
					} else {
						alert("You must select at least one attack before submit.");
					}
				};
				$scope.checkIsRemotly = function () {
					if ($scope.geneType == "remotly") {
						$scope.isRemotly = true;
						changeConfigsState(true);
					} else {
						$scope.isRemotly = false;
						//RESET FIELDS
						changeConfigsState(false);
						resetConfigs();
					}
				}
				$scope.attackAddedOrRemoved = function (attack) {
					if (attack.os == "Windows") {
						if (!attack.select) {
							$scope.numWindowsAttacks += 1;
						} else $scope.numWindowsAttacks -= 1;
					} else { //LINUX
						if (!attack.select) {
							$scope.numLinuxAttacks += 1;
						} else $scope.numLinuxAttacks -= 1;
					}
				};
				//----------------- END SCOPE VARIABLES/FUNCTIONS ------------

				//-------------------- AUXILIARY METHODS ----------------------

				function getSelectedAttacksID() {
					var attacksID = [];
					for (var i = 0; i < $scope.attacks.length; i++) {
						var attack = $scope.attacks[i];
						if (attack.select == true)
							attacksID.push(attack.id);
					}

					return attacksID;
				}

				function askForInstructions() {
					var data = {
						"attacks" : $scope.selectedAttacksID
					};
					var callback = function () {

					};

					sendPostRequest(data, callback, "instructions");
				}

				function downloadFile() {
					var data = {
						"action" : "downloadFile",
						"attacks" : $scope.selectedAttacksID
					};
					var callback = function () {
						if ($scope.numLinuxAttacks > 0)
							getLinkFilename(data, "file.sh").click();
						if ($scope.numWindowsAttacks > 0)
							getLinkFilename(data, "file.bat").click();
					};

					sendPostRequest(data, callback, "downloadFile");
				}

				function remotly() {
					var ipAddress = document.getElementById("ip").value,
						user = document.getElementById("username").value,
						pass = document.getElementById("password").value;
					var callback = function () {

					};
					var data = {
						"attacks" : $scope.selectedAttacksID,
						"ip" : ipAddress,
						"username" : user,
						"password" : pass
					};
					sendPostRequest(data, callback, "remotly");
				}

				function changeConfigsState(bool) {
					document.getElementById("myForm").noValidate = !bool;
				}

				function sendPostRequest(obj, callback, r_action) {						
					$http({
						method: "POST",
						url:  "../wp-admin/admin-ajax.php",
						params: {
							"action" : r_action,
							"data" : obj
						},
					}).
					success( function( data, status, headers, config ) {
						alert("SUCCESS: " + data.success + "DATA: " + data.data);
					}).
					error(function(data, status, headers, config) {});
					
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

				//-------------------- END AUXILIARY METHODS -------------------
        }]);
	</script>';
}

function addAttacks() {
	if(isset($_SESSION['hasAddAttack'])){
		echo '<div id="usp-success-message">'.$_SESSION['hasAddAttack'].'</div>';
		unset($_SESSION['hasAddAttack']);
	}
	if(isset($_SESSION['hasErrorAddAttack'])){
		echo '<div id="usp-success-message">'.$_SESSION['hasErrorAddAttack'].'</div>';
		unset($_SESSION['hasErrorAddAttack']);
	}
    echo '<form action="../wp-admin/admin-post.php" method="POST">
    		<input type="hidden" name="action" value="insert_attack">
            <div id="attack">
                Name:*<br>
                <input type="text" name="name" required>
                <br>
                Description:*<br>
                <input type="text" name="desc" required>
                <fieldset >
                    <legend>Operative system:*</legend>
                    <input type="radio" name="so" value="win" required>Windows<br>
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
                        <input type="text" name="file_path0">
                        <br>
                        String:<br>
                        <input type="text" name="string0">
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

            document.getElementById("field_soft").style.display="none";
            function onSelectChange(p1){
                var txt = p1.value;
                if(txt=="file"){
                    document.getElementById("field_soft").style.display="none";document.getElementById("field_files").style.display="block";
                }else{ document.getElementById("field_soft").style.display="block";
                      document.getElementById("field_files").style.display="none";
                }
            }
            function addFile(){
                var node = document.createElement("DIV");
                node.id="file"+fileNumber;   
                document.getElementById("files").appendChild(node);    
                addElement("SPAN","File path:");
                document.getElementById("file"+fileNumber).appendChild(document.createElement("BR"));
                node = addElement("INPUT","File path:");
                node.name="file_path"+fileNumber;
                node.type="text";
                document.getElementById("file"+fileNumber).appendChild(document.createElement("BR"));
                addElement("SPAN","String:");
                document.getElementById("file"+fileNumber).appendChild(document.createElement("BR"));
                node = addElement("INPUT","String:");
                node.name="string"+fileNumber;
                node.type = "text";
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