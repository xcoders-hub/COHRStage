<?php

class Media extends Database{

	public $conn;
	protected $view_products_by_categories = "view_products_by_categories";
	protected $view_app_codes = "mm_app_codes";
	protected $media = "mm_media";
	protected $media_attributes = "mm_media_attributes";
	protected $media_tags = "mm_media_tags";
	protected $ts = 0;
	
	function __construct(){
		$this->conn = parent::__construct(); // get db connection from Database model
		$this->ts = date("Y-m-d H:i:s",time()); // set current timestamp
	}
	
	/**
	* @param array $data (user,action,object,previous_data,update_data)
	*/
	public function log_action($data){
		parent::log_admin_action($data);
	}
	
	/**
	* get_media_all
	* Retrieve all products
	*
	*/
	public function get_media_all( $folder = "optoskand" ){
		if($folder !== ""){
			$query = "SELECT `MediaID`,`Title`,`Description`,`Category`,`SeoUrl`,`SavedMedia`,`CreatedDateTime`,`Folder`,`Tags`,`Type` FROM `".$this->media."` WHERE `Status`='Active' AND Folder = '".$folder."'";
		}else{
			$query = "SELECT `MediaID`,`Title`,`Description`,`Category`,`SeoUrl`,`SavedMedia`,`CreatedDateTime`,`Folder`,`Tags`,`Type` FROM `".$this->media."` WHERE `Status`='Active'";
		}
		
		$stmt = $this->conn->prepare($query);	
		$stmt->execute();
		$all_results = $stmt->fetchAll();

		if( count($all_results) > 0 ){
			foreach( $all_results as $row ){
				extract($row);
				//print $row['MediaID'] .  '  ' . print_r($this->get_media_tags($row['MediaID']),true);
				//if( $this->get_media_tags($row['MediaID']) > 0){
					//$tags = implode( ", ",$this->get_media_tags($row['MediaID']) );
					//$results[] = array("MediaID"=>$row['MediaID'],"Title"=>$row['Title'],"Category"=>$row['Category'],"Description"=>$row['Description'],"SavedMedia"=>$SavedMedia,"SeoUrl"=>$SeoUrl,"CreatedDateTime"=>$CreatedDateTime,"Tags"=>$tags,"Folder"=>$row['Folder']);
				//}else{
					$results[] = array("MediaID"=>$row['MediaID'],"Title"=>$row['Title'],"Category"=>$row['Category'],"Description"=>$row['Description'],"SavedMedia"=>$SavedMedia,"SeoUrl"=>$SeoUrl,"CreatedDateTime"=>$CreatedDateTime,"Tags"=>$Tags,"Folder"=>$Folder,"Type"=>$Type);
				//}
			}
			return $results;
		}else{
			return 0;
		}
		
	}
	
	
	/**
	* Get Media with given id
	* @param integer $MediaID
	*/
	public function get($MediaID){
		$query = "SELECT `Title`,`Description`,`Category`,`SavedMedia`,`SeoUrl`,`Folder`,`Tags` FROM `".$this->media."` WHERE `MediaID`=:MediaID";
		$stmt = $this->conn->prepare($query);
		$stmt->bindValue(':MediaID',$MediaID, PDO::PARAM_INT);
		$stmt->execute();	
		$result = $stmt->fetch(PDO::FETCH_ASSOC);
		if( count($result) > 0 ){
/* 			if( $this->get_media_tags($MediaID) > 0){
				$tags = implode( " ",$this->get_media_tags($MediaID));
				$result['tags'] = $tags;
			}else{ 
				$result['tags'] = "";
			} */
			return $result;
		}else{
			return 0;
		}		
	}
	
	/**
	* Get Media with given url ( SeoUrl )
	* @param string $seo_url
	*/
	public function get_media_by_url($seo_url){
		$query = "SELECT * FROM `".$this->media."` WHERE `SeoUrl`=:SeoUrl";
		$stmt = $this->conn->prepare($query);
		$stmt->bindValue(':SeoUrl',$seo_url, PDO::PARAM_STR);		
		$stmt->execute();
		$result = $stmt->fetch(PDO::FETCH_ASSOC);
		if( count($result) > 0 ){
			return $result;
		}else{
			return 0;
		}
	}
	
