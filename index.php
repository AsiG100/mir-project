<?php
include './functions.php';
global $warehouse, $getItemByCatalog, $getItemBySerialNum;

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <script src="https://code.jquery.com/jquery-3.4.1.min.js"
    integrity="sha256-CSXorXvZcTkaix6Yvo6HppcZGetbYMGWSFlBw8HfCJo=" crossorigin="anonymous"></script>
    <link href="./style.css" rel="stylesheet" type="text/css" />
    <title>Document</title>
</head>
    <body>  
        <form method="POST">
            <h2>Check inventory</h2>
            <label>Container: <input type="text" name="container" /></label>
            <label>Rack: <input type="text" name="rack" /></label>
            <input type="submit" />
        </form>
        <form method="POST">
            <h2>Find item by catalog / serial number</h2>
            <label>Type: 
                <input type="radio" name="type" value="catalog" checked> Catalog
                <input type="radio" name="type" value="serial"> Serial<br/>
            </label>
            <label>Number: <input type="text" name="num" /></label>
            <input name="find" type="hidden"/>
            <input type="submit" />
        </form>
        <form method="POST">
            <h2>Add package / item</h2>
            <label>Type: 
                <input class="add-radio" type="radio" name="type" value="package" checked> Package
                <input class="add-radio" type="radio" name="type" value="item"> Item<br/>
            </label>
            <h3>Location</h3>
            <label>Container: <input type="text" name="location[container]" /></label>
            <label>Rack: <input type="text" name="location[rack]" /></label>
            <label>X: <input type="text" name="location[x]" /></label>
            <label>Y: <input type="text" name="location[y]" /></label>
            <h3>Details</h3>
            <label class="serial cat">Width: <input type="text" name="data[width]" /><br/></label>
            <label class="serial cat">Length: <input type="text" name="data[length]" /><br/></label>
            <label class="serial cat">Height: <input type="text" name="data[height]" /><br/></label>
            <label class="serial cat">Category num: <input type="text" name="data[catNum]" /><br/></label>
            <label class="serial">Serial num: <input type="text" name="data[serialNum]" /><br/></label>
            <label class="cat">Capacity: <input type="text" name="data[capacity]" /><br/></label>
            <label class="cat">Quantity: <input type="text" name="data[quantity]" /><br/></label>
            <label class="serial cat">Weight: <input type="text" name="data[weight]" /><br/></label>
            <input name="add" type="hidden"/>
            <input type="submit" />
        </form>
        <form method="POST">
            <h2>Remove Package / Item</h2>
            <label>Type: 
                <input type="radio" name="type" value="catalog" checked> Catalog
                <input type="radio" name="type" value="serial"> Serial<br/>
            </label>
            <label>Number: <input type="text" name="num" /></label>
            <input name="remove" type="hidden"/>
            <input type="submit" />
        </form>
        <button id="clear">Clear</button>
        <form method="POST">
            <input type="hidden" name="reset"/>
            <input type="submit" value="Reset" id="reset" />
        </form>

        <div id="res">
        <?php

        if(isset($_POST["container"]) && isset($_POST["rack"])){
            showShelfInventory($_POST["container"], $_POST["rack"]);
        }

        if(isset($_POST["find"]) && isset($_POST["type"]) && isset($_POST["num"])){
            if($_POST["type"] === "catalog"){
                showItemByCatalog($_POST["num"]);
            }else {
                showItemBySerial($_POST["num"]);
            }
        }

        if(isset($_POST["remove"]) && isset($_POST["type"]) && isset($_POST["num"])){
            if($_POST["type"] === "catalog"){
                removeItemByCatalog($_POST["num"]);
            }else {
                removeItemBySerial($_POST["num"]);
            }
        }

        if(isset($_POST["add"]) && isset($_POST["data"]) && isset($_POST["location"])){
            addToRack($_POST["type"], $_POST["data"],$_POST["location"]);
        }

        if(isset($_POST["reset"])){
            global $getItemByCatalog;
            $getItemByCatalog = array();
            readContainer($h);
            unset($_POST["reset"]);
        }

        ?>
        </div>

        <script src="./script.js" type="text/javascript"></script>
    </body>
</html>