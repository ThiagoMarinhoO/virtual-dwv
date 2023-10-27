<?php
function setup_curl_options($ch) {
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 360);
}
?>