	/**
	* Add New Media Asset
	* There needs to be record created for each attribute that is given
	* Example: Title, Description, and Asset is given. There will be 3 records
	* @param array $data
	* @return array $result
	*/
	public function add($data){
		extract($data);
		if ( $this->check_title($Title) == 0 ){
			if($Category == "" || !isset($Category)){
				$Category = "Other";
			}
			if($Tags == "" || !isset($Tags)){
				$Tags = "Other";
			}			
			$stmt = $this->conn->prepare("INSERT INTO `".$this->media."` (Title,Description,Type,SavedMedia, Category, Folder, Tags) VALUES (:Title, :Description, :Type, :SavedMedia,:Category,:Folder, :Tags)");
			$stmt->bindParam(':Title',$title, PDO::PARAM_STR);
			$stmt->bindParam(':Description',$description, PDO::PARAM_STR);
			$stmt->bindParam(':Type',$type, PDO::PARAM_STR);
			$stmt->bindParam(':SavedMedia',$saved_media, PDO::PARAM_STR);
			$category_clean = rtrim($Category,", ");
			$stmt->bindParam(':Category',$category_clean, PDO::PARAM_STR);
			$stmt->bindParam(':Folder',$folder, PDO::PARAM_STR);
			
			$tags_clean = rtrim($Tags,", ");
			$stmt->bindParam(':Tags',$tags_clean, PDO::PARAM_STR);
			
			$title =  $data['Title'];
			$description = $data['Description'];
			$type = $data['Type'];
			$saved_media = $data['saved_media'];
			$category = $data['Category'];
			$folder = $data['Folder'];
			$tags = $data['Tags'];
			
			
			if($stmt->execute()){
				$data['MediaID'] = $this->conn->lastInsertId();
				// check to see if there Tags is available
				/*
				if( strlen(trim($data['Tags'])) > 0 ){
					$this->add_media_tags($data); // add tags for given media					
				}
				*/
				//$this->add_media_tags($data); // add tags for given media
				$media_attribs = array("ID"=>$data['MediaID'],"attributes"=>$data);
				$this->add_media_attributes($media_attribs); // add attributes of given media
				$result = array("MediaID"=>$data['MediaID'],"result"=>true);
				return $result;
				}else{ return false; }
		}else{
			$result = array("result"=>"duplicate title");
			return $result;
		}
	}

	/**
	* Add the attributes associated with Media
	* $data will include the ID of Media from General table
	* @param array $data
	*
	*/
	private function add_media_attributes($data){
		unset($data['attributes']['ID']);
		unset($data['attributes']['Tags']);
		unset($data['attributes']['saved_media']);
		unset($data['attributes']['Title']);
		unset($data['attributes']['Description']);
		unset($data['attributes']['Type']);
		foreach($data['attributes'] as $key=>$value){
			$stmt = $this->conn->prepare("INSERT INTO `".$this->media_attributes."` (Media_ID,Attribute, Attribute_Value) VALUES (:Media_ID, :Attribute, :Attribute_Value)");
			$stmt->bindParam(':Media_ID',$media_id, PDO::PARAM_INT);
			$stmt->bindParam(':Attribute',$attribute, PDO::PARAM_STR);
			$stmt->bindParam(':Attribute_Value',$attribute_value, PDO::PARAM_STR);
			
			$media_id =  $data['ID'];
			$attribute = $key;
			$attribute_value = $value;
			$stmt->execute();
		}		
	}
	
	/**
	* Add tags for Media
	* @param array $media
	*/
	private function add_media_tags($data){
		$tags_array = explode(" ",$data['Tags']);
		foreach($tags_array as $value){
			$stmt = $this->conn->prepare("INSERT INTO `".$this->media_tags."` (MediaID,Tag) VALUES (:MediaID, :Tag)");
			$stmt->bindParam(':MediaID',$media_id, PDO::PARAM_INT);
			$stmt->bindParam(':Tag',$tag, PDO::PARAM_STR);
			
			$media_id =  $data['MediaID'];
			$tag =  $value;
			$stmt->execute();
		}
	}
	
