<?php

/*
  hit URL renderator.com/renderator.php?user=1&visit=setup
  hit URL renderator.com/renderator.php?user=1&visit=visuals
  hit URL renderator.com/renderator.php?user=1&visit=frontend 
  
  userBuildingSettings  = user_id.building_id.building-settings.json
  canvaJSON 			= user_id.building_id.ELEVATION_NAME.json
  elevationImages 		= user_id.building_id.ELEVATION_NAME.image_name
  floorplanImages 		= user_id.image_name
  rendergalleryImages 	= user_id.image_name 
  
*/

class UserFileSystem
{
	public $savingPath = '/home/apprender/saved/';
	public $userFolderPrefix = 'user_data_';
	public $canvasFilePrefix = 'canvas-';
	public $buildingSettingsFileName = 'building-settings.json';
	public $elevationNames = ['west', 'east', 'north', 'south'];
	public $canvasPath = '/canvases';
	public $imagePath = '/images';
	public $floorplansPath = '/floorplans';
	public $rendergalleryPath = '/rendergallery';
	public $generalResponse = array('message' => '', 'error' => false, 'serverdata' => '');
	public $projectData = '/home/apprender/data';

	public function filterResponse($response)
	{
		return $response;
	}


	public function getUploadError($errorNumber)
	{
		if ($errorNumber[0] === 1) {
			return 'The file is bigger than this PHP installation allows';
		} else if ($errorNumber[0] === 2) {
			return 'The file is bigger than this form allows';
		} else if ($errorNumber[0] === 3) {
			return 'Only part of the file was uploaded';
		} else {
			return 'Upload failed for unknown reason';
		}
	}
	public function imageType($path)
	{
		return pathinfo($path, PATHINFO_EXTENSION);
	}
	public function serverImageRenamed($fileName)
	{
		$fileName = strtolower($fileName);
		$fileName = preg_replace('/\s+/', '_', $fileName);
		return $fileName;
	}
	/* logo expression: {user_id}_logo */
	public function getSplashImage($user_id, $fileName)
	{
		$directory = $this->getUserFolderPath($user_id) . $this->imagePath;
		$fileName = $user_id . '_' . $fileName;
		$img = $this->getImage($directory, $fileName);
		return $img;
	}
	/* logo expression: {user_id}_logo */
	public function getUserLogo($token, $fileName)
	{
		$directory = $this->getUserFolderPath($token) . $this->imagePath;
		$img = $this->getImage($directory, $fileName);
		return $img;
	}
	/* RenderGallery expression: {user_id}_image_name */
	public function getUserRenderGalleryImage($user_id, $fileName)
	{
		$directory = $this->getUserFolderPath($user_id) . $this->rendergalleryPath;
		$fileName = $user_id . '_' . $fileName;
		$img = $this->getImage($directory, $fileName);
		return $img;
	}
	/* Floorplan expression: {user_id}_image_name */
	public function getUserFloorplanImage($user_id, $fileName)
	{
		$directory = $this->getUserFolderPath($user_id) . $this->floorplansPath;
		$fileName = $user_id . '_' . $fileName;
		$img = $this->getImage($directory, $fileName);
		return $img;
	}
	/* Elevation  expression: {building_id}_{ELEVATION_NAME}_image_name */
	public function getUserElevationImage($user_id, $building_id, $elevationName, $fileName)
	{
		$directory = $this->getUserFolderPath($user_id) . $this->imagePath;
		$fileName = $user_id . '_' . $building_id . '_' . $elevationName . '_' . $fileName;
		$img = $this->getImage($directory, $fileName);
		return $img;
	}
	public function getImage($dirPath, $imgName)
	{
		$imageFileName = $dirPath . '/' . $imgName;
		$imageFileName = strtolower($imageFileName);	 //clearstatcache(); echo file_exists('../saved/user_data_1/images/1_1_north_mario-north-crop.jpg');return;
		if (!file_exists($imageFileName)) { 		 //var_dump(file_exists($imageFileName), is_readable($imageFileName));
			return false;
		} else {
			$extension = $this->imageType($imageFileName);
			$pureImage = file_get_contents($imageFileName);
			header('Content-type: image/' . $extension . ';');
			header("Content-Length: " . strlen($pureImage));
			header('Access-Control-Allow-Origin: *');
			return $pureImage;
		}
	}


