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

//
// Your code goes below
//