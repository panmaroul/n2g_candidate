<?php
/*
 * Creates the routing key from the information gathered from
 * the API
 */
function routingKey($response){
    $gatewayEui = strval(hexdec($response->gatewayEui));
    $profileId = strval(hexdec($response->profileId));
    $endpointId = strval(hexdec($response->endpointId));
    $clusterId = strval(hexdec($response->clusterId));
    $attributeId = strval(hexdec($response->attributeId));
    $dot = '.';
    return $gatewayEui.$dot.$profileId.$dot.$endpointId.$dot.$clusterId.$dot.$attributeId;
}

/*
 * Creates the rest of the message
 */
function restMessage($response){
    return array('value' => $response->value,'timestamp'=>$response->timestamp);
}