	private function delete_media_tags($MediaID){
		$query = "DELETE FROM `".$this->media_tags."` WHERE `MediaID`=:MediaID";
		$stmt = $this->conn->prepare($query);
		$stmt->bindValue(':MediaID',$MediaID, PDO::PARAM_INT);
		$stmt->execute();
		return $stmt->rowCount();
	}	
	
	/**
	* Get tags for given Media ID
	*
	*/
	private function get_media_tags($media_id){
		$query = "SELECT `Tag` from `" . $this->media_tags . "` WHERE `MediaID`=:MediaID";
		$stmt = $this->conn->prepare($query);
		$stmt->bindValue(':MediaID',$media_id, PDO::PARAM_STR);
		$results = "";
		if($stmt->execute()){
			$all_results = $stmt->fetchAll();
			if(count($all_results > 0)){
				foreach($all_results as $row){
					$results[] = $row['Tag'];
				}
				return $results;
			}else{
				return 0;
			}
		}else{
			return 0;
		}		
	}
	
	/**
	* Get all distinct tags
	* 
	*/
	public function get_tags_v1(){
		$query = "SELECT `Tag` from `" . $this->media_tags . "` WHERE `MediaID`=:MediaID";
	}
	
	/**
	* Update Document/Asset
	* @param array $data ( field = field to be updated; field_value = new value of field )
	* @return integer $return
	*/
	public function update($data){
		extract($data); 
		if ( $this->check_title($Title,$MediaID) == 0 ){
			if($Category == "" || !isset($Category)){
				$Category = "Other";
			}
			if($Tags == "" || !isset($Tags)){
				$Tags = "Other";
			}				
			$stmt = $this->conn->prepare("UPDATE `".$this->media."` SET `Title`=:Title, `Description`=:Description, `ModifiedDateTime`=:ModifiedDateTime, `Category`=:Category,`Tags`=:Tags WHERE `MediaID`=:MediaID");
			$stmt->bindValue(':MediaID',$MediaID, PDO::PARAM_INT);
			$stmt->bindValue(':Description',$Description, PDO::PARAM_INT);
			$stmt->bindValue(':Title',$Title, PDO::PARAM_INT);
			$stmt->bindValue(':ModifiedDateTime',$this->ts, PDO::PARAM_STR);
			$stmt->bindValue(':Category',rtrim($Category,", "), PDO::PARAM_STR);
			$stmt->bindValue(':Tags',rtrim($Tags,", "), PDO::PARAM_STR);
		
			if($stmt->execute()){
				// check to see if there Tags is available
/* 				if( strlen(trim($data['Tags'])) > 0 ){
					$this->delete_media_tags($MediaID);
					$this->add_media_tags($data); // add tags for given media					
				} */
				$result = array("MediaID"=>$MediaID,"result"=>true);
				return true;
			}else{ return false; }
				
		}else{
			$result = array("result"=>"duplicate title");
			return $result;
		}
	}
	
	/**
	* Update Title and SeoUrl
	* @param array $data ( field = field to be updated; field_value = new value of field )
	* @return integer $return
	*/
	public function update_savedmedia_seourl($data){
		extract($data); 
		//if ( $this->check_title($Title,$MediaID) == 0 ){
			$stmt = $this->conn->prepare("UPDATE `".$this->media."` SET `SavedMedia`=:SavedMedia, `SeoUrl`=:SeoUrl, `ModifiedDateTime`=:ModifiedDateTime WHERE `MediaID`=:MediaID");
			$stmt->bindValue(':MediaID',$MediaID, PDO::PARAM_INT);
			$stmt->bindValue(':SavedMedia',$SavedMedia, PDO::PARAM_INT);
			$stmt->bindValue(':SeoUrl',$SeoUrl, PDO::PARAM_STR);
			$stmt->bindValue(':ModifiedDateTime',$this->ts, PDO::PARAM_STR);			
			
			if($stmt->execute()){
				$result = array("MediaID"=>$MediaID,"result"=>true);
				return true;
			}else{ 
				return false; 
			}
				
		//}else{
			//$result = array("result"=>"duplicate title");
			//return $result;
		//}
	}	
	
