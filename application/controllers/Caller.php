<?php

defined('BASEPATH') OR exit('No direct script access allowed');

require APPPATH . '/libraries/REST_Controller.php';

class Caller extends REST_Controller {
    /* Date: 06-03-2019
     * Krishna Gandhi
     * To send the signup permission to user mobile.
     * response will get response id
     */

    public function index_post() {
        //param
        $phoneNumber = $_POST['phoneNumber'];

        //check in db if user information is there or not. if there please send information
        /* $this->db->select('PhoneNumber, FirstName, LastName, Email, Gender, ProfilePhoto, Longitude, Lattitude, FCMToken');
          $this->db->where('PhoneNumber', $phoneNumber);
          $information = $this->db->get('user_information')->row();

          if (!empty($information)) {
          $response = array(
          'status' => TRUE,
          'error_code' => SUCCESS_CODE,
          'requestId' => $information
          );

          $this->set_response($response, REST_Controller::HTTP_OK);
          } else { */

        //call true caller api for signup permission
        $url = CALLER_REQUEST_URL;
        $param = '{"phoneNumber":' . $phoneNumber . ',"state":"ne4_cRtzx73-alui_XDvzS5h"}';

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($curl, CURLOPT_POSTFIELDS, $param);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array(
            "Content-Type: application/json",
            "appKey:" . APP_KEY
                )
        );
        $output = curl_exec($curl);
        curl_close($curl);

        //fetch request id
        $fetchRequestId = json_decode($output);

        //insert into db
        $insertData = array(
            'PhoneNumber' => $phoneNumber,
            'RequestId' => $fetchRequestId->requestId
        );
        $this->db->insert('user_information', $insertData);

        $response = array(
            'status' => TRUE,
            'error_code' => SUCCESS_CODE,
            'output' => $output
        );

        $this->set_response($response, REST_Controller::HTTP_OK);
        //}
    }

    /* Date: 06-05-2019
     * Krishna Gandhi
     * to get the access token and store in db
     * in response will get requestId, accesstoken
     */

    public function accesstoken_post() {
        $data = json_decode(file_get_contents('php://input'), TRUE);

        if (!empty($data['requestId']) && !empty($data['accessToken'])) {

            $updateToken = array(
                'AccessToken' => $data['accessToken']
            );
            $this->db->where('RequestId', $data['requestId']);
            $this->db->update('user_information', $updateToken);
        } else {
            $response = array(
                'status' => FALSE,
                'error_code' => BAD_CODE,
                'error' => 'Bad Request'
            );
            $this->response($response, REST_Controller::HTTP_BAD_REQUEST);
        }
    }

    public function check_access_token_post() {
        $phoneNumber = $_POST['phoneNumber'];

        $this->db->select('AccessToken');
        $this->db->where('PhoneNumber', $phoneNumber);
        $token = $this->db->get('user_information')->row();

        if (!empty($token->AccessToken)) {
            $response = array(
                'status' => TRUE,
                'error_code' => SUCCESS_CODE,
            );

            $this->set_response($response, REST_Controller::HTTP_OK);
        } else {
            $response = array(
                'status' => FALSE,
                'error_code' => BAD_CODE,
                'error' => 'Bad Request'
            );
            $this->response($response, REST_Controller::HTTP_BAD_REQUEST);
        }
    }

    /* Date: 06-05-2019
     * Krishna Gandhi
     * Fetch user information 
     */

    public function information_post() {
        $phoneNumber = $_POST['phoneNumber'];

        if (!empty($phoneNumber)) {
            $this->db->select('AccessToken');
            $this->db->where('PhoneNumber', $phoneNumber);
            $token = $this->db->get('user_information')->row();

            if (!empty($token->AccessToken)) {

                //fetch user profile information
                $url = CALLER_INFO_URL;

                $curl = curl_init();
                curl_setopt($curl, CURLOPT_URL, $url);
                curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "GET");
                curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($curl, CURLOPT_HTTPHEADER, array(
                    "Authorization:Bearer " . $token->AccessToken
                        )
                );
                $output = curl_exec($curl);
                curl_close($curl);

                $infoArray = json_decode($output);
                /* echo '<pre>';
                  print_r($infoArray);exit; */

                //insert into database
                $updateUserInformation = array(
                    'PhoneNumber' => $infoArray->phoneNumbers[0],
                    'FirstName' => $infoArray->name->first,
                    'LastName' => $infoArray->name->last,
                    'Email' => $infoArray->onlineIdentities->email,
                    'Gender' => $infoArray->gender,
                    'ProfilePhoto' => $infoArray->avatarUrl,
                    'DateCreated' => date('Y-m-d H:i:s')
                );
                $this->db->where('PhoneNumber', $phoneNumber);
                $this->db->update('user_information', $updateUserInformation);

                $response = array(
                    'status' => TRUE,
                    'error_code' => SUCCESS_CODE,
                    'user' => $updateUserInformation
                );
                $this->set_response($response, REST_Controller::HTTP_OK);
            } else {
                $response = array(
                    'status' => FALSE,
                    'error_code' => BAD_CODE,
                    'error' => 'Bad Request'
                );
                $this->response($response, REST_Controller::HTTP_BAD_REQUEST);
            }
        } else {
            $response = array(
                'status' => FALSE,
                'error_code' => BAD_CODE,
                'error' => 'Bad Request'
            );
            $this->response($response, REST_Controller::HTTP_BAD_REQUEST);
        }
    }

    /* Date:07-05-2019
     * Krishna Gandhi
     * notification to user
     */

    public function notification_post() {
        $phoneNumber = $_POST['phonrNumber'];
        $fcmToken = $_POST['fcmtoken'];

        $url = "https://android.googleapis.com/gcm/send/" . $fcmToken;

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array(
            "Content-Length: 0",
            "TTL:60",
            "Authorization:key=" . NOTIFICATION_AUTHORIZATION_KEY
                )
        );
        $output = curl_exec($curl);
        curl_close($curl);

        $updateFcmToken = array(
            'FCMToken' => $fcmToken
        );
        $this->db->where('PhoneNumber', $phoneNumber);
        $this->db->update('user_information', $updateFcmToken);

        $response = array(
            'status' => TRUE,
            'error_code' => SUCCESS_CODE,
        );

        $this->set_response($response, REST_Controller::HTTP_OK);
    }

    /* Date: 07-05-2019
     * Krishna Gandhi
     * insert location details to DB
     */

    public function insert_location_post() {
        $phoneNumber = $_POST['phoneNumber'];
        $latitude = $_POST['latitude'];
        $longitude = $_POST['longitude'];

        //insert location
        $updateLocation = array(
            'Latitude' => $latitude,
            'Longitude' => $longitude
        );
        $this->db->where('PhoneNumber', $phoneNumber);
        $this->db->update('user_information', $updateLocation);

        $response = array(
            'status' => TRUE,
            'error_code' => SUCCESS_CODE,
        );

        $this->set_response($response, REST_Controller::HTTP_OK);
    }

    public function delete_notification_post() {
        $phoneNumber = $_POST['phonrNumber'];

        $updateFcmToken = array(
            'FCMToken' => ''
        );
        $this->db->where('PhoneNumber', $phoneNumber);
        $this->db->update('user_information', $updateFcmToken);

        $response = array(
            'status' => TRUE,
            'error_code' => SUCCESS_CODE,
        );

        $this->set_response($response, REST_Controller::HTTP_OK);
    }

}
