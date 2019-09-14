<?php
 session_start();
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
		//keeps track on the current container listed
		$currentContainer = $name;

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
			array_push($warehouse[$name]["racks"], readRack($h, $currentContainer));
		}
	};

	function readRack($h, $currentContainer) {
		$width  = readUint16($h);
		$length  = readUint16($h);
		$height = readNUint16($h);
		$name = readString($h);
		$maxWeight = readNUint32($h);
		$arrCount  = readUint16($h);
		//keeps track on the current rack listed
		$currentRack = $name -1;

		$rack = array(
			"width" => $width,
			"length"=> $length,
			"height"=> $height,
			"name"=> $name,
			"maxWeight"=> $maxWeight,
			"placements" => array()
		);

		for($i=0; $i<$arrCount; $i++){
			array_push($rack["placements"], readPlacement($h, $currentContainer, $currentRack));
		}

		return $rack;
	};
	
	function readPlacement($h, $currentContainer, $currentRack){
		global $getItemByCatalog, $getItemBySerialNum;

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
					$getItemByCatalog[$placement["content"]["catNum"]][] = array("container"=>$currentContainer, "rack"=>$currentRack, "content"=>& $placement["content"]);
					$getItemBySerialNum[$placement["content"]["serialNum"]] = array("container"=>$currentContainer, "rack"=>$currentRack, "content"=>& $placement["content"]);
					break;
			case 3: $placement["content"] = & readPacket($h);
					$getItemByCatalog[$placement["content"]["catNum"]][] = array("container"=>$currentContainer, "rack"=>$currentRack, "content"=>& $placement["content"]);
					break;
		}
		
		return $placement;
	};
	
	function & readItem($h){
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

		$res = & $item;

		return $res;
	};
	
	function & readPacket($h){
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
		
		$res =  & $packet;

		return $res;
	};

	function printArray($arr){
		
		foreach($arr as $key => $val){
			if(is_array($val)){
				if(array_key_exists("content", $val) && !isset($val["content"])){
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
		if(	isset($getItemBySerialNum[$num]["content"])){
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

			foreach($getItemByCatalog[$num] as $item){
				$item["content"] = NULL;
			}

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
			$getItemBySerialNum[$num]["content"] = NULL;
		}else {
			echo 'input error';
		}
	}

	function & addItemToRack($data){
		global $warehouse;
		
		$item = array(
			"width" => intval($data["width"]),
			"length"=> intval($data["length"]),
			"height"=> intval($data["height"]),
			"catNum"=> $data["catNum"],
			"serialNum"=> $data["serialNum"],
			"weight"=> intval($data["weight"]),	
		);

		$res =  & $item;
		echo "Item has been added";
		return $res;
	}

	function & addPackageToRack($data){
		global $warehouse;
		
		$package = array(
			"width" => intval($data["width"]),
			"length"=> intval($data["length"]),
			"height"=> intval($data["height"]),
			"catNum"=> $data["catNum"],
			"capacity"=> intval($data["capacity"]),
			"itemWeight"=> intval($data["weight"]),
			"quantity"=> intval($data["quantity"]),
		);

		$res = & $package;
		echo "Package has been added";
		return $res;
	}

	function addToRack($type, $data, $location){
		global $warehouse, $getItemByCatalog, $getItemBySerialNum;
		$thisRack = & $warehouse[$location["container"]]["racks"][$location["rack"]];
		
		if(hasSpaceInRack($thisRack, $data)){
			$coords = array(
				"x" => intval($location["x"]),
				"y"=> intval($location["y"]),
				"contentType"=> $type === "item"? 2 : 3,
			);
	
			if($type === "item"){
				$coords["content"] = & addItemToRack($data);
				$getItemBySerialNum[$coords["content"]["serialNum"]] = array("container"=>$location["container"], "rack"=>$location["rack"], "content"=>& $coords["content"]);
			}else{
				$coords["content"] = & addPackageToRack($data);
			}

			$getItemByCatalog[$coords["content"]["catNum"]][] = array("container"=>$location["container"], "rack"=>$location["rack"], "content"=>& $coords["content"]);

	
			$thisRack["placements"][] = & $coords;
		}else{
			echo "Insufficient space, please choose another rack";
		}
	}

	function hasSpaceInRack($rack, $newObj){
		$capacity = calculateVolume($rack);
		$load = $rack['maxWeight'];
		$spaceTaken = -0;
		$objects = $rack["placements"];

		foreach($objects as $obj){
			$spaceTaken += calculateVolume($obj['content']);
			
			$weight = (isset($obj['content']['itemWeight']))? $obj['content']['itemWeight'] : $obj['content']['weight'];
			$load -=  $weight;
		}

		$freeSpace = $capacity - $spaceTaken - calculateVolume($newObj);
		$load -= $newObj['weight'];

		if($freeSpace > 0 && $load > 0){
			echo "Free space: ".$freeSpace."<br/>";
			echo "Free weight: ".$load."<br/>";
		}

		return $freeSpace > 0 && $load > 0;
	}

	function calculateVolume($obj){
		return $obj['width'] * $obj['length'] * $obj['height'];
	}

	$h = fopen("mywarehouse", "rb");
	$contentType = readUint8($h);

	if(empty($warehouse)){
		readContainer($h);
	}