	/* saving images */
	/* Logo filename expression: {user_id}_logo */
	public function saveSplashImage($token, $fileName, $IMG_PURE_FILE)
	{

		$directory = $this->getUserFolderPath($token) . $this->imagePath;
		return $saveActionResult = $this->saveImage($directory, $fileName, $IMG_PURE_FILE);
	}
	/* Logo filename expression: {user_id}_logo */
	public function saveUserLogo($token, $fileName, $IMG_PURE_FILE)
	{
		$directory = $this->getUserFolderPath($token) . $this->imagePath;
		$saveActionResult = $this->saveImage($directory, $fileName, $IMG_PURE_FILE);
		return $saveActionResult;
	}
	/* RenderGallery filename expression: {user_id}_image_name */
	public function saveUserRenderGalleryImage($token, $fileName, $IMG_PURE_FILE)
	{
		$directory = $this->getUserFolderPath($token) . $this->rendergalleryPath;
		$saveActionResult = $this->saveImage($directory, $fileName, $IMG_PURE_FILE);
		return $saveActionResult;
	}
	/* Floorplan filename expression: {user_id}_image_name */
	public function saveUserFloorplanImage($token, $fileName, $IMG_PURE_FILE)
	{
		$directory = $this->getUserFolderPath($token) . $this->floorplansPath;
		$saveActionResult = $this->saveImage($directory, $fileName, $IMG_PURE_FILE);
		return $saveActionResult;
	}

	/* Elevation image filename expression: {user_id}_{building_id}_{ELEVATION_NAME}_image_name */
	public function saveUserElevationImage($token, $elevationName, $fileName, $IMG_PURE_FILE)
	{
		$directory = $this->getUserFolderPath($token) . $this->imagePath;
		$fileName = $elevationName . '_' . $fileName;
		$saveActionResult = $this->saveImage($directory, $fileName, $IMG_PURE_FILE);
		return $saveActionResult;
	}

	public function saveImage($dirPath, $fileName, $IMG_PURE_FILE)
	{
		$response = $this->generalResponse;
		$fileName = $this->serverImageRenamed($fileName);
		$uploadfile = $dirPath . '/' . $fileName;
		if (!empty($IMG_PURE_FILE)) {
			if (!file_exists($uploadfile)) {
				if (move_uploaded_file($IMG_PURE_FILE['tmp_name'], $uploadfile)) {
					$response = array('message' => '', 'error' => false, 'serverdata' => $uploadfile);
				} else {
					$response = array('message' => $this->getUploadError($IMG_PURE_FILE['error']), 'error' => true);
				}
			} else {
				$response = array('message' => 'File exist', 'error' => false);
			}
		} else {
			$response = array('message' => 'File in empty', 'error' => true);
		}
		return $response;
	}
	/* deleting images */
	public function deleteUserRenderGalleryImage($user_id, $fileName)
	{
		$directory = $this->getUserFolderPath($user_id) . $this->rendergalleryPath;
		$fileName = $user_id . '_' . $fileName;
		$deleteActionResult = $this->deleteImage($directory, $fileName);
	}

	public function deleteUserFloorplanImage($user_id, $fileName)
	{
		$directory = $this->getUserFolderPath($user_id) . $this->floorplansPath;
		$fileName = $user_id . '_' . $fileName;
		$deleteActionResult = $this->deleteImage($directory, $fileName);
	}

