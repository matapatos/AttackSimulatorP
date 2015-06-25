<?php
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
    wp_register_script('attacks-script', get_stylesheet_directory() . '/js/attacks.js');
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
function addAttacks() {
    echo '<form action="action_page.php">
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
                <select id="select_action" onchange="onSelectChange(this)">
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
                node.id="file_path"+fileNumber;
                node.type="text";
                document.getElementById("file"+fileNumber).appendChild(document.createElement("BR"));
                addElement("SPAN","String:");
                document.getElementById("file"+fileNumber).appendChild(document.createElement("BR"));
                node = addElement("INPUT","String:");
                node.id="string"+fileNumber;
                node.type = "text";
                document.getElementById("file"+fileNumber).appendChild(document.createElement("BR"));
                addElement("BUTTON","Remove").id=fileNumber;
                
                
                
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
//
// Your code goes below
//