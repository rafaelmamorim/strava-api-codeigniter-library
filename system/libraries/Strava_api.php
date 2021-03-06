<?php

/**
 * CodeIgniter
 *
 * An open source application development framework for PHP
 *
 * This content is released under the MIT License (MIT)
 *
 * Copyright (c) 2014 - 2016, British Columbia Institute of Technology
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 *
 * @package	CodeIgniter
 * @author	EllisLab Dev Team
 * @copyright	Copyright (c) 2008 - 2014, EllisLab, Inc. (https://ellislab.com/)
 * @copyright	Copyright (c) 2014 - 2016, British Columbia Institute of Technology (http://bcit.ca/)
 * @license	http://opensource.org/licenses/MIT	MIT License
 * @link	https://codeigniter.com
 * @since	Version 1.0.0
 * @filesource
 */
// <editor-fold desc="To do">
/*

 * Activities
  Create an Activity
  Get Activity Zones
  Update Activity

 * Athletes
  Update Athlete

 * Clubs
  Join Club
  Leave Club

 * Routes
  Get Route
  List Athlete Routes

 * RunningRaces
  Get Running Race
  List Running Races

 * SegmentEfforts
  List Segment Efforts
  Get Segment Effort

 * Segments
  Explore segments
  Get Segment Leaderboard
  List Starred Segments
  Get Segment
  Star Segment

 * Streams
  Get Activity Streams
  Get segment effort streams
  Get Segment Streams

 * Uploads
  Upload Activity
  Get Upload
 */
// </editor-fold>


defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * CLass for leveraging the Strava API
 *
 * @package		CodeIgniter
 * @subpackage	Libraries
 * @category	Third Party API's
 * @author		James Bratt
 * @link		
 */
class CI_strava_api {

    protected $ci;
    protected $client_id;
    protected $client_secret;
    protected $oath_url;

    // --------------------------------------------------------------------

    /**
     * Constructor
     * We will instantiate our config objects here
     * This involves adding your strava app id, client id and api uris to the $config array
     * In application/config/config.php
     */
    public function __construct() {
        $this->ci = & get_instance();

        $this->client_id = $this->ci->config->item('client_id');
        $this->client_secret = $this->ci->config->item('client_secret');
        $this->oath_url = $this->ci->config->item('oath_url');
    }