	/* Elevation image filename expression: {user_id}_{building_id}_{ELEVATION_NAME}_image_name */
	public function deleteUserElevationImage($user_id, $building_id, $elevationName, $fileName = null)
	{
		$directory = $this->getUserFolderPath($user_id) . $this->imagePath;
		$fileName = $user_id . '_' . $building_id . '_' . $elevationName;
		$deleteActionResult = $this->deleteImage($directory, $fileName);
	}

	public function deleteImage($dirPath, $fileName)
	{
		$dirFiles = scandir($dirPath);
		foreach ($dirFiles as $file) {
			$pattern = $fileName;
			if (preg_match('/' . $pattern . '/i', $file)) {
				unlink($dirPath . $file);
				$response = array('fileName' => $file, 'message' => $file . ' deleted', 'error' => false, 'found' => true);
			} else {
				$response = array('fileName' => $file, 'message' => $file . ' not found in server folder', 'error' => false, 'found' => false);
			}
		}
		return $response;
	}





	/* 
   * Building Settings JSON 
   * filename expression: {user_id}_{building_id}_building-settings.json
  */
	public function getUserBuildingSettingsJSON($token)
	{
		$directory = $this->projectData . '/' . $token;
		$jsonName = $this->buildingSettingsFileName;
		$getBuildingSettings = $this->getBuildingSettingsJSON($directory, $jsonName);
		return $getBuildingSettings;
	}
	public function getBuildingSettingsJSON($dirPath, $jsonName)
	{
		$jsonFileName = $dirPath . '/' . $jsonName;
		$jsonFileName = strtolower($jsonFileName);
		if (!file_exists($jsonFileName)) {
			return false;
		} else {
			$pureJSON = file_get_contents($jsonFileName);
			return $pureJSON;
		}
	}
	public function saveUserBuildingSettingsJSON($token, $JSON_DATA)
	{
		$directory = $this->getUserFolderPath($token);
		$jsonName = $this->buildingSettingsFileName;
		return $saveBuildingSettings = $this->saveBuildingSettingsJSON($directory, $jsonName, $JSON_DATA);
	}
	public function saveBuildingSettingsJSON($dirPath, $jsonName, $JSON_DATA)
	{
		$response = $this->generalResponse;
		$jsonFileName = $dirPath . '/' . $jsonName;
		$fh = fopen($jsonFileName, 'w');
		if (!$fh) {
			$response['message'] = 'can"t find ' . $jsonFileName;
			$response['error'] = true;
			return $response;
		} else {
			$encodedJSON = json_encode(json_decode($JSON_DATA, JSON_UNESCAPED_SLASHES), JSON_UNESCAPED_SLASHES);
			$json_edited = stripslashes($JSON_DATA);
			fwrite($fh, $encodedJSON);
			fclose($fh);
		}
		$response['message'] = 'building settings saved';
		$response['error'] = false;
		return $response;
	}





