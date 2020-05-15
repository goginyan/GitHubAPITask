<?php

namespace App\Http\Helpers;

class GithubApiClient
{
    // we will send this as a 'User-Agent' header in api requests
    private $userAgent = 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.13) Gecko/20080311 Firefox/2.0.0.13';
    // users search api url
    private $searchUrl = 'https://api.github.com/search/users?q=';
    // users list api url
    private $usersListUrl = 'https://api.github.com/users';

    /**
     * Get List of users (first page)
     *
     * @return array
     */
    public function getUsers(): array
    {
        $cURLConnection = curl_init();

        curl_setopt($cURLConnection, CURLOPT_URL, $this->usersListUrl);
        curl_setopt($cURLConnection, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($cURLConnection,CURLOPT_USERAGENT, $this->userAgent);


        $result = curl_exec($cURLConnection);
        curl_close($cURLConnection);

        $users = json_decode($result, true);

        return $this->getUsersAdditionalInfo($users);
    }

    /**
     * Get users by search criteria
     *
     * @param string $query
     * @return array
     */
    public function searchUsers(string $query): array
    {
        $cURLConnection = curl_init();

        curl_setopt($cURLConnection, CURLOPT_URL, $this->searchUrl . $query);
        curl_setopt($cURLConnection, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($cURLConnection,CURLOPT_USERAGENT, $this->userAgent);

        $result = curl_exec($cURLConnection);
        curl_close($cURLConnection);

        $users = json_decode($result, true)['items'];

        return $this->getUsersAdditionalInfo($users);
    }

    /**
     * Add followers and repos info to users list
     *
     * @param array $users
     * @return array
     */
    private function getUsersAdditionalInfo(array $users): array
    {
        // Build the multi-curl handle
        $cURLMultiConnection = curl_multi_init();
        // Create get requests for each user to get followers and repos
        foreach ($users as $i => $user) {
            // Initialize curl for followers request
            $ch['followers-' . $i] = curl_init($user['followers_url']);

            curl_setopt($ch['followers-' . $i], CURLOPT_URL, $user['followers_url']);
            curl_setopt($ch['followers-' . $i], CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch['followers-' . $i],CURLOPT_USERAGENT, $this->userAgent);
            // Add the handle
            curl_multi_add_handle($cURLMultiConnection, $ch['followers-' . $i]);
            // Initialize curl for repos request
            $ch['repos-' . $i] = curl_init($user['repos_url']);

            curl_setopt($ch['repos-' . $i], CURLOPT_URL, $user['repos_url']);
            curl_setopt($ch['repos-' . $i], CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch['repos-' . $i],CURLOPT_USERAGENT, $this->userAgent);
            // Add the handle
            curl_multi_add_handle($cURLMultiConnection, $ch['repos-' . $i]);
        }
        // Start performing the request
        do {
            $execReturnValue = curl_multi_exec($cURLMultiConnection, $runningHandles);
        } while ($execReturnValue == CURLM_CALL_MULTI_PERFORM);
        // Loop and continue processing the request
        while ($runningHandles && $execReturnValue == CURLM_OK) {
            if (curl_multi_select($cURLMultiConnection) != -1) {
                usleep(100);
            }
            do {
                $execReturnValue = curl_multi_exec($cURLMultiConnection, $runningHandles);
            } while ($execReturnValue == CURLM_CALL_MULTI_PERFORM);
        }
        // Check for any errors
        if ($execReturnValue != CURLM_OK) {
            trigger_error("Curl multi read error $execReturnValue\n", E_USER_WARNING);
        }
        // Extract the content
        foreach ($users as $i => $user) {
            /* START add followers data*/
            // Check for errors
            $curlError = curl_error($ch['followers-' . $i]);
            if ($curlError == "") {
                $responseContent = curl_multi_getcontent($ch['followers-' . $i]);
                $users[$i]['followers'] = json_decode($responseContent, true);
            } else {
                exit("Curl error on handle $i: $curlError\n");
            }
            // Remove and close the handle
            curl_multi_remove_handle($cURLMultiConnection, $ch['followers-' . $i]);
            curl_close($ch['followers-' . $i]);
            /* END add followers data*/
            /* START add repos data*/
            // Check for errors
            $curlError = curl_error($ch['repos-' . $i]);
            if ($curlError == "") {
                $responseContent = curl_multi_getcontent($ch['repos-' . $i]);
                $users[$i]['repos'] = json_decode($responseContent, true);
            } else {
                exit("Curl error on handle $i: $curlError\n");
            }
            // Remove and close the handle
            curl_multi_remove_handle($cURLMultiConnection, $ch['repos-' . $i]);
            curl_close($ch['repos-' . $i]);
            /* END add repos data*/
        }
        // Clean up the curl_multi handle
        curl_multi_close($cURLMultiConnection);
        // Return users list with updated info
        return $users ;
    }
}