    /**
     * A function for requesting an oath token from the strava api  
     * The token is then used to authenticate further api calls 
     * Pass the strava redirect url from a controller  
     */
    public function getToken($url) {
        /**
         * Extract auth code from the url  
         */
        parse_str($url, $params);
        $code = $params['code'];

        /**
         * Use curl post request against the strava oauth endpoint 
         */
        $get_token = curl_init();

        curl_setopt($get_token, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($get_token, CURLOPT_URL, $this->oath_url);
        curl_setopt($get_token, CURLOPT_POST, 1);

        curl_setopt($get_token, CURLOPT_POSTFIELDS, http_build_query(array('client_id' => $this->client_id, 'client_secret' => $this->client_secret, 'code' => $code)));

        curl_setopt($get_token, CURLOPT_RETURNTRANSFER, true);

        $tokenResponse = curl_exec($get_token);

        if (curl_errno($get_token)) {
            echo 'Curl error: ' . curl_error($get_token);
        }

        /**
         * Decode the response to extract the token
         */
        $decodedToken = json_decode($tokenResponse, true);

        $token = $decodedToken['access_token'];

        return $token;

        curl_close($get_token);
    }

    // <editor-fold desc="Activities">

    /**
     * Returns the activities of an athlete for a specific identifier.
     * 
     * @param string $token
     * @param int $per_page
     * @param int $page
     * @return string
     */
    public function getListOfActivities($token, $page = 1, $per_page = 30) {
        $headers = array('Authorization: Bearer ' . $token, 'per_page=' . $per_page, 'page=' . $page);
        $curl_handler = curl_init();
        $this->CurlOptions($curl_handler, $headers, $this->ci->config->item('list_athlete_activities'));
        $activityResponse = curl_exec($curl_handler);

        if (curl_errno($curl_handler)) {
            echo 'Curl error: ' . curl_error($curl_handler);
        }

        curl_close($curl_handler);
        return $activityResponse;
    }

    /**
     * Get an activity. $token and $idActivity must belong to same athlete
     * 
     * @param string $token
     * @param string $idActivity
     * @return string
     * 
     */
    public function getActivity($token, $idActivity) {
        $headers = array('Authorization: Bearer ' . $token, 'include_all_efforts=true');
        $curl_handler = curl_init();

        $url = str_replace("{id}", $idActivity, $this->ci->config->item('get_activities_url'));
        $this->CurlOptions($curl_handler, $headers, $url);
        $activityResponse = curl_exec($curl_handler);

        if (curl_errno($curl_handler)) {
            echo 'Curl error: ' . curl_error($curl_handler);
        }

        curl_close($curl_handler);

        return $activityResponse;
    }

    /**
     * Get an activity comments. $token and $idActivity must belong to same athlete
     * 
     * @param string $token
     * @param string $idActivity
     * @param int $per_page
     * @param int $page
     * @return string
     */
    public function getActivityComments($token, $idActivity, $per_page = 200, $page = 1) {
        $headers = array('Authorization: Bearer ' . $token, 'per_page=' . $per_page, 'page=' . $page);
        $curl_handler = curl_init();

        $url = str_replace("{id}", $idActivity, $this->ci->config->item('list_activity_comments_url'));
        $this->CurlOptions($curl_handler, $headers, $url);
        $activityResponse = curl_exec($curl_handler);

        if (curl_errno($curl_handler)) {
            echo 'Curl error: ' . curl_error($curl_handler);
        }

        curl_close($curl_handler);
        return $activityResponse;
    }

    /**
     * Get an activity kudoers. $token and $idActivity must belong to same athlete
     * 
     * @param string $token
     * @param string $idActivity
     * @param int $per_page
     * @param int $page
     * @return string
     */
    public function getActivityKudoers($token, $idActivity, $per_page = 200, $page = 1) {
        $headers = array('Authorization: Bearer ' . $token, 'per_page=' . $per_page, 'page=' . $page);
        $curl_handler = curl_init();

        $url = str_replace("{id}", $idActivity, $this->ci->config->item('list_activity_kudoers_url'));
        $this->CurlOptions($curl_handler, $headers, $url);

        $activityResponse = curl_exec($curl_handler);

        if (curl_errno($curl_handler)) {
            echo 'Curl error: ' . curl_error($curl_handler);
        }

        curl_close($curl_handler);
        return $activityResponse;
    }

    /**
     * Returns the laps of an activity identified by an identifier.
     * 
     * @param string $token
     * @param string $idActivity
     * @return string
     */
    public function getActivityLaps($token, $idActivity) {
        $headers = array('Authorization: Bearer ' . $token);
        $curl_handler = curl_init();

        $url = str_replace("{id}", $idActivity, $this->ci->config->item('list_activity_laps_url'));
        $this->CurlOptions($curl_handler, $headers, $url);
        $activityResponse = curl_exec($curl_handler);

        if (curl_errno($curl_handler)) {
            echo 'Curl error: ' . curl_error($curl_handler);
        }

        curl_close($curl_handler);
        return $activityResponse;
    }

    // </editor-fold>
    // <editor-fold desc="Atlhetes">   
    /**
     * Returns the currently authenticated athlete.
     * 
     * @param string $token
     * @return string
     */
    public function getAthlete($token) {
        $headers = array('Authorization: Bearer ' . $token);
        $curl_handler = curl_init();

        $this->CurlOptions($curl_handler, $headers, $this->ci->config->item('get_Authenticated_Athlete_url'));
        $activityResponse = curl_exec($curl_handler);

        if (curl_errno($curl_handler)) {
            echo 'Curl error: ' . curl_error($curl_handler);
        }

        curl_close($curl_handler);
        return $activityResponse;
    }

    /**
     * Returns the the authenticated athlete's heart rate and power zones.
     * 
     * @param string $token
     * @return string
     */
    public function getAthleteZones($token) {
        $headers = array('Authorization: Bearer ' . $token);
        $curl_handler = curl_init();

        $this->CurlOptions($curl_handler, $headers, $this->ci->config->item('get_zones_url'));
        $activityResponse = curl_exec($curl_handler);

        if (curl_errno($curl_handler)) {
            echo 'Curl error: ' . curl_error($curl_handler);
        }

        curl_close($curl_handler);
        return $activityResponse;
    }

    /**
     * Returns the activity stats of an athlete.
     * 
     * @param string $token
     * @param string $id
     * @param int $page
     * @param int $per_page
     * @return string
     */
    public function getAthleteStats($token, $id, $page = 1, $per_page = 30) {
        $headers = array('Authorization: Bearer ' . $token, 'page=' . $page, 'per_page=' . $per_page);
        $curl_handler = curl_init();
        $url = str_replace("{id}", $id, $this->ci->config->item('get_athlete_stats_url'));

        $this->CurlOptions($curl_handler, $headers, $url);
        $activityResponse = curl_exec($curl_handler);

        if (curl_errno($curl_handler)) {
            echo 'Curl error: ' . curl_error($curl_handler);
        }

        curl_close($curl_handler);
        return $activityResponse;
    }

    // </editor-fold>
    // <editor-fold desc="Clubs">
    /**
     * Retrieve recent activities from members of a specific club. 
     * The authenticated athlete must belong to the requested club in 
     * order to hit this endpoint. Pagination is supported. 
     * Enhanced Privacy Mode is respected for all activities.
     * 
     * @param string $token
     * @param string $id
     * @param int $page
     * @param int $per_page
     * @return string
     */
    public function getListOfClubActivities($token, $id, $page = 1, $per_page = 30) {
        $headers = array('Authorization: Bearer ' . $token, 'per_page=' . $page, 'per_page=' . $per_page);
        $curl_handler = curl_init();

        $url = str_replace("{id}", $id, $this->ci->config->item('list_club_activities_url'));
        $this->CurlOptions($curl_handler, $headers, $url);

        $activityResponse = curl_exec($curl_handler);

        if (curl_errno($curl_handler)) {
            echo 'Curl error: ' . curl_error($curl_handler);
        }

        curl_close($curl_handler);
        return $activityResponse;
    }

    /**
     * Returns a list of the administrators of a given club.
     * 
     * @param string $token
     * @param string $id
     * @param int $page
     * @param int $per_page
     * @return string
     */
    public function getClubAdministrators($token, $id, $page = 1, $per_page = 30) {
        $headers = array('Authorization: Bearer ' . $token, 'per_page=' . $page, 'per_page=' . $per_page);
        $curl_handler = curl_init();

        $url = str_replace("{id}", $id, $this->ci->config->item('list_club_administrators_url'));
        $this->CurlOptions($curl_handler, $headers, $url);

        $activityResponse = curl_exec($curl_handler);

        if (curl_errno($curl_handler)) {
            echo 'Curl error: ' . curl_error($curl_handler);
        }

        curl_close($curl_handler);
        return $activityResponse;
    }

    /**
     * Returns a given club using its identifier.
     * 
     * @param string $token
     * @param string $id
     * @return string
     */
    public function getClub($token, $id) {
        $headers = array('Authorization: Bearer ' . $token);
        $curl_handler = curl_init();

        $url = str_replace("{id}", $id, $this->ci->config->item('get_club_url'));
        $this->CurlOptions($curl_handler, $headers, $url);

        $activityResponse = curl_exec($curl_handler);

        if (curl_errno($curl_handler)) {
            echo 'Curl error: ' . curl_error($curl_handler);
        }

        curl_close($curl_handler);
        return $activityResponse;
    }

    /**
     * Returns a list of the athletes who are members of a given club.
     * 
     * @param string $token
     * @param string $id
     * @param int $page
     * @param int $per_page
     * @return string
     */
    public function getClubMembers($token, $id, $page = 1, $per_page = 30) {
        $headers = array('Authorization: Bearer ' . $token, 'per_page=' . $page, 'per_page=' . $per_page);
        $curl_handler = curl_init();

        $url = str_replace("{id}", $id, $this->ci->config->item('list_club_members_url'));
        $this->CurlOptions($curl_handler, $headers, $url);

        $activityResponse = curl_exec($curl_handler);

        if (curl_errno($curl_handler)) {
            echo 'Curl error: ' . curl_error($curl_handler);
        }

        curl_close($curl_handler);
        return $activityResponse;
    }

    /**
     * Returns a list of the clubs whose membership includes the authenticated athlete.
     * 
     * @param string $token
     * @param int $page
     * @param int $per_page
     * @return string
     */
    public function getAthleteClubs($token, $page = 1, $per_page = 30) {
        $headers = array('Authorization: Bearer ' . $token, 'per_page=' . $page, 'per_page=' . $per_page);
        $curl_handler = curl_init();

        $this->CurlOptions($curl_handler, $headers, $this->ci->config->item('list_athlete_clubs_url'));

        $activityResponse = curl_exec($curl_handler);

        if (curl_errno($curl_handler)) {
            echo 'Curl error: ' . curl_error($curl_handler);
        }

        curl_close($curl_handler);
        return $activityResponse;
    }

    // </editor-fold>
    // <editor-fold desc="Gears">
    /**
     * Returns an equipment using its identifier.
     * 
     * @param string $token
     * @param string $id
     * @return string
     */
    public function getGear($token, $id) {
        $headers = array('Authorization: Bearer ' . $token);
        $curl_handler = curl_init();

        $url = str_replace("{id}", $id, $this->ci->config->item('get_equipment_url'));
        curl_setopt($curl_handler, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl_handler, CURLOPT_URL, $url);
        curl_setopt($curl_handler, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($curl_handler, CURLOPT_RETURNTRANSFER, true);

        $activityResponse = curl_exec($curl_handler);

        if (curl_errno($curl_handler)) {
            echo 'Curl error: ' . curl_error($curl_handler);
        }

        curl_close($curl_handler);
        return $activityResponse;
    }

    // </editor-fold>
    // <editor-fold desc="CURL config">
    private function CurlOptions($handler, $headers, $url) {
        curl_setopt($handler, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($handler, CURLOPT_URL, $url);
        curl_setopt($handler, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($handler, CURLOPT_RETURNTRANSFER, true);
    }

    // </editor-fold>
}