	/* Building Canvas JSON 
   * filename expression: {user_id}_{building_id}_{ELEVATION_NAME}.json
  */
	public function canvasJSON_exist($token, $elevationName)
	{
		$directory = $this->getUserFolderPath($token) . $this->canvasPath;
		$jsonName  = $elevationName . '.json';
		$canvasJSON_path = $directory . '/' . $jsonName;
		return file_exists($canvasJSON_path);
	}
	public function saveUserCanvasJSON($token, $elevationName, $JSON_DATA)
	{

		$directory =  $this->projectData . '/' . $token . '/' . $this->canvasPath;

		$jsonName  = $elevationName . '.json';

		$getCanvas = $this->saveCanvasJSON($directory, $jsonName, $JSON_DATA);

		return $getCanvas;
	}
	public function saveCanvasJSON($dirPath, $jsonName, $JSON_DATA)
	{
		$response = $this->generalResponse;
		$jsonFileName = $dirPath . '/' . $jsonName;
		$fh = fopen($jsonFileName, 'w');
		if (!$fh) {
			$response['message'] = 'can"t find' . $jsonName;
			$response['error'] = true;
			return $response;
		} else {
			fwrite($fh, $JSON_DATA);
			fclose($fh);
		}
		$response['message'] = 'canvas saved';
		$response['error'] = false;
		return $response;
	}
	public function writeErrorLog($data)
	{
		$fp = fopen($this->projectData . "/logs/" . date("F_j_Y_g_i a") . "_errorLog.txt", "wb");
		fwrite($fp, $data);
		fclose($fp);
		$contentJson = json_encode($data, JSON_UNESCAPED_SLASHES);
		$fp = fopen($this->projectData . "/logs/" . date("F_j_Y_g_i a") . "_errorLog.json", "wb");
		fwrite($fp, $contentJson);
		fclose($fp);
	}
	public function getUserCanvasElevationJSON($token, $elevationName)
	{


		$directory = $this->projectData . '/' . $token . '/' . $this->canvasPath;
		$jsonName  = $elevationName . '.json';
		$getCanvas = $this->getCanvasJSON($directory, $jsonName);
		return $getCanvas;
	}
	public function getCanvasJSON($dirPath, $jsonName)
	{
		$jsonFileName = $dirPath . '/' . $jsonName;
		$jsonFileName = strtolower($jsonFileName);
		if (!file_exists($jsonFileName)) {
			return false;
		} else {
			$pureJSON = file_get_contents($jsonFileName);
			return $pureJSON;
		}
	}

	public function updateSuitesData($json, $token)
	{
		$trueValue = 'Disponible';
		$falseValue = 'No disponible';
		$suiteColumnString = 'unit__c';
		$statusColumnString = 'status';

		$getSuitesJSON = json_decode($this->getUserCanvasElevationJSON($token, 'north'));
		if (!$getSuitesJSON) {
			$response['error'] = true;
			$response['message'] = 'Problem fetching the saved settings';
			// $response['serverData'] = $userInfo["appStatus"];
			return $response;
		} else {

			$data = json_decode($json);

			if (!empty($data)) {
				foreach ($data as &$newRecord) {
					if (!empty($newRecord->$suiteColumnString) || !empty($newRecord->$statusColumnString)) {
						$newRecordName = $newRecord->$suiteColumnString;
						$newRecordStatus = $newRecord->$statusColumnString === $trueValue;
						foreach ($getSuitesJSON->objects as &$oldRecord) {
							$apartmentName = $oldRecord->RENDERATOR->dataEntry->appartmentName;
							$pattern = "/\d{2,6}/i";
							if (preg_match($pattern, $apartmentName, $matches)) {
								$prop = $matches[0];
								if ($prop == $newRecordName) {
									$oldRecord->RENDERATOR->dataEntry->availability = $newRecordStatus;
								}
							}
						}
					}
				}
			}
			$canvasCreated = $this->saveUserCanvasJSON($token, 'north', json_encode($getSuitesJSON, JSON_UNESCAPED_SLASHES));

			if ($canvasCreated['error'] === true) {
				$response['error'] = true;
				$response['message'] = $canvasCreated['message'];
			} else {
				$response['data'] = array(
					'suites' => $getSuitesJSON,
					'updateData' => $getSuitesJSON
				);
			}
			return $response;
		}
	}