	/**
	* Delete Document/Asset with given document id
	* @param integer $MediaID
	* @return integer $return
	*/
	public function delete($MediaID){
		$query = "UPDATE `".$this->media."` SET `Status` = 'Archived' WHERE `MediaID`=:MediaID";
		$stmt = $this->conn->prepare($query);
		$stmt->bindValue(':MediaID',$MediaID, PDO::PARAM_INT);
		$stmt->execute();
		return $stmt->rowCount();
	}
	
	/**
	* get_app_codes
	* Retrieve all app codes
	* @return array $result 
	*/
	public function get_app_codes(){
		$stmt = $this->conn->prepare("SELECT * FROM `".$this->view_app_codes."`");
		$stmt->execute();
		$results = $stmt->fetchAll(PDO::FETCH_ASSOC);
		return $results;
	}
	
	/*
	* get_categories
	*/
	public function get_categories(){
		$stmt = $this->conn->prepare("SELECT Category FROM mm_media WHERE Category <> ''");
		$stmt->execute();
		$categories_temp = array();

		while( $row = $stmt->fetch(PDO::FETCH_ASSOC)){			
			$row_list = explode(",",$row['Category']);
			if( count($row_list) > 1){
				foreach($row_list as $key=>$value){
					$categories_temp[] = trim($value);
				}
			}else{
				$categories_temp[] = trim($row['Category']);
			}	
		}
		$categories = array_unique($categories_temp,SORT_REGULAR);
		return $categories;
	}
	
	/**
	* get_media_by_folder
	*
	*/
	public function get_media_by_folder($folder){
		$query = "SELECT `MediaID`,`Title`,`Description`,`Category`,`SeoUrl`,`SavedMedia`,`CreatedDateTime`,`Folder`,`Tags`,`Type` FROM `".$this->media."` WHERE `Status`='Active' AND Folder = '".$folder."'";
		$stmt = $this->conn->prepare($query);	
		$stmt->execute();
		$all_results = $stmt->fetchAll(PDO::FETCH_ASSOC);

		if( count($all_results) > 0 ){
			foreach( $all_results as $row ){
				extract($row);
				$results[] = array("MediaID"=>$row['MediaID'],"Title"=>$row['Title'],"Category"=>$row['Category'],"Description"=>$row['Description'],"SavedMedia"=>$SavedMedia,"SeoUrl"=>$SeoUrl,"CreatedDateTime"=>$CreatedDateTime,"Tags"=>$Tags,"Folder"=>$Folder,"Type"=>$Type);
			}
			return $results;
		}else{
			return 0;
		}
		
	}	
	
	/*
	* get_tags
	*/
	public function get_tags(){
		$stmt = $this->conn->prepare("SELECT Tags FROM mm_media WHERE Tags <> ''");
		$stmt->execute();
		$tags_temp = array();

		while( $row = $stmt->fetch(PDO::FETCH_ASSOC)){			
			$row_list = explode(",",$row['Tags']);
			if( count($row_list) > 1){
				foreach($row_list as $key=>$value){
					$tags_temp[] = trim($value);
				}
			}else{
				$tags_temp[] = trim($row['Tags']);
			}	
		}
		$tags = array_unique($tags_temp,SORT_REGULAR);
		return $tags;
	}
	
	private function check_title( $title, $MediaID = 0 ){
		if( $MediaID != 0 ){
			$stmt = $this->conn->prepare("SELECT COUNT(*) FROM `".$this->media."` WHERE Title=:Title AND `MediaID` <> :MediaID");
			$stmt->bindParam(':MediaID',$MediaID, PDO::PARAM_INT);
		}else{
			$stmt = $this->conn->prepare("SELECT COUNT(*) FROM `".$this->media."` WHERE Title=:Title");	
		}
		
		$stmt->bindParam(':Title',$title, PDO::PARAM_STR);
		$stmt->execute();
		$number_of_rows = $stmt->fetchColumn();
		return $number_of_rows;
		
	}
	
	
}