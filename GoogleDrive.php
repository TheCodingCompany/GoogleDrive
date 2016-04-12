<?php
/**
 * Intellectual Property of the Coding Company AB All rights reserved.
 * 
 * @copyright (c) 2015, the Coding Company AB
 * @author V.A. (Victor) Angelier <vangelier@hotmail.com>
 * @version 1.0
 * @license http://www.apache.org/licenses/GPL-compatibility.html GPL
 * @package CodingCompany
 * 
 */

namespace CodingCompany;

/**
 * Description of GoogleDrive
 *
 * @author Victor Angelier <vangelier@hotmail.com>
 * @package CodingCompany
 */
class GoogleDrive
{
    
    /**
     * Holds our Google API Service
     * @var type 
     */
    var $google_service = null;
    
    /**
     * Holds our Google Client
     * @var type 
     */
    var $google_client = null;
    
    /**
     * Holds the file to our Google application Credentials
     * @var type 
     */
    protected $google_drive_api_credentials = "";
    
    /**
     * Construct Google Drive Class
     * 
     * @param string $google_application_credentials Full path to credentials file
     */
    public function __construct($google_application_credentials = null){
        $this->setCredentials($google_application_credentials);
    }
    
    /**
     * Set the Google Application Credentials file path
     * 
     * @param type $filepath Full path to the credentials file
     */
    public function setCredentials($filepath = null){
        //Set our credentials
        putenv('GOOGLE_APPLICATION_CREDENTIALS='.$filepath);
    }
    
    /**
     * Initiate google drive
     */
    public function init_google_drive(){
        ini_set('display_errors', 1);
        
        $this->google_client = new Google_Client();
        $this->google_client->setHttpClient(new GuzzleHttp\Client(['verify'=>false]));
        $this->google_client->useApplicationDefaultCredentials();
        
        $this->google_client->setScopes(['https://www.googleapis.com/auth/drive']);
        $this->google_service = new Google_Service_Drive($this->google_client);
    }
    
    /**
     * Get the used storage quota
     * @return boolean
     */
    public function quota(){
        //Get quota
        $quota = $this->google_service->about->get(array(
                "fields" => "storageQuota"
        ));
        echo "<pre>";
        if(($available = $quota->storageQuota->limit-$quota->storageQuota->usage) > 0){
            return $available." bytes available";
        }else{
            return false;
        }
    }
    
    /**
     * Search for files on Google Drive
     * 
     * @param type $name
     */
    public function searchFile($name = ""){
        $pageToken = null;
        $result = array();
        
        try
        {
            do
            {
                // Print the names and IDs for up to 10 files.
                $optParams = array(
                    'pageToken' => $pageToken,
                    'spaces'    => 'drive',
                    'fields'    => "nextPageToken, files(id, name)",
                    'q'         => "name contains '{$name}'"  
                );

                $response = $this->google_service->files->listFiles($optParams);
                foreach($response->files as $file){
                    array_push($result, $file);
                }

            }
            while($pageToken != null);
        }
        catch(Exception $err){
            return false;
        }
        return $result;
    }
    
    /**
     * Show current files in storage
     */
    public function showFiles(){
        echo "<pre>";
        
        // Print the names and IDs for up to 10 files.
        $optParams = array(
            'pageSize' => 10,
            'fields' => "nextPageToken, files(id, name)"
        );
        $results = $this->google_service->files->listFiles($optParams);

        if(count($results->getFiles()) == 0){
            print "No files found.\n";
        }else {
            print "Files:\n";
            foreach ($results->getFiles() as $file) {
                printf("%s (%s)\n", $file->getName(), $file->getId());
            }
        }
        echo "</pre>";
        die();
    }
    
    /**
     * Delete a file from our drive
     * @param type $file_id
     */
    public function deleteFile($file_id = null){
        return $this->google_service->files->delete($file_id);
    }
    
    /**
     * Create Google Drive file
     * @param type $file Full path to the file
     * @param type $name Name of the file
     */
    public function createFile($file = "", $name = ""){
        
        //Initiate file
        $gfile = new Google_Service_Drive_DriveFile();
        $gfile->setName($name);
        
        try
        {
            //Upload the file
            $result = $this->google_service->files->create($gfile, array(
                'data'          => file_get_contents($file),
                'mimeType'      => 'application/octet-stream',
                'uploadType'    => 'multipart'
            ));
            return $result;
            
        } catch (Exception $ex) {
            echo "<pre>";
            print_r($ex->getMessage());
            return false;
        }
        return false;
    }
    
    /**
     * Store data to Google Drive File
     * @param type $filedata File data to store
     * @param type $name Name of the file
     */
    public function storeFile($filedata = "", $name = ""){
        
        //Initiate file
        $gfile = new Google_Service_Drive_DriveFile();
        $gfile->setName($name);
        
        try
        {
            //Upload the file
            $result = $this->google_service->files->create($gfile, array(
                'data'          => $filedata,
                'mimeType'      => 'application/octet-stream',
                'uploadType'    => 'multipart'
            ));
            return $result;
            
        } catch (Exception $ex) {
            echo "<pre>";
            print_r($ex->getMessage());
            return false;
        }
        return false;
    }
    
    /**
     * Share the file with other users on Google Drive
     * 
     * @param type $file_id  The unique fileID returned by DriveFile->create
     * @param type $users Array of e-mail addresses
     */
    public function shareFile($file_id = "", $users = []){
        //Initiate batch
        $this->google_service->getClient()->setUseBatch(true);
        try
        {
            //Create the batch
            $batch = $this->google_service->createBatch();
            
            //Loop through the users
            foreach($users as $user){
                
                //Get user and domain seperated
                $mail_address_parts = explode("@", $user);
                
                //Create user permissions
                $userPermission = new Google_Service_Drive_Permission(array(
                    'type'                  => 'user',
                    'role'                  => 'writer',
                    'emailAddress'          => $user,
                    'transferOwnership'     => true,
                    'sendNotificationEmail' => true
                ));
                /**
                 * FileID, Email Message, SendNotification, TransferOwnership,
                 */
                $request = $this->google_service->permissions->create(
                    $file_id,
                    $userPermission, 
                    array('fields' => 'id')
                );
                $batch->add($request, 'user');

                //Create domain permissions
                //@todo implement this later
                /*
                $domainPermission = new Google_Service_Drive_Permission(array(
                    'type'              => 'domain',
                    'role'              => 'owner',
                    'domain'            => $mail_address_parts[1],
                    'transferOwnership' => true
                ));
                $request = $this->google_service->permissions->create($file_id, $domainPermission, array('fields' => 'id'));
                $batch->add($request, 'domain');
                */
            }
            
            //Execute the batch
            return $batch->execute();
        }
        finally
        {
            $this->google_service->getClient()->setUseBatch(false);
        }
    }
}
