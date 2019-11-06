<?php
function DBConnect($HOST, $USER, $PWD, $DB)
{
    $con = mysqli_connect($HOST, $USER, $PWD, $DB);
    if (mysqli_connect_errno()){
        return false;
    } else {
        return $con;
    }
}
function pushToResultArray($detArray, $newArray)
{
    for ($x = 0; $x < count($newArray); $x++) {
        array_push($detArray, $newArray[$x]);
    }
    return $detArray;
}
function pushToResultArray1($detArray, $newJsonArray, $newArray)
{
    for ($x = 0; $x < count($newArray); $x++) {
        array_push($detArray, $newJsonArray["$newArray[$x]"]);
    }
    return $detArray;
}
function makeResult(){
    $curl = curl_init();
    $url_first = "https://gateway.reddit.com/desktopapi/v1/subreddits/startups?rtj=only&redditWebClient=web2x&app=web2x-client-production&allow_over18=&include=prefsSubreddit&after=";
    $url_second = "&dist=100&layout=card&sort=hot&geo_filter=TR";
    $url = "";
    $result = [];
    $resultIds   = [];
    $resultPosts = [];
    curl_setopt_array($curl, array(
        CURLOPT_RETURNTRANSFER => 1,
        CURLOPT_URL => 'https://gateway.reddit.com/desktopapi/v1/subreddits/startups',
        CURLOPT_USERAGENT => 'User Agent X'
    ));
    for($i = 0; $i < 2; $i++)
    {
        $resp = curl_exec($curl);
        $jsonData = json_decode($resp, true);
        $len =  count( $jsonData["postIds"]);
        $resultIds   = pushToResultArray($resultIds, $jsonData["postIds"]);
        $resultPosts = pushToResultArray1($resultPosts, $jsonData["posts"], $jsonData["postIds"]);
        
        curl_setopt_array($curl, array(
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_URL => $url_first.$resultIds[count($resultIds) - 1].$url_second,
            CURLOPT_USERAGENT => 'User Agent X'
        ));
    }
    // Close request to clear up some resources
    $result['ids']  = $resultIds;
    $result['data'] = $resultPosts;
    curl_close($curl);
    return $result;
}
function SaveDataToDB(){
    $con = DBConnect("localhost", "root", "", "PostsDb");
    if($con == false)
    {
        echo "Failed to connect to MySQL";
        return;
    } else {
        $ResultData = makeResult();
       
        $Data = $ResultData['data'];
        $Ids  = $ResultData['ids'];
        for($i = 0; $i < count($Data) ; $i++ )
        {
            $post_id       = $Data[$i]['id'];
            $title         = $Data[$i]['title'];
            $permalink     = $Data[$i]['permalink'];
            $author        = $Data[$i]['author'];
            $liveCommentsWebsocket = $Data[$i]['liveCommentsWebsocket'];
            $sql = "INSERT INTO posts (post_id, permalink, author, liveCommentsWebsocket) VALUES ('$post_id','$permalink', '$author', '$liveCommentsWebsocket' )";
            if ($con->query($sql) === TRUE) {
            } else {
                echo "Error: " . $sql . "<br>" . $con->error;
                break;
            }
        }
    }
}
SaveDataToDB();