	/* USER SAVING FOLDER */
	public function createUserFolder($token)
	{
		if (empty($token)) {
			return false;
		}
		$response = $this->generalResponse;
		$path = $this->projectData;

		//    $userDirPrefix = $this->userFolderPrefix;
		//    $directoryName = $userDirPrefix.$user_id;

		$directoryPathName = $path . $token;

		if (!is_dir($directoryPathName)) {
			$errorFound = false;
			if (!mkdir($directoryPathName, 0755)) {
				$errorFound = true;
				return $this->filterResponse($response);
			} else {
				$response['message'] .= 'user directory created. ';
			}
			if (!mkdir($directoryPathName . $this->canvasPath, 0755)) {
				$errorFound = true;
				return $this->filterResponse($response);
			} else {
				$response['message'] .= 'canvas directory created. ';
			}
			if (!mkdir($directoryPathName . $this->imagePath, 0755)) {
				$errorFound = true;
				return $this->filterResponse($response);
			} else {
				$response['message'] .= 'image directory created. ';
			}
			if (!mkdir($directoryPathName . $this->floorplansPath, 0755)) {
				$errorFound = true;
				return $this->filterResponse($response);
			} else {
				$response['message'] .= 'Floor plan directory created. ';
			}
			if (!mkdir($directoryPathName . $this->rendergalleryPath, 0755)) {
				$errorFound = true;
				return $this->filterResponse($response);
			} else {
				$response['message'] .= 'gallery directory created. ';
			}
			$response['error'] = $errorFound;
		} else {
			$response = $this->generalResponse;
			$response['message'] = 'Dir already exist for this user';
			$response['error'] = true;
		}
		return $this->filterResponse($response);
	}


	public function getUserFolderPath($token)
	{
		$path = $this->projectData;
		$userDirectoryPath = $path . $token;
		return $userDirectoryPath;
	}




	public function getUserBuildingIDSbyFiles($token)
	{
		$directory = $this->getUserFolderPath($token);
		$files = scandir($directory);
		$building_IDS = array();
		foreach ($files as $file) {
			$pattern = $this->buildingSettingsFileName;
			if (preg_match('/' . $pattern . '/i', $file)) {
				preg_match('/i', $file, $out); //print_r($out);
				$building_IDS[] = (int)$out[1];
			}
		}
		return $building_IDS;
	}


	public function getUserElevationCanvasesNames($user_id, $elevationName)
	{
		$response = $this->generalResponse;
		$canvasesDir = $this->getUserFolderPath($user_id) . $this->canvasPath;
		$canvasFiles = scandir($canvasesDir);
		if (!$canvasFiles) {
			$response['message'] = 'user directory for canvases not found';
			$response['error'] = true;
			return $this->filterResponse($response);
		}
		$canvasFilePrefix = $this->canvasFilePrefix;
		$results = array();
		foreach ($canvasFiles as $file) {
			$pattern = $canvasFilePrefix . $elevationName . '_';
			if (preg_match('/' . $pattern . '/i', $file)) {
				$results[$elevationName] = $file;
			}
		}
		return $results;
	}



	public function getUserElevationBuildingCanvasesNames($user_id, $building_id,  $elevation = null)
	{
		$building_id = (string)$building_id;
		$canvasesDir = $this->getUserFolderPath($user_id) . $this->canvasPath;
		$canvasFiles = scandir($canvasesDir);
		if (!$canvasFiles) {
			$response['message'] = 'user directory for canvases not found';
			$response['error'] = true;
			return $this->filterResponse($response);
		}
		$canvasFilePrefix = $this->canvasFilePrefix;
		if ($elevation === null) {
			$elevationNames = $this->elevationNames;
		} else {
			$elevationNames = array(strtolower($elevation));
		}
		$results = array();
		foreach ($canvasFiles as $file) {
			foreach ($elevationNames as $elevationName) {
				$pattern = $canvasFilePrefix . '(' . $elevationName . ')' . '_' . $building_id . '_';
				if (preg_match('/' . $pattern . '/i', $file)) {
					$results[$elevationName] = $file;
				}
			}
		}
		return $results;
	}
}
  
//$newS = new UserFileSystem();  
//$folderCreation = $newS->createUserFolder(4); print_r($folderCreation);

//$t = $newS->getUserImage('../saved/user_data_1/images/', '/mario-south-crop.jpg');
//echo $t;
//print_r($newS->getUserElevationBuildingCanvasesNames(1, '1'));
