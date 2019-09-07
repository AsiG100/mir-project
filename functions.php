<?php
 session_start();

	//sets session variables so the data will last the whole session
	if(!isset($_SESSION["warehouse"])){
		$_SESSION["warehouse"] = array();
		$_SESSION["getItemByCatalog"] = array();
		$_SESSION["getItemBySerialNum"] = array();
	}

	global $warehouse, $getItemByCatalog, $getItemBySerialNum;
	$warehouse = &$_SESSION["warehouse"];
	$getItemByCatalog = &$_SESSION["getItemByCatalog"];
	$getItemBySerialNum = &$_SESSION["getItemBySerialNum"];

	function readUint8($f) {
		$data = fread($f, 1);
		return unpack("C", $data)[1];
	};

	function readUint16($f) {
		$data = fread($f, 2);
		return unpack("v", $data)[1];
	};

	function readNUint16($f) {
		$v = readUint16($f);
		return $v == 0xFFFF ? "null" : $v;
	};

	function readUint32($f) {
		$data = fread($f, 4);
		return unpack("V", $data)[1];
	};

	function readNUint32($f) {
		$v = readUint32($f);
		return $v == 0xFFFFFFFF ? "null" : $v;
	};

	function readString($f) {
		$len = readUint16($f);
		$data = fread($f, $len);
		return utf8_decode($data);
	};

	function readContainer($h) {
		global $warehouse;

		$width  = readUint16($h);
		$length  = readUint16($h);
		$height = readNUint16($h);
		$name = readString($h);
		$maxWeight = readNUint32($h);
		$tareWeight = readNUint32($h);

		$arrCount  = readUint16($h);

		$warehouse[$name] = array(
			"width" => $width,
			"length"=> $length,
			"height"=> $height,
			"name"=> $name,
			"maxWeight"=> $maxWeight,
			"tareWeight"=> $tareWeight,
			"racks" => array()
		);

		for($i=0; $i<$arrCount; $i++){
			array_push($warehouse[$name]["racks"], readRack($h));
		}
	};

	function readRack($h) {
		$width  = readUint16($h);
		$length  = readUint16($h);
		$height = readNUint16($h);
		$name = readString($h);
		$maxWeight = readNUint32($h);
		$arrCount  = readUint16($h);

		$rack = array(
			"width" => $width,
			"length"=> $length,
			"height"=> $height,
			"name"=> $name,
			"maxWeight"=> $maxWeight,
			"placements" => array()
		);

		for($i=0; $i<$arrCount; $i++){
			array_push($rack["placements"], readPlacement($h));
		}

		return $rack;
	};
	
	function readPlacement($h){
		$x  = readUint16($h);
		$y  = readUint16($h);
		$contentType  = readUint8($h);

		$placement = array(
			"x" => $x,
			"y"=> $y,
			"contentType"=> $contentType,
		);

		switch($contentType){
			case 1: readContainer($h);
					break;
			case 2: $placement["content"] = & readItem($h);
					break;
			case 3: $placement["content"] = & readPacket($h);
					break;
		}
		
		return $placement;
	};
	
	function & readItem($h){
		global $getItemByCatalog;
		global $getItemBySerialNum;

		$width  = readUint16($h);
		$length  = readUint16($h);
		$height = readNUint16($h);
		$catNum = readString($h);
		$serialNum = readString($h);
		$weight = readUint32($h);

		$item = array(
			"width" => $width,
			"length"=> $length,
			"height"=> $height,
			"catNum"=> $catNum,
			"serialNum"=> $serialNum,
			"weight"=> $weight,
		);

		$getItemByCatalog[$catNum] = & $item;
		$getItemBySerialNum[$serialNum] = & $item;

		return $getItemBySerialNum[$serialNum];
	};
	
	function & readPacket($h){
		global $getItemByCatalog;

		$width  = readUint16($h);
		$length  = readUint16($h);
		$height = readNUint16($h);
		$catNum = readString($h);
		$capacity  = readUint16($h);
		$itemWeight  = readUint32($h);
		$quantity  = readUint16($h);
		
		$packet = array(
			"width" => $width,
			"length"=> $length,
			"height"=> $height,
			"catNum"=> $catNum,
			"capacity"=> $capacity,
			"itemWeight"=> $itemWeight,
			"quantity"=> $quantity,
		);

		$getItemByCatalog[$catNum] = & $packet;
		return $getItemByCatalog[$catNum];
	};

	// a readable display of a row or an array
	function printArray($arr){
		
		foreach($arr as $key => $val){
			if(is_array($val)){
				if(isset($val["contentType"]) && $val["contentType"] > 1 && !isset($val["content"])){
					continue;
				} 

				echo "<br/><b>".$key.":</b><br/> ";
				printArray($val);
			}else{
				echo $key.": ".$val."<br/>";
			}
		}
	}

	function showShelfInventory($container, $rack){
		global $warehouse;
		if(	isset($warehouse[$container]) &&
			isset($warehouse[$container]["racks"]) &&
			isset($warehouse[$container]["racks"][$rack])){
				echo "==={$container} {$rack}===<br/><br/>";
				printArray($warehouse[$container]["racks"][$rack]);
		}else {
			echo 'input error';
		}
	}

	function showItemByCatalog($num){
		global $getItemByCatalog;
		if(	isset($getItemByCatalog[$num])){
				echo "==={$num}===<br/><br/>";
				printArray($getItemByCatalog[$num]);
		}else {
			echo 'input error';
		}
	}

	function showItemBySerial($num){
		global $getItemBySerialNum;
		if(	isset($getItemBySerialNum[$num])){
				echo "==={$num}===<br/><br/>";
				printArray($getItemBySerialNum[$num]);
		}else {
			echo 'input error';
		}
	}

	function removeItemByCatalog($num){
		global $getItemByCatalog;
		if(	isset($getItemByCatalog[$num])){
			echo "The following Item / Package has been removed <br/><br/>";
			printArray($getItemByCatalog[$num]);
			$getItemByCatalog[$num] = NULL;
		}else {
			echo 'input error';
		}
	}

	function removeItemBySerial($num){
		global $getItemBySerialNum;
		if(	isset($getItemBySerialNum[$num])){
			echo "The following Item / Package has been removed <br/><br/>";
			printArray($getItemBySerialNum[$num]);
			$getItemBySerialNum[$num] = NULL;
		}else {
			echo 'input error';
		}
	}

	function & addItemToRack($data){
		global $warehouse, $getItemByCatalog, $getItemBySerialNum;
		
		$item = array(
			"width" => intval($data["width"]),
			"length"=> intval($data["length"]),
			"height"=> intval($data["height"]),
			"catNum"=> $data["catNum"],
			"serialNum"=> $data["serialNum"],
			"weight"=> intval($data["weight"]),
		);


		$getItemByCatalog[$data["catNum"]] = & $item;
		$getItemBySerialNum[$data["serialNum"]] = & $item;
		echo "Item has been added";
		return $getItemBySerialNum[$data["serialNum"]];
	}

	function & addPackageToRack($data){
		global $warehouse, $getItemByCatalog;
		
		$package = array(
			"width" => intval($data["width"]),
			"length"=> intval($data["length"]),
			"height"=> intval($data["height"]),
			"catNum"=> $data["catNum"],
			"capacity"=> intval($data["capacity"]),
			"itemWeight"=> intval($data["weight"]),
			"quantity"=> intval($data["quantity"]),
		);

		$getItemByCatalog[$data["catNum"]] = & $package;
		echo "Package has been added";
		return $getItemByCatalog[$data["catNum"]];
	}

	//adds package or item to rack with their coordinates
	function addToRack($type, $data, $location){
		global $warehouse;

		$coords = array(
			"x" => intval($location["x"]),
			"y"=> intval($location["y"]),
			"contentType"=> $type === "item"? 2 : 3,
		);

		if($type === "item"){
			$coords["content"] = & addItemToRack($data);
		}else{
			$coords["content"] = & addPackageToRack($data);
		}

		$warehouse[$location["container"]]["racks"][$location["rack"]]["placements"][] = & $coords;
	}

	//reads the binary file
	$h = fopen("mywarehouse", "rb");
	$contentType = readUint8($h);

	//initiates the file reading into a variable 
	if(empty($warehouse)){
		readContainer($h);
	}