<?php

defined('BASEPATH') OR exit('No direct script access allowed');

require APPPATH . '/libraries/REST_Controller.php';
/* Login
 * Check user with credential
 */

class Token extends REST_Controller {

    public function index_post() {
        $headers = $this->input->request_headers();
        if (array_key_exists('Authorization', $headers) && !empty($headers['Authorization'])) {
            //TODO: Change 'token_timeout' in application\config\jwt.php
            $decodedToken = AUTHORIZATION::validateTimestamp($headers['Authorization']);

            if (isset($decodedToken->response[0]->UserId) && !empty($decodedToken->response[0]->UserId)) {

                //fetch user details
                $this->db->select('UserId, Mobile, Password, AccountVerified, Status, DateCreated');
                $this->db->where('UserId', $this->input->get('userid'));
                $userDetails = $this->db->get('users')->result_array();

                //response
                $tokenData = array();
                $tokenData['response'] = $userDetails;
                $tokenData['timestamp'] = now();

                $output['token'] = AUTHORIZATION::generateToken($tokenData);
                $response = array(
                    'status' => TRUE,
                    'error_code' => 0,
                    'response' => $output
                );
                $this->set_response($response, REST_Controller::HTTP_OK);
            } else {
                $response = array(
                    'status' => FALSE,
                    'error_code' => 400,
                    'response' => ''
                );
                $this->response($response, REST_Controller::HTTP_BAD_REQUEST);
            }
        } else {
            $response = array(
                'status' => FALSE,
                'error_code' => 401,
                'response' => ''
            );
            $this->response($response, REST_Controller::HTTP_UNAUTHORIZED);
        }
    }

}
